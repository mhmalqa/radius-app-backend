# 📚 دليل شامل للميزات الجديدة

## فهرس المحتويات

1. [ميزة أنواع الوصول للبث المباشر](#1-ميزة-أنواع-الوصول-للبث-المباشر)
2. [ميزة الدفعات النقدية والدفع المؤجل](#2-ميزة-الدفعات-النقدية-والدفع-المؤجل)
3. [ميزة طلب الصيانة](#3-ميزة-طلب-الصيانة)
4. [ميزة تجديد الاشتراك التلقائي](#4-ميزة-تجديد-الاشتراك-التلقائي)
5. [ميزة الإيرادات والصندوق](#5-ميزة-الإيرادات-والصندوق)
6. [ميزة إدارة روابط التواصل والإعدادات العامة](#6-ميزة-إدارة-روابط-التواصل-والإعدادات-العامة)

---

## 1. ميزة أنواع الوصول للبث المباشر

### الوصف

تم إضافة ميزة تسمح بتحديد نوع الوصول للبث المباشر:

-   **لجميع المشتركين** (`all_subscribers`): متاح لجميع المستخدمين الذين لديهم اشتراك نشط
-   **لمشتركي البث المباشر فقط** (`live_subscribers_only`): متاح فقط للمستخدمين الذين لديهم `live_access = true` واشتراك نشط

### الحقول الجديدة

#### جدول `live_streams`

-   `access_type` (string, default: `all_subscribers`)
    -   القيم الممكنة: `all_subscribers`, `live_subscribers_only`

### API Routes

#### 1. إنشاء بث مباشر جديد (Admin فقط)

```http
POST /api/admin/live-streams
Authorization: Bearer {admin_token}
Content-Type: application/json
```

**المعاملات**:

```json
{
    "title": "مباراة اليوم",
    "description": "مباراة كرة قدم مباشرة",
    "stream_url": "https://example.com/stream.m3u8",
    "access_type": "all_subscribers",
    "thumbnail": "https://example.com/thumbnail.jpg",
    "category": "match",
    "stream_type": "live",
    "is_active": true,
    "is_featured": true,
    "start_time": "2025-12-12 20:00:00",
    "end_time": "2025-12-12 22:00:00"
}
```

**ملاحظات**:

-   `access_type` اختياري، القيمة الافتراضية: `all_subscribers`
-   `access_type: "all_subscribers"` - متاح لجميع المشتركين النشطين
-   `access_type: "live_subscribers_only"` - متاح فقط لمشتركي البث المباشر

**الاستجابة**:

```json
{
    "success": true,
    "message": "تم إنشاء البث بنجاح",
    "data": {
        "id": 1,
        "title": "مباراة اليوم",
        "stream_url": "https://example.com/stream.m3u8",
        "access_type": "all_subscribers",
        "access_type_label": "لجميع المشتركين",
        "is_active": true,
        ...
    }
}
```

#### 2. تحديث بث مباشر (Admin فقط)

```http
PUT /api/admin/live-streams/{id}
Authorization: Bearer {admin_token}
Content-Type: application/json
```

**المعاملات**: نفس معاملات الإنشاء

#### 3. عرض قائمة البث المباشر (للمستخدمين)

```http
GET /api/live-streams
Authorization: Bearer {user_token}
```

**السلوك**:

-   إذا كان `access_type = "all_subscribers"`: يظهر للمستخدمين الذين لديهم اشتراك نشط
-   إذا كان `access_type = "live_subscribers_only"`: يظهر فقط للمستخدمين الذين لديهم `live_access = true` واشتراك نشط

#### 4. عرض بث مباشر محدد

```http
GET /api/live-streams/{id}
Authorization: Bearer {user_token}
```

**التحقق من الصلاحيات**:

-   للبث مع `access_type = "all_subscribers"`: يجب أن يكون لدى المستخدم اشتراك نشط
-   للبث مع `access_type = "live_subscribers_only"`: يجب أن يكون لدى المستخدم `live_access = true` واشتراك نشط

### أمثلة الاستخدام

#### مثال 1: إنشاء بث لجميع المشتركين

```bash
curl -X POST "http://localhost/api/admin/live-streams" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "مباراة اليوم",
    "stream_url": "https://example.com/stream.m3u8",
    "access_type": "all_subscribers",
    "category": "match"
  }'
```

#### مثال 2: إنشاء بث لمشتركي البث المباشر فقط

```bash
curl -X POST "http://localhost/api/admin/live-streams" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "بث خاص",
    "stream_url": "https://example.com/premium-stream.m3u8",
    "access_type": "live_subscribers_only",
    "category": "match"
  }'
```

---

## 2. ميزة الدفعات النقدية والدفع المؤجل

### الوصف

تم إضافة ميزة تسمح للمحاسب/المدير بإضافة دفعات نقدية مباشرة عندما يأتي المستخدم إلى المكتب ويدفع نقداً. كما يمكن إضافة دفعات مؤجلة (دفع مؤجل) حيث يتم تجديد الاشتراك ولكن الدفعة غير مدفوعة بعد.

### الحقول الجديدة

#### جدول `payment_requests`

-   `payment_type` (string, default: `online`)
    -   القيم: `online` (طلب دفع من المستخدم), `cash` (دفعة نقدية من المكتب)
-   `created_by` (foreign key, nullable)
    -   المحاسب/المدير الذي أضاف الدفعة النقدية
-   `is_paid` (boolean, default: `false`)
    -   هل تم الدفع فعلياً (`true`: مدفوع, `false`: غير مدفوع/مؤجل)
-   `is_deferred` (boolean, default: `false`)
    -   هل الدفعة مؤجلة (دفع مؤجل)
-   `paid_at` (timestamp, nullable)
    -   تاريخ الدفع الفعلي

### API Routes

#### 1. إضافة دفعة نقدية (Admin/Accountant فقط)

```http
POST /api/admin/payment-requests/cash-payment
Authorization: Bearer {accountant_token}
Content-Type: application/json
```

**المعاملات المطلوبة**:

```json
{
    "user_id": 5,
    "amount": 50000,
    "period_months": 2,
    "currency": "IQD",
    "payment_date": "2025-12-12",
    "plan_name": "2M-PPP",
    "notes": "دفعة نقدية من المكتب"
}
```

**المعاملات**:

-   `user_id` (required): معرف المستخدم الذي دفع
-   `amount` (required): المبلغ
-   `period_months` (required): عدد أشهر التجديد (1-12)
-   `currency` (optional): العملة (افتراضي: IQD)
-   `payment_date` (optional): تاريخ الدفع (افتراضي: اليوم)
-   `plan_name` (optional): اسم الخدمة/الباقة
-   `is_deferred` (optional, boolean): هل الدفعة مؤجلة (افتراضي: false)
    -   `true`: دفعة مؤجلة - يتم تجديد الاشتراك ولكن لا يتم إضافة الإيراد حتى يتم الدفع
    -   `false`: دفعة مدفوعة - يتم تجديد الاشتراك وإضافة الإيراد مباشرة
-   `notes` (optional): ملاحظات

**الاستجابة**:

```json
{
    "success": true,
    "message": "تم إضافة الدفعة النقدية بنجاح",
    "data": {
        "id": 10,
        "user": {...},
        "payment_type": "cash",
        "payment_type_label": "نقدي",
        "amount": 50000,
        "period_months": 2,
        "plan_name": "2M-PPP",
        "status": 1,
        "status_label": "مقبول",
        "created_by": {...},
        ...
    }
}
```

**ملاحظات**:

-   الدفعات النقدية تُقبل تلقائياً (`status = approved`)
-   يتم تجديد الاشتراك تلقائياً في Radius (حتى للدفعات المؤجلة)
-   يتم إضافة الإيراد تلقائياً فقط للدفعات المدفوعة (`is_deferred = false`)
-   الدفعات المؤجلة (`is_deferred = true`): يتم تجديد الاشتراك ولكن لا يتم إضافة الإيراد حتى يتم الدفع لاحقاً

### عرض الدفعات

#### للمستخدمين

```http
GET /api/payment-requests
Authorization: Bearer {user_token}
```

**الاستجابة**: تعرض جميع الدفعات (نقدية وعبر الإنترنت) للمستخدم

#### للمسؤولين

```http
GET /api/admin/payment-requests
Authorization: Bearer {admin_token}
```

**المعاملات الاختيارية**:

-   `status`: فلترة حسب الحالة (0: pending, 1: approved, 2: rejected)
-   `is_paid`: فلترة حسب حالة الدفع (`true`: مدفوع, `false`: غير مدفوع)
-   `is_deferred`: فلترة حسب الدفع المؤجل (`true`: مؤجل, `false`: غير مؤجل)

**أمثلة**:

-   `GET /api/admin/payment-requests?is_deferred=true&is_paid=false` - عرض الدفعات المؤجلة غير المدفوعة
-   `GET /api/admin/payment-requests?is_paid=false` - عرض جميع الدفعات غير المدفوعة

**الاستجابة**: تعرض جميع الدفعات لجميع المستخدمين

### أمثلة الاستخدام

#### مثال 1: إضافة دفعة نقدية مدفوعة

```bash
curl -X POST "http://localhost/api/admin/payment-requests/cash-payment" \
  -H "Authorization: Bearer {accountant_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 5,
    "amount": 50000,
    "period_months": 3,
    "plan_name": "2M-PPP",
    "is_deferred": false,
    "notes": "دفعة نقدية - تم الدفع في المكتب"
  }'
```

#### مثال 2: إضافة دفعة مؤجلة (دفع مؤجل)

```bash
curl -X POST "http://localhost/api/admin/payment-requests/cash-payment" \
  -H "Authorization: Bearer {accountant_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 5,
    "amount": 50000,
    "period_months": 3,
    "plan_name": "2M-PPP",
    "is_deferred": true,
    "notes": "دفعة مؤجلة - سيتم الدفع لاحقاً"
  }'
```

**ما يحدث**:

-   ✅ يتم تجديد الاشتراك في Radius (3 أشهر)
-   ❌ لا يتم إضافة الإيراد (سيتم إضافته عند الدفع لاحقاً)
-   ✅ تظهر في قائمة الدفعات المؤجلة غير المدفوعة

#### مثال 3: تحديث دفعة مؤجلة إلى مدفوعة

```http
POST /api/admin/payment-requests/{id}/mark-as-paid
Authorization: Bearer {accountant_token}
```

**الاستجابة**:

```json
{
    "success": true,
    "message": "تم تحديث حالة الدفعة إلى مدفوع بنجاح. تم إضافة الإيراد",
    "data": {
        "id": 10,
        "is_paid": true,
        "is_deferred": true,
        "payment_status_label": "مدفوع",
        "paid_at": "2025-12-12T15:30:00Z",
        "revenue": {
            "id": 5,
            "amount": 50000,
            "currency": "IQD",
            "period_months": 3,
            "payment_date": "2025-12-12"
        }
    }
}
```

**ما يحدث**:

-   ✅ يتم تحديث `is_paid` إلى `true`
-   ✅ يتم تعيين `paid_at` بتاريخ الدفع
-   ✅ يتم إضافة الإيراد إلى جدول `revenues`

---

## 3. ميزة طلب الصيانة

### الوصف

تم إضافة ميزة تسمح للمستخدمين بطلب صيانة. النظام يجلب بيانات المشترك من Radius تلقائياً ويحفظها مع الطلب.

### الحقول

#### جدول `maintenance_requests`

-   `user_id`: المستخدم الذي طلب الصيانة
-   `address` (required): عنوان الصيانة
-   `subscription_data` (JSON): بيانات المشترك من الراديوس
-   `description` (optional): وصف المشكلة
-   `status`: حالة الطلب
    -   `pending`: قيد الانتظار
    -   `submitted`: تم التقديم
    -   `in_progress`: قيد التنفيذ
    -   `completed`: مكتمل
    -   `cancelled`: ملغي
-   `assigned_to`: المسؤول المكلف
-   `notes`: ملاحظات من المسؤول
-   `completed_at`: تاريخ الإكمال

### API Routes

#### 1. إنشاء طلب صيانة (للمستخدمين)

```http
POST /api/maintenance-requests
Authorization: Bearer {user_token}
Content-Type: application/json
```

**المعاملات المطلوبة**:

```json
{
    "address": "بغداد - الكرادة - شارع الكرادة الداخلية - عمارة رقم 15",
    "description": "مشكلة في الاتصال بالإنترنت"
}
```

**المعاملات**:

-   `address` (required, min: 10, max: 500): عنوان الصيانة
-   `description` (optional, max: 1000): وصف المشكلة

**الاستجابة**:

```json
{
    "success": true,
    "message": "تم إرسال طلب الصيانة بنجاح",
    "data": {
        "id": 1,
        "user": {...},
        "address": "بغداد - الكرادة...",
        "description": "مشكلة في الاتصال بالإنترنت",
        "subscription_data": {
            "expiration_at": "2026-01-02 10:00:00",
            "balance": 8.0,
            "plan_name": "2M-PPP",
            "is_active_radius": true,
            ...
        },
        "status": "pending",
        "status_label": "قيد الانتظار",
        "created_at": "2025-12-12T10:00:00Z"
    }
}
```

**ملاحظات**:

-   يتم جلب بيانات المشترك من Radius تلقائياً
-   إذا فشل جلب البيانات، يتم إرجاع خطأ

#### 2. عرض طلبات الصيانة (للمستخدمين)

```http
GET /api/maintenance-requests
Authorization: Bearer {user_token}
```

**المعاملات الاختيارية**:

-   `status`: فلترة حسب الحالة (`pending`, `submitted`, `in_progress`, `completed`, `cancelled`)

#### 3. عرض طلب صيانة محدد

```http
GET /api/maintenance-requests/{id}
Authorization: Bearer {user_token}
```

#### 4. عرض جميع طلبات الصيانة (Admin/Accountant فقط)

```http
GET /api/admin/maintenance-requests
Authorization: Bearer {admin_token}
```

**المعاملات الاختيارية**:

-   `status`: فلترة حسب الحالة
-   `user_id`: فلترة حسب المستخدم

#### 5. تحديث حالة طلب الصيانة (Admin/Accountant فقط)

```http
PUT /api/admin/maintenance-requests/{id}/status
Authorization: Bearer {admin_token}
Content-Type: application/json
```

**المعاملات**:

```json
{
    "status": "submitted",
    "assigned_to": 2,
    "notes": "تم استلام الطلب وسيتم المتابعة"
}
```

**المعاملات**:

-   `status` (required): الحالة الجديدة
-   `assigned_to` (optional): المسؤول المكلف
-   `notes` (optional): ملاحظات

**ملاحظات**:

-   عند تغيير الحالة إلى `completed`، يتم تعيين `completed_at` تلقائياً

### أمثلة الاستخدام

#### مثال 1: إنشاء طلب صيانة

```bash
curl -X POST "http://localhost/api/maintenance-requests" \
  -H "Authorization: Bearer {user_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "address": "بغداد - الكرادة - شارع الكرادة الداخلية - عمارة رقم 15",
    "description": "مشكلة في الاتصال بالإنترنت - السرعة بطيئة جداً"
  }'
```

#### مثال 2: تحديث حالة الطلب

```bash
curl -X PUT "http://localhost/api/admin/maintenance-requests/1/status" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "in_progress",
    "assigned_to": 2,
    "notes": "تم إرسال فريق الصيانة"
  }'
```

---

## 4. ميزة تجديد الاشتراك التلقائي

### الوصف

عند قبول طلب دفع (أو إضافة دفعة نقدية)، يتم تجديد الاشتراك تلقائياً في Radius API بناءً على عدد الأشهر المحدد.

### آلية العمل

1. عند قبول الدفعة:

    - يتم إرسال طلب تجديد إلى Radius API
    - يتم تحديد عدد الأشهر (`period_months`)
    - يمكن تحديد اسم الخدمة (`plan_name`) اختياري

2. بعد نجاح التجديد:

    - يتم مزامنة بيانات الاشتراك من Radius
    - يتم تحديث قاعدة البيانات المحلية

3. في حال فشل الاتصال:
    - يتم تحديث قاعدة البيانات المحلية فقط
    - يتم تسجيل تحذير في Logs

### الحقول الجديدة

#### جدول `payment_requests`

-   `plan_name` (string, nullable): اسم الخدمة/الباقة للتجديد

### API Routes

#### 1. قبول طلب دفع مع تحديد عدد الأشهر والخدمة

```http
PUT /api/admin/payment-requests/{id}/status
Authorization: Bearer {admin_token}
Content-Type: application/json
```

**المعاملات**:

```json
{
    "status": 1,
    "period_months": 3,
    "plan_name": "2M-PPP",
    "approved_amount": 50000,
    "notes": "تم قبول الدفعة"
}
```

**المعاملات**:

-   `status` (required): `1` (مقبول) أو `2` (مرفوض)
-   `period_months` (required if status=1): عدد أشهر التجديد (1-12)
-   `plan_name` (optional): اسم الخدمة/الباقة
-   `approved_amount` (optional): المبلغ المقبول (إذا كان مختلفاً عن المبلغ المطلوب)
-   `notes` (optional): ملاحظات

**الاستجابة**:

```json
{
    "success": true,
    "message": "تم قبول الطلب بنجاح",
    "data": {
        "id": 1,
        "status": 1,
        "status_label": "مقبول",
        "period_months": 3,
        "plan_name": "2M-PPP",
        "approved_amount": 50000,
        ...
    }
}
```

**ما يحدث تلقائياً**:

1. يتم إرسال طلب تجديد إلى Radius API
2. يتم تجديد الاشتراك في Radius
3. يتم مزامنة بيانات الاشتراك
4. يتم إضافة الإيراد (انظر القسم التالي)

#### 2. إضافة دفعة نقدية (مع تجديد تلقائي)

```http
POST /api/admin/payment-requests/cash-payment
Authorization: Bearer {accountant_token}
Content-Type: application/json
```

**المعاملات**: (انظر القسم 2 - ميزة الدفعات النقدية)

**ملاحظات**:

-   الدفعات النقدية تُقبل تلقائياً
-   يتم تجديد الاشتراك تلقائياً
-   `period_months` إجباري

### Endpoints المحتملة في Radius

النظام يحاول الاتصال بـ endpoints التالية بالترتيب:

1. `/radiusmanager/USERS/renew.php`
2. `/radiusmanager/USERS/extend.php`
3. `/radiusmanager/USERS/update.php`
4. `/radiusmanager/USERS/dash/renew.php`

إذا فشلت جميع المحاولات، يحاول تحديث تاريخ الانتهاء مباشرة.

### أمثلة الاستخدام

#### مثال 1: قبول طلب دفع مع تجديد 3 أشهر

```bash
curl -X PUT "http://localhost/api/admin/payment-requests/1/status" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "status": 1,
    "period_months": 3,
    "plan_name": "2M-PPP",
    "approved_amount": 50000
  }'
```

#### مثال 2: إضافة دفعة نقدية مع تجديد 2 أشهر

```bash
curl -X POST "http://localhost/api/admin/payment-requests/cash-payment" \
  -H "Authorization: Bearer {accountant_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 5,
    "amount": 50000,
    "period_months": 2,
    "plan_name": "2M-PPP"
  }'
```

---

## 5. ميزة الإيرادات والصندوق

### الوصف

عند قبول أي دفعة (نقدية أو عبر الإنترنت)، يتم إضافة المبلغ المقبول تلقائياً إلى جدول الإيرادات.

### الحقول

#### جدول `revenues`

-   `payment_request_id`: ربط بطلب الدفع
-   `user_id`: المستخدم
-   `amount`: المبلغ المقبول
-   `currency`: العملة
-   `period_months`: عدد أشهر التجديد
-   `payment_type`: نوع الدفع (`online` أو `cash`)
-   `payment_date`: تاريخ الدفع
-   `notes`: ملاحظات

### آلية العمل

عند قبول الدفعة:

1. يتم إنشاء سجل في جدول `revenues`
2. يتم ربطه بطلب الدفع
3. يتم حفظ جميع التفاصيل (المبلغ، عدد الأشهر، نوع الدفع، إلخ)

### العلاقات

-   `Revenue` belongs to `PaymentRequest`
-   `Revenue` belongs to `AppUser`
-   `PaymentRequest` has one `Revenue`

### الاستعلامات المفيدة

#### 1. إجمالي الإيرادات

```sql
SELECT
    SUM(amount) as total_revenue,
    currency,
    payment_type
FROM revenues
GROUP BY currency, payment_type;
```

#### 2. الإيرادات حسب التاريخ

```sql
SELECT
    DATE(payment_date) as date,
    SUM(amount) as daily_revenue,
    COUNT(*) as transactions_count
FROM revenues
WHERE payment_date >= '2025-12-01'
GROUP BY DATE(payment_date)
ORDER BY date DESC;
```

#### 3. الإيرادات حسب المستخدم

```sql
SELECT
    u.username,
    u.phone,
    SUM(r.amount) as total_paid,
    COUNT(r.id) as payments_count
FROM revenues r
JOIN app_users u ON r.user_id = u.id
GROUP BY u.id, u.username, u.phone
ORDER BY total_paid DESC;
```

#### 4. الإيرادات النقدية vs عبر الإنترنت

```sql
SELECT
    payment_type,
    COUNT(*) as count,
    SUM(amount) as total
FROM revenues
GROUP BY payment_type;
```

### أمثلة الاستخدام

#### مثال: عرض الإيرادات (يمكن إضافة API endpoint لاحقاً)

```php
// في Controller
public function getRevenues(Request $request)
{
    $query = Revenue::with(['user', 'paymentRequest'])
        ->orderBy('payment_date', 'desc');

    if ($request->has('from_date')) {
        $query->where('payment_date', '>=', $request->from_date);
    }

    if ($request->has('to_date')) {
        $query->where('payment_date', '<=', $request->to_date);
    }

    if ($request->has('payment_type')) {
        $query->where('payment_type', $request->payment_type);
    }

    return response()->json([
        'success' => true,
        'data' => $query->paginate(15),
        'summary' => [
            'total_revenue' => $query->sum('amount'),
            'total_transactions' => $query->count(),
        ]
    ]);
}
```

---

## 6. ميزة الدفع المؤجل - التفاصيل الكاملة

### الوصف

تم إضافة ميزة الدفع المؤجل التي تسمح للمحاسب بإضافة دفعة نقدية مع تجديد الاشتراك، ولكن الدفعة غير مدفوعة بعد. عندما يدفع المستخدم لاحقاً، يتم تحديث حالة الدفعة وإضافة الإيراد.

### آلية العمل

#### 1. عند إضافة دفعة مؤجلة:

-   ✅ يتم تجديد الاشتراك في Radius تلقائياً
-   ✅ يتم تحديث `expiration_at` في قاعدة البيانات
-   ❌ لا يتم إضافة الإيراد (سيتم إضافته عند الدفع)
-   ✅ `is_deferred = true`, `is_paid = false`

#### 2. عند الدفع لاحقاً:

-   ✅ يتم تحديث `is_paid = true`
-   ✅ يتم تعيين `paid_at` بتاريخ الدفع
-   ✅ يتم إضافة الإيراد إلى جدول `revenues`

### API Routes

#### 1. إضافة دفعة مؤجلة

```http
POST /api/admin/payment-requests/cash-payment
Authorization: Bearer {accountant_token}
Content-Type: application/json
```

**المعاملات**:

```json
{
    "user_id": 5,
    "amount": 50000,
    "period_months": 3,
    "plan_name": "2M-PPP",
    "is_deferred": true,
    "notes": "دفع مؤجل - سيتم الدفع لاحقاً"
}
```

#### 2. تحديث دفعة مؤجلة إلى مدفوعة

```http
POST /api/admin/payment-requests/{id}/mark-as-paid
Authorization: Bearer {accountant_token}
```

**الاستجابة**:

```json
{
    "success": true,
    "message": "تم تحديث حالة الدفعة إلى مدفوع بنجاح. تم إضافة الإيراد",
    "data": {
        "id": 10,
        "is_paid": true,
        "is_deferred": true,
        "paid_at": "2025-12-12T15:30:00Z",
        "revenue": {...}
    }
}
```

#### 3. فلترة الدفعات المؤجلة

```http
GET /api/admin/payment-requests?is_deferred=true&is_paid=false
Authorization: Bearer {accountant_token}
```

**الاستجابة**: تعرض جميع الدفعات المؤجلة غير المدفوعة

### حالات الاستخدام

#### السيناريو 1: تجديد مؤجل

1. المستخدم يطلب تجديد ولكن لا يملك المال حالياً
2. المحاسب يضيف دفعة مؤجلة (`is_deferred = true`)
3. يتم تجديد الاشتراك فوراً
4. المستخدم يدفع لاحقاً
5. المحاسب يحدّث الدفعة إلى مدفوعة

#### السيناريو 2: متابعة الدفعات المؤجلة

1. المحاسب يعرض قائمة الدفعات المؤجلة: `GET /api/admin/payment-requests?is_deferred=true&is_paid=false`
2. يرى جميع الدفعات التي لم يتم دفعها بعد
3. عندما يدفع المستخدم، يحدّث الحالة

### أمثلة شاملة

#### مثال كامل: من الدفع المؤجل إلى الدفع الفعلي

**الخطوة 1: إضافة دفعة مؤجلة**

```bash
curl -X POST "http://localhost/api/admin/payment-requests/cash-payment" \
  -H "Authorization: Bearer {accountant_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 5,
    "amount": 50000,
    "period_months": 2,
    "is_deferred": true,
    "notes": "دفع مؤجل"
  }'
```

**النتيجة**:

-   ✅ الاشتراك تم تجديده (2 أشهر)
-   ❌ الإيراد لم يُضف بعد
-   ✅ الدفعة تظهر في قائمة المؤجلة

**الخطوة 2: عرض الدفعات المؤجلة**

```bash
curl -X GET "http://localhost/api/admin/payment-requests?is_deferred=true&is_paid=false" \
  -H "Authorization: Bearer {accountant_token}"
```

**الخطوة 3: تحديث إلى مدفوعة**

```bash
curl -X POST "http://localhost/api/admin/payment-requests/10/mark-as-paid" \
  -H "Authorization: Bearer {accountant_token}"
```

**النتيجة**:

-   ✅ `is_paid = true`
-   ✅ تم إضافة الإيراد
-   ✅ الدفعة لم تعد تظهر في قائمة المؤجلة

---

## 7. ملخص جميع Routes الجديدة

### البث المباشر

-   `POST /api/admin/live-streams` - إنشاء بث (مع `access_type`)
-   `PUT /api/admin/live-streams/{id}` - تحديث بث (مع `access_type`)
-   `GET /api/live-streams` - عرض البث (مع فلترة حسب `access_type`)

### الدفعات

-   `POST /api/admin/payment-requests/cash-payment` - إضافة دفعة نقدية (مع دعم الدفع المؤجل)
-   `PUT /api/admin/payment-requests/{id}/status` - قبول/رفض دفعة (مع `period_months` و `plan_name`)
-   `POST /api/admin/payment-requests/{id}/mark-as-paid` - تحديث دفعة مؤجلة إلى مدفوعة
-   `GET /api/admin/payment-requests?is_deferred=true&is_paid=false` - فلترة الدفعات المؤجلة

### طلبات الصيانة

-   `POST /api/maintenance-requests` - إنشاء طلب صيانة
-   `GET /api/maintenance-requests` - عرض طلبات المستخدم
-   `GET /api/maintenance-requests/{id}` - عرض طلب محدد
-   `GET /api/admin/maintenance-requests` - عرض جميع الطلبات (للمسؤولين)
-   `PUT /api/admin/maintenance-requests/{id}/status` - تحديث حالة الطلب

### الإعدادات وروابط التواصل

-   `GET /api/app-settings` - عرض جميع الإعدادات النشطة (للمستخدمين)
-   `GET /api/app-settings/key/{key}` - عرض إعداد محدد بالمفتاح
-   `GET /api/admin/app-settings` - عرض جميع الإعدادات (Admin فقط)
-   `GET /api/admin/app-settings/{id}` - عرض إعداد محدد (Admin فقط)
-   `PUT /api/admin/app-settings/{id}` - تحديث إعداد (Admin فقط)
-   `POST /api/admin/app-settings/update-multiple` - تحديث عدة إعدادات (Admin فقط)
-   `DELETE /api/admin/app-settings/{id}` - حذف إعداد (Admin فقط)

---

## 8. Migrations المطلوبة

### تشغيل جميع Migrations

```bash
php artisan migrate
```

### Migrations الجديدة

1. `2025_12_12_175443_add_access_type_to_live_streams_table.php`

    - إضافة `access_type` إلى `live_streams`

2. `2025_12_12_175456_add_payment_type_and_created_by_to_payment_requests_table.php`

    - إضافة `payment_type` و `created_by` إلى `payment_requests`

3. `2025_12_12_181222_create_maintenance_requests_table.php`

    - إنشاء جدول `maintenance_requests`

4. `2025_12_12_182839_create_revenues_table.php`

    - إنشاء جدول `revenues`

5. `2025_12_12_184427_add_plan_name_to_payment_requests_table.php`

    - إضافة `plan_name` إلى `payment_requests`

6. `2025_12_12_190257_add_payment_status_to_payment_requests_table.php`

    - إضافة `is_paid`, `is_deferred`, `paid_at` إلى `payment_requests`

7. `2025_12_12_192606_create_app_settings_table.php`
    - إنشاء جدول `app_settings` للإعدادات وروابط التواصل

---

## 9. الصلاحيات

### المستخدمون العاديون (User)

-   ✅ إنشاء طلب صيانة
-   ✅ عرض طلبات الصيانة الخاصة بهم
-   ✅ عرض البث المباشر (حسب `access_type`)
-   ✅ إنشاء طلب دفع
-   ✅ عرض دفعاتهم

### المحاسبون (Accountant)

-   ✅ جميع صلاحيات المستخدمين
-   ✅ إضافة دفعات نقدية
-   ✅ قبول/رفض طلبات الدفع
-   ✅ عرض جميع طلبات الصيانة
-   ✅ تحديث حالة طلبات الصيانة

### المديرون (Admin)

-   ✅ جميع صلاحيات المحاسبين
-   ✅ إدارة البث المباشر (إنشاء، تحديث، حذف)
-   ✅ حذف المستخدمين
-   ✅ جميع الصلاحيات الأخرى

---

## 10. أمثلة شاملة

### سيناريو كامل: من طلب الدفع إلى التجديد

#### الخطوة 1: المستخدم يطلب دفع

```bash
curl -X POST "http://localhost/api/payment-requests" \
  -H "Authorization: Bearer {user_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 50000,
    "period_months": 3,
    "payment_method_id": 1,
    "transaction_number": "TXN123456"
  }'
```

#### الخطوة 2: المحاسب يقبل الطلب

```bash
curl -X PUT "http://localhost/api/admin/payment-requests/1/status" \
  -H "Authorization: Bearer {accountant_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "status": 1,
    "period_months": 3,
    "plan_name": "2M-PPP",
    "approved_amount": 50000,
    "notes": "تم قبول الدفعة"
  }'
```

**ما يحدث تلقائياً**:

1. ✅ يتم إرسال طلب تجديد إلى Radius API
2. ✅ يتم تجديد الاشتراك في Radius (3 أشهر)
3. ✅ يتم مزامنة بيانات الاشتراك
4. ✅ يتم إضافة الإيراد إلى جدول `revenues`

#### الخطوة 3: التحقق من النتيجة

```bash
# عرض طلب الدفع
curl -X GET "http://localhost/api/payment-requests/1" \
  -H "Authorization: Bearer {user_token}"

# عرض الاشتراك (يجب أن يكون محدث)
curl -X GET "http://localhost/api/user/profile" \
  -H "Authorization: Bearer {user_token}"
```

---

## 11. استكشاف الأخطاء

### مشكلة: فشل تجديد الاشتراك في Radius

**الأسباب المحتملة**:

1. Radius API غير متاح
2. Endpoint غير صحيح
3. بيانات المصادقة خاطئة

**الحل**:

-   تحقق من Logs: `storage/logs/laravel.log`
-   تحقق من إعدادات Radius في `.env`:
    ```
    RADIUS_API_URL=http://your-radius-server:port
    RADIUS_API_KEY=your-api-key
    ```
-   النظام سيحاول تحديث قاعدة البيانات المحلية كبديل

### مشكلة: البث لا يظهر للمستخدم

**الأسباب المحتملة**:

1. `access_type = "live_subscribers_only"` والمستخدم ليس لديه `live_access`
2. المستخدم ليس لديه اشتراك نشط
3. البث غير نشط (`is_active = false`)

**الحل**:

-   تحقق من `access_type` للبث
-   تحقق من `live_access` للمستخدم
-   تحقق من حالة الاشتراك (`expiration_at`)

### مشكلة: فشل جلب بيانات Radius في طلب الصيانة

**الأسباب المحتملة**:

1. Radius API غير متاح
2. Username غير موجود في Radius
3. مشكلة في الاتصال

**الحل**:

-   تحقق من Logs
-   تأكد من أن `username` صحيح
-   حاول مرة أخرى

---

## 12. ملاحظات مهمة

1. **تجديد الاشتراك**: يتم في Radius أولاً، ثم يتم مزامنة البيانات (حتى للدفعات المؤجلة)
2. **الإيرادات**: تُضاف تلقائياً عند قبول أي دفعة مدفوعة فقط (ليس للدفعات المؤجلة)
3. **الدفعات النقدية**: تُقبل تلقائياً ولا تحتاج موافقة
4. **الدفعات المؤجلة**: تجدد الاشتراك ولكن لا تضيف إيراد حتى يتم الدفع
5. **طلبات الصيانة**: تجلب بيانات Radius تلقائياً عند الإنشاء
6. **البث المباشر**: الفلترة حسب `access_type` تلقائية

---

## 13. التطوير المستقبلي

### اقتراحات للتحسين

1. **API للإيرادات**:

    - `GET /api/admin/revenues` - عرض جميع الإيرادات
    - `GET /api/admin/revenues/summary` - ملخص الإيرادات
    - `GET /api/admin/revenues/export` - تصدير الإيرادات

2. **إشعارات**:

    - إشعار عند قبول الدفعة
    - إشعار عند تحديث حالة طلب الصيانة

3. **تقارير**:
    - تقرير الإيرادات اليومية/الشهرية
    - تقرير طلبات الصيانة

---

## 14. الدعم والمساعدة

للمزيد من المعلومات:

-   راجع ملفات التوثيق الأخرى في المشروع
-   تحقق من Logs في `storage/logs/laravel.log`
-   راجع كود المصدر في `app/Services/` و `app/Http/Controllers/`

---

**تم إنشاء هذا الدليل بتاريخ**: 2025-12-12  
**آخر تحديث**: 2025-12-12
