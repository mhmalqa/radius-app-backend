# 🗄️ دليل ربط مراقبة عمليات قاعدة البيانات بلوحة التحكم

## 🔐 المصادقة

جميع الـ Endpoints تتطلب:

```
Authorization: Bearer {token}
```

**الصلاحيات**: المدير فقط (Admin)

---

## 📋 نظرة عامة

نظام مراقبة عمليات قاعدة البيانات يتيح للمدير مراقبة جميع الحركات والعمليات التي تحدث في التطبيق، مثل:
- تسجيلات المستخدمين الجدد
- طلبات الدفع
- الإيرادات
- وغيرها من العمليات المهمة

---

## 🛠️ API Endpoints

### 1. عرض أحدث العمليات

**Method**: `GET`  
**URL**: `/api/admin/database/operations/latest`  
**Headers**:

```
Authorization: Bearer {token}
```

**Query Parameters** (اختياري):

| المعامل | النوع   | الوصف                                    | افتراضي |
| ------- | ------- | ---------------------------------------- | ------- |
| `limit` | integer | عدد العمليات المراد عرضها                | 50      |

**مثال**: `GET /api/admin/database/operations/latest?limit=100`

**الاستجابة**:

```json
{
    "success": true,
    "data": [
        {
            "id": 123,
            "table": "payment_requests",
            "type": "insert",
            "data": {
                "user_id": 45,
                "amount": 50000,
                "status": 0,
                "payment_type": "cash"
            },
            "timestamp": "2025-12-15 10:30:00"
        },
        {
            "id": 456,
            "table": "app_users",
            "type": "insert",
            "data": {
                "username": "user001",
                "role": 0,
                "is_active": true
            },
            "timestamp": "2025-12-15 09:15:00"
        },
        {
            "id": 789,
            "table": "revenues",
            "type": "insert",
            "data": {
                "user_id": 45,
                "amount": 50000,
                "payment_type": "cash",
                "payment_date": "2025-12-15"
            },
            "timestamp": "2025-12-15 08:00:00"
        }
    ],
    "meta": {
        "total": 50,
        "limit": 50
    }
}
```

---

### 2. عرض جميع العمليات مع Pagination والفلترة

**Method**: `GET`  
**URL**: `/api/admin/database/operations/all`  
**Headers**:

```
Authorization: Bearer {token}
```

**Query Parameters** (جميعها اختيارية):

| المعامل    | النوع   | الوصف                                    | مثال                    |
| ---------- | ------- | ---------------------------------------- | ----------------------- |
| `per_page` | integer | عدد العمليات في كل صفحة                  | 50                      |
| `page`     | integer | رقم الصفحة                               | 1                       |
| `table`    | string  | تصفية حسب الجدول                          | `payment_requests`      |
| `from_date`| string  | تاريخ البداية (Y-m-d H:i:s)              | `2025-12-01 00:00:00`   |
| `to_date`  | string  | تاريخ النهاية (Y-m-d H:i:s)              | `2025-12-31 23:59:59`   |

**قيم `table` المتاحة**:
- `payment_requests` - طلبات الدفع
- `revenues` - الإيرادات
- `app_users` - المستخدمين

**مثال**: `GET /api/admin/database/operations/all?per_page=25&page=1&table=payment_requests&from_date=2025-12-01&to_date=2025-12-31`

**الاستجابة**:

```json
{
    "success": true,
    "data": [
        {
            "id": 123,
            "table": "payment_requests",
            "type": "insert",
            "data": {
                "user_id": 45,
                "amount": 50000,
                "status": 0,
                "payment_type": "cash"
            },
            "timestamp": "2025-12-15 10:30:00"
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 25,
        "total": 150,
        "last_page": 6
    }
}
```

---

### 3. فحص أمان قاعدة البيانات

**Method**: `GET`  
**URL**: `/api/admin/database/security-check`  
**Headers**:

```
Authorization: Bearer {token}
```

**الاستجابة**:

```json
{
    "success": true,
    "data": {
        "checks": {
            "database_connection": {
                "status": "success",
                "message": "الاتصال بقاعدة البيانات ناجح"
            },
            "table_app_users": {
                "status": "success",
                "message": "جدول app_users موجود"
            },
            "table_payment_requests": {
                "status": "success",
                "message": "جدول payment_requests موجود"
            },
            "users_without_password": {
                "status": "success",
                "message": "جميع المستخدمين لديهم كلمات مرور",
                "count": 0
            },
            "active_admins": {
                "status": "success",
                "message": "يوجد 2 مدير نشط",
                "count": 2
            },
            "database_size": {
                "status": "success",
                "message": "حجم قاعدة البيانات: 125.50 MB",
                "size_mb": 125.5
            }
        },
        "overall_status": "success",
        "timestamp": "2025-12-15T10:30:00Z"
    }
}
```

---

## 📊 هيكل البيانات

### كائن العملية (Operation Object):

| الحقل      | النوع   | الوصف                                    |
| ---------- | ------- | ---------------------------------------- |
| `id`       | integer | معرف العملية (ID من الجدول)              |
| `table`    | string  | اسم الجدول                                |
| `type`     | string  | نوع العملية (حالياً: `insert` فقط)        |
| `data`     | object  | بيانات العملية (يختلف حسب الجدول)        |
| `timestamp`| string  | تاريخ ووقت العملية (Y-m-d H:i:s)          |

### بيانات العملية حسب الجدول:

#### 1. `payment_requests` (طلبات الدفع):

```json
{
    "user_id": 45,
    "amount": 50000,
    "status": 0,
    "payment_type": "cash"
}
```

**حالات `status`**:
- `0` - قيد الانتظار
- `1` - مقبول
- `2` - مرفوض

**أنواع `payment_type`**:
- `cash` - نقدي
- `bank_transfer` - تحويل بنكي
- `credit_card` - بطاقة ائتمان

#### 2. `revenues` (الإيرادات):

```json
{
    "user_id": 45,
    "amount": 50000,
    "payment_type": "cash",
    "payment_date": "2025-12-15"
}
```

#### 3. `app_users` (المستخدمين):

```json
{
    "username": "user001",
    "role": 0,
    "is_active": true
}
```

**قيم `role`**:
- `0` - مستخدم عادي
- `1` - محاسب
- `2` - مدير

---

## 🔍 الفلترة والبحث

### 1. الفلترة حسب الجدول:

```
GET /api/admin/database/operations/all?table=payment_requests
```

### 2. الفلترة حسب التاريخ:

```
GET /api/admin/database/operations/all?from_date=2025-12-01&to_date=2025-12-31
```

### 3. الفلترة المركبة:

```
GET /api/admin/database/operations/all?table=payment_requests&from_date=2025-12-01&to_date=2025-12-31&per_page=25&page=1
```

---

## 👤 الحصول على معلومات المستخدم

للعثور على معلومات المستخدم من `user_id`، استخدم:

```
GET /api/admin/users/{user_id}
Authorization: Bearer {token}
```

**الاستجابة**:

```json
{
    "success": true,
    "data": {
        "id": 45,
        "username": "user001",
        "firstname": "أحمد",
        "phone": "07501234567",
        "email": "user@example.com",
        "role": 0,
        "is_active": true,
        "live_access": false
    }
}
```

---

## 📝 ملاحظات مهمة

### 1. أنواع العمليات:

حالياً، النظام يتتبع فقط عمليات `insert` (الإضافة). قد يتم إضافة أنواع أخرى لاحقاً:
- `update` - التحديث
- `delete` - الحذف

### 2. الجداول المتتبعة:

- `payment_requests` - طلبات الدفع
- `revenues` - الإيرادات
- `app_users` - المستخدمين

### 3. الترتيب:

- جميع العمليات مرتبة حسب `timestamp` (من الأحدث إلى الأقدم)

### 4. Pagination:

- عند استخدام `/all`، يتم تطبيق Pagination تلقائياً
- الحد الأقصى لـ `per_page`: غير محدد (لكن يُنصح بـ 50-100)

### 5. الأداء:

- `/latest` أسرع من `/all` لأنه يعرض عدد محدود فقط
- استخدم `/latest` للعرض السريع
- استخدم `/all` للبحث والفلترة المتقدمة

---

## 🔍 أمثلة على الاستجابات

### نجاح (200):

```json
{
    "success": true,
    "data": [...],
    "meta": {...}
}
```

### خطأ (403 - Unauthorized):

```json
{
    "success": false,
    "message": "This action is unauthorized."
}
```

### خطأ (500 - Server Error):

```json
{
    "success": false,
    "message": "فشل في جلب العمليات",
    "error": "Error message here"
}
```

---

## 📱 أمثلة في Frontend

### عرض أحدث العمليات:

```javascript
fetch("http://domain.com/api/admin/database/operations/latest?limit=50", {
    method: "GET",
    headers: {
        Authorization: `Bearer ${token}`,
    },
})
    .then((response) => response.json())
    .then((data) => {
        console.log("العمليات:", data.data);
        console.log("الإجمالي:", data.meta.total);
    });
```

### عرض جميع العمليات مع فلترة:

```javascript
const params = new URLSearchParams({
    per_page: 25,
    page: 1,
    table: "payment_requests",
    from_date: "2025-12-01 00:00:00",
    to_date: "2025-12-31 23:59:59",
});

fetch(
    `http://domain.com/api/admin/database/operations/all?${params}`,
    {
        method: "GET",
        headers: {
            Authorization: `Bearer ${token}`,
        },
    }
)
    .then((response) => response.json())
    .then((data) => {
        console.log("العمليات:", data.data);
        console.log("الصفحة الحالية:", data.meta.current_page);
        console.log("إجمالي الصفحات:", data.meta.last_page);
    });
```

### الحصول على معلومات المستخدم:

```javascript
// بعد الحصول على user_id من العملية
const userId = operation.data.user_id;

fetch(`http://domain.com/api/admin/users/${userId}`, {
    method: "GET",
    headers: {
        Authorization: `Bearer ${token}`,
    },
})
    .then((response) => response.json())
    .then((data) => {
        console.log("معلومات المستخدم:", data.data);
    });
```

### عرض العملية مع معلومات المستخدم:

```javascript
async function getOperationWithUser(operation) {
    // الحصول على معلومات المستخدم إذا كان موجوداً
    if (operation.data.user_id) {
        const userResponse = await fetch(
            `http://domain.com/api/admin/users/${operation.data.user_id}`,
            {
                headers: {
                    Authorization: `Bearer ${token}`,
                },
            }
        );
        const userData = await userResponse.json();
        return {
            ...operation,
            user: userData.data,
        };
    }
    return operation;
}

// استخدام
const operations = await fetchOperations();
const operationsWithUsers = await Promise.all(
    operations.map((op) => getOperationWithUser(op))
);
```

---

## 🎯 حالات الاستخدام

### 1. لوحة المراقبة (Dashboard):

- عرض آخر 50 عملية في الوقت الفعلي
- استخدام: `/latest?limit=50`

### 2. صفحة العمليات الكاملة:

- عرض جميع العمليات مع Pagination
- استخدام: `/all?per_page=25&page=1`

### 3. تقارير مخصصة:

- فلترة حسب الجدول والتاريخ
- استخدام: `/all?table=payment_requests&from_date=...&to_date=...`

### 4. مراقبة الأمان:

- فحص حالة قاعدة البيانات
- استخدام: `/security-check`

---

## 📊 مثال على عرض العملية في الجدول

```html
<table>
    <thead>
        <tr>
            <th>التاريخ</th>
            <th>الجدول</th>
            <th>المستخدم</th>
            <th>الحدث</th>
            <th>التفاصيل</th>
        </tr>
    </thead>
    <tbody>
        <tr v-for="operation in operations" :key="operation.id">
            <td>{{ formatDate(operation.timestamp) }}</td>
            <td>{{ getTableLabel(operation.table) }}</td>
            <td>
                <span v-if="operation.data.user_id">
                    {{ getUserName(operation.data.user_id) }}
                </span>
                <span v-else>-</span>
            </td>
            <td>{{ getEventLabel(operation) }}</td>
            <td>{{ getDetails(operation) }}</td>
        </tr>
    </tbody>
</table>
```

---

## 🔄 تحديثات تلقائية

للتحديث التلقائي للعمليات في الوقت الفعلي:

```javascript
// تحديث كل 30 ثانية
setInterval(() => {
    fetchLatestOperations();
}, 30000);
```

---

## ✅ نصائح للربط

1. **للـ Token**: استخدم `Bearer Token` في Authorization header
2. **للأداء**: استخدم `/latest` للعرض السريع، `/all` للبحث المتقدم
3. **للPagination**: استخدم `per_page` و `page` للتنقل بين الصفحات
4. **للفلترة**: استخدم `table`, `from_date`, `to_date` للبحث المخصص
5. **للمستخدمين**: احصل على معلومات المستخدم من `/admin/users/{id}` عند الحاجة

