<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;

class AppSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // روابط التواصل الاجتماعي
            [
                'key' => 'whatsapp',
                'value' => null,
                'type' => 'social_link',
                'label' => 'الواتساب',
                'label_en' => 'WhatsApp',
                'description' => 'رابط الواتساب (رقم فريق الدعم)',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'whatsapp_group',
                'value' => null,
                'type' => 'social_link',
                'label' => 'مجموعة الواتساب',
                'label_en' => 'WhatsApp Group',
                'description' => 'رابط مجموعة الواتساب الخاصة بالتطبيق',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'facebook',
                'value' => null,
                'type' => 'social_link',
                'label' => 'الفيسبوك',
                'label_en' => 'Facebook',
                'description' => 'رابط صفحة الفيسبوك',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'key' => 'twitter',
                'value' => null,
                'type' => 'social_link',
                'label' => 'تويتر',
                'label_en' => 'Twitter',
                'description' => 'رابط حساب تويتر',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'key' => 'instagram',
                'value' => null,
                'type' => 'social_link',
                'label' => 'إنستغرام',
                'label_en' => 'Instagram',
                'description' => 'رابط حساب إنستغرام',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'key' => 'linkedin',
                'value' => null,
                'type' => 'social_link',
                'label' => 'لينكد إن',
                'label_en' => 'LinkedIn',
                'description' => 'رابط صفحة لينكد إن',
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'key' => 'tiktok',
                'value' => null,
                'type' => 'social_link',
                'label' => 'تيك توك',
                'label_en' => 'TikTok',
                'description' => 'رابط حساب تيك توك',
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'key' => 'contact_phone',
                'value' => null,
                'type' => 'general_setting',
                'label' => 'رقم الاتصال',
                'label_en' => 'Contact Phone',
                'description' => 'رقم هاتف الاتصال',
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'key' => 'app_version',
                'value' => '1.0.0',
                'type' => 'general_setting',
                'label' => 'إصدار التطبيق',
                'label_en' => 'App Version',
                'description' => 'رقم إصدار التطبيق الحالي',
                'is_active' => true,
                'sort_order' => 20,
            ],
            [
                'key' => 'copyright',
                'value' => '© 2025 جميع الحقوق محفوظة',
                'type' => 'general_setting',
                'label' => 'الحقوق',
                'label_en' => 'Copyright',
                'description' => 'نص حقوق النشر',
                'is_active' => true,
                'sort_order' => 30,
            ],
            // إعدادات إضافية (اختيارية)
            [
                'key' => 'app_name',
                'value' => 'تطبيق WiFi',
                'type' => 'general_setting',
                'label' => 'اسم التطبيق',
                'label_en' => 'App Name',
                'description' => 'اسم التطبيق المعروض للمستخدمين',
                'is_active' => true,
                'sort_order' => 40,
            ],
            [
                'key' => 'support_email',
                'value' => null,
                'type' => 'general_setting',
                'label' => 'البريد الإلكتروني للدعم',
                'label_en' => 'Support Email',
                'description' => 'البريد الإلكتروني للدعم الفني',
                'is_active' => true,
                'sort_order' => 50,
            ],
            [
                'key' => 'office_address',
                'value' => null,
                'type' => 'general_setting',
                'label' => 'عنوان المكتب',
                'label_en' => 'Office Address',
                'description' => 'عنوان المكتب الرئيسي',
                'is_active' => true,
                'sort_order' => 60,
            ],
        ];

        foreach ($settings as $setting) {
            AppSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
