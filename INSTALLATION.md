# 📦 دليل التثبيت والإعداد

## المتطلبات

-   PHP >= 8.2
-   Composer
-   MySQL/MariaDB أو SQLite
-   Laravel 11

## خطوات التثبيت

### 1. تثبيت الحزم المطلوبة

```bash
composer install
```

### 2. تثبيت Laravel Sanctum

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

### 3. إعداد ملف البيئة

انسخ ملف `.env.example` إلى `.env`:

```bash
cp .env.example .env
```

قم بتحديث المتغيرات التالية في ملف `.env`:

```env
APP_NAME="Radius App Backend"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=radius_app
DB_USERNAME=root
DB_PASSWORD=

# Radius API Configuration
RADIUS_API_URL=https://your-radius-api.com
RADIUS_API_KEY=your-radius-api-key

# Sanctum Configuration
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
```

### 4. توليد مفتاح التطبيق

```bash
php artisan key:generate
```

### 5. إنشاء رابط التخزين

```bash
php artisan storage:link
```

### 6. تشغيل المايجريشن

```bash
php artisan migrate
```

### 7. (اختياري) إضافة بيانات تجريبية

```bash
php artisan db:seed
```

## 🔧 الإعدادات الإضافية

### إعداد CORS

قم بتحديث `config/cors.php` إذا كنت تحتاج إلى إعدادات CORS خاصة.

### إعداد Queue (للإشعارات)

إذا كنت تريد استخدام Queue لإرسال الإشعارات:

```bash
php artisan queue:table
php artisan migrate
```

ثم قم بتحديث `.env`:

```env
QUEUE_CONNECTION=database
```

### إعداد Push Notifications

للإشعارات الفورية، ستحتاج إلى:

1. **Firebase Cloud Messaging (FCM)** للـ Android
2. **Apple Push Notification Service (APNS)** للـ iOS

قم بإضافة المفاتيح في `.env`:

```env
FCM_SERVER_KEY=your-fcm-server-key
APNS_KEY_PATH=/path/to/apns-key.p8
APNS_KEY_ID=your-apns-key-id
APNS_TEAM_ID=your-apns-team-id
APNS_BUNDLE_ID=your-bundle-id
```

## 🚀 تشغيل التطبيق

### Development Server

```bash
php artisan serve
```

التطبيق سيكون متاحاً على: `http://localhost:8000`

### Production

استخدم خادم ويب مثل Nginx أو Apache مع PHP-FPM.

## 📝 ملاحظات مهمة

1. **الأمان**: تأكد من تعطيل `APP_DEBUG=false` في الإنتاج
2. **الصلاحيات**: تأكد من تعيين صلاحيات صحيحة لمجلدات `storage` و `bootstrap/cache`
3. **النسخ الاحتياطي**: قم بعمل نسخ احتياطي دوري لقاعدة البيانات
4. **المراقبة**: راقب ملفات الـ logs في `storage/logs`

## 🔍 اختبار الـ API

يمكنك استخدام Postman أو أي أداة مشابهة لاختبار الـ API endpoints.

راجع ملف `README_API.md` للحصول على تفاصيل الـ API.

## 🐛 حل المشاكل الشائعة

### خطأ في المايجريشن

```bash
php artisan migrate:fresh
```

### مشكلة في الصلاحيات

```bash
chmod -R 775 storage bootstrap/cache
```

### مشكلة في الـ Cache

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

## 📚 الوثائق

-   [Laravel Documentation](https://laravel.com/docs)
-   [Laravel Sanctum](https://laravel.com/docs/sanctum)
-   [API Documentation](./README_API.md)
