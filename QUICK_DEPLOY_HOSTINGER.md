# ⚡ دليل سريع للنشر على Hostinger

## 🎯 الخطوات السريعة

### 1️⃣ إعداد قاعدة البيانات
- cPanel → MySQL Databases
- أنشئ قاعدة بيانات + مستخدم
- احفظ: `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

### 2️⃣ رفع المشروع
```bash
# في Terminal على Hostinger
cd ~/public_html/app.weventex.com
git clone https://github.com/your-repo/radius-app-backend.git .
```

### 3️⃣ إعداد .env
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.weventex.com
DB_HOST=localhost
DB_DATABASE=username_dbname
DB_USERNAME=username_dbuser
DB_PASSWORD=your_password
```

### 4️⃣ التثبيت
```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
chmod -R 755 storage bootstrap/cache
```

### 5️⃣ إعداد Cron Job
```bash
* * * * * cd /home/username/public_html/app.weventex.com && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🚀 النشر التلقائي

### الطريقة 1: GitHub Actions (الأفضل)

1. أضف Secrets في GitHub:
   - `HOSTINGER_SSH_HOST`: `app.weventex.com`
   - `HOSTINGER_SSH_USER`: اسم المستخدم
   - `HOSTINGER_SSH_KEY`: SSH private key

2. كل `git push` = نشر تلقائي! ✅

### الطريقة 2: سكربت محلي

```bash
# Windows
.\deploy.ps1
# أو
deploy.bat
```

ثم ارفع الملفات يدوياً عبر FTP.

---

## 📝 ملاحظات مهمة

- ✅ Document Root يجب أن يكون: `public_html/app.weventex.com/public`
- ✅ تأكد من SSL (HTTPS)
- ✅ `APP_DEBUG=false` في الإنتاج
- ✅ لا ترفع `.env` إلى Git

---

## 🔗 للمزيد

راجع: `HOSTINGER_DEPLOYMENT_GUIDE.md` للتفاصيل الكاملة
