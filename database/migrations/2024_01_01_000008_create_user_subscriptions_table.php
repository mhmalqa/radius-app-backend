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
        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('app_users')->onDelete('cascade');
            $table->string('radius_username', 100);
            $table->dateTime('expiration_at')->nullable();
            $table->decimal('balance', 10, 2)->nullable();
            $table->bigInteger('data_limit')->nullable()->comment('Data limit in bytes');
            $table->bigInteger('data_used')->nullable()->comment('Data used in bytes');
            $table->string('plan_name', 100)->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->tinyInteger('sync_status')->default(0)->comment('0: success, 1: failed, 2: pending');
            $table->timestamps();

            // Indexes
            $table->index('expiration_at');
            $table->index(['user_id', 'last_synced_at']);
            $table->index('radius_username');
            $table->index('sync_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
    }
};

