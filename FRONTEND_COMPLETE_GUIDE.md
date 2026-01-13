# 📱 دليل شامل: إعداد Frontend لتلقي الإشعارات

## 📋 نظرة عامة

هذا الدليل يوضح كيفية إعداد Next.js لتلقي الإشعارات من Firebase في:
- ✅ **تطبيق المستخدم** (Next.js → APK)
- ✅ **لوحة تحكم المسؤول** (Next.js Web)

---

## 🎯 المتطلبات من Firebase Console

### للتطبيق المستخدم (Android):
- ✅ **`google-services.json`** - من Firebase Console
- ✅ **إعدادات Firebase** - من Firebase Console
- ✅ **VAPID Key** - من Firebase Console

### للوحة التحكم (Web):
- ✅ **إعدادات Firebase** - من Firebase Console
- ✅ **VAPID Key** - من Firebase Console

---

## 📱 الجزء 1: تطبيق المستخدم (Next.js)

### الخطوة 1: تثبيت Firebase

```bash
npm install firebase
```

---

### الخطوة 2: إنشاء `lib/firebase.js`

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

let messaging = null;
if (typeof window !== "undefined") {
  messaging = getMessaging(app);
}

export const getFCMToken = async () => {
  if (!messaging) {
    console.warn("Messaging not available");
    return null;
  }

  try {
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
    }
    return null;
  } catch (error) {
    console.error("Error getting FCM token:", error);
    return null;
  }
};

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

### الخطوة 3: إنشاء Hook `hooks/useFirebaseMessaging.js`

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

    const registerToken = async () => {
      try {
        const fcmToken = await getFCMToken();
        if (fcmToken) {
          setToken(fcmToken);

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
                device_name: navigator.userAgent || "User Device",
              }),
            }
          );

          if (response.ok) {
            console.log("Device token registered successfully ✅");
            setIsRegistered(true);
          }
        }
      } catch (error) {
        console.error("Error registering token:", error);
      }
    };

    registerToken();

    const setupListener = () => {
      onMessageListener()
        .then((payload) => {
          if (payload) {
            setNotification(payload);
            showNotification(payload);
          }
        })
        .catch((err) => console.error("Error:", err));
    };

    setupListener();
  }, [authToken]);

  const showNotification = (payload) => {
    if (
      typeof window !== "undefined" &&
      "Notification" in window &&
      Notification.permission === "granted"
    ) {
      const notification = new Notification(
        payload.notification?.title || payload.data?.title || "إشعار جديد",
        {
          body: payload.notification?.body || payload.data?.body || "",
          icon: payload.notification?.icon || "/icon.png",
          badge: "/badge.png",
          data: payload.data,
        }
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

### الخطوة 4: استخدام Hook في Layout أو Dashboard

```javascript
"use client";

import { useFirebaseMessaging } from "@/hooks/useFirebaseMessaging";
import { useEffect } from "react";

export default function AppLayout({ children, authToken }) {
  const { token, notification, isRegistered } = useFirebaseMessaging(authToken);

  useEffect(() => {
    if (
      typeof window !== "undefined" &&
      "Notification" in window &&
      Notification.permission === "default"
    ) {
      Notification.requestPermission();
    }
  }, []);

  useEffect(() => {
    if (notification) {
      console.log("New notification:", notification);
      // يمكنك إضافة منطق إضافي هنا
    }
  }, [notification]);

  return <div>{children}</div>;
}
```

---

### الخطوة 5: إنشاء Service Worker `public/firebase-messaging-sw.js`

```javascript
importScripts(
  "https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js"
);
importScripts(
  "https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js"
);

const firebaseConfig = {
  apiKey: "YOUR_API_KEY",
  authDomain: "almutahidat-net.firebaseapp.com",
  projectId: "almutahidat-net",
  storageBucket: "almutahidat-net.appspot.com",
  messagingSenderId: "1051049336008",
  appId: "YOUR_APP_ID",
};

firebase.initializeApp(firebaseConfig);
const messaging = firebase.messaging();

messaging.onBackgroundMessage((payload) => {
  const notificationTitle =
    payload.notification?.title || payload.data?.title || "إشعار جديد";
  const notificationOptions = {
    body: payload.notification?.body || payload.data?.body || "",
    icon: payload.notification?.icon || "/icon.png",
    badge: "/badge.png",
    data: payload.data,
  };

  self.registration.showNotification(notificationTitle, notificationOptions);
});

self.addEventListener("notificationclick", (event) => {
  event.notification.close();
  if (event.notification.data?.action_url) {
    event.waitUntil(clients.openWindow(event.notification.data.action_url));
  } else {
    event.waitUntil(clients.openWindow("/"));
  }
});
```

---

### الخطوة 6: إضافة `.env.local`

```env
NEXT_PUBLIC_API_URL=http://your-backend-url
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

نفس الخطوات السابقة، لكن:

1. استخدم `device_type: 'web'` عند إرسال Token
2. يمكن استخدام نفس Hook `useFirebaseMessaging`

---

## 📦 الجزء 3: تحويل Next.js إلى APK

### الخطوة 1: تثبيت Capacitor

```bash
npm install @capacitor/core @capacitor/cli @capacitor/android
npx cap init
```

### الخطوة 2: إعداد Capacitor

```bash
npx cap add android
npm run build
npx cap sync
npx cap open android
```

### الخطوة 3: في Android Studio

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

## ✅ قائمة التحقق النهائية

### للتطبيق المستخدم:
- [ ] تثبيت `firebase`
- [ ] إنشاء `lib/firebase.js`
- [ ] إنشاء `hooks/useFirebaseMessaging.js`
- [ ] استخدام Hook في Component
- [ ] إنشاء `public/firebase-messaging-sw.js`
- [ ] إضافة `.env.local`
- [ ] إرسال Token إلى Backend

### للوحة التحكم:
- [ ] نفس الخطوات السابقة
- [ ] استخدام `device_type: 'web'`

---

## 🧪 اختبار

1. افتح التطبيق/لوحة التحكم
2. سجل دخول
3. يجب أن يطلب إذن الإشعارات
4. تحقق من Console - يجب أن يظهر FCM Token
5. أرسل إشعار من Backend
6. يجب أن تصل الإشعارات لحظياً ✅

---

## 📚 الملفات المرجعية

- `FRONTEND_STEPS_SIMPLE.md` - خطوات مبسطة
- `NEXTJS_FIREBASE_CODE_EXAMPLES.md` - كود كامل جاهز

