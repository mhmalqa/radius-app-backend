# 📊 واجهة برمجة تطبيقات لوحة المعلومات والإحصائيات

## نظرة عامة

تم إنشاء endpoint موحد ومحسّن لجلب جميع إحصائيات لوحة المعلومات في استعلام واحد سريع مع نظام تخزين مؤقت (Caching) لتحسين الأداء.

---

## 🚀 Endpoint الرئيسي

### جلب جميع إحصائيات لوحة المعلومات

```http
GET /api/admin/dashboard/statistics
Authorization: Bearer {token}
```

**الوصف**: جلب جميع إحصائيات لوحة المعلومات في استعلام واحد محسّن  
**الصلاحيات**: المحاسب والمدير فقط (`role:admin,accountant`)

**المعاملات الاختيارية**:

-   `from_date` - تاريخ البداية (Y-m-d) - فلترة حسب التاريخ
-   `to_date` - تاريخ النهاية (Y-m-d) - فلترة حسب التاريخ

**مثال**:

```http
GET /api/admin/dashboard/statistics?from_date=2024-01-01&to_date=2024-12-31
```

**الاستجابة الناجحة** (200):

```json
{
    "success": true,
    "data": {
        "users": {
            "total": 150,
            "active": 120,
            "regular": 145,
            "accountants": 3,
            "admins": 2
        },
        "payment_requests": {
            "total": 500,
            "pending": 25,
            "approved": 400,
            "rejected": 50,
            "paid": 380,
            "deferred_unpaid": 20
        },
        "revenues": {
            "total_transactions": 400,
            "by_currency": {
                "USD": {
                    "total": 10000.0,
                    "count": 50,
                    "average": 200.0
                },
                "SYP": {
                    "total": 5000000.0,
                    "count": 30,
                    "average": 166666.67
                },
                "TRY": {
                    "total": 50000.0,
                    "count": 20,
                    "average": 2500.0
                }
            },
            "by_payment_type": {
                "online": {
                    "USD": {
                        "total": 5000.0,
                        "count": 25
                    },
                    "SYP": {
                        "total": 2000000.0,
                        "count": 15
                    },
                    "TRY": {
                        "total": 10000.0,
                        "count": 10
                    }
                },
                "cash": {
                    "USD": {
                        "total": 3000.0,
                        "count": 12
                    },
                    "SYP": {
                        "total": 1500000.0,
                        "count": 8
                    },
                    "TRY": {
                        "total": 5000.0,
                        "count": 5
                    }
                }
            },
            "daily_revenue": [
                {
                    "date": "2024-12-20",
                    "currencies": [
                        {
                            "currency": "USD",
                            "total": 1000.0,
                            "count": 5
                        },
                        {
                            "currency": "SYP",
                            "total": 500000.0,
                            "count": 3
                        },
                        {
                            "currency": "TRY",
                            "total": 5000.0,
                            "count": 2
                        }
                    ]
                }
            ],
            "top_users": {
                "USD": [
                    {
                        "id": 1,
                        "username": "user001",
                        "phone": "07501234567",
                        "currency": "USD",
                        "total": 5000.0,
                        "count": 5
                    }
                ],
                "SYP": [
                    {
                        "id": 2,
                        "username": "user002",
                        "phone": "07501234568",
                        "currency": "SYP",
                        "total": 2000000.0,
                        "count": 10
                    }
                ],
                "TRY": [
                    {
                        "id": 3,
                        "username": "user003",
                        "phone": "07501234569",
                        "currency": "TRY",
                        "total": 25000.0,
                        "count": 8
                    }
                ]
            }
        },
        "maintenance_requests": {
            "total": 50,
            "pending": 10,
            "in_progress": 5,
            "completed": 30,
            "cancelled": 5
        }
    }
}
```

---

## 📈 الإحصائيات المتاحة

### 1. إحصائيات المستخدمين (`users`)

| الحقل         | النوع   | الوصف                                    |
| ------------- | ------- | ---------------------------------------- |
| `total`       | integer | إجمالي عدد المستخدمين                    |
| `active`      | integer | عدد المستخدمين النشطين (`is_active = 1`) |
| `regular`     | integer | عدد المستخدمين العاديين (`role = 0`)     |
| `accountants` | integer | عدد المحاسبين (`role = 1`)               |
| `admins`      | integer | عدد المديرين (`role = 2`)                |

**مثال الاستخدام**:

```javascript
const stats = await fetch("/api/admin/dashboard/statistics");
const data = await stats.json();
console.log(`إجمالي المستخدمين: ${data.data.users.total}`);
console.log(`المستخدمين النشطين: ${data.data.users.active}`);
```

---

### 2. إحصائيات طلبات الدفع (`payment_requests`)

| الحقل             | النوع   | الوصف                                |
| ----------------- | ------- | ------------------------------------ |
| `total`           | integer | إجمالي عدد طلبات الدفع               |
| `pending`         | integer | عدد الطلبات المعلقة (`status = 0`)   |
| `approved`        | integer | عدد الطلبات المقبولة (`status = 1`)  |
| `rejected`        | integer | عدد الطلبات المرفوضة (`status = 2`)  |
| `paid`            | integer | عدد الطلبات المدفوعة (`is_paid = 1`) |
| `deferred_unpaid` | integer | عدد الدفعات المؤجلة غير المدفوعة     |

**مثال الاستخدام**:

```javascript
const pendingRequests = data.data.payment_requests.pending;
const deferredAmount = data.data.payment_requests.deferred_unpaid;
```

---

### 3. إحصائيات الإيرادات (`revenues`)

#### 3.1 الإحصائيات الأساسية

| الحقل                | النوع   | الوصف                |
| -------------------- | ------- | -------------------- |
| `total_transactions` | integer | إجمالي عدد المعاملات |

#### 3.2 الإيرادات حسب العملة (`by_currency`)

كل عملة تحتوي على:

-   `total` (float) - إجمالي الإيرادات
-   `count` (integer) - عدد المعاملات
-   `average` (float) - متوسط الإيرادات لكل معاملة

| الحقل | النوع  | الوصف                     |
| ----- | ------ | ------------------------- |
| `USD` | object | إحصائيات الدولار الأمريكي |
| `SYP` | object | إحصائيات الليرة السورية   |
| `TRY` | object | إحصائيات الليرة التركية   |

#### 3.3 الإيرادات حسب نوع الدفع (`by_payment_type`)

**البنية الجديدة**: تفصيل لكل عملة (USD, SYP, TRY)

| الحقل    | النوع  | الوصف                               |
| -------- | ------ | ----------------------------------- |
| `online` | object | إيرادات الدفع الإلكتروني (لكل عملة) |
| `cash`   | object | إيرادات الدفع النقدي (لكل عملة)     |

**هيكل كل نوع دفع**:

-   `USD` (object):
    -   `total` (float) - إجمالي الإيرادات بالدولار
    -   `count` (integer) - عدد المعاملات بالدولار
-   `SYP` (object):
    -   `total` (float) - إجمالي الإيرادات بالليرة السورية
    -   `count` (integer) - عدد المعاملات بالليرة السورية
-   `TRY` (object):
    -   `total` (float) - إجمالي الإيرادات بالليرة التركية
    -   `count` (integer) - عدد المعاملات بالليرة التركية

**ملاحظات**:

-   جميع العملات موجودة حتى لو كانت القيمة 0
-   الفلترة (`from_date`, `to_date`) تطبق على هذه البيانات
-   الفلترة تتم حسب `payment_date` وليس `created_at`

#### 3.4 الإيرادات اليومية (`daily_revenue`)

مصفوفة من الكائنات تحتوي على:

-   `date` (string) - التاريخ (Y-m-d)
-   `currencies` (array) - مصفوفة من الإيرادات لكل عملة في ذلك اليوم:
    -   `currency` (string) - العملة (USD, SYP, TRY)
    -   `total` (float) - إجمالي الإيرادات في ذلك اليوم لتلك العملة
    -   `count` (integer) - عدد المعاملات في ذلك اليوم لتلك العملة

**ملاحظة**: يتم إرجاع آخر 30 يوم فقط، وكل يوم يحتوي على إيرادات لكل عملة بشكل منفصل

**مثال**:

```json
{
    "date": "2024-12-20",
    "currencies": [
        {
            "currency": "USD",
            "total": 1000.0,
            "count": 5
        },
        {
            "currency": "SYP",
            "total": 500000.0,
            "count": 3
        },
        {
            "currency": "TRY",
            "total": 5000.0,
            "count": 2
        }
    ]
}
```

#### 3.5 أفضل المستخدمين (`top_users`)

كائن يحتوي على أفضل 10 مستخدمين لكل عملة بشكل منفصل:

-   `USD` (array) - أفضل 10 مستخدمين بالدولار
-   `SYP` (array) - أفضل 10 مستخدمين بالليرة السورية
-   `TRY` (array) - أفضل 10 مستخدمين بالليرة التركية

كل مستخدم يحتوي على:

-   `id` (integer) - معرف المستخدم
-   `username` (string) - اسم المستخدم
-   `phone` (string) - رقم الهاتف
-   `currency` (string) - العملة
-   `total` (float) - إجمالي الإيرادات بتلك العملة
-   `count` (integer) - عدد المعاملات بتلك العملة

**مثال**:

```json
{
    "USD": [
        {
            "id": 1,
            "username": "user001",
            "phone": "07501234567",
            "currency": "USD",
            "total": 5000.0,
            "count": 5
        }
    ],
    "SYP": [...],
    "TRY": [...]
}
```

**مثال الاستخدام**:

```javascript
// عرض الإيرادات حسب نوع الدفع (لكل عملة)
const byPaymentType = data.data.revenues.by_payment_type;

console.log("إيرادات الدفع الإلكتروني:");
Object.entries(byPaymentType.online).forEach(([currency, data]) => {
    console.log(
        `  ${currency}: ${data.total.toLocaleString()} (${data.count} معاملة)`
    );
});

console.log("\nإيرادات الدفع النقدي:");
Object.entries(byPaymentType.cash).forEach(([currency, data]) => {
    console.log(
        `  ${currency}: ${data.total.toLocaleString()} (${data.count} معاملة)`
    );
});

// عرض الإيرادات اليومية في رسم بياني (لكل عملة)
const dailyRevenue = data.data.revenues.daily_revenue;
dailyRevenue.forEach((day) => {
    console.log(`تاريخ: ${day.date}`);
    day.currencies.forEach((currencyData) => {
        console.log(
            `  ${
                currencyData.currency
            }: ${currencyData.total.toLocaleString()} (${
                currencyData.count
            } معاملة)`
        );
    });
});

// عرض أفضل المستخدمين (لكل عملة)
const topUsers = data.data.revenues.top_users;
Object.entries(topUsers).forEach(([currency, users]) => {
    console.log(`\nأفضل المستخدمين - ${currency}:`);
    users.forEach((user, index) => {
        console.log(
            `${index + 1}. ${
                user.username
            }: ${user.total.toLocaleString()} ${currency} (${
                user.count
            } معاملة)`
        );
    });
});
```

---

### 4. إحصائيات طلبات الصيانة (`maintenance_requests`)

| الحقل         | النوع   | الوصف                                              |
| ------------- | ------- | -------------------------------------------------- |
| `total`       | integer | إجمالي عدد طلبات الصيانة                           |
| `pending`     | integer | عدد الطلبات المعلقة (`status = "pending"`)         |
| `in_progress` | integer | عدد الطلبات قيد التنفيذ (`status = "in_progress"`) |
| `completed`   | integer | عدد الطلبات المكتملة (`status = "completed"`)      |
| `cancelled`   | integer | عدد الطلبات الملغاة (`status = "cancelled"`)       |

**مثال الاستخدام**:

```javascript
const pendingMaintenance = data.data.maintenance_requests.pending;
const completedMaintenance = data.data.maintenance_requests.completed;
```

---

## ⚡ تحسينات الأداء

### 1. نظام التخزين المؤقت (Caching)

-   **مدة التخزين**: 5 دقائق (300 ثانية)
-   **المفتاح**: يتم إنشاء مفتاح ديناميكي بناءً على الفلاتر المستخدمة
-   **الفائدة**: تقليل وقت الاستجابة من عدة ثوانٍ إلى أقل من ثانية

### 2. استعلامات محسّنة

-   **قبل التحسين**: ~10 استعلامات منفصلة
-   **بعد التحسين**: 4 استعلامات فقط
-   **التحسين**: استخدام `CASE WHEN` للـ aggregation في استعلام واحد

### 3. تقليل البيانات المسترجعة

-   استخدام `limit()` لتقليل البيانات غير الضرورية
-   استخدام `selectRaw()` لاختيار الأعمدة المطلوبة فقط

---

## 🔄 مسح الكاش

### مسح كاش لوحة المعلومات

```http
POST /api/admin/dashboard/clear-cache
Authorization: Bearer {token}
```

**الوصف**: مسح كاش لوحة المعلومات لإجبار النظام على إعادة حساب الإحصائيات  
**الصلاحيات**: المحاسب والمدير فقط

**الاستجابة**:

```json
{
    "success": true,
    "message": "تم مسح الكاش بنجاح"
}
```

**متى تستخدم**:

-   بعد إجراء تغييرات كبيرة في البيانات
-   عند الحاجة إلى إحصائيات فورية (بدون كاش)
-   بعد تحديث البيانات يدوياً

---

## 📊 أمثلة الاستخدام في Frontend

### مثال 1: عرض الإحصائيات الأساسية

```javascript
async function loadDashboardStats() {
    try {
        const response = await fetch("/api/admin/dashboard/statistics", {
            headers: {
                Authorization: `Bearer ${token}`,
                "Content-Type": "application/json",
            },
        });

        const result = await response.json();

        if (result.success) {
            const stats = result.data;

            // عرض إحصائيات المستخدمين
            document.getElementById("total-users").textContent =
                stats.users.total;
            document.getElementById("active-users").textContent =
                stats.users.active;

            // عرض إحصائيات طلبات الدفع
            document.getElementById("pending-payments").textContent =
                stats.payment_requests.pending;

            // عرض إجمالي الإيرادات لكل عملة
            const revenueUSD = stats.revenues.by_currency.USD.total;
            const revenueSYP = stats.revenues.by_currency.SYP.total;
            const revenueTRY = stats.revenues.by_currency.TRY.total;
            document.getElementById("total-revenue-usd").textContent =
                revenueUSD.toLocaleString() + " USD";
            document.getElementById("total-revenue-syp").textContent =
                revenueSYP.toLocaleString() + " SYP";
            document.getElementById("total-revenue-try").textContent =
                revenueTRY.toLocaleString() + " TRY";

            // عرض الإيرادات حسب نوع الدفع (لكل عملة)
            const byPaymentType = stats.revenues.by_payment_type;

            // إيرادات الدفع الإلكتروني
            document.getElementById("online-revenue-usd").textContent =
                byPaymentType.online.USD.total.toLocaleString() +
                " USD (" +
                byPaymentType.online.USD.count +
                " معاملة)";
            document.getElementById("online-revenue-syp").textContent =
                byPaymentType.online.SYP.total.toLocaleString() +
                " SYP (" +
                byPaymentType.online.SYP.count +
                " معاملة)";
            document.getElementById("online-revenue-try").textContent =
                byPaymentType.online.TRY.total.toLocaleString() +
                " TRY (" +
                byPaymentType.online.TRY.count +
                " معاملة)";

            // إيرادات الدفع النقدي
            document.getElementById("cash-revenue-usd").textContent =
                byPaymentType.cash.USD.total.toLocaleString() +
                " USD (" +
                byPaymentType.cash.USD.count +
                " معاملة)";
            document.getElementById("cash-revenue-syp").textContent =
                byPaymentType.cash.SYP.total.toLocaleString() +
                " SYP (" +
                byPaymentType.cash.SYP.count +
                " معاملة)";
            document.getElementById("cash-revenue-try").textContent =
                byPaymentType.cash.TRY.total.toLocaleString() +
                " TRY (" +
                byPaymentType.cash.TRY.count +
                " معاملة)";

            // عرض إحصائيات الصيانة
            document.getElementById("pending-maintenance").textContent =
                stats.maintenance_requests.pending;
        }
    } catch (error) {
        console.error("خطأ في جلب الإحصائيات:", error);
    }
}
```

### مثال 2: رسم بياني للإيرادات اليومية (لكل عملة)

```javascript
async function loadDailyRevenueChart() {
    const response = await fetch("/api/admin/dashboard/statistics");
    const result = await response.json();

    const dailyRevenue = result.data.revenues.daily_revenue;

    // إعداد البيانات للرسم البياني - لكل عملة
    const dates = dailyRevenue.map((day) => day.date);

    // استخراج البيانات لكل عملة
    const usdData = dates.map((date) => {
        const day = dailyRevenue.find((d) => d.date === date);
        const currencyData = day?.currencies.find((c) => c.currency === "USD");
        return currencyData?.total || 0;
    });

    const sypData = dates.map((date) => {
        const day = dailyRevenue.find((d) => d.date === date);
        const currencyData = day?.currencies.find((c) => c.currency === "SYP");
        return currencyData?.total || 0;
    });

    const tryData = dates.map((date) => {
        const day = dailyRevenue.find((d) => d.date === date);
        const currencyData = day?.currencies.find((c) => c.currency === "TRY");
        return currencyData?.total || 0;
    });

    const chartData = {
        labels: dates,
        datasets: [
            {
                label: "USD",
                data: usdData,
                backgroundColor: "rgba(54, 162, 235, 0.2)",
                borderColor: "rgba(54, 162, 235, 1)",
                borderWidth: 1,
            },
            {
                label: "SYP",
                data: sypData,
                backgroundColor: "rgba(255, 99, 132, 0.2)",
                borderColor: "rgba(255, 99, 132, 1)",
                borderWidth: 1,
            },
            {
                label: "TRY",
                data: tryData,
                backgroundColor: "rgba(75, 192, 192, 0.2)",
                borderColor: "rgba(75, 192, 192, 1)",
                borderWidth: 1,
            },
        ],
    };

    // رسم المخطط (مثال باستخدام Chart.js)
    new Chart(ctx, {
        type: "line",
        data: chartData,
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                },
            },
        },
    });
}
```

### مثال 3: عرض الإيرادات حسب نوع الدفع (لكل عملة)

```javascript
async function displayRevenueByPaymentType() {
    const response = await fetch("/api/admin/dashboard/statistics");
    const result = await response.json();

    if (result.success) {
        const byPaymentType = result.data.revenues.by_payment_type;

        // عرض إيرادات الدفع الإلكتروني
        console.log("=== إيرادات الدفع الإلكتروني ===");
        Object.entries(byPaymentType.online).forEach(([currency, data]) => {
            console.log(
                `${currency}: ${data.total.toLocaleString()} (${
                    data.count
                } معاملة)`
            );
        });

        // عرض إيرادات الدفع النقدي
        console.log("\n=== إيرادات الدفع النقدي ===");
        Object.entries(byPaymentType.cash).forEach(([currency, data]) => {
            console.log(
                `${currency}: ${data.total.toLocaleString()} (${
                    data.count
                } معاملة)`
            );
        });

        // حساب الإجمالي لكل عملة
        const currencies = ["USD", "SYP", "TRY"];
        currencies.forEach((currency) => {
            const onlineTotal = byPaymentType.online[currency].total;
            const cashTotal = byPaymentType.cash[currency].total;
            const grandTotal = onlineTotal + cashTotal;
            console.log(
                `\nإجمالي ${currency}: ${grandTotal.toLocaleString()} (إلكتروني: ${onlineTotal.toLocaleString()} + نقدي: ${cashTotal.toLocaleString()})`
            );
        });
    }
}
```

### مثال 4: فلترة حسب التاريخ

```javascript
async function loadStatsByDateRange(startDate, endDate) {
    const url = new URL(
        "/api/admin/dashboard/statistics",
        window.location.origin
    );
    url.searchParams.append("from_date", startDate);
    url.searchParams.append("to_date", endDate);

    const response = await fetch(url, {
        headers: {
            Authorization: `Bearer ${token}`,
        },
    });

    const result = await response.json();
    return result.data;
}

// استخدام
const stats = await loadStatsByDateRange("2024-01-01", "2024-12-31");
```

### مثال 4: عرض أفضل المستخدمين (لكل عملة)

```javascript
function displayTopUsers(topUsersByCurrency) {
    const container = document.getElementById("top-users-list");
    container.innerHTML = ""; // مسح المحتوى السابق

    // عرض أفضل المستخدمين لكل عملة
    Object.entries(topUsersByCurrency).forEach(([currency, users]) => {
        // إنشاء قسم لكل عملة
        const currencySection = document.createElement("div");
        currencySection.className = "currency-section";
        currencySection.innerHTML = `<h3>أفضل المستخدمين - ${currency}</h3>`;

        const usersList = document.createElement("div");
        usersList.className = "users-list";

        users.forEach((user, index) => {
            const userCard = document.createElement("div");
            userCard.className = "user-card";
            userCard.innerHTML = `
                <div class="rank">${index + 1}</div>
                <div class="user-info">
                    <h4>${user.username}</h4>
                    <p>${user.phone}</p>
                </div>
                <div class="revenue">
                    <strong>${user.total.toLocaleString()} ${currency}</strong>
                    <small>${user.count} معاملة</small>
                </div>
            `;
            usersList.appendChild(userCard);
        });

        currencySection.appendChild(usersList);
        container.appendChild(currencySection);
    });
}
```

---

## 🎯 إحصائيات إضافية مفيدة للمدير

### إحصائيات مقترحة للإضافة المستقبلية

#### 1. إحصائيات البث المباشر

```json
{
    "live_streams": {
        "total": 20,
        "active": 5,
        "featured": 3,
        "total_views": 50000,
        "average_views": 2500
    }
}
```

#### 2. إحصائيات الإشعارات

```json
{
    "notifications": {
        "total": 1000,
        "unread": 50,
        "sent_today": 25,
        "by_type": {
            "system": 800,
            "payment": 150,
            "maintenance": 50
        }
    }
}
```

#### 3. إحصائيات الاشتراكات

```json
{
    "subscriptions": {
        "total": 120,
        "active": 100,
        "expired": 20,
        "expiring_soon": 5,
        "with_live_access": 80
    }
}
```

#### 4. إحصائيات الأداء

```json
{
    "performance": {
        "average_response_time": "0.5s",
        "cache_hit_rate": "85%",
        "database_queries": 4,
        "total_requests_today": 1000
    }
}
```

#### 5. إحصائيات النمو

```json
{
    "growth": {
        "new_users_this_month": 25,
        "new_users_last_month": 20,
        "growth_rate": "25%",
        "revenue_growth": "15%"
    }
}
```

---

## 🔐 الأمان والصلاحيات

### الصلاحيات المطلوبة

-   **المحاسب** (`role = 1`): يمكنه الوصول إلى جميع الإحصائيات
-   **المدير** (`role = 2`): يمكنه الوصول إلى جميع الإحصائيات + إحصائيات إضافية

### التحقق من الصلاحيات

```php
$this->authorize('viewAny', AppUser::class);
```

---

## ⚠️ معالجة الأخطاء

### الأخطاء المحتملة

#### 401 Unauthorized

```json
{
    "message": "Unauthenticated."
}
```

**الحل**: تأكد من إرسال token صحيح في header

#### 403 Forbidden

```json
{
    "message": "This action is unauthorized."
}
```

**الحل**: المستخدم ليس لديه صلاحية الوصول (يجب أن يكون محاسب أو مدير)

#### 500 Server Error

```json
{
    "message": "Server Error"
}
```

**الحل**: تحقق من سجلات Laravel (`storage/logs/laravel.log`)

---

## 📝 ملاحظات مهمة

1. **الكاش**: الإحصائيات يتم تخزينها مؤقتاً لمدة 5 دقائق. إذا كنت تحتاج بيانات فورية، استخدم `POST /api/admin/dashboard/clear-cache` أولاً.

2. **الفلاتر**: يمكن استخدام `from_date` و `to_date` لفلترة الإحصائيات حسب فترة زمنية محددة.

3. **الأداء**: تم تحسين الـ endpoint ليكون سريعاً جداً. إذا لاحظت بطء، تحقق من:

    - حجم قاعدة البيانات
    - وجود indexes على الأعمدة المستخدمة
    - حالة الكاش

4. **التحديثات**: عند إضافة إحصائيات جديدة، تأكد من:
    - تحديث هذا الملف
    - إضافة الاختبارات المناسبة
    - تحديث الـ frontend إذا لزم الأمر

---

## 🚀 التحسينات المستقبلية

-   [ ] إضافة إحصائيات البث المباشر
-   [ ] إضافة إحصائيات الإشعارات
-   [ ] إضافة إحصائيات الاشتراكات
-   [ ] إضافة إحصائيات الأداء
-   [ ] إضافة إحصائيات النمو
-   [ ] إضافة إحصائيات حسب الفترة (أسبوعي، شهري، سنوي)
-   [ ] إضافة إحصائيات المقارنة (هذا الشهر vs الشهر الماضي)
-   [ ] إضافة تصدير الإحصائيات (PDF, Excel)

---

## 📞 الدعم

إذا واجهت أي مشاكل أو لديك اقتراحات، يرجى التواصل مع فريق التطوير.

---

**آخر تحديث**: 2024-12-20  
**الإصدار**: 1.0.0
