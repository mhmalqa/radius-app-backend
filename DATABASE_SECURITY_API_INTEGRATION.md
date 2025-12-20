# 🔒 دليل ربط فحص أمان قاعدة البيانات بلوحة التحكم

## 🔐 المصادقة

الـ Endpoint يتطلب:

```
Authorization: Bearer {token}
```

**الصلاحيات**: المدير فقط (Admin)

---

## 📋 نظرة عامة

نظام فحص أمان قاعدة البيانات يتيح للمدير مراقبة حالة قاعدة البيانات والأمان، مثل:
- حالة الاتصال بقاعدة البيانات
- وجود الجداول المهمة
- فحص المستخدمين بدون كلمات مرور
- عدد المديرين النشطين
- حجم قاعدة البيانات

---

## 🛠️ API Endpoint

### فحص أمان قاعدة البيانات

**Method**: `GET`  
**URL**: `/api/admin/database/security-check`  
**Headers**:

```
Authorization: Bearer {token}
```

**الاستجابة**:

```json
{
    "success": true,
    "data": {
        "checks": {
            "database_connection": {
                "status": "success",
                "message": "الاتصال بقاعدة البيانات ناجح"
            },
            "table_app_users": {
                "status": "success",
                "message": "جدول app_users موجود"
            },
            "table_payment_requests": {
                "status": "success",
                "message": "جدول payment_requests موجود"
            },
            "table_revenues": {
                "status": "success",
                "message": "جدول revenues موجود"
            },
            "table_user_subscriptions": {
                "status": "success",
                "message": "جدول user_subscriptions موجود"
            },
            "users_without_password": {
                "status": "success",
                "message": "جميع المستخدمين لديهم كلمات مرور",
                "count": 0
            },
            "active_admins": {
                "status": "success",
                "message": "يوجد 2 مدير نشط",
                "count": 2
            },
            "database_size": {
                "status": "success",
                "message": "حجم قاعدة البيانات: 125.50 MB",
                "size_mb": 125.5
            }
        },
        "overall_status": "success",
        "timestamp": "2025-12-15T10:30:00Z"
    }
}
```

---

## 📊 هيكل البيانات

### كائن الفحص (Check Object):

| الحقل     | النوع   | الوصف                                    |
| --------- | ------- | ---------------------------------------- |
| `status`  | string  | حالة الفحص: `success`, `warning`, `error`, `info` |
| `message` | string  | رسالة وصفية للفحص                         |
| `count`   | integer | (اختياري) عدد العناصر (للمستخدمين والمديرين) |
| `size_mb` | float   | (اختياري) حجم قاعدة البيانات بالميجابايت |

### حالات الفحص (Status Values):

| الحالة    | الوصف                                    |
| --------- | ---------------------------------------- |
| `success` | كل شيء يعمل بشكل صحيح                     |
| `warning` | هناك مشكلة محتملة تحتاج انتباه            |
| `error`   | هناك مشكلة خطيرة تحتاج إصلاح فوري        |
| `info`    | معلومات إضافية (غير حرجة)                 |

---

## 🔍 أنواع الفحوصات

### 1. فحص الاتصال بقاعدة البيانات

**المفتاح**: `database_connection`

**الحالات**:
- ✅ `success`: الاتصال ناجح
- ❌ `error`: فشل الاتصال

**مثال**:

```json
{
    "status": "success",
    "message": "الاتصال بقاعدة البيانات ناجح"
}
```

---

### 2. فحص وجود الجداول المهمة

**المفاتيح**: 
- `table_app_users`
- `table_payment_requests`
- `table_revenues`
- `table_user_subscriptions`

**الحالات**:
- ✅ `success`: الجدول موجود
- ❌ `error`: الجدول غير موجود

**مثال**:

```json
{
    "status": "success",
    "message": "جدول app_users موجود"
}
```

---

### 3. فحص المستخدمين بدون كلمات مرور

**المفتاح**: `users_without_password`

**الحالات**:
- ✅ `success`: جميع المستخدمين لديهم كلمات مرور (`count: 0`)
- ⚠️ `warning`: يوجد مستخدمين بدون كلمات مرور (`count > 0`)

**مثال - نجاح**:

```json
{
    "status": "success",
    "message": "جميع المستخدمين لديهم كلمات مرور",
    "count": 0
}
```

**مثال - تحذير**:

```json
{
    "status": "warning",
    "message": "يوجد 3 مستخدمين بدون كلمة مرور",
    "count": 3
}
```

---

### 4. فحص المديرين النشطين

**المفتاح**: `active_admins`

**الحالات**:
- ✅ `success`: يوجد مديرين نشطين (`count > 0`)
- ⚠️ `warning`: لا يوجد مديرين نشطين (`count = 0`)

**مثال - نجاح**:

```json
{
    "status": "success",
    "message": "يوجد 2 مدير نشط",
    "count": 2
}
```

**مثال - تحذير**:

```json
{
    "status": "warning",
    "message": "لا يوجد مديرين نشطين",
    "count": 0
}
```

---

### 5. فحص حجم قاعدة البيانات

**المفتاح**: `database_size`

**الحالات**:
- ✅ `success`: تم حساب الحجم بنجاح
- ℹ️ `info`: لا يمكن حساب الحجم (قد يكون بسبب صلاحيات قاعدة البيانات)

**مثال - نجاح**:

```json
{
    "status": "success",
    "message": "حجم قاعدة البيانات: 125.50 MB",
    "size_mb": 125.5
}
```

**مثال - معلومات**:

```json
{
    "status": "info",
    "message": "لا يمكن حساب حجم قاعدة البيانات"
}
```

---

## 📊 الحالة الإجمالية (Overall Status)

**المفتاح**: `overall_status`

**القيم**:
- `success`: جميع الفحوصات ناجحة
- `warning`: يوجد تحذيرات (لكن لا توجد أخطاء)
- `error`: يوجد أخطاء خطيرة

**المنطق**:
- إذا كان هناك أي `error` → `overall_status = "error"`
- إذا كان هناك أي `warning` (ولكن لا توجد أخطاء) → `overall_status = "warning"`
- خلاف ذلك → `overall_status = "success"`

---

## 🔍 أمثلة على الاستجابات

### حالة نجاح كاملة:

```json
{
    "success": true,
    "data": {
        "checks": {
            "database_connection": {
                "status": "success",
                "message": "الاتصال بقاعدة البيانات ناجح"
            },
            "table_app_users": {
                "status": "success",
                "message": "جدول app_users موجود"
            },
            "users_without_password": {
                "status": "success",
                "message": "جميع المستخدمين لديهم كلمات مرور",
                "count": 0
            },
            "active_admins": {
                "status": "success",
                "message": "يوجد 2 مدير نشط",
                "count": 2
            },
            "database_size": {
                "status": "success",
                "message": "حجم قاعدة البيانات: 125.50 MB",
                "size_mb": 125.5
            }
        },
        "overall_status": "success",
        "timestamp": "2025-12-15T10:30:00Z"
    }
}
```

### حالة مع تحذيرات:

```json
{
    "success": true,
    "data": {
        "checks": {
            "database_connection": {
                "status": "success",
                "message": "الاتصال بقاعدة البيانات ناجح"
            },
            "users_without_password": {
                "status": "warning",
                "message": "يوجد 3 مستخدمين بدون كلمة مرور",
                "count": 3
            },
            "active_admins": {
                "status": "success",
                "message": "يوجد 1 مدير نشط",
                "count": 1
            }
        },
        "overall_status": "warning",
        "timestamp": "2025-12-15T10:30:00Z"
    }
}
```

### حالة مع أخطاء:

```json
{
    "success": true,
    "data": {
        "checks": {
            "database_connection": {
                "status": "error",
                "message": "فشل الاتصال بقاعدة البيانات: Connection refused"
            },
            "table_app_users": {
                "status": "error",
                "message": "جدول app_users غير موجود"
            }
        },
        "overall_status": "error",
        "timestamp": "2025-12-15T10:30:00Z"
    }
}
```

### خطأ في الطلب (403):

```json
{
    "success": false,
    "message": "This action is unauthorized."
}
```

### خطأ في الخادم (500):

```json
{
    "success": false,
    "message": "فشل في فحص أمان قاعدة البيانات",
    "error": "Error message here"
}
```

---

## 📱 أمثلة في Frontend

### فحص الأمان الأساسي:

```javascript
fetch("http://domain.com/api/admin/database/security-check", {
    method: "GET",
    headers: {
        Authorization: `Bearer ${token}`,
    },
})
    .then((response) => response.json())
    .then((data) => {
        if (data.success) {
            console.log("الحالة الإجمالية:", data.data.overall_status);
            console.log("الفحوصات:", data.data.checks);
        }
    });
```

### عرض الفحوصات في واجهة المستخدم:

```javascript
function displaySecurityChecks(checks) {
    const checksContainer = document.getElementById("security-checks");

    Object.entries(checks).forEach(([key, check]) => {
        const checkElement = document.createElement("div");
        checkElement.className = `check-item status-${check.status}`;

        const icon = getStatusIcon(check.status);
        const message = check.message;

        checkElement.innerHTML = `
            <span class="icon">${icon}</span>
            <span class="message">${message}</span>
            ${check.count !== undefined ? `<span class="count">(${check.count})</span>` : ""}
        `;

        checksContainer.appendChild(checkElement);
    });
}

function getStatusIcon(status) {
    const icons = {
        success: "✅",
        warning: "⚠️",
        error: "❌",
        info: "ℹ️",
    };
    return icons[status] || "❓";
}
```

### عرض الحالة الإجمالية:

```javascript
function getOverallStatusBadge(overallStatus) {
    const badges = {
        success: {
            text: "آمن",
            class: "badge-success",
            color: "#28a745",
        },
        warning: {
            text: "تحذير",
            class: "badge-warning",
            color: "#ffc107",
        },
        error: {
            text: "خطأ",
            class: "badge-error",
            color: "#dc3545",
        },
    };

    return badges[overallStatus] || badges.error;
}

// استخدام
const badge = getOverallStatusBadge(data.data.overall_status);
console.log(`الحالة: ${badge.text} (${badge.color})`);
```

### تحديث تلقائي للفحص:

```javascript
// فحص كل 5 دقائق
setInterval(() => {
    fetchSecurityCheck();
}, 5 * 60 * 1000);
```

---

## 🎨 مثال على واجهة المستخدم

### HTML/CSS:

```html
<div class="security-dashboard">
    <div class="overall-status">
        <h2>حالة الأمان</h2>
        <div class="status-badge status-success">
            <span class="icon">✅</span>
            <span class="text">آمن</span>
        </div>
    </div>

    <div class="checks-list">
        <div class="check-item status-success">
            <span class="icon">✅</span>
            <span class="message">الاتصال بقاعدة البيانات ناجح</span>
        </div>
        <div class="check-item status-success">
            <span class="icon">✅</span>
            <span class="message">جميع المستخدمين لديهم كلمات مرور</span>
            <span class="count">(0)</span>
        </div>
        <div class="check-item status-warning">
            <span class="icon">⚠️</span>
            <span class="message">يوجد 3 مستخدمين بدون كلمة مرور</span>
            <span class="count">(3)</span>
        </div>
    </div>
</div>
```

```css
.security-dashboard {
    padding: 20px;
}

.overall-status {
    margin-bottom: 30px;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 10px 20px;
    border-radius: 5px;
    font-weight: bold;
}

.status-badge.status-success {
    background-color: #d4edda;
    color: #155724;
}

.status-badge.status-warning {
    background-color: #fff3cd;
    color: #856404;
}

.status-badge.status-error {
    background-color: #f8d7da;
    color: #721c24;
}

.check-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px;
    margin-bottom: 10px;
    border-radius: 5px;
    border-left: 4px solid;
}

.check-item.status-success {
    background-color: #d4edda;
    border-color: #28a745;
}

.check-item.status-warning {
    background-color: #fff3cd;
    border-color: #ffc107;
}

.check-item.status-error {
    background-color: #f8d7da;
    border-color: #dc3545;
}

.check-item .count {
    margin-left: auto;
    font-weight: bold;
}
```

---

## 🔄 حالات الاستخدام

### 1. لوحة التحكم الرئيسية:

- عرض الحالة الإجمالية للأمان
- استخدام: `/security-check`

### 2. صفحة مراقبة الأمان:

- عرض جميع الفحوصات بالتفصيل
- تحديث تلقائي كل 5 دقائق

### 3. التنبيهات:

- إرسال تنبيه عند وجود `error` أو `warning`
- إشعار المدير فوراً

### 4. التقارير:

- حفظ نتائج الفحص في قاعدة البيانات
- إنشاء تقارير دورية

---

## ✅ نصائح للربط

1. **للـ Token**: استخدم `Bearer Token` في Authorization header
2. **للأداء**: لا تفحص أكثر من مرة كل 5 دقائق (للتحديث التلقائي)
3. **للعرض**: استخدم الألوان والأيقونات المناسبة لكل حالة
4. **للتنبيهات**: ركز على `error` و `warning` فقط
5. **للحجم**: اعرض حجم قاعدة البيانات بشكل واضح (MB/GB)

---

## 🚨 إجراءات عند اكتشاف مشاكل

### عند `error`:

1. **فشل الاتصال**: تحقق من إعدادات قاعدة البيانات
2. **جدول مفقود**: قم بتشغيل الـ migrations المطلوبة
3. **مستخدمين بدون كلمات مرور**: قم بتعيين كلمات مرور فوراً

### عند `warning`:

1. **مستخدمين بدون كلمات مرور**: راجع قائمة المستخدمين
2. **لا يوجد مديرين نشطين**: قم بتفعيل حساب مدير على الأقل

---

## 📊 مثال على React Component

```jsx
import React, { useState, useEffect } from "react";

function SecurityCheck() {
    const [checks, setChecks] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchSecurityCheck();
        // تحديث كل 5 دقائق
        const interval = setInterval(fetchSecurityCheck, 5 * 60 * 1000);
        return () => clearInterval(interval);
    }, []);

    const fetchSecurityCheck = async () => {
        try {
            const response = await fetch(
                "http://domain.com/api/admin/database/security-check",
                {
                    headers: {
                        Authorization: `Bearer ${token}`,
                    },
                }
            );
            const data = await response.json();
            if (data.success) {
                setChecks(data.data);
            }
        } catch (error) {
            console.error("Error fetching security check:", error);
        } finally {
            setLoading(false);
        }
    };

    if (loading) return <div>جاري التحميل...</div>;
    if (!checks) return <div>فشل في جلب البيانات</div>;

    const getStatusColor = (status) => {
        const colors = {
            success: "#28a745",
            warning: "#ffc107",
            error: "#dc3545",
            info: "#17a2b8",
        };
        return colors[status] || "#6c757d";
    };

    return (
        <div className="security-check">
            <div className="overall-status">
                <h2>حالة الأمان</h2>
                <div
                    className="status-badge"
                    style={{
                        backgroundColor: getStatusColor(checks.overall_status),
                    }}
                >
                    {checks.overall_status === "success" && "✅ آمن"}
                    {checks.overall_status === "warning" && "⚠️ تحذير"}
                    {checks.overall_status === "error" && "❌ خطأ"}
                </div>
            </div>

            <div className="checks-list">
                {Object.entries(checks.checks).map(([key, check]) => (
                    <div
                        key={key}
                        className="check-item"
                        style={{
                            borderLeftColor: getStatusColor(check.status),
                        }}
                    >
                        <span>{getStatusIcon(check.status)}</span>
                        <span>{check.message}</span>
                        {check.count !== undefined && (
                            <span className="count">({check.count})</span>
                        )}
                    </div>
                ))}
            </div>
        </div>
    );
}
```

---

## 📝 ملاحظات مهمة

1. **الصلاحيات**: هذا الـ endpoint متاح فقط للمديرين
2. **الأداء**: الفحص قد يستغرق بضع ثوانٍ حسب حجم قاعدة البيانات
3. **التحديث**: يُنصح بعدم الفحص أكثر من مرة كل 5 دقائق
4. **الحجم**: حساب حجم قاعدة البيانات قد لا يعمل في بعض بيئات الاستضافة

