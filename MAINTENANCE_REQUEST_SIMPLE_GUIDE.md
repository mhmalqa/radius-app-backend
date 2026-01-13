# 🔧 دليل بسيط: إرسال طلب صيانة

## 📍 رابط API

```
POST /api/maintenance-requests
```

**يتطلب تسجيل دخول** - يجب إرسال Token

---

## 📤 البيانات المطلوبة

```json
{
    "address": " عمارة رقم 15",
    "description": "مشكلة في الاتصال بالإنترنت، لا يعمل الإنترنت منذ يومين"
}
```

### الحقول

| الحقل        | مطلوب | الوصف                    | القيود           |
| ------------ | ----- | ------------------------ | ---------------- |
| `address`    | ✅    | عنوان الصيانة            | 10-500 حرف       |
| `description`| ❌    | وصف المشكلة (اختياري)    | حتى 1000 حرف     |

---

## 📥 الاستجابة الناجحة

```json
{
    "success": true,
    "message": "تم إرسال طلب الصيانة بنجاح",
    "data": {
        "id": 1,
        "address": "عمارة رقم 15",
        "description": "مشكلة في الاتصال بالإنترنت، لا يعمل الإنترنت منذ يومين",
        "status": "pending",
        "status_label": "قيد الانتظار",
        "subscription_data": {
            "username": "user001",
            "service": "2M-PPP",
            "active": true
        },
        "created_at": "2025-12-15T10:30:00Z"
    }
}
```

---

## ⚠️ حالات الخطأ

### 401 - غير مصرح

```json
{
    "success": false,
    "message": "Unauthenticated."
}
```

**الحل**: تأكد من إرسال Token صحيح

### 422 - خطأ في البيانات

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "address": [
            "العنوان يجب أن يكون على الأقل 10 أحرف"
        ]
    }
}
```

**الحل**: تحقق من صحة البيانات المرسلة

### 500 - خطأ في جلب بيانات Radius

```json
{
    "success": false,
    "message": "فشل في جلب بيانات الاشتراك من الراديوس. يرجى المحاولة مرة أخرى"
}
```

**الحل**: حاول مرة أخرى بعد قليل

---

## 💻 أمثلة الاستخدام

### JavaScript/React

```javascript
// إرسال طلب صيانة
async function submitMaintenanceRequest(address, description = "") {
    const token = localStorage.getItem("token");

    const response = await fetch("/api/maintenance-requests", {
        method: "POST",
        headers: {
            "Authorization": `Bearer ${token}`,
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            address: address,
            description: description,
        }),
    });

    const data = await response.json();

    if (data.success) {
        return data.data;
    } else {
        throw new Error(data.message || "فشل في إرسال الطلب");
    }
}

// الاستخدام
try {
    const request = await submitMaintenanceRequest(
        "بغداد - الكرادة - شارع الكرادة الداخلية - عمارة رقم 15",
        "مشكلة في الاتصال بالإنترنت"
    );
    console.log("تم إرسال الطلب:", request);
} catch (error) {
    console.error("خطأ:", error.message);
}
```

### React Component

```jsx
import { useState } from "react";

function MaintenanceRequestForm() {
    const [address, setAddress] = useState("");
    const [description, setDescription] = useState("");
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(false);

    async function handleSubmit(e) {
        e.preventDefault();
        setLoading(true);
        setError(null);
        setSuccess(false);

        try {
            const token = localStorage.getItem("token");

            const response = await fetch("/api/maintenance-requests", {
                method: "POST",
                headers: {
                    "Authorization": `Bearer ${token}`,
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    address: address,
                    description: description,
                }),
            });

            const data = await response.json();

            if (data.success) {
                setSuccess(true);
                setAddress("");
                setDescription("");
                // إعادة توجيه أو إغلاق النموذج
            } else {
                setError(data.message || "فشل في إرسال الطلب");
            }
        } catch (err) {
            setError("حدث خطأ أثناء إرسال الطلب");
        } finally {
            setLoading(false);
        }
    }

    return (
        <form onSubmit={handleSubmit} className="maintenance-form">
            <h2>طلب صيانة</h2>

            {error && <div className="error">{error}</div>}
            {success && (
                <div className="success">
                    تم إرسال طلب الصيانة بنجاح
                </div>
            )}

            <div className="form-group">
                <label htmlFor="address">العنوان *</label>
                <input
                    id="address"
                    type="text"
                    value={address}
                    onChange={(e) => setAddress(e.target.value)}
                    placeholder="أدخل عنوان الصيانة (10-500 حرف)"
                    required
                    minLength={10}
                    maxLength={500}
                />
            </div>

            <div className="form-group">
                <label htmlFor="description">وصف المشكلة</label>
                <textarea
                    id="description"
                    value={description}
                    onChange={(e) => setDescription(e.target.value)}
                    placeholder="وصف المشكلة (اختياري)"
                    rows={5}
                    maxLength={1000}
                />
            </div>

            <button type="submit" disabled={loading}>
                {loading ? "جاري الإرسال..." : "إرسال الطلب"}
            </button>
        </form>
    );
}
```

### Vue.js

```vue
<template>
    <form @submit.prevent="submitRequest" class="maintenance-form">
        <h2>طلب صيانة</h2>

        <div v-if="error" class="error">{{ error }}</div>
        <div v-if="success" class="success">
            تم إرسال طلب الصيانة بنجاح
        </div>

        <div class="form-group">
            <label>العنوان *</label>
            <input
                v-model="address"
                type="text"
                placeholder="أدخل عنوان الصيانة"
                required
                minlength="10"
                maxlength="500"
            />
        </div>

        <div class="form-group">
            <label>وصف المشكلة</label>
            <textarea
                v-model="description"
                placeholder="وصف المشكلة (اختياري)"
                rows="5"
                maxlength="1000"
            ></textarea>
        </div>

        <button type="submit" :disabled="loading">
            {{ loading ? "جاري الإرسال..." : "إرسال الطلب" }}
        </button>
    </form>
</template>

<script>
export default {
    data() {
        return {
            address: "",
            description: "",
            loading: false,
            error: null,
            success: false,
        };
    },

    methods: {
        async submitRequest() {
            this.loading = true;
            this.error = null;
            this.success = false;

            try {
                const token = localStorage.getItem("token");

                const response = await fetch("/api/maintenance-requests", {
                    method: "POST",
                    headers: {
                        Authorization: `Bearer ${token}`,
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({
                        address: this.address,
                        description: this.description,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    this.success = true;
                    this.address = "";
                    this.description = "";
                } else {
                    this.error = data.message || "فشل في إرسال الطلب";
                }
            } catch (err) {
                this.error = "حدث خطأ أثناء إرسال الطلب";
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>
```

---

## 🎨 CSS بسيط

```css
.maintenance-form {
    max-width: 600px;
    margin: 20px auto;
    padding: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.maintenance-form h2 {
    margin-bottom: 20px;
    color: #333;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #555;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    font-family: inherit;
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.maintenance-form button {
    width: 100%;
    padding: 12px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    cursor: pointer;
}

.maintenance-form button:hover:not(:disabled) {
    background: #0056b3;
}

.maintenance-form button:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.error {
    padding: 10px;
    background: #fee;
    color: #c33;
    border-radius: 4px;
    margin-bottom: 15px;
}

.success {
    padding: 10px;
    background: #efe;
    color: #3c3;
    border-radius: 4px;
    margin-bottom: 15px;
}
```

---

## 📋 معلومات مهمة

### ما يحدث تلقائياً

1. **جلب بيانات الاشتراك**: النظام يجلب بيانات المشترك من Radius تلقائياً
2. **حفظ البيانات**: يتم حفظ بيانات الاشتراك مع الطلب
3. **إشعار المسؤولين**: يتم إرسال إشعار للمديرين والمحاسبين
4. **الحالة الافتراضية**: الطلب يُنشأ بحالة `pending` (قيد الانتظار)

### حالات الطلب

- `pending`: قيد الانتظار
- `submitted`: تم التقديم
- `in_progress`: قيد التنفيذ
- `completed`: مكتمل
- `cancelled`: ملغي

---

## 🔍 عرض طلبات الصيانة

### جلب جميع طلبات المستخدم

```
GET /api/maintenance-requests
Authorization: Bearer {token}
```

**معاملات اختيارية**:
- `status`: فلترة حسب الحالة (`pending`, `in_progress`, `completed`, إلخ)

**مثال**:
```javascript
async function getMyRequests(status = null) {
    const token = localStorage.getItem("token");
    const url = status 
        ? `/api/maintenance-requests?status=${status}`
        : "/api/maintenance-requests";

    const response = await fetch(url, {
        headers: {
            Authorization: `Bearer ${token}`,
        },
    });

    const data = await response.json();
    return data.success ? data.data : [];
}
```

### جلب طلب محدد

```
GET /api/maintenance-requests/{id}
Authorization: Bearer {token}
```

---

## ⚠️ ملاحظات مهمة

1. **يجب تسجيل الدخول**: الطلب يتطلب Token صحيح
2. **العنوان مطلوب**: يجب أن يكون بين 10-500 حرف
3. **الوصف اختياري**: يمكن تركه فارغاً
4. **البيانات التلقائية**: بيانات الاشتراك تُجلب تلقائياً من Radius
5. **الإشعارات**: يتم إرسال إشعار للمسؤولين عند إنشاء الطلب

---

## 📝 الخلاصة

- **الرابط**: `POST /api/maintenance-requests`
- **يتطلب تسجيل دخول**: نعم (Bearer Token)
- **الحقول المطلوبة**: `address` فقط
- **الحقول الاختيارية**: `description`
- **البيانات التلقائية**: بيانات الاشتراك تُجلب تلقائياً

