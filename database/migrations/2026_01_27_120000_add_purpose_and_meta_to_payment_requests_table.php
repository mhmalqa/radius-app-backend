<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->string('purpose', 40)
                ->default('internet_subscription')
                ->after('payment_type')
                ->comment('internet_subscription, live_stream_subscription');

            $table->json('meta')
                ->nullable()
                ->after('plan_name')
                ->comment('Additional metadata for payment purpose');

            $table->index('purpose');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropIndex(['purpose']);
            $table->dropColumn(['purpose', 'meta']);
        });
    }
};

