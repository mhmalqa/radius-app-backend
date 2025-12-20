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
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->boolean('is_paid')->default(false)
                ->after('status')
                ->comment('هل تم الدفع فعلياً (true: مدفوع, false: غير مدفوع/مؤجل)');
            $table->boolean('is_deferred')->default(false)
                ->after('is_paid')
                ->comment('هل الدفعة مؤجلة (دفع مؤجل)');
            $table->timestamp('paid_at')->nullable()
                ->after('is_deferred')
                ->comment('تاريخ الدفع الفعلي');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn(['is_paid', 'is_deferred', 'paid_at']);
        });
    }
};
