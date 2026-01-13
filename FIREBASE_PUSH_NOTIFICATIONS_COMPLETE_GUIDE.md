# 🔔 دليل شامل: ربط Firebase Push Notifications

## 📋 نظرة عامة

هذا الدليل يوضح كيفية ربط Firebase Cloud Messaging (FCM) مع:

-   ✅ **Backend (Laravel)**: لإرسال الإشعارات
-   ✅ **تطبيق المستخدم (Next.js → APK)**: لاستقبال الإشعارات
-   ✅ **لوحة تحكم المسؤول (Next.js)**: لاستقبال الإشعارات

---

## 🎯 المتطلبات منك

قبل البدء، ستحتاج إلى:

### 1. حساب Firebase

1. ✅ إنشاء حساب Firebase على [console.firebase.google.com](https://console.firebase.google.com)
2. ✅ إنشاء مشروع جديد أو استخدام مشروع موجود
3. ✅ تفعيل **Cloud Messaging API**

### 2. ملفات Firebase المطلوبة

#### للتطبيق المستخدم (Android APK):

-   ✅ **`google-services.json`**: ملف إعدادات Firebase لـ Android
-   ✅ **Server Key**: مفتاح الخادم من Firebase Console

#### للوحة التحكم (Next.js Web):

-   ✅ **`firebase-config.js`**: ملف إعدادات Firebase للويب
-   ✅ **Service Account Key**: مفتاح حساب الخدمة (JSON)

---

## 📦 الخطوة 1: إعداد Firebase Console

### 1.1 إنشاء مشروع Firebase

1. اذهب إلى [Firebase Console](https://console.firebase.google.com)
2. انقر على **"Add project"** أو **"إضافة مشروع"**
3. أدخل اسم المشروع (مثلاً: `radius-app`)
4. اتبع الخطوات لإكمال الإنشاء

### 1.2 إضافة تطبيق Android

1. في Firebase Console، انقر على **"Add app"** → **Android**
2. أدخل:
    - **Package name**: `com.yourapp.radius` (يجب أن يكون فريداً)
    - **App nickname**: `Radius User App`
    - **Debug signing certificate SHA-1**: (اختياري للاختبار)
3. انقر **"Register app"**
4. **حمّل ملف `google-services.json`** واحفظه (سنستخدمه لاحقاً)

### 1.3 إضافة تطبيق Web (للوحة التحكم)

1. في Firebase Console، انقر على **"Add app"** → **Web** (</>)
2. أدخل:
    - **App nickname**: `Radius Admin Dashboard`
3. انقر **"Register app"**
4. **انسخ إعدادات Firebase** (سنستخدمها لاحقاً)

### 1.4 الحصول على Server Key

1. في Firebase Console، اذهب إلى **Project Settings** → **Cloud Messaging**
2. في قسم **"Cloud Messaging API (Legacy)"**:
    - انقر على **"Enable"** إذا لم يكن مفعلاً
    - انسخ **"Server key"** واحفظه

### 1.5 الحصول على Service Account Key

1. في Firebase Console، اذهب إلى **Project Settings** → **Service accounts**
2. انقر على **"Generate new private key"**
3. **حمّل ملف JSON** واحفظه بأمان (هذا مفتاح حساس!)

---

## 🔧 الخطوة 2: إعداد Backend (Laravel)

### 2.1 تثبيت حزمة Firebase Admin SDK

```bash
composer require kreait/firebase-php
```

### 2.2 إضافة إعدادات Firebase في `.env`

```env
# Firebase Configuration
FIREBASE_CREDENTIALS_PATH=storage/app/firebase/service-account-key.json
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_SERVER_KEY=your-server-key-here
```

### 2.3 رفع ملف Service Account Key

1. أنشئ مجلد: `storage/app/firebase/`
2. ضع ملف `service-account-key.json` فيه
3. تأكد من أن الملف آمن (لا يظهر في Git)

**ملاحظة:** أضف إلى `.gitignore`:

```
storage/app/firebase/service-account-key.json
```

### 2.4 إنشاء Service Class لإرسال الإشعارات

سنقوم بإنشاء `FirebaseMessagingService` لإرسال الإشعارات.

---

## 📱 الخطوة 3: إعداد تطبيق المستخدم (Next.js → APK)

### 3.1 تثبيت الحزم المطلوبة

```bash
npm install firebase @react-native-firebase/app @react-native-firebase/messaging
```

### 3.2 إعداد Firebase في Next.js

1. ضع ملف `google-services.json` في مجلد `public/`
2. أنشئ ملف `lib/firebase.js`:

```javascript
import { initializeApp } from "firebase/app";
import { getMessaging, getToken, onMessage } from "firebase/messaging";

const firebaseConfig = {
    // من Firebase Console
    apiKey: "YOUR_API_KEY",
    authDomain: "YOUR_PROJECT_ID.firebaseapp.com",
    projectId: "YOUR_PROJECT_ID",
    storageBucket: "YOUR_PROJECT_ID.appspot.com",
    messagingSenderId: "YOUR_SENDER_ID",
    appId: "YOUR_APP_ID",
};

const app = initializeApp(firebaseConfig);
export const messaging = getMessaging(app);

// الحصول على FCM Token
export const getFCMToken = async () => {
    try {
        const token = await getToken(messaging, {
            vapidKey: "YOUR_VAPID_KEY", // من Firebase Console
        });
        return token;
    } catch (error) {
        console.error("Error getting FCM token:", error);
        return null;
    }
};

// الاستماع للإشعارات أثناء فتح التطبيق
export const onMessageListener = () => {
    return new Promise((resolve) => {
        onMessage(messaging, (payload) => {
            resolve(payload);
        });
    });
};
```

### 3.3 إرسال FCM Token إلى Backend

عند تسجيل الدخول، أرسل الـ Token إلى Backend:

```javascript
// في صفحة تسجيل الدخول أو Dashboard
import { getFCMToken } from "@/lib/firebase";
import { useEffect } from "react";

export default function Dashboard() {
    useEffect(() => {
        const registerToken = async () => {
            const token = await getFCMToken();
            if (token) {
                // إرسال Token إلى Backend
                await fetch("/api/user/device-token", {
                    method: "POST",
                    headers: {
                        Authorization: `Bearer ${yourAuthToken}`,
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({
                        device_token: token,
                        device_type: "android",
                        device_name: "User Device",
                    }),
                });
            }
        };

        registerToken();
    }, []);

    // ... باقي الكود
}
```

### 3.4 معالجة الإشعارات الواردة

```javascript
import { onMessageListener } from "@/lib/firebase";
import { useEffect } from "react";

export default function App() {
    useEffect(() => {
        // الاستماع للإشعارات أثناء فتح التطبيق
        onMessageListener()
            .then((payload) => {
                console.log("Notification received:", payload);
                // عرض الإشعار في التطبيق
                showNotification(payload);
            })
            .catch((err) => console.error("Error:", err));
    }, []);
}
```

### 3.5 إعداد Service Worker للإشعارات في الخلفية

أنشئ ملف `public/firebase-messaging-sw.js`:

```javascript
importScripts(
    "https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js"
);
importScripts(
    "https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js"
);

const firebaseConfig = {
    apiKey: "YOUR_API_KEY",
    authDomain: "YOUR_PROJECT_ID.firebaseapp.com",
    projectId: "YOUR_PROJECT_ID",
    storageBucket: "YOUR_PROJECT_ID.appspot.com",
    messagingSenderId: "YOUR_SENDER_ID",
    appId: "YOUR_APP_ID",
};

firebase.initializeApp(firebaseConfig);
const messaging = firebase.messaging();

// معالجة الإشعارات في الخلفية
messaging.onBackgroundMessage((payload) => {
    console.log("Background notification:", payload);

    const notificationTitle = payload.notification.title;
    const notificationOptions = {
        body: payload.notification.body,
        icon: payload.notification.icon || "/icon.png",
        badge: "/badge.png",
        data: payload.data,
    };

    self.registration.showNotification(notificationTitle, notificationOptions);
});
```

---

## 🖥️ الخطوة 4: إعداد لوحة تحكم المسؤول (Next.js)

### 4.1 تثبيت الحزم

```bash
npm install firebase
```

### 4.2 إعداد Firebase

أنشئ ملف `lib/firebase-admin.js`:

```javascript
import { initializeApp, getApps, cert } from "firebase-admin/app";
import { getMessaging } from "firebase-admin/messaging";

if (!getApps().length) {
    initializeApp({
        credential: cert({
            projectId: process.env.NEXT_PUBLIC_FIREBASE_PROJECT_ID,
            clientEmail: process.env.FIREBASE_CLIENT_EMAIL,
            privateKey: process.env.FIREBASE_PRIVATE_KEY?.replace(/\\n/g, "\n"),
        }),
    });
}

export const adminMessaging = getMessaging();
```

### 4.3 إعداد Firebase Client (للإشعارات الواردة)

أنشئ ملف `lib/firebase-client.js`:

```javascript
import { initializeApp } from "firebase/app";
import { getMessaging, getToken, onMessage } from "firebase/messaging";

const firebaseConfig = {
    apiKey: process.env.NEXT_PUBLIC_FIREBASE_API_KEY,
    authDomain: process.env.NEXT_PUBLIC_FIREBASE_AUTH_DOMAIN,
    projectId: process.env.NEXT_PUBLIC_FIREBASE_PROJECT_ID,
    storageBucket: process.env.NEXT_PUBLIC_FIREBASE_STORAGE_BUCKET,
    messagingSenderId: process.env.NEXT_PUBLIC_FIREBASE_MESSAGING_SENDER_ID,
    appId: process.env.NEXT_PUBLIC_FIREBASE_APP_ID,
};

const app = initializeApp(firebaseConfig);
export const messaging = getMessaging(app);

export const getFCMToken = async () => {
    try {
        const token = await getToken(messaging, {
            vapidKey: process.env.NEXT_PUBLIC_FIREBASE_VAPID_KEY,
        });
        return token;
    } catch (error) {
        console.error("Error getting FCM token:", error);
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

### 4.4 إرسال Token إلى Backend

```javascript
// في لوحة التحكم
import { getFCMToken } from "@/lib/firebase-client";

useEffect(() => {
    const registerAdminToken = async () => {
        const token = await getFCMToken();
        if (token) {
            await fetch("/api/admin/device-token", {
                method: "POST",
                headers: {
                    Authorization: `Bearer ${adminToken}`,
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    device_token: token,
                    device_type: "web",
                    device_name: "Admin Dashboard",
                }),
            });
        }
    };

    registerAdminToken();
}, []);
```

---

## 📦 الخطوة 5: تحويل Next.js إلى APK

### 5.1 استخدام Capacitor (الطريقة الموصى بها)

#### تثبيت Capacitor

```bash
npm install @capacitor/core @capacitor/cli @capacitor/android
npx cap init
```

#### إعداد Capacitor

```bash
npx cap add android
```

#### بناء التطبيق

```bash
npm run build
npx cap sync
npx cap open android
```

#### في Android Studio:

1. افتح المشروع في Android Studio
2. ضع ملف `google-services.json` في `android/app/`
3. أضف التبعيات في `android/app/build.gradle`:

```gradle
apply plugin: 'com.google.gms.google-services'

dependencies {
    implementation platform('com.google.firebase:firebase-bom:32.0.0')
    implementation 'com.google.firebase:firebase-messaging'
}
```

4. أنشئ APK: **Build** → **Generate Signed Bundle / APK**

### 5.2 استخدام React Native (بديل)

إذا كنت تريد استخدام React Native بدلاً من Next.js:

```bash
npx react-native init RadiusApp
# ثم اتبع إعداد Firebase لـ React Native
```

---

## ⚡ الخطوة 6: إرسال الإشعارات لحظياً من Backend

سنقوم بتحديث `NotificationService` لإرسال الإشعارات عبر FCM.

---

## 📝 الخطوة 7: API Endpoints المطلوبة

### 7.1 حفظ Device Token (للمستخدم)

```
POST /api/user/device-token
Authorization: Bearer {token}
Content-Type: application/json

{
  "device_token": "fcm_token_here",
  "device_type": "android",
  "device_name": "User Device"
}
```

### 7.2 حفظ Device Token (للمسؤول)

```
POST /api/admin/device-token
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "device_token": "fcm_token_here",
  "device_type": "web",
  "device_name": "Admin Dashboard"
}
```

---

## ✅ قائمة التحقق

### من Firebase Console:

-   [ ] إنشاء مشروع Firebase
-   [ ] إضافة تطبيق Android
-   [ ] تحميل `google-services.json`
-   [ ] إضافة تطبيق Web
-   [ ] نسخ إعدادات Firebase للويب
-   [ ] الحصول على Server Key
-   [ ] تحميل Service Account Key (JSON)

### من Backend:

-   [ ] تثبيت `kreait/firebase-php`
-   [ ] إضافة إعدادات Firebase في `.env`
-   [ ] رفع Service Account Key
-   [ ] إنشاء `FirebaseMessagingService`
-   [ ] تحديث `NotificationService`
-   [ ] إنشاء API endpoints لحفظ Device Tokens

### من تطبيق المستخدم (Next.js):

-   [ ] تثبيت `firebase`
-   [ ] إعداد Firebase Config
-   [ ] إرسال FCM Token إلى Backend
-   [ ] معالجة الإشعارات الواردة
-   [ ] إعداد Service Worker

### من لوحة التحكم (Next.js):

-   [ ] تثبيت `firebase`
-   [ ] إعداد Firebase Config
-   [ ] إرسال FCM Token إلى Backend
-   [ ] معالجة الإشعارات الواردة

### لتحويل Next.js إلى APK:

-   [ ] تثبيت Capacitor
-   [ ] إعداد Android Project
-   [ ] وضع `google-services.json` في Android
-   [ ] بناء APK في Android Studio

---

## 🚀 الخطوات التالية

بعد إكمال جميع الخطوات:

1. ✅ اختبر إرسال إشعار من Firebase Console
2. ✅ اختبر إرسال إشعار من Backend
3. ✅ تأكد من وصول الإشعارات لحظياً
4. ✅ اختبر على أجهزة حقيقية

---

## 📞 الدعم

إذا واجهت مشاكل:

1. تحقق من سجلات Firebase Console
2. تحقق من سجلات Backend (Laravel logs)
3. تحقق من Console في المتصفح/التطبيق
4. تأكد من صحة جميع المفاتيح والإعدادات
