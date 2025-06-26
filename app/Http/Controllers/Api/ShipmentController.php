<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Notification;
use App\Models\TrackingEvent;
use App\Models\ShipmentStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ShipmentController extends Controller
{
    /**
     * Get all shipments (with filters)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Shipment::with(['sender', 'receiver', 'agent', 'driver', 'packages']);

        // Filter by user role
        if ($user->isSender()) {
            $query->where('sender_id', $user->id);
        } elseif ($user->isReceiver()) {
            $query->where('receiver_id', $user->id);
        } elseif ($user->isAgent()) {
            $query->where('agent_id', $user->id);
        } elseif ($user->isDriver()) {
            $query->where('driver_id', $user->id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by business courier
        if ($request->has('is_business_courier')) {
            $query->where('is_business_courier', $request->boolean('is_business_courier'));
        }

        // Search by tracking number
        if ($request->has('tracking_number')) {
            $query->where('tracking_number', 'like', '%' . $request->tracking_number . '%');
        }

        // Date range filter
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $shipments = $query->orderBy('created_at', 'desc')
                          ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $shipments
        ]);
    }

    /**
     * Create a new shipment
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|exists:users,id',
            'pickup_address' => 'required|string',
            'delivery_address' => 'required|string',
            'shipment_fee' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'is_business_courier' => 'boolean',
            'special_instructions' => 'nullable|string',
            'pickup_date' => 'nullable|date',
            'packages' => 'required|array|min:1',
            'packages.*.description' => 'required|string',
            'packages.*.weight' => 'required|numeric|min:0',
            'packages.*.length' => 'nullable|numeric|min:0',
            'packages.*.width' => 'nullable|numeric|min:0',
            'packages.*.height' => 'nullable|numeric|min:0',
            'packages.*.is_fragile' => 'boolean',
            'packages.*.insurance_amount' => 'nullable|numeric|min:0',
            'packages.*.declared_value' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $shipment = Shipment::create([
                'sender_id' => $request->user()->id,
                'receiver_id' => $request->receiver_id,
                'pickup_address' => $request->pickup_address,
                'delivery_address' => $request->delivery_address,
                'shipment_fee' => $request->shipment_fee,
                'total_amount' => $request->total_amount,
                'is_business_courier' => $request->boolean('is_business_courier', false),
                'special_instructions' => $request->special_instructions,
                'pickup_date' => $request->pickup_date,
                'status' => Shipment::STATUS_BOOKED,
                'payment_status' => Shipment::PAYMENT_STATUS_PENDING,
            ]);

            // Create packages
            foreach ($request->packages as $packageData) {
                Package::create([
                    'shipment_id' => $shipment->id,
                    'description' => $packageData['description'],
                    'weight' => $packageData['weight'],
                    'length' => $packageData['length'] ?? null,
                    'width' => $packageData['width'] ?? null,
                    'height' => $packageData['height'] ?? null,
                    'is_fragile' => $packageData['is_fragile'] ?? false,
                    'insurance_amount' => $packageData['insurance_amount'] ?? 0,
                    'declared_value' => $packageData['declared_value'] ?? 0,
                ]);
            }

            // Create initial status
            ShipmentStatus::create([
                'shipment_id' => $shipment->id,
                'status' => Shipment::STATUS_BOOKED,
                'updated_by' => $request->user()->id,
            ]);

            // Create tracking event
            TrackingEvent::create([
                'shipment_id' => $shipment->id,
                'event_type' => TrackingEvent::EVENT_BOOKED,
                'created_by' => $request->user()->id,
            ]);

            // Send notifications
            Notification::createShipmentBooked($shipment->sender_id, $shipment->id);
            Notification::createShipmentBooked($shipment->receiver_id, $shipment->id);

            DB::commit();

            $shipment->load(['sender', 'receiver', 'packages']);

            return response()->json([
                'success' => true,
                'message' => 'Shipment created successfully',
                'data' => [
                    'shipment' => $shipment
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create shipment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get shipment by tracking number
     */
    public function show(Request $request, $trackingNumber): JsonResponse
    {
        $shipment = Shipment::with([
            'sender', 'receiver', 'agent', 'driver', 'hub', 'route',
            'packages', 'payments', 'statuses', 'trackingEvents'
        ])->where('tracking_number', $trackingNumber)->first();

        if (!$shipment) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found'
            ], 404);
        }

        // Check if user has access to this shipment
        $user = $request->user();
        if (!$user->isAdmin() && 
            $shipment->sender_id !== $user->id && 
            $shipment->receiver_id !== $user->id &&
            $shipment->agent_id !== $user->id &&
            $shipment->driver_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'shipment' => $shipment
            ]
        ]);
    }

    /**
     * Update shipment status
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        $shipment = Shipment::findOrFail($id);
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:' . implode(',', [
                Shipment::STATUS_AWAITING_PICKUP,
                Shipment::STATUS_PICKED_UP,
                Shipment::STATUS_RECEIVED_AT_AGENT,
                Shipment::STATUS_IN_TRANSIT,
                Shipment::STATUS_ARRIVED_AT_HUB,
                Shipment::STATUS_DISPATCHED_TO_DESTINATION,
                Shipment::STATUS_ARRIVED_AT_DESTINATION,
                Shipment::STATUS_OUT_FOR_DELIVERY,
                Shipment::STATUS_DELIVERED,
                Shipment::STATUS_PICKED_UP_BY_RECEIVER,
                Shipment::STATUS_RETURNED
            ]),
            'location' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $oldStatus = $shipment->status;
            $newStatus = $request->status;

            // Update shipment status
            $shipment->update(['status' => $newStatus]);

            // Create status record
            ShipmentStatus::create([
                'shipment_id' => $shipment->id,
                'status' => $newStatus,
                'location' => $request->location,
                'notes' => $request->notes,
                'updated_by' => $user->id,
            ]);

            // Create tracking event
            $eventType = $this->getEventTypeFromStatus($newStatus);
            TrackingEvent::create([
                'shipment_id' => $shipment->id,
                'event_type' => $eventType,
                'location' => $request->location,
                'description' => $request->notes,
                'created_by' => $user->id,
            ]);

            // Send notifications based on status change
            $this->sendStatusNotifications($shipment, $oldStatus, $newStatus);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Shipment status updated successfully',
                'data' => [
                    'shipment' => $shipment->fresh(['sender', 'receiver', 'packages'])
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update shipment status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete shipment
     */
    public function destroy($id): JsonResponse
    {
        $shipment = Shipment::findOrFail($id);
        $user = $request->user();

        // Only sender or admin can delete shipment
        if (!$user->isAdmin() && $shipment->sender_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        // Only allow deletion if shipment is still in booked status
        if ($shipment->status !== Shipment::STATUS_BOOKED) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete shipment that has already been processed'
            ], 400);
        }

        $shipment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Shipment deleted successfully'
        ]);
    }

    /**
     * Get event type from status
     */
    private function getEventTypeFromStatus(string $status): string
    {
        $statusToEventMap = [
            Shipment::STATUS_BOOKED => TrackingEvent::EVENT_BOOKED,
            Shipment::STATUS_AWAITING_PICKUP => TrackingEvent::EVENT_PICKUP_SCHEDULED,
            Shipment::STATUS_PICKED_UP => TrackingEvent::EVENT_PICKUP_COMPLETED,
            Shipment::STATUS_RECEIVED_AT_AGENT => TrackingEvent::EVENT_RECEIVED_AT_AGENT,
            Shipment::STATUS_IN_TRANSIT => TrackingEvent::EVENT_IN_TRANSIT,
            Shipment::STATUS_ARRIVED_AT_HUB => TrackingEvent::EVENT_ARRIVED_AT_HUB,
            Shipment::STATUS_DISPATCHED_TO_DESTINATION => TrackingEvent::EVENT_DISPATCHED,
            Shipment::STATUS_ARRIVED_AT_DESTINATION => TrackingEvent::EVENT_ARRIVED_AT_DESTINATION,
            Shipment::STATUS_OUT_FOR_DELIVERY => TrackingEvent::EVENT_OUT_FOR_DELIVERY,
            Shipment::STATUS_DELIVERED => TrackingEvent::EVENT_DELIVERED,
            Shipment::STATUS_PICKED_UP_BY_RECEIVER => TrackingEvent::EVENT_PICKED_UP_BY_RECEIVER,
            Shipment::STATUS_RETURNED => TrackingEvent::EVENT_RETURNED,
        ];

        return $statusToEventMap[$status] ?? TrackingEvent::EVENT_BOOKED;
    }

    /**
     * Send notifications based on status change
     */
    private function sendStatusNotifications(Shipment $shipment, string $oldStatus, string $newStatus): void
    {
        $notificationMap = [
            Shipment::STATUS_PICKED_UP => [
                'sender' => Notification::TYPE_PICKUP_COMPLETED,
                'receiver' => Notification::TYPE_PICKUP_COMPLETED,
            ],
            Shipment::STATUS_IN_TRANSIT => [
                'sender' => Notification::TYPE_IN_TRANSIT,
                'receiver' => Notification::TYPE_IN_TRANSIT,
            ],
            Shipment::STATUS_OUT_FOR_DELIVERY => [
                'sender' => Notification::TYPE_OUT_FOR_DELIVERY,
                'receiver' => Notification::TYPE_OUT_FOR_DELIVERY,
            ],
            Shipment::STATUS_DELIVERED => [
                'sender' => Notification::TYPE_DELIVERED,
                'receiver' => Notification::TYPE_DELIVERED,
            ],
            Shipment::STATUS_PICKED_UP_BY_RECEIVER => [
                'sender' => Notification::TYPE_PICKED_UP,
                'receiver' => Notification::TYPE_PICKED_UP,
            ],
        ];

        if (isset($notificationMap[$newStatus])) {
            $notifications = $notificationMap[$newStatus];
            
            if (isset($notifications['sender'])) {
                Notification::create([
                    'user_id' => $shipment->sender_id,
                    'shipment_id' => $shipment->id,
                    'type' => $notifications['sender'],
                    'title' => 'Shipment Status Update',
                    'message' => "Your shipment {$shipment->tracking_number} status has been updated to {$newStatus}.",
                ]);
            }

            if (isset($notifications['receiver'])) {
                Notification::create([
                    'user_id' => $shipment->receiver_id,
                    'shipment_id' => $shipment->id,
                    'type' => $notifications['receiver'],
                    'title' => 'Shipment Status Update',
                    'message' => "Your shipment {$shipment->tracking_number} status has been updated to {$newStatus}.",
                ]);
            }
        }
    }
}
