<?php

use App\Http\Controllers\AppSettingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CashWithdrawalController;
use App\Http\Controllers\DatabaseBackupController;
use App\Http\Controllers\DatabaseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceTokenController;
use App\Http\Controllers\LiveStreamController;
use App\Http\Controllers\MaintenanceRequestController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\PaymentRequestController;
use App\Http\Controllers\RevenueController;
use App\Http\Controllers\SlideController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

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
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});

Route::get('/payment-methods', [PaymentMethodController::class, 'index']);
Route::get('/slides', [SlideController::class, 'index']);
Route::get('/live-streams', [LiveStreamController::class, 'index']);
Route::get('/app-settings', [AppSettingController::class, 'index']);
Route::get('/app-settings/key/{key}', [AppSettingController::class, 'getByKey']);

// Protected routes
Route::middleware(['auth:sanctum', 'account.active'])->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    // User routes
    Route::prefix('user')->group(function () {
        Route::get('/profile', [UserController::class, 'profile']);
        Route::put('/profile', [UserController::class, 'updateProfile']);
        Route::post('/sync-subscription', [UserController::class, 'syncSubscription']);
        Route::get('/subscription-from-radius', [UserController::class, 'getSubscriptionFromRadius']);
        Route::get('/services-from-radius', [UserController::class, 'getServicesFromRadius']);
        Route::get('/plan-by-username', [UserController::class, 'getUserPlanByUsername']);
        Route::get('/revenues', [RevenueController::class, 'userRevenues']);
        Route::get('/payments', [PaymentRequestController::class, 'index']);
        Route::get('/unpaid-deferred-installments', [PaymentRequestController::class, 'unpaidDeferredInstallments']);
        
        // Device tokens
        Route::prefix('device-tokens')->group(function () {
            Route::get('/', [DeviceTokenController::class, 'index']);
            Route::post('/', [DeviceTokenController::class, 'register']);
            Route::delete('/{deviceToken}', [DeviceTokenController::class, 'remove']);
        });
    });

    // Payment requests routes
    Route::prefix('payment-requests')->group(function () {
        Route::get('/', [PaymentRequestController::class, 'index']);
        Route::post('/', [PaymentRequestController::class, 'store']);
        Route::get('/{paymentRequest}', [PaymentRequestController::class, 'show']);
    });

    // Live streams routes
    Route::prefix('live-streams')->group(function () {
        Route::get('/{liveStream}', [LiveStreamController::class, 'show']);
    });

    // Slides routes
    Route::prefix('slides')->group(function () {
        Route::post('/{slide}/track-click', [SlideController::class, 'trackClick']);
    });

    // Notifications routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/{notification}/mark-as-read', [NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
    });

    // Maintenance requests routes
    Route::prefix('maintenance-requests')->group(function () {
        Route::get('/', [MaintenanceRequestController::class, 'index']);
        Route::post('/', [MaintenanceRequestController::class, 'store']);
        Route::get('/{maintenanceRequest}', [MaintenanceRequestController::class, 'show']);
    });

    // Admin/Accountant routes
    Route::middleware('role:admin,accountant')->group(function () {
        // Dashboard statistics (optimized)
        Route::prefix('admin/dashboard')->group(function () {
            Route::get('/statistics', [DashboardController::class, 'statistics']);
            Route::post('/clear-cache', [DashboardController::class, 'clearCache']);
        });

        // Payment requests management
        Route::prefix('admin/payment-requests')->group(function () {
            Route::get('/', [PaymentRequestController::class, 'all']);
            Route::put('/{paymentRequest}/status', [PaymentRequestController::class, 'updateStatus']);
            Route::post('/cash-payment', [PaymentRequestController::class, 'createCashPayment']);
            Route::post('/{paymentRequest}/mark-as-paid', [PaymentRequestController::class, 'markAsPaid']);
        });

        // User management
        Route::prefix('admin/users')->group(function () {
            Route::get('/', [UserManagementController::class, 'index']);
            Route::get('/{user}', [UserManagementController::class, 'show']);
            Route::put('/{user}', [UserManagementController::class, 'update']);
            Route::post('/{user}/toggle-active', [UserManagementController::class, 'toggleActive']);
            Route::post('/{user}/toggle-live-access', [UserManagementController::class, 'toggleLiveAccess']);
            Route::post('/{user}/change-role', [UserManagementController::class, 'changeRole']);
        });

        // Maintenance requests management
        Route::prefix('admin/maintenance-requests')->group(function () {
            Route::get('/', [MaintenanceRequestController::class, 'all']);
            Route::put('/{maintenanceRequest}/status', [MaintenanceRequestController::class, 'updateStatus']);
        });

        // Revenues management
        Route::prefix('admin/revenues')->group(function () {
            Route::get('/', [RevenueController::class, 'index']);
            Route::get('/summary', [RevenueController::class, 'summary']);
            Route::get('/deferred-payments', [RevenueController::class, 'deferredPayments']);
        });

        // Cash withdrawals management (Cash Box)
        Route::prefix('admin/cash-withdrawals')->group(function () {
            Route::get('/', [CashWithdrawalController::class, 'index']);
            Route::post('/', [CashWithdrawalController::class, 'store']);
            Route::get('/balance', [CashWithdrawalController::class, 'getCashBoxBalance']);
            Route::get('/statistics', [CashWithdrawalController::class, 'statistics']);
            Route::get('/{cashWithdrawal}', [CashWithdrawalController::class, 'show']);
            Route::put('/{cashWithdrawal}', [CashWithdrawalController::class, 'update']);
            Route::delete('/{cashWithdrawal}', [CashWithdrawalController::class, 'destroy']);
        });
    });

    // Admin only routes
    Route::middleware('role:admin')->group(function () {
        // Live streams management
        Route::prefix('admin/live-streams')->group(function () {
            Route::get('/', [LiveStreamController::class, 'adminIndex']);
            Route::post('/', [LiveStreamController::class, 'store']);
            Route::put('/{liveStream}', [LiveStreamController::class, 'update']);
            Route::post('/{liveStream}/update', [LiveStreamController::class, 'update']); // POST route for form-data
            Route::delete('/{liveStream}', [LiveStreamController::class, 'destroy']);
        });

        // Slides management
        Route::prefix('admin/slides')->group(function () {
            Route::get('/', [SlideController::class, 'adminIndex']);
            Route::post('/', [SlideController::class, 'store']);
            Route::put('/{slide}', [SlideController::class, 'update']);
            Route::post('/{slide}/update', [SlideController::class, 'update']); // POST route for form-data
            Route::delete('/{slide}', [SlideController::class, 'destroy']);
        });

        // Notifications management
        Route::prefix('admin/notifications')->group(function () {
            Route::post('/', [NotificationController::class, 'store']);
        });

        // User management (delete only for admin)
        Route::prefix('admin/users')->group(function () {
            Route::delete('/{user}', [UserManagementController::class, 'destroy']);
        });

        // Payment methods management
        Route::prefix('admin/payment-methods')->group(function () {
            Route::get('/', [PaymentMethodController::class, 'adminIndex']);
            Route::post('/', [PaymentMethodController::class, 'store']);
            Route::put('/{paymentMethod}', [PaymentMethodController::class, 'update']);
            Route::post('/{paymentMethod}/update', [PaymentMethodController::class, 'update']); // POST route for form-data
            Route::delete('/{paymentMethod}', [PaymentMethodController::class, 'destroy']);
        });

        // App settings management
        Route::prefix('admin/app-settings')->group(function () {
            Route::get('/', [AppSettingController::class, 'all']);
            Route::get('/{appSetting}', [AppSettingController::class, 'show']);
            Route::put('/{appSetting}', [AppSettingController::class, 'update']);
            Route::post('/update-multiple', [AppSettingController::class, 'updateMultiple']);
            Route::delete('/{appSetting}', [AppSettingController::class, 'destroy']);
        });

        // Database operations (admin only)
        Route::prefix('admin/database')->group(function () {
            Route::get('/operations/latest', [DatabaseController::class, 'latestOperations']);
            Route::get('/operations/all', [DatabaseController::class, 'allOperations']);
            Route::get('/security-check', [DatabaseController::class, 'checkSecurity']);
        });

        // Database backups (admin only)
        Route::prefix('admin/database/backups')->group(function () {
            Route::get('/', [DatabaseBackupController::class, 'index']);
            Route::post('/', [DatabaseBackupController::class, 'store']);
            Route::post('/{backup}/download', [DatabaseBackupController::class, 'download']);
            Route::post('/schedule', [DatabaseBackupController::class, 'setSchedule']);
            Route::get('/schedule', [DatabaseBackupController::class, 'getSchedule']);
            Route::delete('/{backup}', [DatabaseBackupController::class, 'destroy']);
        });
    });
});

