# 📱 دليل إعداد Frontend: تلقي الإشعارات من Firebase

## 📋 نظرة عامة

هذا الدليل يوضح كيفية إعداد Next.js لتلقي الإشعارات من Firebase في:

-   ✅ **تطبيق المستخدم** (Next.js → APK)
-   ✅ **لوحة تحكم المسؤول** (Next.js Web)

---

## 🎯 المتطلبات من Firebase Console

### 1. للتطبيق المستخدم (Android):

-   ✅ **`google-services.json`** - من Firebase Console → Project Settings → Your apps → Android app
-   ✅ **VAPID Key** - من Firebase Console → Project Settings → Cloud Messaging → Web configuration

### 2. للوحة التحكم (Web):

-   ✅ **إعدادات Firebase** - من Firebase Console → Project Settings → Your apps → Web app
-   ✅ **VAPID Key** - من Firebase Console → Project Settings → Cloud Messaging → Web configuration

---

## 📱 الجزء 1: تطبيق المستخدم (Next.js)

### الخطوة 1.1: تثبيت Firebase

```bash
npm install firebase
```

---

### الخطوة 1.2: إنشاء ملف `lib/firebase.js`

أنشئ ملف `lib/firebase.js`:

```javascript
import { initializeApp } from "firebase/app";
import { getMessaging, getToken, onMessage } from "firebase/messaging";

// إعدادات Firebase - استبدلها بإعداداتك من Firebase Console
const firebaseConfig = {
    apiKey: process.env.NEXT_PUBLIC_FIREBASE_API_KEY,
    authDomain: process.env.NEXT_PUBLIC_FIREBASE_AUTH_DOMAIN,
    projectId: process.env.NEXT_PUBLIC_FIREBASE_PROJECT_ID,
    storageBucket: process.env.NEXT_PUBLIC_FIREBASE_STORAGE_BUCKET,
    messagingSenderId: process.env.NEXT_PUBLIC_FIREBASE_MESSAGING_SENDER_ID,
    appId: process.env.NEXT_PUBLIC_FIREBASE_APP_ID,
};

// تهيئة Firebase
const app = initializeApp(firebaseConfig);

// الحصول على Messaging instance (فقط في المتصفح)
let messaging = null;
if (typeof window !== "undefined") {
    messaging = getMessaging(app);
}

// الحصول على FCM Token
export const getFCMToken = async () => {
    if (!messaging) {
        console.warn("Messaging not available");
        return null;
    }

    try {
        // طلب إذن الإشعارات
        const permission = await Notification.requestPermission();
        if (permission !== "granted") {
            console.warn("Notification permission denied");
            return null;
        }

        const token = await getToken(messaging, {
            vapidKey: process.env.NEXT_PUBLIC_FIREBASE_VAPID_KEY,
        });

        if (token) {
            console.log("FCM Token:", token);
            return token;
        } else {
            console.warn("No registration token available.");
            return null;
        }
    } catch (error) {
        console.error("An error occurred while retrieving token:", error);
        return null;
    }
};

// الاستماع للإشعارات أثناء فتح التطبيق
export const onMessageListener = () => {
    return new Promise((resolve) => {
        if (!messaging) {
            resolve(null);
            return;
        }

        onMessage(messaging, (payload) => {
            console.log("Message received:", payload);
            resolve(payload);
        });
    });
};
```

---

### الخطوة 1.3: إنشاء Hook `hooks/useFirebaseMessaging.js`

أنشئ ملف `hooks/useFirebaseMessaging.js`:

```javascript
"use client";

import { useEffect, useState } from "react";
import { getFCMToken, onMessageListener } from "@/lib/firebase";

export const useFirebaseMessaging = (authToken) => {
    const [token, setToken] = useState(null);
    const [notification, setNotification] = useState(null);
    const [isRegistered, setIsRegistered] = useState(false);

    useEffect(() => {
        if (!authToken) return;

        // الحصول على Token وإرساله إلى Backend
        const registerToken = async () => {
            try {
                const fcmToken = await getFCMToken();
                if (fcmToken) {
                    setToken(fcmToken);

                    // إرسال Token إلى Backend
                    const response = await fetch(
                        `${process.env.NEXT_PUBLIC_API_URL}/api/user/device-tokens`,
                        {
                            method: "POST",
                            headers: {
                                Authorization: `Bearer ${authToken}`,
                                "Content-Type": "application/json",
                            },
                            body: JSON.stringify({
                                device_token: fcmToken,
                                device_type: "android", // أو "web" للوحة التحكم
                                device_name:
                                    typeof window !== "undefined"
                                        ? navigator.userAgent || "User Device"
                                        : "User Device",
                            }),
                        }
                    );

                    if (response.ok) {
                        console.log("Device token registered successfully");
                        setIsRegistered(true);
                    } else {
                        console.error("Failed to register device token");
                    }
                }
            } catch (error) {
                console.error("Error registering FCM token:", error);
            }
        };

        registerToken();

        // الاستماع للإشعارات
        const setupMessageListener = () => {
            onMessageListener()
                .then((payload) => {
                    if (payload) {
                        setNotification(payload);
                        showNotification(payload);
                    }
                })
                .catch((err) => {
                    console.error("Error in message listener:", err);
                });
        };

        setupMessageListener();
    }, [authToken]);

    const showNotification = (payload) => {
        if (
            typeof window !== "undefined" &&
            "Notification" in window &&
            Notification.permission === "granted"
        ) {
            const notificationTitle =
                payload.notification?.title ||
                payload.data?.title ||
                "إشعار جديد";
            const notificationOptions = {
                body: payload.notification?.body || payload.data?.body || "",
                icon: payload.notification?.icon || "/icon.png",
                badge: "/badge.png",
                data: payload.data,
                requireInteraction: true,
            };

            const notification = new Notification(
                notificationTitle,
                notificationOptions
            );

            notification.onclick = () => {
                window.focus();
                if (payload.data?.action_url) {
                    window.location.href = payload.data.action_url;
                }
                notification.close();
            };
        }
    };

    return { token, notification, isRegistered };
};
```

---

### الخطوة 1.4: استخدام Hook في Component

في صفحة Dashboard أو Layout الرئيسي:

```javascript
"use client";

import { useFirebaseMessaging } from "@/hooks/useFirebaseMessaging";
import { useEffect } from "react";

export default function DashboardLayout({ children, authToken }) {
    const { token, notification, isRegistered } =
        useFirebaseMessaging(authToken);

    // طلب إذن الإشعارات عند تحميل الصفحة
    useEffect(() => {
        if (
            typeof window !== "undefined" &&
            "Notification" in window &&
            Notification.permission === "default"
        ) {
            Notification.requestPermission();
        }
    }, []);

    // عرض الإشعارات الواردة
    useEffect(() => {
        if (notification) {
            console.log("New notification received:", notification);
            // يمكنك إضافة منطق إضافي هنا (مثل إظهار Toast، تحديث العد، إلخ)
        }
    }, [notification]);

    return (
        <div>
            {children}
            {/* يمكنك إضافة مؤشر للإشعارات هنا */}
        </div>
    );
}
```

---

### الخطوة 1.5: إنشاء Service Worker `public/firebase-messaging-sw.js`

أنشئ ملف `public/firebase-messaging-sw.js`:

```javascript
// Import Firebase scripts
importScripts(
    "https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js"
);
importScripts(
    "https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js"
);

// Firebase configuration - استبدلها بإعداداتك
const firebaseConfig = {
    apiKey: "YOUR_API_KEY",
    authDomain: "YOUR_PROJECT_ID.firebaseapp.com",
    projectId: "YOUR_PROJECT_ID",
    storageBucket: "YOUR_PROJECT_ID.appspot.com",
    messagingSenderId: "YOUR_SENDER_ID",
    appId: "YOUR_APP_ID",
};

// Initialize Firebase
firebase.initializeApp(firebaseConfig);

// Retrieve an instance of Firebase Messaging
const messaging = firebase.messaging();

// Handle background messages
messaging.onBackgroundMessage((payload) => {
    console.log("Background notification received:", payload);

    const notificationTitle =
        payload.notification?.title || payload.data?.title || "إشعار جديد";
    const notificationOptions = {
        body: payload.notification?.body || payload.data?.body || "",
        icon: payload.notification?.icon || "/icon.png",
        badge: "/badge.png",
        data: payload.data,
        requireInteraction: true,
    };

    self.registration.showNotification(notificationTitle, notificationOptions);
});

// Handle notification click
self.addEventListener("notificationclick", (event) => {
    console.log("Notification clicked:", event);

    event.notification.close();

    // Open app or specific URL
    if (event.notification.data?.action_url) {
        event.waitUntil(clients.openWindow(event.notification.data.action_url));
    } else {
        event.waitUntil(clients.openWindow("/"));
    }
});
```

---

### الخطوة 1.6: إضافة متغيرات البيئة `.env.local`

أنشئ ملف `.env.local`:

```env
# API URL
NEXT_PUBLIC_API_URL=http://your-backend-url

# Firebase Configuration
NEXT_PUBLIC_FIREBASE_API_KEY=your-api-key
NEXT_PUBLIC_FIREBASE_AUTH_DOMAIN=almutahidat-net.firebaseapp.com
NEXT_PUBLIC_FIREBASE_PROJECT_ID=almutahidat-net
NEXT_PUBLIC_FIREBASE_STORAGE_BUCKET=almutahidat-net.appspot.com
NEXT_PUBLIC_FIREBASE_MESSAGING_SENDER_ID=1051049336008
NEXT_PUBLIC_FIREBASE_APP_ID=your-app-id
NEXT_PUBLIC_FIREBASE_VAPID_KEY=your-vapid-key
```

---

## 🖥️ الجزء 2: لوحة تحكم المسؤول (Next.js)

### الخطوة 2.1: تثبيت Firebase

```bash
npm install firebase
```

---

### الخطوة 2.2: إنشاء ملف `lib/firebase-admin.js`

نفس الكود من الجزء 1.2، لكن استخدم `device_type: 'web'` عند إرسال Token.

---

### الخطوة 2.3: استخدام Hook

نفس Hook من الجزء 1.3، لكن في لوحة التحكم:

```javascript
const { token, notification, isRegistered } = useFirebaseMessaging(adminToken);
```

---

## 📦 الجزء 3: تحويل Next.js إلى APK

### الخطوة 3.1: تثبيت Capacitor

```bash
npm install @capacitor/core @capacitor/cli @capacitor/android
npx cap init
```

### الخطوة 3.2: إعداد Capacitor

```bash
npx cap add android
npm run build
npx cap sync
npx cap open android
```

### الخطوة 3.3: في Android Studio

1. ضع `google-services.json` في `android/app/`
2. أضف في `android/app/build.gradle`:

```gradle
apply plugin: 'com.google.gms.google-services'

dependencies {
    implementation platform('com.google.firebase:firebase-bom:32.0.0')
    implementation 'com.google.firebase:firebase-messaging'
}
```

3. أنشئ APK

---

## ✅ قائمة التحقق

### للتطبيق المستخدم:

-   [ ] تثبيت `firebase`
-   [ ] إنشاء `lib/firebase.js`
-   [ ] إنشاء `hooks/useFirebaseMessaging.js`
-   [ ] استخدام Hook في Component
-   [ ] إنشاء `public/firebase-messaging-sw.js`
-   [ ] إضافة متغيرات البيئة في `.env.local`
-   [ ] إرسال Token إلى Backend

### للوحة التحكم:

-   [ ] تثبيت `firebase`
-   [ ] إنشاء `lib/firebase-admin.js`
-   [ ] استخدام Hook في لوحة التحكم
-   [ ] إضافة متغيرات البيئة

### لتحويل Next.js إلى APK:

-   [ ] تثبيت Capacitor
-   [ ] إعداد Android Project
-   [ ] وضع `google-services.json`
-   [ ] بناء APK

---

## 🧪 اختبار

بعد إعداد كل شيء:

1. افتح التطبيق/لوحة التحكم
2. سجل دخول
3. يجب أن يطلب إذن الإشعارات
4. تحقق من Console - يجب أن يظهر FCM Token
5. أرسل إشعار من Backend
6. يجب أن تصل الإشعارات لحظياً

---

## 📚 الملفات المرجعية

-   `NEXTJS_FIREBASE_CODE_EXAMPLES.md` - أمثلة كود كاملة
-   `FIREBASE_PUSH_NOTIFICATIONS_COMPLETE_GUIDE.md` - دليل شامل
