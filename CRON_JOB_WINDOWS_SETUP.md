# ⏰ تفعيل Cron Job على Windows

## 🎯 الطريقة الموصى بها: Task Scheduler

### الخطوة 1: إنشاء ملف Batch Script

أنشئ ملف `run-scheduler.bat` في مجلد المشروع:

```batch
@echo off
cd /d "D:\backend-wifi\radius-app-backend"
php artisan schedule:run >> storage\logs\scheduler.log 2>&1
```

### الخطوة 2: إعداد Task Scheduler

1. **افتح Task Scheduler:**

    - اضغط `Win + R`
    - اكتب `taskschd.msc` واضغط Enter

2. **أنشئ مهمة جديدة:**

    - اضغط على "Create Basic Task" من الجانب الأيمن
    - الاسم: `Laravel Scheduler`
    - الوصف: `تشغيل Laravel Scheduler كل دقيقة`

3. **عيّن المشغل (Trigger):**

    - اختر "When the computer starts" (عند بدء التشغيل)
    - أو "When I log on" (عند تسجيل الدخول)

4. **عيّن الإجراء (Action):**

    - اختر "Start a program"
    - البرنامج: `C:\Windows\System32\cmd.exe`
    - المعاملات: `/c "D:\backend-wifi\radius-app-backend\run-scheduler.bat"`
    - مجلد البدء: `D:\backend-wifi\radius-app-backend`

5. **إعدادات إضافية:**

    - ✅ Run whether user is logged on or not
    - ✅ Run with highest privileges
    - ✅ Configure for: Windows 10

6. **إعداد التكرار:**
    - بعد إنشاء المهمة، انقر عليها بالزر الأيمن → Properties
    - تبويب Triggers → Edit
    - ✅ Repeat task every: 1 minute
    - ✅ For a duration of: Indefinitely

---

## 🚀 الطريقة السريعة: PowerShell Script

### إنشاء ملف PowerShell

أنشئ ملف `start-scheduler.ps1`:

```powershell
# Laravel Scheduler for Windows
$projectPath = "D:\backend-wifi\radius-app-backend"
$logFile = "$projectPath\storage\logs\scheduler.log"

while ($true) {
    Set-Location $projectPath
    php artisan schedule:run | Out-File -Append -FilePath $logFile
    Start-Sleep -Seconds 60
}
```

### تشغيله:

```powershell
# في PowerShell (كمسؤول)
.\start-scheduler.ps1
```

أو تشغيله في الخلفية:

```powershell
Start-Process powershell -ArgumentList "-File", "start-scheduler.ps1" -WindowStyle Hidden
```

---

## 🔧 الطريقة للتطوير: schedule:work

للتطوير والاختبار، استخدم:

```bash
php artisan schedule:work
```

هذا الأمر سيعمل في الخلفية ويشغل المهام تلقائياً.

**لإيقافه:** اضغط `Ctrl + C`

---

## 📝 الملفات الجاهزة

تم إنشاء الملفات التالية في مجلد المشروع:

1. **`run-scheduler.bat`** - ملف Batch لتشغيل Scheduler
2. **`start-scheduler.ps1`** - ملف PowerShell لتشغيل مستمر
3. **`install-scheduler-task.bat`** - تثبيت تلقائي في Task Scheduler

---

## ⚡ التثبيت السريع (الطريقة الأسهل)

### الطريقة 1: تثبيت تلقائي (يحتاج صلاحيات مسؤول)

1. انقر بالزر الأيمن على `install-scheduler-task.bat`
2. اختر "Run as administrator"
3. تم! المهمة ستشغل تلقائياً كل دقيقة

### الطريقة 2: تشغيل يدوي (للتطوير)

افتح PowerShell في مجلد المشروع وشغّل:

```powershell
.\start-scheduler.ps1
```

---

## ✅ التحقق من أن Cron Job يعمل

### 1. تحقق من Task Scheduler:

```bash
# عرض المهمة
schtasks /query /tn "Laravel Scheduler"

# تشغيل المهمة يدوياً
schtasks /run /tn "Laravel Scheduler"
```

### 2. تحقق من السجلات:

```bash
# عرض سجل Scheduler
type storage\logs\scheduler.log

# أو في PowerShell
Get-Content storage\logs\scheduler.log -Tail 50
```

### 3. اختبر الأوامر يدوياً:

```bash
# اختبار إشعار قبل ساعتين
php artisan notifications:subscription-expiry --hours=2

# اختبار إشعار انتهاء الاشتراك
php artisan notifications:subscription-expired
```

---

## 🔍 استكشاف الأخطاء

### المشكلة: المهمة لا تعمل

**الحلول:**

1. **تحقق من صلاحيات المسؤول:**

    ```bash
    # تأكد أنك تشغل كمسؤول
    whoami /priv
    ```

2. **تحقق من مسار PHP:**

    ```bash
    php -v
    ```

3. **اختبر الملف يدوياً:**
    ```bash
    .\run-scheduler.bat
    ```

### المشكلة: لا توجد سجلات

**الحلول:**

1. **تحقق من وجود مجلد السجلات:**

    ```bash
    if not exist "storage\logs" mkdir "storage\logs"
    ```

2. **تحقق من الصلاحيات:**
    - تأكد أن المجلد `storage\logs` قابل للكتابة

---

## 📊 مراقبة Cron Job

### عرض المهام النشطة:

```bash
# في PowerShell
Get-ScheduledTask | Where-Object {$_.TaskName -like "*Laravel*"}
```

### عرض آخر تنفيذ:

```bash
# في PowerShell
Get-ScheduledTaskInfo -TaskName "Laravel Scheduler"
```

---

## 🎯 ملخص الطرق

| الطريقة               | الاستخدام       | المميزات                 |
| --------------------- | --------------- | ------------------------ |
| **Task Scheduler**    | الإنتاج         | يعمل حتى بدون تسجيل دخول |
| **PowerShell Script** | التطوير/الإنتاج | سهل التشغيل والإيقاف     |
| **schedule:work**     | التطوير فقط     | سريع للاختبار            |

---

## ⚠️ ملاحظات مهمة

1. **Task Scheduler** يعمل حتى لو لم يكن المستخدم مسجل دخول (إذا عُيّن كـ SYSTEM)
2. **PowerShell Script** يحتاج أن يكون PowerShell مفتوحاً
3. **schedule:work** يتوقف عند إغلاق Terminal
4. جميع الطرق تسجل في `storage\logs\scheduler.log`

---

## 🚀 الخطوات التالية

1. ✅ اختر طريقة من الطرق أعلاه
2. ✅ شغّل Cron Job
3. ✅ تحقق من السجلات
4. ✅ اختبر الإشعارات يدوياً
5. ✅ راقب الإشعارات التلقائية

---

**آخر تحديث**: 2024-12-20  
**الحالة**: ✅ جاهز للاستخدام
