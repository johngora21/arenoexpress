<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Get payments
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Payment::with(['shipment', 'user']);

        // Filter by user role
        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        // Filter by payment type
        if ($request->has('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $payments = $query->orderBy('created_at', 'desc')
                         ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }

    /**
     * Create a new payment
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'shipment_id' => 'required|exists:shipments,id',
            'payment_type' => 'required|in:shipment_fee,product_payment,return_fee,insurance',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,card,mobile_money,bank_transfer',
            'gateway_response' => 'nullable|array',
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

            $payment = Payment::create([
                'shipment_id' => $request->shipment_id,
                'user_id' => $request->user()->id,
                'payment_type' => $request->payment_type,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'status' => Payment::STATUS_PENDING,
                'gateway_response' => $request->gateway_response,
            ]);

            // Update shipment payment status if this is a shipment fee payment
            if ($request->payment_type === Payment::TYPE_SHIPMENT_FEE) {
                $shipment = Shipment::find($request->shipment_id);
                $shipment->update(['payment_status' => Shipment::PAYMENT_STATUS_PAID]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment created successfully',
                'data' => [
                    'payment' => $payment->load(['shipment', 'user'])
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment details
     */
    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        
        $payment = Payment::with(['shipment', 'user'])
            ->where('id', $id)
            ->when(!$user->isAdmin(), function ($query) use ($user) {
                return $query->where('user_id', $user->id);
            })
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'payment' => $payment
            ]
        ]);
    }

    /**
     * Process payment (mark as completed)
     */
    public function processPayment(Request $request, $id): JsonResponse
    {
        $payment = Payment::findOrFail($id);
        
        if ($payment->isCompleted()) {
            return response()->json([
                'success' => false,
                'message' => 'Payment is already completed'
            ], 400);
        }

        $payment->markAsCompleted($request->gateway_response);

        return response()->json([
            'success' => true,
            'message' => 'Payment processed successfully',
            'data' => [
                'payment' => $payment->fresh()
            ]
        ]);
    }

    /**
     * Refund payment
     */
    public function refund(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $payment = Payment::findOrFail($id);
        
        if (!$payment->canBeRefunded()) {
            return response()->json([
                'success' => false,
                'message' => 'Payment cannot be refunded'
            ], 400);
        }

        $payment->refund($request->reason);

        return response()->json([
            'success' => true,
            'message' => 'Payment refunded successfully',
            'data' => [
                'payment' => $payment->fresh()
            ]
        ]);
    }
}
