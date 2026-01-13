# ⚙️ أضف هذه السطور في ملف `.env`

## 📝 افتح ملف `.env` وأضف في النهاية:

```env
# Firebase Configuration
FIREBASE_CREDENTIALS_PATH=storage/app/firebase/service-account-key.json
FIREBASE_PROJECT_ID=almutahidat-net
FIREBASE_SERVER_KEY=
```

**ملاحظة:** `FIREBASE_SERVER_KEY` يمكن تركه فارغاً لأننا نستخدم Firebase Admin SDK (V1 API) وليس Legacy API.

---

## ✅ بعد إضافة الإعدادات

```bash
php artisan config:clear
```

---

## 🧪 اختبار الإعداد

بعد إضافة الإعدادات وتشغيل `config:clear`، يمكنك اختبار:

```bash
php artisan tinker
```

```php
$service = app(App\Services\FirebaseMessagingService::class);
// إذا لم يظهر خطأ، يعني الإعداد صحيح ✅
```

---

## 📋 ملخص الإعدادات

- ✅ **FIREBASE_CREDENTIALS_PATH**: `storage/app/firebase/service-account-key.json` (موجود ✅)
- ✅ **FIREBASE_PROJECT_ID**: `almutahidat-net` (من ملف JSON ✅)
- ⚠️ **FIREBASE_SERVER_KEY**: فارغ (غير مطلوب لأن Legacy API معطّل)

---

## 🚀 بعد الإعداد

بعد إضافة الإعدادات، Backend جاهز لإرسال الإشعارات عبر Firebase Admin SDK (V1 API)!

