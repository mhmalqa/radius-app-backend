<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class DatabaseController extends Controller
{
    /**
     * Get latest database operations (admin only).
     */
    public function latestOperations(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\AppUser::class);

        $limit = $request->get('limit', 50);

        try {
            // Get latest operations from various tables
            $operations = [];

            // Latest payment requests
            $paymentRequests = DB::table('payment_requests')
                ->select('id', 'user_id', 'amount', 'status', 'payment_type', 'created_at', DB::raw("'payment_request' as table_name"))
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            foreach ($paymentRequests as $pr) {
                $operations[] = [
                    'id' => $pr->id,
                    'table' => 'payment_requests',
                    'type' => 'insert',
                    'data' => [
                        'user_id' => $pr->user_id,
                        'amount' => $pr->amount,
                        'status' => $pr->status,
                        'payment_type' => $pr->payment_type,
                    ],
                    'timestamp' => $pr->created_at,
                ];
            }

            // Latest revenues
            $revenues = DB::table('revenues')
                ->select('id', 'user_id', 'amount', 'payment_type', 'payment_date', 'created_at', DB::raw("'revenue' as table_name"))
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            foreach ($revenues as $rev) {
                $operations[] = [
                    'id' => $rev->id,
                    'table' => 'revenues',
                    'type' => 'insert',
                    'data' => [
                        'user_id' => $rev->user_id,
                        'amount' => $rev->amount,
                        'payment_type' => $rev->payment_type,
                        'payment_date' => $rev->payment_date,
                    ],
                    'timestamp' => $rev->created_at,
                ];
            }

            // Latest user registrations
            $users = DB::table('app_users')
                ->select('id', 'username', 'role', 'is_active', 'created_at', DB::raw("'app_user' as table_name"))
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            foreach ($users as $user) {
                $operations[] = [
                    'id' => $user->id,
                    'table' => 'app_users',
                    'type' => 'insert',
                    'data' => [
                        'username' => $user->username,
                        'role' => $user->role,
                        'is_active' => $user->is_active,
                    ],
                    'timestamp' => $user->created_at,
                ];
            }

            // Sort by timestamp
            usort($operations, function ($a, $b) {
                return strtotime($b['timestamp']) - strtotime($a['timestamp']);
            });

            // Limit results
            $operations = array_slice($operations, 0, $limit);

            return response()->json([
                'success' => true,
                'data' => $operations,
                'meta' => [
                    'total' => count($operations),
                    'limit' => $limit,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get database operations', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب العمليات',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all database operations with pagination (admin only).
     */
    public function allOperations(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\AppUser::class);

        $perPage = $request->get('per_page', 50);
        $table = $request->get('table');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        try {
            $operations = [];

            // Get operations from payment_requests
            if (!$table || $table === 'payment_requests') {
                $query = DB::table('payment_requests')
                    ->select('id', 'user_id', 'amount', 'status', 'payment_type', 'created_at', DB::raw("'payment_requests' as table_name"));

                if ($fromDate) {
                    $query->where('created_at', '>=', $fromDate);
                }
                if ($toDate) {
                    $query->where('created_at', '<=', $toDate);
                }

                $paymentRequests = $query->orderBy('created_at', 'desc')->get();

                foreach ($paymentRequests as $pr) {
                    $operations[] = [
                        'id' => $pr->id,
                        'table' => 'payment_requests',
                        'type' => 'insert',
                        'data' => [
                            'user_id' => $pr->user_id,
                            'amount' => $pr->amount,
                            'status' => $pr->status,
                            'payment_type' => $pr->payment_type,
                        ],
                        'timestamp' => $pr->created_at,
                    ];
                }
            }

            // Get operations from revenues
            if (!$table || $table === 'revenues') {
                $query = DB::table('revenues')
                    ->select('id', 'user_id', 'amount', 'payment_type', 'payment_date', 'created_at', DB::raw("'revenues' as table_name"));

                if ($fromDate) {
                    $query->where('created_at', '>=', $fromDate);
                }
                if ($toDate) {
                    $query->where('created_at', '<=', $toDate);
                }

                $revenues = $query->orderBy('created_at', 'desc')->get();

                foreach ($revenues as $rev) {
                    $operations[] = [
                        'id' => $rev->id,
                        'table' => 'revenues',
                        'type' => 'insert',
                        'data' => [
                            'user_id' => $rev->user_id,
                            'amount' => $rev->amount,
                            'payment_type' => $rev->payment_type,
                            'payment_date' => $rev->payment_date,
                        ],
                        'timestamp' => $rev->created_at,
                    ];
                }
            }

            // Get operations from app_users
            if (!$table || $table === 'app_users') {
                $query = DB::table('app_users')
                    ->select('id', 'username', 'role', 'is_active', 'created_at', DB::raw("'app_users' as table_name"));

                if ($fromDate) {
                    $query->where('created_at', '>=', $fromDate);
                }
                if ($toDate) {
                    $query->where('created_at', '<=', $toDate);
                }

                $users = $query->orderBy('created_at', 'desc')->get();

                foreach ($users as $user) {
                    $operations[] = [
                        'id' => $user->id,
                        'table' => 'app_users',
                        'type' => 'insert',
                        'data' => [
                            'username' => $user->username,
                            'role' => $user->role,
                            'is_active' => $user->is_active,
                        ],
                        'timestamp' => $user->created_at,
                    ];
                }
            }

            // Sort by timestamp
            usort($operations, function ($a, $b) {
                return strtotime($b['timestamp']) - strtotime($a['timestamp']);
            });

            // Paginate manually
            $total = count($operations);
            $page = $request->get('page', 1);
            $offset = ($page - 1) * $perPage;
            $paginatedOperations = array_slice($operations, $offset, $perPage);

            return response()->json([
                'success' => true,
                'data' => $paginatedOperations,
                'meta' => [
                    'current_page' => (int) $page,
                    'per_page' => (int) $perPage,
                    'total' => $total,
                    'last_page' => (int) ceil($total / $perPage),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get all database operations', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب العمليات',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check database security and permissions (admin only).
     */
    public function checkSecurity(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\AppUser::class);

        try {
            $checks = [];

            // Check database connection
            try {
                DB::connection()->getPdo();
                $checks['database_connection'] = [
                    'status' => 'success',
                    'message' => 'الاتصال بقاعدة البيانات ناجح',
                ];
            } catch (\Exception $e) {
                $checks['database_connection'] = [
                    'status' => 'error',
                    'message' => 'فشل الاتصال بقاعدة البيانات: ' . $e->getMessage(),
                ];
            }

            // Check if sensitive tables exist
            $sensitiveTables = ['app_users', 'payment_requests', 'revenues', 'user_subscriptions'];
            foreach ($sensitiveTables as $table) {
                $exists = Schema::hasTable($table);
                $checks['table_' . $table] = [
                    'status' => $exists ? 'success' : 'error',
                    'message' => $exists ? "جدول {$table} موجود" : "جدول {$table} غير موجود",
                ];
            }

            // Check for users without passwords (security issue)
            $usersWithoutPassword = DB::table('app_users')
                ->whereNull('password')
                ->orWhere('password', '')
                ->count();

            $checks['users_without_password'] = [
                'status' => $usersWithoutPassword === 0 ? 'success' : 'warning',
                'message' => $usersWithoutPassword === 0 
                    ? 'جميع المستخدمين لديهم كلمات مرور' 
                    : "يوجد {$usersWithoutPassword} مستخدم بدون كلمة مرور",
                'count' => $usersWithoutPassword,
            ];

            // Check for active admin accounts
            $activeAdmins = DB::table('app_users')
                ->where('role', 2)
                ->where('is_active', true)
                ->count();

            $checks['active_admins'] = [
                'status' => $activeAdmins > 0 ? 'success' : 'warning',
                'message' => $activeAdmins > 0 
                    ? "يوجد {$activeAdmins} مدير نشط" 
                    : 'لا يوجد مديرين نشطين',
                'count' => $activeAdmins,
            ];

            // Check database size (if possible)
            try {
                $databaseName = DB::connection()->getDatabaseName();
                $size = DB::select("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb FROM information_schema.tables WHERE table_schema = ?", [$databaseName]);
                $checks['database_size'] = [
                    'status' => 'success',
                    'message' => 'حجم قاعدة البيانات: ' . ($size[0]->size_mb ?? 0) . ' MB',
                    'size_mb' => $size[0]->size_mb ?? 0,
                ];
            } catch (\Exception $e) {
                $checks['database_size'] = [
                    'status' => 'info',
                    'message' => 'لا يمكن حساب حجم قاعدة البيانات',
                ];
            }

            // Overall status
            $hasErrors = collect($checks)->contains(fn($check) => $check['status'] === 'error');
            $hasWarnings = collect($checks)->contains(fn($check) => $check['status'] === 'warning');

            return response()->json([
                'success' => true,
                'data' => [
                    'checks' => $checks,
                    'overall_status' => $hasErrors ? 'error' : ($hasWarnings ? 'warning' : 'success'),
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to check database security', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في فحص أمان قاعدة البيانات',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

