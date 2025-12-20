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
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('app_users')->onDelete('set null');
            $table->string('sync_type', 50)->comment('user, all, scheduled');
            $table->tinyInteger('status')->comment('0: success, 1: failed');
            $table->text('error_message')->nullable();
            $table->integer('records_synced')->default(0);
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'created_at']);
            $table->index('sync_type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};

