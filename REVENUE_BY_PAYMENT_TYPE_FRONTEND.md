# 💳 الإيرادات حسب نوع الدفع - دليل Frontend

## 📋 نظرة عامة

تم تحديث endpoint `/api/admin/dashboard/statistics` لإرجاع تفاصيل الإيرادات حسب نوع الدفع (إلكتروني/نقدي) **لكل عملة** بشكل منفصل.

---

## 🔗 Endpoint

```http
GET /api/admin/dashboard/statistics
Authorization: Bearer {token}
```

**المعاملات الاختيارية**:
- `from_date` (Y-m-d) - تاريخ البداية
- `to_date` (Y-m-d) - تاريخ النهاية

---

## 📊 البنية الجديدة

### الموقع في الاستجابة:

```json
{
  "success": true,
  "data": {
    "revenues": {
      "by_payment_type": {
        "online": { ... },
        "cash": { ... }
      }
    }
  }
}
```

### البنية الكاملة:

```json
{
  "revenues": {
    "by_payment_type": {
      "online": {
        "USD": {
          "total": 5000.0,    // إجمالي المبلغ بالدولار
          "count": 25          // عدد المعاملات
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
    }
  }
}
```

---

## 💻 أمثلة الاستخدام

### 1. جلب البيانات

```javascript
async function getDashboardStats() {
    const response = await fetch('/api/admin/dashboard/statistics', {
        headers: {
            'Authorization': `Bearer ${token}`
        }
    });
    
    const result = await response.json();
    return result.data.revenues.by_payment_type;
}
```

### 2. عرض الإيرادات حسب نوع الدفع

```javascript
function displayPaymentTypeStats(byPaymentType) {
    // إيرادات الدفع الإلكتروني
    console.log('=== الدفع الإلكتروني ===');
    console.log(`USD: ${byPaymentType.online.USD.total} (${byPaymentType.online.USD.count} معاملة)`);
    console.log(`SYP: ${byPaymentType.online.SYP.total} (${byPaymentType.online.SYP.count} معاملة)`);
    console.log(`TRY: ${byPaymentType.online.TRY.total} (${byPaymentType.online.TRY.count} معاملة)`);
    
    // إيرادات الدفع النقدي
    console.log('\n=== الدفع النقدي ===');
    console.log(`USD: ${byPaymentType.cash.USD.total} (${byPaymentType.cash.USD.count} معاملة)`);
    console.log(`SYP: ${byPaymentType.cash.SYP.total} (${byPaymentType.cash.SYP.count} معاملة)`);
    console.log(`TRY: ${byPaymentType.cash.TRY.total} (${byPaymentType.cash.TRY.count} معاملة)`);
}
```

### 3. مقارنة بين الدفع الإلكتروني والنقدي

```javascript
function comparePaymentTypes(byPaymentType) {
    const currencies = ['USD', 'SYP', 'TRY'];
    
    currencies.forEach(currency => {
        const online = byPaymentType.online[currency];
        const cash = byPaymentType.cash[currency];
        
        const onlineTotal = online.total;
        const cashTotal = cash.total;
        const grandTotal = onlineTotal + cashTotal;
        
        const onlinePercentage = (onlineTotal / grandTotal * 100).toFixed(2);
        const cashPercentage = (cashTotal / grandTotal * 100).toFixed(2);
        
        console.log(`\n=== ${currency} ===`);
        console.log(`إجمالي: ${grandTotal.toLocaleString()}`);
        console.log(`  إلكتروني: ${onlineTotal.toLocaleString()} (${onlinePercentage}%) - ${online.count} معاملة`);
        console.log(`  نقدي: ${cashTotal.toLocaleString()} (${cashPercentage}%) - ${cash.count} معاملة`);
    });
}
```

### 4. عرض في React Component

```tsx
function PaymentTypeStats({ stats }) {
    const byPaymentType = stats.revenues.by_payment_type;
    const currencies = ['USD', 'SYP', 'TRY'];
    
    return (
        <div className="payment-type-stats">
            <h2>الإيرادات حسب نوع الدفع</h2>
            
            <div className="stats-grid">
                {/* الدفع الإلكتروني */}
                <div className="payment-type-card">
                    <h3>الدفع الإلكتروني</h3>
                    {currencies.map(currency => (
                        <div key={currency} className="currency-stat">
                            <span className="currency-label">{currency}:</span>
                            <span className="amount">
                                {byPaymentType.online[currency].total.toLocaleString()}
                            </span>
                            <span className="count">
                                ({byPaymentType.online[currency].count} معاملة)
                            </span>
                        </div>
                    ))}
                </div>
                
                {/* الدفع النقدي */}
                <div className="payment-type-card">
                    <h3>الدفع النقدي</h3>
                    {currencies.map(currency => (
                        <div key={currency} className="currency-stat">
                            <span className="currency-label">{currency}:</span>
                            <span className="amount">
                                {byPaymentType.cash[currency].total.toLocaleString()}
                            </span>
                            <span className="count">
                                ({byPaymentType.cash[currency].count} معاملة)
                            </span>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}
```

### 5. رسم بياني للمقارنة

```javascript
function createPaymentTypeChart(byPaymentType) {
    const currencies = ['USD', 'SYP', 'TRY'];
    
    const chartData = {
        labels: currencies,
        datasets: [
            {
                label: 'الدفع الإلكتروني',
                data: currencies.map(c => byPaymentType.online[c].total),
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
            },
            {
                label: 'الدفع النقدي',
                data: currencies.map(c => byPaymentType.cash[c].total),
                backgroundColor: 'rgba(255, 99, 132, 0.6)',
                borderColor: 'rgba(255, 99, 132, 1)',
            }
        ]
    };
    
    // استخدام Chart.js أو أي مكتبة رسم بياني
    new Chart(ctx, {
        type: 'bar',
        data: chartData,
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}
```

---

## 📈 مقارنة سريعة

### حساب النسب المئوية:

```javascript
function getPaymentTypePercentages(byPaymentType, currency) {
    const online = byPaymentType.online[currency].total;
    const cash = byPaymentType.cash[currency].total;
    const total = online + cash;
    
    return {
        online: (online / total * 100).toFixed(2),
        cash: (cash / total * 100).toFixed(2),
        total: total
    };
}

// استخدام
const usdStats = getPaymentTypePercentages(byPaymentType, 'USD');
console.log(`USD - إلكتروني: ${usdStats.online}%, نقدي: ${usdStats.cash}%`);
```

### حساب الإجمالي لكل عملة:

```javascript
function getTotalByCurrency(byPaymentType) {
    const currencies = ['USD', 'SYP', 'TRY'];
    
    return currencies.map(currency => ({
        currency,
        total: byPaymentType.online[currency].total + byPaymentType.cash[currency].total,
        online: byPaymentType.online[currency].total,
        cash: byPaymentType.cash[currency].total,
        onlineCount: byPaymentType.online[currency].count,
        cashCount: byPaymentType.cash[currency].count,
        totalCount: byPaymentType.online[currency].count + byPaymentType.cash[currency].count
    }));
}
```

---

## ⚠️ ملاحظات مهمة

1. **جميع العملات موجودة**: حتى لو لم تكن هناك إيرادات، ستجد القيم `0.0` و `0`
2. **الفلترة**: إذا استخدمت `from_date` و `to_date`، ستطبق على هذه البيانات
3. **الأداء**: البيانات محسّنة ومخزّنة في الكاش لمدة 5 دقائق

---

## 🔄 مثال كامل

```javascript
async function displayFullComparison() {
    const response = await fetch('/api/admin/dashboard/statistics', {
        headers: {
            'Authorization': `Bearer ${token}`
        }
    });
    
    const result = await response.json();
    const byPaymentType = result.data.revenues.by_payment_type;
    
    const currencies = ['USD', 'SYP', 'TRY'];
    
    console.log('=== مقارنة الإيرادات حسب نوع الدفع ===\n');
    
    currencies.forEach(currency => {
        const online = byPaymentType.online[currency];
        const cash = byPaymentType.cash[currency];
        const total = online.total + cash.total;
        
        const onlinePercent = total > 0 ? (online.total / total * 100).toFixed(1) : 0;
        const cashPercent = total > 0 ? (cash.total / total * 100).toFixed(1) : 0;
        
        console.log(`${currency}:`);
        console.log(`  إجمالي: ${total.toLocaleString()}`);
        console.log(`  إلكتروني: ${online.total.toLocaleString()} (${onlinePercent}%) - ${online.count} معاملة`);
        console.log(`  نقدي: ${cash.total.toLocaleString()} (${cashPercent}%) - ${cash.count} معاملة`);
        console.log('');
    });
}
```

---

## ✅ Checklist

- [ ] جلب البيانات من `/api/admin/dashboard/statistics`
- [ ] الوصول إلى `data.revenues.by_payment_type`
- [ ] عرض البيانات لكل عملة (USD, SYP, TRY)
- [ ] حساب النسب المئوية للمقارنة
- [ ] عرض في UI مناسب

---

**آخر تحديث**: 2024-12-20

