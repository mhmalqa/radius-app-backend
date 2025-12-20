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
        Schema::create('app_users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->string('password', 255);
            $table->string('phone', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('live_access')->default(false);
            $table->tinyInteger('role')->default(0)->comment('0: user, 1: accountant, 2: admin');
            $table->string('device_token', 500)->nullable();
            $table->string('language', 10)->default('ar')->comment('ar, en');
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('username');
            $table->index('phone');
            $table->index('role');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_users');
    }
};

