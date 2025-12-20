# 💰 واجهة برمجة تطبيقات الصندوق والسحوبات

## نظرة عامة

نظام شامل لإدارة الصندوق (Cash Box) يتيح للمدير والمحاسب تتبع جميع السحوبات من الصندوق مع توثيق كامل لكل عملية سحب. يتم حساب رصيد الصندوق تلقائياً بناءً على المعادلة: **الإيرادات - السحوبات**.

---

## 📊 مفهوم الصندوق

الصندوق = **إجمالي الإيرادات - إجمالي السحوبات**

-   **الإيرادات**: جميع المدفوعات المقبولة والمؤكدة في جدول `revenues`
-   **السحوبات**: جميع عمليات السحب المسجلة في جدول `cash_withdrawals`
-   **الرصيد**: الفرق بين الإيرادات والسحوبات لكل عملة (USD, SYP, TRY)

---

## 🚀 Endpoints المتاحة

### 1. عرض جميع السحوبات

```http
GET /api/admin/cash-withdrawals
Authorization: Bearer {token}
```

**الوصف**: عرض جميع السحوبات مع إمكانية الفلترة والبحث  
**الصلاحيات**: المحاسب والمدير فقط

**المعاملات الاختيارية**:

-   `currency` - فلترة حسب العملة (USD, SYP, TRY)
-   `category` - فلترة حسب الفئة
-   `from_date` - تاريخ البداية (Y-m-d)
-   `to_date` - تاريخ النهاية (Y-m-d)
-   `withdrawn_by` - فلترة حسب المستخدم الذي قام بالسحب
-   `search` - بحث في سبب السحب، الرقم المرجعي، أو الوصف
-   `per_page` - عدد النتائج في الصفحة (افتراضي: 15)
-   `page` - رقم الصفحة

**مثال**:

```http
GET /api/admin/cash-withdrawals?currency=USD&category=operational&from_date=2024-01-01&per_page=20
```

**الاستجابة الناجحة** (200):

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "amount": 500000.0,
            "currency": "USD",
            "reason": "دفع فاتورة الكهرباء",
            "description": "فاتورة شهر ديسمبر 2024",
            "reference_number": "INV-2024-12-001",
            "category": "utilities",
            "category_label": "فواتير",
            "withdrawal_date": "2024-12-20",
            "attachments": [],
            "withdrawn_by": {
                "id": 2,
                "username": "admin",
                "firstname": "أحمد"
            },
            "created_at": "2024-12-20 10:30:00",
            "updated_at": "2024-12-20 10:30:00"
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

---

### 2. إنشاء سحب جديد

```http
POST /api/admin/cash-withdrawals
Authorization: Bearer {token}
Content-Type: application/json
```

**الوصف**: تسجيل سحب جديد من الصندوق  
**الصلاحيات**: المحاسب والمدير فقط

**المعاملات المطلوبة**:

```json
{
    "amount": 500.0,
    "currency": "USD",
    "reason": "دفع فاتورة الكهرباء",
    "category": "utilities",
    "withdrawal_date": "2024-12-20"
}
```

**المعاملات الاختيارية**:

```json
{
    "description": "فاتورة شهر ديسمبر 2024",
    "reference_number": "INV-2024-12-001",
    "attachments": ["path/to/receipt.jpg"]
}
```

**فئات السحب المتاحة** (`category`):

-   `operational` - تشغيلية
-   `maintenance` - صيانة
-   `salary` - رواتب
-   `utilities` - فواتير
-   `supplies` - مستلزمات
-   `marketing` - تسويق
-   `emergency` - طوارئ
-   `other` - أخرى

**الاستجابة الناجحة** (201):

```json
{
    "success": true,
    "message": "تم تسجيل السحب بنجاح",
    "data": {
        "id": 1,
        "amount": 500000.0,
        "currency": "IQD",
        "reason": "دفع فاتورة الكهرباء",
        "category": "utilities",
        "category_label": "فواتير",
        "withdrawal_date": "2024-12-20",
        "withdrawn_by": {
            "id": 2,
            "username": "admin",
            "firstname": "أحمد"
        }
    }
}
```

**أخطاء محتملة**:

-   `422` - خطأ في التحقق من البيانات
-   `403` - ليس لديك صلاحية

---

### 3. عرض سحب محدد

```http
GET /api/admin/cash-withdrawals/{id}
Authorization: Bearer {token}
```

**الوصف**: عرض تفاصيل سحب محدد  
**الصلاحيات**: المحاسب والمدير فقط

**الاستجابة الناجحة** (200):

```json
{
    "success": true,
    "data": {
        "id": 1,
        "amount": 500000.0,
        "currency": "IQD",
        "reason": "دفع فاتورة الكهرباء",
        "description": "فاتورة شهر ديسمبر 2024",
        "reference_number": "INV-2024-12-001",
        "category": "utilities",
        "category_label": "فواتير",
        "withdrawal_date": "2024-12-20",
        "attachments": ["path/to/receipt.jpg"],
        "withdrawn_by": {
            "id": 2,
            "username": "admin",
            "firstname": "أحمد"
        },
        "created_at": "2024-12-20 10:30:00",
        "updated_at": "2024-12-20 10:30:00"
    }
}
```

---

### 4. تحديث سحب

```http
PUT /api/admin/cash-withdrawals/{id}
Authorization: Bearer {token}
Content-Type: application/json
```

**الوصف**: تحديث بيانات سحب موجود  
**الصلاحيات**: المحاسب والمدير فقط

**المعاملات** (جميعها اختيارية):

```json
{
    "amount": 600000.0,
    "currency": "IQD",
    "reason": "دفع فاتورة الكهرباء والماء",
    "description": "فاتورة شهر ديسمبر 2024 - محدثة",
    "reference_number": "INV-2024-12-001-UPDATED",
    "category": "utilities",
    "withdrawal_date": "2024-12-20",
    "attachments": ["path/to/receipt.jpg", "path/to/bill.pdf"]
}
```

**الاستجابة الناجحة** (200):

```json
{
    "success": true,
    "message": "تم تحديث السحب بنجاح",
    "data": {
        "id": 1,
        "amount": 600000.0,
        "currency": "IQD",
        "reason": "دفع فاتورة الكهرباء والماء",
        ...
    }
}
```

---

### 5. حذف سحب

```http
DELETE /api/admin/cash-withdrawals/{id}
Authorization: Bearer {token}
```

**الوصف**: حذف سحب (المدير فقط)  
**الصلاحيات**: المدير فقط

**الاستجابة الناجحة** (200):

```json
{
    "success": true,
    "message": "تم حذف السحب بنجاح"
}
```

---

### 6. رصيد الصندوق

```http
GET /api/admin/cash-withdrawals/balance
Authorization: Bearer {token}
```

**الوصف**: حساب رصيد الصندوق (الإيرادات - السحوبات)  
**الصلاحيات**: المحاسب والمدير فقط

**المعاملات الاختيارية**:

-   `from_date` - تاريخ البداية (Y-m-d)
-   `to_date` - تاريخ النهاية (Y-m-d)

**مثال**:

```http
GET /api/admin/cash-withdrawals/balance?from_date=2024-01-01&to_date=2024-12-31
```

**الاستجابة الناجحة** (200):

```json
{
    "success": true,
    "data": {
        "by_currency": {
            "USD": {
                "total_revenue": 10000.0,
                "total_withdrawals": 2000.0,
                "balance": 8000.0
            },
            "SYP": {
                "total_revenue": 5000000.0,
                "total_withdrawals": 1000000.0,
                "balance": 4000000.0
            },
            "TRY": {
                "total_revenue": 50000.0,
                "total_withdrawals": 10000.0,
                "balance": 40000.0
            }
        },
        "summary": {
            "total_revenue": {
                "USD": 10000.0,
                "SYP": 5000000.0,
                "TRY": 50000.0
            },
            "total_withdrawals": {
                "USD": 2000.0,
                "SYP": 1000000.0,
                "TRY": 10000.0
            },
            "total_balance": {
                "USD": 8000.0,
                "SYP": 4000000.0,
                "TRY": 40000.0
            }
        }
    }
}
```

---

### 7. إحصائيات السحوبات

```http
GET /api/admin/cash-withdrawals/statistics
Authorization: Bearer {token}
```

**الوصف**: إحصائيات شاملة للسحوبات حسب الفئة والعملة  
**الصلاحيات**: المحاسب والمدير فقط

**المعاملات الاختيارية**:

-   `from_date` (Y-m-d) - تاريخ البداية للفلترة
-   `to_date` (Y-m-d) - تاريخ النهاية للفلترة

**ملاحظات مهمة**:

-   الفلترة تتم حسب `withdrawal_date` وليس `created_at`
-   يتم إرجاع جميع الفئات حتى لو كانت القيم 0
-   `total_count` هو العدد الإجمالي لجميع السحوبات (بعد تطبيق الفلاتر)
-   `total_by_currency` يحتوي على مجموع المبالغ لكل عملة

**مثال**:

```http
GET /api/admin/cash-withdrawals/statistics?from_date=2024-01-01&to_date=2024-12-31
```

**الاستجابة الناجحة** (200):

```json
{
    "success": true,
    "data": {
        "total_count": 50,
        "total_by_currency": {
            "USD": 2000.0,
            "SYP": 1000000.0,
            "TRY": 10000.0
        },
        "by_category": {
            "operational": {
                "count": 10,
                "total_usd": 500.0,
                "total_syp": 500000.0,
                "total_try": 2000.0
            },
            "maintenance": {
                "count": 8,
                "total_usd": 300.0,
                "total_syp": 300000.0,
                "total_try": 5000.0
            },
            "salary": {
                "count": 5,
                "total_usd": 1000.0,
                "total_syp": 0.0,
                "total_try": 0.0
            },
            "utilities": {
                "count": 10,
                "total_usd": 500.0,
                "total_syp": 500000.0,
                "total_try": 2000.0
            },
            "supplies": {
                "count": 7,
                "total_usd": 200.0,
                "total_syp": 0.0,
                "total_try": 0.0
            },
            "marketing": {
                "count": 5,
                "total_usd": 300.0,
                "total_syp": 200000.0,
                "total_try": 3000.0
            },
            "emergency": {
                "count": 3,
                "total_usd": 100.0,
                "total_syp": 0.0,
                "total_try": 0.0
            },
            "other": {
                "count": 2,
                "total_usd": 100.0,
                "total_syp": 0.0,
                "total_try": 0.0
            }
        }
    }
}
```

**هيكل البيانات**:

1. **`total_count`** (integer): إجمالي عدد السحوبات (بعد تطبيق الفلاتر)

2. **`total_by_currency`** (object): إجمالي السحوبات حسب العملة (مجموع المبالغ)

    - `USD` (float): إجمالي السحوبات بالدولار
    - `SYP` (float): إجمالي السحوبات بالليرة السورية
    - `TRY` (float): إجمالي السحوبات بالليرة التركية

3. **`by_category`** (object): السحوبات حسب الفئة (يحتوي على جميع الفئات حتى لو كانت 0)
    - كل فئة تحتوي على:
        - `count` (integer): عدد السحوبات في هذه الفئة
        - `total_usd` (float): مجموع المبالغ بالدولار
        - `total_syp` (float): مجموع المبالغ بالليرة السورية
        - `total_try` (float): مجموع المبالغ بالليرة التركية

**الفئات المتاحة**:

-   `operational` - تشغيلية
-   `maintenance` - صيانة
-   `salary` - رواتب
-   `utilities` - فواتير
-   `supplies` - مستلزمات
-   `marketing` - تسويق
-   `emergency` - طوارئ
-   `other` - أخرى

**أخطاء محتملة**:

-   `422` - خطأ في صيغة التاريخ (يجب أن يكون بصيغة Y-m-d)
-   `403` - ليس لديك صلاحية

---

## 📊 إحصائيات الصندوق في Dashboard

تم إضافة إحصائيات الصندوق إلى endpoint لوحة المعلومات:

```http
GET /api/admin/dashboard/statistics
Authorization: Bearer {token}
```

**قسم الصندوق في الاستجابة**:

```json
{
    "success": true,
    "data": {
        ...
        "cash_box": {
            "total_revenue": {
                "USD": 10000.0,
                "SYP": 5000000.0,
                "TRY": 50000.0
            },
            "total_withdrawals": {
                "USD": 2000.0,
                "SYP": 1000000.0,
                "TRY": 10000.0
            },
            "balance": {
                "USD": 8000.0,
                "SYP": 4000000.0,
                "TRY": 40000.0
            },
            "total_withdrawals_count": 50
        }
    }
}
```

---

## 📝 فئات السحب (Categories)

| الفئة (Category) | التسمية العربية | الاستخدام                        |
| ---------------- | --------------- | -------------------------------- |
| `operational`    | تشغيلية         | مصاريف تشغيلية عامة              |
| `maintenance`    | صيانة           | صيانة المعدات والمباني           |
| `salary`         | رواتب           | رواتب الموظفين                   |
| `utilities`      | فواتير          | فواتير الكهرباء، الماء، الإنترنت |
| `supplies`       | مستلزمات        | شراء مستلزمات مكتبية أو تقنية    |
| `marketing`      | تسويق           | مصاريف التسويق والإعلان          |
| `emergency`      | طوارئ           | مصاريف طارئة غير متوقعة          |
| `other`          | أخرى            | مصاريف أخرى غير مصنفة            |

---

## 🔐 الأمان والصلاحيات

### الصلاحيات المطلوبة

-   **المحاسب** (`role = 1`): يمكنه:

    -   عرض جميع السحوبات
    -   إنشاء سحب جديد
    -   تحديث السحوبات
    -   عرض رصيد الصندوق
    -   عرض الإحصائيات

-   **المدير** (`role = 2`): يمكنه:
    -   جميع صلاحيات المحاسب
    -   حذف السحوبات

---

## 📋 أمثلة الاستخدام

### مثال 1: إنشاء سحب جديد

```javascript
async function createWithdrawal(withdrawalData) {
    const response = await fetch("/api/admin/cash-withdrawals", {
        method: "POST",
        headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            amount: 500,
            currency: "USD",
            reason: "دفع فاتورة الكهرباء",
            description: "فاتورة شهر ديسمبر 2024",
            reference_number: "INV-2024-12-001",
            category: "utilities",
            withdrawal_date: "2024-12-20",
        }),
    });

    const result = await response.json();
    if (result.success) {
        console.log("تم تسجيل السحب بنجاح:", result.data);
    }
}
```

### مثال 2: عرض رصيد الصندوق

```javascript
async function getCashBoxBalance() {
    const response = await fetch("/api/admin/cash-withdrawals/balance", {
        headers: {
            Authorization: `Bearer ${token}`,
        },
    });

    const result = await response.json();
    if (result.success) {
        const balance = result.data.summary.total_balance;
        console.log(`رصيد الصندوق - USD: ${balance.USD.toLocaleString()}`);
        console.log(`رصيد الصندوق - SYP: ${balance.SYP.toLocaleString()}`);
        console.log(`رصيد الصندوق - TRY: ${balance.TRY.toLocaleString()}`);
    }
}
```

### مثال 3: عرض السحوبات مع فلترة

```javascript
async function getWithdrawals(filters) {
    const params = new URLSearchParams();

    if (filters.currency) params.append("currency", filters.currency);
    if (filters.category) params.append("category", filters.category);
    if (filters.fromDate) params.append("from_date", filters.fromDate);
    if (filters.toDate) params.append("to_date", filters.toDate);
    if (filters.search) params.append("search", filters.search);

    const response = await fetch(
        `/api/admin/cash-withdrawals?${params.toString()}`,
        {
            headers: {
                Authorization: `Bearer ${token}`,
            },
        }
    );

    const result = await response.json();
    return result.data;
}
```

### مثال 4: عرض إحصائيات السحوبات حسب الفئة

```javascript
async function displayWithdrawalStats(fromDate = null, toDate = null) {
    const params = new URLSearchParams();
    if (fromDate) params.append("from_date", fromDate);
    if (toDate) params.append("to_date", toDate);

    const url = `/api/admin/cash-withdrawals/statistics${
        params.toString() ? `?${params.toString()}` : ""
    }`;

    const response = await fetch(url, {
        headers: {
            Authorization: `Bearer ${token}`,
        },
    });

    const result = await response.json();
    if (result.success) {
        const stats = result.data;

        console.log(`إجمالي عدد السحوبات: ${stats.total_count}`);
        console.log(
            `إجمالي السحوبات - USD: ${stats.total_by_currency.USD.toLocaleString()}`
        );
        console.log(
            `إجمالي السحوبات - SYP: ${stats.total_by_currency.SYP.toLocaleString()}`
        );
        console.log(
            `إجمالي السحوبات - TRY: ${stats.total_by_currency.TRY.toLocaleString()}`
        );

        // عرض حسب الفئة (جميع الفئات موجودة حتى لو كانت 0)
        const categoryLabels = {
            operational: "تشغيلية",
            maintenance: "صيانة",
            salary: "رواتب",
            utilities: "فواتير",
            supplies: "مستلزمات",
            marketing: "تسويق",
            emergency: "طوارئ",
            other: "أخرى",
        };

        Object.entries(stats.by_category).forEach(([category, data]) => {
            console.log(`\n${categoryLabels[category]} (${category}):`);
            console.log(`  عدد السحوبات: ${data.count}`);
            console.log(`  USD: ${data.total_usd.toLocaleString()}`);
            console.log(`  SYP: ${data.total_syp.toLocaleString()}`);
            console.log(`  TRY: ${data.total_try.toLocaleString()}`);
        });
    }
}

// استخدام بدون فلترة
displayWithdrawalStats();

// استخدام مع فلترة حسب التاريخ
displayWithdrawalStats("2024-01-01", "2024-12-31");
```

---

## ⚠️ معالجة الأخطاء

### الأخطاء المحتملة

#### 422 Validation Error

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "amount": ["المبلغ مطلوب"],
        "currency": ["العملة يجب أن تكون USD أو SYP أو TRY"]
    }
}
```

#### 403 Forbidden

```json
{
    "message": "This action is unauthorized."
}
```

**الحل**: المستخدم ليس لديه صلاحية الوصول

#### 404 Not Found

```json
{
    "message": "No query results for model [App\\Models\\CashWithdrawal] {id}"
}
```

**الحل**: السحب المطلوب غير موجود

---

## 📝 ملاحظات مهمة

1. **تتبع كامل**: كل سحب يتم تسجيله مع:

    - المستخدم الذي قام بالسحب
    - التاريخ والوقت
    - السبب والوصف
    - الفئة والرقم المرجعي
    - المرفقات (إن وجدت)

2. **حساب تلقائي**: رصيد الصندوق يتم حسابه تلقائياً من:

    - إجمالي الإيرادات من جدول `revenues`
    - إجمالي السحوبات من جدول `cash_withdrawals`

3. **دعم العملات**: النظام يدعم USD (الدولار)، SYP (الليرة السورية)، و TRY (الليرة التركية) بشكل منفصل

4. **الفلترة**: يمكن فلترة السحوبات حسب:

    - العملة
    - الفئة
    - التاريخ
    - المستخدم
    - البحث النصي

5. **الأمان**: فقط المحاسب والمدير يمكنهم الوصول إلى هذه الـ endpoints

---

## 🚀 التحسينات المستقبلية

-   [ ] إضافة إشعارات عند السحب
-   [ ] إضافة موافقات متعددة المستويات للسحوبات الكبيرة
-   [ ] إضافة تقارير PDF للسحوبات
-   [ ] إضافة تصدير Excel
-   [ ] إضافة ميزة الميزانية المخططة vs الفعلية
-   [ ] إضافة تنبيهات عند انخفاض رصيد الصندوق

---

**آخر تحديث**: 2024-12-20  
**الإصدار**: 1.0.0
