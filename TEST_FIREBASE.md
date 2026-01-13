# 🧪 اختبار Firebase Service

## أوامر الاختبار في Tinker

### 1. اختبار تحميل Service:

```php
$service = app(App\Services\FirebaseMessagingService::class);
echo "Firebase Service loaded successfully! ✅";
```

### 2. اختبار التحقق من الإعدادات:

```php
echo "Credentials Path: " . config('services.firebase.credentials_path') . PHP_EOL;
echo "Project ID: " . config('services.firebase.project_id') . PHP_EOL;
echo "File exists: " . (file_exists(config('services.firebase.credentials_path')) ? 'YES ✅' : 'NO ❌') . PHP_EOL;
```

### 3. اختبار إرسال إشعار (يحتاج Device Token):

```php
$user = App\Models\AppUser::first();
$notificationService = app(App\Services\NotificationService::class);

$notificationService->createNotification([
    'title' => 'اختبار Firebase',
    'body' => 'هذا إشعار تجريبي من Backend',
    'type' => 'system',
    'priority' => 1,
], [$user->id], 'specific');
```

### 4. التحقق من Device Tokens:

```php
$user = App\Models\AppUser::first();
$tokens = $user->deviceTokens()->where('is_active', true)->get();
echo "Active tokens: " . $tokens->count() . PHP_EOL;
```

---

## ملاحظات

-   إذا لم يكن لدى المستخدم Device Token، لن يتم إرسال الإشعار فعلياً
-   الإشعار سيُحفظ في قاعدة البيانات حتى لو لم يتم إرساله
-   للتجربة الكاملة، تحتاج إلى Device Token من تطبيق Next.js
