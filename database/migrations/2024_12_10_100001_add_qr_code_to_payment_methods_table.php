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
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->string('qr_code', 500)->nullable()->after('icon')->comment('QR Code image path or data');
            $table->string('code', 100)->nullable()->after('qr_code')->comment('Code to copy (e.g., account number, wallet number)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn(['qr_code', 'code']);
        });
    }
};

