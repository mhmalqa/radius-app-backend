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
            $table->string('payment_type', 20)->default('online')
                ->after('user_id')
                ->comment('online: payment request from user, cash: cash payment added by admin/accountant');
            $table->foreignId('created_by')->nullable()
                ->after('payment_type')
                ->constrained('app_users')
                ->onDelete('set null')
                ->comment('Admin/Accountant who created the cash payment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['payment_type', 'created_by']);
        });
    }
};
