# 👤 دليل ربط الملف الشخصي (Profile) بلوحة التحكم

## 🔐 المصادقة

جميع الـ Endpoints تتطلب:

```
Authorization: Bearer {token}
```

---

## 📋 نظرة عامة

نظام الملف الشخصي يتيح للمستخدم:
- عرض معلوماته الشخصية
- تحديث بعض المعلومات (محدودة)
- عرض معلومات الاشتراك

**⚠️ مهم**: المستخدم **لا يستطيع** تغيير:
- اسم المستخدم (`username`)
- الاسم (`firstname`)
- رقم الهاتف (`phone`)

هذه المعلومات محمية ولا يمكن تعديلها من قبل المستخدم.

---

## 🛠️ API Endpoints

### 1. عرض الملف الشخصي

**Method**: `GET`  
**URL**: `/api/user/profile`  
**Headers**:

```
Authorization: Bearer {token}
```

**الاستجابة**:

```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "username": "user001",
            "firstname": "أحمد",
            "phone": "07501234567",
            "email": "user@example.com",
            "is_active": true,
            "live_access": false,
            "role": 0,
            "language": "ar",
            "last_login_at": "2025-12-15T10:30:00Z",
            "created_at": "2025-01-01T00:00:00Z",
            "updated_at": "2025-12-15T10:30:00Z"
        },
        "subscription": {
            "id": 1,
            "radius_username": "user001",
            "expires_at": "2026-01-02T10:00:00Z",
            "balance": 8.0,
            "plan_name": "2M-PPP",
            "data_used": 79352827904,
            "total_MB": 75688.39,
            "last_synced_at": "2025-12-15T09:00:00Z",
            "is_active": true
        }
    }
}
```

---

### 2. تحديث الملف الشخصي

**Method**: `PUT`  
**URL**: `/api/user/profile`  
**Headers**:

```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body** (JSON):

```json
{
    "email": "newemail@example.com",
    "language": "en",
    "device_token": "fcm_token_here",
    "device_type": "android"
}
```

**الحقول المسموحة للتعديل**:

| الحقل         | النوع   | الوصف                                    | مثال                    |
| ------------- | ------- | ---------------------------------------- | ----------------------- |
| `email`       | string  | البريد الإلكتروني (اختياري)              | `"user@example.com"`    |
| `language`    | string  | اللغة: `ar` أو `en` (اختياري)            | `"ar"`                  |
| `device_token`| string  | Token الجهاز للإشعارات (اختياري)          | `"fcm_token_here"`      |
| `device_type` | string  | نوع الجهاز (اختياري)                      | `"android"`, `"ios"`    |
| `device_name` | string  | اسم الجهاز (اختياري)                      | `"Samsung Galaxy"`      |

**⚠️ الحقول المحمية (غير قابلة للتعديل)**:

- ❌ `username` - اسم المستخدم
- ❌ `firstname` - الاسم
- ❌ `phone` - رقم الهاتف

هذه الحقول **محظورة** ولا يمكن إرسالها في طلب التحديث.

**الاستجابة**:

```json
{
    "success": true,
    "message": "تم تحديث الملف الشخصي بنجاح",
    "data": {
        "id": 1,
        "username": "user001",
        "firstname": "أحمد",
        "phone": "07501234567",
        "email": "newemail@example.com",
        "is_active": true,
        "live_access": false,
        "role": 0,
        "language": "en",
        "last_login_at": "2025-12-15T10:30:00Z",
        "created_at": "2025-01-01T00:00:00Z",
        "updated_at": "2025-12-15T10:35:00Z"
    }
}
```

---

## 📊 هيكل البيانات

### كائن المستخدم (User Object):

| الحقل           | النوع    | الوصف                                    | قابل للتعديل |
| --------------- | -------- | ---------------------------------------- | ------------- |
| `id`            | integer  | معرف المستخدم                            | ❌            |
| `username`      | string   | اسم المستخدم                             | ❌ **محمي**   |
| `firstname`     | string   | الاسم                                    | ❌ **محمي**   |
| `phone`         | string   | رقم الهاتف                               | ❌ **محمي**   |
| `email`         | string   | البريد الإلكتروني                        | ✅            |
| `is_active`     | boolean  | حالة التفعيل                             | ❌            |
| `live_access`   | boolean  | صلاحية البث المباشر                      | ❌            |
| `role`          | integer  | الدور (0: مستخدم، 1: محاسب، 2: مدير)    | ❌            |
| `language`      | string   | اللغة المفضلة                            | ✅            |
| `last_login_at` | string   | آخر تسجيل دخول                           | ❌            |
| `created_at`    | string   | تاريخ الإنشاء                            | ❌            |
| `updated_at`    | string   | تاريخ التحديث                            | ❌            |

### كائن الاشتراك (Subscription Object):

| الحقل            | النوع   | الوصف                                    |
| ---------------- | ------- | ---------------------------------------- |
| `id`             | integer | معرف الاشتراك                            |
| `radius_username`| string  | اسم المستخدم في Radius                   |
| `expires_at`     | string  | تاريخ انتهاء الاشتراك                    |
| `balance`        | float   | الرصيد                                   |
| `plan_name`      | string  | اسم الخطة                                |
| `data_used`      | integer | البيانات المستخدمة (بالبايت)            |
| `total_MB`       | float   | إجمالي البيانات (بالميجابايت)            |
| `last_synced_at` | string  | آخر مزامنة                                |
| `is_active`      | boolean | حالة الاشتراك                            |

---

## 🔍 أمثلة على الاستجابات

### نجاح (200):

```json
{
    "success": true,
    "message": "تم تحديث الملف الشخصي بنجاح",
    "data": {...}
}
```

### خطأ (422 - Validation Error):

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "email": ["البريد الإلكتروني غير صحيح"],
        "language": ["اللغة يجب أن تكون ar أو en"]
    }
}
```

### خطأ (401 - Unauthorized):

```json
{
    "success": false,
    "message": "Unauthenticated."
}
```

---

## 📱 أمثلة في Frontend

### عرض الملف الشخصي:

```javascript
fetch("http://domain.com/api/user/profile", {
    method: "GET",
    headers: {
        Authorization: `Bearer ${token}`,
    },
})
    .then((response) => response.json())
    .then((data) => {
        if (data.success) {
            const user = data.data.user;
            const subscription = data.data.subscription;
            
            // عرض المعلومات
            console.log("اسم المستخدم:", user.username);
            console.log("الاسم:", user.firstname);
            console.log("البريد:", user.email);
            console.log("الاشتراك:", subscription);
        }
    });
```

### تحديث الملف الشخصي:

```javascript
// تحديث البريد الإلكتروني واللغة فقط
fetch("http://domain.com/api/user/profile", {
    method: "PUT",
    headers: {
        Authorization: `Bearer ${token}`,
        "Content-Type": "application/json",
    },
    body: JSON.stringify({
        email: "newemail@example.com",
        language: "en",
    }),
})
    .then((response) => response.json())
    .then((data) => {
        if (data.success) {
            alert("تم التحديث بنجاح");
            // تحديث البيانات في الواجهة
            refreshProfile();
        } else {
            alert(data.message);
        }
    });
```

### مثال على Form في React:

```jsx
function ProfileForm({ user, onUpdate }) {
    const [email, setEmail] = useState(user.email || "");
    const [language, setLanguage] = useState(user.language || "ar");

    const handleSubmit = async (e) => {
        e.preventDefault();

        const response = await fetch("http://domain.com/api/user/profile", {
            method: "PUT",
            headers: {
                Authorization: `Bearer ${token}`,
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                email,
                language,
            }),
        });

        const data = await response.json();
        if (data.success) {
            onUpdate(data.data);
            alert("تم التحديث بنجاح");
        }
    };

    return (
        <form onSubmit={handleSubmit}>
            {/* معلومات للعرض فقط (غير قابلة للتعديل) */}
            <div>
                <label>اسم المستخدم:</label>
                <input type="text" value={user.username} disabled readOnly />
            </div>

            <div>
                <label>الاسم:</label>
                <input type="text" value={user.firstname || ""} disabled readOnly />
            </div>

            <div>
                <label>رقم الهاتف:</label>
                <input type="text" value={user.phone || ""} disabled readOnly />
            </div>

            {/* معلومات قابلة للتعديل */}
            <div>
                <label>البريد الإلكتروني:</label>
                <input
                    type="email"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                />
            </div>

            <div>
                <label>اللغة:</label>
                <select value={language} onChange={(e) => setLanguage(e.target.value)}>
                    <option value="ar">العربية</option>
                    <option value="en">English</option>
                </select>
            </div>

            <button type="submit">حفظ التغييرات</button>
        </form>
    );
}
```

---

## ✅ نصائح للربط

### 1. الحقول المحمية:

- **لا ترسل** `username`, `firstname`, `phone` في طلب التحديث
- هذه الحقول **للعرض فقط** في الواجهة
- استخدم `disabled` أو `readOnly` في حقول النموذج

### 2. الحقول القابلة للتعديل:

- `email` - البريد الإلكتروني
- `language` - اللغة المفضلة
- `device_token` - Token الجهاز (للإشعارات)

### 3. عرض المعلومات:

- استخدم `GET /api/user/profile` لعرض جميع المعلومات
- يعرض معلومات المستخدم + معلومات الاشتراك

### 4. التحديث:

- استخدم `PUT /api/user/profile` لتحديث الحقول المسموحة فقط
- جميع الحقول اختيارية في التحديث

---

## 🎯 حالات الاستخدام

### 1. صفحة الملف الشخصي:

- عرض جميع المعلومات (قابلة للتعديل وغير قابلة)
- تحديث البريد الإلكتروني واللغة فقط

### 2. إعدادات التطبيق:

- تغيير اللغة المفضلة
- تحديث Token الجهاز للإشعارات

### 3. معلومات الاشتراك:

- عرض معلومات الاشتراك من `subscription` object
- استخدام `sync-subscription` لتحديث المعلومات

---

## 📝 ملاحظات مهمة

### 1. الحقول المحمية:

- `username`, `firstname`, `phone` **محظورة** من التعديل
- إذا أرسلتها في الطلب، سيتم تجاهلها
- هذه المعلومات تُحدّث فقط من قبل المدير

### 2. معلومات الاشتراك:

- معلومات الاشتراك تأتي من `subscription` object
- يمكن تحديثها باستخدام `POST /api/user/sync-subscription`
- أو قراءتها مباشرة من Radius باستخدام `GET /api/user/subscription-from-radius`

### 3. Device Token:

- عند إرسال `device_token`، يتم حفظه تلقائياً
- يُستخدم لإرسال الإشعارات الفورية
- يمكن إرسال `device_type` و `device_name` أيضاً

---

## 🔄 مثال كامل

```javascript
// 1. جلب الملف الشخصي
async function loadProfile() {
    const response = await fetch("http://domain.com/api/user/profile", {
        headers: {
            Authorization: `Bearer ${token}`,
        },
    });
    const data = await response.json();
    return data.data;
}

// 2. تحديث الملف الشخصي
async function updateProfile(email, language) {
    const response = await fetch("http://domain.com/api/user/profile", {
        method: "PUT",
        headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            email: email,
            language: language,
        }),
    });
    const data = await response.json();
    return data;
}

// 3. استخدام
const profile = await loadProfile();
console.log("اسم المستخدم:", profile.user.username); // للعرض فقط
console.log("البريد:", profile.user.email); // قابل للتعديل

// تحديث البريد فقط
await updateProfile("newemail@example.com", "ar");
```

---

## 🚫 ما لا يجب فعله

### ❌ لا ترسل:

```json
{
    "username": "new_username",  // ❌ محظور
    "firstname": "اسم جديد",     // ❌ محظور
    "phone": "07509999999"       // ❌ محظور
}
```

### ✅ ما يمكن إرساله:

```json
{
    "email": "newemail@example.com",  // ✅ مسموح
    "language": "en"                  // ✅ مسموح
}
```

---

## 📋 ملخص سريع

| العملية | Method | URL                    | الحقول القابلة للتعديل        |
| ------- | ------ | ---------------------- | ----------------------------- |
| عرض     | GET    | `/api/user/profile`    | - (للعرض فقط)                  |
| تحديث   | PUT    | `/api/user/profile`    | `email`, `language`, `device_token` |

**الحقول المحمية**: `username`, `firstname`, `phone`

