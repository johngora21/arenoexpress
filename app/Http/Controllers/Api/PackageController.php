<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class PackageController extends Controller
{
    /**
     * Get packages for a shipment
     */
    public function index(Request $request, $shipmentId): JsonResponse
    {
        $shipment = Shipment::findOrFail($shipmentId);
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

        $packages = $shipment->packages;

        return response()->json([
            'success' => true,
            'data' => [
                'packages' => $packages
            ]
        ]);
    }

    /**
     * Add package to shipment
     */
    public function store(Request $request, $shipmentId): JsonResponse
    {
        $shipment = Shipment::findOrFail($shipmentId);
        $user = $request->user();

        // Only sender, agent, or admin can add packages
        if (!$user->isAdmin() && 
            $shipment->sender_id !== $user->id && 
            !$user->isAgent()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'description' => 'required|string',
            'weight' => 'required|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'is_fragile' => 'boolean',
            'insurance_amount' => 'nullable|numeric|min:0',
            'declared_value' => 'nullable|numeric|min:0',
            'special_instructions' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $package = Package::create([
            'shipment_id' => $shipmentId,
            'description' => $request->description,
            'weight' => $request->weight,
            'length' => $request->length,
            'width' => $request->width,
            'height' => $request->height,
            'is_fragile' => $request->boolean('is_fragile', false),
            'insurance_amount' => $request->insurance_amount ?? 0,
            'declared_value' => $request->declared_value ?? 0,
            'special_instructions' => $request->special_instructions,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Package added successfully',
            'data' => [
                'package' => $package
            ]
        ], 201);
    }

    /**
     * Update package
     */
    public function update(Request $request, $id): JsonResponse
    {
        $package = Package::with('shipment')->findOrFail($id);
        $user = $request->user();

        // Check if user has access to this package
        if (!$user->isAdmin() && 
            $package->shipment->sender_id !== $user->id && 
            !$user->isAgent()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'description' => 'sometimes|string',
            'weight' => 'sometimes|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'is_fragile' => 'boolean',
            'insurance_amount' => 'nullable|numeric|min:0',
            'declared_value' => 'nullable|numeric|min:0',
            'special_instructions' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $package->update($request->only([
            'description', 'weight', 'length', 'width', 'height',
            'is_fragile', 'insurance_amount', 'declared_value', 'special_instructions'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Package updated successfully',
            'data' => [
                'package' => $package->fresh()
            ]
        ]);
    }

    /**
     * Delete package
     */
    public function destroy($id): JsonResponse
    {
        $package = Package::with('shipment')->findOrFail($id);
        $user = $request->user();

        // Only sender or admin can delete packages
        if (!$user->isAdmin() && $package->shipment->sender_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $package->delete();

        return response()->json([
            'success' => true,
            'message' => 'Package deleted successfully'
        ]);
    }

    /**
     * Add photo to package
     */
    public function addPhoto(Request $request, $id): JsonResponse
    {
        $package = Package::findOrFail($id);
        $user = $request->user();

        // Check if user has access to this package
        if (!$user->isAdmin() && 
            $package->shipment->sender_id !== $user->id && 
            !$user->isAgent() && 
            !$user->isDriver()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'photo' => 'required|string', // Base64 encoded image or URL
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $package->addPhoto($request->photo);

        return response()->json([
            'success' => true,
            'message' => 'Photo added successfully',
            'data' => [
                'package' => $package->fresh()
            ]
        ]);
    }
}
