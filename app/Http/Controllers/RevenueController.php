<?php

namespace App\Http\Controllers;

use App\Models\Revenue;
use App\Models\AppUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\CashWithdrawal;
use App\Models\PaymentRequest;
use App\Enums\PaymentRequestStatus;

use App\Models\UserSubscription;

class RevenueController extends Controller
{
    /**
     * Generate comprehensive financial report (Report/Invoice).
     * Returns:
     * - Subscriptions count (Renewals)
     * - Total Income (Revenues)
     * - Total Withdrawals (Expenses)
     * - Net Profit
     * - Expired users who did not renew in this period
     * All grouped by currency for a specific period.
     */
    public function financialReport(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Revenue::class);

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        // 5. Get Expired Users (Who expired in this period and didn't renew)
        // Logic: Expiration date is between start and end date.
        // This assumes that if they renewed, the expiration date would have been pushed to the future.
        $expiredUsers = UserSubscription::with('user')
            ->whereBetween('expiration_at', [$startDate, $endDate])
            ->get()
            ->map(function ($sub) {
                return [
                    'user_id' => $sub->user_id,
                    'username' => $sub->radius_username ?? $sub->user->username ?? 'N/A',
                    'firstname' => $sub->firstname ?? $sub->user->firstname ?? 'N/A',
                    'phone' => $sub->mobile ?? $sub->user->phone ?? 'N/A',
                    'plan_name' => $sub->plan_name ?? 'N/A',
                    'expiration_date' => $sub->expiration_at->format('Y-m-d'),
                    'days_since_expiry' => (int) abs(now()->diffInDays($sub->expiration_at, false)), // Negative means past
                ];
            });

        $currencies = ['USD', 'SYP', 'TRY'];
        $report = [];

        foreach ($currencies as $currency) {
            // 1. Calculate Total Revenue (Income)
            $revenueQuery = Revenue::where('currency', $currency)
                ->whereBetween('payment_date', [$startDate, $endDate]);

            $totalRevenue = $revenueQuery->sum('amount');
            $revenueCount = $revenueQuery->count();

            // 2. Calculate Total Withdrawals (Expenses)
            $withdrawalQuery = CashWithdrawal::where('currency', $currency)
                ->whereBetween('withdrawal_date', [$startDate, $endDate]);

            $totalWithdrawals = $withdrawalQuery->sum('amount');
            $withdrawalCount = $withdrawalQuery->count();

            // 3. Count Renewals/Subscriptions (Approved Payment Requests)
            $subscriptionsQuery = PaymentRequest::where('currency', $currency)
                ->where('status', PaymentRequestStatus::APPROVED->value)
                ->whereBetween('updated_at', [$startDate, $endDate]);

            $renewalsCount = $subscriptionsQuery->count();

            // 4. Calculate Net Profit
            $netProfit = $totalRevenue - $totalWithdrawals;

            // Only add currency to report if there is any activity
            if ($totalRevenue > 0 || $totalWithdrawals > 0 || $renewalsCount > 0) {
                $report[] = [
                    'currency' => $currency,
                    'summary' => [
                        'income' => (float) $totalRevenue,
                        'expenses' => (float) $totalWithdrawals,
                        'net_profit' => (float) $netProfit,
                    ],
                    'activity' => [
                        'renewals_count' => (int) $renewalsCount,
                        'transactions_count' => (int) $revenueCount,
                        'withdrawals_count' => (int) $withdrawalCount,
                    ]
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'generated_at' => now()->toIso8601String(),
                'report' => $report,
                'expired_users' => $expiredUsers, // List of users who expired and didn't renew
                'note' => 'التقرير المالي الشامل (فاتورة)',
            ]
        ]);
    }

    /**
     * Get all revenues with filtering (admin/accountant only).
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Revenue::class);

        $query = Revenue::with(['user', 'paymentRequest']);

        // Filter by user_id
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('payment_date', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('payment_date', '<=', $request->to_date);
        }

        // Filter by payment_type
        if ($request->has('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }

        // Filter by currency
        if ($request->has('currency')) {
            $query->where('currency', $request->currency);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'payment_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $revenues = $query->paginate($request->get('per_page', 15));

        // Calculate summary
        $summaryQuery = Revenue::query();
        if ($request->has('user_id')) {
            $summaryQuery->where('user_id', $request->user_id);
        }
        if ($request->has('from_date')) {
            $summaryQuery->where('payment_date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $summaryQuery->where('payment_date', '<=', $request->to_date);
        }
        if ($request->has('payment_type')) {
            $summaryQuery->where('payment_type', $request->payment_type);
        }
        if ($request->has('currency')) {
            $summaryQuery->where('currency', $request->currency);
        }

        $totalRevenue = $summaryQuery->sum('amount');
        $totalTransactions = $summaryQuery->count();

        return response()->json([
            'success' => true,
            'data' => $revenues->items(),
            'summary' => [
                'total_revenue' => (float) $totalRevenue,
                'total_transactions' => $totalTransactions,
                'average_revenue' => $totalTransactions > 0 ? (float) ($totalRevenue / $totalTransactions) : 0,
            ],
            'meta' => [
                'current_page' => $revenues->currentPage(),
                'last_page' => $revenues->lastPage(),
                'per_page' => $revenues->perPage(),
                'total' => $revenues->total(),
            ],
        ]);
    }

    /**
     * Get revenue summary/statistics (admin/accountant only).
     * Optimized to use single aggregated query instead of multiple queries.
     */
    public function summary(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Revenue::class);

        // Build base query with filters
        $baseQuery = Revenue::query();

        if ($request->has('from_date')) {
            $baseQuery->where('payment_date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $baseQuery->where('payment_date', '<=', $request->to_date);
        }
        if ($request->has('user_id')) {
            $baseQuery->where('user_id', $request->user_id);
        }

        // Single optimized query for all statistics - حساب لكل عملة بشكل منفصل
        $stats = $baseQuery
            ->selectRaw('
                COUNT(*) as total_transactions,
                SUM(CASE WHEN payment_type = "online" THEN amount ELSE 0 END) as revenue_online,
                SUM(CASE WHEN payment_type = "cash" THEN amount ELSE 0 END) as revenue_cash,
                COUNT(CASE WHEN payment_type = "online" THEN 1 END) as count_online,
                COUNT(CASE WHEN payment_type = "cash" THEN 1 END) as count_cash,
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

        // Daily revenue (last 30 days) - لكل عملة بشكل منفصل
        $dailyRevenue = Revenue::query()
            ->where('payment_date', '>=', now()->subDays(30))
            ->when($request->has('from_date'), fn($q) => $q->where('payment_date', '>=', $request->from_date))
            ->when($request->has('to_date'), fn($q) => $q->where('payment_date', '<=', $request->to_date))
            ->when($request->has('user_id'), fn($q) => $q->where('user_id', $request->user_id))
            ->selectRaw('
                DATE(payment_date) as date,
                currency,
                SUM(amount) as total,
                COUNT(*) as count
            ')
            ->groupBy(DB::raw('DATE(payment_date)'), 'currency')
            ->orderBy('date', 'desc')
            ->orderBy('currency', 'asc')
            ->get()
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

        // Top users by revenue - لكل عملة بشكل منفصل
        $topUsersByCurrency = [];
        $currencies = ['USD', 'SYP', 'TRY'];

        foreach ($currencies as $currency) {
            $topUsers = Revenue::query()
                ->where('currency', $currency)
                ->when($request->has('from_date'), fn($q) => $q->where('payment_date', '>=', $request->from_date))
                ->when($request->has('to_date'), fn($q) => $q->where('payment_date', '<=', $request->to_date))
                ->when($request->has('user_id'), fn($q) => $q->where('user_id', $request->user_id))
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

        return response()->json([
            'success' => true,
            'data' => [
                'total_transactions' => (int) $stats->total_transactions,
                'by_payment_type' => [
                    [
                        'payment_type' => 'online',
                        'total' => (float) $stats->revenue_online,
                        'count' => (int) $stats->count_online,
                    ],
                    [
                        'payment_type' => 'cash',
                        'total' => (float) $stats->revenue_cash,
                        'count' => (int) $stats->count_cash,
                    ],
                ],
                'by_currency' => [
                    [
                        'currency' => 'USD',
                        'total' => (float) $stats->revenue_usd,
                        'count' => (int) $stats->count_usd,
                        'average' => (float) ($stats->avg_usd ?? 0),
                    ],
                    [
                        'currency' => 'SYP',
                        'total' => (float) $stats->revenue_syp,
                        'count' => (int) $stats->count_syp,
                        'average' => (float) ($stats->avg_syp ?? 0),
                    ],
                    [
                        'currency' => 'TRY',
                        'total' => (float) $stats->revenue_try,
                        'count' => (int) $stats->count_try,
                        'average' => (float) ($stats->avg_try ?? 0),
                    ],
                ],
                'daily_revenue' => $dailyRevenue,
                'top_users' => $topUsersByCurrency,
            ],
        ]);
    }

    /**
     * Get user's revenues (for users).
     */
    public function userRevenues(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = $user->revenues()->with('paymentRequest');

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('payment_date', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('payment_date', '<=', $request->to_date);
        }

        $revenues = $query->orderBy('payment_date', 'desc')->paginate($request->get('per_page', 15));

        $totalRevenue = $user->revenues()
            ->when($request->has('from_date'), fn($q) => $q->where('payment_date', '>=', $request->from_date))
            ->when($request->has('to_date'), fn($q) => $q->where('payment_date', '<=', $request->to_date))
            ->sum('amount');

        return response()->json([
            'success' => true,
            'data' => $revenues->items(),
            'summary' => [
                'total_revenue' => (float) $totalRevenue,
                'total_transactions' => $revenues->total(),
            ],
            'meta' => [
                'current_page' => $revenues->currentPage(),
                'last_page' => $revenues->lastPage(),
                'per_page' => $revenues->perPage(),
                'total' => $revenues->total(),
            ],
        ]);
    }

    /**
     * Get deferred payments amount (admin/accountant only).
     */
    public function deferredPayments(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Revenue::class);

        $query = \App\Models\PaymentRequest::where('is_deferred', true)
            ->where('is_paid', false)
            ->with(['user', 'paymentMethod']);

        // Filter by user_id
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('created_at', '<=', $request->to_date);
        }

        $deferredPayments = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        // Calculate total deferred amount
        $totalDeferredQuery = \App\Models\PaymentRequest::where('is_deferred', true)
            ->where('is_paid', false);

        if ($request->has('user_id')) {
            $totalDeferredQuery->where('user_id', $request->user_id);
        }
        if ($request->has('from_date')) {
            $totalDeferredQuery->where('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $totalDeferredQuery->where('created_at', '<=', $request->to_date);
        }

        $totalDeferredAmount = $totalDeferredQuery->sum('amount');
        $totalDeferredCount = $totalDeferredQuery->count();

        return response()->json([
            'success' => true,
            'data' => $deferredPayments->items(),
            'summary' => [
                'total_deferred_amount' => (float) $totalDeferredAmount,
                'total_deferred_count' => $totalDeferredCount,
            ],
            'meta' => [
                'current_page' => $deferredPayments->currentPage(),
                'last_page' => $deferredPayments->lastPage(),
                'per_page' => $deferredPayments->perPage(),
                'total' => $deferredPayments->total(),
            ],
        ]);
    }
}

