# 🔗 دليل ربط إدارة السلايدات بلوحة التحكم

## 📍 نقاط النهاية (API Endpoints)

### 1. إنشاء سلايد جديد
```
POST /api/admin/slides
Content-Type: multipart/form-data
Authorization: Bearer {token}
```

### 2. تحديث سلايد
```
PUT /api/admin/slides/{id}
Content-Type: multipart/form-data
Authorization: Bearer {token}
```

### 3. حذف سلايد
```
DELETE /api/admin/slides/{id}
Authorization: Bearer {token}
```

### 4. عرض السلايدات (للعرض في لوحة التحكم)
```
GET /api/slides
Authorization: Bearer {token}
```

---

## 📋 الحقول وأنواع البيانات

### الحقول المطلوبة (Required)

| الحقل | النوع | الوصف |
|------|------|-------|
| `title` | string | عنوان السلايد (الحد الأقصى: 150 حرف) |
| `image` | file | الصورة الرئيسية (jpeg, jpg, png, webp - الحد الأقصى: 5MB) |

**ملاحظة**: عند التحديث (PUT)، حقل `image` يصبح اختياري

---

### الحقول الاختيارية (Optional)

| الحقل | النوع | القيمة الافتراضية | الوصف |
|------|------|------------------|-------|
| `image_mobile` | file | null | صورة للموبايل (jpeg, jpg, png, webp - الحد الأقصى: 5MB) |
| `image_desktop` | file | null | صورة للديسكتوب (jpeg, jpg, png, webp - الحد الأقصى: 5MB) |
| `link_url` | string (URL) | null | رابط عند الضغط على السلايد (الحد الأقصى: 255 حرف) |
| `is_active` | boolean | true | تفعيل/تعطيل السلايد |
| `target_audience` | string | 'all' | الجمهور المستهدف: `'all'` أو `'active_users'` أو `'expired_users'` |
| `sort_order` | integer | 0 | ترتيب العرض (الأقل = الأول) |
| `start_at` | datetime | null | تاريخ ووقت بداية العرض (صيغة: Y-m-d H:i:s) |
| `end_at` | datetime | null | تاريخ ووقت نهاية العرض (يجب أن يكون بعد start_at) |

---

## 🔐 متطلبات المصادقة

- **الصلاحيات**: المدير فقط (Admin - Role: 2)
- **نوع المصادقة**: Bearer Token
- **إرسال Token**: في Header باسم `Authorization`

---

## 📤 مثال على الطلب (Create)

```http
POST /api/admin/slides
Content-Type: multipart/form-data
Authorization: Bearer 1|xxxxxxxxxxxxx

Form Data:
- title: "عرض خاص"
- image: [file]
- image_mobile: [file] (optional)
- image_desktop: [file] (optional)
- link_url: "https://example.com"
- is_active: true
- target_audience: "all"
- sort_order: 1
- start_at: "2025-01-01 00:00:00"
- end_at: "2025-01-31 23:59:59"
```

---

## 📥 مثال على الاستجابة (Success)

```json
{
    "success": true,
    "message": "تم إنشاء السلايد بنجاح",
    "data": {
        "id": 1,
        "title": "عرض خاص",
        "image_path": "https://domain.com/storage/slides/xxx.jpg",
        "image_mobile": "https://domain.com/storage/slides/xxx-mobile.jpg",
        "image_desktop": "https://domain.com/storage/slides/xxx-desktop.jpg",
        "link_url": "https://example.com",
        "is_active": true,
        "target_audience": "all",
        "sort_order": 1,
        "start_at": "2025-01-01T00:00:00Z",
        "end_at": "2025-01-31T23:59:59Z",
        "click_count": 0,
        "created_at": "2025-01-01T10:00:00Z",
        "updated_at": "2025-01-01T10:00:00Z"
    }
}
```

---

## ⚠️ ملاحظات مهمة

1. **رفع الملفات**: يجب استخدام `multipart/form-data` عند إرسال الصور
2. **تخزين الصور**: يتم حفظ الصور في `storage/app/public/slides/`
3. **الروابط**: يتم إرجاع روابط كاملة للصور في الاستجابة
4. **الترتيب**: السلايدات تُعرض حسب `sort_order` ثم `created_at`
5. **التصفية التلقائية**: عند عرض السلايدات للمستخدمين، يتم تصفية السلايدات النشطة فقط والمتاحة زمنياً

---

## 🗄️ هيكل قاعدة البيانات

| العمود | النوع | Nullable | Default |
|--------|------|----------|---------|
| id | bigint | ❌ | - |
| title | varchar(150) | ❌ | - |
| image_path | varchar(255) | ❌ | - |
| image_mobile | varchar(255) | ✅ | null |
| image_desktop | varchar(255) | ✅ | null |
| link_url | varchar(255) | ✅ | null |
| is_active | boolean | ❌ | true |
| target_audience | varchar(20) | ❌ | 'all' |
| sort_order | integer | ❌ | 0 |
| start_at | datetime | ✅ | null |
| end_at | datetime | ✅ | null |
| click_count | integer | ❌ | 0 |
| created_at | timestamp | ❌ | - |
| updated_at | timestamp | ❌ | - |

