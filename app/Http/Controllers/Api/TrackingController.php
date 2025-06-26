<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TrackingController extends Controller
{
    /**
     * Track shipment by tracking number (authenticated)
     */
    public function track(Request $request, $trackingNumber): JsonResponse
    {
        $shipment = Shipment::with([
            'sender', 'receiver', 'packages', 'trackingEvents'
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
                'shipment' => $shipment,
                'tracking_events' => $shipment->trackingEvents()->orderBy('timestamp', 'desc')->get()
            ]
        ]);
    }

    /**
     * Public tracking (no authentication required)
     */
    public function publicTrack($trackingNumber): JsonResponse
    {
        $shipment = Shipment::with([
            'packages', 'trackingEvents'
        ])->where('tracking_number', $trackingNumber)->first();

        if (!$shipment) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found'
            ], 404);
        }

        // For public tracking, only return basic information
        $trackingEvents = $shipment->trackingEvents()
            ->orderBy('timestamp', 'desc')
            ->get(['event_type', 'location', 'description', 'timestamp']);

        return response()->json([
            'success' => true,
            'data' => [
                'tracking_number' => $shipment->tracking_number,
                'status' => $shipment->status,
                'pickup_address' => $shipment->pickup_address,
                'delivery_address' => $shipment->delivery_address,
                'tracking_events' => $trackingEvents
            ]
        ]);
    }

    /**
     * Get tracking history for a shipment
     */
    public function getTrackingHistory(Request $request, $id): JsonResponse
    {
        $shipment = Shipment::findOrFail($id);
        $user = $request->user();

        // Check if user has access to this shipment
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

        $trackingEvents = $shipment->trackingEvents()
            ->with('createdBy')
            ->orderBy('timestamp', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'shipment' => $shipment,
                'tracking_history' => $trackingEvents
            ]
        ]);
    }
}
