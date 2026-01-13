# 💰 API: الدفعات المؤجلة للمستخدم

## 📍 الرابط
```
GET /api/payment-requests
GET /api/payment-requests/{id}
```

## 🔐 المصادقة
```
Authorization: Bearer {token}
```

---

## 📥 Response Structure للدفعات المؤجلة

عندما يكون `is_deferred: true`، ستجد معلومات إضافية في `payment_summary`:

### ✅ Success Response (200)

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "amount": 100000,
      "currency": "USD",
      "status": 1,
      "status_label": "مقبول",
      "is_paid": false,
      "is_deferred": true,
      "payment_status_label": "دفع مؤجل",
      "approved_amount": 100000,
      "paid_amount": 30000,
      "remaining_amount": 70000,
      "is_fully_paid": false,
      
      // معلومات تفصيلية للدفعات المؤجلة
      "payment_summary": {
        "total_amount": 100000,        // المبلغ الكلي
        "paid_amount": 30000,          // المبلغ المدفوع
        "remaining_amount": 70000,      // المبلغ المتبقي
        "currency": "USD",
        "payment_percentage": 30.0,    // نسبة الدفع (%)
        "is_fully_paid": false         // هل تم الدفع بالكامل
      },
      
      // الدفعات الجزئية (إن وجدت)
      "partial_payments": [
        {
          "id": 1,
          "amount": 20000,
          "currency": "USD",
          "payment_date": "2024-01-10",
          "notes": "دفعة جزئية - 20000 USD",
          "created_by": {
            "id": 2,
            "username": "accountant",
            "role": 1
          },
          "created_at": "2024-01-10T10:30:00.000000Z"
        },
        {
          "id": 2,
          "amount": 10000,
          "currency": "USD",
          "payment_date": "2024-01-15",
          "notes": "دفعة جزئية - 10000 USD",
          "created_by": {
            "id": 2,
            "username": "accountant",
            "role": 1
          },
          "created_at": "2024-01-15T14:20:00.000000Z"
        }
      ],
      
      "payment_method_details": {
        "id": 1,
        "name": "Zain Cash",
        "icon_url": "https://...",
        "instructions": "Send payment to..."
      },
      "created_at": "2024-01-01T10:00:00.000000Z",
      "updated_at": "2024-01-15T14:20:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

---

## 📊 شرح الحقول

### `payment_summary` (يظهر فقط للدفعات المؤجلة)

| الحقل | النوع | الوصف |
|------|------|-------|
| `total_amount` | number | المبلغ الكلي المطلوب دفعه |
| `paid_amount` | number | المبلغ المدفوع حتى الآن |
| `remaining_amount` | number | المبلغ المتبقي للدفع |
| `currency` | string | العملة (USD, SYP, TRY) |
| `payment_percentage` | number | نسبة الدفع المكتملة (0-100) |
| `is_fully_paid` | boolean | هل تم الدفع بالكامل |

### `partial_payments` (قائمة الدفعات الجزئية)

| الحقل | النوع | الوصف |
|------|------|-------|
| `id` | number | معرف الدفعة الجزئية |
| `amount` | number | مبلغ الدفعة الجزئية |
| `currency` | string | العملة |
| `payment_date` | string | تاريخ الدفع |
| `notes` | string | ملاحظات |
| `created_by` | object | معلومات من قام بإضافة الدفعة |
| `created_at` | string | تاريخ الإنشاء |

---

## 💡 حالات الدفعات المؤجلة

### 1. دفعة مؤجلة غير مدفوعة
```json
{
  "is_deferred": true,
  "is_paid": false,
  "payment_summary": {
    "total_amount": 100000,
    "paid_amount": 0,
    "remaining_amount": 100000,
    "payment_percentage": 0,
    "is_fully_paid": false
  },
  "partial_payments": []
}
```

### 2. دفعة مؤجلة مع دفعات جزئية
```json
{
  "is_deferred": true,
  "is_paid": false,
  "payment_summary": {
    "total_amount": 100000,
    "paid_amount": 50000,
    "remaining_amount": 50000,
    "payment_percentage": 50.0,
    "is_fully_paid": false
  },
  "partial_payments": [
    { "id": 1, "amount": 30000, ... },
    { "id": 2, "amount": 20000, ... }
  ]
}
```

### 3. دفعة مؤجلة مكتملة الدفع
```json
{
  "is_deferred": true,
  "is_paid": true,
  "payment_summary": {
    "total_amount": 100000,
    "paid_amount": 100000,
    "remaining_amount": 0,
    "payment_percentage": 100.0,
    "is_fully_paid": true
  },
  "partial_payments": [
    { "id": 1, "amount": 50000, ... },
    { "id": 2, "amount": 50000, ... }
  ]
}
```

---

## 🎯 أمثلة استخدام

### عرض جميع الدفعات المؤجلة
```javascript
// جلب جميع طلبات الدفع
const response = await fetch('https://api.example.com/api/payment-requests', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});

const data = await response.json();

// تصفية الدفعات المؤجلة
const deferredPayments = data.data.filter(payment => payment.is_deferred);

// عرض معلومات كل دفعة مؤجلة
deferredPayments.forEach(payment => {
  if (payment.payment_summary) {
    console.log(`المبلغ الكلي: ${payment.payment_summary.total_amount}`);
    console.log(`المدفوع: ${payment.payment_summary.paid_amount}`);
    console.log(`المتبقي: ${payment.payment_summary.remaining_amount}`);
    console.log(`نسبة الدفع: ${payment.payment_summary.payment_percentage}%`);
  }
});
```

### عرض تفاصيل دفعة مؤجلة محددة
```javascript
const paymentId = 1;
const response = await fetch(`https://api.example.com/api/payment-requests/${paymentId}`, {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});

const data = await response.json();
const payment = data.data;

if (payment.is_deferred && payment.payment_summary) {
  const summary = payment.payment_summary;
  
  // عرض شريط التقدم
  const progressBar = (summary.payment_percentage / 100) * 100;
  
  // عرض الدفعات الجزئية
  if (payment.partial_payments && payment.partial_payments.length > 0) {
    payment.partial_payments.forEach(partial => {
      console.log(`دفعة جزئية: ${partial.amount} ${partial.currency} بتاريخ ${partial.payment_date}`);
    });
  }
}
```

### React Component Example
```jsx
function DeferredPaymentCard({ payment }) {
  if (!payment.is_deferred || !payment.payment_summary) {
    return null;
  }

  const { total_amount, paid_amount, remaining_amount, payment_percentage, currency } = payment.payment_summary;

  return (
    <div className="deferred-payment-card">
      <h3>دفعة مؤجلة #{payment.id}</h3>
      
      <div className="amounts">
        <div>
          <span>المبلغ الكلي:</span>
          <strong>{total_amount} {currency}</strong>
        </div>
        <div>
          <span>المدفوع:</span>
          <strong className="paid">{paid_amount} {currency}</strong>
        </div>
        <div>
          <span>المتبقي:</span>
          <strong className="remaining">{remaining_amount} {currency}</strong>
        </div>
      </div>

      <div className="progress-bar">
        <div 
          className="progress-fill" 
          style={{ width: `${payment_percentage}%` }}
        />
        <span className="progress-text">{payment_percentage}%</span>
      </div>

      {payment.partial_payments && payment.partial_payments.length > 0 && (
        <div className="partial-payments">
          <h4>الدفعات الجزئية:</h4>
          {payment.partial_payments.map(partial => (
            <div key={partial.id} className="partial-payment-item">
              <span>{partial.amount} {partial.currency}</span>
              <span>{partial.payment_date}</span>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
```

---

## ✅ Checklist للربط

- [ ] التحقق من وجود `is_deferred: true` قبل عرض `payment_summary`
- [ ] عرض المبلغ الكلي والمدفوع والمتبقي بوضوح
- [ ] استخدام `payment_percentage` لعرض شريط التقدم
- [ ] عرض قائمة الدفعات الجزئية إن وجدت
- [ ] التحقق من `is_fully_paid` لتحديد حالة الدفعة
- [ ] عرض رسالة مناسبة عندما تكون الدفعة مكتملة

---

## 📝 ملاحظات مهمة

1. **`payment_summary` يظهر فقط للدفعات المؤجلة**: إذا كان `is_deferred: false`، لن يظهر `payment_summary`
2. **المبلغ الكلي**: يستخدم `approved_amount` إن وجد، وإلا يستخدم `amount`
3. **الدفعات الجزئية**: قد تكون القائمة فارغة إذا لم يتم دفع أي دفعات جزئية بعد
4. **نسبة الدفع**: تُحسب تلقائياً بناءً على (المدفوع / الكلي) × 100
5. **التحديث التلقائي**: البيانات تُحدث تلقائياً عند إضافة دفعات جزئية جديدة

