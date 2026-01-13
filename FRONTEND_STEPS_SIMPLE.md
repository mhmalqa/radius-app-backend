# 📱 خطوات إعداد Frontend لتلقي الإشعارات

## 🎯 الخطوات الأساسية (للتطبيق ولوحة التحكم)

### 1️⃣ تثبيت Firebase

```bash
npm install firebase
```

---

### 2️⃣ إنشاء ملف `lib/firebase.js`

انسخ الكود من `NEXTJS_FIREBASE_CODE_EXAMPLES.md` (السطور 7-68)

**أو استخدم هذا الكود:**

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
  if (!messaging) return null;

  try {
    const permission = await Notification.requestPermission();
    if (permission !== "granted") return null;

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
    if (!messaging) {
      resolve(null);
      return;
    }
    onMessage(messaging, (payload) => {
      resolve(payload);
    });
  });
};
```

---

### 3️⃣ إنشاء Hook `hooks/useFirebaseMessaging.js`

انسخ الكود من `NEXTJS_FIREBASE_CODE_EXAMPLES.md` (السطور 131-212)

**أو استخدم هذا الكود:**

```javascript
"use client";

import { useEffect, useState } from "react";
import { getFCMToken, onMessageListener } from "@/lib/firebase";

export const useFirebaseMessaging = (authToken) => {
  const [token, setToken] = useState(null);
  const [notification, setNotification] = useState(null);

  useEffect(() => {
    if (!authToken) return;

    // الحصول على Token وإرساله إلى Backend
    const registerToken = async () => {
      const fcmToken = await getFCMToken();
      if (fcmToken) {
        setToken(fcmToken);

        // إرسال Token إلى Backend
        try {
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
            console.log("Device token registered ✅");
          }
        } catch (error) {
          console.error("Failed to register token:", error);
        }
      }
    };

    registerToken();

    // الاستماع للإشعارات
    const setupListener = () => {
      onMessageListener()
        .then((payload) => {
          if (payload) {
            setNotification(payload);
            // عرض الإشعار
            if ("Notification" in window && Notification.permission === "granted") {
              new Notification(payload.notification?.title || "إشعار جديد", {
                body: payload.notification?.body || "",
                icon: "/icon.png",
              });
            }
          }
        })
        .catch((err) => console.error("Error:", err));
    };

    setupListener();
  }, [authToken]);

  return { token, notification };
};
```

---

### 4️⃣ استخدام Hook في Component

**للتطبيق المستخدم:**

```javascript
"use client";

import { useFirebaseMessaging } from "@/hooks/useFirebaseMessaging";

export default function Dashboard({ authToken }) {
  const { token, notification } = useFirebaseMessaging(authToken);

  return (
    <div>
      <h1>Dashboard</h1>
      {token && <p>الإشعارات مفعّلة ✅</p>}
      {/* باقي الكود */}
    </div>
  );
}
```

**للوحة التحكم:**

نفس الكود، لكن استخدم `device_type: 'web'` في Hook.

---

### 5️⃣ إنشاء Service Worker `public/firebase-messaging-sw.js`

انسخ الكود من `NEXTJS_FIREBASE_CODE_EXAMPLES.md` (السطور 73-126)

**ملاحظة:** استبدل إعدادات Firebase في الملف.

---

### 6️⃣ إضافة متغيرات البيئة `.env.local`

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

## 🔑 كيفية الحصول على القيم من Firebase Console

### 1. إعدادات Firebase (للتطبيق Web):

1. Firebase Console → Project Settings → Your apps → Web app
2. انسخ جميع القيم

### 2. VAPID Key:

1. Firebase Console → Project Settings → Cloud Messaging
2. Web configuration → Web Push certificates
3. إذا لم يكن موجوداً، انقر "Generate key pair"
4. انسخ Key pair

### 3. google-services.json (للتطبيق Android):

1. Firebase Console → Project Settings → Your apps → Android app
2. حمّل ملف `google-services.json`

---

## ✅ قائمة التحقق

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

## 📚 للمزيد من التفاصيل

- `FRONTEND_FIREBASE_SETUP_COMPLETE.md` - دليل شامل
- `NEXTJS_FIREBASE_CODE_EXAMPLES.md` - كود كامل جاهز

