# ✅ ربط باقات اشتراك البث المباشر بتطبيق المستخدم (User App Integration)

هذا الملف يشرح لطرف تطبيق المستخدم كيف:
- يعرض باقات اشتراك البث
- يرسل طلب شراء باقة (طلب دفع)
- يتحقق من الاشتراك الحالي
- يشغّل البث عبر **رابط محمي** بدون كشف الرابط الحقيقي

---

## 1) المتطلبات الأساسية

- جميع الطلبات المحمية تحتاج:
  - `Authorization: Bearer {token}` (Sanctum token)
- المستخدم يجب أن يكون حسابه فعال (`account.active` middleware)
- **مهم**: تشغيل البث يجب أن يكون داخل التطبيق (Video Player / WebView داخل التطبيق).

---

## 1.1) قاعدة مبسطة لتجنب اللبس (مهم)

- إذا كان المستخدم لديه `live_access=true` (مفعلة يدويًا من الأدمن): يستطيع مشاهدة كل القنوات.
- إذا كانت `live_access=false`: لا يشاهد إلا القنوات التي تخص باقاته النشطة (وأيضًا القنوات العامة غير المربوطة بباقة حسب `access_type`).
- عند انتهاء الباقة: تختفي قنواتها من القائمة ويُرفض تشغيلها تلقائيًا (403).

---

## 2) عرض باقات البث المتاحة

### Endpoint
`GET /api/live-stream-packages`

Response:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "باقة شهر",
      "description": "اشتراك بث لمدة شهر",
      "duration_days": 30,
      "price": 5,
      "currency": "USD",
      "is_active": true,
      "sort_order": 0
    }
  ]
}
```

---

## 3) معرفة اشتراك البث الحالي للمستخدم

### Endpoint
`GET /api/user/live-stream-subscription`

Headers:
- `Authorization: Bearer {token}`

Response (عند وجود اشتراك نشط):
```json
{
  "success": true,
  "data": {
    "id": 10,
    "user_id": 5,
    "package": { "id": 1, "name": "باقة شهر", "duration_days": 30, "price": 5, "currency": "USD", "is_active": true, "sort_order": 0 },
    "payment_request_id": 100,
    "starts_at": "2026-01-27T10:00:00Z",
    "expires_at": "2026-02-26T10:00:00Z",
    "status": "active",
    "is_active": true
  }
}
```

Response (لا يوجد):
```json
{ "success": true, "data": null }
```

### (موصى به) معرفة كل اشتراكات البث النشطة (في حال وجود أكثر من باقة)
`GET /api/user/live-stream-subscriptions`

Headers:
- `Authorization: Bearer {token}`

Response:
```json
{
  "success": true,
  "data": [
    {
      "id": 10,
      "package": { "id": 1, "name": "باقة شهر", "duration_days": 30, "price": 5, "currency": "USD", "is_active": true, "sort_order": 0 },
      "expires_at": "2026-02-26T10:00:00Z",
      "status": "active",
      "is_active": true
    }
  ]
}
```

---

## 4) شراء باقة بث (إنشاء طلب دفع اشتراك بث)

### Endpoint
`POST /api/live-stream-packages/{package}/purchase`

Headers:
- `Authorization: Bearer {token}`

Body:
- يمكن الإرسال كـ `multipart/form-data` عند وجود `receipt_file`

حقول اختيارية:
- `payment_method` (string)
- `payment_method_id` (int)
- `transaction_number` (string)
- `receipt_file` (jpg/jpeg/png/pdf - حتى 5MB)
- `payment_date` (date)

Response:
```json
{
  "success": true,
  "message": "تم إرسال طلب الاشتراك بالبث بنجاح وسيتم تفعيله بعد الموافقة",
  "data": {
    "payment_request": {
      "id": 100,
      "payment_type": "online",
      "purpose": "live_stream_subscription",
      "amount": 5,
      "currency": "USD",
      "plan_name": "باقة شهر",
      "meta": { "package_id": 1 },
      "status": 0
    }
  }
}
```

### ماذا يحدث بعد الشراء؟
- يظهر الطلب للأدمن في لوحة التحكم (طلبات الدفع).
- عند موافقة الأدمن عليه:
  - السيرفر يقوم تلقائيًا بإنشاء/تجديد اشتراك البث في جدول `live_stream_subscriptions`.

---

## 5) عرض البثوث وتشغيلها بالرابط المحمي

### 5.1 عرض البثوث
`GET /api/live-streams`

ملاحظة:
- للمستخدم العادي، السيرفر **لا يُظهر الرابط الحقيقي** للبث.
- `stream_url` الذي سيظهر للمستخدم سيكون رابط تشغيل محمي (Proxy) أو قد يكون null حسب منطق الواجهة.
- قد تكون بعض البثوث/القنوات **مرتبطة بباقة محددة**:
  - السيرفر يعرض للمستخدم فقط ما يملك صلاحية له (بحسب اشتراكه في باقة البث)
  - إذا أرادت الواجهة UX “محتوى مقفل” يمكن طلب تطوير إضافي لإظهار البث المقفل مع `required_package` بدل إخفائه

### 5.2 أفضل ممارسة للتشغيل (موصى بها)
قبل بدء التشغيل مباشرة (عند ضغط زر “تشغيل”):
`POST /api/live-streams/{liveStream}/playback`

Headers:
- `Authorization: Bearer {token}`

Response:
```json
{
  "success": true,
  "data": {
    "playback_url": "https://your-domain.com/api/live-streams/12/secure?token=....",
    "expires_at": "2026-01-27T12:00:00Z"
  }
}
```

ثم مرّر `playback_url` إلى مشغّل الفيديو داخل التطبيق (HLS m3u8).

### 5.3 رابط التشغيل المحمي (Proxy)
`GET /api/live-streams/{liveStream}/secure?token=...`

- هذا الرابط **لا يحتاج Authorization** لأنه مخصص لمشغّل الفيديو الذي لا يرسل Headers بسهولة.
- الرابط قصير العمر (TTL) ويمكن التحكم به عبر:
  - `LIVE_STREAM_TOKEN_TTL_SECONDS` (افتراضي 300 ثانية)

### التعامل مع انتهاء صلاحية التوكن
إذا فشل التشغيل أو ظهر 403:
- أعد طلب توكن جديد عبر `POST /playback`
- ثم أعد تشغيل المشغّل بالرابط الجديد.

---

## 6) زر “فتح داخل التطبيق” و”الخيار الإضافي” داخل التطبيق

بما أن الرابط محمي ومؤقت:
- **زر أساسي**: “مشاهدة البث” => يفتح شاشة Player داخل التطبيق ويستخدم `POST /playback` ثم يشغّل `playback_url`.
- **خيار إضافي داخل التطبيق** (بدون خروج للتطبيقات الخارجية):
  - “فتح داخل WebView” (WebView داخل التطبيق فقط) باستخدام `playback_url`.

ملاحظة أمان:
- لا توفّر زر “نسخ الرابط” للمستخدم العادي.
- لا تفتح الرابط في متصفح خارجي (حتى لا يُستفاد منه خارج التطبيق).

---

## 7) رسائل الأخطاء المتوقعة

- 401: المستخدم غير مسجّل دخول
- 403: لا يملك صلاحية (لا يوجد اشتراك نشط / لا يوجد اشتراك بث مباشر للبث الحصري / الحساب معطل)
- 404: البث أو الباقة غير موجودة أو غير متاحة
- 422: أخطاء تحقق (ملف إيصال غير صالح…)

---

## 8) ملاحظات أمان عالية (High Security Notes)

- لا تُسجل `playback_url` في logs أو crash reports.
- التوكن مخزّن في قاعدة البيانات بشكل Hash.
- رابط البث الحقيقي (upstream) مخفي خلف Proxy ولا يصل للمستخدم.

