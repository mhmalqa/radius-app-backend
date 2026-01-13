# 🖥️ دليل إعداد لوحة تحكم المسؤول: تلقي الإشعارات من Firebase

## 📋 نظرة عامة

هذا الدليل موجه للمسؤول عن تطوير لوحة التحكم (Frontend Developer). يوضح كيفية إعداد Next.js لتلقي الإشعارات من Firebase Cloud Messaging (FCM) لحظياً.

---

## 🎯 ما ستحتاجه من Firebase Console

### 1. إعدادات Firebase (Web App):

1. اذهب إلى [Firebase Console](https://console.firebase.google.com)
2. اختر مشروع `almutahidat-net`
3. اذهب إلى **Project Settings** (⚙️) → **Your apps**
4. إذا لم يكن موجوداً، أضف تطبيق Web:
    - انقر **Add app** → **Web** (</>)
    - أدخل **App nickname**: `Radius Admin Dashboard`
    - انقر **Register app**
5. **انسخ إعدادات Firebase** واحفظها:

```javascript
{
  apiKey: "AIzaSy...",
  authDomain: "almutahidat-net.firebaseapp.com",
  projectId: "almutahidat-net",
  storageBucket: "almutahidat-net.appspot.com",
  messagingSenderId: "1051049336008",
  appId: "1:1051049336008:web:..."
}
```

### 2. VAPID Key:

✅ **تم الحصول عليه**:

```
BBiDbyEE9PKzBsMqYJpp3W6HhNKwLsawkUASVH58PmNQpQBVR7zvwTMJWXyVQFPrvJKw_tD-S66Ubzlv33RF30o
```

**الموقع**: Firebase Console → Project Settings → Cloud Messaging → Web Push certificates

---

## 📦 الخطوة 1: تثبيت Firebase

في مجلد مشروع لوحة التحكم (Next.js):

```bash
npm install firebase
```

---

## 📝 الخطوة 2: إنشاء ملف `lib/firebase.js`

أنشئ ملف جديد `lib/firebase.js`:

```javascript
import { initializeApp } from "firebase/app";
import { getMessaging, getToken, onMessage } from "firebase/messaging";

// إعدادات Firebase - استبدلها بالقيم من Firebase Console
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

/**
 * الحصول على FCM Token
 * @returns {Promise<string|null>} FCM Token أو null
 */
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
            console.log("FCM Token obtained:", token.substring(0, 20) + "...");
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

/**
 * الاستماع للإشعارات أثناء فتح التطبيق
 * @returns {Promise<object|null>} Payload الإشعار أو null
 */
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

## 🎣 الخطوة 3: إنشاء Hook `hooks/useFirebaseMessaging.js`

أنشئ ملف جديد `hooks/useFirebaseMessaging.js`:

```javascript
"use client";

import { useEffect, useState } from "react";
import { getFCMToken, onMessageListener } from "@/lib/firebase";

/**
 * Hook لإدارة Firebase Cloud Messaging
 * @param {string} authToken - Token المصادقة من Backend
 * @returns {object} { token, notification, isRegistered }
 */
export const useFirebaseMessaging = (authToken) => {
    const [token, setToken] = useState(null);
    const [notification, setNotification] = useState(null);
    const [isRegistered, setIsRegistered] = useState(false);

    useEffect(() => {
        if (!authToken) {
            console.warn("Auth token not provided");
            return;
        }

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
                                device_type: "web", // مهم: استخدم "web" للوحة التحكم
                                device_name: "Admin Dashboard",
                            }),
                        }
                    );

                    if (response.ok) {
                        const data = await response.json();
                        console.log(
                            "Device token registered successfully ✅",
                            data
                        );
                        setIsRegistered(true);
                    } else {
                        const error = await response.json();
                        console.error(
                            "Failed to register device token:",
                            error
                        );
                    }
                }
            } catch (error) {
                console.error("Error registering FCM token:", error);
            }
        };

        registerToken();

        // الاستماع للإشعارات الواردة
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

    /**
     * عرض الإشعار في المتصفح
     * @param {object} payload - Payload الإشعار
     */
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

## 🎨 الخطوة 4: استخدام Hook في Layout أو Component الرئيسي

في ملف Layout الرئيسي (مثلاً `app/layout.js` أو `components/Layout.js`):

```javascript
"use client";

import { useFirebaseMessaging } from "@/hooks/useFirebaseMessaging";
import { useEffect } from "react";
import { useAuth } from "@/hooks/useAuth"; // أو أي Hook تستخدمه للمصادقة

export default function AdminLayout({ children }) {
    // احصل على authToken من نظام المصادقة الخاص بك
    const { user, token } = useAuth(); // استبدل هذا بـ Hook المصادقة الخاص بك
    const {
        token: fcmToken,
        notification,
        isRegistered,
    } = useFirebaseMessaging(token);

    // طلب إذن الإشعارات عند تحميل الصفحة
    useEffect(() => {
        if (
            typeof window !== "undefined" &&
            "Notification" in window &&
            Notification.permission === "default"
        ) {
            Notification.requestPermission().then((permission) => {
                console.log("Notification permission:", permission);
            });
        }
    }, []);

    // معالجة الإشعارات الواردة
    useEffect(() => {
        if (notification) {
            console.log("New notification received:", notification);

            // يمكنك إضافة منطق إضافي هنا:
            // - إظهار Toast notification
            // - تحديث عداد الإشعارات غير المقروءة
            // - إعادة توجيه إلى صفحة معينة
            // - إضافة الإشعار إلى قائمة الإشعارات
        }
    }, [notification]);

    return (
        <div>
            {/* يمكنك إضافة مؤشر للإشعارات هنا */}
            {isRegistered && (
                <div className="notification-indicator">
                    الإشعارات مفعّلة ✅
                </div>
            )}

            {children}
        </div>
    );
}
```

---

## 🔧 الخطوة 5: إنشاء Service Worker `public/firebase-messaging-sw.js`

أنشئ ملف جديد `public/firebase-messaging-sw.js`:

```javascript
// Import Firebase scripts
importScripts(
    "https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js"
);
importScripts(
    "https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js"
);

// Firebase configuration
const firebaseConfig = {
    apiKey: "AIzaSyCcsx0T2OTasI2fVeSue0_ER30xKWKZQiU",
    authDomain: "almutahidat-net.firebaseapp.com",
    projectId: "almutahidat-net",
    storageBucket: "almutahidat-net.firebasestorage.app",
    messagingSenderId: "1051049336008",
    appId: "1:1051049336008:web:9e9c079adf25ec26b0d9fd",
};

// Initialize Firebase
firebase.initializeApp(firebaseConfig);

// Retrieve an instance of Firebase Messaging
const messaging = firebase.messaging();

// Handle background messages (عندما يكون التطبيق في الخلفية)
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

**ملاحظة:** جميع القيم جاهزة ومضبوطة من Firebase Console.

---

## ⚙️ الخطوة 6: إضافة متغيرات البيئة `.env.local`

أنشئ ملف `.env.local` في جذر المشروع (أو أضف إلى الملف الموجود):

```env
# Backend API URL
NEXT_PUBLIC_API_URL=http://your-backend-url

# Firebase Configuration
NEXT_PUBLIC_FIREBASE_API_KEY=AIzaSyCcsx0T2OTasI2fVeSue0_ER30xKWKZQiU
NEXT_PUBLIC_FIREBASE_AUTH_DOMAIN=almutahidat-net.firebaseapp.com
NEXT_PUBLIC_FIREBASE_PROJECT_ID=almutahidat-net
NEXT_PUBLIC_FIREBASE_STORAGE_BUCKET=almutahidat-net.firebasestorage.app
NEXT_PUBLIC_FIREBASE_MESSAGING_SENDER_ID=1051049336008
NEXT_PUBLIC_FIREBASE_APP_ID=1:1051049336008:web:9e9c079adf25ec26b0d9fd
NEXT_PUBLIC_FIREBASE_VAPID_KEY=BBiDbyEE9PKzBsMqYJpp3W6HhNKwLsawkUASVH58PmNQpQBVR7zvwTMJWXyVQFPrvJKw_tD-S66Ubzlv33RF30o
```

**ملاحظة:** استبدل `http://your-backend-url` برابط Backend API الفعلي فقط. باقي القيم جاهزة للاستخدام!

---

## 📋 الخطوة 7: تسجيل Service Worker

في ملف Layout الرئيسي أو `_app.js` (إذا كنت تستخدم Pages Router):

```javascript
"use client";

import { useEffect } from "react";

export default function App({ Component, pageProps }) {
    useEffect(() => {
        if (typeof window !== "undefined" && "serviceWorker" in navigator) {
            navigator.serviceWorker
                .register("/firebase-messaging-sw.js")
                .then((registration) => {
                    console.log("Service Worker registered:", registration);
                })
                .catch((error) => {
                    console.error("Service Worker registration failed:", error);
                });
        }
    }, []);

    return <Component {...pageProps} />;
}
```

---

## ✅ قائمة التحقق

-   [ ] تثبيت `firebase` package
-   [ ] إنشاء `lib/firebase.js`
-   [ ] إنشاء `hooks/useFirebaseMessaging.js`
-   [ ] استخدام Hook في Layout الرئيسي
-   [ ] إنشاء `public/firebase-messaging-sw.js`
-   [ ] إضافة متغيرات البيئة في `.env.local`
-   [ ] تسجيل Service Worker
-   [ ] اختبار الإشعارات

---

## 🧪 اختبار الإعداد

### 1. التحقق من التثبيت:

```bash
npm list firebase
```

### 2. تشغيل التطبيق:

```bash
npm run dev
```

### 3. التحقق من Console:

1. افتح لوحة التحكم في المتصفح
2. افتح Developer Tools (F12)
3. اذهب إلى Console
4. يجب أن ترى:
    - `FCM Token obtained: ...` ✅
    - `Device token registered successfully ✅` ✅

### 4. اختبار إرسال إشعار:

من Backend، أرسل إشعار تجريبي. يجب أن:

-   ✅ تظهر الإشعار في المتصفح
-   ✅ يتم حفظ Token في قاعدة البيانات
-   ✅ تصل الإشعارات لحظياً

---

## 🔍 استكشاف الأخطاء

### المشكلة: "Messaging not available"

**الحل:** تأكد من أن الكود يعمل في المتصفح (ليس في Server-Side Rendering)

### المشكلة: "Notification permission denied"

**الحل:**

1. تأكد من طلب الإذن من المستخدم
2. تحقق من إعدادات المتصفح للإشعارات

### المشكلة: "Failed to register device token"

**الحل:**

1. تحقق من صحة `authToken`
2. تحقق من صحة `NEXT_PUBLIC_API_URL`
3. تحقق من Console للأخطاء

### المشكلة: الإشعارات لا تصل

**الحل:**

1. تحقق من أن Token تم إرساله إلى Backend
2. تحقق من أن Backend يرسل الإشعارات
3. تحقق من Console للأخطاء

---

## 📚 معلومات إضافية

### Backend API Endpoint:

```
POST /api/user/device-tokens
Authorization: Bearer {token}
Content-Type: application/json

{
  "device_token": "fcm_token_here",
  "device_type": "web",
  "device_name": "Admin Dashboard"
}
```

### هيكل الإشعار الوارد:

```javascript
{
  notification: {
    title: "عنوان الإشعار",
    body: "نص الإشعار",
    icon: "/icon.png"
  },
  data: {
    notification_id: "1",
    type: "system",
    action_url: "/admin/payment-requests/1",
    action_text: "عرض الطلب"
  }
}
```

---

## 🎯 ملخص الخطوات

1. ✅ تثبيت `firebase`
2. ✅ إنشاء `lib/firebase.js`
3. ✅ إنشاء `hooks/useFirebaseMessaging.js`
4. ✅ استخدام Hook في Layout
5. ✅ إنشاء Service Worker
6. ✅ إضافة `.env.local`
7. ✅ تسجيل Service Worker
8. ✅ اختبار

---

## 📞 الدعم

إذا واجهت مشاكل:

1. تحقق من Console في المتصفح
2. تحقق من Network tab في Developer Tools
3. تحقق من Backend logs
4. راجع `FRONTEND_FIREBASE_SETUP_COMPLETE.md` للتفاصيل الكاملة

---

## ✅ بعد الإعداد

بعد إكمال جميع الخطوات، ستصل الإشعارات لحظياً إلى لوحة التحكم عند:

-   ✅ قبول/رفض طلب دفع
-   ✅ إضافة دفع نقدي
-   ✅ إنشاء إشعار يدوي
-   ✅ أي إشعارات أخرى من Backend

---

## 🎉 مبروك!

لوحة التحكم جاهزة الآن لتلقي الإشعارات لحظياً من Firebase! 🚀
