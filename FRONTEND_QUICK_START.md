# ⚡ دليل البدء السريع: Frontend Firebase

## 🎯 الخطوات السريعة

### 1. تثبيت Firebase

```bash
npm install firebase
```

---

### 2. إنشاء ملف `lib/firebase.js`

انسخ الكود من `NEXTJS_FIREBASE_CODE_EXAMPLES.md` أو استخدم الكود أدناه.

---

### 3. إنشاء Hook `hooks/useFirebaseMessaging.js`

انسخ الكود من `FRONTEND_FIREBASE_SETUP_COMPLETE.md`.

---

### 4. استخدام Hook في Component

```javascript
"use client";

import { useFirebaseMessaging } from "@/hooks/useFirebaseMessaging";

export default function Dashboard({ authToken }) {
    const { token, notification, isRegistered } =
        useFirebaseMessaging(authToken);

    return (
        <div>
            {isRegistered && <p>الإشعارات مفعّلة ✅</p>}
            {/* باقي الكود */}
        </div>
    );
}
```

---

### 5. إضافة متغيرات البيئة `.env.local`

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

### 6. إنشاء Service Worker `public/firebase-messaging-sw.js`

انسخ الكود من `FRONTEND_FIREBASE_SETUP_COMPLETE.md`.

---

## 🔑 كيفية الحصول على القيم المطلوبة

### من Firebase Console:

1. **إعدادات Firebase**:

    - Firebase Console → Project Settings → Your apps → Web app
    - انسخ جميع القيم

2. **VAPID Key**:
    - Firebase Console → Project Settings → Cloud Messaging
    - Web configuration → Web Push certificates
    - انسخ Key pair

---

## ✅ هذا كل شيء!

بعد إكمال هذه الخطوات، ستحصل على الإشعارات لحظياً.

---

## 📚 للمزيد من التفاصيل

راجع `FRONTEND_FIREBASE_SETUP_COMPLETE.md` للدليل الكامل.
