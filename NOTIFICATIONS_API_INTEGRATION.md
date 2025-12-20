# 🔔 دليل ربط إدارة الإشعارات بلوحة التحكم

## 📍 نقاط النهاية (API Endpoints)

### 1. إنشاء وإرسال إشعار جديد
```
POST /api/admin/notifications
Content-Type: application/json
Authorization: Bearer {token}
```

---

## 📋 الحقول وأنواع البيانات

### الحقول المطلوبة (Required)

| الحقل | النوع | الوصف |
|------|------|-------|
| `title` | string | عنوان الإشعار (الحد الأقصى: 150 حرف) |
| `body` | string | محتوى الإشعار |

---

### الحقول الاختيارية (Optional)

| الحقل | النوع | القيمة الافتراضية | الوصف |
|------|------|------------------|-------|
| `type` | string | 'manual' | نوع الإشعار: `'system'` أو `'manual'` |
| `priority` | integer | 0 | الأولوية: `0` (عادي), `1` (مهم), `2` (عاجل) |
| `action_url` | string (URL) | null | رابط الإجراء عند الضغط على الإشعار (الحد الأقصى: 255 حرف) |
| `action_text` | string | null | نص زر الإجراء (الحد الأقصى: 50 حرف) |
| `icon` | string | null | أيقونة الإشعار (الحد الأقصى: 255 حرف) |
| `sound` | string | null | صوت الإشعار (الحد الأقصى: 255 حرف) |
| `badge` | integer | null | رقم الشارة (Badge) |
| `target_type` | string | 'all' | نوع المستلمين: `'all'` (جميع المستخدمين النشطين), `'active'` (المستخدمين النشطين مع اشتراك صالح), `'specific'` (مستخدمين محددين) |
| `user_ids` | array | null | مصفوفة من معرفات المستخدمين (مطلوب إذا كان `target_type = 'specific'`) |
| `user_ids.*` | integer | - | معرف المستخدم (يجب أن يكون موجود في `app_users`) |

---

## 🔐 متطلبات المصادقة

- **الصلاحيات**: المدير فقط (Admin - Role: 2)
- **نوع المصادقة**: Bearer Token
- **إرسال Token**: في Header باسم `Authorization`

---

## 📤 مثال على الطلب (Create)

```http
POST /api/admin/notifications
Content-Type: application/json
Authorization: Bearer 1|xxxxxxxxxxxxx

{
    "title": "إشعار مهم",
    "body": "هذا إشعار مهم لجميع المستخدمين",
    "type": "manual",
    "priority": 1,
    "action_url": "https://example.com/subscription",
    "action_text": "تجديد الآن",
    "target_type": "all"
}
```

**مثال مع مستخدمين محددين:**

```json
{
    "title": "إشعار خاص",
    "body": "هذا إشعار لمستخدمين محددين",
    "type": "manual",
    "priority": 2,
    "target_type": "specific",
    "user_ids": [1, 2, 3]
}
```

---

## 📥 مثال على الاستجابة (Success)

```json
{
    "success": true,
    "message": "تم إرسال الإشعار بنجاح",
    "data": {
        "id": 1,
        "title": "إشعار مهم",
        "body": "هذا إشعار مهم لجميع المستخدمين",
        "type": "manual",
        "priority": 1,
        "action_url": "https://example.com/subscription",
        "action_text": "تجديد الآن",
        "icon": null,
        "sound": null,
        "badge": null,
        "creator": {
            "id": 1,
            "username": "admin001",
            "firstname": "المدير",
            "phone": "123456789"
        },
        "is_read": false,
        "read_at": null,
        "is_sent": true,
        "sent_at": "2025-12-15T10:00:00Z",
        "created_at": "2025-12-15T10:00:00Z",
        "updated_at": "2025-12-15T10:00:00Z"
    }
}
```

---

## ⚠️ ملاحظات مهمة

1. **نوع المستلمين (`target_type`)**:
   - `'all'`: إرسال لجميع المستخدمين النشطين (`is_active = true`)
   - `'active'`: إرسال للمستخدمين النشطين مع اشتراك صالح (غير منتهي)
   - `'specific'`: إرسال لمستخدمين محددين (يجب تحديد `user_ids`)

2. **إرسال Push Notifications**: يتم إرسال الإشعارات تلقائياً عبر Firebase Cloud Messaging (FCM) للمستخدمين الذين لديهم `device_token`

3. **حفظ العلاقة**: يتم حفظ العلاقة بين الإشعار والمستخدمين في جدول `notification_user` مع حالة القراءة والإرسال

4. **الأولوية (`priority`)**:
   - `0`: عادي
   - `1`: مهم
   - `2`: عاجل

---

## 🗄️ هيكل قاعدة البيانات

### جدول notifications

| العمود | النوع | Nullable | Default |
|--------|------|----------|---------|
| id | bigint | ❌ | - |
| title | varchar(150) | ❌ | - |
| body | text | ❌ | - |
| type | varchar(50) | ❌ | 'manual' |
| priority | tinyint | ❌ | 0 |
| action_url | varchar(255) | ✅ | null |
| action_text | varchar(50) | ✅ | null |
| icon | varchar(255) | ✅ | null |
| sound | varchar(255) | ✅ | null |
| badge | integer | ✅ | null |
| created_by | bigint | ✅ | null |
| created_at | timestamp | ❌ | - |
| updated_at | timestamp | ❌ | - |

### جدول notification_user (Pivot Table)

| العمود | النوع | Nullable | Default |
|--------|------|----------|---------|
| notification_id | bigint | ❌ | - |
| user_id | bigint | ❌ | - |
| is_read | boolean | ❌ | false |
| is_sent | boolean | ❌ | false |
| sent_at | timestamp | ✅ | null |
| read_at | timestamp | ✅ | null |
| send_error | text | ✅ | null |
| created_at | timestamp | ❌ | - |
| updated_at | timestamp | ❌ | - |

---

## 🔍 حالات الخطأ المحتملة

### 422 - Validation Error

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "title": ["حقل العنوان مطلوب"],
        "body": ["حقل المحتوى مطلوب"],
        "user_ids": ["يجب تحديد معرفات المستخدمين عند اختيار specific"]
    }
}
```

### 403 - Forbidden

```json
{
    "success": false,
    "message": "This action is unauthorized."
}
```

---

## 📝 أمثلة إضافية

### إشعار عاجل لجميع المستخدمين النشطين

```json
{
    "title": "صيانة مجدولة",
    "body": "سيتم إجراء صيانة للنظام يوم غد",
    "type": "manual",
    "priority": 2,
    "target_type": "active"
}
```

### إشعار مع رابط إجراء

```json
{
    "title": "تجديد الاشتراك",
    "body": "اشتراكك سينتهي قريباً",
    "type": "manual",
    "priority": 1,
    "action_url": "/subscription/renew",
    "action_text": "تجديد الآن",
    "target_type": "active"
}
```

