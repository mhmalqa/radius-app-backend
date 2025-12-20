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
        // Find subscriptions that expired in the last hour
        $oneHourAgo = now()->subHour();
        $now = now();

        $subscriptions = UserSubscription::whereBetween('expiration_at', [$oneHourAgo, $now])
            ->whereHas('user', function ($query) {
                $query->where('is_active', true);
            })
            ->with('user')
            ->get();

        $sentCount = 0;

        foreach ($subscriptions as $subscription) {
            if (!$subscription->isExpired()) {
                continue;
            }

            $expirationDate = $subscription->expiration_at->format('Y-m-d H:i');
            
            $this->notificationService->createNotification([
                'title' => '⛔ انتهى الاشتراك',
                'body' => "اشتراكك انتهى في ({$expirationDate}). يجب تجديد الاشتراك لتعود خدمة الإنترنت.",
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
        ]);

        return 0;
    }
}

