<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // If a previous attempt created the table but failed on indexes (common on MySQL due
        // to auto-generated long index names), just ensure the required indexes exist.
        if (Schema::hasTable('live_stream_access_tokens')) {
            try {
                DB::statement('CREATE UNIQUE INDEX lsat_token_hash_unique ON live_stream_access_tokens (token_hash)');
            } catch (\Throwable) {
                // Ignore if it already exists
            }

            try {
                DB::statement('CREATE INDEX lsat_user_stream_exp_idx ON live_stream_access_tokens (user_id, live_stream_id, expires_at)');
            } catch (\Throwable) {
                // Ignore if it already exists
            }

            // Foreign keys (best-effort; ignore if already present)
            try {
                DB::statement('ALTER TABLE live_stream_access_tokens ADD CONSTRAINT lsat_user_fk FOREIGN KEY (user_id) REFERENCES app_users(id) ON DELETE CASCADE');
            } catch (\Throwable) {
            }

            try {
                DB::statement('ALTER TABLE live_stream_access_tokens ADD CONSTRAINT lsat_stream_fk FOREIGN KEY (live_stream_id) REFERENCES live_streams(id) ON DELETE CASCADE');
            } catch (\Throwable) {
            }

            return;
        }

        Schema::create('live_stream_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('app_users')->onDelete('cascade');
            $table->foreignId('live_stream_id')->constrained('live_streams')->onDelete('cascade');
            // Use short index names to avoid MySQL identifier length limits (64 chars)
            $table->char('token_hash', 64)->unique('lsat_token_hash_unique');
            $table->dateTime('expires_at');
            $table->dateTime('last_used_at')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'live_stream_id', 'expires_at'], 'lsat_user_stream_exp_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_stream_access_tokens');
    }
};

