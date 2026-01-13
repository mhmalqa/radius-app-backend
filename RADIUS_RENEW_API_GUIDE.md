# 🔄 دليل تجديد الاشتراك من Radius API

## 📋 نظرة عامة

عند قبول طلب الدفع أو إضافة دفع نقدي (عادي أو مؤجل)، يتم تجديد الاشتراك تلقائياً من Radius API باستخدام:

-   **اسم المستخدم** (`username`)
-   **الخطة المحددة** (`plan_name`)
-   **عدد الأشهر** (`period_months`)

## 🔗 رابط API المطلوب

### Endpoint محدد للتجديد

الرابط المحدد لتجديد الاشتراك في Radius API:

```env
RADIUS_RENEW_ENDPOINT=/radiusmanager/USERS/dash/renew_subscription.php
```

**الرابط الكامل:**

```
POST http://38.156.75.137:3031/radiusmanager/USERS/dash/renew_subscription.php
```

### Headers المطلوبة

```
Authorization: Bearer APP2025M
Accept: application/json
Content-Type: application/json
```

### Payload المطلوب

```json
{
    "username": "testt",
    "plan_id": 2,
    "months": 0.5,
    "paid_status": "paid"
}
```

**الحقول:**

-   `username` (مطلوب): اسم المستخدم في Radius
-   `plan_id` (مطلوب): معرف الخطة (رقم) - سيتم الحصول عليه تلقائياً من `plan_name` إذا تم توفيره
-   `months` (مطلوب): عدد الأشهر المراد إضافتها (يدعم القيم العشرية مثل 0.5)
-   `paid_status` (مطلوب): حالة الدفع - `"paid"` أو `"unpaid"` (افتراضي: `"paid"`)

**ملاحظات مهمة:**

-   إذا تم توفير `plan_name` في طلب الدفع، سيتم البحث عن `plan_id` المقابل تلقائياً من قائمة الخدمات
-   `months` يدعم القيم العشرية (مثل 0.5 = 15 يوم)
-   `paid_status` يتم تعيينه تلقائياً بناءً على حالة الدفع:
    -   `"paid"`: للدفعات المدفوعة
    -   `"unpaid"`: للدفعات المؤجلة

### الاستجابة المتوقعة

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

**الحقول في الاستجابة:**

-   `status`: حالة العملية (`"success"` أو `"error"`)
-   `username`: اسم المستخدم
-   `service`: اسم الخدمة/الخطة
-   `price`: السعر
-   `months`: الأشهر المضافة
-   `days_added`: الأيام المضافة (مفيد عند استخدام قيم عشرية)
-   `old_expiration`: تاريخ الانتهاء القديم
-   `new_expiration`: تاريخ الانتهاء الجديد
-   `paid_status`: حالة الدفع

## 🔄 آلية العمل

1. **الحصول على plan_id**: إذا تم توفير `plan_name`، يتم البحث عن `plan_id` المقابل من قائمة الخدمات في Radius
2. **إرسال الطلب**: يتم إرسال الطلب إلى `/radiusmanager/USERS/dash/renew_subscription.php` مع البيانات المطلوبة
3. **المزامنة**: بعد نجاح التجديد، يتم مزامنة بيانات الاشتراك من Radius تلقائياً

## 📝 طريقة بديلة: تحديث تاريخ الانتهاء مباشرة

إذا فشلت جميع الـ endpoints، سيتم محاولة تحديث تاريخ الانتهاء مباشرة:

```
POST http://38.156.75.137:3031/radiusmanager/USERS/update.php
```

**Payload:**

```json
{
    "username": "001",
    "expiration": "2026-04-02 10:00:00",
    "service": "2M-PPP",
    "plan": "2M-PPP"
}
```

## ⚙️ الإعدادات

### ملف `.env`

```env
# رابط API الأساسي
RADIUS_API_URL=http://38.156.75.137:3031

# مفتاح API
RADIUS_API_KEY=APP2025M

# Endpoint محدد للتجديد (افتراضي: renew_subscription.php)
RADIUS_RENEW_ENDPOINT=/radiusmanager/USERS/dash/renew_subscription.php
```

### ملف `config/services.php`

```php
'radius' => [
    'api_url' => env('RADIUS_API_URL', 'http://38.156.75.137:3031'),
    'api_key' => env('RADIUS_API_KEY', 'APP2025M'),
    'renew_endpoint' => env('RADIUS_RENEW_ENDPOINT', '/radiusmanager/USERS/dash/renew_subscription.php'),
],
```

## 🎯 متى يتم التجديد تلقائياً؟

يتم تجديد الاشتراك تلقائياً في الحالات التالية:

### 1. عند الموافقة على طلب الدفع

```http
PUT /api/admin/payment-requests/{id}/status
{
  "status": 1,
  "period_months": 3,
  "plan_name": "2M-PPP",
  "approved_amount": 100
}
```

**ما يحدث:**

-   ✅ يتم استدعاء `renewSubscription(username, months, planName)`
-   ✅ يتم تجديد الاشتراك في Radius
-   ✅ يتم مزامنة بيانات الاشتراك من Radius
-   ✅ يتم إضافة الإيرادات (revenue)

### 2. عند إضافة دفع نقدي عادي

```http
POST /api/admin/payment-requests/cash-payment
{
  "user_id": 1,
  "amount": 100,
  "currency": "USD",
  "period_months": 3,
  "plan_name": "2M-PPP",
  "is_deferred": false
}
```

**ما يحدث:**

-   ✅ يتم إنشاء طلب دفع نقدي
-   ✅ يتم الموافقة عليه تلقائياً
-   ✅ يتم تجديد الاشتراك في Radius
-   ✅ يتم إضافة الإيرادات

### 3. عند إضافة دفع نقدي مؤجل

```http
POST /api/admin/payment-requests/cash-payment
{
  "user_id": 1,
  "amount": 100,
  "currency": "USD",
  "period_months": 3,
  "plan_name": "2M-PPP",
  "is_deferred": true
}
```

**ما يحدث:**

-   ✅ يتم إنشاء طلب دفع مؤجل
-   ✅ يتم الموافقة عليه تلقائياً
-   ✅ يتم تجديد الاشتراك في Radius (حتى لو لم يتم الدفع بعد)
-   ❌ لا يتم إضافة الإيرادات (حتى يتم الدفع)

## 📊 مثال على الاستخدام

### مثال 1: تجديد لمدة 3 أشهر مع خطة "2M-PPP"

**الطلب إلى Backend:**

```json
{
    "status": 1,
    "period_months": 3,
    "plan_name": "2M-PPP",
    "approved_amount": 100
}
```

**ما يحدث تلقائياً:**

1. البحث عن `plan_id` من `plan_name` "2M-PPP"
2. إرسال الطلب إلى Radius API:

```json
{
    "username": "001",
    "plan_id": 2,
    "months": 3,
    "paid_status": "paid"
}
```

**الاستجابة من Radius:**

```json
{
    "status": "success",
    "username": "001",
    "service": "2M-PPP",
    "price": "24.000000",
    "months": 3,
    "days_added": 90,
    "old_expiration": "2026-01-02 10:00:00",
    "new_expiration": "2026-04-02 10:00:00",
    "paid_status": "paid"
}
```

### مثال 2: تجديد لمدة 0.5 شهر (15 يوم) مع خطة "2M-PPP"

**الطلب إلى Backend:**

```json
{
    "status": 1,
    "period_months": 0.5,
    "plan_name": "2M-PPP",
    "approved_amount": 4
}
```

**الطلب إلى Radius API:**

```json
{
    "username": "testt",
    "plan_id": 2,
    "months": 0.5,
    "paid_status": "paid"
}
```

**الاستجابة:**

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

## 🔍 السجلات (Logs)

جميع محاولات التجديد يتم تسجيلها في ملفات الـ logs:

### نجاح التجديد

```
[INFO] Subscription renewed successfully in Radius
{
  "username": "001",
  "months": 3,
  "plan": "2M-PPP",
  "endpoint": "/radiusmanager/USERS/renew.php"
}
```

### فشل التجديد

```
[WARNING] Failed to renew subscription in Radius - trying alternative method
{
  "username": "001",
  "months": 3,
  "last_error": "HTTP 404: Not Found"
}
```

## ⚠️ ملاحظات مهمة

1. **التجديد من تاريخ الانتهاء الحالي**: يتم إضافة الأشهر إلى تاريخ الانتهاء الحالي في Radius
2. **دعم الأشهر العشرية**: يمكن استخدام قيم عشرية مثل `0.5` (15 يوم) أو `1.5` (45 يوم)
3. **الحصول على plan_id**: إذا تم توفير `plan_name`، يتم البحث عن `plan_id` المقابل تلقائياً من قائمة الخدمات
4. **paid_status**: يتم تعيينه تلقائياً بناءً على حالة الدفع:
    - `"paid"`: للدفعات المدفوعة (افتراضي)
    - `"unpaid"`: للدفعات المؤجلة
5. **المزامنة التلقائية**: بعد نجاح التجديد، يتم مزامنة بيانات الاشتراك من Radius تلقائياً
6. **معالجة الأخطاء**: إذا فشل الحصول على `plan_id` من `plan_name`، سيتم محاولة التجديد بدون `plan_id` (قد يفشل إذا كان مطلوباً)

## 🛠️ اختبار الـ API

### طريقة 1: استخدام Postman

#### 1. اختبار تجديد الاشتراك مباشرة من Radius API

**إعدادات الطلب:**

-   **Method:** `POST`
-   **URL:** `http://38.156.75.137:3031/radiusmanager/USERS/dash/renew_subscription.php`

**Headers:**

```
Authorization: Bearer APP2025M
Accept: application/json
Content-Type: application/json
```

**Body (raw JSON):**

```json
{
    "username": "testt",
    "plan_id": 2,
    "months": 0.5,
    "paid_status": "paid"
}
```

**الاستجابة المتوقعة:**

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

#### 2. الحصول على قائمة الخدمات (للعثور على plan_id)

**إعدادات الطلب:**

-   **Method:** `GET`
-   **URL:** `http://38.156.75.137:3031/radiusmanager/USERS/dash/get_services.php`

**Headers:**

```
Authorization: Bearer APP2025M
Accept: application/json
```

**الاستجابة:**

```json
{
    "status": "success",
    "count": 12,
    "services": [
        {
            "service_id": 2,
            "service_name": "2M-PPP",
            "price": "8.00",
            "currency": "USD",
            "speed": {
                "download_kbps": 2097152,
                "upload_kbps": 20971520
            }
        }
    ]
}
```

#### 3. اختبار من خلال Backend API (قبول طلب دفع)

**إعدادات الطلب:**

-   **Method:** `PUT`
-   **URL:** `http://your-backend-url/api/admin/payment-requests/{payment_request_id}/status`
-   **Authorization:** `Bearer {your_token}` (من تسجيل الدخول)

**Headers:**

```
Authorization: Bearer {your_token}
Accept: application/json
Content-Type: application/json
```

**Body (raw JSON):**

```json
{
    "status": 1,
    "period_months": 0.5,
    "plan_name": "2M-PPP",
    "approved_amount": 4,
    "notes": "تم الموافقة على الطلب"
}
```

**الاستجابة:**

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
        "approved_amount": 4
    }
}
```

#### 4. اختبار إضافة دفع نقدي

**إعدادات الطلب:**

-   **Method:** `POST`
-   **URL:** `http://your-backend-url/api/admin/payment-requests/cash-payment`
-   **Authorization:** `Bearer {your_token}`

**Headers:**

```
Authorization: Bearer {your_token}
Accept: application/json
Content-Type: application/json
```

**Body (raw JSON):**

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

**الاستجابة:**

```json
{
    "success": true,
    "message": "تم إضافة الدفعة النقدية بنجاح",
    "data": {
        "id": 2,
        "payment_type": "cash",
        "amount": 4,
        "period_months": 0.5,
        "plan_name": "2M-PPP",
        "status": 1,
        "is_paid": true
    }
}
```

### طريقة 2: استخدام cURL

```bash
curl -X POST http://38.156.75.137:3031/radiusmanager/USERS/dash/renew_subscription.php \
  -H "Authorization: Bearer APP2025M" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "testt",
    "plan_id": 2,
    "months": 0.5,
    "paid_status": "paid"
  }'
```

**ملاحظات مهمة:**

-   يجب أن يكون `plan_id` موجوداً في قاعدة بيانات Radius
-   يمكنك الحصول على قائمة الخدمات من `/get_services.php` لمعرفة `plan_id` لكل خطة
-   `months` يدعم القيم العشرية (0.5 = 15 يوم)
-   `paid_status` يجب أن يكون `"paid"` أو `"unpaid"`

## 📞 الدعم

إذا واجهت مشاكل في التجديد:

1. تحقق من سجلات الـ logs
2. تأكد من صحة رابط API
3. تأكد من صحة مفتاح API
4. تحقق من أن المستخدم موجود في Radius
5. تحقق من أن الـ endpoint يعمل بشكل صحيح
