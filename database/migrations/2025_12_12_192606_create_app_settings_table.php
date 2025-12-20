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
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique()->comment('مفتاح الإعداد (مثل: whatsapp_group, facebook_page, etc.)');
            $table->text('value')->nullable()->comment('قيمة الإعداد (الرابط أو النص)');
            $table->string('type', 50)->default('general')->comment('نوع الإعداد: social_link, general_setting');
            $table->string('label', 255)->nullable()->comment('التسمية بالعربية');
            $table->string('label_en', 255)->nullable()->comment('التسمية بالإنجليزية');
            $table->text('description')->nullable()->comment('وصف الإعداد');
            $table->boolean('is_active')->default(true)->comment('هل الإعداد نشط');
            $table->integer('sort_order')->default(0)->comment('ترتيب العرض');
            $table->timestamps();

            // Indexes
            $table->index(['type', 'is_active']);
            $table->index('key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
