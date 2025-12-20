<?php

namespace App\Providers;

use App\Models\AppSetting;
use App\Models\AppUser;
use App\Models\CashWithdrawal;
use App\Models\LiveStream;
use App\Models\MaintenanceRequest;
use App\Models\Notification;
use App\Models\PaymentMethod;
use App\Models\PaymentRequest;
use App\Models\Slide;
use App\Policies\AppSettingPolicy;
use App\Policies\CashWithdrawalPolicy;
use App\Policies\LiveStreamPolicy;
use App\Policies\MaintenanceRequestPolicy;
use App\Policies\NotificationPolicy;
use App\Policies\PaymentMethodPolicy;
use App\Policies\PaymentRequestPolicy;
use App\Policies\SlidePolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        AppUser::class => UserPolicy::class,
        PaymentRequest::class => PaymentRequestPolicy::class,
        LiveStream::class => LiveStreamPolicy::class,
        Slide::class => SlidePolicy::class,
        Notification::class => NotificationPolicy::class,
        PaymentMethod::class => PaymentMethodPolicy::class,
        MaintenanceRequest::class => MaintenanceRequestPolicy::class,
        AppSetting::class => AppSettingPolicy::class,
        CashWithdrawal::class => CashWithdrawalPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
