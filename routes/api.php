<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ShipmentController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\TrackingController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\AdminController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/profile', [AuthController::class, 'profile']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);

    // Shipment routes
    Route::get('/shipments', [ShipmentController::class, 'index']);
    Route::post('/shipments', [ShipmentController::class, 'store']);
    Route::get('/shipments/{trackingNumber}', [ShipmentController::class, 'show']);
    Route::post('/shipments/{id}/status', [ShipmentController::class, 'updateStatus']);
    Route::delete('/shipments/{id}', [ShipmentController::class, 'destroy']);

    // Package routes
    Route::get('/shipments/{shipmentId}/packages', [PackageController::class, 'index']);
    Route::post('/shipments/{shipmentId}/packages', [PackageController::class, 'store']);
    Route::put('/packages/{id}', [PackageController::class, 'update']);
    Route::delete('/packages/{id}', [PackageController::class, 'destroy']);

    // Payment routes
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::get('/payments/{id}', [PaymentController::class, 'show']);
    Route::post('/payments/{id}/refund', [PaymentController::class, 'refund']);

    // Tracking routes
    Route::get('/tracking/{trackingNumber}', [TrackingController::class, 'track']);
    Route::get('/shipments/{id}/tracking', [TrackingController::class, 'getTrackingHistory']);

    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    // Agent routes
    Route::middleware('role:agent')->group(function () {
        Route::get('/agent/shipments', [AgentController::class, 'getShipments']);
        Route::post('/agent/shipments', [AgentController::class, 'createWalkInShipment']);
        Route::put('/agent/shipments/{id}/receive', [AgentController::class, 'receiveShipment']);
        Route::get('/agent/manifest', [AgentController::class, 'getManifest']);
    });

    // Driver routes
    Route::middleware('role:driver')->group(function () {
        Route::get('/driver/assignments', [DriverController::class, 'getAssignments']);
        Route::put('/driver/assignments/{id}/accept', [DriverController::class, 'acceptAssignment']);
        Route::put('/driver/assignments/{id}/complete', [DriverController::class, 'completeAssignment']);
        Route::post('/driver/shipments/{id}/pickup', [DriverController::class, 'pickupShipment']);
        Route::post('/driver/shipments/{id}/delivery', [DriverController::class, 'deliverShipment']);
    });

    // Admin routes
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/admin/analytics', [AdminController::class, 'analytics']);
        Route::get('/admin/reports', [AdminController::class, 'reports']);
        Route::post('/admin/bulk-operations', [AdminController::class, 'bulkOperations']);
    });
});

// Public tracking route (no authentication required)
Route::get('/track/{trackingNumber}', [TrackingController::class, 'publicTrack']); 