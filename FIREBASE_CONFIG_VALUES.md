# 🔑 قيم Firebase الكاملة للوحة التحكم

## ✅ جميع القيم من Firebase Console

### 1. Project Information:
- **Project ID**: `almutahidat-net`
- **Project Name**: `almutahidat-net`
- **Auth Domain**: `almutahidat-net.firebaseapp.com`
- **Storage Bucket**: `almutahidat-net.firebasestorage.app`
- **Messaging Sender ID**: `1051049336008`

### 2. Web App Configuration:
- **API Key**: `AIzaSyCcsx0T2OTasI2fVeSue0_ER30xKWKZQiU`
- **App ID**: `1:1051049336008:web:9e9c079adf25ec26b0d9fd`
- **Measurement ID**: `G-FYNP48YS1V` (لـ Analytics، اختياري)

### 3. VAPID Key (Web Push Certificate):
```
BBiDbyEE9PKzBsMqYJpp3W6HhNKwLsawkUASVH58PmNQpQBVR7zvwTMJWXyVQFPrvJKw_tD-S66Ubzlv33RF30o
```

**تاريخ الإضافة**: Dec 28, 2025

---

## 📝 إعدادات Firebase الكاملة

```javascript
const firebaseConfig = {
  apiKey: "AIzaSyCcsx0T2OTasI2fVeSue0_ER30xKWKZQiU",
  authDomain: "almutahidat-net.firebaseapp.com",
  projectId: "almutahidat-net",
  storageBucket: "almutahidat-net.firebasestorage.app",
  messagingSenderId: "1051049336008",
  appId: "1:1051049336008:web:9e9c079adf25ec26b0d9fd",
  measurementId: "G-FYNP48YS1V" // اختياري
};
```

---

## 📋 ملف `.env.local` للوحة التحكم

**انسخ هذا الكود مباشرة إلى `.env.local`:**

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

**ملاحظة:** استبدل `http://your-backend-url` برابط Backend API الفعلي.

---

## ✅ جميع القيم جاهزة!

تم الحصول على جميع القيم من Firebase Console:
- ✅ API Key
- ✅ App ID
- ✅ VAPID Key
- ✅ جميع إعدادات Firebase الأخرى

---

## 📝 Service Worker Configuration

في ملف `public/firebase-messaging-sw.js`، استخدم:

```javascript
const firebaseConfig = {
  apiKey: "AIzaSyCcsx0T2OTasI2fVeSue0_ER30xKWKZQiU",
  authDomain: "almutahidat-net.firebaseapp.com",
  projectId: "almutahidat-net",
  storageBucket: "almutahidat-net.firebasestorage.app",
  messagingSenderId: "1051049336008",
  appId: "1:1051049336008:web:9e9c079adf25ec26b0d9fd",
};
```

---

## ✅ قائمة التحقق

- [x] Project ID: `almutahidat-net`
- [x] Auth Domain: `almutahidat-net.firebaseapp.com`
- [x] Storage Bucket: `almutahidat-net.firebasestorage.app`
- [x] Messaging Sender ID: `1051049336008`
- [x] API Key: `AIzaSyCcsx0T2OTasI2fVeSue0_ER30xKWKZQiU`
- [x] App ID: `1:1051049336008:web:9e9c079adf25ec26b0d9fd`
- [x] VAPID Key: `BBiDbyEE9PKzBsMqYJpp3W6HhNKwLsawkUASVH58PmNQpQBVR7zvwTMJWXyVQFPrvJKw_tD-S66Ubzlv33RF30o`

---

## 🎯 الخطوات التالية

1. ✅ جميع القيم جاهزة
2. ✅ أضف القيم في `.env.local` (استخدم القيم أعلاه)
3. ✅ استخدم القيم في `lib/firebase.js` و `public/firebase-messaging-sw.js`
4. ✅ ابدأ التطوير!

---

## 📚 المراجع

- `ADMIN_DASHBOARD_FIREBASE_SETUP.md` - دليل شامل
- `ADMIN_DASHBOARD_QUICK_SETUP.md` - دليل سريع

