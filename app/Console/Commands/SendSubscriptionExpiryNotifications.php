<?php

namespace App\Console\Commands;

use App\Models\AppUser;
use App\Models\UserSubscription;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendSubscriptionExpiryNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:subscription-expiry {--hours=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send subscription expiry notifications';

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
        $hours = (int) $this->option('hours');
        
        if ($hours === 0) {
            $this->error('يجب تحديد عدد الساعات (--hours)');
            return 1;
        }

        $targetTime = now()->addHours($hours);
        $startTime = $targetTime->copy()->subMinutes(30);
        $endTime = $targetTime->copy()->addMinutes(30);

        // Find users whose subscription expires around the target time
        $subscriptions = UserSubscription::whereBetween('expiration_at', [$startTime, $endTime])
            ->whereHas('user', function ($query) {
                $query->where('is_active', true);
            })
            ->with('user')
            ->get();

        $sentCount = 0;

        foreach ($subscriptions as $subscription) {
            if ($subscription->isExpired()) {
                continue;
            }

            $message = $this->getMessageForHours($hours, $subscription);
            
            $this->notificationService->createNotification([
                'title' => $message['title'],
                'body' => $message['body'],
                'type' => 'system',
                'priority' => $hours <= 1 ? 2 : ($hours <= 24 ? 1 : 0),
                'action_url' => '/subscription',
                'action_text' => 'تجديد الاشتراك',
            ], [$subscription->user_id], 'specific');

            $sentCount++;
        }

        $this->info("تم إرسال {$sentCount} إشعار");
        Log::info("Subscription expiry notifications sent", [
            'hours' => $hours,
            'count' => $sentCount,
        ]);

        return 0;
    }

    /**
     * Get message based on hours until expiry.
     */
    protected function getMessageForHours(int $hours, UserSubscription $subscription): array
    {
        $expirationDate = $subscription->expiration_at->format('Y-m-d H:i');
        
        return match (true) {
            $hours <= 1 => [
                'title' => '⚠️ انتهاء الاشتراك قريباً',
                'body' => "اشتراكك سينتهي خلال ساعة واحدة ({$expirationDate}). يرجى تجديد الاشتراك لتستمر خدمة الإنترنت.",
            ],
            $hours <= 24 => [
                'title' => 'تنبيه: انتهاء الاشتراك قريباً',
                'body' => "اشتراكك سينتهي خلال يوم واحد ({$expirationDate}). يرجى تجديد الاشتراك لتستمر خدمة الإنترنت.",
            ],
            $hours <= 48 => [
                'title' => 'تذكير: انتهاء الاشتراك',
                'body' => "اشتراكك سينتهي خلال يومين ({$expirationDate}). يرجى تجديد الاشتراك لتستمر خدمة الإنترنت.",
            ],
            default => [
                'title' => 'تذكير: انتهاء الاشتراك',
                'body' => "اشتراكك سينتهي في ({$expirationDate}). يرجى تجديد الاشتراك.",
            ],
        };
    }
}

