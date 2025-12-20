# 💾 دليل ربط النسخ الاحتياطي لقاعدة البيانات بلوحة التحكم

## 🔐 المصادقة

جميع الـ Endpoints تتطلب:

```
Authorization: Bearer {token}
```

**الصلاحيات**: المدير فقط (Admin)

---

## 📋 نظرة عامة

نظام النسخ الاحتياطي يتيح للمدير:

-   إنشاء نسخة احتياطية يدوياً
-   جدولة النسخ التلقائي (يومي، أسبوعي، شهري)
-   تحميل النسخ الاحتياطية (مع طلب كلمة المرور)
-   إدارة النسخ الاحتياطية (عرض، حذف)

---

## 🛠️ API Endpoints

### 1. عرض جميع النسخ الاحتياطية

**Method**: `GET`  
**URL**: `/api/admin/database/backups`  
**Headers**:

```
Authorization: Bearer {token}
```

**Query Parameters** (اختيارية):

| المعامل    | النوع   | الوصف                                         | مثال      |
| ---------- | ------- | --------------------------------------------- | --------- |
| `per_page` | integer | عدد النسخ في كل صفحة                          | 20        |
| `page`     | integer | رقم الصفحة                                    | 1         |
| `type`     | string  | نوع النسخة: `manual` أو `automatic`           | manual    |
| `status`   | string  | حالة النسخة: `pending`, `completed`, `failed` | completed |

**مثال**: `GET /api/admin/database/backups?per_page=20&type=manual&status=completed`

**الاستجابة**:

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "filename": "backup_radius_app_2025-12-15_143022.sql",
            "file_path": "backups/backup_radius_app_2025-12-15_143022.sql",
            "file_size": "5242880",
            "type": "manual",
            "status": "completed",
            "error_message": null,
            "scheduled_time": null,
            "backup_date": "2025-12-15T14:30:22Z",
            "created_at": "2025-12-15T14:30:22Z",
            "updated_at": "2025-12-15T14:30:25Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 3,
        "per_page": 20,
        "total": 45
    }
}
```

---

### 2. إنشاء نسخة احتياطية يدوياً

**Method**: `POST`  
**URL**: `/api/admin/database/backups`  
**Headers**:

```
Authorization: Bearer {token}
```

**Body**: لا يحتاج body

**الاستجابة**:

```json
{
    "success": true,
    "message": "تم بدء إنشاء النسخة الاحتياطية",
    "data": {
        "id": 1,
        "filename": "backup_radius_app_2025-12-15_143022.sql",
        "file_path": "backups/backup_radius_app_2025-12-15_143022.sql",
        "type": "manual",
        "status": "completed",
        "backup_date": "2025-12-15T14:30:22Z"
    }
}
```

---

### 3. تحميل نسخة احتياطية (يتطلب كلمة المرور)

**Method**: `POST`  
**URL**: `/api/admin/database/backups/{id}/download`  
**Headers**:

```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body** (JSON):

```json
{
    "password": "your_password"
}
```

**الاستجابة**:

```json
{
    "success": true,
    "message": "تم التحقق من كلمة المرور بنجاح",
    "data": {
        "download_url": "http://domain.com/storage/backups/backup_radius_app_2025-12-15_143022.sql?signature=...",
        "filename": "backup_radius_app_2025-12-15_143022.sql",
        "expires_at": "2025-12-15T14:35:22Z"
    }
}
```

**ملاحظات مهمة**:

-   يجب إرسال كلمة مرور المدير للتحقق
-   رابط التحميل صالح لمدة 5 دقائق فقط
-   يجب استخدام الرابط فوراً قبل انتهاء صلاحيته

**خطأ - كلمة مرور خاطئة**:

```json
{
    "success": false,
    "message": "كلمة المرور غير صحيحة"
}
```

---

### 4. تعيين جدولة النسخ التلقائي

**Method**: `POST`  
**URL**: `/api/admin/database/backups/schedule`  
**Headers**:

```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body** (JSON):

```json
{
    "schedule": "daily",
    "time": "02:00"
}
```

**قيم `schedule` المتاحة**:

-   `daily` - يومي
-   `weekly` - أسبوعي (كل يوم أحد)
-   `monthly` - شهري (اليوم الأول من كل شهر)

**قيمة `time`**: يجب أن تكون بصيغة `HH:MM` (24 ساعة)

**أمثلة**:

-   `"02:00"` - الساعة 2 صباحاً
-   `"14:30"` - الساعة 2:30 مساءً
-   `"23:59"` - الساعة 11:59 مساءً

**الاستجابة**:

```json
{
    "success": true,
    "message": "تم تعيين جدولة النسخ الاحتياطي بنجاح",
    "data": {
        "schedule": "daily",
        "time": "02:00"
    }
}
```

---

### 5. الحصول على جدولة النسخ التلقائي

**Method**: `GET`  
**URL**: `/api/admin/database/backups/schedule`  
**Headers**:

```
Authorization: Bearer {token}
```

**الاستجابة**:

```json
{
    "success": true,
    "data": {
        "schedule": "daily",
        "time": "02:00"
    }
}
```

**إذا لم يتم تعيين جدولة**:

```json
{
    "success": true,
    "data": {
        "schedule": null,
        "time": null
    }
}
```

---

### 6. حذف نسخة احتياطية

**Method**: `DELETE`  
**URL**: `/api/admin/database/backups/{id}`  
**Headers**:

```
Authorization: Bearer {token}
```

**الاستجابة**:

```json
{
    "success": true,
    "message": "تم حذف النسخة الاحتياطية بنجاح"
}
```

---

## 📊 هيكل البيانات

### كائن النسخة الاحتياطية (Backup Object):

| الحقل            | النوع   | الوصف                                    |
| ---------------- | ------- | ---------------------------------------- |
| `id`             | integer | معرف النسخة الاحتياطية                   |
| `filename`       | string  | اسم الملف                                |
| `file_path`      | string  | مسار الملف                               |
| `file_size`      | string  | حجم الملف (بالبايت)                      |
| `type`           | string  | نوع النسخة: `manual` أو `automatic`      |
| `status`         | string  | الحالة: `pending`, `completed`, `failed` |
| `error_message`  | string  | رسالة الخطأ (إذا فشلت)                   |
| `scheduled_time` | string  | وقت الجدولة (للنسخ التلقائي)             |
| `backup_date`    | string  | تاريخ ووقت النسخة الاحتياطية             |
| `created_at`     | string  | تاريخ الإنشاء                            |
| `updated_at`     | string  | تاريخ التحديث                            |

### حالات النسخة الاحتياطية (Status):

| الحالة      | الوصف               |
| ----------- | ------------------- |
| `pending`   | النسخة قيد الإنشاء  |
| `completed` | النسخة اكتملت بنجاح |
| `failed`    | فشل إنشاء النسخة    |

---

## 🔍 أمثلة على الاستجابات

### نجاح (200/201):

```json
{
    "success": true,
    "message": "تم العملية بنجاح",
    "data": {...}
}
```

### خطأ (422 - Validation Error):

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "password": ["حقل كلمة المرور مطلوب"],
        "time": ["صيغة الوقت غير صحيحة"]
    }
}
```

### خطأ (403 - Unauthorized):

```json
{
    "success": false,
    "message": "This action is unauthorized."
}
```

### خطأ (404 - Not Found):

```json
{
    "success": false,
    "message": "ملف النسخة الاحتياطية غير موجود"
}
```

---

## 📱 أمثلة في Frontend

### إنشاء نسخة احتياطية:

```javascript
fetch("http://domain.com/api/admin/database/backups", {
    method: "POST",
    headers: {
        Authorization: `Bearer ${token}`,
    },
})
    .then((response) => response.json())
    .then((data) => {
        if (data.success) {
            console.log("تم بدء إنشاء النسخة الاحتياطية");
            // تحديث قائمة النسخ
            refreshBackupsList();
        }
    });
```

### تحميل نسخة احتياطية:

```javascript
async function downloadBackup(backupId, password) {
    const response = await fetch(
        `http://domain.com/api/admin/database/backups/${backupId}/download`,
        {
            method: "POST",
            headers: {
                Authorization: `Bearer ${token}`,
                "Content-Type": "application/json",
            },
            body: JSON.stringify({ password }),
        }
    );

    const data = await response.json();

    if (data.success) {
        // فتح رابط التحميل في نافذة جديدة
        window.open(data.data.download_url, "_blank");
    } else {
        alert(data.message);
    }
}
```

### تعيين جدولة النسخ التلقائي:

```javascript
function setBackupSchedule(schedule, time) {
    fetch("http://domain.com/api/admin/database/backups/schedule", {
        method: "POST",
        headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            schedule: schedule, // "daily", "weekly", "monthly"
            time: time, // "02:00"
        }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                alert("تم تعيين الجدولة بنجاح");
            }
        });
}
```

### عرض قائمة النسخ:

```javascript
function getBackups(page = 1, type = null, status = null) {
    const params = new URLSearchParams({
        per_page: 20,
        page: page,
    });

    if (type) params.append("type", type);
    if (status) params.append("status", status);

    fetch(`http://domain.com/api/admin/database/backups?${params}`, {
        headers: {
            Authorization: `Bearer ${token}`,
        },
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                displayBackups(data.data);
                updatePagination(data.meta);
            }
        });
}
```

---

## 🎯 حالات الاستخدام

### 1. إنشاء نسخة احتياطية قبل تحديث مهم:

-   قبل تحديث قاعدة البيانات
-   قبل تغييرات كبيرة في النظام

### 2. النسخ التلقائي:

-   نسخ يومي في الساعة 2 صباحاً
-   نسخ أسبوعي كل يوم أحد
-   نسخ شهري في اليوم الأول من كل شهر

### 3. تحميل النسخ:

-   للاحتفاظ بنسخة محلية
-   للاستعادة في حالة الطوارئ
-   للنسخ على خادم آخر

---

## ✅ نصائح للربط

1. **للـ Token**: استخدم `Bearer Token` في Authorization header
2. **لكلمة المرور**: تأكد من إرسال كلمة مرور المدير الصحيحة
3. **للتحميل**: استخدم رابط التحميل فوراً (صالح لمدة 5 دقائق فقط)
4. **للجدولة**: اختر وقت مناسب (مثل 2 صباحاً) لتقليل التأثير على الأداء
5. **للحذف**: احذف النسخ القديمة لتوفير المساحة

---

## 🔄 النسخ التلقائي

### كيف يعمل:

1. المدير يحدد الجدولة والوقت
2. النظام يتحقق كل ساعة من الوقت المحدد
3. عند الوصول للوقت المحدد، يتم إنشاء النسخة تلقائياً
4. النسخة تُحفظ مع `type: "automatic"`

### ملاحظات:

-   النسخ التلقائي يعمل فقط إذا كان Cron Job نشطاً
-   تأكد من إعداد Cron Job في الخادم:
    ```bash
    * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
    ```

---

## 📝 ملاحظات مهمة

1. **الأمان**: رابط التحميل محمي بكلمة المرور ومؤقت (5 دقائق)
2. **الحجم**: النسخ الاحتياطية قد تكون كبيرة حسب حجم قاعدة البيانات
3. **التخزين**: النسخ تُحفظ في `storage/app/backups/`
4. **الأداء**: النسخ قد تستغرق وقتاً حسب حجم قاعدة البيانات
5. **الصلاحيات**: يتطلب صلاحيات المدير فقط
