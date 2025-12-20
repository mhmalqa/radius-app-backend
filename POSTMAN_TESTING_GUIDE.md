# 🧪 دليل اختبار API باستخدام Postman

## 🔐 المصادقة (Authentication)

### 1. تسجيل الدخول كمدير

**Method**: `POST`  
**URL**: `http://your-domain.com/api/auth/login`  
**Headers**:

```
Content-Type: application/json
```

**Body** (raw JSON):

```json
{
    "username": "admin001",
    "password": "your_password"
}
```

**الاستجابة**: ستحصل على `token` - احفظه للاستخدام في الطلبات التالية

---

## 🖼️ اختبار السلايدات (Slides)

### 1. عرض جميع السلايدات (للمدير)

**Method**: `GET`  
**URL**: `http://your-domain.com/api/admin/slides`  
**Headers**:

```
Authorization: Bearer {your_token}
```

**Query Parameters** (اختياري):

-   `is_active`: `true` أو `false`
-   `target_audience`: `all`, `active_users`, `expired_users`
-   `per_page`: `15`
-   `page`: `1`

---

### 2. إنشاء سلايد جديد

**Method**: `POST`  
**URL**: `http://your-domain.com/api/admin/slides`  
**Headers**:

```
Authorization: Bearer {your_token}
Content-Type: multipart/form-data
```

**Body** (form-data):

-   `title`: `"عرض خاص"` (text)
-   `image`: [اختر ملف صورة] (file)
-   `image_mobile`: [اختر ملف صورة] (file - optional)
-   `image_desktop`: [اختر ملف صورة] (file - optional)
-   `link_url`: `"https://example.com"` (text - optional)
-   `is_active`: `true` (text - optional)
-   `target_audience`: `"all"` (text - optional)
-   `sort_order`: `1` (text - optional)
-   `start_at`: `"2025-12-15 00:00:00"` (text - optional)
-   `end_at`: `"2025-12-31 23:59:59"` (text - optional)

---

### 3. تحديث سلايد

**Method**: `PUT`  
**URL**: `http://your-domain.com/api/admin/slides/{id}`  
**Headers**:

```
Authorization: Bearer {your_token}
Content-Type: multipart/form-data
```

**Body** (form-data):

-   `title`: `"عنوان محدث"` (text - optional)
-   `image`: [اختر ملف صورة جديد] (file - optional)
-   `image_mobile`: [اختر ملف صورة] (file - optional)
-   `image_desktop`: [اختر ملف صورة] (file - optional)
-   `is_active`: `false` (text - optional)
-   أي حقل آخر تريد تحديثه

**مثال**: `PUT /api/admin/slides/1`

---

### 4. حذف سلايد

**Method**: `DELETE`  
**URL**: `http://your-domain.com/api/admin/slides/{id}`  
**Headers**:

```
Authorization: Bearer {your_token}
```

**مثال**: `DELETE /api/admin/slides/1`

---

## 📺 اختبار البثوث المباشرة (Live Streams)

### 1. عرض جميع البثوث (للمدير)

**Method**: `GET`  
**URL**: `http://your-domain.com/api/admin/live-streams`  
**Headers**:

```
Authorization: Bearer {your_token}
```

**Query Parameters** (اختياري):

-   `is_active`: `true` أو `false`
-   `access_type`: `all_subscribers`, `live_subscribers_only`
-   `category`: `match`, `channel`, `event`
-   `featured`: `true` أو `false`
-   `per_page`: `15`
-   `page`: `1`

---

### 2. إنشاء بث مباشر جديد

**Method**: `POST`  
**URL**: `http://your-domain.com/api/admin/live-streams`  
**Headers**:

```
Authorization: Bearer {your_token}
Content-Type: multipart/form-data
```

**Body** (form-data):

-   `title`: `"مباراة اليوم"` (text - required)
-   `stream_url`: `"https://example.com/stream.m3u8"` (text - required)
-   `description`: `"وصف البث"` (text - optional)
-   `thumbnail`: [اختر ملف صورة] (file - optional)
-   `access_type`: `"all_subscribers"` (text - optional)
-   `category`: `"match"` (text - optional)
-   `stream_type`: `"live"` (text - optional)
-   `is_active`: `true` (text - optional)
-   `is_featured`: `true` (text - optional)
-   `start_time`: `"2025-12-15 20:00:00"` (text - optional)
-   `end_time`: `"2025-12-15 22:00:00"` (text - optional)
-   `max_viewers`: `1000` (text - optional)
-   `sort_order`: `1` (text - optional)

---

### 3. تحديث بث مباشر

**Method**: `PUT`  
**URL**: `http://your-domain.com/api/admin/live-streams/{id}`  
**Headers**:

```
Authorization: Bearer {your_token}
Content-Type: multipart/form-data
```

**Body** (form-data):

-   `title`: `"عنوان محدث"` (text - optional)
-   `thumbnail`: [اختر ملف صورة جديد] (file - optional)
-   `is_active`: `false` (text - optional)
-   أي حقل آخر تريد تحديثه

**مثال**: `PUT /api/admin/live-streams/1`

**ملاحظة مهمة**: عند تحديث الصورة، تأكد من:

1. اختيار `form-data` في Postman
2. إضافة `thumbnail` كـ **File** (وليس Text)
3. اختيار ملف صورة صالح

---

### 4. حذف بث مباشر

**Method**: `DELETE`  
**URL**: `http://your-domain.com/api/admin/live-streams/{id}`  
**Headers**:

```
Authorization: Bearer {your_token}
```

**مثال**: `DELETE /api/admin/live-streams/1`

---

## 🔔 اختبار الإشعارات (Notifications)

### إنشاء وإرسال إشعار

**Method**: `POST`  
**URL**: `http://your-domain.com/api/admin/notifications`  
**Headers**:

```
Authorization: Bearer {your_token}
Content-Type: application/json
```

**Body** (raw JSON):

```json
{
    "title": "إشعار مهم",
    "body": "هذا إشعار مهم لجميع المستخدمين",
    "type": "manual",
    "priority": 1,
    "action_url": "https://example.com",
    "action_text": "عرض التفاصيل",
    "target_type": "all"
}
```

**أو لإرسال لمستخدمين محددين**:

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

## 📝 خطوات استخدام Postman

### 1. إعداد Environment (اختياري)

أنشئ Environment جديد في Postman:

-   `base_url`: `http://your-domain.com/api`
-   `token`: `{your_token}`

### 2. إعداد Authorization

في كل request:

1. اذهب إلى تبويب **Authorization**
2. اختر **Type**: `Bearer Token`
3. أدخل الـ token في حقل **Token**

### 3. إرسال الملفات (للصور)

1. اختر **Body** tab
2. اختر **form-data**
3. أضف الحقول:
    - للحقول النصية: اختر **Text**
    - للملفات: اختر **File** ثم اختر الملف

### 4. مثال على تحديث سلايد مع صورة

```
Method: PUT
URL: {{base_url}}/admin/slides/1
Authorization: Bearer {{token}}
Body (form-data):
  - title: "عنوان جديد" (Text)
  - image: [اختر ملف] (File)
  - is_active: "true" (Text)
```

---

## ✅ نصائح مهمة

1. **للصور**: استخدم `multipart/form-data` وليس `application/json`
2. **للـ Token**: استخدم `Bearer Token` في Authorization
3. **للحقول الاختيارية**: يمكنك تركها فارغة أو عدم إضافتها
4. **للحقول المطلوبة**: يجب إضافتها دائماً
5. **للأخطاء**: تحقق من Status Code و Response Body

---

## 🔍 أمثلة على الاستجابات

### نجاح (200/201):

```json
{
    "success": true,
    "message": "تم التحديث بنجاح",
    "data": {...}
}
```

### خطأ (422):

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "title": ["حقل العنوان مطلوب"]
    }
}
```

### خطأ (403):

```json
{
    "success": false,
    "message": "This action is unauthorized."
}
```
