# 🚀 ملخص التغييرات في API - للفرونت إند

## ⚡ التغييرات السريعة

### 1. **نظام الدفعات الجزئية**
الآن يمكن دفع جزء من الدفعة المؤجلة بدلاً من دفعها كاملة.

---

## 📡 Endpoint المحدث

### `POST /api/admin/payment-requests/{id}/mark-as-paid`

#### **قبل:**
```json
POST /api/admin/payment-requests/123/mark-as-paid
{}
```
→ يدفع المبلغ كاملاً

#### **بعد:**
```json
POST /api/admin/payment-requests/123/mark-as-paid
{
  "amount": 5,              // ⭐ جديد: مبلغ جزئي (اختياري)
  "notes": "دفعة جزئية",   // ⭐ جديد: ملاحظات (اختياري)
  "payment_date": "2025-12-14"  // ⭐ جديد: تاريخ (اختياري)
}
```
→ يدفع المبلغ المحدد فقط

**بدون `amount`:** يدفع المتبقي بالكامل (كما كان سابقاً)

---

## 📦 Response الجديد

### **الحقول المضافة في PaymentRequest:**

```json
{
  "paid_amount": 5.00,           // ⭐ المبلغ المدفوع حتى الآن
  "remaining_amount": 5.00,      // ⭐ المبلغ المتبقي
  "is_fully_paid": false,         // ⭐ هل الدفعة مكتملة
  "partial_payments": [          // ⭐ قائمة الدفعات الجزئية
    {
      "id": 1,
      "amount": 5.00,
      "currency": "IQD",
      "payment_date": "2025-12-14",
      "notes": "دفعة جزئية - 5 IQD",
      "created_by": { ... },
      "created_at": "2025-12-14T10:30:00Z"
    }
  ]
}
```

---

## 💻 أمثلة الكود

### **JavaScript/TypeScript:**

```typescript
// إضافة دفعة جزئية
async function addPartialPayment(paymentRequestId: number, amount: number) {
  const response = await fetch(
    `/api/admin/payment-requests/${paymentRequestId}/mark-as-paid`,
    {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({
        amount: amount,
        notes: 'دفعة جزئية',
        payment_date: new Date().toISOString().split('T')[0]
      })
    }
  );
  
  return await response.json();
}

// إكمال الدفعة بالكامل
async function completePayment(paymentRequestId: number) {
  const response = await fetch(
    `/api/admin/payment-requests/${paymentRequestId}/mark-as-paid`,
    {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({})
    }
  );
  
  return await response.json();
}
```

### **React Example:**

```jsx
function PaymentRequestCard({ paymentRequest }) {
  const [amount, setAmount] = useState('');
  
  const handlePartialPayment = async () => {
    try {
      const result = await addPartialPayment(
        paymentRequest.id, 
        parseFloat(amount)
      );
      
      if (result.success) {
        // تحديث البيانات
        updatePaymentRequest(result.data);
        alert(result.message);
      }
    } catch (error) {
      alert('حدث خطأ: ' + error.message);
    }
  };
  
  return (
    <div>
      <h3>دفعة مؤجلة: {paymentRequest.approved_amount} {paymentRequest.currency}</h3>
      
      <div>
        <p>المدفوع: {paymentRequest.paid_amount} {paymentRequest.currency}</p>
        <p>المتبقي: {paymentRequest.remaining_amount} {paymentRequest.currency}</p>
        <p>الحالة: {paymentRequest.is_fully_paid ? 'مكتملة ✅' : 'غير مكتملة'}</p>
      </div>
      
      {!paymentRequest.is_fully_paid && (
        <div>
          <input
            type="number"
            value={amount}
            onChange={(e) => setAmount(e.target.value)}
            placeholder="المبلغ الجزئي"
            max={paymentRequest.remaining_amount}
          />
          <button onClick={handlePartialPayment}>
            إضافة دفعة جزئية
          </button>
          <button onClick={() => completePayment(paymentRequest.id)}>
            إكمال الدفعة
          </button>
        </div>
      )}
      
      {paymentRequest.partial_payments && paymentRequest.partial_payments.length > 0 && (
        <div>
          <h4>الدفعات الجزئية:</h4>
          {paymentRequest.partial_payments.map(partial => (
            <div key={partial.id}>
              <p>{partial.amount} {partial.currency} - {partial.payment_date}</p>
              <p>{partial.notes}</p>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
```

---

## ⚠️ الأخطاء المحتملة

```json
// المبلغ أكبر من المتبقي
{
  "success": false,
  "message": "المبلغ المدخل (10) أكبر من المبلغ المتبقي (5)"
}

// المبلغ صفر أو سالب
{
  "success": false,
  "message": "المبلغ يجب أن يكون أكبر من صفر"
}

// الدفعة ليست مؤجلة
{
  "success": false,
  "message": "هذه الدفعة ليست مؤجلة"
}
```

---

## 📋 Checklist للفرونت إند

- [ ] تحديث نموذج `mark-as-paid` لإضافة حقل `amount` (اختياري)
- [ ] عرض `paid_amount` و `remaining_amount` في واجهة الدفعات
- [ ] عرض `is_fully_paid` لتحديد حالة الدفعة
- [ ] عرض قائمة `partial_payments` عند وجودها
- [ ] إضافة validation للمبلغ الجزئي (لا يتجاوز `remaining_amount`)
- [ ] تحديث الـ API calls لتضمين `partialPayments` في الـ eager loading
- [ ] معالجة الأخطاء الجديدة

---

## 🔗 الملفات المرجعية

- **التوثيق الكامل:** `API_CHANGES_FOR_FRONTEND.md`
- **Endpoint:** `POST /api/admin/payment-requests/{id}/mark-as-paid`
- **الصلاحيات:** `admin` أو `accountant`

---

**آخر تحديث:** 2025-12-14

