# 📺 دليل ربط إدارة البث المباشر بلوحة التحكم

## 📍 نقاط النهاية (API Endpoints)

### 1. إنشاء بث مباشر جديد

```
POST /api/admin/live-streams
Content-Type: multipart/form-data
Authorization: Bearer {token}
```

### 2. تحديث بث مباشر

```
PUT /api/admin/live-streams/{id}
Content-Type: multipart/form-data
Authorization: Bearer {token}
```

### 3. حذف بث مباشر

```
DELETE /api/admin/live-streams/{id}
Authorization: Bearer {token}
```

### 4. عرض البثوث المباشرة (للعرض في لوحة التحكم)

```
GET /api/live-streams
Authorization: Bearer {token}
```

---

## 📋 الحقول وأنواع البيانات

### الحقول المطلوبة (Required)

| الحقل        | النوع        | الوصف                                     |
| ------------ | ------------ | ----------------------------------------- |
| `title`      | string       | عنوان البث المباشر (الحد الأقصى: 150 حرف) |
| `stream_url` | string (URL) | رابط البث المباشر (الحد الأقصى: 500 حرف)  |

---

### الحقول الاختيارية (Optional)

| الحقل             | النوع    | القيمة الافتراضية | الوصف                                                                                                     |
| ----------------- | -------- | ----------------- | --------------------------------------------------------------------------------------------------------- |
| `description`     | string   | null              | وصف البث المباشر                                                                                          |
| `thumbnail`       | file     | null              | الصورة المصغرة (jpg, jpeg, png, webp - الحد الأقصى: 2MB)                                                  |
| `access_type`     | string   | 'all_subscribers' | نوع الوصول: `'all_subscribers'` (لجميع المشتركين) أو `'live_subscribers_only'` (لمشتركي البث المباشر فقط) |
| `live_stream_package_id` | integer | null        | ربط البث بباقة معينة (اختياري). إذا تم تحديده يصبح البث متاحًا فقط لمشتركي هذه الباقة (أو من لديه live_access=true) |
| `category`        | string   | 'match'           | الفئة: `'match'` (مباراة), `'channel'` (قناة), `'event'` (حدث)                                            |
| `stream_type`     | string   | 'live'            | نوع البث: `'live'` (مباشر) أو `'vod'` (فيديو عند الطلب)                                                   |
| `is_active`       | boolean  | true              | تفعيل/تعطيل البث                                                                                          |
| `is_featured`     | boolean  | false             | تحديد كبث مميز                                                                                            |
| `start_time`      | datetime | null              | تاريخ ووقت بداية البث (صيغة: Y-m-d H:i:s)                                                                 |
| `end_time`        | datetime | null              | تاريخ ووقت نهاية البث (يجب أن يكون بعد start_time)                                                        |
| `max_viewers`     | integer  | null              | الحد الأقصى للمشاهدين (الحد الأدنى: 1)                                                                    |
| `sort_order`      | integer  | 0                 | ترتيب العرض (الأقل = الأول)                                                                               |
| `quality_options` | array    | null              | مصفوفة خيارات الجودة (JSON)                                                                               |

**ملاحظة**: عند التحديث (PUT)، جميع الحقول اختيارية

---

## 🔐 متطلبات المصادقة

-   **الصلاحيات**: المدير فقط (Admin - Role: 2)
-   **نوع المصادقة**: Bearer Token
-   **إرسال Token**: في Header باسم `Authorization`

---

## 📤 مثال على الطلب (Create)

```http
POST /api/admin/live-streams
Content-Type: multipart/form-data
Authorization: Bearer 1|xxxxxxxxxxxxx

Form Data:
- title: "مباراة اليوم"
- description: "مباراة مهمة"
- stream_url: "https://example.com/stream.m3u8"
- thumbnail: [file]
- access_type: "all_subscribers"
- live_stream_package_id: 1
- category: "match"
- stream_type: "live"
- is_active: true
- is_featured: true
- start_time: "2025-12-15 20:00:00"
- end_time: "2025-12-15 22:00:00"
- max_viewers: 1000
- sort_order: 1
```

---

## 📥 مثال على الاستجابة (Success)

```json
{
    "success": true,
    "message": "تم إنشاء البث بنجاح",
    "data": {
        "id": 1,
        "title": "مباراة اليوم",
        "description": "مباراة مهمة",
        "stream_url": "https://example.com/stream.m3u8",
        "access_type": "all_subscribers",
        "access_type_label": "لجميع المشتركين",
        "thumbnail": "https://domain.com/storage/live_stream_thumbnails/xxx.jpg",
        "category": "match",
        "stream_type": "live",
        "is_active": true,
        "is_featured": true,
        "start_time": "2025-12-15T20:00:00Z",
        "end_time": "2025-12-15T22:00:00Z",
        "view_count": 0,
        "max_viewers": 1000,
        "sort_order": 1,
        "quality_options": null,
        "created_at": "2025-12-15T10:00:00Z",
        "updated_at": "2025-12-15T10:00:00Z"
    }
}
```

---

## ⚠️ ملاحظات مهمة

1. **رفع الملفات**: يجب استخدام `multipart/form-data` عند إرسال الصورة المصغرة

2. **تخزين الصور**: يتم حفظ الصور في `storage/app/public/live_stream_thumbnails/`

3. **الروابط**: يتم إرجاع روابط كاملة للصور في الاستجابة

4. **نوع الوصول (`access_type`)**:

    - `'all_subscribers'`: متاح لجميع المشتركين النشطين
    - `'live_subscribers_only'`: متاح فقط للمستخدمين الذين لديهم `live_access = true` واشتراك نشط

5. **الترتيب**: البثوث تُعرض حسب `sort_order` ثم `created_at`

6. **الإشعارات التلقائية**: عند إنشاء بث جديد مع `start_time` في نفس اليوم، يتم إرسال إشعار تلقائي للمستخدمين المصرح لهم

7. **الفلترة التلقائية**: عند عرض البثوث للمستخدمين، يتم تصفية البثوث النشطة فقط والمتاحة زمنياً حسب نوع الوصول

---

## 🗄️ هيكل قاعدة البيانات

| العمود          | النوع        | Nullable | Default           |
| --------------- | ------------ | -------- | ----------------- |
| id              | bigint       | ❌       | -                 |
| title           | varchar(150) | ❌       | -                 |
| description     | text         | ✅       | null              |
| stream_url      | varchar(500) | ❌       | -                 |
| access_type     | varchar(50)  | ✅       | 'all_subscribers' |
| thumbnail       | varchar(255) | ✅       | null              |
| category        | varchar(50)  | ❌       | 'match'           |
| stream_type     | varchar(20)  | ❌       | 'live'            |
| is_active       | boolean      | ❌       | true              |
| is_featured     | boolean      | ❌       | false             |
| start_time      | datetime     | ✅       | null              |
| end_time        | datetime     | ✅       | null              |
| view_count      | integer      | ❌       | 0                 |
| max_viewers     | integer      | ✅       | null              |
| sort_order      | integer      | ❌       | 0                 |
| quality_options | json         | ✅       | null              |
| created_at      | timestamp    | ❌       | -                 |
| updated_at      | timestamp    | ❌       | -                 |

---

## 🔍 حالات الخطأ المحتملة

### 422 - Validation Error

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "title": ["حقل العنوان مطلوب"],
        "stream_url": ["حقل رابط البث مطلوب"],
        "end_time": ["تاريخ النهاية يجب أن يكون بعد تاريخ البداية"]
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

### 404 - Not Found

```json
{
    "success": false,
    "message": "البث غير متاح"
}
```

---

## 📝 أمثلة إضافية

### بث مباشر لمشتركي البث المباشر فقط

```json
{
    "title": "مباراة حصرية",
    "description": "مباراة حصرية لمشتركي البث المباشر",
    "stream_url": "https://example.com/exclusive.m3u8",
    "access_type": "live_subscribers_only",
    "category": "match",
    "is_featured": true,
    "start_time": "2025-12-16 19:00:00"
}
```

### بث فيديو عند الطلب (VOD)

```json
{
    "title": "مباراة مسجلة",
    "description": "إعادة مباراة الأمس",
    "stream_url": "https://example.com/vod.m3u8",
    "stream_type": "vod",
    "category": "match",
    "is_active": true
}
```

### بث مع خيارات الجودة

```json
{
    "title": "بث عالي الجودة",
    "stream_url": "https://example.com/stream.m3u8",
    "quality_options": [
        { "label": "HD", "url": "https://example.com/hd.m3u8" },
        { "label": "SD", "url": "https://example.com/sd.m3u8" },
        { "label": "Low", "url": "https://example.com/low.m3u8" }
    ]
}
```
