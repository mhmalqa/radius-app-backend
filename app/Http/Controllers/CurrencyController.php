<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\CurrencyHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\NotificationService;

class CurrencyController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Get all currencies.
     * - Admins/Accountants: See ALL currencies (Active + Inactive).
     * - Regular Users: See only ACTIVE currencies.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if user is Admin (2) or Accountant (3)
        $isAdminOrAccountant = $user && in_array($user->role, [2, 3]);

        if ($isAdminOrAccountant) {
            // Admins/Accountants see all currencies
            $currencies = Currency::orderBy('is_default', 'desc')->get();
        } else {
            // Regular users see only active currencies
            // Cache for 1 hour
            $currencies = Cache::remember('currencies_list', 3600, function () {
                return Currency::where('is_active', true)
                    ->orderBy('is_default', 'desc')
                    ->get();
            });
        }

        return response()->json([
            'success' => true,
            'data' => $currencies,
        ]);
    }

    /**
     * Update currency exchange rate.
     * Accessible by: Admin and Accountant only.
     */
    public function update(Request $request, Currency $currency): JsonResponse
    {
        // Authorization check (Admin or Accountant)
        $user = $request->user();
        if (!in_array($user->role, [2, 3])) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بتعديل العملات',
            ], 403);
        }

        $request->validate([
            'exchange_rate' => 'sometimes|numeric|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $changesMade = false;
        $rateChanged = false;
        $statusChanged = false;
        $oldRate = $currency->exchange_rate;
        $newRate = $oldRate;

        // Prevent deactivating the default currency
        if ($request->has('is_active') && !$request->is_active && $currency->is_default) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إلغاء تفعيل العملة الافتراضية',
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Handle Status Change
            if ($request->has('is_active') && $currency->is_active !== $request->is_active) {
                $currency->is_active = $request->is_active;
                $changesMade = true;
                $statusChanged = true;
            }

            // Handle Rate Change
            if ($request->has('exchange_rate')) {
                $newRate = $request->exchange_rate;
                if ($newRate != $oldRate) {
                    // Create history record
                    CurrencyHistory::create([
                        'currency_id' => $currency->id,
                        'old_rate' => $oldRate,
                        'new_rate' => $newRate,
                        'updated_by' => $user->id,
                        'created_at' => now(),
                    ]);

                    $currency->exchange_rate = $newRate;
                    $rateChanged = true;
                    $changesMade = true;
                }
            }

            if ($changesMade) {
                $currency->last_updated_at = now();
                $currency->updated_by = $user->id;
                $currency->save();

                DB::commit();

                // Clear cache
                Cache::forget('currencies_list');

                // Notify Admins only on rate change
                if ($rateChanged) {
                    $this->notifyAdminsOfChange($currency, $oldRate, $newRate, $user);
                }

                // Notify Admins on status change
                if ($statusChanged) {
                    $this->notifyAdminsOfStatusChange($currency, $user);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'تم تحديث العملة بنجاح',
                    'data' => $currency,
                ]);
            } else {
                DB::rollBack();
                return response()->json([
                    'success' => true,
                    'message' => 'لم يتم إجراء أي تغييرات',
                    'data' => $currency,
                ]);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء التحديث: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get history of a currency.
     * Accessible by: Admin and Accountant only.
     */
    public function history(Request $request, Currency $currency): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role, [2, 3])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $history = $currency->history()
            ->with('updater:id,username,firstname')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Convert amount from one currency to another.
     * Accessible by: All authenticated users.
     */
    public function convert(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'from_currency' => 'required|string|in:USD,SYP,TRY',
            'to_currency' => 'required|string|in:USD,SYP,TRY',
        ]);

        $amount = (float) $request->input('amount');
        $fromCurrencyCode = $request->input('from_currency');
        $toCurrencyCode = $request->input('to_currency');

        // If same currency, return same amount
        if ($fromCurrencyCode === $toCurrencyCode) {
            return response()->json([
                'success' => true,
                'data' => [
                    'from_currency' => $fromCurrencyCode,
                    'to_currency' => $toCurrencyCode,
                    'amount' => $amount,
                    'converted_amount' => round($amount, 2),
                    'exchange_rate' => 1.0,
                ],
            ]);
        }

        // Get currencies
        $fromCurrency = Currency::where('code', $fromCurrencyCode)
            ->where('is_active', true)
            ->first();

        $toCurrency = Currency::where('code', $toCurrencyCode)
            ->where('is_active', true)
            ->first();

        if (!$fromCurrency) {
            return response()->json([
                'success' => false,
                'message' => "العملة المصدر ({$fromCurrencyCode}) غير موجودة أو غير مفعلة",
            ], 404);
        }

        if (!$toCurrency) {
            return response()->json([
                'success' => false,
                'message' => "العملة الهدف ({$toCurrencyCode}) غير موجودة أو غير مفعلة",
            ], 404);
        }

        // Get default currency (USD)
        $defaultCurrency = Currency::where('is_default', true)->first();

        if (!$defaultCurrency) {
            return response()->json([
                'success' => false,
                'message' => 'العملة الافتراضية غير موجودة',
            ], 500);
        }

        try {
            // Convert to default currency first, then to target currency
            // If from currency is default, use amount as is
            if ($fromCurrency->is_default) {
                $amountInDefault = $amount;
            } else {
                // Convert from source currency to default currency
                // exchange_rate represents: 1 default_currency = exchange_rate source_currency
                // So: amount_in_default = amount / exchange_rate
                $amountInDefault = $amount / $fromCurrency->exchange_rate;
            }

            // Convert from default currency to target currency
            if ($toCurrency->is_default) {
                $convertedAmount = $amountInDefault;
                $exchangeRate = $fromCurrency->is_default ? 1.0 : (1 / $fromCurrency->exchange_rate);
            } else {
                // Convert from default to target
                // exchange_rate represents: 1 default_currency = exchange_rate target_currency
                // So: amount_in_target = amount_in_default * target_exchange_rate
                $convertedAmount = $amountInDefault * $toCurrency->exchange_rate;
                $exchangeRate = $fromCurrency->is_default
                    ? $toCurrency->exchange_rate
                    : ($toCurrency->exchange_rate / $fromCurrency->exchange_rate);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'from_currency' => $fromCurrencyCode,
                    'to_currency' => $toCurrencyCode,
                    'amount' => $amount,
                    'converted_amount' => round($convertedAmount, 2),
                    'exchange_rate' => round($exchangeRate, 6),
                    'from_currency_info' => [
                        'code' => $fromCurrency->code,
                        'name' => $fromCurrency->name,
                        'symbol' => $fromCurrency->symbol,
                        'exchange_rate' => (float) $fromCurrency->exchange_rate,
                    ],
                    'to_currency_info' => [
                        'code' => $toCurrency->code,
                        'name' => $toCurrency->name,
                        'symbol' => $toCurrency->symbol,
                        'exchange_rate' => (float) $toCurrency->exchange_rate,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء التحويل: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper to notify admins of change.
     */
    protected function notifyAdminsOfChange(Currency $currency, float $oldRate, float $newRate, $updater)
    {
        // Use NotificationService to send to admins
        // Assuming sendToAdmins accepts title, body, etc.
        $title = "تحديث سعر صرف {$currency->code}";
        $body = "قام {$updater->firstname} بتعديل سعر صرف {$currency->name} من {$oldRate} إلى {$newRate}";

        try {
            $this->notificationService->sendToAdmins([
                'title' => $title,
                'body' => $body,
                'type' => 'system',
                'icon' => 'currency_exchange',
                'action_url' => '/admin/currencies',
            ]);
        } catch (\Exception $e) {
            // Log error but don't fail the request
            Log::error('Failed to notify admins of currency update', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Helper to notify admins of status change.
     */
    protected function notifyAdminsOfStatusChange(Currency $currency, $updater)
    {
        $action = $currency->is_active ? 'تفعيل' : 'اخفاء';
        $title = "تغيير حالة عملة {$currency->code}";
        $body = "تم {$action} العملة {$currency->name} من قبل {$updater->firstname}";

        try {
            $this->notificationService->sendToAdmins([
                'title' => $title,
                'body' => $body,
                'type' => 'system',
                'icon' => $currency->is_active ? 'visibility' : 'visibility_off',
                'action_url' => '/admin/currencies',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to notify admins of currency status change', ['error' => $e->getMessage()]);
        }
    }
}
