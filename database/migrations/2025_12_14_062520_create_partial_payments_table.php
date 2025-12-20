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
        Schema::create('partial_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_request_id')->constrained('payment_requests')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('app_users')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('app_users')->onDelete('cascade')->comment('المستخدم الذي أضاف الدفعة الجزئية');
            $table->decimal('amount', 10, 2)->comment('مبلغ الدفعة الجزئية');
            $table->string('currency', 3)->default('IQD');
            $table->date('payment_date')->comment('تاريخ الدفعة');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->timestamps();

            // Indexes
            $table->index(['payment_request_id', 'payment_date']);
            $table->index('payment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partial_payments');
    }
};
