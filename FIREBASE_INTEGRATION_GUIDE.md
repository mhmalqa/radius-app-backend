# 🔥 دليل دمج Firebase Cloud Messaging (FCM)

## 📋 المحتويات

1. [نظرة عامة](#نظرة-عامة)
2. [إعداد Firebase Project](#إعداد-firebase-project)
3. [تثبيت المكتبات](#تثبيت-المكتبات)
4. [إعداد Laravel](#إعداد-laravel)
5. [تنفيذ الكود](#تنفيذ-الكود)
6. [اختبار التكامل](#اختبار-التكامل)
7. [أفضل الممارسات](#أفضل-الممارسات)
8. [استكشاف الأخطاء](#استكشاف-الأخطاء)

---

## نظرة عامة

Firebase Cloud Messaging (FCM) هو خدمة مجانية لإرسال الإشعارات الفورية للمستخدمين على Android و iOS و Web.

### المميزات

-   ✅ **مجاني**: حتى 100 مليون رسالة شهرياً
-   ✅ **موثوق**: بنية تحتية من Google
-   ✅ **متعدد المنصات**: Android, iOS, Web
-   ✅ **سهل التكامل**: مكتبات جاهزة
-   ✅ **تتبع متقدم**: إحصائيات وصول وفتح

---

## إعداد Firebase Project

### الخطوة 1: إنشاء Firebase Project

1. اذهب إلى [Firebase Console](https://console.firebase.google.com/)
2. انقر على **"Add project"** أو **"إنشاء مشروع"**
3. أدخل اسم المشروع (مثلاً: `radius-app`)
4. اختر **Google Analytics** (اختياري)
5. انقر **"Create project"**

### الخطوة 2: إضافة تطبيق

#### Android

1. انقر على أيقونة Android
2. أدخل **Package name** (مثلاً: `com.yourapp.radius`)
3. انقر **"Register app"**
4. حمّل ملف `google-services.json`
5. انقر **"Next"** حتى النهاية

#### iOS

1. انقر على أيقونة iOS
2. أدخل **Bundle ID** (مثلاً: `com.yourapp.radius`)
3. انقر **"Register app"**
4. حمّل ملف `GoogleService-Info.plist`
5. انقر **"Next"** حتى النهاية

#### Web

1. انقر على أيقونة Web
2. أدخل **App nickname**
3. انقر **"Register app"**
4. انسخ **Firebase configuration**
5. انقر **"Next"** حتى النهاية

### الخطوة 3: الحصول على Server Key

1. في Firebase Console، اذهب إلى **Project Settings** (⚙️)
2. اختر **Cloud Messaging** tab
3. انسخ **Server key** (ستحتاجه لاحقاً)

### الخطوة 4: إنشاء Service Account

1. في **Project Settings**، اختر **Service Accounts** tab
2. انقر **"Generate new private key"**
3. حمّل ملف JSON واحفظه في `storage/app/firebase-service-account.json`

---

## تثبيت المكتبات

### الطريقة 1: استخدام HTTP Client (مبسطة)

لا تحتاج مكتبات إضافية، فقط استخدم Laravel HTTP Client:

```bash
# لا حاجة لتثبيت مكتبات إضافية
```

### الطريقة 2: استخدام Laravel Firebase Package

```bash
composer require kreait/laravel-firebase
```

### الطريقة 3: استخدام FCM Package

```bash
composer require laravel-notification-channels/fcm
```

**نوصي بالطريقة 1** لأنها أبسط ولا تحتاج مكتبات إضافية.

---

## إعداد Laravel

### 1. إضافة متغيرات البيئة

أضف إلى ملف `.env`:

```env
# Firebase Cloud Messaging
FCM_SERVER_KEY=AAAAxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
FCM_PROJECT_ID=your-project-id
FCM_SERVICE_ACCOUNT_PATH=storage/app/firebase-service-account.json
```

### 2. تحديث `config/services.php`

```php
<?php

return [
    // ... existing services ...

    'fcm' => [
        'server_key' => env('FCM_SERVER_KEY'),
        'project_id' => env('FCM_PROJECT_ID'),
        'service_account_path' => env('FCM_SERVICE_ACCOUNT_PATH'),
        'fcm_url' => 'https://fcm.googleapis.com/fcm/send',
    ],
];
```

### 3. إنشاء FcmService

أنشئ ملف `app/Services/FcmService.php`:

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    protected string $serverKey;
    protected string $fcmUrl;

    public function __construct()
    {
        $this->serverKey = config('services.fcm.server_key');
        $this->fcmUrl = config('services.fcm.fcm_url', 'https://fcm.googleapis.com/fcm/send');
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
     * Send notification to multiple devices (up to 1000).
     */
    public function sendToDevices(array $deviceTokens, array $notification, array $data = []): array
    {
        $results = [];

        // FCM supports up to 1000 tokens per request
        $chunks = array_chunk($deviceTokens, 1000);

        foreach ($chunks as $chunk) {
            $payload = [
                'registration_ids' => $chunk,
                'notification' => [
                    'title' => $notification['title'],
                    'body' => $notification['body'],
                    'sound' => $notification['sound'] ?? 'default',
                    'badge' => $notification['badge'] ?? null,
                ],
                'data' => $data,
                'priority' => 'high',
            ];

            $response = $this->send($payload);

            // Map results to tokens
            foreach ($chunk as $token) {
                $results[$token] = $response;
            }
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
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'key=' . $this->serverKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->fcmUrl, $payload);

            if ($response->successful()) {
                $responseData = $response->json();

                Log::info('FCM notification sent successfully', [
                    'response' => $responseData,
                ]);

                // Check for errors in batch response
                if (isset($responseData['results'])) {
                    foreach ($responseData['results'] as $index => $result) {
                        if (isset($result['error'])) {
                            Log::warning('FCM token error', [
                                'token_index' => $index,
                                'error' => $result['error'],
                            ]);
                        }
                    }
                }

                return true;
            }

            Log::error('FCM notification failed', [
                'status' => $response->status(),
                'response' => $response->body(),
                'payload' => $payload,
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('FCM notification exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Validate device token.
     */
    public function validateToken(string $deviceToken): bool
    {
        // Basic validation - FCM tokens are typically 152+ characters
        return strlen($deviceToken) > 50;
    }
}
```

---

## تنفيذ الكود

### 1. تحديث NotificationService

حدّث `app/Services/NotificationService.php`:

```php
protected function sendPushNotification(Notification $notification, AppUser $user): void
{
    try {
        $deviceTokens = $user->deviceTokens()
            ->where('is_active', true)
            ->get();

        if ($deviceTokens->isEmpty()) {
            Log::info('No active device tokens for user', [
                'user_id' => $user->id,
            ]);

            // Mark as sent even without tokens (notification saved in DB)
            $notification->users()->updateExistingPivot($user->id, [
                'is_sent' => true,
                'sent_at' => now(),
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
            'priority' => (string) ($notification->priority ?? 0),
        ];

        $tokens = $deviceTokens->pluck('device_token')->toArray();
        $results = $fcmService->sendToDevices($tokens, $notificationData, $data);

        // Check results
        $allSent = true;
        $failedTokens = [];

        foreach ($results as $token => $success) {
            if (!$success) {
                $allSent = false;
                $failedTokens[] = $token;
            }
        }

        // Update sent status
        $notification->users()->updateExistingPivot($user->id, [
            'is_sent' => $allSent,
            'sent_at' => now(),
        ]);

        if (!$allSent) {
            Log::warning('Some FCM notifications failed', [
                'user_id' => $user->id,
                'notification_id' => $notification->id,
                'failed_tokens' => $failedTokens,
            ]);

            // Deactivate failed tokens
            DeviceToken::whereIn('device_token', $failedTokens)
                ->update(['is_active' => false]);
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

### 2. إنشاء API لتسجيل Device Token

أضف Route في `routes/api.php`:

```php
// في middleware('auth:sanctum')
Route::prefix('user')->group(function () {
    // ... existing routes ...
    Route::post('/device-token', [UserController::class, 'registerDeviceToken']);
});
```

أضف Method في `app/Http/Controllers/UserController.php`:

```php
use App\Models\DeviceToken;
use App\Services\FcmService;

public function registerDeviceToken(Request $request): JsonResponse
{
    $validated = $request->validate([
        'device_token' => 'required|string|max:500',
        'device_type' => 'required|in:android,ios,web',
        'device_name' => 'nullable|string|max:255',
    ]);

    $user = $request->user();

    // Validate token format
    $fcmService = app(FcmService::class);
    if (!$fcmService->validateToken($validated['device_token'])) {
        return response()->json([
            'success' => false,
            'message' => 'صيغة التوكن غير صحيحة',
        ], 422);
    }

    // Deactivate old tokens for this device type (optional: keep multiple devices)
    // DeviceToken::where('user_id', $user->id)
    //     ->where('device_type', $validated['device_type'])
    //     ->update(['is_active' => false]);

    // Create or update device token
    $deviceToken = DeviceToken::updateOrCreate(
        [
            'user_id' => $user->id,
            'device_token' => $validated['device_token'],
        ],
        [
            'device_type' => $validated['device_type'],
            'device_name' => $validated['device_name'] ?? 'Unknown Device',
            'is_active' => true,
            'last_used_at' => now(),
        ]
    );

    return response()->json([
        'success' => true,
        'message' => 'تم تسجيل الجهاز بنجاح',
        'data' => [
            'id' => $deviceToken->id,
            'device_type' => $deviceToken->device_type,
            'device_name' => $deviceToken->device_name,
            'is_active' => $deviceToken->is_active,
        ],
    ]);
}
```

### 3. استخدام Laravel Queues (اختياري لكن موصى به)

أنشئ Job:

```bash
php artisan make:job SendPushNotificationJob
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
use Illuminate\Support\Facades\Log;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    public function __construct(
        public Notification $notification,
        public AppUser $user
    ) {}

    public function handle(FcmService $fcmService): void
    {
        $deviceTokens = $this->user->deviceTokens()
            ->where('is_active', true)
            ->get();

        if ($deviceTokens->isEmpty()) {
            return;
        }

        $notificationData = [
            'title' => $this->notification->title,
            'body' => $this->notification->body,
            'sound' => $this->notification->sound ?? 'default',
            'badge' => $this->notification->badge,
        ];

        $data = [
            'notification_id' => (string) $this->notification->id,
            'type' => $this->notification->type,
            'action_url' => $this->notification->action_url ?? '',
        ];

        $tokens = $deviceTokens->pluck('device_token')->toArray();
        $results = $fcmService->sendToDevices($tokens, $notificationData, $data);

        // Update sent status
        $allSent = !in_array(false, $results, true);
        $this->notification->users()->updateExistingPivot($this->user->id, [
            'is_sent' => $allSent,
            'sent_at' => now(),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendPushNotificationJob failed', [
            'notification_id' => $this->notification->id,
            'user_id' => $this->user->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

ثم في `NotificationService`:

```php
use App\Jobs\SendPushNotificationJob;

protected function attachNotificationToUser(Notification $notification, AppUser $user): void
{
    try {
        $notification->users()->attach($user->id, [
            'is_read' => false,
            'is_sent' => false,
        ]);

        // Dispatch job for async sending
        SendPushNotificationJob::dispatch($notification, $user);
    } catch (\Exception $e) {
        Log::error('Failed to attach notification to user', [
            'notification_id' => $notification->id,
            'user_id' => $user->id,
            'error' => $e->getMessage(),
        ]);
    }
}
```

---

## اختبار التكامل

### 1. اختبار تسجيل Device Token

```bash
curl -X POST "http://localhost/api/user/device-token" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "device_token": "test_fcm_token_from_app",
    "device_type": "android",
    "device_name": "Samsung Galaxy S21"
  }'
```

**الاستجابة المتوقعة:**

```json
{
    "success": true,
    "message": "تم تسجيل الجهاز بنجاح",
    "data": {
        "id": 1,
        "device_type": "android",
        "device_name": "Samsung Galaxy S21",
        "is_active": true
    }
}
```

### 2. اختبار إرسال إشعار

```bash
curl -X POST "http://localhost/api/admin/notifications" \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "اختبار FCM",
    "body": "هذا إشعار تجريبي من Firebase",
    "target_type": "specific",
    "user_ids": [1]
  }'
```

### 3. التحقق من Logs

```bash
tail -f storage/logs/laravel.log | grep FCM
```

### 4. اختبار من Firebase Console

1. اذهب إلى Firebase Console
2. اختر **Cloud Messaging**
3. انقر **"Send test message"**
4. أدخل Device Token
5. أرسل الإشعار

---

## أفضل الممارسات

### 1. إدارة Device Tokens

-   ✅ **تحديث دوري**: تحديث التوكن عند كل تسجيل دخول
-   ✅ **تنظيف**: حذف التوكنات غير النشطة لأكثر من 90 يوم
-   ✅ **متعدد الأجهزة**: السماح للمستخدم بتسجيل عدة أجهزة
-   ✅ **إلغاء التفعيل**: إلغاء تفعيل التوكنات الفاشلة تلقائياً

### 2. معالجة الأخطاء

```php
// في FcmService
if (isset($result['error'])) {
    switch ($result['error']) {
        case 'InvalidRegistration':
        case 'NotRegistered':
            // حذف التوكن
            DeviceToken::where('device_token', $token)->delete();
            break;
        case 'Unavailable':
            // إعادة المحاولة لاحقاً
            break;
    }
}
```

### 3. تحسين الأداء

-   ✅ **Queues**: استخدام Laravel Queues للإرسال غير المتزامن
-   ✅ **Batch Sending**: إرسال حتى 1000 توكن في طلب واحد
-   ✅ **Caching**: تخزين التوكنات في Cache
-   ✅ **Rate Limiting**: إضافة تأخير عند إرسال كميات كبيرة

### 4. الأمان

-   ✅ **HTTPS Only**: استخدام HTTPS فقط
-   ✅ **Server Key**: عدم تعريض Server Key في الكود
-   ✅ **Validation**: التحقق من صحة التوكنات
-   ✅ **Logging**: تسجيل جميع محاولات الإرسال

---

## استكشاف الأخطاء

### المشكلة: الإشعارات لا تصل

**الحلول:**

1. تحقق من صحة `FCM_SERVER_KEY`
2. تحقق من صحة Device Token
3. تحقق من Logs: `storage/logs/laravel.log`
4. تأكد من تفعيل Cloud Messaging في Firebase Console

### المشكلة: InvalidRegistration

**السبب:** التوكن غير صالح أو تم حذفه

**الحل:** حذف التوكن من قاعدة البيانات

### المشكلة: Unavailable

**السبب:** خدمة FCM غير متاحة مؤقتاً

**الحل:** إعادة المحاولة تلقائياً (Laravel Queues)

### المشكلة: Rate Limit

**السبب:** إرسال الكثير من الطلبات

**الحل:** استخدام Queues وإضافة تأخير

---

## مراجع إضافية

-   [Firebase Cloud Messaging Documentation](https://firebase.google.com/docs/cloud-messaging)
-   [FCM HTTP v1 API](https://firebase.google.com/docs/cloud-messaging/migrate-v1)
-   [Laravel Queues](https://laravel.com/docs/queues)

---

تم إعداد دمج Firebase بنجاح! 🎉
