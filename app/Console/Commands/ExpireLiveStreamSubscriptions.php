<?php

namespace App\Console\Commands;

use App\Models\LiveStreamSubscription;
use Illuminate\Console\Command;

class ExpireLiveStreamSubscriptions extends Command
{
    protected $signature = 'live-stream:expire-subscriptions {--dry-run : Do not update, only show count}';

    protected $description = 'Mark expired live stream subscriptions as expired';

    public function handle(): int
    {
        $query = LiveStreamSubscription::query()
            ->where('status', 'active')
            ->where('expires_at', '<=', now());

        $count = (int) $query->count();

        if ($this->option('dry-run')) {
            $this->info("Would expire {$count} subscriptions.");
            return self::SUCCESS;
        }

        $updated = (int) $query->update([
            'status' => 'expired',
        ]);

        $this->info("Expired {$updated} subscriptions.");
        return self::SUCCESS;
    }
}

