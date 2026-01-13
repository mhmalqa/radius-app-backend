# 🧪 دليل اختبار API على Postman

## 📋 نظرة عامة

هذا الدليل يوضح كيفية اختبار API تجديد الاشتراك من Radius باستخدام Postman.

---

## 🔐 الخطوة 1: تسجيل الدخول للحصول على Token

### إعدادات الطلب

-   **Method:** `POST`
-   **URL:** `http://your-backend-url/api/auth/login`

### Headers

```
Accept: application/json
Content-Type: application/json
```

### Body (raw JSON)

```json
{
    "username": "your_username",
    "password": "your_password"
}
```

### الاستجابة المتوقعة

```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "username": "admin",
            "role": 2
        },
        "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
    }
}
```

**احفظ الـ Token** لاستخدامه في الطلبات التالية.

---

## 🔄 الخطوة 2: اختبار تجديد الاشتراك مباشرة من Radius API

### إعدادات الطلب

-   **Method:** `POST`
-   **URL:** `http://38.156.75.137:3031/radiusmanager/USERS/dash/renew_subscription.php`

### Headers

```
Authorization: Bearer APP2025M
Accept: application/json
Content-Type: application/json
```

### Body (raw JSON)

```json
{
    "username": "testt",
    "plan_id": 2,
    "months": 0.5,
    "paid_status": "paid"
}
```

### الاستجابة المتوقعة (نجاح)

```json
{
    "status": "success",
    "username": "testt",
    "service": "2M-PPP",
    "price": "8.000000",
    "months": 0.5,
    "days_added": 15,
    "old_expiration": "2026-06-06 00:00:00",
    "new_expiration": "2026-06-21 00:00:00",
    "paid_status": "paid"
}
```

### الاستجابة المتوقعة (فشل)

```json
{
    "status": "error",
    "message": "Unauthorized Access"
}
```

**ملاحظة:** إذا حصلت على `"Unauthorized Access"`، تحقق من:
-   صحة `Authorization` header
-   صحة قيمة `Bearer APP2025M`

---

## 📋 الخطوة 3: الحصول على قائمة الخدمات (للعثور على plan_id)

### إعدادات الطلب

-   **Method:** `GET`
-   **URL:** `http://38.156.75.137:3031/radiusmanager/USERS/dash/get_services.php`

### Headers

```
Authorization: Bearer APP2025M
Accept: application/json
```

### الاستجابة المتوقعة

```json
{
    "status": "success",
    "count": 12,
    "services": [
        {
            "service_id": 1,
            "service_name": "10M-PPP",
            "price": "30.00",
            "currency": "SYP",
            "speed": {
                "download_kbps": 10485760,
                "upload_kbps": 104857600,
                "download_mbps": 10485.8,
                "upload_mbps": 104857.6
            },
            "unlimited": true
        },
        {
            "service_id": 2,
            "service_name": "2M-PPP",
            "price": "8.00",
            "currency": "USD",
            "speed": {
                "download_kbps": 2097152,
                "upload_kbps": 20971520,
                "download_mbps": 2097.2,
                "upload_mbps": 20971.5
            },
            "unlimited": false
        }
    ]
}
```

**استخدم `service_id` كـ `plan_id` في طلب التجديد.**

---

## 💰 الخطوة 4: اختبار قبول طلب دفع من Backend

### إعدادات الطلب

-   **Method:** `PUT`
-   **URL:** `http://your-backend-url/api/admin/payment-requests/{payment_request_id}/status`

**استبدل `{payment_request_id}` بـ ID الطلب الفعلي.**

### Headers

```
Authorization: Bearer {your_token}
Accept: application/json
Content-Type: application/json
```

**استبدل `{your_token}` بالـ Token الذي حصلت عليه من تسجيل الدخول.**

### Body (raw JSON)

```json
{
    "status": 1,
    "period_months": 0.5,
    "plan_name": "2M-PPP",
    "approved_amount": 4,
    "notes": "تم الموافقة على الطلب"
}
```

**الحقول:**

-   `status`: `1` (موافق) أو `2` (مرفوض)
-   `period_months`: عدد الأشهر (يدعم القيم العشرية مثل 0.5)
-   `plan_name`: اسم الخطة (سيتم البحث عن `plan_id` تلقائياً)
-   `approved_amount`: المبلغ المعتمد
-   `notes`: ملاحظات (اختياري)

### الاستجابة المتوقعة

```json
{
    "success": true,
    "message": "تم قبول الطلب بنجاح",
    "data": {
        "id": 1,
        "status": 1,
        "status_label": "مقبول",
        "plan_name": "2M-PPP",
        "period_months": 0.5,
        "approved_amount": 4,
        "is_paid": true
    }
}
```

**ما يحدث تلقائياً:**

1. ✅ البحث عن `plan_id` من `plan_name`
2. ✅ إرسال طلب التجديد إلى Radius API
3. ✅ مزامنة بيانات الاشتراك من Radius
4. ✅ إضافة الإيرادات (revenue)

---

## 💵 الخطوة 5: اختبار إضافة دفع نقدي

### إعدادات الطلب

-   **Method:** `POST`
-   **URL:** `http://your-backend-url/api/admin/payment-requests/cash-payment`

### Headers

```
Authorization: Bearer {your_token}
Accept: application/json
Content-Type: application/json
```

### Body (raw JSON) - دفع نقدي عادي

```json
{
    "user_id": 1,
    "amount": 4,
    "currency": "USD",
    "period_months": 0.5,
    "plan_name": "2M-PPP",
    "is_deferred": false,
    "payment_date": "2026-01-15",
    "notes": "دفعة نقدية"
}
```

### Body (raw JSON) - دفع مؤجل

```json
{
    "user_id": 1,
    "amount": 4,
    "currency": "USD",
    "period_months": 0.5,
    "plan_name": "2M-PPP",
    "is_deferred": true,
    "payment_date": "2026-01-15",
    "notes": "دفعة مؤجلة"
}
```

### الاستجابة المتوقعة

```json
{
    "success": true,
    "message": "تم إضافة الدفعة النقدية بنجاح",
    "data": {
        "id": 2,
        "payment_type": "cash",
        "amount": 4,
        "currency": "USD",
        "period_months": 0.5,
        "plan_name": "2M-PPP",
        "status": 1,
        "is_paid": true,
        "is_deferred": false
    }
}
```

**ما يحدث تلقائياً:**

1. ✅ إنشاء طلب دفع نقدي
2. ✅ الموافقة عليه تلقائياً
3. ✅ البحث عن `plan_id` من `plan_name`
4. ✅ إرسال طلب التجديد إلى Radius API
5. ✅ مزامنة بيانات الاشتراك من Radius
6. ✅ إضافة الإيرادات (إذا لم يكن مؤجل)

---

## 📝 أمثلة إضافية

### مثال 1: تجديد لمدة 3 أشهر

```json
{
    "username": "testt",
    "plan_id": 2,
    "months": 3,
    "paid_status": "paid"
}
```

### مثال 2: تجديد لمدة 1.5 شهر (45 يوم)

```json
{
    "username": "testt",
    "plan_id": 2,
    "months": 1.5,
    "paid_status": "paid"
}
```

### مثال 3: تجديد بدون تحديد plan_id

```json
{
    "username": "testt",
    "months": 1,
    "paid_status": "paid"
}
```

**ملاحظة:** قد يفشل إذا كان `plan_id` مطلوباً في Radius API.

---

## ⚠️ استكشاف الأخطاء

### خطأ: "Unauthorized Access"

**السبب:** مفتاح API غير صحيح أو مفقود.

**الحل:**

1. تحقق من `Authorization` header
2. تأكد من استخدام `Bearer APP2025M`
3. تحقق من أن المفتاح صحيح في ملف `.env`

### خطأ: "plan_id not found"

**السبب:** `plan_id` غير موجود في قاعدة بيانات Radius.

**الحل:**

1. احصل على قائمة الخدمات من `/get_services.php`
2. استخدم `service_id` الصحيح كـ `plan_id`

### خطأ: "User not found"

**السبب:** اسم المستخدم غير موجود في Radius.

**الحل:**

1. تحقق من اسم المستخدم
2. تأكد من أن المستخدم موجود في Radius

### خطأ: HTTP 401/403

**السبب:** مشكلة في المصادقة.

**الحل:**

1. تحقق من `Authorization` header
2. تأكد من صحة مفتاح API
3. تحقق من أن الـ Token صالح (للطلبات من Backend)

---

## 📚 روابط مفيدة

-   [دليل تجديد الاشتراك الكامل](./RADIUS_RENEW_API_GUIDE.md)
-   [دليل API العام](./README_API.md)
