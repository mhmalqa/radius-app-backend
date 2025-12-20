# 🔧 دليل تقديم طلب صيانة

## 📋 نظرة عامة

يمكن للمستخدمين تقديم طلبات صيانة من خلال التطبيق. النظام يجلب بيانات المشترك من Radius تلقائياً ويحفظها مع الطلب.

---

## 🚀 طريقة تقديم طلب صيانة

### **Endpoint:**
```
POST /api/maintenance-requests
```

### **المتطلبات:**
- ✅ تسجيل الدخول (Bearer Token)
- ✅ المستخدم يجب أن يكون نشطاً

### **Request Headers:**
```http
Authorization: Bearer {user_token}
Content-Type: application/json
```

### **Request Body:**
```json
{
  "address": "بغداد - الكرادة - شارع الكرادة الداخلية - عمارة رقم 15",
  "description": "مشكلة في الاتصال بالإنترنت، لا يعمل الإنترنت منذ يومين"
}
```

### **الحقول:**

| الحقل | النوع | مطلوب | الوصف | القيود |
|------|------|-------|------|--------|
| `address` | string | ✅ نعم | عنوان الصيانة | 10-500 حرف |
| `description` | string | ❌ لا | وصف المشكلة | حتى 1000 حرف |

---

## 📝 مثال كامل

### **JavaScript/TypeScript:**
```typescript
async function createMaintenanceRequest(address: string, description?: string) {
  const response = await fetch('/api/maintenance-requests', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
      address: address,
      description: description || ''
    })
  });
  
  return await response.json();
}

// استخدام
const result = await createMaintenanceRequest(
  'بغداد - الكرادة - شارع الكرادة الداخلية - عمارة رقم 15',
  'مشكلة في الاتصال بالإنترنت'
);
```

### **React Example:**
```jsx
function MaintenanceRequestForm() {
  const [address, setAddress] = useState('');
  const [description, setDescription] = useState('');
  const [loading, setLoading] = useState(false);
  
  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    
    try {
      const response = await fetch('/api/maintenance-requests', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({
          address: address,
          description: description
        })
      });
      
      const data = await response.json();
      
      if (data.success) {
        alert('تم إرسال طلب الصيانة بنجاح');
        // إعادة تعيين النموذج
        setAddress('');
        setDescription('');
      } else {
        alert('حدث خطأ: ' + data.message);
      }
    } catch (error) {
      alert('حدث خطأ في الاتصال');
    } finally {
      setLoading(false);
    }
  };
  
  return (
    <form onSubmit={handleSubmit}>
      <div>
        <label>العنوان *</label>
        <input
          type="text"
          value={address}
          onChange={(e) => setAddress(e.target.value)}
          required
          minLength={10}
          maxLength={500}
          placeholder="أدخل عنوان الصيانة بالتفصيل"
        />
      </div>
      
      <div>
        <label>وصف المشكلة</label>
        <textarea
          value={description}
          onChange={(e) => setDescription(e.target.value)}
          maxLength={1000}
          placeholder="وصف المشكلة (اختياري)"
          rows={5}
        />
        <small>{description.length} / 1000</small>
      </div>
      
      <button type="submit" disabled={loading}>
        {loading ? 'جاري الإرسال...' : 'إرسال طلب الصيانة'}
      </button>
    </form>
  );
}
```

---

## ✅ Response الناجح

### **Status Code:** `201 Created`

```json
{
  "success": true,
  "message": "تم إرسال طلب الصيانة بنجاح",
  "data": {
    "id": 1,
    "user": {
      "id": 1,
      "name": "أحمد محمد",
      "username": "ahmed123"
    },
    "address": "بغداد - الكرادة - شارع الكرادة الداخلية - عمارة رقم 15",
    "description": "مشكلة في الاتصال بالإنترنت",
    "subscription_data": {
      "expiration_at": "2026-01-02 10:00:00",
      "balance": 8.0,
      "plan_name": "2M-PPP",
      "is_active_radius": true,
      "data_usage": {
        "upload": 1024,
        "download": 2048
      }
    },
    "status": "pending",
    "status_label": "قيد الانتظار",
    "assigned_to": null,
    "notes": null,
    "completed_at": null,
    "created_at": "2025-12-14T15:30:00Z",
    "updated_at": "2025-12-14T15:30:00Z"
  }
}
```

---

## ❌ الأخطاء المحتملة

### **1. العنوان قصير جداً**
```json
{
  "success": false,
  "message": "العنوان يجب أن يكون على الأقل 10 أحرف",
  "errors": {
    "address": ["العنوان يجب أن يكون على الأقل 10 أحرف"]
  }
}
```

### **2. العنوان طويل جداً**
```json
{
  "success": false,
  "message": "العنوان يجب أن يكون على الأكثر 500 حرف",
  "errors": {
    "address": ["العنوان يجب أن يكون على الأكثر 500 حرف"]
  }
}
```

### **3. العنوان مفقود**
```json
{
  "success": false,
  "message": "العنوان مطلوب",
  "errors": {
    "address": ["العنوان مطلوب"]
  }
}
```

### **4. فشل جلب بيانات Radius**
```json
{
  "success": false,
  "message": "فشل في جلب بيانات الاشتراك من الراديوس. يرجى المحاولة مرة أخرى"
}
```

### **5. غير مصرح (غير مسجل دخول)**
```json
{
  "success": false,
  "message": "غير مصرح"
}
```

---

## 🔄 ما يحدث تلقائياً

عند تقديم طلب صيانة:

1. ✅ **جلب بيانات المشترك من Radius** - يتم جلب بيانات الاشتراك تلقائياً
2. ✅ **حفظ الطلب** - يتم حفظ الطلب بحالة `pending` (قيد الانتظار)
3. ✅ **إرسال إشعار** - يتم إرسال إشعار للمحاسبين والمديرين
4. ✅ **ربط البيانات** - يتم ربط بيانات Radius مع الطلب

---

## 📊 حالات الطلب

| الحالة | الوصف | Label |
|--------|-------|-------|
| `pending` | قيد الانتظار | قيد الانتظار |
| `submitted` | تم التقديم | تم التقديم |
| `in_progress` | قيد التنفيذ | قيد التنفيذ |
| `completed` | مكتمل | مكتمل |
| `cancelled` | ملغي | ملغي |

---

## 📱 عرض طلبات الصيانة

### **عرض جميع طلبات المستخدم:**
```http
GET /api/maintenance-requests
Authorization: Bearer {token}
```

### **عرض طلب محدد:**
```http
GET /api/maintenance-requests/{id}
Authorization: Bearer {token}
```

### **فلترة حسب الحالة:**
```http
GET /api/maintenance-requests?status=pending
Authorization: Bearer {token}
```

---

## 🎯 نصائح للاستخدام

1. **العنوان التفصيلي:** اكتب العنوان بشكل واضح ومفصل (على الأقل 10 أحرف)
2. **وصف المشكلة:** اكتب وصفاً واضحاً للمشكلة لتسهيل عملية الصيانة
3. **التحقق من البيانات:** تأكد من أن بيانات الاشتراك صحيحة قبل الإرسال
4. **متابعة الطلب:** يمكنك متابعة حالة الطلب من قائمة طلبات الصيانة

---

## 📌 ملاحظات مهمة

- ✅ يتم جلب بيانات المشترك من Radius تلقائياً
- ✅ إذا فشل جلب البيانات، لن يتم إنشاء الطلب
- ✅ يتم إرسال إشعار للمحاسبين والمديرين تلقائياً
- ✅ يمكن للمستخدم متابعة حالة طلبه من التطبيق
- ✅ يمكن للمحاسب/المدير تحديث حالة الطلب وتعيين مسؤول

---

**تاريخ التحديث:** 2025-12-14

