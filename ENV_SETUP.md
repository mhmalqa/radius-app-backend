# ⚙️ دليل إعداد ملف .env

## 📋 نظرة عامة

ملف `.env` يحتوي على جميع الإعدادات الخاصة بالبيئة (Environment Variables) للمشروع.

## 🔧 الإعدادات الأساسية

### Application Settings

```env
APP_NAME="Radius App Backend"
APP_ENV=local                    # local, staging, production
APP_KEY=                         # سيتم توليده تلقائياً بـ php artisan key:generate
APP_DEBUG=true                   # false في الإنتاج
APP_TIMEZONE=UTC
APP_URL=http://localhost
```

### Locale Settings

```env
APP_LOCALE=ar                    # اللغة الافتراضية (ar, en)
APP_FALLBACK_LOCALE=en           # اللغة الاحتياطية
APP_FAKER_LOCALE=en_US           # لغة البيانات التجريبية
```

## 🗄️ إعدادات قاعدة البيانات

### MySQL (مُوصى به)

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=radius_app
DB_USERNAME=root
DB_PASSWORD=your_password
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

**ملاحظة مهمة**:

-   `utf8mb4_unicode_ci` متوافق مع جميع إصدارات MySQL و MariaDB
-   إذا كنت تستخدم MySQL 8.0+ وترغب في `utf8mb4_0900_ai_ci`، يمكنك تغييره في `.env`

### SQLite (للاختبار)

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

## 🔐 إعدادات Radius API

```env
RADIUS_API_URL=http://38.156.75.137:3031
RADIUS_API_KEY=APP2025M
```

**ملاحظة**: هذه القيم موجودة كقيم افتراضية في `config/services.php`، لذا يمكنك تركها فارغة إذا كانت نفس القيم.

## 🔑 إعدادات Sanctum (API Authentication)

```env
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
```

أضف جميع النطاقات التي ستستخدم API من خلالها.

## 📧 إعدادات البريد الإلكتروني (اختياري)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

## 📱 إعدادات Push Notifications (اختياري)

### Firebase Cloud Messaging (FCM)

```env
FCM_SERVER_KEY=your_fcm_server_key
```

### Apple Push Notification Service (APNS)

```env
APNS_KEY_PATH=/path/to/apns-key.p8
APNS_KEY_ID=your_apns_key_id
APNS_TEAM_ID=your_apns_team_id
APNS_BUNDLE_ID=your_bundle_id
```

## 🚀 خطوات الإعداد

### 1. نسخ ملف .env.example

```bash
cp .env.example .env
```

### 2. توليد مفتاح التطبيق

```bash
php artisan key:generate
```

### 3. تحديث إعدادات قاعدة البيانات

قم بتحديث:

-   `DB_DATABASE`
-   `DB_USERNAME`
-   `DB_PASSWORD`

### 4. تحديث إعدادات Radius (إذا لزم الأمر)

إذا كانت إعدادات Radius مختلفة، قم بتحديث:

-   `RADIUS_API_URL`
-   `RADIUS_API_KEY`

### 5. تحديث APP_URL

```env
APP_URL=http://your-domain.com
```

## ⚠️ ملاحظات أمنية مهمة

1. **لا ترفع ملف `.env` إلى Git**

    - ملف `.env` موجود في `.gitignore`
    - استخدم `.env.example` كقالب

2. **في الإنتاج**:

    ```env
    APP_ENV=production
    APP_DEBUG=false
    ```

3. **كلمات المرور**:

    - استخدم كلمات مرور قوية
    - لا تشارك ملف `.env` مع أي شخص

4. **APP_KEY**:
    - يجب أن يكون فريداً لكل بيئة
    - لا تشاركه أبداً

## 🔍 التحقق من الإعدادات

### التحقق من الاتصال بقاعدة البيانات

```bash
php artisan migrate:status
```

### التحقق من إعدادات Radius

```php
// في tinker
php artisan tinker
>>> config('services.radius.api_url')
>>> config('services.radius.api_key')
```

### التحقق من APP_KEY

```bash
php artisan key:generate --show
```

## 📝 مثال على ملف .env كامل

```env
APP_NAME="Radius App Backend"
APP_ENV=production
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
APP_DEBUG=false
APP_TIMEZONE=UTC
APP_URL=https://api.example.com

APP_LOCALE=ar
APP_FALLBACK_LOCALE=en

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=radius_app
DB_USERNAME=radius_user
DB_PASSWORD=secure_password_here

RADIUS_API_URL=http://38.156.75.137:3031
RADIUS_API_KEY=APP2025M

SANCTUM_STATEFUL_DOMAINS=api.example.com,app.example.com
```

## 🐛 حل المشاكل

### المشكلة: خطأ في الاتصال بقاعدة البيانات

**الحل**:

1. تحقق من `DB_HOST`, `DB_PORT`, `DB_DATABASE`
2. تحقق من `DB_USERNAME` و `DB_PASSWORD`
3. تأكد من أن MySQL يعمل

### المشكلة: خطأ Unknown collation 'utf8mb4_0900_ai_ci'

**الحل**:

هذا الخطأ يحدث في إصدارات MySQL القديمة (< 8.0) أو MariaDB. الحل:

1. أضف في ملف `.env`:

    ```env
    DB_COLLATION=utf8mb4_unicode_ci
    ```

2. أو استخدم `utf8mb4_general_ci`:

    ```env
    DB_COLLATION=utf8mb4_general_ci
    ```

3. ثم قم بتشغيل:
    ```bash
    php artisan config:clear
    php artisan migrate
    ```

### المشكلة: خطأ في APP_KEY

**الحل**:

```bash
php artisan key:generate
```

### المشكلة: خطأ في Radius API

**الحل**:

1. تحقق من `RADIUS_API_URL`
2. تحقق من `RADIUS_API_KEY`
3. تحقق من الاتصال بالشبكة

## 📞 الدعم

للمزيد من المعلومات:

-   راجع `config/` للمزيد من الإعدادات
-   راجع `INSTALLATION.md` لدليل التثبيت الكامل
