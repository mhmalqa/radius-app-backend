# ⚙️ دليل احترافي: ربط إعدادات التطبيق في الواجهة الأمامية

## 📋 نظرة عامة

إعدادات التطبيق هي قيم قابلة للتعديل من قبل المدير وتستخدم في التطبيق لعرض معلومات مثل:
- روابط التواصل الاجتماعي (فيسبوك، واتساب، تيليجرام، إلخ)
- معلومات عامة عن التطبيق (اسم التطبيق، رقم الهاتف، البريد الإلكتروني)
- إعدادات أخرى قابلة للتخصيص

---

## 🎯 المفهوم الأساسي

### ما هي إعدادات التطبيق؟

إعدادات التطبيق هي **مفاتيح وقيم** (Key-Value pairs) مخزنة في قاعدة البيانات يمكن:
- **قراءتها** من قبل جميع المستخدمين (الإعدادات النشطة فقط)
- **تعديلها** من قبل المدير فقط
- **فلترتها** حسب النوع (`social_link` أو `general_setting`)

### البنية

```
┌─────────────────────────────────────┐
│         App Settings                 │
├─────────────────────────────────────┤
│ key: "whatsapp_group"               │
│ value: "https://chat.whatsapp.com/" │
│ type: "social_link"                 │
│ label: "مجموعة الواتساب"            │
│ is_active: true                     │
└─────────────────────────────────────┘
```

---

## 🔗 نقاط النهاية (API Endpoints)

### 1. جلب جميع الإعدادات

```
GET /api/app-settings
```

**لا يتطلب تسجيل دخول** - متاح للجميع

**المعاملات الاختيارية**:
- `type`: `social_link` أو `general_setting` - لفلترة حسب النوع

**مثال**:
```http
GET /api/app-settings?type=social_link
```

---

### 2. جلب إعداد محدد بالمفتاح

```
GET /api/app-settings/key/{key}
```

**لا يتطلب تسجيل دخول** - متاح للجميع

**مثال**:
```http
GET /api/app-settings/key/whatsapp_group
```

---

## 📥 تنسيق الاستجابة

### استجابة جميع الإعدادات

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "key": "whatsapp_group",
            "value": "https://chat.whatsapp.com/ABC123",
            "type": "social_link",
            "type_label": "رابط تواصل",
            "label": "مجموعة الواتساب",
            "label_en": "WhatsApp Group",
            "description": "رابط مجموعة الواتساب الرسمية",
            "is_active": true,
            "sort_order": 1,
            "created_at": "2025-12-15T10:00:00Z",
            "updated_at": "2025-12-15T10:00:00Z"
        },
        {
            "id": 2,
            "key": "facebook_page",
            "value": "https://facebook.com/ourpage",
            "type": "social_link",
            "type_label": "رابط تواصل",
            "label": "صفحة الفيسبوك",
            "label_en": "Facebook Page",
            "description": "رابط صفحة الفيسبوك الرسمية",
            "is_active": true,
            "sort_order": 2,
            "created_at": "2025-12-15T10:00:00Z",
            "updated_at": "2025-12-15T10:00:00Z"
        },
        {
            "id": 3,
            "key": "support_phone",
            "value": "07501234567",
            "type": "general_setting",
            "type_label": "إعداد عام",
            "label": "رقم الدعم الفني",
            "label_en": "Support Phone",
            "description": "رقم هاتف الدعم الفني",
            "is_active": true,
            "sort_order": 1,
            "created_at": "2025-12-15T10:00:00Z",
            "updated_at": "2025-12-15T10:00:00Z"
        }
    ]
}
```

### استجابة إعداد واحد

```json
{
    "success": true,
    "data": {
        "id": 1,
        "key": "whatsapp_group",
        "value": "https://chat.whatsapp.com/ABC123",
        "type": "social_link",
        "type_label": "رابط تواصل",
        "label": "مجموعة الواتساب",
        "label_en": "WhatsApp Group",
        "description": "رابط مجموعة الواتساب الرسمية",
        "is_active": true,
        "sort_order": 1,
        "created_at": "2025-12-15T10:00:00Z",
        "updated_at": "2025-12-15T10:00:00Z"
    }
}
```

---

## 📊 شرح الحقول

| الحقل         | النوع   | الوصف                                    |
| ------------- | ------- | ----------------------------------------- |
| `id`          | number  | رقم الإعداد                               |
| `key`         | string  | المفتاح الفريد للإعداد (مثل: `whatsapp_group`) |
| `value`       | string  | قيمة الإعداد (الرابط أو النص)             |
| `type`        | string  | نوع الإعداد: `social_link` أو `general_setting` |
| `type_label`  | string  | تسمية النوع بالعربية                      |
| `label`       | string  | التسمية بالعربية                          |
| `label_en`    | string  | التسمية بالإنجليزية                       |
| `description` | string  | وصف الإعداد                                |
| `is_active`   | boolean | حالة التفعيل (النشطة فقط تُعاد للمستخدمين) |
| `sort_order`  | number  | ترتيب العرض (الأقل = الأول)                |

---

## 💻 التكامل الاحترافي

### 1. Service Class (مُوصى به)

إنشاء Service class لإدارة إعدادات التطبيق:

```javascript
// services/AppSettingsService.js

class AppSettingsService {
    constructor() {
        this.cache = new Map();
        this.cacheExpiry = 5 * 60 * 1000; // 5 دقائق
    }

    /**
     * جلب جميع الإعدادات
     */
    async getAllSettings(type = null) {
        const cacheKey = `all_${type || 'all'}`;
        
        // التحقق من التخزين المؤقت
        if (this.cache.has(cacheKey)) {
            const cached = this.cache.get(cacheKey);
            if (Date.now() - cached.timestamp < this.cacheExpiry) {
                return cached.data;
            }
        }

        try {
            const url = type 
                ? `/api/app-settings?type=${type}`
                : '/api/app-settings';
            
            const response = await fetch(url);
            const data = await response.json();

            if (data.success) {
                // حفظ في التخزين المؤقت
                this.cache.set(cacheKey, {
                    data: data.data,
                    timestamp: Date.now(),
                });

                return data.data;
            }

            throw new Error(data.message || 'فشل في جلب الإعدادات');
        } catch (error) {
            console.error('خطأ في جلب الإعدادات:', error);
            throw error;
        }
    }

    /**
     * جلب إعداد محدد بالمفتاح
     */
    async getSettingByKey(key) {
        const cacheKey = `key_${key}`;
        
        // التحقق من التخزين المؤقت
        if (this.cache.has(cacheKey)) {
            const cached = this.cache.get(cacheKey);
            if (Date.now() - cached.timestamp < this.cacheExpiry) {
                return cached.data;
            }
        }

        try {
            const response = await fetch(`/api/app-settings/key/${key}`);
            const data = await response.json();

            if (data.success) {
                // حفظ في التخزين المؤقت
                this.cache.set(cacheKey, {
                    data: data.data,
                    timestamp: Date.now(),
                });

                return data.data;
            }

            if (response.status === 404) {
                return null; // الإعداد غير موجود
            }

            throw new Error(data.message || 'فشل في جلب الإعداد');
        } catch (error) {
            console.error(`خطأ في جلب الإعداد ${key}:`, error);
            return null;
        }
    }

    /**
     * جلب قيمة إعداد مباشرة (بدون كائن كامل)
     */
    async getSettingValue(key, defaultValue = null) {
        const setting = await this.getSettingByKey(key);
        return setting?.value ?? defaultValue;
    }

    /**
     * جلب روابط التواصل الاجتماعي فقط
     */
    async getSocialLinks() {
        return await this.getAllSettings('social_link');
    }

    /**
     * جلب الإعدادات العامة فقط
     */
    async getGeneralSettings() {
        return await this.getAllSettings('general_setting');
    }

    /**
     * تحويل الإعدادات إلى كائن (Key-Value)
     */
    async getSettingsAsObject(type = null) {
        const settings = await this.getAllSettings(type);
        const obj = {};
        
        settings.forEach(setting => {
            obj[setting.key] = setting.value;
        });

        return obj;
    }

    /**
     * مسح التخزين المؤقت
     */
    clearCache() {
        this.cache.clear();
    }
}

// Export singleton instance
export default new AppSettingsService();
```

---

### 2. React Hook (Custom Hook)

```javascript
// hooks/useAppSettings.js
import { useState, useEffect } from 'react';
import AppSettingsService from '../services/AppSettingsService';

/**
 * Hook لجلب جميع الإعدادات
 */
export function useAppSettings(type = null) {
    const [settings, setSettings] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        let isMounted = true;

        async function fetchSettings() {
            try {
                setLoading(true);
                setError(null);
                
                const data = await AppSettingsService.getAllSettings(type);
                
                if (isMounted) {
                    setSettings(data);
                }
            } catch (err) {
                if (isMounted) {
                    setError(err.message);
                }
            } finally {
                if (isMounted) {
                    setLoading(false);
                }
            }
        }

        fetchSettings();

        return () => {
            isMounted = false;
        };
    }, [type]);

    return { settings, loading, error };
}

/**
 * Hook لجلب إعداد محدد
 */
export function useAppSetting(key, defaultValue = null) {
    const [setting, setSetting] = useState(null);
    const [value, setValue] = useState(defaultValue);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        let isMounted = true;

        async function fetchSetting() {
            try {
                setLoading(true);
                setError(null);
                
                const data = await AppSettingsService.getSettingByKey(key);
                
                if (isMounted) {
                    setSetting(data);
                    setValue(data?.value ?? defaultValue);
                }
            } catch (err) {
                if (isMounted) {
                    setError(err.message);
                }
            } finally {
                if (isMounted) {
                    setLoading(false);
                }
            }
        }

        if (key) {
            fetchSetting();
        }

        return () => {
            isMounted = false;
        };
    }, [key, defaultValue]);

    return { setting, value, loading, error };
}

/**
 * Hook لجلب روابط التواصل الاجتماعي
 */
export function useSocialLinks() {
    return useAppSettings('social_link');
}

/**
 * Hook لجلب الإعدادات العامة
 */
export function useGeneralSettings() {
    return useAppSettings('general_setting');
}
```

---

### 3. React Context (للمشاركة بين المكونات)

```javascript
// context/AppSettingsContext.jsx
import React, { createContext, useContext, useState, useEffect } from 'react';
import AppSettingsService from '../services/AppSettingsService';

const AppSettingsContext = createContext();

export function AppSettingsProvider({ children }) {
    const [settings, setSettings] = useState([]);
    const [settingsMap, setSettingsMap] = useState({});
    const [socialLinks, setSocialLinks] = useState([]);
    const [generalSettings, setGeneralSettings] = useState({});
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        loadAllSettings();
    }, []);

    async function loadAllSettings() {
        try {
            setLoading(true);
            setError(null);

            // جلب جميع الإعدادات
            const allSettings = await AppSettingsService.getAllSettings();
            setSettings(allSettings);

            // تحويل إلى Map للوصول السريع
            const map = {};
            allSettings.forEach(setting => {
                map[setting.key] = setting.value;
            });
            setSettingsMap(map);

            // جلب روابط التواصل
            const links = await AppSettingsService.getSocialLinks();
            setSocialLinks(links);

            // جلب الإعدادات العامة
            const general = await AppSettingsService.getGeneralSettings();
            const generalMap = {};
            general.forEach(setting => {
                generalMap[setting.key] = setting.value;
            });
            setGeneralSettings(generalMap);

        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    }

    /**
     * الحصول على قيمة إعداد بالمفتاح
     */
    function getSetting(key, defaultValue = null) {
        return settingsMap[key] ?? defaultValue;
    }

    /**
     * إعادة تحميل الإعدادات
     */
    async function refresh() {
        AppSettingsService.clearCache();
        await loadAllSettings();
    }

    const value = {
        settings,
        settingsMap,
        socialLinks,
        generalSettings,
        loading,
        error,
        getSetting,
        refresh,
    };

    return (
        <AppSettingsContext.Provider value={value}>
            {children}
        </AppSettingsContext.Provider>
    );
}

/**
 * Hook لاستخدام Context
 */
export function useAppSettingsContext() {
    const context = useContext(AppSettingsContext);
    
    if (!context) {
        throw new Error('useAppSettingsContext must be used within AppSettingsProvider');
    }
    
    return context;
}
```

**الاستخدام في App.jsx**:

```jsx
import { AppSettingsProvider } from './context/AppSettingsContext';

function App() {
    return (
        <AppSettingsProvider>
            {/* باقي التطبيق */}
        </AppSettingsProvider>
    );
}
```

---

### 4. أمثلة الاستخدام

#### مثال 1: عرض روابط التواصل الاجتماعي

```jsx
import { useSocialLinks } from '../hooks/useAppSettings';

function SocialLinks() {
    const { settings, loading, error } = useSocialLinks();

    if (loading) return <div>جاري التحميل...</div>;
    if (error) return <div>خطأ: {error}</div>;

    return (
        <div className="social-links">
            <h3>تواصل معنا</h3>
            <div className="links">
                {settings.map(link => (
                    <a
                        key={link.id}
                        href={link.value}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="social-link"
                    >
                        <span>{link.label}</span>
                    </a>
                ))}
            </div>
        </div>
    );
}
```

#### مثال 2: استخدام Context

```jsx
import { useAppSettingsContext } from '../context/AppSettingsContext';

function ContactInfo() {
    const { getSetting, loading } = useAppSettingsContext();

    if (loading) return <div>جاري التحميل...</div>;

    const supportPhone = getSetting('support_phone', 'غير متاح');
    const supportEmail = getSetting('support_email', 'غير متاح');

    return (
        <div className="contact-info">
            <h3>معلومات التواصل</h3>
            <p>الهاتف: {supportPhone}</p>
            <p>البريد: {supportEmail}</p>
        </div>
    );
}
```

#### مثال 3: استخدام Hook لإعداد واحد

```jsx
import { useAppSetting } from '../hooks/useAppSettings';

function WhatsAppButton() {
    const { value: whatsappLink, loading } = useAppSetting('whatsapp_group');

    if (loading) return null;
    if (!whatsappLink) return null;

    return (
        <a
            href={whatsappLink}
            target="_blank"
            rel="noopener noreferrer"
            className="whatsapp-button"
        >
            انضم إلى مجموعة الواتساب
        </a>
    );
}
```

---

### 5. Vue.js Integration

```javascript
// composables/useAppSettings.js
import { ref, onMounted } from 'vue';
import AppSettingsService from '../services/AppSettingsService';

export function useAppSettings(type = null) {
    const settings = ref([]);
    const loading = ref(true);
    const error = ref(null);

    async function loadSettings() {
        try {
            loading.value = true;
            error.value = null;
            
            const data = await AppSettingsService.getAllSettings(type);
            settings.value = data;
        } catch (err) {
            error.value = err.message;
        } finally {
            loading.value = false;
        }
    }

    onMounted(() => {
        loadSettings();
    });

    return {
        settings,
        loading,
        error,
        refresh: loadSettings,
    };
}

export function useAppSetting(key, defaultValue = null) {
    const setting = ref(null);
    const value = ref(defaultValue);
    const loading = ref(true);
    const error = ref(null);

    async function loadSetting() {
        try {
            loading.value = true;
            error.value = null;
            
            const data = await AppSettingsService.getSettingByKey(key);
            setting.value = data;
            value.value = data?.value ?? defaultValue;
        } catch (err) {
            error.value = err.message;
        } finally {
            loading.value = false;
        }
    }

    onMounted(() => {
        if (key) {
            loadSetting();
        }
    });

    return {
        setting,
        value,
        loading,
        error,
        refresh: loadSetting,
    };
}
```

**الاستخدام في Vue Component**:

```vue
<template>
    <div class="social-links">
        <h3>تواصل معنا</h3>
        <div v-if="loading">جاري التحميل...</div>
        <div v-else-if="error">خطأ: {{ error }}</div>
        <div v-else class="links">
            <a
                v-for="link in settings"
                :key="link.id"
                :href="link.value"
                target="_blank"
                rel="noopener noreferrer"
            >
                {{ link.label }}
            </a>
        </div>
    </div>
</template>

<script setup>
import { useAppSettings } from '../composables/useAppSettings';

const { settings, loading, error } = useAppSettings('social_link');
</script>
```

---

## 🎨 أفضل الممارسات

### 1. التخزين المؤقت (Caching)

- استخدم التخزين المؤقت لتقليل عدد الطلبات
- حدد مدة صلاحية للتخزين المؤقت (5-10 دقائق)
- امسح التخزين المؤقت عند تحديث الإعدادات

### 2. معالجة الأخطاء

```javascript
try {
    const setting = await AppSettingsService.getSettingByKey('whatsapp_group');
    if (!setting) {
        // الإعداد غير موجود - استخدم قيمة افتراضية
        console.warn('إعداد whatsapp_group غير موجود');
    }
} catch (error) {
    // معالجة الخطأ
    console.error('خطأ في جلب الإعداد:', error);
}
```

### 3. القيم الافتراضية

```javascript
// دائماً استخدم قيم افتراضية
const phone = await AppSettingsService.getSettingValue('support_phone', 'غير متاح');
const email = await AppSettingsService.getSettingValue('support_email', 'info@example.com');
```

### 4. TypeScript Types (اختياري)

```typescript
// types/AppSettings.ts
export interface AppSetting {
    id: number;
    key: string;
    value: string;
    type: 'social_link' | 'general_setting';
    type_label: string;
    label: string;
    label_en: string;
    description: string | null;
    is_active: boolean;
    sort_order: number;
    created_at: string;
    updated_at: string;
}

export interface AppSettingsResponse {
    success: boolean;
    data: AppSetting[];
}
```

---

## 📝 أمثلة حالات الاستخدام

### 1. Footer مع روابط التواصل

```jsx
import { useSocialLinks } from '../hooks/useAppSettings';

function Footer() {
    const { settings: socialLinks } = useSocialLinks();

    return (
        <footer>
            <div className="social-links">
                {socialLinks.map(link => (
                    <a
                        key={link.id}
                        href={link.value}
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        {link.label}
                    </a>
                ))}
            </div>
        </footer>
    );
}
```

### 2. صفحة الاتصال

```jsx
import { useAppSettingsContext } from '../context/AppSettingsContext';

function ContactPage() {
    const { getSetting, loading } = useAppSettingsContext();

    if (loading) return <div>جاري التحميل...</div>;

    return (
        <div className="contact-page">
            <h1>اتصل بنا</h1>
            <div className="contact-info">
                <p>
                    <strong>الهاتف:</strong> {getSetting('support_phone', 'غير متاح')}
                </p>
                <p>
                    <strong>البريد:</strong> {getSetting('support_email', 'غير متاح')}
                </p>
                <p>
                    <strong>العنوان:</strong> {getSetting('company_address', 'غير متاح')}
                </p>
            </div>
        </div>
    );
}
```

### 3. زر الواتساب العائم

```jsx
import { useAppSetting } from '../hooks/useAppSettings';

function FloatingWhatsAppButton() {
    const { value: whatsappLink } = useAppSetting('whatsapp_group');

    if (!whatsappLink) return null;

    return (
        <a
            href={whatsappLink}
            target="_blank"
            rel="noopener noreferrer"
            className="floating-whatsapp"
        >
            💬 واتساب
        </a>
    );
}
```

---

## ⚠️ ملاحظات مهمة

1. **لا يحتاج تسجيل دخول**: جميع endpoints متاحة للجميع
2. **النشطة فقط**: يتم إرجاع فقط الإعدادات النشطة (`is_active = true`)
3. **التخزين المؤقت**: يُنصح باستخدام تخزين مؤقت لتقليل الطلبات
4. **القيم الافتراضية**: استخدم قيم افتراضية عند عدم وجود الإعداد
5. **معالجة الأخطاء**: تعامل مع الأخطاء بشكل مناسب

---

## 📝 الخلاصة

- **الرابط الأساسي**: `GET /api/app-settings`
- **إعداد واحد**: `GET /api/app-settings/key/{key}`
- **لا يحتاج تسجيل دخول**: متاح للجميع
- **الفلترة**: حسب `type` (social_link أو general_setting)
- **التخزين المؤقت**: مُوصى به لتقليل الطلبات
- **القيم الافتراضية**: استخدمها دائماً

---

## 🔄 التحديثات التلقائية

يمكنك تحديث الإعدادات تلقائياً عند تغييرها:

```javascript
// في AppSettingsService
async function startPolling(interval = 60000) {
    setInterval(async () => {
        this.clearCache();
        await this.getAllSettings();
    }, interval);
}
```

أو استخدام WebSockets إذا كان متاحاً.

