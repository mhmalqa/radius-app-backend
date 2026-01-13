# 📱 دليل API الخاصة بالمستخدم

## 🔐 المصادقة (Authentication)

### تسجيل الدخول

```
POST /api/auth/login
Content-Type: application/json
```

**المعاملات:**

```json
{
    "username": "string (required)",
    "password": "string (required)"
}
```

**الاستجابة الناجحة (200):**

```json
{
    "success": true,
    "message": "تم تسجيل الدخول بنجاح",
    "data": {
        "user": {
            "id": 1,
            "username": "user001",
            "email": "user@example.com",
            "phone": "07501234567",
            "role": 0,
            "is_active": true,
            "live_access": false,
            "language": "ar"
        },
        "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
    }
}
```

**الأخطاء:**

-   `401`: بيانات الدخول غير صحيحة
-   `422`: أخطاء التحقق من البيانات

---

### التسجيل

```
POST /api/auth/register
Content-Type: application/json
```

**المعاملات:**

```json
{
    "username": "string (required, unique in radius)",
    "password": "string (required, min:8)",
    "password_confirmation": "string (required, must match password)",
    "phone": "string (required)",
    "email": "string (optional, email format)",
    "language": "string (optional, default: 'ar')"
}
```

**الاستجابة الناجحة (201):**

```json
{
    "success": true,
    "message": "تم إنشاء الحساب بنجاح",
    "data": {
        "user": {
            /* User object */
        },
        "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
    }
}
```

**الأخطاء:**

-   `422`: أخطاء التحقق أو المستخدم موجود مسبقاً

---

### الحصول على المستخدم الحالي

```
GET /api/auth/me
Authorization: Bearer {token}
```

**الاستجابة (200):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "username": "user001",
        "email": "user@example.com",
        "phone": "07501234567",
        "role": 0,
        "is_active": true,
        "live_access": false,
        "language": "ar"
    }
}
```

---

### تسجيل الخروج

```
POST /api/auth/logout
Authorization: Bearer {token}
```

**الاستجابة (200):**

```json
{
    "success": true,
    "message": "تم تسجيل الخروج بنجاح"
}
```

---

## 👤 الملف الشخصي (Profile)

### عرض الملف الشخصي

```
GET /api/user/profile
Authorization: Bearer {token}
```

**الاستجابة (200):**

```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "username": "user001",
            "email": "user@example.com",
            "phone": "07501234567",
            "role": 0,
            "is_active": true,
            "live_access": false,
            "language": "ar"
        },
        "subscription": {
            "expires_at": "2024-12-31T23:59:59Z",
            "is_active": true,
            "plan_name": "Monthly Plan"
        }
    }
}
```

---

### تحديث الملف الشخصي

```
PUT /api/user/profile
Authorization: Bearer {token}
Content-Type: application/json
```

**المعاملات:**

```json
{
    "email": "string (optional, email format)",
    "language": "string (optional, 'ar' or 'en')",
    "device_token": "string (optional, for push notifications)",
    "device_type": "string (optional, 'android' or 'ios' or 'web')",
    "device_name": "string (optional)"
}
```

**ملاحظة:** لا يمكن تحديث `username`, `phone`, `firstname`

**الاستجابة (200):**

```json
{
    "success": true,
    "message": "تم تحديث الملف الشخصي بنجاح",
    "data": {
        /* Updated user object */
    }
}
```

---

## 📡 الاشتراك (Subscription)

### مزامنة الاشتراك من Radius

```
POST /api/user/sync-subscription
Authorization: Bearer {token}
```

**الاستجابة (200):**

```json
{
    "success": true,
    "message": "تم مزامنة بيانات الاشتراك بنجاح",
    "data": {
        "subscription": {
            "expires_at": "2024-12-31T23:59:59Z",
            "is_active": true,
            "plan_name": "Monthly Plan"
        }
    }
}
```

**الأخطاء:**

-   `500`: فشل في مزامنة البيانات

---

### الحصول على بيانات الاشتراك من Radius مباشرة

```
GET /api/user/subscription-from-radius
Authorization: Bearer {token}
```

**الاستجابة (200):**

```json
{
    "success": true,
    "message": "تم جلب بيانات الاشتراك بنجاح",
    "data": {
        "subscription": {
            /* Subscription data from Radius */
        },
        "fetched_at": "2024-01-15T10:30:00Z",
        "source": "radius_api_direct"
    }
}
```

**ملاحظة:** هذه البيانات مباشرة من Radius ولا يتم حفظها في قاعدة البيانات

---

## 💳 طلبات الدفع (Payment Requests)

### إنشاء طلب دفع

```
POST /api/payment-requests
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**المعاملات:**

```
amount: number (required)
currency: string (required, 'USD' | 'SYP' | 'TRY')
period_months: number (required, min:0)
payment_method_id: number (required)
transaction_number: string (optional)
receipt_file: file (required, image file)
payment_date: string (optional, date format: Y-m-d)
```

**الاستجابة (201):**

```json
{
    "success": true,
    "message": "تم إرسال طلب الدفع بنجاح",
    "data": {
        "id": 1,
        "amount": 50000,
        "currency": "IQD",
        "status": 0,
        "payment_method": {
            /* Payment method object */
        },
        "receipt_file_url": "https://...",
        "created_at": "2024-01-15T10:30:00Z"
    }
}
```

---

### عرض طلبات الدفع الخاصة بالمستخدم

```
GET /api/payment-requests?status=0&currency=USD
Authorization: Bearer {token}
```

**المعاملات الاختيارية:**

-   `status`: number (0: pending, 1: approved, 2: rejected)
-   `currency`: string ('USD' | 'SYP' | 'TRY')

**الاستجابة (200):**

```json
{
    "success": true,
    "data": [
        /* Array of payment requests */
    ],
    "meta": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 75
    }
}
```

---

### عرض طلب دفع محدد

```
GET /api/payment-requests/{id}
Authorization: Bearer {token}
```

**الاستجابة (200):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "amount": 50000,
        "currency": "IQD",
        "status": 1,
        "status_label": "مقبول",
        "payment_method": {
            /* Payment method object */
        },
        "receipt_file_url": "https://...",
        "reviewer": {
            /* Reviewer user object */
        },
        "reject_reason": null,
        "notes": null,
        "created_at": "2024-01-15T10:30:00Z"
    }
}
```

**الأخطاء:**

-   `403`: غير مصرح لك بالوصول إلى هذا الطلب

---

## 💰 الإيرادات (Revenues)

### عرض إيرادات المستخدم

```
GET /api/user/revenues?from_date=2024-01-01&to_date=2024-01-31
Authorization: Bearer {token}
```

**المعاملات الاختيارية:**

-   `from_date`: string (date format: Y-m-d)
-   `to_date`: string (date format: Y-m-d)
-   `per_page`: number (default: 15)

**الاستجابة (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "amount": 50000,
            "currency": "IQD",
            "payment_type": "online",
            "payment_date": "2024-01-15",
            "payment_request": {
                /* Payment request object */
            }
        }
    ],
    "summary": {
        "total_revenue": 150000,
        "total_transactions": 3
    },
    "meta": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 15,
        "total": 3
    }
}
```

---

## 📢 الإشعارات (Notifications)

### عرض الإشعارات

```
GET /api/notifications?unread_only=false
Authorization: Bearer {token}
```

**المعاملات الاختيارية:**

-   `unread_only`: boolean (default: false)

**الاستجابة (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "تم قبول طلب الدفع",
            "body": "تم قبول طلب الدفع الخاص بك",
            "type": "system",
            "is_read": false,
            "created_at": "2024-01-15T10:30:00Z",
            "action_url": "/payment-requests/1",
            "action_text": "عرض الطلب"
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 75,
        "unread_count": 5
    }
}
```

---

### عدد الإشعارات غير المقروءة

```
GET /api/notifications/unread-count
Authorization: Bearer {token}
```

**الاستجابة (200):**

```json
{
    "success": true,
    "data": {
        "count": 5
    }
}
```

---

### تحديد إشعار كمقروء

```
POST /api/notifications/{id}/mark-as-read
Authorization: Bearer {token}
```

**الاستجابة (200):**

```json
{
    "success": true,
    "message": "تم تحديد الإشعار كمقروء"
}
```

---

### تحديد جميع الإشعارات كمقروءة

```
POST /api/notifications/mark-all-as-read
Authorization: Bearer {token}
```

**الاستجابة (200):**

```json
{
    "success": true,
    "message": "تم تحديد جميع الإشعارات كمقروءة"
}
```

---

## 🔧 طلبات الصيانة (Maintenance Requests)

### إنشاء طلب صيانة

```
POST /api/maintenance-requests
Authorization: Bearer {token}
Content-Type: application/json
```

**المعاملات:**

```json
{
    "address": "string (required)",
    "description": "string (required)"
}
```

**الاستجابة (201):**

```json
{
    "success": true,
    "message": "تم إرسال طلب الصيانة بنجاح",
    "data": {
        "id": 1,
        "address": "Baghdad, Al-Karada",
        "description": "No internet connection",
        "status": "pending",
        "subscription_data": {
            /* Subscription data from Radius */
        },
        "created_at": "2024-01-15T10:30:00Z"
    }
}
```

**الأخطاء:**

-   `500`: فشل في جلب بيانات الاشتراك من Radius

---

### عرض طلبات الصيانة

```
GET /api/maintenance-requests?status=pending
Authorization: Bearer {token}
```

**المعاملات الاختيارية:**

-   `status`: string ('pending' | 'submitted' | 'in_progress' | 'completed' | 'cancelled')

**الاستجابة (200):**

```json
{
    "success": true,
    "data": [
        /* Array of maintenance requests */
    ],
    "meta": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 75
    }
}
```

---

### عرض طلب صيانة محدد

```
GET /api/maintenance-requests/{id}
Authorization: Bearer {token}
```

**الاستجابة (200):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "address": "Baghdad, Al-Karada",
        "description": "No internet connection",
        "status": "in_progress",
        "notes": "Technician assigned",
        "assigned_to": {
            /* Assigned user object */
        },
        "created_at": "2024-01-15T10:30:00Z",
        "completed_at": null
    }
}
```

---

## 📺 المحتوى (Content)

### عرض السلايدات

```
GET /api/slides?target_audience=all&is_active=true
Authorization: Bearer {token} (optional - public endpoint)
```

**المعاملات الاختيارية:**

-   `target_audience`: string ('all' | 'active_users' | 'expired_users')
-   `is_active`: boolean

**الاستجابة (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "Slide Title",
            "image_url": "https://...",
            "link_url": "https://...",
            "target_audience": "all",
            "is_active": true
        }
    ]
}
```

---

### تتبع النقر على السلايد

```
POST /api/slides/{id}/track-click
Authorization: Bearer {token}
```

**الاستجابة (200):**

```json
{
    "success": true,
    "message": "تم تتبع النقر بنجاح"
}
```

---

### عرض البث المباشر

```
GET /api/live-streams?category=match&featured=true&is_active=true
Authorization: Bearer {token} (optional - public endpoint)
```

**المعاملات الاختيارية:**

-   `category`: string
-   `featured`: boolean
-   `is_active`: boolean

**الاستجابة (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "Live Stream Title",
            "stream_url": "https://...",
            "thumbnail_url": "https://...",
            "category": "match",
            "featured": true,
            "is_active": true
        }
    ]
}
```

---

### عرض بث مباشر محدد

```
GET /api/live-streams/{id}
Authorization: Bearer {token}
```

**الاستجابة (200):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "title": "Live Stream Title",
        "stream_url": "https://...",
        "thumbnail_url": "https://...",
        "description": "Stream description",
        "category": "match",
        "featured": true,
        "is_active": true
    }
}
```

---

## ⚙️ الإعدادات (Settings)

### عرض إعدادات التطبيق

```
GET /api/app-settings
Authorization: Bearer {token} (optional - public endpoint)
```

**الاستجابة (200):**

```json
{
    "success": true,
    "data": [
        {
            "key": "app_name",
            "value": "My App",
            "type": "string"
        }
    ]
}
```

---

### عرض إعداد محدد

```
GET /api/app-settings/key/{key}
Authorization: Bearer {token} (optional - public endpoint)
```

**الاستجابة (200):**

```json
{
    "success": true,
    "data": {
        "key": "app_name",
        "value": "My App",
        "type": "string"
    }
}
```

---

### عرض طرق الدفع المتاحة

```
GET /api/payment-methods
Authorization: Bearer {token} (optional - public endpoint)
```

**الاستجابة (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Zain Cash",
            "icon_url": "https://...",
            "instructions": "Send payment to...",
            "is_active": true
        }
    ]
}
```

---

## 📝 ملاحظات مهمة

1. **المصادقة:** جميع الـ endpoints (ما عدا المسموح) تتطلب `Authorization: Bearer {token}` في الـ header
2. **Content-Type:** استخدم `application/json` للـ JSON requests و `multipart/form-data` لرفع الملفات
3. **الصلاحيات:** جميع الـ endpoints المذكورة متاحة للمستخدمين العاديين (role: 0)
4. **الأخطاء:** جميع الأخطاء تعيد `success: false` مع `message` يوضح السبب
5. **Pagination:** الـ endpoints التي تعيد قوائم تدعم pagination عبر `meta` object
6. **التوقيت:** جميع التواريخ في صيغة ISO 8601 (UTC)
