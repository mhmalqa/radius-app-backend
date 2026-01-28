<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Subscription expiry notifications
Schedule::command('notifications:subscription-expiry --hours=48')->dailyAt('09:00'); // قبل يومين
Schedule::command('notifications:subscription-expiry --hours=24')->dailyAt('09:00'); // قبل يوم
Schedule::command('notifications:subscription-expiry --hours=2')->hourly(); // قبل ساعتين
Schedule::command('notifications:subscription-expiry --hours=1')->hourly(); // قبل ساعة
Schedule::command('notifications:subscription-expired')->everyFiveMinutes(); // أثناء فصل الخدمة وبعد ربع ساعة

// Live stream day notifications
Schedule::command('notifications:live-stream-day')->dailyAt('08:00'); // Send at 8 AM for streams starting today

// Live stream packages: mark expired subscriptions for clean admin reporting
Schedule::command('live-stream:expire-subscriptions')->hourly();

// Live stream package notifications (expiry, renewal reminders)
Schedule::command('notifications:live-stream-packages')->everyFiveMinutes();

// Database backups - check schedule from app_settings
Schedule::call(function () {
    $schedule = \Illuminate\Support\Facades\DB::table('app_settings')
        ->where('key', 'backup_schedule')
        ->value('value');

    $time = \Illuminate\Support\Facades\DB::table('app_settings')
        ->where('key', 'backup_time')
        ->value('value');

    if ($schedule && $time) {
        $currentTime = now()->format('H:i');
        if ($currentTime === $time) {
            // Check if we should run based on schedule
            $shouldRun = false;
            if ($schedule === 'daily') {
                $shouldRun = true;
            } elseif ($schedule === 'weekly' && now()->dayOfWeek === 0) { // Sunday
                $shouldRun = true;
            } elseif ($schedule === 'monthly' && now()->day === 1) { // First day of month
                $shouldRun = true;
            }

            if ($shouldRun) {
                Artisan::call('db:backup');
            }
        }
    }
})->hourly(); // Check every hour
