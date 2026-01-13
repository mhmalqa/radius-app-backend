# 🧪 أمثلة اختبار الإشعارات

## 📋 أمثلة سريعة لاختبار نظام الإشعارات

### 1. اختبارات API باستخدام cURL

#### إنشاء إشعار لجميع المستخدمين
```bash
curl -X POST "http://localhost/api/admin/notifications" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "إشعار عام",
    "body": "هذا إشعار لجميع المستخدمين النشطين",
    "type": "manual",
    "target_type": "all"
  }'
```

#### إنشاء إشعار للمستخدمين النشطين فقط
```bash
curl -X POST "http://localhost/api/admin/notifications" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "تذكير تجديد الاشتراك",
    "body": "اشتراكك سينتهي قريباً",
    "type": "manual",
    "target_type": "active",
    "priority": 1,
    "action_url": "/subscription",
    "action_text": "تجديد الآن"
  }'
```

#### إنشاء إشعار لمستخدمين محددين
```bash
curl -X POST "http://localhost/api/admin/notifications" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "إشعار خاص",
    "body": "هذا إشعار لمستخدمين محددين",
    "type": "manual",
    "target_type": "specific",
    "user_ids": [1, 2, 3, 5]
  }'
```

#### عرض إشعارات المستخدم
```bash
curl -X GET "http://localhost/api/notifications" \
  -H "Authorization: Bearer YOUR_USER_TOKEN"
```

#### عرض الإشعارات غير المقروءة فقط
```bash
curl -X GET "http://localhost/api/notifications?unread_only=true" \
  -H "Authorization: Bearer YOUR_USER_TOKEN"
```

#### الحصول على عدد الإشعارات غير المقروءة
```bash
curl -X GET "http://localhost/api/notifications/unread-count" \
  -H "Authorization: Bearer YOUR_USER_TOKEN"
```

#### تحديد إشعار كمقروء
```bash
curl -X POST "http://localhost/api/notifications/1/mark-as-read" \
  -H "Authorization: Bearer YOUR_USER_TOKEN"
```

#### تحديد جميع الإشعارات كمقروءة
```bash
curl -X POST "http://localhost/api/notifications/mark-all-as-read" \
  -H "Authorization: Bearer YOUR_USER_TOKEN"
```

---

### 2. اختبارات باستخدام Postman

#### Collection JSON للاستيراد في Postman

```json
{
  "info": {
    "name": "Notifications API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Create Notification - All Users",
      "request": {
        "method": "POST",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer {{admin_token}}",
            "type": "text"
          },
          {
            "key": "Content-Type",
            "value": "application/json",
            "type": "text"
          }
        ],
        "body": {
          "mode": "raw",
          "raw": "{\n  \"title\": \"إشعار عام\",\n  \"body\": \"هذا إشعار لجميع المستخدمين\",\n  \"type\": \"manual\",\n  \"target_type\": \"all\"\n}"
        },
        "url": {
          "raw": "{{base_url}}/api/admin/notifications",
          "host": ["{{base_url}}"],
          "path": ["api", "admin", "notifications"]
        }
      }
    },
    {
      "name": "Get User Notifications",
      "request": {
        "method": "GET",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer {{user_token}}",
            "type": "text"
          }
        ],
        "url": {
          "raw": "{{base_url}}/api/notifications",
          "host": ["{{base_url}}"],
          "path": ["api", "notifications"]
        }
      }
    },
    {
      "name": "Get Unread Count",
      "request": {
        "method": "GET",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer {{user_token}}",
            "type": "text"
          }
        ],
        "url": {
          "raw": "{{base_url}}/api/notifications/unread-count",
          "host": ["{{base_url}}"],
          "path": ["api", "notifications", "unread-count"]
        }
      }
    }
  ],
  "variable": [
    {
      "key": "base_url",
      "value": "http://localhost",
      "type": "string"
    },
    {
      "key": "admin_token",
      "value": "",
      "type": "string"
    },
    {
      "key": "user_token",
      "value": "",
      "type": "string"
    }
  ]
}
```

---

### 3. اختبارات باستخدام PHPUnit

#### إنشاء ملف Test

```php
<?php

namespace Tests\Feature;

use App\Models\AppUser;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = AppUser::factory()->create(['role' => 2]);
        $this->user = AppUser::factory()->create(['role' => 0, 'is_active' => true]);
    }

    /** @test */
    public function admin_can_create_notification_for_all_users()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/notifications', [
                'title' => 'Test Notification',
                'body' => 'Test Body',
                'type' => 'manual',
                'target_type' => 'all',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('notifications', [
            'title' => 'Test Notification',
        ]);
    }

    /** @test */
    public function user_can_get_their_notifications()
    {
        $notification = Notification::factory()->create();
        $notification->users()->attach($this->user->id, [
            'is_read' => false,
            'is_sent' => false,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/notifications');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function user_can_mark_notification_as_read()
    {
        $notification = Notification::factory()->create();
        $notification->users()->attach($this->user->id, [
            'is_read' => false,
            'is_sent' => true,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/notifications/{$notification->id}/mark-as-read");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('notification_user', [
            'notification_id' => $notification->id,
            'user_id' => $this->user->id,
            'is_read' => true,
        ]);
    }
}
```

---

### 4. اختبارات الأوامر التلقائية

#### اختبار إشعارات انتهاء الاشتراك

```bash
# قبل 48 ساعة
php artisan notifications:subscription-expiry --hours=48

# قبل 24 ساعة
php artisan notifications:subscription-expiry --hours=24

# قبل ساعة واحدة
php artisan notifications:subscription-expiry --hours=1
```

#### اختبار إشعارات بعد الانتهاء

```bash
php artisan notifications:subscription-expired
```

#### اختبار إشعارات البث المباشر

```bash
php artisan notifications:live-stream-day
```

---

### 5. اختبارات قاعدة البيانات

#### التحقق من إنشاء الإشعار

```sql
-- عرض آخر إشعار
SELECT * FROM notifications ORDER BY id DESC LIMIT 1;

-- عرض جميع الإشعارات
SELECT 
    id,
    title,
    body,
    type,
    created_at
FROM notifications
ORDER BY created_at DESC;
```

#### التحقق من ربط الإشعار بالمستخدمين

```sql
-- عرض جميع المستلمين لإشعار محدد
SELECT 
    u.id as user_id,
    u.username,
    nu.is_read,
    nu.is_sent,
    nu.sent_at,
    nu.read_at
FROM notifications n
JOIN notification_user nu ON n.id = nu.notification_id
JOIN app_users u ON nu.user_id = u.id
WHERE n.id = 1;
```

#### إحصائيات الإشعار

```sql
-- إحصائيات شاملة لإشعار
SELECT 
    n.id,
    n.title,
    COUNT(nu.user_id) as total_recipients,
    SUM(CASE WHEN nu.is_read = 1 THEN 1 ELSE 0 END) as read_count,
    SUM(CASE WHEN nu.is_sent = 1 THEN 1 ELSE 0 END) as sent_count,
    SUM(CASE WHEN nu.is_read = 0 THEN 1 ELSE 0 END) as unread_count
FROM notifications n
LEFT JOIN notification_user nu ON n.id = nu.notification_id
WHERE n.id = 1
GROUP BY n.id, n.title;
```

#### إحصائيات المستخدم

```sql
-- إحصائيات إشعارات مستخدم
SELECT 
    u.id,
    u.username,
    COUNT(nu.notification_id) as total_notifications,
    SUM(CASE WHEN nu.is_read = 0 THEN 1 ELSE 0 END) as unread_count,
    SUM(CASE WHEN nu.is_read = 1 THEN 1 ELSE 0 END) as read_count
FROM app_users u
LEFT JOIN notification_user nu ON u.id = nu.user_id
WHERE u.id = 1
GROUP BY u.id, u.username;
```

---

### 6. سيناريوهات اختبار شاملة

#### السيناريو 1: إشعار لجميع المستخدمين النشطين

1. **الإعداد:**
   - إنشاء 3 مستخدمين نشطين
   - إنشاء 1 مستخدم غير نشط

2. **الإجراء:**
   ```bash
   curl -X POST "http://localhost/api/admin/notifications" \
     -H "Authorization: Bearer {admin_token}" \
     -H "Content-Type: application/json" \
     -d '{
       "title": "إشعار عام",
       "body": "هذا إشعار لجميع المستخدمين النشطين",
       "target_type": "all"
     }'
   ```

3. **التحقق:**
   - يجب أن يُربط الإشعار بـ 3 مستخدمين فقط
   - المستخدم غير النشط لا يجب أن يحصل على الإشعار

#### السيناريو 2: إشعار للمستخدمين النشطين مع اشتراك صالح

1. **الإعداد:**
   - إنشاء مستخدم مع اشتراك صالح (expiration_at > now)
   - إنشاء مستخدم مع اشتراك منتهي (expiration_at < now)

2. **الإجراء:**
   ```bash
   curl -X POST "http://localhost/api/admin/notifications" \
     -H "Authorization: Bearer {admin_token}" \
     -H "Content-Type: application/json" \
     -d '{
       "title": "إشعار للمشتركين",
       "body": "هذا إشعار للمستخدمين النشطين فقط",
       "target_type": "active"
     }'
   ```

3. **التحقق:**
   - فقط المستخدم مع اشتراك صالح يجب أن يحصل على الإشعار

#### السيناريو 3: إشعار لمستخدمين محددين

1. **الإعداد:**
   - إنشاء 5 مستخدمين

2. **الإجراء:**
   ```bash
   curl -X POST "http://localhost/api/admin/notifications" \
     -H "Authorization: Bearer {admin_token}" \
     -H "Content-Type: application/json" \
     -d '{
       "title": "إشعار خاص",
       "body": "هذا إشعار لمستخدمين محددين",
       "target_type": "specific",
       "user_ids": [1, 3, 5]
     }'
   ```

3. **التحقق:**
   - فقط المستخدمين 1, 3, 5 يجب أن يحصلوا على الإشعار

---

### 7. اختبارات Firebase Integration

#### تسجيل Device Token

```bash
curl -X POST "http://localhost/api/user/device-token" \
  -H "Authorization: Bearer {user_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "device_token": "dGVzdF90b2tlbg==",
    "device_type": "android",
    "device_name": "Test Device"
  }'
```

#### التحقق من تسجيل التوكن

```sql
SELECT * FROM device_tokens WHERE user_id = 1;
```

#### اختبار إرسال إشعار مع Firebase

1. سجّل Device Token من التطبيق
2. أرسل إشعار من API
3. تحقق من وصول الإشعار للجهاز
4. تحقق من تحديث `is_sent` في قاعدة البيانات

---

### 8. نصائح للاختبار

1. **استخدم بيئة اختبار منفصلة** لتجنب إرسال إشعارات حقيقية
2. **تحقق من Logs** بعد كل اختبار: `storage/logs/laravel.log`
3. **استخدم قاعدة بيانات منفصلة** للاختبار
4. **اختبر السيناريوهات الفاشلة** أيضاً (مثل مستخدم غير موجود)
5. **اختبر الأداء** عند إرسال إشعارات لعدد كبير من المستخدمين

---

تم إعداد أمثلة الاختبار بنجاح! 🎉










































