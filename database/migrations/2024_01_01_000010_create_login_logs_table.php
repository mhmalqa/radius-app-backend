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
        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('app_users')->onDelete('set null');
            $table->string('username', 100);
            $table->string('ip_address', 45);
            $table->string('user_agent', 255)->nullable();
            $table->string('device_type', 50)->nullable()->comment('mobile, tablet, desktop');
            $table->tinyInteger('status')->comment('0: failed, 1: success');
            $table->string('failure_reason', 100)->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'created_at']);
            $table->index('ip_address');
            $table->index('status');
            $table->index('username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_logs');
    }
};

