# 🚀 خطوات التنفيذ التفصيلية: Firebase Push Notifications

## 📋 نظرة عامة

هذا الملف يوضح الخطوات التفصيلية لتنفيذ Firebase Push Notifications خطوة بخطوة.

---

## ✅ ما ستحتاجه من Firebase

### 1. ملفات Firebase المطلوبة:

#### للتطبيق المستخدم (Android):
- ✅ **`google-services.json`** - من Firebase Console → Project Settings → Your apps → Android app

#### للوحة التحكم (Web):
- ✅ **Firebase Config** - من Firebase Console → Project Settings → Your apps → Web app
- ✅ **VAPID Key** - من Firebase Console → Project Settings → Cloud Messaging → Web configuration

#### للـ Backend:
- ✅ **Service Account Key (JSON)** - من Firebase Console → Project Settings → Service accounts → Generate new private key
- ✅ **Server Key** - من Firebase Console → Project Settings → Cloud Messaging → Server key

---

## 🔧 الجزء 1: إعداد Backend (Laravel)

### الخطوة 1.1: تثبيت حزمة Firebase

```bash
composer require kreait/firebase-php
```

### الخطوة 1.2: إضافة إعدادات في `.env`

```env
# Firebase Configuration
FIREBASE_CREDENTIALS_PATH=storage/app/firebase/service-account-key.json
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_SERVER_KEY=your-server-key-here
```

### الخطوة 1.3: رفع Service Account Key

1. أنشئ مجلد: `storage/app/firebase/`
2. ضع ملف `service-account-key.json` فيه
3. تأكد من إضافة إلى `.gitignore`:

```
storage/app/firebase/service-account-key.json
```

### الخطوة 1.4: إنشاء FirebaseMessagingService

سنقوم بإنشاء Service Class لإرسال الإشعارات عبر FCM.

### الخطوة 1.5: تحديث NotificationService

سنقوم بتحديث `sendPushNotification` لاستخدام Firebase.

### الخطوة 1.6: إنشاء API Endpoints

سنقوم بإنشاء endpoints لحفظ Device Tokens.

---

## 📱 الجزء 2: إعداد تطبيق المستخدم (Next.js)

### الخطوة 2.1: تثبيت الحزم

```bash
npm install firebase
```

### الخطوة 2.2: إعداد Firebase Config

أنشئ ملف `lib/firebase.js` مع إعدادات Firebase.

### الخطوة 2.3: إرسال FCM Token إلى Backend

عند تسجيل الدخول، أرسل Token إلى Backend.

### الخطوة 2.4: معالجة الإشعارات

استمع للإشعارات الواردة وعرضها.

---

## 🖥️ الجزء 3: إعداد لوحة التحكم (Next.js)

### الخطوة 3.1: تثبيت الحزم

```bash
npm install firebase
```

### الخطوة 3.2: إعداد Firebase Config

أنشئ ملف `lib/firebase-client.js`.

### الخطوة 3.3: إرسال FCM Token إلى Backend

عند تسجيل دخول المسؤول، أرسل Token إلى Backend.

---

## 📦 الجزء 4: تحويل Next.js إلى APK

### الخطوة 4.1: استخدام Capacitor

```bash
npm install @capacitor/core @capacitor/cli @capacitor/android
npx cap init
npx cap add android
```

### الخطوة 4.2: بناء التطبيق

```bash
npm run build
npx cap sync
npx cap open android
```

### الخطوة 4.3: في Android Studio

1. ضع `google-services.json` في `android/app/`
2. أضف التبعيات في `build.gradle`
3. أنشئ APK

---

## ⚡ الجزء 5: إرسال الإشعارات لحظياً

بعد إكمال جميع الخطوات، سيتم إرسال الإشعارات تلقائياً عند:
- ✅ قبول/رفض طلب دفع
- ✅ إضافة دفع نقدي
- ✅ إنشاء إشعار يدوي
- ✅ إشعارات تلقائية (انتهاء الاشتراك، بث مباشر، إلخ)

---

## 📝 الملفات التي سننشئها

### في Backend:

1. ✅ `app/Services/FirebaseMessagingService.php` - Service لإرسال الإشعارات
2. ✅ تحديث `app/Services/NotificationService.php` - استخدام Firebase
3. ✅ `app/Http/Controllers/DeviceTokenController.php` - API لحفظ Tokens
4. ✅ تحديث `routes/api.php` - إضافة Routes

### في تطبيق المستخدم (Next.js):

1. ✅ `lib/firebase.js` - إعدادات Firebase
2. ✅ `public/firebase-messaging-sw.js` - Service Worker
3. ✅ Hook لاستقبال الإشعارات

### في لوحة التحكم (Next.js):

1. ✅ `lib/firebase-client.js` - إعدادات Firebase
2. ✅ Hook لاستقبال الإشعارات

---

## 🎯 الخطوات التالية

بعد قراءة هذا الملف، سنقوم بتنفيذ:

1. ✅ إنشاء FirebaseMessagingService في Backend
2. ✅ تحديث NotificationService
3. ✅ إنشاء API Endpoints
4. ✅ إعطاءك كود Next.js جاهز للتطبيق
5. ✅ إعطاءك كود Next.js جاهز للوحة التحكم
6. ✅ دليل تحويل Next.js إلى APK

---

## ⚠️ ملاحظات مهمة

1. **الأمان**: لا ترفع ملفات Firebase إلى Git
2. **الاختبار**: اختبر على أجهزة حقيقية
3. **الأداء**: استخدم Queues لإرسال الإشعارات بكميات كبيرة
4. **التوكنات**: نظف التوكنات القديمة دورياً

---

## 📞 الدعم

إذا واجهت مشاكل، تحقق من:
- ✅ سجلات Firebase Console
- ✅ Laravel logs
- ✅ Browser/App console
- ✅ صحة جميع المفاتيح

