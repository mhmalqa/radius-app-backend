# دليل ربط نظام العملات (Currency API Guide)

## 1. عرض قائمة العملات (للتطبيق والداشبورد)

يستخدم هذا الرابط لجلب جميع العملات المتاحة مع أسعار الصرف الحالية.

-   **الرابط:** `GET /api/currencies`
-   **الصلاحية:** متاح لجميع المستخدمين المسجلين (Token Required).

### نموذج الاستجابة (Success Response)

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "code": "USD",
            "name": "الدولار الأمريكي",
            "symbol": "$",
            "exchange_rate": "1.00",
            "is_default": true,
            "is_active": true,
            "last_updated_at": null,
            "updated_by": null,
            "created_at": "2024-01-20T12:00:00.000000Z",
            "updated_at": "2024-01-20T12:00:00.000000Z"
        },
        {
            "id": 2,
            "code": "SYP",
            "name": "الليرة السورية",
            "symbol": "ل.س",
            "exchange_rate": "15000.00",
            "is_default": false,
            "is_active": true,
            "last_updated_at": "2024-01-20T12:05:00.000000Z",
            "updated_by": 1,
            "created_at": "2024-01-20T12:00:00.000000Z",
            "updated_at": "2024-01-20T12:05:00.000000Z"
        }
    ]
}
```

---

## 2. تحديث العملة (للمدراء والمحاسبين)

يستخدم هذا الرابط لتعديل سعر صرف عملة محددة أو تغيير حالتها (تفعيل/إلغاء تفعيل).

-   **الرابط:** `PUT /api/admin/currencies/{id}`
-   **مثال:** `PUT /api/admin/currencies/2`
-   **الصلاحية:** Admin أو Accountant فقط.

### البيانات المرسلة (Request Body)

يمكن إرسال أحد الحقلين أو كلاهما:

```json
{
    "exchange_rate": 15200.5,
    "is_active": true
}
```

-   `exchange_rate`: (اختياري) رقم عشري، يمثل سعر الصرف الجديد.
-   `is_active`: (اختياري) true أو false، لتحديد ما إذا كانت العملة ظاهرة في التطبيق أم لا.
    -   **ملاحظة:** لا يمكن إلغاء تفعيل العملة الافتراضية (USD).

### نموذج الاستجابة (Success Response)

```json
{
    "success": true,
    "message": "تم تحديث العملة بنجاح",
    "data": {
        "id": 2,
        "code": "SYP",
        "name": "الليرة السورية",
        "symbol": "ل.س",
        "exchange_rate": "15200.50",
        "is_default": false,
        "is_active": true,
        "last_updated_at": "2024-01-20T12:10:00.000000Z",
        "updated_by": 1,
        "created_at": "2024-01-20T12:00:00.000000Z",
        "updated_at": "2024-01-20T12:10:00.000000Z"
    }
}
```

---

## 3. سجل تغييرات الأسعار (History Log)

لعرض أرشيف التعديلات التي تمت على عملة معينة.

-   **الرابط:** `GET /api/admin/currencies/{id}/history`
-   **مثال:** `GET /api/admin/currencies/2/history`
-   **الصلاحية:** Admin أو Accountant فقط.

### نموذج الاستجابة (Success Response)

```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "currency_id": 2,
                "old_rate": "15000.00",
                "new_rate": "15200.50",
                "updated_by": 1,
                "created_at": "2024-01-20T12:10:00.000000Z",
                "updater": {
                    "id": 1,
                    "username": "admin",
                    "firstname": "Samer"
                }
            }
        ],
        "total": 1
    }
}
```

---

## ملاحظات هامة للمطور (Frontend Notes)

1. **العملة الأساسية:** الدولار الأمريكي (USD) هو العملة الأساسية (`is_default: true`) وسعر صرفه دائماً 1.00. لا يجب السماح بتعديل سعره.
2. **آلية التحويل في التطبيق:**
    - المعادلة: `المبلغ بالعملة المحلية = المبلغ بالدولار * سعر الصرف`
    - مثال: اشتراك قيمته 10$ والدفع بالليرة السورية (سعر الصرف 15000).
    - المبلغ المطلوب = 10 \* 15000 = 150,000 ل.س.
3. **التخزين المؤقت:** عند تحديث السعر، يتم تحديث الكاش تلقائياً، لذا ستظهر الأسعار الجديدة فوراً في التطبيق.
