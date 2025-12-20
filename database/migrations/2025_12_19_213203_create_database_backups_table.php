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
        Schema::create('database_backups', function (Blueprint $table) {
            $table->id();
            $table->string('filename')->unique();
            $table->string('file_path');
            $table->string('file_size')->nullable(); // in bytes or human readable
            $table->enum('type', ['manual', 'automatic'])->default('manual');
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->string('scheduled_time')->nullable(); // for automatic backups: cron expression or time
            $table->timestamp('backup_date')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('type');
            $table->index('status');
            $table->index('backup_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('database_backups');
    }
};
