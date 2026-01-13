# ⚡ دليل سريع: إعداد لوحة التحكم للإشعارات

## 🎯 الخطوات السريعة (5 خطوات)

### 1️⃣ تثبيت Firebase

```bash
npm install firebase
```

---

### 2️⃣ إنشاء `lib/firebase.js`

انسخ الكود من `ADMIN_DASHBOARD_FIREBASE_SETUP.md` (الخطوة 2)

---

### 3️⃣ إنشاء `hooks/useFirebaseMessaging.js`

انسخ الكود من `ADMIN_DASHBOARD_FIREBASE_SETUP.md` (الخطوة 3)

**مهم:** استخدم `device_type: "web"` عند إرسال Token

---

### 4️⃣ استخدام Hook في Layout

```javascript
"use client";
import { useFirebaseMessaging } from "@/hooks/useFirebaseMessaging";

export default function Layout({ children, authToken }) {
  const { isRegistered } = useFirebaseMessaging(authToken);
  
  return <div>{children}</div>;
}
```

---

### 5️⃣ إضافة `.env.local`

```env
NEXT_PUBLIC_API_URL=http://your-backend-url
NEXT_PUBLIC_FIREBASE_API_KEY=AIzaSyCcsx0T2OTasI2fVeSue0_ER30xKWKZQiU
NEXT_PUBLIC_FIREBASE_AUTH_DOMAIN=almutahidat-net.firebaseapp.com
NEXT_PUBLIC_FIREBASE_PROJECT_ID=almutahidat-net
NEXT_PUBLIC_FIREBASE_STORAGE_BUCKET=almutahidat-net.firebasestorage.app
NEXT_PUBLIC_FIREBASE_MESSAGING_SENDER_ID=1051049336008
NEXT_PUBLIC_FIREBASE_APP_ID=1:1051049336008:web:9e9c079adf25ec26b0d9fd
NEXT_PUBLIC_FIREBASE_VAPID_KEY=BBiDbyEE9PKzBsMqYJpp3W6HhNKwLsawkUASVH58PmNQpQBVR7zvwTMJWXyVQFPrvJKw_tD-S66Ubzlv33RF30o
```

**ملاحظة:** استبدل `http://your-backend-url` برابط Backend API الفعلي فقط.

---

## ✅ جميع القيم جاهزة!

جميع قيم Firebase جاهزة ومضبوطة. فقط استبدل `http://your-backend-url` برابط Backend API الفعلي.

---

## ✅ هذا كل شيء!

بعد إكمال هذه الخطوات، ستصل الإشعارات لحظياً.

---

## 📚 للمزيد من التفاصيل

راجع `ADMIN_DASHBOARD_FIREBASE_SETUP.md` للدليل الكامل.

