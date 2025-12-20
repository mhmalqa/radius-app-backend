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
        Schema::create('live_streams', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->string('stream_url', 500);
            $table->string('thumbnail', 255)->nullable();
            $table->string('category', 50)->default('match')->comment('match, channel, event');
            $table->string('stream_type', 20)->default('live')->comment('live, vod');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->integer('view_count')->default(0);
            $table->integer('max_viewers')->nullable();
            $table->integer('sort_order')->default(0);
            $table->json('quality_options')->nullable()->comment('JSON array of quality options');
            $table->timestamps();

            // Indexes
            $table->index(['is_active', 'is_featured']);
            $table->index(['start_time', 'end_time']);
            $table->index('category');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_streams');
    }
};

