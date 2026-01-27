# ✅ دمج باقات اشتراك البث المباشر في لوحة تحكم الإدارة (Admin Dashboard)

هذا الملف مكتوب ليُرسل كما هو إلى أداة ذكاء اصطناعي/مطور الواجهة الخاصة بلوحة التحكم، لتنفيذ شاشات الإدارة بشكل **احترافي وآمن** وفق الـ API الحالي في السيرفر.

---

## 1) الهدف والوظائف المطلوبة

### المطلوب في لوحة التحكم
- **إدارة باقات اشتراك البث المباشر**:
  - إضافة/تعديل/حذف باقة
  - تفعيل/تعطيل باقة
  - ترتيب الباقات
- **ربط البث بباقة محددة (Streams/Channels)**:
  - عند إنشاء/تعديل بث مباشر من شاشة إدارة البث، يمكن اختيار باقة ينتمي لها البث (`live_stream_package_id`)
  - يمكن تغيير الباقة لاحقًا لأي بث (تعديل)
- **عرض ومعالجة طلبات دفع “اشتراك بث مباشر”** ضمن صفحة طلبات الدفع الحالية:
  - تمييز الطلبات التي `purpose=live_stream_subscription`
  - عند الموافقة: **لا تطلب `period_months`** (هذه خاصة بتجديد اشتراك الإنترنت/الراوتر)
  - عند الموافقة يتم تفعيل/تجديد اشتراك البث تلقائيًا في السيرفر (بدون أي خطوة إضافية من الداشبورد)
 - **إضافة اشتراك بث من المكتب (نقدي فوري / نقدي مؤجل)** عبر نفس مسار الدفعات النقدية.

---

## 2) صلاحيات وأمان (مهم جدًا)

- **كل مسارات إدارة الباقات Admin فقط**: `role:admin`
- المصادقة: `Authorization: Bearer {token}`
- ممنوع كشف روابط البث الحقيقية للمستخدمين (السيرفر يطبّق ذلك تلقائيًا للمستخدم العادي).

---

## 3) الكيانات (Data Models)

### A) LiveStreamPackage (باقة بث مباشر)
الحقول:
- `id` رقم
- `name` اسم الباقة (string, max 100)
- `description` وصف (nullable)
- `duration_days` مدة الباقة بالأيام (1..3650)
- `price` السعر (>= 0)
- `currency` العملة: `USD | SYP | TRY`
- `is_active` فعال/غير فعال (boolean)
- `sort_order` ترتيب العرض (int)

### B) PaymentRequest (طلب دفع)
مضاف حديثًا:
- `purpose`:
  - `internet_subscription` (الافتراضي/القديم)
  - `live_stream_subscription` (جديد)
- `meta` (JSON):
  - عند شراء باقة بث: `meta.package_id = {id}`

### C) LiveStream (البث المباشر)
مضاف حديثًا:
- `live_stream_package_id` (nullable):
  - إذا تم تحديده: هذا البث/القناة يصبح متاحًا فقط لمشتركي هذه الباقة (أو من لديه `live_access=true` كاستثناء يدوي)

---

## 4) تعريف واضح لتداخل “live_access” مع “الباقات” (لتجنب الأخطاء)

لتكون الفكرة سهلة ومقاومة للأخطاء، اعتبر:

- `live_access` = **صلاحية يدوية استثنائية (VIP Override)** من الأدمن:
  - إذا كانت مفعّلة: المستخدم يستطيع مشاهدة **كل** البثوث (حتى البثوط المربوطة بباقات) طالما لديه اشتراك إنترنت أساسي نشط.
- “الباقات” = **الطريقة الطبيعية للشراء**:
  - إذا كانت `live_access` غير مفعلة: المستخدم لا يرى/لا يشاهد إلا البثوط التي تسمح بها باقاته.

### مصفوفة السيناريوهات (User State Matrix)

> ملاحظة: دائمًا يشترط وجود اشتراك إنترنت أساسي نشط لمشاهدة البث.

| حالة المستخدم | يملك باقة نشطة؟ | live_access | ماذا يرى/يشاهد؟ |
|---|---:|---:|---|
| 1 | نعم | ❌ | يرى فقط البثوط/القنوات المرتبطة بباقته + البثوط العامة (غير المربوطة بباقة) حسب `access_type` |
| 2 | نعم | ✅ | يرى كل البثوط (Override) |
| 3 | لا | ✅ | يرى كل البثوط (Override) |
| 4 | لا | ❌ | يرى فقط البثوط العامة غير المربوطة بباقة من نوع `all_subscribers` |

### نقطة مهمة لواجهة الداشبورد
- اعرض `live_access` في صفحة المستخدم باسم واضح مثل:
  - **“صلاحية بث استثنائية (VIP)”**
  - مع تحذير: “تتجاوز الباقات وتفتح جميع القنوات”.

---

## 4) نقاط النهاية الخاصة بالباقات (API Endpoints)

### 4.1 عرض الباقات في لوحة التحكم (Admin list)
`GET /api/admin/live-stream-packages`

Headers:
- `Authorization: Bearer {token}`

Query (اختياري):
- `is_active=true|false`
- `per_page=15`

Response (مختصر):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "باقة شهر",
      "description": "وصف",
      "duration_days": 30,
      "price": 5,
      "currency": "USD",
      "is_active": true,
      "sort_order": 0
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "per_page": 15, "total": 1 }
}
```

### 4.2 إنشاء باقة
`POST /api/admin/live-stream-packages`

Body (JSON):
```json
{
  "name": "باقة شهر",
  "description": "اشتراك بث لمدة شهر",
  "duration_days": 30,
  "price": 5,
  "currency": "USD",
  "is_active": true,
  "sort_order": 1
}
```

### 4.3 تحديث باقة
`PUT /api/admin/live-stream-packages/{package}`

Body (JSON) (كلها اختيارية):
```json
{
  "price": 7,
  "is_active": false,
  "sort_order": 3
}
```

### 4.4 حذف باقة
`DELETE /api/admin/live-stream-packages/{package}`

---

## 5) دمج “طلبات دفع اشتراك بث مباشر” في شاشة طلبات الدفع الحالية

### كيف تميّز طلبات اشتراك البث؟
داخل كائن `PaymentRequestResource` سيأتي:
- `purpose: "live_stream_subscription"`
- `plan_name`: اسم الباقة
- `meta.package_id`: رقم الباقة

### عند الموافقة على الطلب (Approve)
Endpoint الموجود مسبقًا:
`PUT /api/admin/payment-requests/{paymentRequest}/status`

Body:
- **لـ live_stream_subscription**:
```json
{
  "status": 1,
  "approved_amount": 5,
  "notes": "تمت الموافقة على اشتراك البث"
}
```
ملاحظة: `period_months` **غير مطلوب** لهذا النوع.

- **لـ internet_subscription** (القديم):
```json
{
  "status": 1,
  "period_months": 1,
  "plan_name": "خطة 1 شهر",
  "approved_amount": 10
}
```

### عند الرفض (Reject)
```json
{
  "status": 2,
  "reject_reason": "الايصال غير واضح"
}
```

### ملاحظة تنفيذية مهمة للواجهة
- عند فتح مودال “تحديث الحالة”:
  - إذا كان `paymentRequest.purpose === "live_stream_subscription"`:
    - اخفِ حقل `period_months` تمامًا
    - اعرض badge “اشتراك بث”
    - اعرض `plan_name` و `meta.package_id`
  - غير ذلك: اعرض الحقول القديمة (تجديد الانترنت).

---

## 6) شاشات UI المقترحة (جاهزة للتنفيذ)

### شاشة: إدارة باقات البث
- Table columns:
  - الاسم
  - المدة بالأيام
  - السعر + العملة
  - الحالة (Active)
  - الترتيب
  - أزرار: تعديل/حذف
- Actions:
  - زر “إضافة باقة”
  - Toggle `is_active`
  - Sort editing عبر `sort_order`

### شاشة: طلبات الدفع (تعديل بسيط)
- أضف عمود/Badge يوضح:
  - `purpose` (اشتراك بث / تجديد انترنت)
- عند عرض التفاصيل:
  - إذا `purpose=live_stream_subscription`: اعرض الباقة من `plan_name` و `meta.package_id`
- عند الموافقة:
  - لا تطلب `period_months` للبث

### شاشة: إدارة البث المباشر (تعديل بسيط على شاشة موجودة)
- أضف dropdown/select باسم: **الباقة (اختياري)**
  - القيمة: `live_stream_package_id`
  - الخيارات: من `GET /api/admin/live-stream-packages` (يمكن إظهار كل الباقات بما فيها غير النشطة لأغراض التعديل، مع تحذير)
  - عند تركها فارغة: البث غير مربوط بباقة (يطبق فقط access_type القديم)

---

## 7) إضافة اشتراك بث “نقدي فوري / مؤجل” من لوحة التحكم

Endpoint الموجود مسبقًا:
`POST /api/admin/payment-requests/cash-payment`

### 7.1 نقدي فوري (Paid) لاشتراك بث
Body:
```json
{
  "user_id": 5,
  "purpose": "live_stream_subscription",
  "package_id": 1,
  "amount": 5,
  "currency": "USD",
  "is_deferred": false,
  "notes": "اشتراك بث من المكتب"
}
```

### 7.2 نقدي مؤجل (Deferred) لاشتراك بث
Body:
```json
{
  "user_id": 5,
  "purpose": "live_stream_subscription",
  "package_id": 1,
  "amount": 5,
  "currency": "USD",
  "is_deferred": true,
  "notes": "اشتراك بث مؤجل"
}
```

ملاحظات:
- في الحالتين: يتم إنشاء `PaymentRequest` بحالة APPROVED تلقائيًا (لأنها دفعة نقدية من المكتب).
- يتم تفعيل/تجديد اشتراك البث تلقائيًا عند إنشاء الدفعة (حتى لو كانت مؤجلة).

### 7.3 نقدي (تجديد إنترنت) — القديم
Body مثال:
```json
{
  "user_id": 5,
  "purpose": "internet_subscription",
  "period_months": 1,
  "amount": 10,
  "currency": "USD",
  "is_deferred": false
}
```

---

## 8) ملاحظات أمان عالية (High Security Notes)

- لا تُظهر أي “رابط بث” حقيقي في لوحة المستخدم العادي.
- لا تُسجل أو تُطبع روابط التشغيل المحمية في logs أو analytics.
- اعتبر `payment_request.meta` بيانات حساسة (لا تعتمد عليها وحدها في الواجهة، لكنها مفيدة للعرض).

---

## 9) مراجع للـ Endpoints التي تم إضافتها في السيرفر

- إدارة الباقات: `api/admin/live-stream-packages/*`
- باقات للمستخدم: `api/live-stream-packages`
- شراء باقة: `api/live-stream-packages/{package}/purchase`
- تشغيل محمي للبث: مذكور في ملف تطبيق المستخدم.

