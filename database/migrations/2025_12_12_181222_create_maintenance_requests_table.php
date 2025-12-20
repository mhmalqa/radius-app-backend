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
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('app_users')->onDelete('cascade');
            $table->text('address')->comment('عنوان الصيانة - إجباري');
            $table->json('subscription_data')->nullable()->comment('بيانات المشترك من الراديوس');
            $table->text('description')->nullable()->comment('وصف المشكلة');
            $table->string('status', 20)->default('pending')->comment('pending: قيد الانتظار, submitted: تم التقديم, in_progress: قيد التنفيذ, completed: مكتمل, cancelled: ملغي');
            $table->foreignId('assigned_to')->nullable()->constrained('app_users')->onDelete('set null')->comment('المسؤول المكلف');
            $table->text('notes')->nullable()->comment('ملاحظات من المسؤول');
            $table->timestamp('completed_at')->nullable()->comment('تاريخ الإكمال');
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index('created_at');
            $table->index('assigned_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');
    }
};
