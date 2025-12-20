# 📚 API Documentation

## 🔐 Authentication

جميع الـ API endpoints (ما عدا المسموح) تتطلب مصادقة باستخدام Laravel Sanctum.

### تسجيل الدخول

```http
POST /api/auth/login
Content-Type: application/json

{
    "username": "user123",
    "password": "password123"
}
```

### التسجيل

```http
POST /api/auth/register
Content-Type: application/json

{
    "username": "user123",
    "password": "password123",
    "password_confirmation": "password123",
    "phone": "07501234567",
    "email": "user@example.com",
    "language": "ar"
}
```

### الحصول على المستخدم الحالي

```http
GET /api/auth/me
Authorization: Bearer {token}
```

### تسجيل الخروج

```http
POST /api/auth/logout
Authorization: Bearer {token}
```

---

## 👤 User Endpoints

### الحصول على الملف الشخصي

```http
GET /api/user/profile
Authorization: Bearer {token}
```

### تحديث الملف الشخصي

```http
PUT /api/user/profile
Authorization: Bearer {token}
Content-Type: application/json

{
    "phone": "07501234567",
    "email": "user@example.com",
    "language": "ar",
    "device_token": "fcm_token_here",
    "device_type": "android"
}
```

### مزامنة الاشتراك من Radius

```http
POST /api/user/sync-subscription
Authorization: Bearer {token}
```

---

## 💳 Payment Requests

### إنشاء طلب دفع

```http
POST /api/payment-requests
Authorization: Bearer {token}
Content-Type: multipart/form-data

{
    "amount": 50000,
    "currency": "IQD",
    "period_months": 1,
    "payment_method_id": 1,
    "transaction_number": "TXN123456",
    "receipt_file": {file},
    "payment_date": "2024-01-15"
}
```

### الحصول على طلبات الدفع

```http
GET /api/payment-requests?status=0
Authorization: Bearer {token}
```

### الحصول على طلب دفع محدد

```http
GET /api/payment-requests/{id}
Authorization: Bearer {token}
```

---

## 💰 Payment Methods

### الحصول على طرق الدفع المتاحة

```http
GET /api/payment-methods
```

---

## 📺 Live Streams

### الحصول على البث المباشر

```http
GET /api/live-streams?category=match&featured=true
```

### الحصول على بث محدد

```http
GET /api/live-streams/{id}
Authorization: Bearer {token}
```

---

## 🖼️ Slides

### الحصول على السلايدات

```http
GET /api/slides?target_audience=all
```

### تتبع النقرات

```http
POST /api/slides/{id}/track-click
Authorization: Bearer {token}
```

---

## 🔔 Notifications

### الحصول على الإشعارات

```http
GET /api/notifications?unread_only=true
Authorization: Bearer {token}
```

### عدد الإشعارات غير المقروءة

```http
GET /api/notifications/unread-count
Authorization: Bearer {token}
```

### تحديد إشعار كمقروء

```http
POST /api/notifications/{id}/mark-as-read
Authorization: Bearer {token}
```

### تحديد جميع الإشعارات كمقروءة

```http
POST /api/notifications/mark-all-as-read
Authorization: Bearer {token}
```

---

## 👨‍💼 Admin Endpoints

### إدارة طلبات الدفع (Admin/Accountant)

```http
GET /api/admin/payment-requests?status=0
Authorization: Bearer {token}
```

### تحديث حالة طلب الدفع

```http
PUT /api/admin/payment-requests/{id}/status
Authorization: Bearer {token}
Content-Type: application/json

{
    "status": 1,
    "notes": "تم قبول الطلب",
    "approved_amount": 50000
}
```

### إنشاء بث مباشر (Admin)

```http
POST /api/admin/live-streams
Authorization: Bearer {token}
Content-Type: application/json

{
    "title": "مباراة اليوم",
    "stream_url": "https://example.com/stream.m3u8",
    "category": "match",
    "is_active": true,
    "is_featured": true
}
```

### إنشاء سلايد (Admin)

```http
POST /api/admin/slides
Authorization: Bearer {token}
Content-Type: application/json

{
    "title": "عرض خاص",
    "image_path": "slides/slide1.jpg",
    "link_url": "https://example.com",
    "target_audience": "all"
}
```

### إنشاء إشعار (Admin)

```http
POST /api/admin/notifications
Authorization: Bearer {token}
Content-Type: application/json

{
    "title": "إشعار مهم",
    "body": "هذا إشعار مهم",
    "type": "manual",
    "priority": 1,
    "user_ids": [1, 2, 3]
}
```

---

## 📊 Response Format

جميع الـ API responses تتبع نفس التنسيق:

### Success Response

```json
{
    "success": true,
    "message": "تمت العملية بنجاح",
    "data": {
        // البيانات
    }
}
```

### Error Response

```json
{
    "success": false,
    "message": "رسالة الخطأ",
    "errors": {
        "field": ["خطأ في الحقل"]
    }
}
```

---

## 🔒 Status Codes

-   `200` - Success
-   `201` - Created
-   `400` - Bad Request
-   `401` - Unauthorized
-   `403` - Forbidden
-   `404` - Not Found
-   `422` - Validation Error
-   `500` - Server Error

---

## 📝 Notes

1. جميع التواريخ بصيغة ISO 8601
2. جميع المبالغ المالية بصيغة decimal
3. الصور والملفات تُخزن في `storage/app/public`
4. الـ API يدعم اللغة العربية والإنجليزية
