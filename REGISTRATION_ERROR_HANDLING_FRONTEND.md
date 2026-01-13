# 🔐 دليل معالجة أخطاء التسجيل في الواجهة الأمامية

## 📋 نظرة عامة

يشرح هذا الدليل كيفية التعامل مع رسائل الخطأ عند التسجيل وعرضها للمستخدم بشكل واضح واحترافي. النظام يعيد رسائل خطأ محددة وواضحة لكل حالة فشل.

---

## 🔗 نقطة النهاية (API Endpoint)

```
POST /api/auth/register
Content-Type: application/json
```

---

## 📤 البيانات المطلوبة

```json
{
    "username": "user001",
    "password": "password123",
    "password_confirmation": "password123",
    "phone": "07501234567",
    "email": "user@example.com",
    "language": "ar"
}
```

---

## 📥 حالات الاستجابة

### ✅ نجاح التسجيل (201)

```json
{
    "success": true,
    "message": "تم إنشاء الحساب بنجاح",
    "data": {
        "user": {
            "id": 1,
            "username": "user001",
            "email": "user@example.com",
            "phone": "07501234567",
            "role": 0,
            "is_active": true,
            "live_access": false,
            "language": "ar"
        },
        "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
    }
}
```

### ❌ فشل التسجيل (422)

```json
{
    "success": false,
    "message": "رسالة الخطأ الواضحة هنا"
}
```

---

## ⚠️ جميع حالات الخطأ المحتملة

### 1. أخطاء التحقق من البيانات (Validation Errors)

#### أ. اسم المستخدم مطلوب

```json
{
    "success": false,
    "message": "اسم المستخدم مطلوب"
}
```

#### ب. اسم المستخدم مستخدم بالفعل

```json
{
    "success": false,
    "message": "اسم المستخدم مستخدم بالفعل. يرجى استخدام اسم مستخدم آخر أو تسجيل الدخول إذا كان لديك حساب."
}
```

#### ج. كلمة المرور قصيرة

```json
{
    "success": false,
    "message": "كلمة المرور يجب أن تكون على الأقل 6 أحرف"
}
```

#### د. تأكيد كلمة المرور غير متطابق

```json
{
    "success": false,
    "message": "تأكيد كلمة المرور غير متطابق. يرجى التأكد من تطابق كلمة المرور وتأكيدها."
}
```

#### هـ. البريد الإلكتروني غير صحيح

```json
{
    "success": false,
    "message": "البريد الإلكتروني غير صحيح. يرجى إدخال بريد إلكتروني صالح."
}
```

### 2. أخطاء منطق التطبيق (Business Logic Errors)

#### أ. المستخدم مسجل بالفعل في التطبيق

```json
{
    "success": false,
    "message": "اسم المستخدم مستخدم بالفعل. يرجى استخدام اسم مستخدم آخر أو تسجيل الدخول إذا كان لديك حساب."
}
```

#### ب. اسم المستخدم غير موجود في نظام Radius

```json
{
    "success": false,
    "message": "اسم المستخدم غير موجود في نظام Radius. يرجى التحقق من اسم المستخدم."
}
```

#### ج. فشل الاتصال بنظام Radius

```json
{
    "success": false,
    "message": "فشل الاتصال بنظام Radius. يرجى المحاولة مرة أخرى لاحقاً أو الاتصال بالدعم الفني."
}
```

#### د. فشل جلب بيانات المستخدم من Radius

```json
{
    "success": false,
    "message": "فشل في جلب بيانات المستخدم من نظام Radius. يرجى المحاولة مرة أخرى أو الاتصال بالدعم الفني."
}
```

---

## 💻 التكامل مع الواجهة الأمامية

### 1. JavaScript/React - مثال بسيط

```javascript
async function registerUser(userData) {
    try {
        const response = await fetch("/api/auth/register", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify(userData),
        });

        const data = await response.json();

        if (data.success) {
            // نجاح التسجيل
            console.log("تم إنشاء الحساب بنجاح:", data.data);
            return { success: true, data: data.data };
        } else {
            // فشل التسجيل - رسالة خطأ واضحة
            return {
                success: false,
                error: data.message,
            };
        }
    } catch (error) {
        // خطأ في الاتصال
        return {
            success: false,
            error: "حدث خطأ أثناء الاتصال بالخادم. يرجى المحاولة مرة أخرى.",
        };
    }
}
```

### 2. React Component - مثال كامل

```jsx
import { useState } from "react";
import { useNavigate } from "react-router-dom";

function RegisterForm() {
    const navigate = useNavigate();
    const [formData, setFormData] = useState({
        username: "",
        password: "",
        password_confirmation: "",
        phone: "",
        email: "",
        language: "ar",
    });
    const [errors, setErrors] = useState({});
    const [loading, setLoading] = useState(false);
    const [generalError, setGeneralError] = useState(null);

    async function handleSubmit(e) {
        e.preventDefault();
        setLoading(true);
        setErrors({});
        setGeneralError(null);

        try {
            const response = await fetch("/api/auth/register", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(formData),
            });

            const data = await response.json();

            if (data.success) {
                // حفظ Token
                localStorage.setItem("token", data.data.token);

                // توجيه المستخدم
                navigate("/dashboard");
            } else {
                // عرض رسالة الخطأ الواضحة
                setGeneralError(data.message);
            }
        } catch (error) {
            setGeneralError(
                "حدث خطأ أثناء الاتصال بالخادم. يرجى المحاولة مرة أخرى."
            );
        } finally {
            setLoading(false);
        }
    }

    function handleChange(e) {
        const { name, value } = e.target;
        setFormData((prev) => ({
            ...prev,
            [name]: value,
        }));
        // مسح الخطأ عند التعديل
        if (errors[name]) {
            setErrors((prev) => ({
                ...prev,
                [name]: null,
            }));
        }
        if (generalError) {
            setGeneralError(null);
        }
    }

    return (
        <form onSubmit={handleSubmit} className="register-form">
            <h2>إنشاء حساب جديد</h2>

            {/* رسالة الخطأ العامة */}
            {generalError && (
                <div className="error-message general-error">
                    <span className="error-icon">⚠️</span>
                    <span>{generalError}</span>
                </div>
            )}

            {/* حقل اسم المستخدم */}
            <div className="form-group">
                <label htmlFor="username">اسم المستخدم *</label>
                <input
                    id="username"
                    name="username"
                    type="text"
                    value={formData.username}
                    onChange={handleChange}
                    required
                    className={errors.username ? "error" : ""}
                />
                {errors.username && (
                    <span className="field-error">{errors.username}</span>
                )}
            </div>

            {/* حقل كلمة المرور */}
            <div className="form-group">
                <label htmlFor="password">كلمة المرور *</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    value={formData.password}
                    onChange={handleChange}
                    required
                    minLength={6}
                    className={errors.password ? "error" : ""}
                />
                {errors.password && (
                    <span className="field-error">{errors.password}</span>
                )}
            </div>

            {/* حقل تأكيد كلمة المرور */}
            <div className="form-group">
                <label htmlFor="password_confirmation">
                    تأكيد كلمة المرور *
                </label>
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    value={formData.password_confirmation}
                    onChange={handleChange}
                    required
                    className={errors.password_confirmation ? "error" : ""}
                />
                {errors.password_confirmation && (
                    <span className="field-error">
                        {errors.password_confirmation}
                    </span>
                )}
            </div>

            {/* حقل الهاتف */}
            <div className="form-group">
                <label htmlFor="phone">رقم الهاتف</label>
                <input
                    id="phone"
                    name="phone"
                    type="tel"
                    value={formData.phone}
                    onChange={handleChange}
                />
            </div>

            {/* حقل البريد الإلكتروني */}
            <div className="form-group">
                <label htmlFor="email">البريد الإلكتروني</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value={formData.email}
                    onChange={handleChange}
                    className={errors.email ? "error" : ""}
                />
                {errors.email && (
                    <span className="field-error">{errors.email}</span>
                )}
            </div>

            {/* زر الإرسال */}
            <button type="submit" disabled={loading} className="submit-button">
                {loading ? "جاري إنشاء الحساب..." : "إنشاء حساب"}
            </button>
        </form>
    );
}

export default RegisterForm;
```

### 3. React Hook - Custom Hook للتعامل مع الأخطاء

```javascript
// hooks/useRegistration.js
import { useState } from "react";
import { useNavigate } from "react-router-dom";

export function useRegistration() {
    const navigate = useNavigate();
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    async function register(userData) {
        setLoading(true);
        setError(null);

        try {
            const response = await fetch("/api/auth/register", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(userData),
            });

            const data = await response.json();

            if (data.success) {
                // حفظ Token
                localStorage.setItem("token", data.data.token);

                // توجيه المستخدم
                navigate("/dashboard");

                return { success: true, data: data.data };
            } else {
                // عرض رسالة الخطأ الواضحة
                setError(data.message);
                return { success: false, error: data.message };
            }
        } catch (err) {
            const errorMessage =
                "حدث خطأ أثناء الاتصال بالخادم. يرجى المحاولة مرة أخرى.";
            setError(errorMessage);
            return { success: false, error: errorMessage };
        } finally {
            setLoading(false);
        }
    }

    return {
        register,
        loading,
        error,
        clearError: () => setError(null),
    };
}
```

**الاستخدام**:

```jsx
import { useRegistration } from "../hooks/useRegistration";

function RegisterPage() {
    const { register, loading, error } = useRegistration();

    async function handleSubmit(formData) {
        await register(formData);
    }

    return (
        <div>
            {error && <div className="error-message">{error}</div>}
            {/* باقي النموذج */}
        </div>
    );
}
```

### 4. Vue.js - مثال كامل

```vue
<template>
    <form @submit.prevent="handleSubmit" class="register-form">
        <h2>إنشاء حساب جديد</h2>

        <!-- رسالة الخطأ العامة -->
        <div v-if="error" class="error-message general-error">
            <span class="error-icon">⚠️</span>
            <span>{{ error }}</span>
        </div>

        <!-- حقل اسم المستخدم -->
        <div class="form-group">
            <label>اسم المستخدم *</label>
            <input
                v-model="formData.username"
                type="text"
                required
                :class="{ error: fieldErrors.username }"
            />
            <span v-if="fieldErrors.username" class="field-error">
                {{ fieldErrors.username }}
            </span>
        </div>

        <!-- حقل كلمة المرور -->
        <div class="form-group">
            <label>كلمة المرور *</label>
            <input
                v-model="formData.password"
                type="password"
                required
                minlength="6"
                :class="{ error: fieldErrors.password }"
            />
            <span v-if="fieldErrors.password" class="field-error">
                {{ fieldErrors.password }}
            </span>
        </div>

        <!-- حقل تأكيد كلمة المرور -->
        <div class="form-group">
            <label>تأكيد كلمة المرور *</label>
            <input
                v-model="formData.password_confirmation"
                type="password"
                required
                :class="{ error: fieldErrors.password_confirmation }"
            />
            <span v-if="fieldErrors.password_confirmation" class="field-error">
                {{ fieldErrors.password_confirmation }}
            </span>
        </div>

        <!-- حقل الهاتف -->
        <div class="form-group">
            <label>رقم الهاتف</label>
            <input v-model="formData.phone" type="tel" />
        </div>

        <!-- حقل البريد الإلكتروني -->
        <div class="form-group">
            <label>البريد الإلكتروني</label>
            <input
                v-model="formData.email"
                type="email"
                :class="{ error: fieldErrors.email }"
            />
            <span v-if="fieldErrors.email" class="field-error">
                {{ fieldErrors.email }}
            </span>
        </div>

        <!-- زر الإرسال -->
        <button type="submit" :disabled="loading" class="submit-button">
            {{ loading ? "جاري إنشاء الحساب..." : "إنشاء حساب" }}
        </button>
    </form>
</template>

<script>
export default {
    data() {
        return {
            formData: {
                username: "",
                password: "",
                password_confirmation: "",
                phone: "",
                email: "",
                language: "ar",
            },
            error: null,
            fieldErrors: {},
            loading: false,
        };
    },

    methods: {
        async handleSubmit() {
            this.loading = true;
            this.error = null;
            this.fieldErrors = {};

            try {
                const response = await fetch("/api/auth/register", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify(this.formData),
                });

                const data = await response.json();

                if (data.success) {
                    // حفظ Token
                    localStorage.setItem("token", data.data.token);

                    // توجيه المستخدم
                    this.$router.push("/dashboard");
                } else {
                    // عرض رسالة الخطأ الواضحة
                    this.error = data.message;
                }
            } catch (err) {
                this.error =
                    "حدث خطأ أثناء الاتصال بالخادم. يرجى المحاولة مرة أخرى.";
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>
```

---

## 🎨 CSS للتصميم

```css
.register-form {
    max-width: 500px;
    margin: 40px auto;
    padding: 30px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.register-form h2 {
    margin-bottom: 25px;
    text-align: center;
    color: #333;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #555;
}

.form-group input {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
    transition: border-color 0.3s;
}

.form-group input:focus {
    outline: none;
    border-color: #007bff;
}

.form-group input.error {
    border-color: #dc3545;
}

.field-error {
    display: block;
    margin-top: 5px;
    font-size: 14px;
    color: #dc3545;
}

.error-message {
    padding: 15px;
    margin-bottom: 20px;
    background: #fee;
    border: 1px solid #fcc;
    border-radius: 4px;
    color: #c33;
    display: flex;
    align-items: center;
    gap: 10px;
}

.error-message.general-error {
    background: #fff3cd;
    border-color: #ffc107;
    color: #856404;
}

.error-icon {
    font-size: 20px;
}

.submit-button {
    width: 100%;
    padding: 14px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s;
}

.submit-button:hover:not(:disabled) {
    background: #0056b3;
}

.submit-button:disabled {
    background: #ccc;
    cursor: not-allowed;
}
```

---

## 🔍 معالجة أنواع الأخطاء المختلفة

### 1. عرض رسائل خطأ محددة حسب النوع

```javascript
function getErrorType(errorMessage) {
    if (errorMessage.includes("اسم المستخدم مستخدم")) {
        return "username_exists";
    }
    if (errorMessage.includes("غير موجود في نظام Radius")) {
        return "username_not_in_radius";
    }
    if (errorMessage.includes("فشل الاتصال")) {
        return "connection_error";
    }
    if (errorMessage.includes("كلمة المرور")) {
        return "password_error";
    }
    if (errorMessage.includes("البريد الإلكتروني")) {
        return "email_error";
    }
    return "general_error";
}

function getErrorIcon(errorType) {
    const icons = {
        username_exists: "👤",
        username_not_in_radius: "🔍",
        connection_error: "🌐",
        password_error: "🔒",
        email_error: "📧",
        general_error: "⚠️",
    };
    return icons[errorType] || icons.general_error;
}
```

### 2. مثال React Component مع معالجة متقدمة

```jsx
function ErrorDisplay({ error }) {
    if (!error) return null;

    const errorType = getErrorType(error);
    const icon = getErrorIcon(errorType);

    return (
        <div className={`error-message error-${errorType}`}>
            <span className="error-icon">{icon}</span>
            <div className="error-content">
                <strong>خطأ في التسجيل</strong>
                <p>{error}</p>
                {errorType === "username_not_in_radius" && (
                    <small>
                        تأكد من أن اسم المستخدم صحيح وأن لديك اشتراك نشط.
                    </small>
                )}
                {errorType === "connection_error" && (
                    <small>
                        يرجى التحقق من اتصالك بالإنترنت والمحاولة مرة أخرى.
                    </small>
                )}
            </div>
        </div>
    );
}
```

---

## 📝 أفضل الممارسات

### 1. مسح الأخطاء عند التعديل

```javascript
function handleChange(e) {
    setFormData((prev) => ({
        ...prev,
        [e.target.name]: e.target.value,
    }));

    // مسح الخطأ عند التعديل
    if (error) {
        setError(null);
    }
}
```

### 2. عرض رسائل نجاح

```javascript
if (data.success) {
    // عرض رسالة نجاح
    showSuccessMessage("تم إنشاء الحساب بنجاح! جاري تسجيل الدخول...");

    // حفظ Token
    localStorage.setItem("token", data.data.token);

    // توجيه بعد ثانية
    setTimeout(() => {
        navigate("/dashboard");
    }, 1000);
}
```

### 3. معالجة الأخطاء المتعددة (إذا كانت API ترجعها)

```javascript
if (data.errors) {
    // أخطاء متعددة (validation errors)
    setFieldErrors(data.errors);
} else if (data.message) {
    // خطأ واحد واضح
    setGeneralError(data.message);
}
```

---

## ⚠️ ملاحظات مهمة

1. **رسائل واضحة**: جميع الرسائل بالعربية وواضحة للمستخدم
2. **لا تظهر تفاصيل تقنية**: لا تظهر أخطاء السيرفر للمستخدم
3. **إرشادات واضحة**: كل رسالة خطأ تحتوي على إرشاد للمستخدم
4. **معالجة الاتصال**: معالجة أخطاء الاتصال بشكل منفصل
5. **UX جيد**: مسح الأخطاء عند التعديل، وعرض حالة التحميل

---

## 📝 الخلاصة

-   **الرابط**: `POST /api/auth/register`
-   **النجاح**: 201 مع Token وبيانات المستخدم
-   **الفشل**: 422 مع رسالة خطأ واضحة
-   **الرسائل**: جميع الرسائل بالعربية وواضحة
-   **المعالجة**: معالجة جميع أنواع الأخطاء بشكل مناسب
