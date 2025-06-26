<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\User;
use App\Models\AgentStation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AgentController extends Controller
{
    /**
     * Get agent's shipments
     */
    public function getShipments(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $shipments = Shipment::with(['sender', 'receiver', 'packages'])
            ->where('agent_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $shipments
        ]);
    }

    /**
     * Create walk-in shipment
     */
    public function createWalkInShipment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sender_name' => 'required|string|max:255',
            'sender_phone' => 'required|string|max:20',
            'sender_email' => 'nullable|email',
            'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'required|string|max:20',
            'receiver_email' => 'nullable|email',
            'pickup_address' => 'required|string',
            'delivery_address' => 'required|string',
            'shipment_fee' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'is_business_courier' => 'boolean',
            'packages' => 'required|array|min:1',
            'packages.*.description' => 'required|string',
            'packages.*.weight' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Create or find sender
        $sender = User::firstOrCreate(
            ['phone' => $request->sender_phone],
            [
                'name' => $request->sender_name,
                'email' => $request->sender_email,
                'role' => User::ROLE_SENDER,
                'is_verified' => false,
                'is_active' => true,
            ]
        );

        // Create or find receiver
        $receiver = User::firstOrCreate(
            ['phone' => $request->receiver_phone],
            [
                'name' => $request->receiver_name,
                'email' => $request->receiver_email,
                'role' => User::ROLE_RECEIVER,
                'is_verified' => false,
                'is_active' => true,
            ]
        );

        // Create shipment
        $shipment = Shipment::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'agent_id' => $request->user()->id,
            'pickup_address' => $request->pickup_address,
            'delivery_address' => $request->delivery_address,
            'shipment_fee' => $request->shipment_fee,
            'total_amount' => $request->total_amount,
            'is_business_courier' => $request->boolean('is_business_courier', false),
            'status' => Shipment::STATUS_BOOKED,
            'payment_status' => Shipment::PAYMENT_STATUS_PENDING,
        ]);

        // Create packages
        foreach ($request->packages as $packageData) {
            $shipment->packages()->create([
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

        return response()->json([
            'success' => true,
            'message' => 'Walk-in shipment created successfully',
            'data' => [
                'shipment' => $shipment->load(['sender', 'receiver', 'packages'])
            ]
        ], 201);
    }

    /**
     * Receive shipment at agent station
     */
    public function receiveShipment(Request $request, $id): JsonResponse
    {
        $shipment = Shipment::findOrFail($id);
        
        if ($shipment->agent_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $shipment->update([
            'status' => Shipment::STATUS_RECEIVED_AT_AGENT
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shipment received successfully',
            'data' => [
                'shipment' => $shipment->fresh()
            ]
        ]);
    }

    /**
     * Get agent manifest
     */
    public function getManifest(Request $request): JsonResponse
    {
        $user = $request->user();
        $date = $request->get('date', now()->format('Y-m-d'));

        $manifest = Shipment::with(['sender', 'receiver', 'packages'])
            ->where('agent_id', $user->id)
            ->whereDate('created_at', $date)
            ->get();

        $summary = [
            'total_shipments' => $manifest->count(),
            'total_packages' => $manifest->sum(function ($shipment) {
                return $shipment->packages->count();
            }),
            'total_weight' => $manifest->sum(function ($shipment) {
                return $manifest->packages->sum('weight');
            }),
            'total_revenue' => $manifest->sum('shipment_fee'),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'summary' => $summary,
                'manifest' => $manifest
            ]
        ]);
    }
}
