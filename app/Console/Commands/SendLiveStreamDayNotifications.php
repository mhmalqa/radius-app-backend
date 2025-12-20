<?php

namespace App\Console\Commands;

use App\Models\AppUser;
use App\Models\LiveStream;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendLiveStreamDayNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:live-stream-day';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications for live streams scheduled for today';

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
        $today = now()->startOfDay();
        $tomorrow = now()->copy()->addDay()->startOfDay();

        // Find streams that start today
        $streams = LiveStream::where('is_active', true)
            ->whereNotNull('start_time')
            ->whereBetween('start_time', [$today, $tomorrow])
            ->get();

        $sentCount = 0;

        foreach ($streams as $stream) {
            // Get users with live_access
            $userIds = AppUser::where('is_active', true)
                ->where('live_access', true)
                ->pluck('id')
                ->toArray();

            if (empty($userIds)) {
                continue;
            }

            $startTime = $stream->start_time->format('H:i');
            $description = $stream->description ?: $stream->title;

            $this->notificationService->createNotification([
                'title' => "📺 بث مباشر اليوم: {$stream->title}",
                'body' => "{$description} - يبدأ في الساعة {$startTime}",
                'type' => 'system',
                'priority' => 1,
                'action_url' => "/live-streams/{$stream->id}",
                'action_text' => 'شاهد الآن',
            ], $userIds, 'specific');

            $sentCount++;
        }

        $this->info("تم إرسال {$sentCount} إشعار");
        Log::info("Live stream day notifications sent", [
            'count' => $sentCount,
        ]);

        return 0;
    }
}

