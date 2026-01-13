# 🔥 تعليمات إعداد Firebase - خطوة بخطوة

## ✅ ما تم إعداده تلقائياً

-   ✅ تثبيت حزمة `kreait/firebase-php`
-   ✅ إنشاء مجلد `storage/app/firebase/`
-   ✅ إضافة `service-account-key.json` إلى `.gitignore`
-   ✅ إنشاء جميع الملفات المطلوبة في Backend

---

## 📝 ما هو مطلوب منك (من Firebase Console)

### الخطوة 1: إنشاء/اختيار مشروع Firebase

1. اذهب إلى [Firebase Console](https://console.firebase.google.com)
2. أنشئ مشروع جديد أو اختر مشروع موجود
3. **انسخ Project ID** واحفظه

---

### الخطوة 2: الحصول على Server Key

1. في Firebase Console → **Project Settings** (⚙️)
2. اذهب إلى تبويب **Cloud Messaging**
3. في قسم **"Cloud Messaging API (Legacy)"**:
    - إذا لم يكن مفعلاً، انقر **"Enable"**
    - انسخ **"Server key"** واحفظه

---

### الخطوة 3: تحميل Service Account Key

1. في Firebase Console → **Project Settings** → **Service accounts**
2. انقر على **"Generate new private key"**
3. سيظهر تحذير → انقر **"Generate key"**
4. **حمّل ملف JSON** واحفظه
5. **أعد تسميته** إلى: `service-account-key.json`
6. **انقله** إلى: `storage/app/firebase/service-account-key.json`

---

### الخطوة 4: إضافة تطبيق Android (للتطبيق المستخدم)

1. في Firebase Console → **Add app** → **Android**
2. أدخل:
    - **Package name**: `com.yourapp.radius` (أو أي اسم فريد)
    - **App nickname**: `Radius User App`
3. انقر **"Register app"**
4. **حمّل ملف `google-services.json`** واحفظه (ستحتاجه لاحقاً في Next.js)

---

### الخطوة 5: إضافة تطبيق Web (للوحة التحكم)

1. في Firebase Console → **Add app** → **Web** (</>)
2. أدخل:
    - **App nickname**: `Radius Admin Dashboard`
3. انقر **"Register app"**
4. **انسخ إعدادات Firebase** واحفظها (ستحتاجها في Next.js):
    ```javascript
    {
      apiKey: "...",
      authDomain: "...",
      projectId: "...",
      storageBucket: "...",
      messagingSenderId: "...",
      appId: "..."
    }
    ```

---

### الخطوة 6: الحصول على VAPID Key (للوحة التحكم)

1. في Firebase Console → **Project Settings** → **Cloud Messaging**
2. في قسم **"Web configuration"** → **"Web Push certificates"**
3. إذا لم يكن موجوداً، انقر **"Generate key pair"**
4. **انسخ Key pair** (VAPID key) واحفظه

---

## ⚙️ إعداد Backend (Laravel)

### الخطوة 1: إضافة إعدادات في `.env`

افتح ملف `.env` وأضف هذه السطور:

```env
# Firebase Configuration
FIREBASE_CREDENTIALS_PATH=storage/app/firebase/service-account-key.json
FIREBASE_PROJECT_ID=your-project-id-here
FIREBASE_SERVER_KEY=your-server-key-here
```

**استبدل:**

-   `your-project-id-here` → Project ID من الخطوة 1
-   `your-server-key-here` → Server Key من الخطوة 2

---

### الخطوة 2: التحقق من ملف Service Account Key

تأكد من أن الملف موجود في:

```
storage/app/firebase/service-account-key.json
```

---

## ✅ قائمة التحقق النهائية

### من Firebase Console:

-   [ ] إنشاء/اختيار مشروع Firebase
-   [ ] نسخ Project ID
-   [ ] الحصول على Server Key
-   [ ] تحميل Service Account Key ووضعه في `storage/app/firebase/`
-   [ ] إضافة تطبيق Android
-   [ ] تحميل `google-services.json`
-   [ ] إضافة تطبيق Web
-   [ ] نسخ إعدادات Firebase للويب
-   [ ] الحصول على VAPID Key

### من Backend:

-   [ ] إضافة إعدادات Firebase في `.env`
-   [ ] وضع `service-account-key.json` في `storage/app/firebase/`

---

## 🧪 اختبار الإعداد

بعد إكمال جميع الخطوات، يمكنك اختبار إرسال إشعار:

### من Tinker:

```bash
php artisan tinker
```

```php
$user = App\Models\AppUser::first();
$notificationService = app(App\Services\NotificationService::class);

$notificationService->createNotification([
    'title' => 'اختبار',
    'body' => 'هذا إشعار تجريبي من Backend',
    'type' => 'system',
    'priority' => 1,
], [$user->id], 'specific');
```

---

## 📞 إذا واجهت مشاكل

1. **تحقق من سجلات Laravel**: `storage/logs/laravel.log`
2. **تحقق من المسار**: `storage/app/firebase/service-account-key.json`
3. **تحقق من صحة القيم** في `.env`
4. **تأكد من أن الملف موجود** ويمكن قراءته

---

## 🚀 بعد إكمال الإعداد

بعد إكمال جميع الخطوات، سيتم إرسال الإشعارات تلقائياً عند:

-   ✅ قبول/رفض طلب دفع
-   ✅ إضافة دفع نقدي
-   ✅ إنشاء إشعار يدوي
-   ✅ إشعارات تلقائية (انتهاء الاشتراك، بث مباشر، إلخ)

---

## 📚 ملفات مرجعية

-   `FIREBASE_PUSH_NOTIFICATIONS_COMPLETE_GUIDE.md` - دليل شامل
-   `BACKEND_REQUIREMENTS_ARABIC.md` - متطلبات Backend
-   `NEXTJS_FIREBASE_CODE_EXAMPLES.md` - كود Next.js جاهز
