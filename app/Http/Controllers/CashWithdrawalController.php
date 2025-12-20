<?php

namespace App\Http\Controllers;

use App\Http\Requests\CashWithdrawal\CreateCashWithdrawalRequest;
use App\Http\Resources\CashWithdrawalResource;
use App\Models\CashWithdrawal;
use App\Models\Revenue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashWithdrawalController extends Controller
{
    /**
     * Get all cash withdrawals with filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CashWithdrawal::class);

        $query = CashWithdrawal::with('withdrawnBy')
            ->orderBy('withdrawal_date', 'desc')
            ->orderBy('created_at', 'desc');

        // Filter by currency
        if ($request->has('currency')) {
            $query->where('currency', $request->currency);
        }

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('withdrawal_date', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('withdrawal_date', '<=', $request->to_date);
        }

        // Filter by user who made withdrawal
        if ($request->has('withdrawn_by')) {
            $query->where('withdrawn_by', $request->withdrawn_by);
        }

        // Search by reason or reference number
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $withdrawals = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => CashWithdrawalResource::collection($withdrawals->items()),
            'meta' => [
                'current_page' => $withdrawals->currentPage(),
                'last_page' => $withdrawals->lastPage(),
                'per_page' => $withdrawals->perPage(),
                'total' => $withdrawals->total(),
            ],
        ]);
    }

    /**
     * Create new cash withdrawal.
     */
    public function store(CreateCashWithdrawalRequest $request): JsonResponse
    {
        $this->authorize('create', CashWithdrawal::class);

        $withdrawal = DB::transaction(function () use ($request) {
            return CashWithdrawal::create([
                'withdrawn_by' => $request->user()->id,
                'amount' => $request->amount,
                'currency' => $request->currency,
                'reason' => $request->reason,
                'description' => $request->description,
                'reference_number' => $request->reference_number,
                'category' => $request->category,
                'withdrawal_date' => $request->withdrawal_date,
                'attachments' => $request->attachments ?? [],
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل السحب بنجاح',
            'data' => new CashWithdrawalResource($withdrawal->load('withdrawnBy')),
        ], 201);
    }

    /**
     * Get single cash withdrawal.
     */
    public function show(CashWithdrawal $cashWithdrawal): JsonResponse
    {
        $this->authorize('view', $cashWithdrawal);

        return response()->json([
            'success' => true,
            'data' => new CashWithdrawalResource($cashWithdrawal->load('withdrawnBy')),
        ]);
    }

    /**
     * Update cash withdrawal.
     */
    public function update(Request $request, CashWithdrawal $cashWithdrawal): JsonResponse
    {
        $this->authorize('update', $cashWithdrawal);

        $validated = $request->validate([
            'amount' => ['sometimes', 'numeric', 'min:0.01', 'max:999999999.99'],
            'currency' => ['sometimes', 'string', 'in:USD,SYP,TRY'],
            'reason' => ['sometimes', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:1000'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'category' => ['sometimes', 'string', 'in:operational,maintenance,salary,utilities,supplies,marketing,emergency,other'],
            'withdrawal_date' => ['sometimes', 'date', 'before_or_equal:today'],
            'attachments' => ['nullable', 'array'],
        ]);

        $cashWithdrawal->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث السحب بنجاح',
            'data' => new CashWithdrawalResource($cashWithdrawal->fresh()->load('withdrawnBy')),
        ]);
    }

    /**
     * Delete cash withdrawal.
     */
    public function destroy(CashWithdrawal $cashWithdrawal): JsonResponse
    {
        $this->authorize('delete', $cashWithdrawal);

        $cashWithdrawal->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف السحب بنجاح',
        ]);
    }

    /**
     * Get cash box balance (revenues - withdrawals).
     */
    public function getCashBoxBalance(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CashWithdrawal::class);

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        // Calculate total revenues
        $revenueQuery = Revenue::query();
        if ($fromDate) {
            $revenueQuery->where('payment_date', '>=', $fromDate);
        }
        if ($toDate) {
            $revenueQuery->where('payment_date', '<=', $toDate);
        }

        $revenues = $revenueQuery
            ->selectRaw('
                currency,
                SUM(amount) as total
            ')
            ->groupBy('currency')
            ->get()
            ->keyBy('currency');

        // Calculate total withdrawals
        $withdrawalQuery = CashWithdrawal::query();
        if ($fromDate) {
            $withdrawalQuery->where('withdrawal_date', '>=', $fromDate);
        }
        if ($toDate) {
            $withdrawalQuery->where('withdrawal_date', '<=', $toDate);
        }

        $withdrawals = $withdrawalQuery
            ->selectRaw('
                currency,
                SUM(amount) as total
            ')
            ->groupBy('currency')
            ->get()
            ->keyBy('currency');

        // Calculate balance for each currency
        $balance = [];
        $currencies = ['USD', 'SYP', 'TRY'];

        foreach ($currencies as $currency) {
            $totalRevenue = (float) ($revenues->get($currency)->total ?? 0);
            $totalWithdrawal = (float) ($withdrawals->get($currency)->total ?? 0);
            $balance[$currency] = [
                'total_revenue' => $totalRevenue,
                'total_withdrawals' => $totalWithdrawal,
                'balance' => $totalRevenue - $totalWithdrawal,
            ];
        }

        // Overall totals
        $totalRevenueUSD = $balance['USD']['total_revenue'];
        $totalRevenueSYP = $balance['SYP']['total_revenue'];
        $totalRevenueTRY = $balance['TRY']['total_revenue'];
        $totalWithdrawalUSD = $balance['USD']['total_withdrawals'];
        $totalWithdrawalSYP = $balance['SYP']['total_withdrawals'];
        $totalWithdrawalTRY = $balance['TRY']['total_withdrawals'];

        return response()->json([
            'success' => true,
            'data' => [
                'by_currency' => $balance,
                'summary' => [
                    'total_revenue' => [
                        'USD' => $totalRevenueUSD,
                        'SYP' => $totalRevenueSYP,
                        'TRY' => $totalRevenueTRY,
                    ],
                    'total_withdrawals' => [
                        'USD' => $totalWithdrawalUSD,
                        'SYP' => $totalWithdrawalSYP,
                        'TRY' => $totalWithdrawalTRY,
                    ],
                    'total_balance' => [
                        'USD' => $totalRevenueUSD - $totalWithdrawalUSD,
                        'SYP' => $totalRevenueSYP - $totalWithdrawalSYP,
                        'TRY' => $totalRevenueTRY - $totalWithdrawalTRY,
                    ],
                ],
            ],
        ]);
    }

    /**
     * Get withdrawals statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CashWithdrawal::class);

        // Validate dates if provided
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        if ($fromDate && !strtotime($fromDate)) {
            return response()->json([
                'success' => false,
                'message' => 'تاريخ البداية غير صحيح. يجب أن يكون بصيغة Y-m-d',
            ], 422);
        }

        if ($toDate && !strtotime($toDate)) {
            return response()->json([
                'success' => false,
                'message' => 'تاريخ النهاية غير صحيح. يجب أن يكون بصيغة Y-m-d',
            ], 422);
        }

        // Build base query with date filters
        $query = CashWithdrawal::query();
        if ($fromDate) {
            $query->where('withdrawal_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->where('withdrawal_date', '<=', $toDate);
        }

        // 1. Get total count
        $totalCount = (int) $query->count();

        // 2. Get total by currency (clone query to avoid interference)
        $currencyStats = (clone $query)
            ->selectRaw('
                currency,
                SUM(amount) as total
            ')
            ->groupBy('currency')
            ->get()
            ->keyBy('currency');

        $totalByCurrency = [
            'USD' => (float) ($currencyStats->get('USD')->total ?? 0),
            'SYP' => (float) ($currencyStats->get('SYP')->total ?? 0),
            'TRY' => (float) ($currencyStats->get('TRY')->total ?? 0),
        ];

        // 3. Get statistics by category
        $categoryStats = (clone $query)
            ->selectRaw('
                category,
                COUNT(*) as count,
                SUM(CASE WHEN currency = "USD" THEN amount ELSE 0 END) as total_usd,
                SUM(CASE WHEN currency = "SYP" THEN amount ELSE 0 END) as total_syp,
                SUM(CASE WHEN currency = "TRY" THEN amount ELSE 0 END) as total_try
            ')
            ->groupBy('category')
            ->get()
            ->keyBy('category');

        // Define all required categories
        $allCategories = [
            'operational',
            'maintenance',
            'salary',
            'utilities',
            'supplies',
            'marketing',
            'emergency',
            'other',
        ];

        // Build by_category response with all categories (even if 0)
        $byCategory = [];
        foreach ($allCategories as $category) {
            $categoryData = $categoryStats->get($category);
            $byCategory[$category] = [
                'count' => (int) ($categoryData->count ?? 0),
                'total_usd' => (float) ($categoryData->total_usd ?? 0),
                'total_syp' => (float) ($categoryData->total_syp ?? 0),
                'total_try' => (float) ($categoryData->total_try ?? 0),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_count' => $totalCount,
                'total_by_currency' => $totalByCurrency,
                'by_category' => $byCategory,
            ],
        ]);
    }
}

