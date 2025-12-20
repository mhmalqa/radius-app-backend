# 📋 توثيق الميزات الجديدة

## 1. إدارة المستخدمين (User Management)

### الوصف

نظام شامل لإدارة المستخدمين يتيح للمدير والمحاسب البحث والفلترة وإدارة المستخدمين.

### الصلاحيات

-   **المدير (Admin)**: جميع الصلاحيات (عرض، تعديل، حذف، تغيير الأدوار)
-   **المحاسب (Accountant)**: عرض وتعديل المستخدمين العاديين فقط

### API Routes

#### 1. عرض جميع المستخدمين مع البحث والفلترة

```http
GET /api/admin/users
```

**المعاملات الاختيارية**:

-   `search` - البحث في username, phone, email
-   `role` - فلترة حسب الدور (0: user, 1: accountant, 2: admin)
-   `is_active` - فلترة حسب حالة الحساب (true/false)
-   `live_access` - فلترة حسب صلاحية البث (true/false)
-   `language` - فلترة حسب اللغة (ar/en)
-   `sort_by` - ترتيب حسب (created_at, username, etc.)
-   `sort_order` - اتجاه الترتيب (asc/desc)
-   `per_page` - عدد النتائج في الصفحة (افتراضي: 15)

**الاستجابة**:

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "username": "user001",
      "phone": "123456789",
      "email": "user@example.com",
      "role": 0,
      "is_active": true,
      "live_access": true,
      "language": "ar",
      "subscription": {...}
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 75
  }
}
```

#### 2. عرض تفاصيل مستخدم محدد

```http
GET /api/admin/users/{id}
```

#### 3. تحديث مستخدم

```http
PUT /api/admin/users/{id}
```

**المعاملات**:

```json
{
    "username": "new_username",
    "password": "new_password",
    "phone": "123456789",
    "email": "new@example.com",
    "language": "ar",
    "is_active": true,
    "live_access": true,
    "role": 0
}
```

**ملاحظات**:

-   عند تغيير `username`، يتم تحديث `radius_username` في الاشتراك تلقائياً
-   جميع الحقول اختيارية

#### 4. تفعيل/تعطيل الحساب

```http
POST /api/admin/users/{id}/toggle-active
```

#### 5. تفعيل/إلغاء صلاحية البث

```http
POST /api/admin/users/{id}/toggle-live-access
```

#### 6. تغيير دور المستخدم

```http
POST /api/admin/users/{id}/change-role
```

**المعاملات**:

```json
{
    "role": 1
}
```

**القيم**:

-   `0` - مستخدم عادي
-   `1` - محاسب
-   `2` - مدير

#### 7. حذف مستخدم (Admin فقط)

```http
DELETE /api/admin/users/{id}
```

---

## 2. طرق الدفع المحسّنة (Enhanced Payment Methods)

### الوصف

تم إضافة حقول QR Code و Code لطرق الدفع لتسهيل عملية الدفع.

### الحقول الجديدة

-   `qr_code` - مسار صورة QR Code أو بيانات QR Code
-   `code` - كود للنسخ (مثل رقم الحساب، رقم المحفظة)

### API Routes

#### 1. إنشاء طريقة دفع جديدة (Admin فقط)

```http
POST /api/admin/payment-methods
```

**المعاملات**:

```json
{
    "name": "Visa",
    "name_ar": "فيزا",
    "icon": "visa.png",
    "qr_code": "qr_codes/visa_qr.png",
    "code": "1234567890",
    "is_active": true,
    "instructions": "أرسل المبلغ إلى الحساب أعلاه",
    "sort_order": 1
}
```

#### 2. تحديث طريقة دفع (Admin فقط)

```http
PUT /api/admin/payment-methods/{id}
```

#### 3. حذف طريقة دفع (Admin فقط)

```http
DELETE /api/admin/payment-methods/{id}
```

### الاستجابة المحدثة

```json
{
    "id": 1,
    "name": "Visa",
    "name_ar": "فيزا",
    "icon": "http://example.com/storage/visa.png",
    "qr_code": "http://example.com/storage/qr_codes/visa_qr.png",
    "code": "1234567890",
    "is_active": true,
    "instructions": "أرسل المبلغ إلى الحساب أعلاه",
    "sort_order": 1
}
```

---

## 3. الإشعارات المحسّنة (Enhanced Notifications)

### الوصف

تم تحسين نظام الإشعارات لدعم فلترة المستخدمين المستلمين.

### API Routes

#### إنشاء إشعار (Admin فقط)

```http
POST /api/admin/notifications
```

**المعاملات**:

```json
{
    "title": "عنوان الإشعار",
    "body": "محتوى الإشعار",
    "type": "manual",
    "priority": 1,
    "action_url": "/subscription",
    "action_text": "تجديد الآن",
    "target_type": "active",
    "user_ids": [1, 2, 3]
}
```

**target_type**:

-   `all` - إرسال لجميع المستخدمين النشطين
-   `active` - إرسال للمستخدمين النشطين فقط (مع اشتراك صالح)
-   `specific` - إرسال لمستخدمين محددين (يجب تحديد `user_ids`)

**ملاحظات**:

-   عند `target_type = "specific"`، يجب تحديد `user_ids`
-   عند `target_type = "active"`، يتم إرسال الإشعار للمستخدمين الذين لديهم اشتراك صالح فقط

---

## 4. الإشعارات التلقائية (Automatic Notifications)

### الوصف

نظام إشعارات تلقائي لإشعارات انتهاء الاشتراك والبث المباشر.

### إشعارات انتهاء الاشتراك

يتم إرسال الإشعارات تلقائياً في الأوقات التالية:

-   **قبل يومين** - الساعة 9:00 صباحاً
-   **قبل يوم واحد** - الساعة 9:00 صباحاً
-   **قبل ساعة واحدة** - كل ساعة
-   **بعد الانتهاء بساعة** - كل ساعة

### إشعارات البث المباشر

#### 1. عند إضافة بث مباشر

-   إذا كان `start_time` في نفس اليوم، يتم إرسال إشعار فوري

#### 2. عند بدء البث

-   عند تفعيل البث (`is_active = true`)، يتم إرسال إشعار

#### 3. إشعار يوم المباراة

-   يتم إرسال إشعار في الساعة 8:00 صباحاً للبث المباشر المقرر في نفس اليوم

### الأوامر (Commands)

#### إرسال إشعارات انتهاء الاشتراك

```bash
php artisan notifications:subscription-expiry --hours=48  # قبل يومين
php artisan notifications:subscription-expiry --hours=24  # قبل يوم واحد
php artisan notifications:subscription-expiry --hours=1   # قبل ساعة
```

#### إرسال إشعارات بعد انتهاء الاشتراك

```bash
php artisan notifications:subscription-expired
```

#### إرسال إشعارات يوم البث المباشر

```bash
php artisan notifications:live-stream-day
```

### الجدولة (Scheduled Tasks)

تم إعداد الجدولة التلقائية في `routes/console.php`:

```php
// إشعارات انتهاء الاشتراك
Schedule::command('notifications:subscription-expiry --hours=48')->dailyAt('09:00');
Schedule::command('notifications:subscription-expiry --hours=24')->dailyAt('09:00');
Schedule::command('notifications:subscription-expiry --hours=1')->hourly();
Schedule::command('notifications:subscription-expired')->hourly();

// إشعارات البث المباشر
Schedule::command('notifications:live-stream-day')->dailyAt('08:00');
```

**ملاحظة**: تأكد من إعداد Cron Job على الخادم:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 5. ملخص Routes الجديدة

### إدارة المستخدمين (Admin/Accountant)

-   `GET /api/admin/users` - عرض جميع المستخدمين
-   `GET /api/admin/users/{id}` - عرض مستخدم محدد
-   `PUT /api/admin/users/{id}` - تحديث مستخدم
-   `POST /api/admin/users/{id}/toggle-active` - تفعيل/تعطيل الحساب
-   `POST /api/admin/users/{id}/toggle-live-access` - تفعيل/إلغاء صلاحية البث
-   `POST /api/admin/users/{id}/change-role` - تغيير دور المستخدم
-   `DELETE /api/admin/users/{id}` - حذف مستخدم (Admin فقط)

### إدارة طرق الدفع (Admin فقط)

-   `POST /api/admin/payment-methods` - إنشاء طريقة دفع
-   `PUT /api/admin/payment-methods/{id}` - تحديث طريقة دفع
-   `DELETE /api/admin/payment-methods/{id}` - حذف طريقة دفع

### الإشعارات (محسّنة)

-   `POST /api/admin/notifications` - إنشاء إشعار (مع `target_type`)

---

## 6. Migration الجديدة

### إضافة حقول QR Code و Code لطرق الدفع

```bash
php artisan migrate
```

**الملف**: `2024_12_10_100001_add_qr_code_to_payment_methods_table.php`

**الحقول المضافة**:

-   `qr_code` (string, nullable, 500 chars)
-   `code` (string, nullable, 100 chars)

---

## 7. أمثلة الاستخدام

### مثال 1: البحث عن مستخدم

```http
GET /api/admin/users?search=ahmed&role=0&is_active=true
```

### مثال 2: إرسال إشعار للمستخدمين النشطين فقط

```http
POST /api/admin/notifications
Content-Type: application/json
Authorization: Bearer {token}

{
  "title": "تحديث مهم",
  "body": "تم تحديث النظام",
  "target_type": "active"
}
```

### مثال 3: إرسال إشعار لمستخدمين محددين

```http
POST /api/admin/notifications
Content-Type: application/json
Authorization: Bearer {token}

{
  "title": "إشعار خاص",
  "body": "هذا إشعار خاص",
  "target_type": "specific",
  "user_ids": [1, 5, 10]
}
```

### مثال 4: تغيير دور مستخدم

```http
POST /api/admin/users/5/change-role
Content-Type: application/json
Authorization: Bearer {token}

{
  "role": 1
}
```

---

## 8. ملاحظات مهمة

1. **الصلاحيات**: تأكد من أن المستخدم لديه الصلاحيات المناسبة قبل الوصول للـ Routes
2. **Cron Job**: يجب إعداد Cron Job لتشغيل Scheduled Tasks
3. **الإشعارات التلقائية**: تعمل فقط للمستخدمين النشطين (`is_active = true`)
4. **البث المباشر**: الإشعارات تُرسل فقط للمستخدمين الذين لديهم `live_access = true`
5. **تغيير Username**: عند تغيير username، يتم تحديث `radius_username` في الاشتراك تلقائياً

---

## 9. الاختبار

### اختبار إدارة المستخدمين

```bash
# عرض جميع المستخدمين
curl -X GET "http://localhost/api/admin/users" \
  -H "Authorization: Bearer {token}"

# البحث عن مستخدم
curl -X GET "http://localhost/api/admin/users?search=ahmed" \
  -H "Authorization: Bearer {token}"
```

### اختبار الإشعارات

#### 1. اختبار إنشاء إشعار يدوي

**إرسال إشعار لجميع المستخدمين النشطين:**

```bash
curl -X POST "http://localhost/api/admin/notifications" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "إشعار تجريبي",
    "body": "هذا إشعار تجريبي لجميع المستخدمين",
    "type": "manual",
    "target_type": "all"
  }'
```

**إرسال إشعار للمستخدمين النشطين فقط (مع اشتراك صالح):**

```bash
curl -X POST "http://localhost/api/admin/notifications" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "إشعار للمشتركين",
    "body": "هذا إشعار للمستخدمين النشطين فقط",
    "type": "manual",
    "target_type": "active",
    "priority": 1,
    "action_url": "/subscription",
    "action_text": "تجديد الآن"
  }'
```

**إرسال إشعار لمستخدمين محددين:**

```bash
curl -X POST "http://localhost/api/admin/notifications" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "إشعار خاص",
    "body": "هذا إشعار لمستخدمين محددين",
    "type": "manual",
    "target_type": "specific",
    "user_ids": [1, 2, 3]
  }'
```

#### 2. اختبار عرض الإشعارات (من جهة المستخدم)

**عرض جميع الإشعارات:**

```bash
curl -X GET "http://localhost/api/notifications" \
  -H "Authorization: Bearer {user_token}"
```

**عرض الإشعارات غير المقروءة فقط:**

```bash
curl -X GET "http://localhost/api/notifications?unread_only=true" \
  -H "Authorization: Bearer {user_token}"
```

**الحصول على عدد الإشعارات غير المقروءة:**

```bash
curl -X GET "http://localhost/api/notifications/unread-count" \
  -H "Authorization: Bearer {user_token}"
```

**تحديد إشعار كمقروء:**

```bash
curl -X POST "http://localhost/api/notifications/1/mark-as-read" \
  -H "Authorization: Bearer {user_token}"
```

**تحديد جميع الإشعارات كمقروءة:**

```bash
curl -X POST "http://localhost/api/notifications/mark-all-as-read" \
  -H "Authorization: Bearer {user_token}"
```

#### 3. اختبار الأوامر التلقائية

**اختبار إشعارات انتهاء الاشتراك:**

```bash
# قبل يومين (48 ساعة)
php artisan notifications:subscription-expiry --hours=48

# قبل يوم واحد (24 ساعة)
php artisan notifications:subscription-expiry --hours=24

# قبل ساعة واحدة
php artisan notifications:subscription-expiry --hours=1
```

**اختبار إشعارات بعد انتهاء الاشتراك:**

```bash
php artisan notifications:subscription-expired
```

**اختبار إشعارات يوم البث المباشر:**

```bash
php artisan notifications:live-stream-day
```

#### 4. اختبار قاعدة البيانات

**التحقق من إنشاء الإشعار:**

```sql
SELECT * FROM notifications ORDER BY id DESC LIMIT 1;
```

**التحقق من ربط الإشعار بالمستخدمين:**

```sql
SELECT
    n.id,
    n.title,
    n.body,
    nu.user_id,
    nu.is_read,
    nu.is_sent,
    nu.sent_at,
    nu.read_at
FROM notifications n
JOIN notification_user nu ON n.id = nu.notification_id
WHERE n.id = 1;
```

**التحقق من عدد المستلمين:**

```sql
SELECT
    notification_id,
    COUNT(*) as recipients_count,
    SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as read_count,
    SUM(CASE WHEN is_sent = 1 THEN 1 ELSE 0 END) as sent_count
FROM notification_user
WHERE notification_id = 1
GROUP BY notification_id;
```

#### 5. اختبار باستخدام Postman

1. **إنشاء Request جديد:**

    - Method: `POST`
    - URL: `http://localhost/api/admin/notifications`
    - Headers:
        - `Authorization: Bearer {admin_token}`
        - `Content-Type: application/json`
    - Body (JSON):
        ```json
        {
            "title": "إشعار تجريبي",
            "body": "محتوى الإشعار",
            "type": "manual",
            "target_type": "all",
            "priority": 1
        }
        ```

2. **اختبار الاستجابة:**
    - يجب أن تكون `success: true`
    - يجب أن يحتوي `data` على تفاصيل الإشعار
    - يجب أن يكون `id` موجود

#### 6. اختبار السيناريوهات المختلفة

**سيناريو 1: إشعار بدون مستخدمين نشطين**

-   تأكد من عدم وجود مستخدمين نشطين (`is_active = false`)
-   أرسل إشعار بـ `target_type: "all"`
-   يجب أن يُنشأ الإشعار ولكن لا يتم ربطه بأي مستخدم

**سيناريو 2: إشعار لمستخدمين محددين غير موجودين**

-   أرسل إشعار بـ `target_type: "specific"` و `user_ids: [999, 1000]`
-   يجب أن يُنشأ الإشعار ولكن لا يتم ربطه بأي مستخدم

**سيناريو 3: إشعار لمستخدم غير نشط**

-   أرسل إشعار لمستخدم مع `is_active = false`
-   يجب ألا يتم ربط الإشعار بهذا المستخدم

### اختبار الأوامر

```bash
# اختبار إشعارات انتهاء الاشتراك
php artisan notifications:subscription-expiry --hours=24

# اختبار إشعارات البث المباشر
php artisan notifications:live-stream-day
```

---

## 10. دمج Firebase Cloud Messaging (FCM)

### لماذا Firebase Cloud Messaging؟

Firebase Cloud Messaging (FCM) هو الحل الأمثل لإرسال الإشعارات الفورية للأسباب التالية:

1. **مجاني**: حتى 100 مليون رسالة شهرياً
2. **موثوق**: بنية تحتية قوية من Google
3. **متعدد المنصات**: يعمل على Android و iOS و Web
4. **سهل التكامل**: مكتبات جاهزة للاستخدام
5. **تتبع متقدم**: إحصائيات وصول وفتح الإشعارات

### خطوات التكامل

#### 1. تثبيت حزمة Laravel FCM

```bash
composer require laravel-notification-channels/fcm
```

أو استخدام حزمة بديلة:

```bash
composer require kreait/laravel-firebase
```

#### 2. إعداد Firebase Project

1. اذهب إلى [Firebase Console](https://console.firebase.google.com/)
2. أنشئ مشروع جديد أو استخدم مشروع موجود
3. أضف تطبيق Android/iOS/Web
4. احصل على:
    - **Server Key** (من Cloud Messaging settings)
    - **Project ID**
    - **Service Account JSON** (من Project Settings > Service Accounts)

#### 3. إضافة متغيرات البيئة

أضف إلى ملف `.env`:

```env
FCM_SERVER_KEY=your_server_key_here
FCM_PROJECT_ID=your_project_id_here
FCM_SERVICE_ACCOUNT_PATH=storage/app/firebase-service-account.json
```

#### 4. تحديث ملف `config/services.php`

```php
'fcm' => [
    'server_key' => env('FCM_SERVER_KEY'),
    'project_id' => env('FCM_PROJECT_ID'),
    'service_account_path' => env('FCM_SERVICE_ACCOUNT_PATH'),
],
```

#### 5. إنشاء Service Class للإشعارات

أنشئ ملف `app/Services/FcmService.php`:

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    protected string $serverKey;
    protected string $fcmUrl = 'https://fcm.googleapis.com/fcm/send';

    public function __construct()
    {
        $this->serverKey = config('services.fcm.server_key');
    }

    /**
     * Send notification to single device.
     */
    public function sendToDevice(string $deviceToken, array $notification, array $data = []): bool
    {
        $payload = [
            'to' => $deviceToken,
            'notification' => [
                'title' => $notification['title'],
                'body' => $notification['body'],
                'sound' => $notification['sound'] ?? 'default',
                'badge' => $notification['badge'] ?? null,
            ],
            'data' => $data,
            'priority' => 'high',
        ];

        return $this->send($payload);
    }

    /**
     * Send notification to multiple devices.
     */
    public function sendToDevices(array $deviceTokens, array $notification, array $data = []): array
    {
        $results = [];

        foreach ($deviceTokens as $token) {
            $results[$token] = $this->sendToDevice($token, $notification, $data);
        }

        return $results;
    }

    /**
     * Send notification using topic.
     */
    public function sendToTopic(string $topic, array $notification, array $data = []): bool
    {
        $payload = [
            'to' => '/topics/' . $topic,
            'notification' => [
                'title' => $notification['title'],
                'body' => $notification['body'],
                'sound' => $notification['sound'] ?? 'default',
            ],
            'data' => $data,
            'priority' => 'high',
        ];

        return $this->send($payload);
    }

    /**
     * Send FCM request.
     */
    protected function send(array $payload): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $payload);

            if ($response->successful()) {
                Log::info('FCM notification sent successfully', [
                    'response' => $response->json(),
                ]);
                return true;
            }

            Log::error('FCM notification failed', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('FCM notification exception', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
```

#### 6. تحديث `NotificationService.php`

حدّث دالة `sendPushNotification`:

```php
protected function sendPushNotification(Notification $notification, AppUser $user): void
{
    try {
        $deviceTokens = $user->deviceTokens()->where('is_active', true)->get();

        if ($deviceTokens->isEmpty()) {
            Log::info('No active device tokens for user', [
                'user_id' => $user->id,
            ]);
            return;
        }

        $fcmService = app(\App\Services\FcmService::class);

        $notificationData = [
            'title' => $notification->title,
            'body' => $notification->body,
            'sound' => $notification->sound ?? 'default',
            'badge' => $notification->badge,
        ];

        $data = [
            'notification_id' => (string) $notification->id,
            'type' => $notification->type,
            'action_url' => $notification->action_url ?? '',
            'action_text' => $notification->action_text ?? '',
        ];

        $tokens = $deviceTokens->pluck('device_token')->toArray();
        $results = $fcmService->sendToDevices($tokens, $notificationData, $data);

        // Update sent status
        $allSent = !in_array(false, $results, true);
        $notification->users()->updateExistingPivot($user->id, [
            'is_sent' => $allSent,
            'sent_at' => now(),
        ]);

        if (!$allSent) {
            $failedTokens = array_keys(array_filter($results, fn($result) => !$result));
            Log::warning('Some FCM notifications failed', [
                'user_id' => $user->id,
                'failed_tokens' => $failedTokens,
            ]);
        }
    } catch (\Exception $e) {
        Log::error('Failed to send push notification', [
            'notification_id' => $notification->id,
            'user_id' => $user->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        $notification->users()->updateExistingPivot($user->id, [
            'send_error' => $e->getMessage(),
        ]);
    }
}
```

#### 7. إنشاء API Endpoint لتسجيل Device Token

أضف Route جديد في `routes/api.php`:

```php
// في middleware('auth:sanctum')
Route::post('/user/device-token', [UserController::class, 'registerDeviceToken']);
```

أضف Method في `UserController`:

```php
public function registerDeviceToken(Request $request): JsonResponse
{
    $request->validate([
        'device_token' => 'required|string',
        'device_type' => 'required|in:android,ios,web',
        'device_name' => 'nullable|string|max:255',
    ]);

    $user = $request->user();

    // Deactivate old tokens for this device
    DeviceToken::where('user_id', $user->id)
        ->where('device_type', $request->device_type)
        ->update(['is_active' => false]);

    // Create or update device token
    $deviceToken = DeviceToken::updateOrCreate(
        [
            'user_id' => $user->id,
            'device_token' => $request->device_token,
        ],
        [
            'device_type' => $request->device_type,
            'device_name' => $request->device_name ?? 'Unknown Device',
            'is_active' => true,
            'last_used_at' => now(),
        ]
    );

    return response()->json([
        'success' => true,
        'message' => 'تم تسجيل الجهاز بنجاح',
        'data' => $deviceToken,
    ]);
}
```

### أفضل الممارسات

#### 1. إدارة Device Tokens

-   **تحديث التوكنات**: تحديث التوكن عند كل تسجيل دخول
-   **إلغاء تفعيل القديمة**: إلغاء تفعيل التوكنات القديمة عند تسجيل جهاز جديد
-   **تنظيف دوري**: حذف التوكنات غير النشطة لفترة طويلة

#### 2. معالجة الأخطاء

-   **Invalid Token**: حذف التوكن من قاعدة البيانات
-   **Unregistered Device**: إلغاء تفعيل التوكن
-   **Rate Limiting**: إضافة تأخير عند إرسال كميات كبيرة

#### 3. تحسين الأداء

-   **Batch Sending**: إرسال الإشعارات في مجموعات
-   **Queue Jobs**: استخدام Laravel Queues للإرسال غير المتزامن
-   **Caching**: تخزين التوكنات في Cache

#### 4. استخدام Laravel Queues

أنشئ Job للإرسال غير المتزامن:

```bash
php artisan make:job SendPushNotification
```

```php
<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\AppUser;
use App\Services\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Notification $notification,
        public AppUser $user
    ) {}

    public function handle(FcmService $fcmService): void
    {
        // إرسال الإشعار
        // ...
    }
}
```

ثم في `NotificationService`:

```php
SendPushNotification::dispatch($notification, $user);
```

### اختبار Firebase Integration

#### 1. اختبار تسجيل Device Token

```bash
curl -X POST "http://localhost/api/user/device-token" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "device_token": "test_fcm_token_12345",
    "device_type": "android",
    "device_name": "Samsung Galaxy"
  }'
```

#### 2. اختبار إرسال إشعار

بعد تسجيل التوكن، أرسل إشعار وتحقق من:

-   وصول الإشعار للجهاز
-   تحديث `is_sent` في قاعدة البيانات
-   عدم وجود أخطاء في Logs

#### 3. اختبار مع Firebase Console

استخدم Firebase Console لإرسال إشعار تجريبي والتحقق من:

-   صحة التوكن
-   وصول الإشعار
-   تنسيق البيانات

### نصائح إضافية

1. **استخدام Topics**: للإشعارات العامة (مثل: `all_users`, `active_subscribers`)
2. **Conditional Sending**: إرسال حسب شروط معينة
3. **Scheduled Notifications**: جدولة الإشعارات للمستقبل
4. **Analytics**: تتبع معدلات الفتح والتفاعل
5. **A/B Testing**: اختبار محتوى الإشعارات

---

## 11. ملفات التوثيق الإضافية

للمزيد من التفاصيل، راجع الملفات التالية:

-   **[FIREBASE_INTEGRATION_GUIDE.md](./FIREBASE_INTEGRATION_GUIDE.md)** - دليل شامل لدمج Firebase Cloud Messaging
-   **[NOTIFICATION_TESTING_EXAMPLES.md](./NOTIFICATION_TESTING_EXAMPLES.md)** - أمثلة شاملة لاختبار الإشعارات

---

تم إنشاء جميع الميزات المطلوبة بنجاح! 🎉
