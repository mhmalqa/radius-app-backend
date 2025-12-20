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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->text('body');
            $table->string('type', 50)->default('manual')->comment('system, manual');
            $table->tinyInteger('priority')->default(0)->comment('0: normal, 1: important, 2: urgent');
            $table->string('action_url', 255)->nullable();
            $table->string('action_text', 50)->nullable();
            $table->string('icon', 255)->nullable();
            $table->string('sound', 255)->nullable();
            $table->integer('badge')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('app_users')->onDelete('set null');
            $table->timestamps();

            // Indexes
            $table->index('type');
            $table->index('priority');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

