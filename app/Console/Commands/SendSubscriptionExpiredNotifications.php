<?php

namespace App\Console\Commands;

use App\Models\UserSubscription;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendSubscriptionExpiredNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:subscription-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications to users whose subscription expired recently';

    public function __construct(
        protected NotificationService $notificationService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sentCount = 0;

        // 1. إشعار أثناء فصل الخدمة (عند انتهاء الاشتراك بالضبط - نطاق 5 دقائق مضت)
        $now = now();
        $fiveMinutesAgo = $now->copy()->subMinutes(5);

        $expiringNow = UserSubscription::whereBetween('expiration_at', [$fiveMinutesAgo, $now])
            ->whereHas('user', function ($query) {
                $query->where('is_active', true);
            })
            ->with('user')
            ->get();

        foreach ($expiringNow as $subscription) {
            // التأكد من أن الاشتراك انتهى بالفعل
            if (!$subscription->isExpired()) {
                continue;
            }

            $expirationDate = $subscription->expiration_at->format('Y-m-d H:i');
            
            $this->notificationService->createNotification([
                'title' => '⛔ تم فصل الخدمة',
                'body' => "تم فصل خدمة الإنترنت الخاصة بك الآن ({$expirationDate}). يجب تجديد الاشتراك لتعود الخدمة.",
                'type' => 'system',
                'priority' => 2, // Urgent
                'action_url' => '/payment-requests',
                'action_text' => 'تجديد الآن',
            ], [$subscription->user_id], 'specific');

            $sentCount++;
        }

        // 2. إشعار بعد ربع ساعة من انتهاء الاشتراك
        $fifteenMinutesAgo = $now->copy()->subMinutes(15);
        $sixteenMinutesAgo = $now->copy()->subMinutes(16);

        $expired15MinutesAgo = UserSubscription::whereBetween('expiration_at', [$sixteenMinutesAgo, $fifteenMinutesAgo])
            ->whereHas('user', function ($query) {
                $query->where('is_active', true);
            })
            ->with('user')
            ->get();

        foreach ($expired15MinutesAgo as $subscription) {
            if (!$subscription->isExpired()) {
                continue;
            }

            $expirationDate = $subscription->expiration_at->format('Y-m-d H:i');
            
            $this->notificationService->createNotification([
                'title' => '⛔ انتهى الاشتراك',
                'body' => "اشتراكك انتهى منذ ربع ساعة ({$expirationDate}). يجب تجديد الاشتراك لتعود خدمة الإنترنت.",
                'type' => 'system',
                'priority' => 2, // Urgent
                'action_url' => '/payment-requests',
                'action_text' => 'تجديد الآن',
            ], [$subscription->user_id], 'specific');

            $sentCount++;
        }

        $this->info("تم إرسال {$sentCount} إشعار");
        Log::info("Subscription expired notifications sent", [
            'count' => $sentCount,
            'expiring_now' => $expiringNow->count(),
            'expired_15min_ago' => $expired15MinutesAgo->count(),
        ]);

        return 0;
    }
}

