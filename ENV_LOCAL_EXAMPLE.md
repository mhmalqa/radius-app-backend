# 📋 ملف `.env.local` جاهز للنسخ

## ✅ انسخ هذا الكود مباشرة إلى `.env.local`

```env
# Backend API URL
# استبدل هذا الرابط برابط Backend API الفعلي
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

---

## 📝 خطوات الاستخدام

1. أنشئ ملف `.env.local` في جذر مشروع Next.js (لوحة التحكم)
2. انسخ الكود أعلاه
3. استبدل `http://your-backend-url` برابط Backend API الفعلي
4. احفظ الملف
5. أعد تشغيل Next.js (`npm run dev`)

---

## ✅ قائمة التحقق

-   [x] جميع قيم Firebase جاهزة
-   [ ] استبدال `NEXT_PUBLIC_API_URL` برابط Backend الفعلي
-   [ ] إنشاء ملف `.env.local` في جذر المشروع
-   [ ] إعادة تشغيل Next.js

---

## 🎯 مثال

إذا كان Backend API على `http://localhost:8000`:

```env
NEXT_PUBLIC_API_URL=http://localhost:8000
```

أو إذا كان على `https://api.yourapp.com`:

```env
NEXT_PUBLIC_API_URL=https://api.yourapp.com
```
