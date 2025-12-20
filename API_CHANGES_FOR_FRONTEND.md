# 📋 التغييرات الجديدة في API - للفرونت إند

## 🆕 الميزات الجديدة

### 1. **نظام الدفعات الجزئية للدفعات المؤجلة**

الآن يمكن إضافة دفعات جزئية للدفعات المؤجلة بدلاً من دفع المبلغ كاملاً مرة واحدة.

---

## 🔄 التغييرات في الـ Endpoints

### **POST `/api/admin/payment-requests/{id}/mark-as-paid`**

#### **التغييرات:**

-   ✅ الآن يقبل **مبلغ جزئي** (اختياري)
-   ✅ يمكن إرسال `amount` لإضافة دفعة جزئية
-   ✅ بدون `amount` = إكمال الدفعة بالكامل

#### **Request Body (اختياري):**

```json
{
    "amount": 5, // المبلغ الجزئي (اختياري)
    "notes": "دفعة جزئية", // ملاحظات (اختياري)
    "payment_date": "2025-12-14" // تاريخ الدفعة (اختياري)
}
```

#### **الحالات:**

**1. إضافة دفعة جزئية (مع `amount`):**

```http
POST /api/admin/payment-requests/123/mark-as-paid
Content-Type: application/json
Authorization: Bearer {token}

{
  "amount": 5,
  "notes": "دفعة جزئية",
  "payment_date": "2025-12-14"
}
```

**Response:**

```json
{
    "success": true,
    "message": "تم إضافة دفعة جزئية بنجاح. المبلغ المدفوع: 5، المتبقي: 5",
    "data": {
        // PaymentRequest object مع الحقول الجديدة
    },
    "partial_payment": {
        "id": 1,
        "amount": 5,
        "payment_date": "2025-12-14"
    }
}
```

**2. إكمال الدفعة بالكامل (بدون `amount`):**

```http
POST /api/admin/payment-requests/123/mark-as-paid
Content-Type: application/json
Authorization: Bearer {token}

{}
```

**Response:**

```json
{
    "success": true,
    "message": "تم تحديث حالة الدفعة إلى مدفوع بنجاح",
    "data": {
        // PaymentRequest object
    }
}
```

---

## 📊 التغييرات في Response Structure

### **PaymentRequest Object - الحقول الجديدة:**

#### **1. `paid_amount` (number)**

-   المبلغ المدفوع حتى الآن
-   القيمة الافتراضية: `0`
-   مثال: `5.00`

#### **2. `remaining_amount` (number)**

-   المبلغ المتبقي للدفع
-   يتم حسابه تلقائياً: `approved_amount - paid_amount`
-   مثال: `5.00`

#### **3. `is_fully_paid` (boolean)**

-   هل الدفعة مكتملة بالكامل
-   `true` إذا كان `paid_amount >= approved_amount`
-   مثال: `false`

#### **4. `partial_payments` (array)**

-   قائمة بجميع الدفعات الجزئية
-   يظهر فقط عند تحميل العلاقة `partialPayments`
-   **يجب إضافة `partialPayments` في الـ eager loading**

**مثال:**

```json
{
    "partial_payments": [
        {
            "id": 1,
            "amount": 5.0,
            "currency": "IQD",
            "payment_date": "2025-12-14",
            "notes": "دفعة جزئية - 5 IQD",
            "created_by": {
                "id": 2,
                "name": "محاسب",
                "email": "accountant@example.com"
            },
            "created_at": "2025-12-14T10:30:00Z"
        },
        {
            "id": 2,
            "amount": 3.0,
            "currency": "IQD",
            "payment_date": "2025-12-15",
            "notes": "دفعة جزئية - 3 IQD",
            "created_by": {
                "id": 2,
                "name": "محاسب",
                "email": "accountant@example.com"
            },
            "created_at": "2025-12-15T14:20:00Z"
        }
    ]
}
```

---

## 📝 مثال كامل للـ Response

```json
{
    "success": true,
    "data": {
        "id": 123,
        "user": {
            "id": 1,
            "name": "أحمد محمد",
            "username": "ahmed123"
        },
        "payment_type": "cash",
        "payment_type_label": "نقدي",
        "amount": 10.0,
        "currency": "IQD",
        "approved_amount": 10.0,
        "paid_amount": 5.0, // ⭐ جديد
        "remaining_amount": 5.0, // ⭐ جديد
        "is_fully_paid": false, // ⭐ جديد
        "is_deferred": true,
        "is_paid": false,
        "payment_status_label": "دفع مؤجل",
        "partial_payments": [
            // ⭐ جديد
            {
                "id": 1,
                "amount": 5.0,
                "currency": "IQD",
                "payment_date": "2025-12-14",
                "notes": "دفعة جزئية - 5 IQD",
                "created_by": {
                    "id": 2,
                    "name": "محاسب",
                    "email": "accountant@example.com"
                },
                "created_at": "2025-12-14T10:30:00Z"
            }
        ],
        "status": 1,
        "status_label": "مقبول",
        "notes": "دفعة مؤجلة - لم يتم الدفع بعد - تم دفع 5 IQD في: 2025-12-14 10:30:00",
        "created_at": "2025-12-13T08:00:00Z",
        "updated_at": "2025-12-14T10:30:00Z"
    }
}
```

---

## 🔍 ملاحظات مهمة للفرونت إند

### **1. عند جلب PaymentRequest:**

يجب إضافة `partialPayments` في الـ eager loading:

```javascript
// مثال في API call
GET /api/admin/payment-requests/123?include=partialPayments,user,creator,reviewer
```

أو في الـ Controller:

```php
$paymentRequest->load(['partialPayments', 'user', 'creator', 'reviewer']);
```

### **2. التحقق من الأخطاء:**

عند إضافة دفعة جزئية، قد تحصل على هذه الأخطاء:

```json
{
    "success": false,
    "message": "المبلغ المدخل (10) أكبر من المبلغ المتبقي (5)"
}
```

```json
{
    "success": false,
    "message": "المبلغ يجب أن يكون أكبر من صفر"
}
```

```json
{
    "success": false,
    "message": "هذه الدفعة ليست مؤجلة"
}
```

### **3. عرض حالة الدفعة:**

استخدم الحقول الجديدة لعرض حالة الدفعة:

```javascript
// مثال في React/Vue
const paymentStatus = () => {
    if (paymentRequest.is_fully_paid) {
        return "مدفوع بالكامل";
    }
    if (paymentRequest.paid_amount > 0) {
        return `مدفوع جزئياً (${paymentRequest.paid_amount} / ${paymentRequest.approved_amount})`;
    }
    if (paymentRequest.is_deferred) {
        return "دفع مؤجل";
    }
    return "غير مدفوع";
};
```

### **4. عرض قائمة الدفعات الجزئية:**

```javascript
// مثال في React/Vue
{
    paymentRequest.partial_payments?.map((partial) => (
        <div key={partial.id}>
            <p>
                المبلغ: {partial.amount} {partial.currency}
            </p>
            <p>التاريخ: {partial.payment_date}</p>
            <p>الملاحظات: {partial.notes}</p>
            <p>أضافها: {partial.created_by.name}</p>
        </div>
    ));
}
```

### **5. نموذج إضافة دفعة جزئية:**

```javascript
// مثال في React/Vue
const handlePartialPayment = async (paymentRequestId, amount) => {
    try {
        const response = await fetch(
            `/api/admin/payment-requests/${paymentRequestId}/mark-as-paid`,
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Authorization: `Bearer ${token}`,
                },
                body: JSON.stringify({
                    amount: amount,
                    notes: "دفعة جزئية",
                    payment_date: new Date().toISOString().split("T")[0],
                }),
            }
        );

        const data = await response.json();

        if (data.success) {
            // تحديث PaymentRequest
            updatePaymentRequest(data.data);

            // عرض رسالة نجاح
            showMessage(data.message);
        }
    } catch (error) {
        console.error("Error:", error);
    }
};
```

---

## 📌 ملخص التغييرات

| الحقل              | النوع   | الوصف                   | مثال    |
| ------------------ | ------- | ----------------------- | ------- |
| `paid_amount`      | number  | المبلغ المدفوع حتى الآن | `5.00`  |
| `remaining_amount` | number  | المبلغ المتبقي          | `5.00`  |
| `is_fully_paid`    | boolean | هل الدفعة مكتملة        | `false` |
| `partial_payments` | array   | قائمة الدفعات الجزئية   | `[...]` |

---

## 🎯 سيناريوهات الاستخدام

### **السيناريو 1: دفعة مؤجلة 10 IQD**

1. إنشاء دفعة مؤجلة: `10 IQD`
2. إضافة دفعة جزئية: `5 IQD` → المتبقي: `5 IQD`
3. إضافة دفعة جزئية: `3 IQD` → المتبقي: `2 IQD`
4. إكمال الدفعة: `2 IQD` → الدفعة مكتملة ✅

### **السيناريو 2: إكمال دفعة جزئية مباشرة**

1. إنشاء دفعة مؤجلة: `10 IQD`
2. إضافة دفعة جزئية: `5 IQD` → المتبقي: `5 IQD`
3. إكمال الدفعة (بدون `amount`) → يدفع المتبقي `5 IQD` تلقائياً ✅

---

## ⚠️ ملاحظات أمنية

1. **التحقق من المبلغ:** النظام يتحقق تلقائياً من أن المبلغ الجزئي لا يتجاوز المبلغ المتبقي
2. **تسجيل الدفعات:** كل دفعة جزئية تُسجل في جدول منفصل مع معلومات من أضافها
3. **الإيرادات:** كل دفعة جزئية تُضاف تلقائياً إلى جدول الإيرادات

---

## 🔗 روابط مفيدة

-   **Endpoint:** `POST /api/admin/payment-requests/{id}/mark-as-paid`
-   **الصلاحيات:** `admin` أو `accountant` فقط
-   **التحقق:** يجب أن تكون الدفعة `is_deferred = true` و `status = approved`

---

**تاريخ التحديث:** 2025-12-14  
**الإصدار:** 2.0.0
