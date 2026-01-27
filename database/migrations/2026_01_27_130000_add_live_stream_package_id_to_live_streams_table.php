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
        Schema::table('live_streams', function (Blueprint $table) {
            $table->foreignId('live_stream_package_id')
                ->nullable()
                ->after('access_type')
                ->constrained('live_stream_packages')
                ->nullOnDelete()
                ->comment('If set, stream is restricted to subscribers of this package (or users with live_access).');

            $table->index('live_stream_package_id', 'live_streams_package_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('live_streams', function (Blueprint $table) {
            $table->dropForeign(['live_stream_package_id']);
            $table->dropIndex('live_streams_package_idx');
            $table->dropColumn('live_stream_package_id');
        });
    }
};

