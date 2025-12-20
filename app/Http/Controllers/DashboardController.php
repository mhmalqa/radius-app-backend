<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Models\PaymentRequest;
use App\Models\Revenue;
use App\Models\MaintenanceRequest;
use App\Models\CashWithdrawal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics (admin/accountant only).
     * Optimized with caching and single queries.
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AppUser::class);

        // Cache key with filters
        $cacheKey = 'dashboard_stats_' . md5(json_encode($request->all()));
        
        // Cache for 5 minutes
        $stats = Cache::remember($cacheKey, 300, function () use ($request) {
            return $this->calculateStatistics($request);
        });

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Calculate dashboard statistics.
     */
    private function calculateStatistics(Request $request): array
    {
        // Apply date filters if provided
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        // 1. Users statistics - Single query with conditional aggregation
        $usersStats = DB::table('app_users')
            ->selectRaw('
                COUNT(*) as total_users,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users,
                SUM(CASE WHEN role = 0 THEN 1 ELSE 0 END) as regular_users,
                SUM(CASE WHEN role = 1 THEN 1 ELSE 0 END) as accountants,
                SUM(CASE WHEN role = 2 THEN 1 ELSE 0 END) as admins
            ')
            ->first();

        // 2. Payment requests statistics - Single query
        $paymentRequestsQuery = PaymentRequest::query();
        if ($fromDate) {
            $paymentRequestsQuery->where('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $paymentRequestsQuery->where('created_at', '<=', $toDate);
        }

        $paymentStats = $paymentRequestsQuery
            ->selectRaw('
                COUNT(*) as total_requests,
                SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as pending_requests,
                SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as approved_requests,
                SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as rejected_requests,
                SUM(CASE WHEN is_paid = 1 THEN 1 ELSE 0 END) as paid_requests,
                SUM(CASE WHEN is_deferred = 1 AND is_paid = 0 THEN 1 ELSE 0 END) as deferred_unpaid_requests
            ')
            ->first();

        // 3. Revenue statistics - Optimized single query
        $revenueQuery = Revenue::query();
        if ($fromDate) {
            $revenueQuery->where('payment_date', '>=', $fromDate);
        }
        if ($toDate) {
            $revenueQuery->where('payment_date', '<=', $toDate);
        }

        $revenueStats = $revenueQuery
            ->selectRaw('
                COUNT(*) as total_transactions,
                SUM(CASE WHEN currency = "USD" THEN amount ELSE 0 END) as revenue_usd,
                SUM(CASE WHEN currency = "SYP" THEN amount ELSE 0 END) as revenue_syp,
                SUM(CASE WHEN currency = "TRY" THEN amount ELSE 0 END) as revenue_try,
                COUNT(CASE WHEN currency = "USD" THEN 1 END) as count_usd,
                COUNT(CASE WHEN currency = "SYP" THEN 1 END) as count_syp,
                COUNT(CASE WHEN currency = "TRY" THEN 1 END) as count_try,
                AVG(CASE WHEN currency = "USD" THEN amount END) as avg_usd,
                AVG(CASE WHEN currency = "SYP" THEN amount END) as avg_syp,
                AVG(CASE WHEN currency = "TRY" THEN amount END) as avg_try
            ')
            ->first();

        // Get revenue by payment type and currency (detailed breakdown)
        $byPaymentType = [
            'online' => $this->getRevenueByPaymentTypeAndCurrency('online', $fromDate, $toDate),
            'cash' => $this->getRevenueByPaymentTypeAndCurrency('cash', $fromDate, $toDate),
        ];

        // 4. Maintenance requests statistics - Single query
        $maintenanceQuery = MaintenanceRequest::query();
        if ($fromDate) {
            $maintenanceQuery->where('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $maintenanceQuery->where('created_at', '<=', $toDate);
        }

        $maintenanceStats = $maintenanceQuery
            ->selectRaw('
                COUNT(*) as total_requests,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_requests,
                SUM(CASE WHEN status = "in_progress" THEN 1 ELSE 0 END) as in_progress_requests,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_requests,
                SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled_requests
            ')
            ->first();

        // 5. Recent activity - Last 30 days revenue by day and currency (optimized)
        $dailyRevenueRaw = Revenue::query()
            ->where('payment_date', '>=', now()->subDays(30))
            ->when($fromDate, fn($q) => $q->where('payment_date', '>=', $fromDate))
            ->when($toDate, fn($q) => $q->where('payment_date', '<=', $toDate))
            ->selectRaw('
                DATE(payment_date) as date,
                currency,
                SUM(amount) as total,
                COUNT(*) as count
            ')
            ->groupBy(DB::raw('DATE(payment_date)'), 'currency')
            ->orderBy('date', 'desc')
            ->orderBy('currency', 'asc')
            ->get();

        // Group by date with currencies
        $dailyRevenue = $dailyRevenueRaw
            ->groupBy('date')
            ->map(function ($dayGroup) {
                $date = $dayGroup->first()->date;
                return [
                    'date' => $date,
                    'currencies' => $dayGroup->map(function ($item) {
                        return [
                            'currency' => $item->currency,
                            'total' => (float) $item->total,
                            'count' => (int) $item->count,
                        ];
                    })->values()->toArray(),
                ];
            })
            ->values();

        // 6. Top users by revenue - لكل عملة بشكل منفصل
        $topUsersByCurrency = [];
        $currencies = ['USD', 'SYP', 'TRY'];
        
        foreach ($currencies as $currency) {
            $topUsers = Revenue::query()
                ->where('currency', $currency)
                ->when($fromDate, fn($q) => $q->where('payment_date', '>=', $fromDate))
                ->when($toDate, fn($q) => $q->where('payment_date', '<=', $toDate))
                ->join('app_users', 'revenues.user_id', '=', 'app_users.id')
                ->selectRaw('
                    app_users.id,
                    app_users.username,
                    app_users.phone,
                    SUM(revenues.amount) as total,
                    COUNT(revenues.id) as count
                ')
                ->groupBy('app_users.id', 'app_users.username', 'app_users.phone')
                ->orderBy('total', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($item) use ($currency) {
                    return [
                        'id' => $item->id,
                        'username' => $item->username,
                        'phone' => $item->phone,
                        'currency' => $currency,
                        'total' => (float) $item->total,
                        'count' => (int) $item->count,
                    ];
                });
            
            $topUsersByCurrency[$currency] = $topUsers;
        }

        // 7. Cash box statistics (revenues - withdrawals)
        $withdrawalQuery = CashWithdrawal::query();
        if ($fromDate) {
            $withdrawalQuery->where('withdrawal_date', '>=', $fromDate);
        }
        if ($toDate) {
            $withdrawalQuery->where('withdrawal_date', '<=', $toDate);
        }

        $withdrawalStats = $withdrawalQuery
            ->selectRaw('
                COUNT(*) as total_withdrawals,
                SUM(CASE WHEN currency = "USD" THEN amount ELSE 0 END) as withdrawals_usd,
                SUM(CASE WHEN currency = "SYP" THEN amount ELSE 0 END) as withdrawals_syp,
                SUM(CASE WHEN currency = "TRY" THEN amount ELSE 0 END) as withdrawals_try
            ')
            ->first();

        // Calculate cash box balance
        $totalRevenueUSD = (float) $revenueStats->revenue_usd;
        $totalRevenueSYP = (float) $revenueStats->revenue_syp;
        $totalRevenueTRY = (float) $revenueStats->revenue_try;
        $totalWithdrawalsUSD = (float) ($withdrawalStats->withdrawals_usd ?? 0);
        $totalWithdrawalsSYP = (float) ($withdrawalStats->withdrawals_syp ?? 0);
        $totalWithdrawalsTRY = (float) ($withdrawalStats->withdrawals_try ?? 0);

        return [
            'users' => [
                'total' => (int) $usersStats->total_users,
                'active' => (int) $usersStats->active_users,
                'regular' => (int) $usersStats->regular_users,
                'accountants' => (int) $usersStats->accountants,
                'admins' => (int) $usersStats->admins,
            ],
            'payment_requests' => [
                'total' => (int) $paymentStats->total_requests,
                'pending' => (int) $paymentStats->pending_requests,
                'approved' => (int) $paymentStats->approved_requests,
                'rejected' => (int) $paymentStats->rejected_requests,
                'paid' => (int) $paymentStats->paid_requests,
                'deferred_unpaid' => (int) $paymentStats->deferred_unpaid_requests,
            ],
            'revenues' => [
                'total_transactions' => (int) $revenueStats->total_transactions,
                'by_currency' => [
                    'USD' => [
                        'total' => (float) $revenueStats->revenue_usd,
                        'count' => (int) $revenueStats->count_usd,
                        'average' => (float) ($revenueStats->avg_usd ?? 0),
                    ],
                    'SYP' => [
                        'total' => (float) $revenueStats->revenue_syp,
                        'count' => (int) $revenueStats->count_syp,
                        'average' => (float) ($revenueStats->avg_syp ?? 0),
                    ],
                    'TRY' => [
                        'total' => (float) $revenueStats->revenue_try,
                        'count' => (int) $revenueStats->count_try,
                        'average' => (float) ($revenueStats->avg_try ?? 0),
                    ],
                ],
                'by_payment_type' => $byPaymentType,
                'daily_revenue' => $dailyRevenue,
                'top_users' => $topUsersByCurrency,
            ],
            'maintenance_requests' => [
                'total' => (int) $maintenanceStats->total_requests,
                'pending' => (int) $maintenanceStats->pending_requests,
                'in_progress' => (int) $maintenanceStats->in_progress_requests,
                'completed' => (int) $maintenanceStats->completed_requests,
                'cancelled' => (int) $maintenanceStats->cancelled_requests,
            ],
            'cash_box' => [
                'total_revenue' => [
                    'USD' => $totalRevenueUSD,
                    'SYP' => $totalRevenueSYP,
                    'TRY' => $totalRevenueTRY,
                ],
                'total_withdrawals' => [
                    'USD' => $totalWithdrawalsUSD,
                    'SYP' => $totalWithdrawalsSYP,
                    'TRY' => $totalWithdrawalsTRY,
                ],
                'balance' => [
                    'USD' => $totalRevenueUSD - $totalWithdrawalsUSD,
                    'SYP' => $totalRevenueSYP - $totalWithdrawalsSYP,
                    'TRY' => $totalRevenueTRY - $totalWithdrawalsTRY,
                ],
                'total_withdrawals_count' => (int) ($withdrawalStats->total_withdrawals ?? 0),
            ],
        ];
    }

    /**
     * Get revenue by payment type and currency.
     * Returns detailed breakdown for each currency (USD, SYP, TRY).
     */
    private function getRevenueByPaymentTypeAndCurrency(string $paymentType, ?string $fromDate = null, ?string $toDate = null): array
    {
        $query = Revenue::query()->where('payment_type', $paymentType);

        // Apply date filters
        if ($fromDate) {
            $query->where('payment_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->where('payment_date', '<=', $toDate);
        }

        // Group by currency and calculate totals
        $results = $query
            ->selectRaw('
                currency,
                SUM(amount) as total,
                COUNT(*) as count
            ')
            ->groupBy('currency')
            ->get()
            ->keyBy('currency');

        // Build response with all currencies (even if 0)
        $byCurrency = [];
        $currencies = ['USD', 'SYP', 'TRY'];
        
        foreach ($currencies as $currency) {
            $result = $results->get($currency);
            $byCurrency[$currency] = [
                'total' => (float) ($result->total ?? 0),
                'count' => (int) ($result->count ?? 0),
            ];
        }

        return $byCurrency;
    }

    /**
     * Clear dashboard cache.
     */
    public function clearCache(): JsonResponse
    {
        Cache::flush(); // Or use pattern matching for dashboard keys only
        
        return response()->json([
            'success' => true,
            'message' => 'تم مسح الكاش بنجاح',
        ]);
    }
}

