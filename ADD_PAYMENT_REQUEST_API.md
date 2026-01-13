# 💳 API: إضافة طلب دفع (دفعة) للمستخدم

## 📍 الرابط
```
POST /api/payment-requests
```

## 🔐 المصادقة
```
Authorization: Bearer {token}
```

## 📤 Request

### Headers
```
Content-Type: multipart/form-data
Authorization: Bearer {token}
```

### Parameters (Form Data)

| الحقل | النوع | مطلوب | الوصف | القيم المسموحة |
|------|------|-------|-------|----------------|
| `amount` | number | ✅ نعم | المبلغ المدفوع | رقم أكبر من 0.01 |
| `currency` | string | ❌ لا | العملة | `USD`, `SYP`, `TRY` |
| `period_months` | number | ❌ لا | عدد أشهر الاشتراك | 1-12 |
| `payment_method_id` | number | ❌ لا | معرف طريقة الدفع | ID موجود في payment_methods |
| `transaction_number` | string | ❌ لا | رقم المعاملة | نص (حد أقصى 100 حرف) |
| `receipt_file` | file | ❌ لا | صورة/ملف الإيصال | jpg, jpeg, png, pdf (حد أقصى 5MB) |
| `payment_date` | string | ❌ لا | تاريخ الدفع | صيغة: `Y-m-d` (مثال: 2024-01-15) |

### مثال Request (JavaScript/Fetch)
```javascript
const formData = new FormData();
formData.append('amount', 50000);
formData.append('currency', 'USD');
formData.append('period_months', 1);
formData.append('payment_method_id', 1);
formData.append('transaction_number', 'TXN123456');
formData.append('receipt_file', fileInput.files[0]); // File object
formData.append('payment_date', '2024-01-15');

fetch('https://your-api.com/api/payment-requests', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN_HERE'
  },
  body: formData
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Error:', error));
```

### مثال Request (cURL)
```bash
curl -X POST https://your-api.com/api/payment-requests \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -F "amount=50000" \
  -F "currency=USD" \
  -F "period_months=1" \
  -F "payment_method_id=1" \
  -F "transaction_number=TXN123456" \
  -F "receipt_file=@/path/to/receipt.jpg" \
  -F "payment_date=2024-01-15"
```

### مثال Request (Axios)
```javascript
import axios from 'axios';

const formData = new FormData();
formData.append('amount', 50000);
formData.append('currency', 'USD');
formData.append('period_months', 1);
formData.append('payment_method_id', 1);
formData.append('transaction_number', 'TXN123456');
formData.append('receipt_file', fileInput.files[0]);
formData.append('payment_date', '2024-01-15');

axios.post('https://your-api.com/api/payment-requests', formData, {
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN_HERE',
    'Content-Type': 'multipart/form-data'
  }
})
.then(response => {
  console.log('Success:', response.data);
})
.catch(error => {
  console.error('Error:', error.response.data);
});
```

---

## 📥 Response

### ✅ Success Response (201 Created)
```json
{
  "success": true,
  "message": "تم إرسال طلب الدفع بنجاح",
  "data": {
    "id": 1,
    "user_id": 5,
    "amount": 50000,
    "currency": "USD",
    "period_months": 1,
    "status": 0,
    "status_label": "قيد المراجعة",
    "payment_method_id": 1,
    "payment_method": {
      "id": 1,
      "name": "Zain Cash",
      "icon_url": "https://example.com/icon.png",
      "instructions": "Send payment to...",
      "is_active": true
    },
    "transaction_number": "TXN123456",
    "receipt_file_url": "https://example.com/storage/receipts/receipt_123.jpg",
    "payment_date": "2024-01-15",
    "created_at": "2024-01-15T10:30:00.000000Z",
    "updated_at": "2024-01-15T10:30:00.000000Z"
  }
}
```

### ❌ Error Responses

#### 401 Unauthorized
```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

#### 422 Validation Error
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "amount": [
      "المبلغ مطلوب"
    ],
    "receipt_file": [
      "نوع الملف غير مدعوم. المسموح: jpg, jpeg, png, pdf"
    ]
  }
}
```

#### 500 Server Error
```json
{
  "success": false,
  "message": "Internal server error"
}
```

---

## 📋 حالات الطلب (Status)

| القيمة | الحالة | الوصف |
|--------|--------|-------|
| `0` | قيد المراجعة | الطلب في انتظار المراجعة من المحاسب/المدير |
| `1` | مقبول | تم قبول الطلب وتم تجديد الاشتراك |
| `2` | مرفوض | تم رفض الطلب |

---

## 💡 ملاحظات مهمة

1. **رفع الملفات**: استخدم `multipart/form-data` عند إرسال ملف الإيصال
2. **حجم الملف**: الحد الأقصى لحجم ملف الإيصال هو 5 ميجابايت
3. **أنواع الملفات**: الملفات المدعومة: `jpg`, `jpeg`, `png`, `pdf`
4. **المبلغ**: يجب أن يكون المبلغ أكبر من 0.01
5. **العملة**: إذا لم يتم تحديد العملة، سيتم استخدام العملة الافتراضية
6. **الإشعارات**: سيتم إرسال إشعار تلقائي للمحاسبين والمديرين عند إنشاء الطلب
7. **المصادقة**: يجب أن يكون المستخدم مسجل دخول وصالح (is_active = true)

---

## 🔄 سير العمل (Workflow)

1. المستخدم يملأ بيانات الدفعة (المبلغ، طريقة الدفع، إلخ)
2. المستخدم يرفع صورة/ملف الإيصال (اختياري)
3. يتم إرسال الطلب إلى السيرفر
4. الطلب يُحفظ بحالة "قيد المراجعة" (status = 0)
5. المحاسب/المدير يراجع الطلب ويقبله أو يرفضه
6. المستخدم يتلقى إشعار عند تغيير حالة الطلب

---

## 🎯 مثال كامل (Complete Example)

```javascript
// React Native / Expo Example
import * as ImagePicker from 'expo-image-picker';
import axios from 'axios';

async function submitPaymentRequest() {
  try {
    // 1. اختيار صورة الإيصال
    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ImagePicker.MediaTypeOptions.Images,
      allowsEditing: true,
      quality: 0.8,
    });

    if (result.cancelled) {
      return;
    }

    // 2. إنشاء FormData
    const formData = new FormData();
    formData.append('amount', 50000);
    formData.append('currency', 'USD');
    formData.append('period_months', 1);
    formData.append('payment_method_id', 1);
    formData.append('transaction_number', 'TXN123456');
    
    // إضافة الصورة
    formData.append('receipt_file', {
      uri: result.uri,
      type: 'image/jpeg',
      name: 'receipt.jpg',
    });
    
    formData.append('payment_date', new Date().toISOString().split('T')[0]);

    // 3. إرسال الطلب
    const response = await axios.post(
      'https://your-api.com/api/payment-requests',
      formData,
      {
        headers: {
          'Authorization': `Bearer ${userToken}`,
          'Content-Type': 'multipart/form-data',
        },
      }
    );

    if (response.data.success) {
      console.log('تم إرسال الطلب بنجاح:', response.data.data);
      // عرض رسالة نجاح للمستخدم
      Alert.alert('نجح', 'تم إرسال طلب الدفع بنجاح');
    }
  } catch (error) {
    console.error('خطأ في إرسال الطلب:', error.response?.data || error.message);
    // عرض رسالة خطأ للمستخدم
    Alert.alert('خطأ', error.response?.data?.message || 'حدث خطأ أثناء إرسال الطلب');
  }
}
```

---

## ✅ Checklist للربط

- [ ] إضافة Header للمصادقة (`Authorization: Bearer {token}`)
- [ ] استخدام `multipart/form-data` عند رفع الملف
- [ ] التحقق من صحة البيانات قبل الإرسال
- [ ] معالجة الأخطاء بشكل صحيح
- [ ] عرض رسالة نجاح/خطأ للمستخدم
- [ ] تحديث قائمة طلبات الدفع بعد الإرسال الناجح
- [ ] التحقق من حجم الملف قبل الرفع (حد أقصى 5MB)
- [ ] التحقق من نوع الملف (jpg, jpeg, png, pdf فقط)

