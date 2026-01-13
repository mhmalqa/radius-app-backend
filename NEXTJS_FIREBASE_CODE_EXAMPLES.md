# 💻 كود Next.js جاهز: Firebase Push Notifications

## 📱 تطبيق المستخدم (Next.js)

### 1. ملف `lib/firebase.js`

```javascript
import { initializeApp } from 'firebase/app';
import { getMessaging, getToken, onMessage } from 'firebase/messaging';

// استبدل هذه القيم بإعدادات Firebase الخاصة بك
const firebaseConfig = {
  apiKey: process.env.NEXT_PUBLIC_FIREBASE_API_KEY,
  authDomain: process.env.NEXT_PUBLIC_FIREBASE_AUTH_DOMAIN,
  projectId: process.env.NEXT_PUBLIC_FIREBASE_PROJECT_ID,
  storageBucket: process.env.NEXT_PUBLIC_FIREBASE_STORAGE_BUCKET,
  messagingSenderId: process.env.NEXT_PUBLIC_FIREBASE_MESSAGING_SENDER_ID,
  appId: process.env.NEXT_PUBLIC_FIREBASE_APP_ID
};

// تهيئة Firebase
const app = initializeApp(firebaseConfig);

// الحصول على Messaging instance
let messaging = null;
if (typeof window !== 'undefined') {
  messaging = getMessaging(app);
}

// الحصول على FCM Token
export const getFCMToken = async () => {
  if (!messaging) {
    console.warn('Messaging not available');
    return null;
  }

  try {
    const token = await getToken(messaging, {
      vapidKey: process.env.NEXT_PUBLIC_FIREBASE_VAPID_KEY
    });
    
    if (token) {
      console.log('FCM Token:', token);
      return token;
    } else {
      console.warn('No registration token available.');
      return null;
    }
  } catch (error) {
    console.error('An error occurred while retrieving token:', error);
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
      console.log('Message received:', payload);
      resolve(payload);
    });
  });
};
```

### 2. ملف `public/firebase-messaging-sw.js`

```javascript
// Import Firebase scripts
importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js');

// Firebase configuration
const firebaseConfig = {
  apiKey: "YOUR_API_KEY",
  authDomain: "YOUR_PROJECT_ID.firebaseapp.com",
  projectId: "YOUR_PROJECT_ID",
  storageBucket: "YOUR_PROJECT_ID.appspot.com",
  messagingSenderId: "YOUR_SENDER_ID",
  appId: "YOUR_APP_ID"
};

// Initialize Firebase
firebase.initializeApp(firebaseConfig);

// Retrieve an instance of Firebase Messaging
const messaging = firebase.messaging();

// Handle background messages
messaging.onBackgroundMessage((payload) => {
  console.log('Background notification received:', payload);

  const notificationTitle = payload.notification?.title || 'إشعار جديد';
  const notificationOptions = {
    body: payload.notification?.body || '',
    icon: payload.notification?.icon || '/icon.png',
    badge: '/badge.png',
    data: payload.data,
    requireInteraction: true,
  };

  self.registration.showNotification(notificationTitle, notificationOptions);
});

// Handle notification click
self.addEventListener('notificationclick', (event) => {
  console.log('Notification clicked:', event);
  
  event.notification.close();

  // Open app or specific URL
  if (event.notification.data?.action_url) {
    event.waitUntil(
      clients.openWindow(event.notification.data.action_url)
    );
  } else {
    event.waitUntil(
      clients.openWindow('/')
    );
  }
});
```

### 3. Hook لاستخدام Firebase في Components

```javascript
// hooks/useFirebaseMessaging.js
import { useEffect, useState } from 'react';
import { getFCMToken, onMessageListener } from '@/lib/firebase';

export const useFirebaseMessaging = (authToken) => {
  const [token, setToken] = useState(null);
  const [notification, setNotification] = useState(null);

  useEffect(() => {
    // الحصول على Token وإرساله إلى Backend
    const registerToken = async () => {
      const fcmToken = await getFCMToken();
      if (fcmToken && authToken) {
        setToken(fcmToken);
        
        // إرسال Token إلى Backend
        try {
          const response = await fetch('/api/user/device-tokens', {
            method: 'POST',
            headers: {
              'Authorization': `Bearer ${authToken}`,
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              device_token: fcmToken,
              device_type: 'android', // أو 'web' للوحة التحكم
              device_name: navigator.userAgent || 'User Device'
            })
          });

          if (response.ok) {
            console.log('Device token registered successfully');
          }
        } catch (error) {
          console.error('Failed to register device token:', error);
        }
      }
    };

    registerToken();

    // الاستماع للإشعارات
    const setupMessageListener = () => {
      onMessageListener()
        .then((payload) => {
          if (payload) {
            setNotification(payload);
            // عرض الإشعار
            showNotification(payload);
          }
        })
        .catch((err) => {
          console.error('Error in message listener:', err);
        });
    };

    setupMessageListener();
  }, [authToken]);

  const showNotification = (payload) => {
    if ('Notification' in window && Notification.permission === 'granted') {
      const notification = new Notification(payload.notification?.title || 'إشعار جديد', {
        body: payload.notification?.body || '',
        icon: payload.notification?.icon || '/icon.png',
        badge: '/badge.png',
        data: payload.data,
        requireInteraction: true,
      });

      notification.onclick = () => {
        window.focus();
        if (payload.data?.action_url) {
          window.location.href = payload.data.action_url;
        }
        notification.close();
      };
    }
  };

  return { token, notification };
};
```

### 4. استخدام Hook في Component

```javascript
// components/Dashboard.js
'use client';

import { useEffect } from 'react';
import { useFirebaseMessaging } from '@/hooks/useFirebaseMessaging';
import { useState } from 'react';

export default function Dashboard({ authToken }) {
  const { token, notification } = useFirebaseMessaging(authToken);
  const [notifications, setNotifications] = useState([]);

  // طلب إذن الإشعارات
  useEffect(() => {
    if ('Notification' in window && Notification.permission === 'default') {
      Notification.requestPermission().then((permission) => {
        console.log('Notification permission:', permission);
      });
    }
  }, []);

  // إضافة الإشعار الجديد إلى القائمة
  useEffect(() => {
    if (notification) {
      setNotifications((prev) => [notification, ...prev]);
    }
  }, [notification]);

  return (
    <div>
      <h1>Dashboard</h1>
      {token && <p>Device registered: {token.substring(0, 20)}...</p>}
      
      {notifications.length > 0 && (
        <div>
          <h2>الإشعارات</h2>
          {notifications.map((notif, index) => (
            <div key={index}>
              <h3>{notif.notification?.title}</h3>
              <p>{notif.notification?.body}</p>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
```

### 5. ملف `.env.local` (للتطبيق)

```env
NEXT_PUBLIC_FIREBASE_API_KEY=your-api-key
NEXT_PUBLIC_FIREBASE_AUTH_DOMAIN=your-project-id.firebaseapp.com
NEXT_PUBLIC_FIREBASE_PROJECT_ID=your-project-id
NEXT_PUBLIC_FIREBASE_STORAGE_BUCKET=your-project-id.appspot.com
NEXT_PUBLIC_FIREBASE_MESSAGING_SENDER_ID=your-sender-id
NEXT_PUBLIC_FIREBASE_APP_ID=your-app-id
NEXT_PUBLIC_FIREBASE_VAPID_KEY=your-vapid-key
```

---

## 🖥️ لوحة تحكم المسؤول (Next.js)

### 1. ملف `lib/firebase-admin.js`

```javascript
import { initializeApp, getApps, cert } from 'firebase-admin/app';
import { getMessaging } from 'firebase-admin/messaging';

if (!getApps().length) {
  initializeApp({
    credential: cert({
      projectId: process.env.FIREBASE_PROJECT_ID,
      clientEmail: process.env.FIREBASE_CLIENT_EMAIL,
      privateKey: process.env.FIREBASE_PRIVATE_KEY?.replace(/\\n/g, '\n'),
    }),
  });
}

export const adminMessaging = getMessaging();
```

### 2. ملف `lib/firebase-client.js` (للاستقبال)

```javascript
import { initializeApp } from 'firebase/app';
import { getMessaging, getToken, onMessage } from 'firebase/messaging';

const firebaseConfig = {
  apiKey: process.env.NEXT_PUBLIC_FIREBASE_API_KEY,
  authDomain: process.env.NEXT_PUBLIC_FIREBASE_AUTH_DOMAIN,
  projectId: process.env.NEXT_PUBLIC_FIREBASE_PROJECT_ID,
  storageBucket: process.env.NEXT_PUBLIC_FIREBASE_STORAGE_BUCKET,
  messagingSenderId: process.env.NEXT_PUBLIC_FIREBASE_MESSAGING_SENDER_ID,
  appId: process.env.NEXT_PUBLIC_FIREBASE_APP_ID
};

const app = initializeApp(firebaseConfig);
export const messaging = getMessaging(app);

export const getFCMToken = async () => {
  try {
    const token = await getToken(messaging, {
      vapidKey: process.env.NEXT_PUBLIC_FIREBASE_VAPID_KEY
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

### 3. Hook للمسؤول

```javascript
// hooks/useAdminFirebaseMessaging.js
import { useEffect, useState } from 'react';
import { getFCMToken, onMessageListener } from '@/lib/firebase-client';

export const useAdminFirebaseMessaging = (adminToken) => {
  const [token, setToken] = useState(null);
  const [notification, setNotification] = useState(null);

  useEffect(() => {
    const registerToken = async () => {
      const fcmToken = await getFCMToken();
      if (fcmToken && adminToken) {
        setToken(fcmToken);
        
        try {
          const response = await fetch('/api/user/device-tokens', {
            method: 'POST',
            headers: {
              'Authorization': `Bearer ${adminToken}`,
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              device_token: fcmToken,
              device_type: 'web',
              device_name: 'Admin Dashboard'
            })
          });

          if (response.ok) {
            console.log('Admin device token registered');
          }
        } catch (error) {
          console.error('Failed to register admin token:', error);
        }
      }
    };

    registerToken();

    const setupMessageListener = () => {
      onMessageListener()
        .then((payload) => {
          if (payload) {
            setNotification(payload);
            showNotification(payload);
          }
        })
        .catch((err) => console.error('Error:', err));
    };

    setupMessageListener();
  }, [adminToken]);

  const showNotification = (payload) => {
    if ('Notification' in window && Notification.permission === 'granted') {
      new Notification(payload.notification?.title || 'إشعار جديد', {
        body: payload.notification?.body || '',
        icon: payload.notification?.icon || '/icon.png',
        data: payload.data,
      });
    }
  };

  return { token, notification };
};
```

---

## 📦 إعداد Capacitor (لتحويل Next.js إلى APK)

### 1. تثبيت Capacitor

```bash
npm install @capacitor/core @capacitor/cli @capacitor/android
npx cap init
```

### 2. إعداد `capacitor.config.json`

```json
{
  "appId": "com.yourapp.radius",
  "appName": "Radius App",
  "webDir": "out",
  "bundledWebRuntime": false,
  "plugins": {
    "PushNotifications": {
      "presentationOptions": ["badge", "sound", "alert"]
    }
  }
}
```

### 3. إضافة Android

```bash
npx cap add android
npm run build
npx cap sync
npx cap open android
```

### 4. في Android Studio:

1. ضع `google-services.json` في `android/app/`
2. أضف في `android/app/build.gradle`:

```gradle
apply plugin: 'com.google.gms.google-services'

dependencies {
    implementation platform('com.google.firebase:firebase-bom:32.0.0')
    implementation 'com.google.firebase:firebase-messaging'
}
```

3. أضف في `android/build.gradle`:

```gradle
dependencies {
    classpath 'com.google.gms:google-services:4.4.0'
}
```

---

## ✅ ملاحظات مهمة

1. **استبدل جميع القيم** في الكود بإعدادات Firebase الخاصة بك
2. **أضف متغيرات البيئة** في `.env.local`
3. **اختبر على أجهزة حقيقية** للتأكد من عمل الإشعارات
4. **اطلب إذن الإشعارات** من المستخدم قبل استخدامها

---

## 🚀 الخطوات التالية

1. ✅ انسخ الكود إلى مشروعك
2. ✅ استبدل إعدادات Firebase
3. ✅ اختبر الإشعارات
4. ✅ أنشئ APK

