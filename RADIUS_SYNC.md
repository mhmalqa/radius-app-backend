# 🔄 دليل المزامنة مع Radius

## 📋 نظرة عامة

تم تكوين خدمة المزامنة للاتصال مع Radius API لجلب بيانات المستخدمين والاشتراكات.

## 🔗 رابط API

```
GET http://38.156.75.137:3031/radiusmanager/USERS/dash/test.php?input={username}
```

### Headers

```
Authorization: Bearer APP2025M
Accept: application/json
```

## ⚙️ الإعدادات

### ملف `.env`

```env
RADIUS_API_URL=http://38.156.75.137:3031
RADIUS_API_KEY=APP2025M
```

القيم الافتراضية موجودة في `config/services.php`، لذا يمكنك تركها فارغة إذا كانت نفس القيم.

## 📊 استخدام الخدمة

### 1. مزامنة مستخدم واحد

```php
use App\Services\RadiusSyncService;
use App\Models\AppUser;

$radiusSyncService = app(RadiusSyncService::class);
$user = AppUser::find(1);

$success = $radiusSyncService->syncUserSubscription($user);
```

### 2. مزامنة جميع المستخدمين

```php
use App\Services\RadiusSyncService;

$radiusSyncService = app(RadiusSyncService::class);
$syncedCount = $radiusSyncService->syncAllUsers();
```

### 3. عبر API Endpoint

```http
POST /api/user/sync-subscription
Authorization: Bearer {token}
```

## 📥 تنسيق الاستجابة من Radius API

### التنسيق الفعلي من Radius

```json
{
    "status": "success",
    "username": "001",
    "firstname": "الشيخ احمد الطحبش",
    "mobile": "",
    "service": "2M-PPP",
    "price": "8.000000",
    "expiration": "2026-01-02 10:00:00",
    "active": true,
    "online": false,
    "speed": {
        "download_kbps": 2097152,
        "upload_kbps": 20971520,
        "download_mbps": 2097.2,
        "upload_mbps": 20971.5
    },
    "usage": {
        "download_MB": 8371.59,
        "upload_MB": 67316.8,
        "total_MB": 75688.39
    }
}
```

### التحويل التلقائي

الخدمة تقوم بتحويل البيانات تلقائياً:

| حقل Radius       | حقل قاعدة البيانات | التحويل               |
| ---------------- | ------------------ | --------------------- |
| `expiration`     | `expiration_at`    | تحويل التاريخ         |
| `price`          | `balance`          | تحويل إلى decimal     |
| `service`        | `plan_name`        | نص مباشر              |
| `usage.total_MB` | `data_used`        | تحويل من MB إلى bytes |
| `active`         | -                  | (يمكن إضافته لاحقاً)  |

### مثال على التحويل

```php
// من Radius:
"usage": { "total_MB": 75688.39 }

// إلى قاعدة البيانات:
"data_used": 79352827904  // 75688.39 * 1024 * 1024 bytes
```

### تنسيقات البيانات المدعومة

#### التاريخ

-   Date String: `"2026-01-02 10:00:00"` ✅ (التنسيق المستخدم)
-   Timestamp (Unix): `1704067199`
-   Date String: `"2024-12-31"`

#### البيانات (Data)

-   MB (decimal): `75688.39` ✅ (التنسيق المستخدم من `usage.total_MB`)
-   Bytes: `10737418240`
-   KB: `"10485760KB"`
-   MB: `"10240MB"`
-   GB: `"10GB"`

## 🔧 معالجة الأخطاء

### تسجيل الأخطاء

جميع الأخطاء يتم تسجيلها في:

-   `storage/logs/laravel.log`
-   جدول `sync_logs` في قاعدة البيانات

### حالات الخطأ

1. **فشل الاتصال**: يتم تسجيل الخطأ وإرجاع `false`
2. **استجابة غير صحيحة**: يتم تسجيل التحذير وإرجاع `null`
3. **خطأ في التحويل**: يتم استخدام القيم الافتراضية

## 📝 مثال على الاستخدام الكامل

```php
use App\Services\RadiusSyncService;
use App\Models\AppUser;

// في Controller أو Service
public function syncUserData(AppUser $user)
{
    $radiusSyncService = app(RadiusSyncService::class);

    $success = $radiusSyncService->syncUserSubscription($user);

    if ($success) {
        $subscription = $user->subscription;

        return [
            'expiration_at' => $subscription->expiration_at,
            'balance' => $subscription->balance,
            'data_usage' => $subscription->getDataUsagePercentage(),
            'remaining_data' => $subscription->getRemainingData(),
        ];
    }

    return ['error' => 'Failed to sync'];
}
```

## 🔍 فحص سجلات المزامنة

```php
use App\Models\SyncLog;

// آخر 10 سجلات
$logs = SyncLog::latest()->take(10)->get();

// سجلات فاشلة
$failedLogs = SyncLog::where('status', 1)->latest()->get();

// سجلات مستخدم معين
$userLogs = SyncLog::where('user_id', $userId)->latest()->get();
```

## ⚡ تحسينات مقترحة

1. **Caching**: تخزين مؤقت للبيانات لتقليل الطلبات
2. **Queue Jobs**: استخدام Queue للمزامنة المجمعة
3. **Retry Logic**: إعادة المحاولة التلقائية عند الفشل
4. **Webhooks**: استقبال تحديثات من Radius مباشرة

## 🐛 حل المشاكل

### المشكلة: فشل الاتصال

**الحل**:

1. تحقق من إعدادات `RADIUS_API_URL`
2. تحقق من الاتصال بالشبكة
3. تحقق من `Authorization` header

### المشكلة: استجابة غير صحيحة

**الحل**:

1. تحقق من تنسيق الاستجابة من Radius
2. راجع ملفات الـ Logs
3. أضف معالجة مخصصة في `normalizeRadiusResponse()`

### المشكلة: بيانات غير صحيحة

**الحل**:

1. تحقق من أسماء الحقول في الاستجابة
2. أضف mapping جديد في `normalizeRadiusResponse()`
3. تحقق من `parseDate()` و `parseBytes()` للتحويلات

## 📞 الدعم

للمزيد من المعلومات:

-   راجع `app/Services/RadiusSyncService.php`
-   راجع سجلات المزامنة في `sync_logs`
-   راجع ملفات الـ Logs في `storage/logs`
