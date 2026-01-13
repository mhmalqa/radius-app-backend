# ⚡ دليل البدء السريع: Firebase Push Notifications

## ✅ ما تم إعداده تلقائياً

تم إعداد كل شيء في Backend بشكل احترافي:

- ✅ تثبيت حزمة `kreait/firebase-php`
- ✅ إنشاء `FirebaseMessagingService`
- ✅ تحديث `NotificationService` لاستخدام Firebase
- ✅ إنشاء `DeviceTokenController` و Routes
- ✅ إنشاء مجلد `storage/app/firebase/`
- ✅ إضافة `service-account-key.json` إلى `.gitignore`
- ✅ تحديث `config/services.php`

---

## 🎯 ما هو مطلوب منك (3 خطوات فقط)

### الخطوة 1: من Firebase Console

#### أ. الحصول على Project ID:
1. Firebase Console → Project Settings → General
2. انسخ **Project ID**

#### ب. الحصول على Server Key:
1. Firebase Console → Project Settings → Cloud Messaging
2. انسخ **Server key**

#### ج. تحميل Service Account Key:
1. Firebase Console → Project Settings → Service accounts
2. انقر **"Generate new private key"**
3. حمّل ملف JSON
4. أعد تسميته إلى `service-account-key.json`
5. ضعه في: `storage/app/firebase/service-account-key.json`

---

### الخطوة 2: إضافة إعدادات في `.env`

افتح ملف `.env` وأضف:

```env
# Firebase Configuration
FIREBASE_CREDENTIALS_PATH=storage/app/firebase/service-account-key.json
FIREBASE_PROJECT_ID=your-project-id-here
FIREBASE_SERVER_KEY=your-server-key-here
```

**استبدل:**
- `your-project-id-here` → Project ID من الخطوة 1
- `your-server-key-here` → Server Key من الخطوة 1

---

### الخطوة 3: التحقق

```bash
php artisan config:clear
```

---

## ✅ هذا كل شيء!

بعد إكمال هذه الخطوات، Backend جاهز لإرسال الإشعارات.

---

## 🧪 اختبار سريع

```bash
php artisan tinker
```

```php
$user = App\Models\AppUser::first();
$notificationService = app(App\Services\NotificationService::class);

$notificationService->createNotification([
    'title' => 'اختبار',
    'body' => 'هذا إشعار تجريبي',
    'type' => 'system',
], [$user->id], 'specific');
```

---

## 📚 ملفات مرجعية

- `FIREBASE_SETUP_INSTRUCTIONS.md` - تعليمات مفصلة خطوة بخطوة
- `BACKEND_REQUIREMENTS_ARABIC.md` - متطلبات Backend
- `FIREBASE_PUSH_NOTIFICATIONS_COMPLETE_GUIDE.md` - دليل شامل

---

## ⚠️ ملاحظات مهمة

1. **لا ترفع `service-account-key.json` إلى Git** - تم إضافته تلقائياً إلى `.gitignore`
2. **تأكد من صحة المسار** في `FIREBASE_CREDENTIALS_PATH`
3. **تأكد من صحة Project ID و Server Key** في `.env`

