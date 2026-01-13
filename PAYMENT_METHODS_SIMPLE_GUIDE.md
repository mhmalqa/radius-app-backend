# 💳 دليل بسيط: عرض طرق الدفع

## 📍 رابط API

```
GET /api/payment-methods
```

**لا يتطلب تسجيل دخول** - متاح للجميع

---

## 📥 الاستجابة

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Credit Card",
            "name_ar": "بطاقة ائتمان",
            "icon": "https://domain.com/storage/payment_methods/icon.jpg",
            "qr_code": "https://domain.com/storage/payment_methods/qr.jpg",
            "code": "credit_card",
            "is_active": true,
            "instructions": "قم بتحويل المبلغ إلى الحساب التالي...",
            "sort_order": 1
        },
        {
            "id": 2,
            "name": "Bank Transfer",
            "name_ar": "تحويل بنكي",
            "icon": "https://domain.com/storage/payment_methods/bank.jpg",
            "qr_code": null,
            "code": "bank_transfer",
            "is_active": true,
            "instructions": "قم بإرسال صورة الإيصال بعد التحويل",
            "sort_order": 2
        }
    ]
}
```

---

## 📋 الحقول المهمة

| الحقل          | الوصف                   | مثال                      |
| -------------- | ----------------------- | ------------------------- |
| `id`           | رقم طريقة الدفع         | `1`                       |
| `name`         | الاسم بالإنجليزية       | `"Credit Card"`           |
| `name_ar`      | الاسم بالعربية          | `"بطاقة ائتمان"`          |
| `icon`         | رابط أيقونة طريقة الدفع | `"https://..."` أو `null` |
| `qr_code`      | رابط صورة QR Code       | `"https://..."` أو `null` |
| `code`         | كود طريقة الدفع         | `"credit_card"`           |
| `instructions` | تعليمات الدفع           | `"قم بتحويل المبلغ..."`   |
| `sort_order`   | ترتيب العرض             | `1` (الأقل = الأول)       |

**ملاحظة**: يتم إرجاع فقط طرق الدفع النشطة (`is_active = true`)

---

## 💻 مثال بسيط للاستخدام

### JavaScript/React

```javascript
// جلب طرق الدفع
async function getPaymentMethods() {
    const response = await fetch("/api/payment-methods");
    const result = await response.json();

    if (result.success) {
        return result.data;
    }
    return [];
}

// الاستخدام
const methods = await getPaymentMethods();
console.log("طرق الدفع:", methods);
```

### عرض في الواجهة

```jsx
import { useState, useEffect } from "react";

function PaymentMethodsList() {
    const [methods, setMethods] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchPaymentMethods();
    }, []);

    async function fetchPaymentMethods() {
        try {
            const response = await fetch("/api/payment-methods");
            const data = await response.json();

            if (data.success) {
                setMethods(data.data);
            }
        } catch (error) {
            console.error("خطأ:", error);
        } finally {
            setLoading(false);
        }
    }

    if (loading) return <div>جاري التحميل...</div>;

    return (
        <div className="payment-methods">
            <h2>طرق الدفع المتاحة</h2>

            {methods.map((method) => (
                <div key={method.id} className="payment-method-card">
                    {/* الأيقونة */}
                    {method.icon && (
                        <img
                            src={method.icon}
                            alt={method.name_ar}
                            className="icon"
                        />
                    )}

                    {/* الاسم */}
                    <h3>{method.name_ar}</h3>

                    {/* QR Code */}
                    {method.qr_code && (
                        <img
                            src={method.qr_code}
                            alt="QR Code"
                            className="qr-code"
                        />
                    )}

                    {/* التعليمات */}
                    {method.instructions && (
                        <p className="instructions">{method.instructions}</p>
                    )}

                    {/* زر اختيار */}
                    <button onClick={() => selectMethod(method.id)}>
                        اختر هذه الطريقة
                    </button>
                </div>
            ))}
        </div>
    );
}
```

---

## 🎨 مثال CSS بسيط

```css
.payment-methods {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    padding: 20px;
}

.payment-method-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    background: white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.payment-method-card .icon {
    width: 60px;
    height: 60px;
    object-fit: contain;
    margin-bottom: 10px;
}

.payment-method-card .qr-code {
    width: 150px;
    height: 150px;
    margin: 15px auto;
    display: block;
}

.payment-method-card h3 {
    margin: 10px 0;
    color: #333;
}

.payment-method-card .instructions {
    font-size: 14px;
    color: #666;
    margin: 10px 0;
    text-align: right;
}

.payment-method-card button {
    background: #007bff;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    margin-top: 10px;
}

.payment-method-card button:hover {
    background: #0056b3;
}
```

---

## 📱 مثال Vue.js

```vue
<template>
    <div class="payment-methods">
        <h2>طرق الدفع المتاحة</h2>

        <div v-if="loading">جاري التحميل...</div>

        <div v-else class="methods-grid">
            <div v-for="method in methods" :key="method.id" class="method-card">
                <img
                    v-if="method.icon"
                    :src="method.icon"
                    :alt="method.name_ar"
                    class="icon"
                />

                <h3>{{ method.name_ar }}</h3>

                <img
                    v-if="method.qr_code"
                    :src="method.qr_code"
                    alt="QR Code"
                    class="qr-code"
                />

                <p v-if="method.instructions" class="instructions">
                    {{ method.instructions }}
                </p>

                <button @click="selectMethod(method)">اختر هذه الطريقة</button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            methods: [],
            loading: true,
        };
    },

    mounted() {
        this.fetchMethods();
    },

    methods: {
        async fetchMethods() {
            try {
                const response = await fetch("/api/payment-methods");
                const data = await response.json();

                if (data.success) {
                    this.methods = data.data;
                }
            } catch (error) {
                console.error("خطأ:", error);
            } finally {
                this.loading = false;
            }
        },

        selectMethod(method) {
            // حفظ طريقة الدفع المختارة
            this.$emit("method-selected", method);
        },
    },
};
</script>
```

---

## ⚠️ ملاحظات مهمة

1. **الترتيب**: طرق الدفع مرتبة حسب `sort_order` (الأقل = الأول)
2. **النشطة فقط**: يتم إرجاع فقط طرق الدفع النشطة
3. **الصور**: الأيقونة و QR Code اختيارية (قد تكون `null`)
4. **لا يحتاج تسجيل دخول**: هذا الـ endpoint متاح للجميع

---

## 🔗 استخدام طريقة الدفع

بعد اختيار المستخدم لطريقة الدفع، يمكنك استخدام `id` أو `code` عند إنشاء طلب دفع:

```javascript
// مثال: إنشاء طلب دفع
async function createPaymentRequest(amount, paymentMethodId) {
    const token = localStorage.getItem("token");

    const response = await fetch("/api/payment-requests", {
        method: "POST",
        headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            amount: amount,
            payment_method_id: paymentMethodId,
            // ... باقي البيانات
        }),
    });

    return await response.json();
}
```

---

## 📝 الخلاصة

-   **الرابط**: `GET /api/payment-methods`
-   **لا يحتاج تسجيل دخول**: متاح للجميع
-   **الترتيب**: حسب `sort_order`
-   **النشطة فقط**: يتم إرجاع فقط طرق الدفع النشطة
-   **الصور**: `icon` و `qr_code` اختيارية
