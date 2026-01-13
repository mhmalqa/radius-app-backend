# 📋 ملخص إعداد Firebase Push Notifications

## ✅ ما تم إنجازه في Backend

### 1. الملفات التي تم إنشاؤها:

- ✅ `app/Services/FirebaseMessagingService.php` - Service لإرسال الإشعارات عبر FCM
- ✅ `app/Http/Controllers/DeviceTokenController.php` - Controller لحفظ Device Tokens
- ✅ تحديث `app/Services/NotificationService.php` - ربط Firebase بإرسال الإشعارات
- ✅ تحديث `config/services.php` - إضافة إعدادات Firebase
- ✅ تحديث `routes/api.php` - إضافة Routes لحفظ Device Tokens

### 2. API Endpoints المتوفرة:

```
POST /api/user/device-tokens - حفظ Device Token (للمستخدم)
GET /api/user/device-tokens - عرض Device Tokens الخاصة بالمستخدم
DELETE /api/user/device-tokens/{id} - حذف Device Token
```

---

## 📝 ما هو مطلوب منك

### 1. من Firebase Console:

#### أ. إنشاء مشروع Firebase:
1. اذهب إلى [Firebase Console](https://console.firebase.google.com)
2. أنشئ مشروع جديد أو استخدم مشروع موجود

#### ب. إضافة تطبيق Android (للتطبيق المستخدم):
1. في Firebase Console → **Add app** → **Android**
2. أدخل **Package name** (مثلاً: `com.yourapp.radius`)
3. **حمّل ملف `google-services.json`** واحفظه

#### ج. إضافة تطبيق Web (للوحة التحكم):
1. في Firebase Console → **Add app** → **Web** (</>)
2. **انسخ إعدادات Firebase** (apiKey, authDomain, projectId, إلخ)

#### د. الحصول على Server Key:
1. Firebase Console → **Project Settings** → **Cloud Messaging**
2. انسخ **Server key** واحفظه

#### هـ. الحصول على Service Account Key:
1. Firebase Console → **Project Settings** → **Service accounts**
2. انقر **Generate new private key**
3. **حمّل ملف JSON** واحفظه بأمان

#### و. الحصول على VAPID Key (للوحة التحكم):
1. Firebase Console → **Project Settings** → **Cloud Messaging**
2. في قسم **Web configuration** → **Web Push certificates**
3. انسخ **Key pair** (VAPID key)

---

### 2. في Backend (Laravel):

#### أ. تثبيت حزمة Firebase:

```bash
composer require kreait/firebase-php
```

#### ب. إضافة إعدادات في `.env`:

```env
# Firebase Configuration
FIREBASE_CREDENTIALS_PATH=storage/app/firebase/service-account-key.json
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_SERVER_KEY=your-server-key-here
```

#### ج. رفع Service Account Key:

1. أنشئ مجلد: `storage/app/firebase/`
2. ضع ملف `service-account-key.json` فيه
3. تأكد من إضافة إلى `.gitignore`:

```
storage/app/firebase/service-account-key.json
```

---

### 3. في تطبيق المستخدم (Next.js):

#### أ. تثبيت Firebase:

```bash
npm install firebase
```

#### ب. إنشاء ملف `lib/firebase.js`:

```javascript
import { initializeApp } from 'firebase/app';
import { getMessaging, getToken, onMessage } from 'firebase/messaging';

const firebaseConfig = {
  apiKey: "YOUR_API_KEY",
  authDomain: "YOUR_PROJECT_ID.firebaseapp.com",
  projectId: "YOUR_PROJECT_ID",
  storageBucket: "YOUR_PROJECT_ID.appspot.com",
  messagingSenderId: "YOUR_SENDER_ID",
  appId: "YOUR_APP_ID"
};

const app = initializeApp(firebaseConfig);
export const messaging = getMessaging(app);

export const getFCMToken = async () => {
  try {
    const token = await getToken(messaging, {
      vapidKey: "YOUR_VAPID_KEY"
    });
    return token;
  } catch (error) {
    console.error('Error getting FCM token:', error);
    return null;
  }
};

export const onMessageListener = () => {
  return new Promise((resolve) => {
    onMessage(messaging, (payload) => {
      resolve(payload);
    });
  });
};
```

#### ج. إرسال Token إلى Backend:

```javascript
// في صفحة Dashboard أو بعد تسجيل الدخول
import { getFCMToken } from '@/lib/firebase';
import { useEffect } from 'react';

useEffect(() => {
  const registerToken = async () => {
    const token = await getFCMToken();
    if (token) {
      await fetch('/api/user/device-tokens', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${authToken}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          device_token: token,
          device_type: 'android', // أو 'web' للوحة التحكم
          device_name: 'User Device'
        })
      });
    }
  };
  
  registerToken();
}, []);
```

#### د. معالجة الإشعارات:

```javascript
import { onMessageListener } from '@/lib/firebase';

useEffect(() => {
  onMessageListener()
    .then((payload) => {
      console.log('Notification received:', payload);
      // عرض الإشعار
    })
    .catch((err) => console.error('Error:', err));
}, []);
```

#### هـ. إنشاء Service Worker:

أنشئ ملف `public/firebase-messaging-sw.js` (انظر الدليل الكامل)

---

### 4. في لوحة التحكم (Next.js):

نفس الخطوات السابقة لكن:
- استخدم `device_type: 'web'`
- استخدم VAPID Key من Firebase Console

---

### 5. تحويل Next.js إلى APK:

#### أ. تثبيت Capacitor:

```bash
npm install @capacitor/core @capacitor/cli @capacitor/android
npx cap init
npx cap add android
```

#### ب. بناء التطبيق:

```bash
npm run build
npx cap sync
npx cap open android
```

#### ج. في Android Studio:

1. ضع `google-services.json` في `android/app/`
2. أضف التبعيات في `android/app/build.gradle`
3. أنشئ APK

---

## 📚 الملفات المرجعية

1. **`FIREBASE_PUSH_NOTIFICATIONS_COMPLETE_GUIDE.md`** - دليل شامل كامل
2. **`IMPLEMENTATION_STEPS_FIREBASE.md`** - خطوات التنفيذ التفصيلية

---

## ✅ قائمة التحقق النهائية

### من Firebase:
- [ ] إنشاء مشروع Firebase
- [ ] إضافة تطبيق Android
- [ ] تحميل `google-services.json`
- [ ] إضافة تطبيق Web
- [ ] نسخ إعدادات Firebase
- [ ] الحصول على Server Key
- [ ] تحميل Service Account Key
- [ ] الحصول على VAPID Key

### من Backend:
- [ ] تثبيت `kreait/firebase-php`
- [ ] إضافة إعدادات في `.env`
- [ ] رفع Service Account Key
- [ ] اختبار إرسال إشعار

### من تطبيق المستخدم:
- [ ] تثبيت `firebase`
- [ ] إعداد Firebase Config
- [ ] إرسال Token إلى Backend
- [ ] معالجة الإشعارات

### من لوحة التحكم:
- [ ] تثبيت `firebase`
- [ ] إعداد Firebase Config
- [ ] إرسال Token إلى Backend
- [ ] معالجة الإشعارات

### لتحويل Next.js إلى APK:
- [ ] تثبيت Capacitor
- [ ] إعداد Android Project
- [ ] وضع `google-services.json`
- [ ] بناء APK

---

## 🚀 بعد إكمال جميع الخطوات

1. ✅ اختبر إرسال إشعار من Firebase Console
2. ✅ اختبر إرسال إشعار من Backend
3. ✅ تأكد من وصول الإشعارات لحظياً
4. ✅ اختبر على أجهزة حقيقية

---

## 📞 الدعم

إذا واجهت مشاكل:
1. تحقق من سجلات Firebase Console
2. تحقق من Laravel logs (`storage/logs/laravel.log`)
3. تحقق من Console في المتصفح/التطبيق
4. تأكد من صحة جميع المفاتيح والإعدادات

