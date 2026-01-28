<?php

namespace App\Console\Commands;

use App\Models\LiveStreamSubscription;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendLiveStreamPackageNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:live-stream-packages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications for live stream package expirations (2 days before, 1 day before, 10 min before, at expiry, 2 hours after)';

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
        $now = now();
        $count = 0;

        // We assume the command runs frequently (e.g., every 5 minutes).
        // We look for subscriptions in a wide range to be safe, then check specific windows.
        
        $subscriptions = LiveStreamSubscription::query()
            ->with(['user', 'package'])
            ->where(function($q) use ($now) {
                // Future expirations: up to 3 days ahead
                // Past expirations: up to 5 hours ago
                $q->where('expires_at', '<=', $now->clone()->addDays(3))
                  ->where('expires_at', '>', $now->clone()->subHours(5));
            })
            // Exclude cancelled? Usually yes.
            ->where('status', '!=', 'cancelled') 
            ->chunk(100, function ($subs) use ($now, &$count) {
                foreach ($subs as $sub) {
                    $this->checkAndNotify($sub, $now);
                    $count++;
                }
            });

        // Log::info("Checked {$count} subscriptions for live stream notifications.");
        return 0;
    }

    protected function checkAndNotify(LiveStreamSubscription $sub, Carbon $now)
    {
        $flags = $sub->notification_flags ?? [];
        $expiresAt = $sub->expires_at;
        $packageName = $sub->package->name ?? 'باقة البث';
        $userId = $sub->user_id;

        // Skip if user is deleted or inactive
        if (!$sub->user || !$sub->user->is_active) {
            return;
        }

        // Calculate differences
        // positive = future, negative = past
        $diffInMinutes = $now->diffInMinutes($expiresAt, false);
        $diffInHours = $now->diffInHours($expiresAt, false);

        // 1. Before 2 days (48 hours)
        // Trigger window: between 47 and 49 hours remaining
        if ($diffInHours >= 47 && $diffInHours <= 49 && !($flags['2_days_before'] ?? false)) {
            $this->send($userId, "تذكير: قرب انتهاء الباقة", "باقة {$packageName} ستنتهي خلال يومين. يرجى التجديد لضمان استمرار الخدمة.");
            $this->markAsSent($sub, '2_days_before');
        }

        // 2. Before 1 day (24 hours)
        // Trigger window: between 23 and 25 hours remaining
        elseif ($diffInHours >= 23 && $diffInHours <= 25 && !($flags['1_day_before'] ?? false)) {
             $this->send($userId, "تنبيه: يوم واحد متبقي", "باقة {$packageName} ستنتهي غداً. سارع بالتجديد.");
             $this->markAsSent($sub, '1_day_before');
        }

        // 3. Before 10 minutes
        // Trigger window: between 0 and 15 minutes remaining
        elseif ($diffInMinutes > 0 && $diffInMinutes <= 15 && !($flags['10_min_before'] ?? false)) {
             $this->send($userId, "تنبيه هام", "باقة {$packageName} ستنتهي خلال 10 دقائق.");
             $this->markAsSent($sub, '10_min_before');
        }

        // 4. At expiration (Just expired)
        // Trigger window: between -15 and 0 minutes (expired in last 15 mins)
        elseif ($diffInMinutes <= 0 && $diffInMinutes >= -15 && !($flags['at_expiration'] ?? false)) {
             $this->send($userId, "انتهت الباقة", "لقد انتهت صلاحية باقة {$packageName}. جدد اشتراكك الآن.");
             $this->markAsSent($sub, 'at_expiration');
        }

        // 5. After 2 hours (without renewal)
        // Trigger window: between -150 mins (-2.5h) and -120 mins (-2h)
        elseif ($diffInMinutes <= -120 && $diffInMinutes >= -150 && !($flags['2_hours_after'] ?? false)) {
            // Check if user has renewed (has ANY active subscription for this package ID)
            $hasActive = LiveStreamSubscription::where('user_id', $userId)
                ->where('package_id', $sub->package_id)
                ->where('status', 'active')
                ->where('expires_at', '>', $now)
                ->exists();

            if (!$hasActive) {
                $this->send($userId, "لا تفوت المشاهدة", "انتهت باقة {$packageName} منذ ساعتين. هل ترغب بالتجديد؟");
            }
            // Mark as sent regardless, so we don't spam
            $this->markAsSent($sub, '2_hours_after');
        }
    }

    protected function send($userId, $title, $body)
    {
        try {
            $this->notificationService->createNotification([
                'title' => $title,
                'body' => $body,
                'type' => 'system',
                'priority' => 1,
            ], [$userId], 'specific');
        } catch (\Exception $e) {
            Log::error("Failed to send live stream notification to user {$userId}: " . $e->getMessage());
        }
    }

    protected function markAsSent(LiveStreamSubscription $sub, $key)
    {
        $flags = $sub->notification_flags ?? [];
        $flags[$key] = true;
        $sub->notification_flags = $flags;
        $sub->save();
    }
}
