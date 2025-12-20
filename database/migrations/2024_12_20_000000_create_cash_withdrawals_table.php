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
        Schema::create('cash_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('withdrawn_by')->constrained('app_users')->onDelete('restrict')->comment('المستخدم الذي قام بالسحب');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD')->comment('USD, SYP, or TRY');
            $table->text('reason')->comment('سبب السحب');
            $table->text('description')->nullable()->comment('وصف تفصيلي للسحب');
            $table->string('reference_number', 100)->nullable()->comment('رقم مرجعي (فاتورة، إيصال، إلخ)');
            $table->enum('category', [
                'operational',      // تشغيلية
                'maintenance',     // صيانة
                'salary',          // رواتب
                'utilities',       // فواتير
                'supplies',        // مستلزمات
                'marketing',       // تسويق
                'emergency',       // طوارئ
                'other'            // أخرى
            ])->default('other');
            $table->date('withdrawal_date')->comment('تاريخ السحب');
            $table->json('attachments')->nullable()->comment('مرفقات (صور فواتير، إيصالات)');
            $table->timestamps();

            // Indexes
            $table->index('withdrawn_by');
            $table->index('withdrawal_date');
            $table->index('currency');
            $table->index('category');
            $table->index(['withdrawal_date', 'currency']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_withdrawals');
    }
};

