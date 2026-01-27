<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update payment_requests table
        Schema::table('payment_requests', function (Blueprint $table) {
            // Change default currency from IQD to USD
            DB::statement("ALTER TABLE payment_requests ALTER COLUMN currency SET DEFAULT 'USD'");
        });

        // Update revenues table
        Schema::table('revenues', function (Blueprint $table) {
            // Change default currency from IQD to USD
            DB::statement("ALTER TABLE revenues ALTER COLUMN currency SET DEFAULT 'USD'");
        });

        // Update partial_payments table
        Schema::table('partial_payments', function (Blueprint $table) {
            // Change default currency from IQD to USD
            DB::statement("ALTER TABLE partial_payments ALTER COLUMN currency SET DEFAULT 'USD'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to IQD
        Schema::table('payment_requests', function (Blueprint $table) {
            DB::statement("ALTER TABLE payment_requests ALTER COLUMN currency SET DEFAULT 'IQD'");
        });

        Schema::table('revenues', function (Blueprint $table) {
            DB::statement("ALTER TABLE revenues ALTER COLUMN currency SET DEFAULT 'IQD'");
        });

        Schema::table('partial_payments', function (Blueprint $table) {
            DB::statement("ALTER TABLE partial_payments ALTER COLUMN currency SET DEFAULT 'IQD'");
        });
    }
};

