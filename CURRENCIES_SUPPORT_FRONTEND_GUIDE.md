# 💱 دليل دعم العملات للمطورين - Frontend

## نظرة عامة

يدعم النظام الآن **ثلاث عملات** فقط:

-   **USD** - الدولار الأمريكي
-   **SYP** - الليرة السورية
-   **TRY** - الليرة التركية

---

## 📋 العملات المدعومة

| الكود | الاسم        | الاسم العربي     |
| ----- | ------------ | ---------------- |
| `USD` | US Dollar    | الدولار الأمريكي |
| `SYP` | Syrian Pound | الليرة السورية   |
| `TRY` | Turkish Lira | الليرة التركية   |

**ملاحظة مهمة**: تم إزالة دعم `IQD` (الدينار العراقي) من النظام.

---

## 🔄 التغييرات في API

### 1. إنشاء طلب دفع (Payment Request)

**Endpoint**: `POST /api/payment-requests`

**قبل التحديث**:

```json
{
    "amount": 50000,
    "currency": "IQD", // ❌ لم يعد مدعوماً
    "period_months": 1
}
```

**بعد التحديث**:

```json
{
    "amount": 50,
    "currency": "USD", // ✅ أو "SYP" أو "TRY"
    "period_months": 1
}
```

**Validation Rules**:

-   `currency` يجب أن يكون واحداً من: `USD`, `SYP`, `TRY`
-   إذا لم يتم تحديد العملة، القيمة الافتراضية هي `USD`
-   `currency` حقل اختياري (nullable)

---

### 2. إنشاء دفعة نقدية (Cash Payment)

**Endpoint**: `POST /api/admin/payment-requests/cash-payment`

**قبل التحديث**:

```json
{
    "user_id": 1,
    "amount": 50000,
    "currency": "IQD", // ❌ لم يعد مدعوماً
    "period_months": 1
}
```

**بعد التحديث**:

```json
{
    "user_id": 1,
    "amount": 50,
    "currency": "USD", // ✅ أو "SYP" أو "TRY"
    "period_months": 1
}
```

---

### 3. الإيرادات (Revenues)

جميع الإيرادات الآن تدعم العملات الثلاث:

**Response Example**:

```json
{
    "success": true,
    "data": {
        "id": 1,
        "amount": 50.0,
        "currency": "USD", // ✅ USD, SYP, or TRY
        "payment_type": "online",
        "payment_date": "2024-12-20"
    }
}
```

---

### 4. إحصائيات Dashboard

**Endpoint**: `GET /api/admin/dashboard/statistics`

**قسم الإيرادات**:

```json
{
    "revenues": {
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
                    }
                ]
            }
        ],
        "top_users": {
            "USD": [
                {
                    "id": 1,
                    "username": "user001",
                    "currency": "USD",
                    "total": 5000.0,
                    "count": 5
                }
            ],
            "SYP": [...],
            "TRY": [...]
        }
    },
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
        }
    }
}
```

---

## 🎨 التوصيات للـ Frontend

### 1. قائمة منسدلة للعملات

```typescript
const currencies = [
    { code: "USD", name: "دولار أمريكي", symbol: "$" },
    { code: "SYP", name: "ليرة سورية", symbol: "ل.س" },
    { code: "TRY", name: "ليرة تركية", symbol: "₺" },
];
```

**مثال React**:

```tsx
<select
    name="currency"
    value={formData.currency || "USD"}
    onChange={(e) => setFormData({ ...formData, currency: e.target.value })}
>
    {currencies.map((currency) => (
        <option key={currency.code} value={currency.code}>
            {currency.name} ({currency.code})
        </option>
    ))}
</select>
```

---

### 2. عرض المبالغ مع رمز العملة

```typescript
function formatAmount(amount: number, currency: string): string {
    const symbols: Record<string, string> = {
        USD: "$",
        SYP: "ل.س",
        TRY: "₺",
    };

    const symbol = symbols[currency] || currency;
    return `${amount.toLocaleString()} ${symbol}`;
}

// استخدام
formatAmount(1000, "USD"); // "1,000 $"
formatAmount(500000, "SYP"); // "500,000 ل.س"
formatAmount(5000, "TRY"); // "5,000 ₺"
```

---

### 3. التحقق من صحة العملة

```typescript
const SUPPORTED_CURRENCIES = ["USD", "SYP", "TRY"] as const;

type Currency = (typeof SUPPORTED_CURRENCIES)[number];

function isValidCurrency(currency: string): currency is Currency {
    return SUPPORTED_CURRENCIES.includes(currency as Currency);
}

// استخدام
if (isValidCurrency(formData.currency)) {
    // العملة صحيحة
} else {
    // خطأ: العملة غير مدعومة
}
```

---

### 4. معالجة القيمة الافتراضية

```typescript
// عند إنشاء طلب دفع
const paymentData = {
    amount: 50,
    currency: formData.currency || "USD", // افتراضي USD
    period_months: 1,
};
```

---

### 5. عرض الإحصائيات حسب العملة

```tsx
function CurrencyStats({ stats }) {
    const currencies = ["USD", "SYP", "TRY"];

    return (
        <div className="currency-stats">
            {currencies.map((currency) => (
                <div key={currency} className="stat-card">
                    <h3>{currency}</h3>
                    <p>
                        الإيرادات:{" "}
                        {formatAmount(stats.revenues[currency], currency)}
                    </p>
                    <p>
                        السحوبات:{" "}
                        {formatAmount(stats.withdrawals[currency], currency)}
                    </p>
                    <p>
                        الرصيد:{" "}
                        {formatAmount(stats.balance[currency], currency)}
                    </p>
                </div>
            ))}
        </div>
    );
}
```

---

## ⚠️ نقاط مهمة للمطورين

### 1. Migration للبيانات الموجودة

إذا كان لديك بيانات موجودة بعملة `IQD`:

-   يجب تحديثها يدوياً إلى إحدى العملات المدعومة
-   أو إنشاء migration script لتحويلها

### 2. Validation في Frontend

**يجب التحقق من العملة قبل الإرسال**:

```typescript
function validatePaymentRequest(data: PaymentRequestData): ValidationResult {
    const errors: string[] = [];

    if (
        data.currency &&
        !SUPPORTED_CURRENCIES.includes(data.currency as Currency)
    ) {
        errors.push("العملة يجب أن تكون USD أو SYP أو TRY");
    }

    return {
        isValid: errors.length === 0,
        errors,
    };
}
```

### 3. عرض الأخطاء

```typescript
// عند استلام خطأ من API
if (error.response?.status === 422) {
    const validationErrors = error.response.data.errors;

    if (validationErrors.currency) {
        // عرض رسالة: "العملة يجب أن تكون USD أو SYP أو TRY"
        setFieldError("currency", validationErrors.currency[0]);
    }
}
```

---

## 📊 أمثلة كاملة

### مثال 1: نموذج إنشاء طلب دفع

```tsx
import React, { useState } from "react";

const CURRENCIES = [
    { code: "USD", name: "دولار أمريكي", symbol: "$" },
    { code: "SYP", name: "ليرة سورية", symbol: "ل.س" },
    { code: "TRY", name: "ليرة تركية", symbol: "₺" },
];

function PaymentRequestForm() {
    const [formData, setFormData] = useState({
        amount: "",
        currency: "USD", // افتراضي
        period_months: 1,
        payment_method_id: null,
    });

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        try {
            const response = await fetch("/api/payment-requests", {
                method: "POST",
                headers: {
                    Authorization: `Bearer ${token}`,
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    amount: parseFloat(formData.amount),
                    currency: formData.currency,
                    period_months: formData.period_months,
                    payment_method_id: formData.payment_method_id,
                }),
            });

            const result = await response.json();

            if (result.success) {
                alert("تم إرسال طلب الدفع بنجاح");
            }
        } catch (error) {
            console.error("خطأ في إرسال طلب الدفع:", error);
        }
    };

    return (
        <form onSubmit={handleSubmit}>
            <div>
                <label>المبلغ</label>
                <input
                    type="number"
                    step="0.01"
                    min="0.01"
                    value={formData.amount}
                    onChange={(e) =>
                        setFormData({ ...formData, amount: e.target.value })
                    }
                    required
                />
            </div>

            <div>
                <label>العملة</label>
                <select
                    value={formData.currency}
                    onChange={(e) =>
                        setFormData({ ...formData, currency: e.target.value })
                    }
                >
                    {CURRENCIES.map((currency) => (
                        <option key={currency.code} value={currency.code}>
                            {currency.name} ({currency.code})
                        </option>
                    ))}
                </select>
            </div>

            <div>
                <label>عدد الأشهر</label>
                <input
                    type="number"
                    min="1"
                    max="12"
                    value={formData.period_months}
                    onChange={(e) =>
                        setFormData({
                            ...formData,
                            period_months: parseInt(e.target.value),
                        })
                    }
                    required
                />
            </div>

            <button type="submit">إرسال طلب الدفع</button>
        </form>
    );
}
```

---

### مثال 2: عرض الإحصائيات

```tsx
function DashboardStats() {
    const [stats, setStats] = useState(null);

    useEffect(() => {
        fetch("/api/admin/dashboard/statistics", {
            headers: {
                Authorization: `Bearer ${token}`,
            },
        })
            .then((res) => res.json())
            .then((data) => {
                if (data.success) {
                    setStats(data.data);
                }
            });
    }, []);

    if (!stats) return <div>جاري التحميل...</div>;

    return (
        <div className="dashboard">
            {/* إحصائيات الإيرادات */}
            <div className="revenue-stats">
                <h2>الإيرادات حسب العملة</h2>
                <div className="currency-grid">
                    {["USD", "SYP", "TRY"].map((currency) => {
                        const currencyData =
                            stats.revenues.by_currency[currency];
                        return (
                            <div key={currency} className="stat-card">
                                <h3>{currency}</h3>
                                <p>
                                    إجمالي:{" "}
                                    {formatAmount(currencyData.total, currency)}
                                </p>
                                <p>
                                    متوسط:{" "}
                                    {formatAmount(
                                        currencyData.average,
                                        currency
                                    )}
                                </p>
                                <p>عدد المعاملات: {currencyData.count}</p>
                            </div>
                        );
                    })}
                </div>
            </div>

            {/* الإيرادات اليومية */}
            <div className="daily-revenue">
                <h2>الإيرادات اليومية (آخر 30 يوم)</h2>
                {stats.revenues.daily_revenue.map((day, index) => (
                    <div key={index} className="day-card">
                        <h4>{day.date}</h4>
                        {day.currencies.map((currencyData) => (
                            <p key={currencyData.currency}>
                                {currencyData.currency}:{" "}
                                {formatAmount(
                                    currencyData.total,
                                    currencyData.currency
                                )}
                                ({currencyData.count} معاملة)
                            </p>
                        ))}
                    </div>
                ))}
            </div>

            {/* أفضل المستخدمين */}
            <div className="top-users">
                <h2>أفضل المستخدمين</h2>
                {["USD", "SYP", "TRY"].map((currency) => (
                    <div key={currency} className="currency-users">
                        <h3>أفضل المستخدمين - {currency}</h3>
                        {stats.revenues.top_users[currency].map(
                            (user, index) => (
                                <div key={user.id} className="user-card">
                                    <span className="rank">{index + 1}</span>
                                    <span className="username">
                                        {user.username}
                                    </span>
                                    <span className="total">
                                        {formatAmount(user.total, currency)} (
                                        {user.count} معاملة)
                                    </span>
                                </div>
                            )
                        )}
                    </div>
                ))}
            </div>

            {/* رصيد الصندوق */}
            <div className="cash-box">
                <h2>رصيد الصندوق</h2>
                {["USD", "SYP", "TRY"].map((currency) => (
                    <div key={currency} className="balance-card">
                        <h3>{currency}</h3>
                        <p>
                            الإيرادات:{" "}
                            {formatAmount(
                                stats.cash_box.total_revenue[currency],
                                currency
                            )}
                        </p>
                        <p>
                            السحوبات:{" "}
                            {formatAmount(
                                stats.cash_box.total_withdrawals[currency],
                                currency
                            )}
                        </p>
                        <p className="balance">
                            الرصيد:{" "}
                            {formatAmount(
                                stats.cash_box.balance[currency],
                                currency
                            )}
                        </p>
                    </div>
                ))}
            </div>
        </div>
    );
}
```

---

### مثال 3: فلترة طلبات الدفع حسب العملة

**Endpoint للمستخدمين**: `GET /api/payment-requests?currency=USD`  
**Endpoint للمديرين/المحاسبين**: `GET /api/admin/payment-requests?currency=USD`

**المعاملات**:

-   `currency` (optional) - فلترة حسب العملة: `USD`, `SYP`, أو `TRY`
-   `status` (optional) - فلترة حسب الحالة: `0` (معلق), `1` (مقبول), `2` (مرفوض)
-   `is_paid` (optional) - فلترة حسب حالة الدفع: `true` أو `false`
-   `is_deferred` (optional) - فلترة حسب الدفعات المؤجلة: `true` أو `false`

**مثال React**:

```tsx
function PaymentRequestsList() {
    const [currency, setCurrency] = useState<string>("all");
    const [status, setStatus] = useState<string>("all");
    const [requests, setRequests] = useState([]);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        setLoading(true);
        const params = new URLSearchParams();

        if (currency !== "all") {
            params.append("currency", currency);
        }
        if (status !== "all") {
            params.append("status", status);
        }

        const url = `/api/admin/payment-requests${
            params.toString() ? `?${params.toString()}` : ""
        }`;

        fetch(url, {
            headers: {
                Authorization: `Bearer ${token}`,
            },
        })
            .then((res) => res.json())
            .then((data) => {
                if (data.success) {
                    setRequests(data.data);
                }
            })
            .finally(() => setLoading(false));
    }, [currency, status]);

    return (
        <div>
            <div className="filters">
                <select
                    value={currency}
                    onChange={(e) => setCurrency(e.target.value)}
                >
                    <option value="all">جميع العملات</option>
                    <option value="USD">دولار (USD)</option>
                    <option value="SYP">ليرة سورية (SYP)</option>
                    <option value="TRY">ليرة تركية (TRY)</option>
                </select>

                <select
                    value={status}
                    onChange={(e) => setStatus(e.target.value)}
                >
                    <option value="all">جميع الحالات</option>
                    <option value="0">معلق</option>
                    <option value="1">مقبول</option>
                    <option value="2">مرفوض</option>
                </select>
            </div>

            {loading ? (
                <div>جاري التحميل...</div>
            ) : (
                <table>
                    <thead>
                        <tr>
                            <th>المبلغ</th>
                            <th>العملة</th>
                            <th>الحالة</th>
                            <th>التاريخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        {requests.map((request) => (
                            <tr key={request.id}>
                                <td>{request.amount}</td>
                                <td>{request.currency}</td>
                                <td>
                                    {request.status === 0
                                        ? "معلق"
                                        : request.status === 1
                                        ? "مقبول"
                                        : "مرفوض"}
                                </td>
                                <td>{request.created_at}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}
        </div>
    );
}
```

---

## 🔍 Endpoints المتأثرة

### Endpoints التي تدعم العملات:

1. **`POST /api/payment-requests`** - إنشاء طلب دفع
2. **`POST /api/admin/payment-requests/cash-payment`** - إنشاء دفعة نقدية
3. **`GET /api/admin/payment-requests`** - عرض طلبات الدفع (فلترة حسب العملة)
4. **`GET /api/admin/revenues`** - عرض الإيرادات (فلترة حسب العملة)
5. **`GET /api/admin/revenues/summary`** - ملخص الإيرادات
6. **`GET /api/admin/dashboard/statistics`** - إحصائيات Dashboard
7. **`GET /api/admin/cash-withdrawals`** - عرض السحوبات (فلترة حسب العملة)
8. **`POST /api/admin/cash-withdrawals`** - إنشاء سحب جديد
9. **`GET /api/admin/cash-withdrawals/balance`** - رصيد الصندوق

---

## ✅ Checklist للمطورين

-   [ ] تحديث جميع النماذج (Forms) لإظهار العملات الثلاث فقط
-   [ ] إزالة أي مراجع لـ `IQD` من الكود
-   [ ] تحديث Validation في Frontend
-   [ ] تحديث عرض المبالغ مع رموز العملات
-   [ ] تحديث إحصائيات Dashboard
-   [ ] تحديث الفلاتر حسب العملة
-   [ ] اختبار جميع الـ endpoints المتأثرة
-   [ ] تحديث أي Constants أو Enums متعلقة بالعملات

---

## 🚨 أخطاء شائعة يجب تجنبها

### ❌ خطأ 1: استخدام IQD

```typescript
// ❌ خطأ
const currency = "IQD";

// ✅ صحيح
const currency = "USD"; // أو 'SYP' أو 'TRY'
```

### ❌ خطأ 2: عدم التحقق من العملة

```typescript
// ❌ خطأ
function submitPayment(amount: number, currency: string) {
    // لا يوجد تحقق من العملة
    fetch("/api/payment-requests", {
        body: JSON.stringify({ amount, currency }),
    });
}

// ✅ صحيح
function submitPayment(amount: number, currency: string) {
    const SUPPORTED = ["USD", "SYP", "TRY"];
    if (!SUPPORTED.includes(currency)) {
        throw new Error("العملة غير مدعومة");
    }
    // ...
}
```

### ❌ خطأ 3: افتراض IQD كقيمة افتراضية

```typescript
// ❌ خطأ
const currency = formData.currency || "IQD";

// ✅ صحيح
const currency = formData.currency || "USD";
```

---

## 📞 الدعم

إذا واجهت أي مشاكل أو لديك أسئلة، يرجى التواصل مع فريق Backend.

---

**آخر تحديث**: 2024-12-20  
**الإصدار**: 2.0.0
