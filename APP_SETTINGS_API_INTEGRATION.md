# ⚙️ دليل ربط إدارة إعدادات التطبيق بلوحة التحكم

## 🔐 المصادقة

جميع الـ Endpoints (عدا العرض العام) تتطلب:

```
Authorization: Bearer {token}
```

---

## 📋 الحقول وأنواع البيانات

### الحقول الأساسية:

| الحقل         | النوع              | الوصف                                           |
| ------------- | ------------------ | ----------------------------------------------- |
| `key`         | string (max: 100)  | مفتاح الإعداد (فريد - مثل: whatsapp_group)      |
| `value`       | string (max: 1000) | قيمة الإعداد (الرابط أو النص)                   |
| `type`        | string             | نوع الإعداد: `social_link` أو `general_setting` |
| `label`       | string (max: 255)  | التسمية بالعربية                                |
| `label_en`    | string (max: 255)  | التسمية بالإنجليزية                             |
| `description` | string (max: 500)  | وصف الإعداد                                     |
| `is_active`   | boolean            | حالة التفعيل (true/false)                       |
| `sort_order`  | integer (min: 0)   | ترتيب العرض                                     |

### أنواع الإعدادات:

-   `social_link`: روابط التواصل الاجتماعي (فيسبوك، واتساب، إلخ)
-   `general_setting`: إعدادات عامة للتطبيق

---

## 🛠️ API Endpoints

### 1. عرض جميع الإعدادات (للمستخدمين - النشطة فقط)

**Method**: `GET`  
**URL**: `/api/app-settings`  
**Headers**: لا يتطلب مصادقة

**Query Parameters** (اختياري):

-   `type`: `social_link` أو `general_setting` - لتصفية حسب النوع

**الاستجابة**:

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "key": "whatsapp_group",
            "value": "https://chat.whatsapp.com/...",
            "type": "social_link",
            "type_label": "رابط تواصل",
            "label": "مجموعة الواتساب",
            "label_en": "WhatsApp Group",
            "description": "رابط مجموعة الواتساب الرسمية",
            "is_active": true,
            "sort_order": 1,
            "created_at": "2025-12-15T10:00:00Z",
            "updated_at": "2025-12-15T10:00:00Z"
        }
    ]
}
```

---

### 2. الحصول على إعداد بواسطة المفتاح (للمستخدمين)

**Method**: `GET`  
**URL**: `/api/app-settings/key/{key}`  
**Headers**: لا يتطلب مصادقة

**مثال**: `GET /api/app-settings/key/whatsapp_group`

**الاستجابة**:

```json
{
    "success": true,
    "data": {
        "id": 1,
        "key": "whatsapp_group",
        "value": "https://chat.whatsapp.com/...",
        "type": "social_link",
        "type_label": "رابط تواصل",
        "label": "مجموعة الواتساب",
        "label_en": "WhatsApp Group",
        "description": "رابط مجموعة الواتساب الرسمية",
        "is_active": true,
        "sort_order": 1,
        "created_at": "2025-12-15T10:00:00Z",
        "updated_at": "2025-12-15T10:00:00Z"
    }
}
```

---

### 3. عرض جميع الإعدادات (للمدير - مع غير النشطة)

**Method**: `GET`  
**URL**: `/api/admin/app-settings`  
**Headers**:

```
Authorization: Bearer {token}
```

**Query Parameters** (اختياري):

-   `type`: `social_link` أو `general_setting` - لتصفية حسب النوع
-   `is_active`: `true` أو `false` - لتصفية حسب الحالة

**الاستجابة**: نفس شكل الاستجابة السابقة (لكن يشمل غير النشطة)

---

### 4. عرض إعداد محدد (للمدير)

**Method**: `GET`  
**URL**: `/api/admin/app-settings/{id}`  
**Headers**:

```
Authorization: Bearer {token}
```

**مثال**: `GET /api/admin/app-settings/1`

**الاستجابة**: نفس شكل الاستجابة السابقة

---

### 5. تحديث إعداد محدد

**Method**: `PUT`  
**URL**: `/api/admin/app-settings/{id}`  
**Headers**:

```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body** (JSON):

```json
{
    "value": "https://chat.whatsapp.com/new-link",
    "label": "مجموعة الواتساب الجديدة",
    "label_en": "New WhatsApp Group",
    "description": "وصف محدث",
    "is_active": true,
    "sort_order": 2
}
```

**ملاحظة**: جميع الحقول اختيارية عند التحديث

**الاستجابة**:

```json
{
    "success": true,
    "message": "تم تحديث الإعداد بنجاح",
    "data": {
        "id": 1,
        "key": "whatsapp_group",
        "value": "https://chat.whatsapp.com/new-link",
        "type": "social_link",
        "type_label": "رابط تواصل",
        "label": "مجموعة الواتساب الجديدة",
        "label_en": "New WhatsApp Group",
        "description": "وصف محدث",
        "is_active": true,
        "sort_order": 2,
        "created_at": "2025-12-15T10:00:00Z",
        "updated_at": "2025-12-15T11:00:00Z"
    }
}
```

---

### 6. تحديث عدة إعدادات دفعة واحدة

**Method**: `POST`  
**URL**: `/api/admin/app-settings/update-multiple`  
**Headers**:

```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body** (JSON):

```json
{
    "settings": [
        {
            "key": "whatsapp_group",
            "value": "https://chat.whatsapp.com/...",
            "type": "social_link",
            "label": "مجموعة الواتساب",
            "label_en": "WhatsApp Group",
            "description": "رابط مجموعة الواتساب",
            "is_active": true,
            "sort_order": 1
        },
        {
            "key": "facebook_page",
            "value": "https://facebook.com/...",
            "type": "social_link",
            "label": "صفحة الفيسبوك",
            "label_en": "Facebook Page",
            "is_active": true,
            "sort_order": 2
        }
    ]
}
```

**ملاحظات**:

-   إذا كان `key` موجود، يتم التحديث
-   إذا كان `key` غير موجود، يتم الإنشاء
-   جميع الحقول داخل `settings.*` اختيارية ما عدا `key`

**الاستجابة**:

```json
{
    "success": true,
    "message": "تم تحديث الإعدادات بنجاح",
    "data": [
        {
            "id": 1,
            "key": "whatsapp_group",
            "value": "https://chat.whatsapp.com/...",
            "type": "social_link",
            "label": "مجموعة الواتساب",
            "label_en": "WhatsApp Group",
            "is_active": true,
            "sort_order": 1
        },
        {
            "id": 2,
            "key": "facebook_page",
            "value": "https://facebook.com/...",
            "type": "social_link",
            "label": "صفحة الفيسبوك",
            "label_en": "Facebook Page",
            "is_active": true,
            "sort_order": 2
        }
    ]
}
```

---

### 7. حذف إعداد

**Method**: `DELETE`  
**URL**: `/api/admin/app-settings/{id}`  
**Headers**:

```
Authorization: Bearer {token}
```

**مثال**: `DELETE /api/admin/app-settings/1`

**الاستجابة**:

```json
{
    "success": true,
    "message": "تم حذف الإعداد بنجاح"
}
```

---

## 📝 ملاحظات مهمة

### 1. المفتاح (Key):

-   يجب أن يكون فريداً (unique)
-   لا يمكن تحديث `key` بعد الإنشاء
-   أمثلة: `whatsapp`, `facebook`, `twitter`, `contact_phone`, `app_version`, `copyright`

### 2. القيمة (Value):

-   يمكن أن تكون رابط URL أو نص عادي
-   الحد الأقصى: 1000 حرف
-   يمكن أن تكون `null`

### 3. النوع (Type):

-   `social_link`: لروابط التواصل الاجتماعي
-   `general_setting`: للإعدادات العامة
-   القيمة الافتراضية: `general_setting`

### 4. التحديث:

-   جميع الحقول اختيارية عند التحديث
-   يمكن تحديث حقل واحد فقط أو عدة حقول
-   إذا لم ترسل حقل، لن يتم تحديثه

### 5. Boolean Fields:

-   `is_active`: يمكن إرسال `true` أو `false`
-   القيمة الافتراضية: `true`

### 6. الترتيب:

-   `sort_order`: رقم صحيح (≥ 0)، كلما كان أصغر كلما ظهر أولاً
-   القيمة الافتراضية: `0`

### 7. التحديث المتعدد:

-   مفيد لتحديث عدة إعدادات دفعة واحدة
-   إذا كان `key` موجود، يتم التحديث
-   إذا كان `key` غير موجود، يتم الإنشاء تلقائياً

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
        "value": ["القيمة يجب أن تكون على الأكثر 1000 حرف"],
        "sort_order": ["ترتيب العرض يجب أن يكون رقماً صحيحاً"]
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
    "message": "الإعداد غير موجود"
}
```

---

## 🗄️ هيكل قاعدة البيانات

| العمود      | النوع        | Nullable | Default           |
| ----------- | ------------ | -------- | ----------------- |
| id          | bigint       | ❌       | -                 |
| key         | varchar(100) | ❌       | - (unique)        |
| value       | text         | ✅       | null              |
| type        | varchar(50)  | ❌       | 'general_setting' |
| label       | varchar(255) | ✅       | null              |
| label_en    | varchar(255) | ✅       | null              |
| description | text         | ✅       | null              |
| is_active   | boolean      | ❌       | true              |
| sort_order  | integer      | ❌       | 0                 |
| created_at  | timestamp    | ❌       | -                 |
| updated_at  | timestamp    | ❌       | -                 |

---

## ✅ نصائح للربط

1. **للـ Token**: استخدم `Bearer Token` في Authorization header
2. **للتحديث**: استخدم `PUT` لتحديث إعداد واحد، أو `POST /update-multiple` لتحديث عدة إعدادات
3. **للحقول الاختيارية**: يمكنك تركها فارغة أو عدم إضافتها
4. **للمفتاح (Key)**: لا يمكن تحديثه بعد الإنشاء
5. **للتصفية**: استخدم query parameters (`type`, `is_active`) لعرض إعدادات محددة

---

## 📱 أمثلة في Frontend

### تحديث إعداد واحد:

```javascript
fetch("http://domain.com/api/admin/app-settings/1", {
    method: "PUT",
    headers: {
        Authorization: `Bearer ${token}`,
        "Content-Type": "application/json",
    },
    body: JSON.stringify({
        value: "https://chat.whatsapp.com/new-link",
        label: "مجموعة الواتساب الجديدة",
        is_active: true,
    }),
})
    .then((response) => response.json())
    .then((data) => console.log(data));
```

### تحديث عدة إعدادات دفعة واحدة:

```javascript
fetch("http://domain.com/api/admin/app-settings/update-multiple", {
    method: "POST",
    headers: {
        Authorization: `Bearer ${token}`,
        "Content-Type": "application/json",
    },
    body: JSON.stringify({
        settings: [
            {
                key: "whatsapp_group",
                value: "https://chat.whatsapp.com/...",
                type: "social_link",
                label: "مجموعة الواتساب",
                is_active: true,
                sort_order: 1,
            },
            {
                key: "facebook_page",
                value: "https://facebook.com/...",
                type: "social_link",
                label: "صفحة الفيسبوك",
                is_active: true,
                sort_order: 2,
            },
        ],
    }),
})
    .then((response) => response.json())
    .then((data) => console.log(data));
```

### عرض الإعدادات حسب النوع:

```javascript
// عرض روابط التواصل فقط
fetch("http://domain.com/api/app-settings?type=social_link")
    .then((response) => response.json())
    .then((data) => console.log(data));

// عرض الإعدادات العامة فقط
fetch("http://domain.com/api/app-settings?type=general_setting")
    .then((response) => response.json())
    .then((data) => console.log(data));
```

---

## 🎯 حالات الاستخدام الشائعة

### 1. إدارة روابط التواصل الاجتماعي:

-   **الواتساب** (`whatsapp`): رابط الواتساب أو رقم الهاتف
-   **الفيسبوك** (`facebook`): رابط صفحة الفيسبوك
-   **تويتر** (`twitter`): رابط حساب تويتر
-   **إنستغرام** (`instagram`): رابط حساب إنستغرام
-   **لينكد إن** (`linkedin`): رابط صفحة لينكد إن
-   **تيك توك** (`tiktok`): رابط حساب تيك توك
-   نوع: `social_link`

### 2. إعدادات عامة:

-   **رقم الاتصال** (`contact_phone`): رقم هاتف الاتصال
-   **إصدار التطبيق** (`app_version`): رقم إصدار التطبيق الحالي (مثل: 1.0.0)
-   **الحقوق** (`copyright`): نص حقوق النشر (مثل: © 2025 جميع الحقوق محفوظة)
-   **اسم التطبيق** (`app_name`): اسم التطبيق المعروض
-   **البريد الإلكتروني** (`support_email`): بريد الدعم الفني
-   **عنوان المكتب** (`office_address`): عنوان المكتب الرئيسي
-   نوع: `general_setting`

### 3. الإعدادات الافتراضية:

عند تشغيل الـ seeder، يتم إنشاء الإعدادات التالية تلقائياً:

**روابط التواصل:**

-   `whatsapp` - الواتساب
-   `facebook` - الفيسبوك
-   `twitter` - تويتر
-   `instagram` - إنستغرام
-   `linkedin` - لينكد إن
-   `tiktok` - تيك توك

**إعدادات عامة:**

-   `contact_phone` - رقم الاتصال
-   `app_version` - إصدار التطبيق (افتراضي: 1.0.0)
-   `copyright` - الحقوق (افتراضي: © 2025 جميع الحقوق محفوظة)
-   `app_name` - اسم التطبيق (افتراضي: تطبيق WiFi)
-   `support_email` - بريد الدعم
-   `office_address` - عنوان المكتب

### 4. التحديث السريع:

-   استخدام `update-multiple` لتحديث جميع الإعدادات دفعة واحدة
-   مفيد عند حفظ صفحة إعدادات كاملة

---

## 🔄 تشغيل الـ Seeder

لإنشاء الإعدادات الافتراضية في قاعدة البيانات:

```bash
php artisan db:seed --class=AppSettingsSeeder
```

أو لتشغيل جميع الـ seeders:

```bash
php artisan db:seed
```
