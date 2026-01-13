# ⚙️ إعداد Firebase في ملف .env

## ✅ ما تم إنجازه

- ✅ تم إعادة تسمية ملف Service Account Key إلى `service-account-key.json`
- ✅ الملف موجود في: `storage/app/firebase/service-account-key.json`
- ✅ Project ID: `almutahidat-net`

---

## 📝 أضف هذه السطور في ملف `.env`

افتح ملف `.env` وأضف في النهاية:

```env
# Firebase Configuration
FIREBASE_CREDENTIALS_PATH=storage/app/firebase/service-account-key.json
FIREBASE_PROJECT_ID=almutahidat-net
FIREBASE_SERVER_KEY=your-server-key-here
```

**استبدل:**
- `your-server-key-here` → Server Key من Firebase Console

---

## 🔑 كيفية الحصول على Server Key

1. اذهب إلى [Firebase Console](https://console.firebase.google.com)
2. اختر مشروع `almutahidat-net`
3. اذهب إلى **Project Settings** (⚙️) → **Cloud Messaging**
4. في قسم **"Cloud Messaging API (Legacy)"**:
   - إذا لم يكن مفعلاً، انقر **"Enable"**
   - انسخ **"Server key"**
5. الصق Server Key في `.env` مكان `your-server-key-here`

---

## ✅ بعد إضافة Server Key

```bash
php artisan config:clear
```

---

## 🧪 اختبار الإعداد

بعد إضافة Server Key، يمكنك اختبار:

```bash
php artisan tinker
```

```php
$fcmService = app(App\Services\FirebaseMessagingService::class);
// إذا لم يظهر خطأ، يعني الإعداد صحيح
```

---

## 📋 قائمة التحقق

- [x] ملف `service-account-key.json` موجود
- [x] Project ID: `almutahidat-net`
- [ ] إضافة Server Key في `.env`
- [ ] تشغيل `php artisan config:clear`

