# 📁 هيكل المشروع

## البنية الأساسية

```
radius-app-backend/
├── app/
│   ├── Constants/              # الثوابت
│   │   └── SystemNotificationTypes.php
│   ├── Enums/                  # التعدادات
│   │   ├── DeviceType.php
│   │   ├── NotificationPriority.php
│   │   ├── NotificationType.php
│   │   ├── PaymentRequestStatus.php
│   │   ├── SyncStatus.php
│   │   └── UserRole.php
│   ├── Http/
│   │   ├── Controllers/        # Controllers
│   │   │   ├── AuthController.php
│   │   │   ├── LiveStreamController.php
│   │   │   ├── NotificationController.php
│   │   │   ├── PaymentMethodController.php
│   │   │   ├── PaymentRequestController.php
│   │   │   ├── SlideController.php
│   │   │   └── UserController.php
│   │   ├── Middleware/         # Middleware
│   │   │   └── CheckRole.php
│   │   ├── Requests/           # Request Validation
│   │   │   ├── Auth/
│   │   │   ├── LiveStream/
│   │   │   ├── Notification/
│   │   │   ├── PaymentRequest/
│   │   │   ├── Slide/
│   │   │   └── User/
│   │   └── Resources/          # API Resources
│   │       ├── LiveStreamResource.php
│   │       ├── NotificationResource.php
│   │       ├── PaymentMethodResource.php
│   │       ├── PaymentRequestResource.php
│   │       ├── SlideResource.php
│   │       ├── UserResource.php
│   │       └── UserSubscriptionResource.php
│   ├── Models/                # Eloquent Models
│   │   ├── AppUser.php
│   │   ├── DeviceToken.php
│   │   ├── LiveStream.php
│   │   ├── LoginLog.php
│   │   ├── Notification.php
│   │   ├── PaymentMethod.php
│   │   ├── PaymentRequest.php
│   │   ├── Slide.php
│   │   ├── SyncLog.php
│   │   └── UserSubscription.php
│   ├── Policies/              # Authorization Policies
│   │   ├── LiveStreamPolicy.php
│   │   ├── NotificationPolicy.php
│   │   ├── PaymentRequestPolicy.php
│   │   └── SlidePolicy.php
│   ├── Providers/
│   │   └── AppServiceProvider.php
│   └── Services/              # Business Logic Services
│       ├── AuthService.php
│       ├── NotificationService.php
│       ├── PaymentService.php
│       └── RadiusSyncService.php
├── database/
│   └── migrations/            # Database Migrations
│       ├── 2024_01_01_000001_create_app_users_table.php
│       ├── 2024_01_01_000002_create_payment_methods_table.php
│       ├── 2024_01_01_000003_create_payment_requests_table.php
│       ├── 2024_01_01_000004_create_live_streams_table.php
│       ├── 2024_01_01_000005_create_slides_table.php
│       ├── 2024_01_01_000006_create_notifications_table.php
│       ├── 2024_01_01_000007_create_notification_user_table.php
│       ├── 2024_01_01_000008_create_user_subscriptions_table.php
│       ├── 2024_01_01_000009_create_device_tokens_table.php
│       ├── 2024_01_01_000010_create_login_logs_table.php
│       └── 2024_01_01_000011_create_sync_logs_table.php
├── routes/
│   └── api.php                # API Routes
├── config/
│   ├── auth.php               # Authentication Config
│   └── services.php           # Services Config (Radius)
└── bootstrap/
    └── app.php                # Application Bootstrap
```

## 📊 الجداول في قاعدة البيانات

### الجداول الأساسية

1. **app_users** - المستخدمون
2. **payment_methods** - طرق الدفع
3. **payment_requests** - طلبات الدفع
4. **live_streams** - البث المباشر
5. **slides** - السلايدات الإعلانية
6. **notifications** - الإشعارات
7. **notification_user** - ربط الإشعارات بالمستخدمين
8. **user_subscriptions** - اشتراكات المستخدمين
9. **device_tokens** - أجهزة المستخدمين
10. **login_logs** - سجلات الدخول
11. **sync_logs** - سجلات المزامنة

## 🔄 تدفق العمل

### 1. المصادقة (Authentication)

-   `AuthService` - معالجة تسجيل الدخول والتسجيل
-   `LoginLog` - تسجيل محاولات الدخول

### 2. طلبات الدفع (Payment Requests)

-   `PaymentService` - إدارة طلبات الدفع
-   `PaymentRequestController` - API endpoints
-   `PaymentRequestPolicy` - الصلاحيات

### 3. الإشعارات (Notifications)

-   `NotificationService` - إرسال وإدارة الإشعارات
-   `NotificationController` - API endpoints
-   دعم Push Notifications

### 4. المزامنة مع Radius

-   `RadiusSyncService` - مزامنة بيانات الاشتراكات
-   `UserSubscription` - تخزين بيانات الاشتراك
-   `SyncLog` - سجلات المزامنة

### 5. البث المباشر (Live Streams)

-   `LiveStreamController` - إدارة البث
-   دعم فئات متعددة (match, channel, event)
-   تتبع المشاهدات

### 6. السلايدات (Slides)

-   `SlideController` - إدارة السلايدات
-   دعم جماهير مستهدفة مختلفة
-   تتبع النقرات

## 🔐 الأمان

### Middleware

-   `auth:sanctum` - مصادقة API
-   `role` - التحقق من الصلاحيات

### Policies

-   `PaymentRequestPolicy` - صلاحيات طلبات الدفع
-   `LiveStreamPolicy` - صلاحيات البث
-   `SlidePolicy` - صلاحيات السلايدات
-   `NotificationPolicy` - صلاحيات الإشعارات

## 📝 الملاحظات

1. **Services**: تحتوي على Business Logic الرئيسي
2. **Controllers**: تحتوي على منطق الـ API فقط
3. **Requests**: التحقق من صحة البيانات
4. **Resources**: تنسيق استجابات الـ API
5. **Policies**: إدارة الصلاحيات

## 🚀 التطوير المستقبلي

-   [ ] إضافة Queue Jobs للإشعارات
-   [ ] إضافة Event Listeners
-   [ ] إضافة Caching Layer
-   [ ] إضافة Rate Limiting
-   [ ] إضافة API Documentation (Swagger)
-   [ ] إضافة Unit Tests
-   [ ] إضافة Integration Tests
