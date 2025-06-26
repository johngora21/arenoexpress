<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Payment;
use App\Models\Hub;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Get admin dashboard data
     */
    public function dashboard(Request $request): JsonResponse
    {
        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();

        // Shipment statistics
        $totalShipments = Shipment::count();
        $todayShipments = Shipment::whereDate('created_at', $today)->count();
        $thisMonthShipments = Shipment::whereDate('created_at', '>=', $thisMonth)->count();
        $pendingShipments = Shipment::where('status', Shipment::STATUS_BOOKED)->count();
        $inTransitShipments = Shipment::whereIn('status', [
            Shipment::STATUS_IN_TRANSIT,
            Shipment::STATUS_OUT_FOR_DELIVERY
        ])->count();

        // Payment statistics
        $totalRevenue = Payment::where('status', Payment::STATUS_COMPLETED)->sum('amount');
        $todayRevenue = Payment::where('status', Payment::STATUS_COMPLETED)
            ->whereDate('payment_date', $today)
            ->sum('amount');
        $thisMonthRevenue = Payment::where('status', Payment::STATUS_COMPLETED)
            ->whereDate('payment_date', '>=', $thisMonth)
            ->sum('amount');

        // User statistics
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $totalAgents = User::where('role', User::ROLE_AGENT)->count();
        $totalDrivers = User::where('role', User::ROLE_DRIVER)->count();

        // Recent shipments
        $recentShipments = Shipment::with(['sender', 'receiver'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Status distribution
        $statusDistribution = Shipment::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'statistics' => [
                    'shipments' => [
                        'total' => $totalShipments,
                        'today' => $todayShipments,
                        'this_month' => $thisMonthShipments,
                        'pending' => $pendingShipments,
                        'in_transit' => $inTransitShipments,
                    ],
                    'revenue' => [
                        'total' => $totalRevenue,
                        'today' => $todayRevenue,
                        'this_month' => $thisMonthRevenue,
                    ],
                    'users' => [
                        'total' => $totalUsers,
                        'active' => $activeUsers,
                        'agents' => $totalAgents,
                        'drivers' => $totalDrivers,
                    ],
                ],
                'recent_shipments' => $recentShipments,
                'status_distribution' => $statusDistribution,
            ]
        ]);
    }

    /**
     * Get analytics data
     */
    public function analytics(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $startDate = now()->subDays($days);

        // Shipment trends
        $shipmentTrends = Shipment::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('count(*) as count')
        )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Revenue trends
        $revenueTrends = Payment::select(
            DB::raw('DATE(payment_date) as date'),
            DB::raw('sum(amount) as total')
        )
            ->where('status', Payment::STATUS_COMPLETED)
            ->where('payment_date', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Payment method distribution
        $paymentMethodDistribution = Payment::select('payment_method', DB::raw('count(*) as count'))
            ->where('status', Payment::STATUS_COMPLETED)
            ->groupBy('payment_method')
            ->get();

        // Top performing agents
        $topAgents = User::where('role', User::ROLE_AGENT)
            ->withCount(['agentShipments as shipments_count'])
            ->orderBy('shipments_count', 'desc')
            ->limit(10)
            ->get();

        // Top performing drivers
        $topDrivers = User::where('role', User::ROLE_DRIVER)
            ->withCount(['driverShipments as shipments_count'])
            ->orderBy('shipments_count', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'shipment_trends' => $shipmentTrends,
                'revenue_trends' => $revenueTrends,
                'payment_method_distribution' => $paymentMethodDistribution,
                'top_agents' => $topAgents,
                'top_drivers' => $topDrivers,
            ]
        ]);
    }

    /**
     * Get reports
     */
    public function reports(Request $request): JsonResponse
    {
        $reportType = $request->get('type', 'shipments');
        $startDate = $request->get('start_date', now()->subMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        switch ($reportType) {
            case 'shipments':
                $data = $this->getShipmentReport($startDate, $endDate);
                break;
            case 'revenue':
                $data = $this->getRevenueReport($startDate, $endDate);
                break;
            case 'users':
                $data = $this->getUserReport($startDate, $endDate);
                break;
            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid report type'
                ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Bulk operations
     */
    public function bulkOperations(Request $request): JsonResponse
    {
        $validator = $request->validate([
            'operation' => 'required|in:update_status,assign_driver,assign_agent',
            'shipment_ids' => 'required|array',
            'shipment_ids.*' => 'exists:shipments,id',
            'data' => 'required|array',
        ]);

        try {
            DB::beginTransaction();

            $operation = $request->operation;
            $shipmentIds = $request->shipment_ids;
            $data = $request->data;

            switch ($operation) {
                case 'update_status':
                    Shipment::whereIn('id', $shipmentIds)
                        ->update(['status' => $data['status']]);
                    break;
                case 'assign_driver':
                    Shipment::whereIn('id', $shipmentIds)
                        ->update(['driver_id' => $data['driver_id']]);
                    break;
                case 'assign_agent':
                    Shipment::whereIn('id', $shipmentIds)
                        ->update(['agent_id' => $data['agent_id']]);
                    break;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bulk operation completed successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Bulk operation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get shipment report
     */
    private function getShipmentReport($startDate, $endDate): array
    {
        $shipments = Shipment::with(['sender', 'receiver', 'agent', 'driver'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $statusCounts = $shipments->groupBy('status')->map->count();
        $dailyCounts = $shipments->groupBy(function ($shipment) {
            return $shipment->created_at->format('Y-m-d');
        })->map->count();

        return [
            'total_shipments' => $shipments->count(),
            'status_counts' => $statusCounts,
            'daily_counts' => $dailyCounts,
            'shipments' => $shipments,
        ];
    }

    /**
     * Get revenue report
     */
    private function getRevenueReport($startDate, $endDate): array
    {
        $payments = Payment::with(['shipment', 'user'])
            ->where('status', Payment::STATUS_COMPLETED)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->get();

        $totalRevenue = $payments->sum('amount');
        $paymentTypeCounts = $payments->groupBy('payment_type')->map->count();
        $paymentMethodCounts = $payments->groupBy('payment_method')->map->count();
        $dailyRevenue = $payments->groupBy(function ($payment) {
            return $payment->payment_date->format('Y-m-d');
        })->map->sum('amount');

        return [
            'total_revenue' => $totalRevenue,
            'payment_type_counts' => $paymentTypeCounts,
            'payment_method_counts' => $paymentMethodCounts,
            'daily_revenue' => $dailyRevenue,
            'payments' => $payments,
        ];
    }

    /**
     * Get user report
     */
    private function getUserReport($startDate, $endDate): array
    {
        $users = User::whereBetween('created_at', [$startDate, $endDate])->get();

        $roleCounts = $users->groupBy('role')->map->count();
        $activeUsers = $users->where('is_active', true)->count();
        $verifiedUsers = $users->where('is_verified', true)->count();

        return [
            'total_users' => $users->count(),
            'active_users' => $activeUsers,
            'verified_users' => $verifiedUsers,
            'role_counts' => $roleCounts,
            'users' => $users,
        ];
    }
}
