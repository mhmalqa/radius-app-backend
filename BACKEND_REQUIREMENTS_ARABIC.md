# 🔧 متطلبات Backend: Firebase Push Notifications

## ✅ ما تم إنجازه تلقائياً

تم إنشاء الملفات التالية تلقائياً:

1. ✅ `app/Services/FirebaseMessagingService.php` - Service لإرسال الإشعارات
2. ✅ `app/Http/Controllers/DeviceTokenController.php` - Controller لحفظ Device Tokens
3. ✅ تحديث `app/Services/NotificationService.php` - ربط Firebase
4. ✅ تحديث `config/services.php` - إضافة إعدادات Firebase
5. ✅ تحديث `routes/api.php` - إضافة Routes

---

## 📝 ما هو مطلوب منك (3 خطوات فقط)

### الخطوة 1: تثبيت حزمة Firebase

```bash
composer require kreait/firebase-php
```

---

### الخطوة 2: إضافة إعدادات Firebase في ملف `.env`

افتح ملف `.env` وأضف هذه السطور:

```env
# Firebase Configuration
FIREBASE_CREDENTIALS_PATH=storage/app/firebase/service-account-key.json
FIREBASE_PROJECT_ID=your-project-id-here
FIREBASE_SERVER_KEY=your-server-key-here
```

**استبدل:**
- `your-project-id-here` → Project ID من Firebase Console
- `your-server-key-here` → Server Key من Firebase Console

---

### الخطوة 3: رفع ملف Service Account Key

#### أ. من Firebase Console:

1. اذهب إلى **Firebase Console** → **Project Settings** → **Service accounts**
2. انقر على **"Generate new private key"**
3. **حمّل ملف JSON** واحفظه

#### ب. في Backend:

1. أنشئ مجلد:
   ```bash
   mkdir -p storage/app/firebase
   ```

2. ضع ملف `service-account-key.json` في المجلد:
   ```
   storage/app/firebase/service-account-key.json
   ```

3. تأكد من إضافة إلى `.gitignore`:
   ```
   storage/app/firebase/service-account-key.json
   ```

---

## ✅ هذا كل شيء!

بعد إكمال هذه الخطوات الثلاث، سيكون Backend جاهزاً لإرسال الإشعارات.

---

## 🧪 اختبار سريع

بعد إكمال الخطوات، يمكنك اختبار إرسال إشعار من Backend:

### من Tinker:

```bash
php artisan tinker
```

```php
$user = App\Models\AppUser::first();
$notification = App\Models\Notification::create([
    'title' => 'اختبار',
    'body' => 'هذا إشعار تجريبي',
    'type' => 'system',
]);

$notificationService = app(App\Services\NotificationService::class);
$notificationService->createNotification([
    'title' => 'اختبار',
    'body' => 'هذا إشعار تجريبي',
    'type' => 'system',
], [$user->id], 'specific');
```

---

## 📋 قائمة التحقق

- [ ] تثبيت `kreait/firebase-php`
- [ ] إضافة إعدادات Firebase في `.env`
- [ ] رفع ملف `service-account-key.json` إلى `storage/app/firebase/`
- [ ] إضافة `service-account-key.json` إلى `.gitignore`

---

## ⚠️ ملاحظات مهمة

1. **لا ترفع ملف `service-account-key.json` إلى Git** - هذا ملف حساس!
2. **تأكد من صحة المسار** في `FIREBASE_CREDENTIALS_PATH`
3. **تأكد من صحة Project ID و Server Key** في `.env`

---

## 🔍 كيفية الحصول على القيم المطلوبة

### 1. Project ID:
- Firebase Console → **Project Settings** → **General**
- انسخ **Project ID**

### 2. Server Key:
- Firebase Console → **Project Settings** → **Cloud Messaging**
- في قسم **"Cloud Messaging API (Legacy)"**
- انسخ **Server key**

### 3. Service Account Key:
- Firebase Console → **Project Settings** → **Service accounts**
- انقر **"Generate new private key"**
- حمّل ملف JSON

---

## 📞 إذا واجهت مشاكل

1. تحقق من سجلات Laravel: `storage/logs/laravel.log`
2. تأكد من أن المسار صحيح: `storage/app/firebase/service-account-key.json`
3. تأكد من أن الملف موجود ويمكن قراءته
4. تحقق من صحة جميع القيم في `.env`

---

## 🚀 بعد إكمال الخطوات

بعد إكمال الخطوات الثلاث، سيتم إرسال الإشعارات تلقائياً عند:
- ✅ قبول/رفض طلب دفع
- ✅ إضافة دفع نقدي
- ✅ إنشاء إشعار يدوي
- ✅ إشعارات تلقائية (انتهاء الاشتراك، بث مباشر، إلخ)

