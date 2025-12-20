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
        Schema::create('slides', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->string('image_path', 255);
            $table->string('image_mobile', 255)->nullable();
            $table->string('image_desktop', 255)->nullable();
            $table->string('link_url', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('target_audience', 20)->default('all')->comment('all, active_users, expired_users');
            $table->integer('sort_order')->default(0);
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->integer('click_count')->default(0);
            $table->timestamps();

            // Indexes
            $table->index(['is_active', 'target_audience']);
            $table->index(['start_at', 'end_at']);
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slides');
    }
};

