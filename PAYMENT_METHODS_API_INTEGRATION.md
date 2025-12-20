# 💳 دليل ربط إدارة طرق الدفع بلوحة التحكم

## 🔐 المصادقة

جميع الـ Endpoints تتطلب:

```
Authorization: Bearer {token}
```

---

## 📋 الحقول وأنواع البيانات

### الحقول المطلوبة عند الإنشاء:

| الحقل     | النوع            | الوصف                       |
| --------- | ---------------- | --------------------------- |
| `name`    | string (max: 50) | اسم طريقة الدفع بالإنجليزية |
| `name_ar` | string (max: 50) | اسم طريقة الدفع بالعربية    |

### الحقول الاختيارية:

| الحقل          | النوع             | الوصف                                                     |
| -------------- | ----------------- | --------------------------------------------------------- |
| `icon`         | file (image)      | أيقونة طريقة الدفع (jpg, jpeg, png, webp, svg - max: 2MB) |
| `qr_code`      | file (image)      | صورة QR Code (jpg, jpeg, png, webp - max: 2MB)            |
| `code`         | string (max: 100) | كود طريقة الدفع                                           |
| `is_active`    | boolean           | حالة التفعيل (true/false)                                 |
| `instructions` | string            | تعليمات طريقة الدفع                                       |
| `sort_order`   | integer           | ترتيب العرض                                               |

---

## 🛠️ API Endpoints

### 1. عرض جميع طرق الدفع (للمستخدمين)

**Method**: `GET`  
**URL**: `/api/payment-methods`  
**Headers**: لا يتطلب مصادقة

**الاستجابة**:

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Credit Card",
            "name_ar": "بطاقة ائتمان",
            "icon": "http://domain.com/storage/payment_methods/icon.jpg",
            "qr_code": "http://domain.com/storage/payment_methods/qr.jpg",
            "code": "credit_card",
            "is_active": true,
            "instructions": "تعليمات الدفع",
            "sort_order": 1
        }
    ]
}
```

---

### 2. عرض جميع طرق الدفع (للمدير - مع غير النشطة)

**Method**: `GET`  
**URL**: `/api/admin/payment-methods`  
**Headers**:

```
Authorization: Bearer {token}
```

**ملاحظة**: هذا الـ endpoint غير موجود حالياً، يمكن استخدام نفس endpoint المستخدمين أو إضافته لاحقاً.

---

### 3. إنشاء طريقة دفع جديدة

**Method**: `POST`  
**URL**: `/api/admin/payment-methods`  
**Headers**:

```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Body** (form-data):

-   `name`: `"Credit Card"` (text - required)
-   `name_ar`: `"بطاقة ائتمان"` (text - required)
-   `icon`: [اختر ملف صورة] (file - optional)
-   `qr_code`: [اختر ملف صورة] (file - optional)
-   `code`: `"credit_card"` (text - optional)
-   `is_active`: `true` (text - optional)
-   `instructions`: `"تعليمات الدفع"` (text - optional)
-   `sort_order`: `1` (text - optional)

**الاستجابة**:

```json
{
    "success": true,
    "message": "تم إنشاء طريقة الدفع بنجاح",
    "data": {
        "id": 1,
        "name": "Credit Card",
        "name_ar": "بطاقة ائتمان",
        "icon": "http://domain.com/storage/payment_methods/icon.jpg",
        "qr_code": "http://domain.com/storage/payment_methods/qr.jpg",
        "code": "credit_card",
        "is_active": true,
        "instructions": "تعليمات الدفع",
        "sort_order": 1
    }
}
```

---

### 4. تحديث طريقة دفع

**⚠️ مهم**: للتحديث مع form-data (الصور)، استخدم **POST** وليس PUT

**Method**: `POST` (لـ form-data) أو `PUT` (لـ JSON)  
**URL**:

-   `POST /api/admin/payment-methods/{id}/update` (لـ form-data مع الصور)
-   `PUT /api/admin/payment-methods/{id}` (لـ JSON فقط)

**Headers**:

```
Authorization: Bearer {token}
Content-Type: multipart/form-data (لـ form-data)
```

**Body** (form-data):

-   `name`: `"اسم محدث"` (text - optional)
-   `name_ar`: `"اسم محدث"` (text - optional)
-   `icon`: [اختر ملف صورة جديد] (file - optional)
-   `qr_code`: [اختر ملف صورة جديد] (file - optional)
-   `code`: `"code_updated"` (text - optional)
-   `is_active`: `false` (text - optional)
-   `instructions`: `"تعليمات محدثة"` (text - optional)
-   `sort_order`: `2` (text - optional)

**مثال**: `POST /api/admin/payment-methods/1/update`

**الاستجابة**:

```json
{
    "success": true,
    "message": "تم تحديث طريقة الدفع بنجاح",
    "data": {
        "id": 1,
        "name": "اسم محدث",
        "name_ar": "اسم محدث",
        "icon": "http://domain.com/storage/payment_methods/new_icon.jpg",
        "qr_code": "http://domain.com/storage/payment_methods/new_qr.jpg",
        "code": "code_updated",
        "is_active": false,
        "instructions": "تعليمات محدثة",
        "sort_order": 2
    }
}
```

---

### 5. حذف طريقة دفع

**Method**: `DELETE`  
**URL**: `/api/admin/payment-methods/{id}`  
**Headers**:

```
Authorization: Bearer {token}
```

**مثال**: `DELETE /api/admin/payment-methods/1`

**الاستجابة**:

```json
{
    "success": true,
    "message": "تم حذف طريقة الدفع بنجاح"
}
```

---

## 📝 ملاحظات مهمة

### 1. رفع الصور:

-   عند رفع صورة جديدة، يتم حذف الصورة القديمة تلقائياً
-   الصور تُحفظ في: `storage/app/public/payment_methods/`
-   الروابط تُرجع كاملة: `http://domain.com/storage/payment_methods/filename.jpg`

### 2. التحديث مع form-data:

-   **يجب استخدام POST** للتحديث عند إرسال form-data (الصور)
-   Route: `POST /api/admin/payment-methods/{id}/update`
-   يمكن استخدام PUT فقط عند إرسال JSON بدون صور

### 3. الحقول الاختيارية:

-   عند التحديث، جميع الحقول اختيارية
-   يمكن تحديث حقل واحد فقط أو عدة حقول
-   إذا لم ترسل حقل، لن يتم تحديثه

### 4. Boolean Fields:

-   `is_active`: يمكن إرسال `"true"`, `"1"`, `"yes"`, `"on"` للتفعيل
-   أو `"false"`, `"0"`, `"no"`, `"off"` للإلغاء

### 5. الترتيب:

-   `sort_order`: رقم صحيح، كلما كان أصغر كلما ظهر أولاً
-   القيمة الافتراضية: `0`

---

## 🔍 أمثلة على الاستجابات

### نجاح (200/201):

```json
{
    "success": true,
    "message": "تم العملية بنجاح",
    "data": {...}
}
```

### خطأ (422 - Validation Error):

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "name": ["حقل الاسم مطلوب"],
        "name_ar": ["حقل الاسم العربي مطلوب"]
    }
}
```

### خطأ (403 - Unauthorized):

```json
{
    "success": false,
    "message": "This action is unauthorized."
}
```

### خطأ (404 - Not Found):

```json
{
    "success": false,
    "message": "No query results for model [App\\Models\\PaymentMethod] {id}"
}
```

---

## 🗄️ هيكل قاعدة البيانات

| العمود       | النوع        | Nullable | Default |
| ------------ | ------------ | -------- | ------- |
| id           | bigint       | ❌       | -       |
| name         | varchar(50)  | ❌       | -       |
| name_ar      | varchar(50)  | ❌       | -       |
| icon         | varchar(255) | ✅       | null    |
| qr_code      | varchar(500) | ✅       | null    |
| code         | varchar(100) | ✅       | null    |
| is_active    | boolean      | ❌       | true    |
| instructions | text         | ✅       | null    |
| sort_order   | integer      | ❌       | 0       |
| created_at   | timestamp    | ❌       | -       |
| updated_at   | timestamp    | ❌       | -       |

---

## ✅ نصائح للربط

1. **للصور**: استخدم `multipart/form-data` وليس `application/json`
2. **للـ Token**: استخدم `Bearer Token` في Authorization header
3. **للتحديث مع الصور**: استخدم `POST /update` وليس `PUT`
4. **للحقول الاختيارية**: يمكنك تركها فارغة أو عدم إضافتها
5. **للحقول المطلوبة**: يجب إضافتها عند الإنشاء فقط

---

## 📱 مثال كامل في Frontend

```javascript
// تحديث طريقة دفع مع صورة
const formData = new FormData();
formData.append("name", "Credit Card");
formData.append("name_ar", "بطاقة ائتمان");
formData.append("icon", iconFile); // File object
formData.append("is_active", "true");

fetch("http://domain.com/api/admin/payment-methods/1/update", {
    method: "POST",
    headers: {
        Authorization: `Bearer ${token}`,
        // لا تضيف Content-Type header، المتصفح يضيفه تلقائياً مع boundary
    },
    body: formData,
})
    .then((response) => response.json())
    .then((data) => console.log(data));
```
