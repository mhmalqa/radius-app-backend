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
        Schema::table('user_subscriptions', function (Blueprint $table) {
            // معلومات إضافية من Radius
            $table->string('firstname', 255)->nullable()->after('radius_username');
            $table->string('mobile', 20)->nullable()->after('firstname');
            $table->boolean('is_active_radius')->nullable()->after('auto_renew')->comment('Active status from Radius');
            $table->boolean('is_online')->nullable()->after('is_active_radius')->comment('Online status from Radius');
            
            // معلومات السرعة
            $table->bigInteger('download_kbps')->nullable()->after('is_online');
            $table->bigInteger('upload_kbps')->nullable()->after('download_kbps');
            $table->decimal('download_mbps', 10, 2)->nullable()->after('upload_kbps');
            $table->decimal('upload_mbps', 10, 2)->nullable()->after('download_mbps');
            
            // تفاصيل الاستخدام
            $table->decimal('download_MB', 15, 2)->nullable()->after('upload_mbps');
            $table->decimal('upload_MB', 15, 2)->nullable()->after('download_MB');
            $table->decimal('total_MB', 15, 2)->nullable()->after('upload_MB');
            
            // JSON field لحفظ البيانات الكاملة من Radius
            $table->json('raw_data')->nullable()->after('total_MB')->comment('Complete response from Radius API');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'firstname',
                'mobile',
                'is_active_radius',
                'is_online',
                'download_kbps',
                'upload_kbps',
                'download_mbps',
                'upload_mbps',
                'download_MB',
                'upload_MB',
                'total_MB',
                'raw_data',
            ]);
        });
    }
};

