# 🚀 دليل النشر على Hostinger - Deployment Guide

## 📋 نظرة عامة

هذا الدليل يشرح كيفية نشر مشروع Laravel على Hostinger وجعل التحديثات تتم بتعليمة واحدة.

---

## ✅ المتطلبات الأساسية

-   حساب Hostinger مع دومين فرعي `app.weventex.com`
-   وصول إلى cPanel
-   Git على جهازك المحلي
-   Composer على السيرفر (عادة متوفر في Hostinger)

---

## 📝 الخطوة 1: إعداد قاعدة البيانات على Hostinger

### 1.1 إنشاء قاعدة البيانات

1. ادخل إلى **cPanel** في Hostinger
2. اذهب إلى **MySQL Databases**
3. أنشئ قاعدة بيانات جديدة:
    - **اسم قاعدة البيانات**: `weventex_radius_app` (مثال)
    - احفظ الاسم الكامل (عادة يكون `username_dbname`)
4. أنشئ مستخدم جديد:
    - **اسم المستخدم**: `weventex_radius_user` (مثال)
    - **كلمة المرور**: كلمة مرور قوية
    - احفظ الاسم الكامل (عادة يكون `username_dbuser`)
5. أضف المستخدم إلى قاعدة البيانات مع صلاحيات **ALL PRIVILEGES**

### 1.2 معلومات الاتصال

احفظ هذه المعلومات:

```
DB_HOST: localhost (أو IP المقدم من Hostinger)
DB_DATABASE: username_weventex_radius_app
DB_USERNAME: username_weventex_radius_user
DB_PASSWORD: [كلمة المرور التي أنشأتها]
```

---

## 📤 الخطوة 2: رفع المشروع لأول مرة

### 2.1 طريقة 1: عبر File Manager (للمرة الأولى)

1. في cPanel، اذهب إلى **File Manager**
2. اذهب إلى مجلد الدومين الفرعي (عادة `public_html/app` أو `public_html/app.weventex.com`)
3. ارفع ملفات المشروع:
    - **ملاحظة**: ارفع فقط الملفات الضرورية (لا ترفع `vendor` و `node_modules`)
    - استخدم **ZIP** ثم **Extract** في File Manager

### 2.2 طريقة 2: عبر Git (الأفضل - إذا كان متوفر)

1. في cPanel، افتح **Terminal** أو **SSH Access**
2. اذهب إلى مجلد الدومين:
    ```bash
    cd ~/public_html/app.weventex.com
    # أو
    cd ~/domains/app.weventex.com/public_html
    ```
3. استنسخ المشروع:
    ```bash
    git clone https://github.com/your-username/radius-app-backend.git .
    # أو إذا كان المجلد موجود، استخدم:
    git clone https://github.com/your-username/radius-app-backend.git temp
    cp -r temp/* .
    rm -rf temp
    ```

---

## ⚙️ الخطوة 3: إعداد ملف .env

### 3.1 إنشاء ملف .env

1. في File Manager، اذهب إلى مجلد المشروع
2. أنشئ ملف `.env` (إذا لم يكن موجود)
3. انسخ محتوى من `.env.example` أو استخدم القالب التالي:

```env
APP_NAME="Radius App Backend"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_TIMEZONE=UTC
APP_URL=https://app.weventex.com

APP_LOCALE=ar
APP_FALLBACK_LOCALE=en

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=username_weventex_radius_app
DB_USERNAME=username_weventex_radius_user
DB_PASSWORD=your_password_here
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci

RADIUS_API_URL=http://38.156.75.137:3031
RADIUS_API_KEY=APP2025M

SANCTUM_STATEFUL_DOMAINS=app.weventex.com,weventex.com

# Firebase (إذا كنت تستخدمه)
FCM_SERVER_KEY=your_fcm_key_here

# Mail Settings (اختياري)
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=587
MAIL_USERNAME=your_email@weventex.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@weventex.com
MAIL_FROM_NAME="${APP_NAME}"
```

### 3.2 توليد APP_KEY

في Terminal أو SSH:

```bash
cd ~/public_html/app.weventex.com
php artisan key:generate
```

---

## 🗄️ الخطوة 4: تثبيت Dependencies وتشغيل Migrations

في Terminal:

```bash
cd ~/public_html/app.weventex.com

# تثبيت Composer dependencies
composer install --no-dev --optimize-autoloader

# تشغيل Migrations
php artisan migrate --force

# تحسين الأداء
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🔧 الخطوة 5: إعداد Permissions

```bash
cd ~/public_html/app.weventex.com

# إعطاء صلاحيات للمجلدات
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

---

## 🌐 الخطوة 6: إعداد Nginx/Apache (عادة Hostinger يقوم بهذا تلقائياً)

### إذا كان لديك وصول لـ Nginx:

تأكد من أن `public` هو Document Root:

```nginx
root /home/username/public_html/app.weventex.com/public;
index index.php index.html;
```

### في cPanel:

1. اذهب إلى **Subdomains**
2. تأكد من أن `app.weventex.com` يشير إلى المجلد الصحيح
3. Document Root يجب أن يكون: `public_html/app.weventex.com/public`

---

## 📅 الخطوة 7: إعداد Cron Jobs

في cPanel، اذهب إلى **Cron Jobs** وأضف:

```bash
* * * * * cd /home/username/public_html/app.weventex.com && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🚀 الخطوة 8: إعداد Deployment التلقائي

### الطريقة 1: GitHub Actions + SSH (إذا كان SSH متاح)

#### 8.1 إعداد SSH Key على Hostinger

1. في Terminal على Hostinger:

    ```bash
    ssh-keygen -t rsa -b 4096 -C "deploy@weventex.com"
    # اضغط Enter للقيم الافتراضية
    cat ~/.ssh/id_rsa.pub
    # انسخ المفتاح العام
    ```

2. أضف المفتاح إلى `authorized_keys`:
    ```bash
    cat ~/.ssh/id_rsa.pub >> ~/.ssh/authorized_keys
    chmod 600 ~/.ssh/authorized_keys
    ```

#### 8.2 إعداد GitHub Secrets

في GitHub Repository → Settings → Secrets and variables → Actions:

أضف:

-   `SSH_HOST`: `app.weventex.com` أو IP السيرفر
-   `SSH_USER`: اسم المستخدم في Hostinger
-   `SSH_KEY`: محتوى الملف `~/.ssh/id_rsa` (المفتاح الخاص)

#### 8.3 إنشاء GitHub Action

أنشئ ملف `.github/workflows/deploy.yml`:

```yaml
name: Deploy to Hostinger

on:
    push:
        branches: ["main"]

jobs:
    deploy:
        runs-on: ubuntu-latest

        steps:
            - name: Deploy via SSH
              uses: appleboy/ssh-action@v1.0.3
              with:
                  host: ${{ secrets.SSH_HOST }}
                  username: ${{ secrets.SSH_USER }}
                  key: ${{ secrets.SSH_KEY }}
                  script: |
                      cd ~/public_html/app.weventex.com
                      git pull origin main
                      composer install --no-dev --optimize-autoloader
                      php artisan migrate --force
                      php artisan config:cache
                      php artisan route:cache
                      php artisan view:cache
                      echo "✅ Deployment completed successfully!"
```

### الطريقة 2: سكربت Deployment محلي (بدون SSH)

أنشئ ملف `deploy.ps1` (PowerShell) أو `deploy.bat`:

#### deploy.ps1

```powershell
# إعدادات Hostinger
$FTP_HOST = "ftp.app.weventex.com"
$FTP_USER = "your_ftp_username"
$FTP_PASS = "your_ftp_password"
$REMOTE_PATH = "/public_html/app.weventex.com"

# إعدادات Git
$BRANCH = "main"

Write-Host "🚀 Starting deployment..." -ForegroundColor Green

# 1. تحديث الكود من Git
Write-Host "📥 Pulling latest changes..." -ForegroundColor Yellow
git pull origin $BRANCH

# 2. تحديث Composer dependencies
Write-Host "📦 Updating dependencies..." -ForegroundColor Yellow
composer install --no-dev --optimize-autoloader

# 3. رفع الملفات عبر FTP
Write-Host "📤 Uploading files..." -ForegroundColor Yellow

# استخدم WinSCP أو FileZilla CLI
# أو استخدم PowerShell FTP module

# 4. تشغيل أوامر Laravel على السيرفر (يحتاج SSH)
Write-Host "⚙️ Running Laravel commands..." -ForegroundColor Yellow
# هذا يحتاج SSH access

Write-Host "✅ Deployment completed!" -ForegroundColor Green
```

#### deploy.bat (بديل بسيط)

```batch
@echo off
echo 🚀 Starting deployment...

echo 📥 Pulling latest changes...
git pull origin main

echo 📦 Installing dependencies...
composer install --no-dev --optimize-autoloader

echo 📤 Files ready for upload!
echo.
echo ⚠️  Manual step required:
echo    1. Upload files via FTP/SFTP to Hostinger
echo    2. SSH to server and run:
echo       php artisan migrate --force
echo       php artisan config:cache
echo       php artisan route:cache

echo.
echo ✅ Local preparation completed!
pause
```

### الطريقة 3: استخدام Deployer (الأكثر احترافية)

1. تثبيت Deployer:

    ```bash
    composer require deployer/deployer --dev
    ```

2. أنشئ ملف `deploy.php`:

```php
<?php
namespace Deployer;

require 'recipe/laravel.php';

// Configuration
set('application', 'Radius App Backend');
set('repository', 'https://github.com/your-username/radius-app-backend.git');
set('git_tty', true);
set('ssh_multiplexing', true);

// Hostinger server
host('app.weventex.com')
    ->set('remote_user', 'your_username')
    ->set('deploy_path', '~/public_html/app.weventex.com')
    ->set('branch', 'main');

// Tasks
task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'artisan:migrate',
    'artisan:config:cache',
    'artisan:route:cache',
    'artisan:view:cache',
    'deploy:publish',
]);

after('deploy:failed', 'deploy:unlock');
```

3. استخدمه:
    ```bash
    vendor/bin/dep deploy app.weventex.com
    ```

---

## 🔄 استخدام Deployment

### مع GitHub Actions:

```bash
git add .
git commit -m "Update: your changes"
git push origin main
# ✅ Deployment يحدث تلقائياً!
```

### مع Deployer:

```bash
vendor/bin/dep deploy
```

### مع السكربت المحلي:

```bash
.\deploy.ps1
# أو
deploy.bat
```

---

## ✅ قائمة التحقق (Checklist)

-   [ ] قاعدة البيانات منشأة ومتصلة
-   [ ] ملف `.env` معد بشكل صحيح
-   [ ] `APP_KEY` تم توليده
-   [ ] Dependencies مثبتة (`composer install`)
-   [ ] Migrations تم تشغيلها
-   [ ] Permissions صحيحة (`storage`, `bootstrap/cache`)
-   [ ] Document Root يشير إلى `public`
-   [ ] Cron Job معد
-   [ ] SSL Certificate مثبت (HTTPS)
-   [ ] Deployment script جاهز

---

## 🐛 حل المشاكل الشائعة

### المشكلة: 500 Internal Server Error

**الحل**:

1. تحقق من ملف `.env`
2. تحقق من Permissions:
    ```bash
    chmod -R 755 storage bootstrap/cache
    ```
3. تحقق من Logs:
    ```bash
    tail -f storage/logs/laravel.log
    ```

### المشكلة: Database Connection Error

**الحل**:

1. تحقق من `DB_HOST` (عادة `localhost` في Hostinger)
2. تحقق من اسم قاعدة البيانات (يجب أن يكون كاملاً مع prefix)
3. تحقق من كلمة المرور

### المشكلة: Composer not found

**الحل**:

```bash
# استخدم المسار الكامل
/usr/local/bin/composer install
# أو
php /usr/local/bin/composer install
```

### المشكلة: Permission Denied

**الحل**:

```bash
chmod -R 755 storage bootstrap/cache
chown -R username:username storage bootstrap/cache
```

---

## 🔒 الأمان

1. **لا ترفع `.env`** إلى Git
2. **استخدم HTTPS** دائماً
3. **غير `APP_DEBUG=false`** في الإنتاج
4. **استخدم كلمات مرور قوية** لقاعدة البيانات
5. **حدث Laravel** بانتظام

---

## 📞 الدعم

إذا واجهت مشاكل:

1. راجع Logs: `storage/logs/laravel.log`
2. تحقق من cPanel Error Logs
3. راجع [Hostinger Documentation](https://www.hostinger.com/tutorials)

---

**آخر تحديث**: 2024-12-20
