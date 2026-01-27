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
        Schema::create('live_stream_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('app_users')->onDelete('cascade');
            $table->foreignId('package_id')->nullable()->constrained('live_stream_packages')->onDelete('set null');
            $table->foreignId('payment_request_id')->nullable()->constrained('payment_requests')->onDelete('set null');
            $table->dateTime('starts_at');
            $table->dateTime('expires_at');
            $table->string('status', 20)->default('active')->comment('active, expired, cancelled');
            $table->dateTime('cancelled_at')->nullable();
            $table->unsignedBigInteger('renewed_from_id')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'expires_at']);
            $table->index(['payment_request_id']);
            $table->foreign('renewed_from_id')->references('id')->on('live_stream_subscriptions')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_stream_subscriptions');
    }
};

