<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DriverAssignment;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class DriverController extends Controller
{
    /**
     * Get driver assignments
     */
    public function getAssignments(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $assignments = DriverAssignment::with(['shipment.sender', 'shipment.receiver', 'vehicle'])
            ->where('driver_id', $user->id)
            ->orderBy('assigned_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $assignments
        ]);
    }

    /**
     * Accept assignment
     */
    public function acceptAssignment(Request $request, $id): JsonResponse
    {
        $assignment = DriverAssignment::findOrFail($id);
        
        if ($assignment->driver_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        if (!$assignment->canBeAccepted()) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment cannot be accepted'
            ], 400);
        }

        $assignment->accept();

        return response()->json([
            'success' => true,
            'message' => 'Assignment accepted successfully',
            'data' => [
                'assignment' => $assignment->fresh(['shipment', 'vehicle'])
            ]
        ]);
    }

    /**
     * Complete assignment
     */
    public function completeAssignment(Request $request, $id): JsonResponse
    {
        $assignment = DriverAssignment::findOrFail($id);
        
        if ($assignment->driver_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        if (!$assignment->canBeCompleted()) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment cannot be completed'
            ], 400);
        }

        $assignment->complete();

        return response()->json([
            'success' => true,
            'message' => 'Assignment completed successfully',
            'data' => [
                'assignment' => $assignment->fresh(['shipment', 'vehicle'])
            ]
        ]);
    }

    /**
     * Pickup shipment
     */
    public function pickupShipment(Request $request, $id): JsonResponse
    {
        $shipment = Shipment::findOrFail($id);
        $user = $request->user();

        if ($shipment->driver_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        if (!$shipment->canBePickedUp()) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment cannot be picked up'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'location' => 'nullable|string',
            'notes' => 'nullable|string',
            'photos' => 'nullable|array',
            'photos.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $shipment->update([
            'status' => Shipment::STATUS_PICKED_UP,
            'pickup_date' => now(),
        ]);

        // Add photos to packages if provided
        if ($request->has('photos')) {
            foreach ($shipment->packages as $index => $package) {
                if (isset($request->photos[$index])) {
                    $package->addPhoto($request->photos[$index]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Shipment picked up successfully',
            'data' => [
                'shipment' => $shipment->fresh(['packages'])
            ]
        ]);
    }

    /**
     * Deliver shipment
     */
    public function deliverShipment(Request $request, $id): JsonResponse
    {
        $shipment = Shipment::findOrFail($id);
        $user = $request->user();

        if ($shipment->driver_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        if (!$shipment->canBeDelivered()) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment cannot be delivered'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'delivery_type' => 'required|in:delivered,picked_up_by_receiver',
            'location' => 'nullable|string',
            'notes' => 'nullable|string',
            'signature' => 'nullable|string',
            'photos' => 'nullable|array',
            'photos.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $deliveryStatus = $request->delivery_type === 'delivered' 
            ? Shipment::STATUS_DELIVERED 
            : Shipment::STATUS_PICKED_UP_BY_RECEIVER;

        $shipment->update([
            'status' => $deliveryStatus,
            'delivery_date' => now(),
        ]);

        // Add photos to packages if provided
        if ($request->has('photos')) {
            foreach ($shipment->packages as $index => $package) {
                if (isset($request->photos[$index])) {
                    $package->addPhoto($request->photos[$index]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Shipment delivered successfully',
            'data' => [
                'shipment' => $shipment->fresh(['packages'])
            ]
        ]);
    }
}
