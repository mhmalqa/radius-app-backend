# 📺 دليل ربط البثوث مع تطبيق المستخدم للفرونت إند

## 📋 نظرة عامة

يشرح هذا الدليل كيفية ربط البثوث المباشرة (Live Streams) مع تطبيق المستخدم وعرضها في الواجهة الأمامية. يمكن للمستخدم الوصول إلى عدة بثوث بناءً على صلاحياته واشتراكه.

---

## 🔗 نقاط النهاية (API Endpoints)

### 1. الحصول على جميع البثوث المتاحة للمستخدم

```
GET /api/live-streams
Authorization: Bearer {token} (اختياري)
```

**الوصف**: يعيد قائمة بجميع البثوث المباشرة المتاحة للمستخدم حسب صلاحياته واشتراكه.

**المعاملات الاختيارية**:

-   `category`: فلترة حسب الفئة (`match`, `channel`, `event`)
-   `featured`: فلترة حسب المميز (`true`/`false`)
-   `page`: رقم الصفحة (افتراضي: 1)
-   `per_page`: عدد النتائج في الصفحة (افتراضي: 15)

**مثال الطلب**:

```http
GET /api/live-streams?category=match&featured=true&page=1
Authorization: Bearer 1|xxxxxxxxxxxxx
```

---

### 2. الحصول على بث مباشر محدد

```
GET /api/live-streams/{id}
Authorization: Bearer {token} (مطلوب)
```

**الوصف**: يعيد تفاصيل بث مباشر محدد. يجب أن يكون المستخدم مسجل دخول ولديه صلاحيات الوصول.

**مثال الطلب**:

```http
GET /api/live-streams/1
Authorization: Bearer 1|xxxxxxxxxxxxx
```

---

## 📥 تنسيق الاستجابة

### استجابة قائمة البثوث

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "مباراة اليوم",
            "description": "وصف المباراة",
            "stream_url": "https://example.com/stream.m3u8",
            "access_type": "all_subscribers",
            "access_type_label": "لجميع المشتركين",
            "thumbnail": "https://domain.com/storage/live_stream_thumbnails/xxx.jpg",
            "category": "match",
            "stream_type": "live",
            "is_active": true,
            "is_featured": true,
            "start_time": "2025-12-15T20:00:00Z",
            "end_time": "2025-12-15T22:00:00Z",
            "view_count": 150,
            "max_viewers": 1000,
            "sort_order": 1,
            "quality_options": null,
            "created_at": "2025-12-15T10:00:00Z",
            "updated_at": "2025-12-15T10:00:00Z"
        },
        {
            "id": 2,
            "title": "بث حصري",
            "description": "بث حصري لمشتركي البث المباشر",
            "stream_url": "https://example.com/exclusive.m3u8",
            "access_type": "live_subscribers_only",
            "access_type_label": "لمشتركي البث المباشر فقط",
            "thumbnail": "https://domain.com/storage/live_stream_thumbnails/yyy.jpg",
            "category": "channel",
            "stream_type": "live",
            "is_active": true,
            "is_featured": false,
            "start_time": "2025-12-16T19:00:00Z",
            "end_time": null,
            "view_count": 75,
            "max_viewers": null,
            "sort_order": 2,
            "quality_options": [
                {
                    "label": "HD",
                    "url": "https://example.com/hd.m3u8"
                },
                {
                    "label": "SD",
                    "url": "https://example.com/sd.m3u8"
                }
            ],
            "created_at": "2025-12-16T08:00:00Z",
            "updated_at": "2025-12-16T08:00:00Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 2,
        "per_page": 15,
        "total": 25
    }
}
```

### استجابة بث واحد

```json
{
    "success": true,
    "data": {
        "id": 1,
        "title": "مباراة اليوم",
        "description": "وصف المباراة",
        "stream_url": "https://example.com/stream.m3u8",
        "access_type": "all_subscribers",
        "access_type_label": "لجميع المشتركين",
        "thumbnail": "https://domain.com/storage/live_stream_thumbnails/xxx.jpg",
        "category": "match",
        "stream_type": "live",
        "is_active": true,
        "is_featured": true,
        "start_time": "2025-12-15T20:00:00Z",
        "end_time": "2025-12-15T22:00:00Z",
        "view_count": 151,
        "max_viewers": 1000,
        "sort_order": 1,
        "quality_options": null,
        "created_at": "2025-12-15T10:00:00Z",
        "updated_at": "2025-12-15T10:00:00Z"
    }
}
```

---

## 🔐 آلية الصلاحيات والفلترة

### 1. للمستخدمين غير المسجلين

-   **البثوث المتاحة**: فقط البثوث التي `access_type = 'all_subscribers'` أو `null`
-   **الحالة**: يجب أن تكون `is_active = true`
-   **الوقت**: يجب أن يكون البث متاحاً زمنياً (بين `start_time` و `end_time`)

### 2. للمستخدمين المسجلين بدون اشتراك نشط

-   **البثوث المتاحة**: لا شيء (لا تظهر أي بثوث)

### 3. للمستخدمين المسجلين مع اشتراك نشط

#### أ. بدون صلاحية البث المباشر (`live_access = false`)

-   **البثوث المتاحة**: فقط البثوث التي `access_type = 'all_subscribers'` أو `null`
-   **الحالة**: يجب أن تكون `is_active = true`
-   **الوقت**: يجب أن يكون البث متاحاً زمنياً

#### ب. مع صلاحية البث المباشر (`live_access = true`)

-   **البثوث المتاحة**: جميع البثوث (`all_subscribers` و `live_subscribers_only`)
-   **الحالة**: يجب أن تكون `is_active = true`
-   **الوقت**: يجب أن يكون البث متاحاً زمنياً

### 4. للمديرين (Admin - Role: 2)

-   **البثوث المتاحة**: جميع البثوث (نشطة وغير نشطة، جميع أنواع الوصول، جميع الأوقات)
-   **لا توجد فلترة**: المدير يرى كل شيء

---

## 💻 أمثلة التكامل للفرونت إند

### مثال 1: جلب جميع البثوث المتاحة

```javascript
// React/Vue/Angular Example
async function fetchLiveStreams(filters = {}) {
    const token = localStorage.getItem("token");
    const params = new URLSearchParams();

    if (filters.category) params.append("category", filters.category);
    if (filters.featured !== undefined)
        params.append("featured", filters.featured);
    if (filters.page) params.append("page", filters.page);

    const response = await fetch(`/api/live-streams?${params}`, {
        method: "GET",
        headers: {
            Authorization: token ? `Bearer ${token}` : "",
            Accept: "application/json",
        },
    });

    const data = await response.json();

    if (data.success) {
        return {
            streams: data.data,
            pagination: data.meta,
        };
    } else {
        throw new Error(data.message || "فشل في جلب البثوث");
    }
}

// الاستخدام
try {
    const { streams, pagination } = await fetchLiveStreams({
        category: "match",
        featured: true,
        page: 1,
    });

    console.log("البثوث المتاحة:", streams);
    console.log("معلومات الصفحات:", pagination);
} catch (error) {
    console.error("خطأ:", error.message);
}
```

### مثال 2: جلب بث محدد

```javascript
async function fetchLiveStream(streamId) {
    const token = localStorage.getItem("token");

    if (!token) {
        throw new Error("يجب تسجيل الدخول لمشاهدة البث");
    }

    const response = await fetch(`/api/live-streams/${streamId}`, {
        method: "GET",
        headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
        },
    });

    const data = await response.json();

    if (data.success) {
        return data.data;
    } else {
        if (response.status === 401) {
            throw new Error("يجب تسجيل الدخول لمشاهدة هذا البث");
        } else if (response.status === 403) {
            throw new Error(data.message || "ليس لديك صلاحية لمشاهدة هذا البث");
        } else if (response.status === 404) {
            throw new Error("البث غير متاح");
        } else {
            throw new Error(data.message || "فشل في جلب البث");
        }
    }
}

// الاستخدام
try {
    const stream = await fetchLiveStream(1);
    console.log("تفاصيل البث:", stream);
    // عرض البث في المشغل
    playStream(stream.stream_url);
} catch (error) {
    console.error("خطأ:", error.message);
    // عرض رسالة خطأ للمستخدم
}
```

### مثال 3: عرض قائمة البثوث مع فلترة

```javascript
// React Component Example
import { useState, useEffect } from "react";

function LiveStreamsList() {
    const [streams, setStreams] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [filters, setFilters] = useState({
        category: "",
        featured: false,
        page: 1,
    });
    const [pagination, setPagination] = useState(null);

    useEffect(() => {
        loadStreams();
    }, [filters]);

    async function loadStreams() {
        setLoading(true);
        setError(null);

        try {
            const token = localStorage.getItem("token");
            const params = new URLSearchParams();

            if (filters.category) params.append("category", filters.category);
            if (filters.featured) params.append("featured", "true");
            params.append("page", filters.page);

            const response = await fetch(`/api/live-streams?${params}`, {
                headers: {
                    Authorization: token ? `Bearer ${token}` : "",
                },
            });

            const data = await response.json();

            if (data.success) {
                setStreams(data.data);
                setPagination(data.meta);
            } else {
                setError(data.message);
            }
        } catch (err) {
            setError("حدث خطأ أثناء جلب البثوث");
        } finally {
            setLoading(false);
        }
    }

    if (loading) return <div>جاري التحميل...</div>;
    if (error) return <div>خطأ: {error}</div>;

    return (
        <div>
            {/* فلاتر */}
            <div>
                <select
                    value={filters.category}
                    onChange={(e) =>
                        setFilters({
                            ...filters,
                            category: e.target.value,
                            page: 1,
                        })
                    }
                >
                    <option value="">جميع الفئات</option>
                    <option value="match">مباراة</option>
                    <option value="channel">قناة</option>
                    <option value="event">حدث</option>
                </select>

                <label>
                    <input
                        type="checkbox"
                        checked={filters.featured}
                        onChange={(e) =>
                            setFilters({
                                ...filters,
                                featured: e.target.checked,
                                page: 1,
                            })
                        }
                    />
                    المميز فقط
                </label>
            </div>

            {/* قائمة البثوث */}
            <div className="streams-grid">
                {streams.map((stream) => (
                    <div key={stream.id} className="stream-card">
                        <img
                            src={stream.thumbnail || "/default-thumbnail.jpg"}
                            alt={stream.title}
                        />
                        <h3>{stream.title}</h3>
                        <p>{stream.description}</p>
                        <span className="badge">
                            {stream.access_type_label}
                        </span>
                        {stream.is_featured && (
                            <span className="featured">مميز</span>
                        )}
                        <button onClick={() => openStream(stream.id)}>
                            شاهد الآن
                        </button>
                    </div>
                ))}
            </div>

            {/* صفحات */}
            {pagination && pagination.last_page > 1 && (
                <div className="pagination">
                    <button
                        disabled={pagination.current_page === 1}
                        onClick={() =>
                            setFilters({ ...filters, page: filters.page - 1 })
                        }
                    >
                        السابق
                    </button>
                    <span>
                        صفحة {pagination.current_page} من {pagination.last_page}
                    </span>
                    <button
                        disabled={
                            pagination.current_page === pagination.last_page
                        }
                        onClick={() =>
                            setFilters({ ...filters, page: filters.page + 1 })
                        }
                    >
                        التالي
                    </button>
                </div>
            )}
        </div>
    );
}
```

### مثال 4: معالجة الأخطاء والصلاحيات

```javascript
async function handleStreamAccess(streamId) {
    const token = localStorage.getItem("token");

    if (!token) {
        // توجيه المستخدم إلى صفحة تسجيل الدخول
        window.location.href = "/login?redirect=/streams/" + streamId;
        return;
    }

    try {
        const stream = await fetchLiveStream(streamId);

        // التحقق من أن البث متاح زمنياً
        const now = new Date();
        const startTime = stream.start_time
            ? new Date(stream.start_time)
            : null;
        const endTime = stream.end_time ? new Date(stream.end_time) : null;

        if (startTime && now < startTime) {
            alert(`البث سيبدأ في: ${formatDate(startTime)}`);
            return;
        }

        if (endTime && now > endTime) {
            alert("انتهى البث");
            return;
        }

        // فتح البث
        openStreamPlayer(stream);
    } catch (error) {
        if (error.message.includes("صلاحية")) {
            // عرض رسالة للمستخدم بأنه يحتاج إلى ترقية اشتراكه
            showUpgradeMessage();
        } else if (error.message.includes("تسجيل الدخول")) {
            window.location.href = "/login";
        } else {
            alert(error.message);
        }
    }
}
```

---

## 📊 شرح الحقول المهمة

### `access_type` و `access_type_label`

-   **`all_subscribers`**: متاح لجميع المشتركين النشطين
-   **`live_subscribers_only`**: متاح فقط للمستخدمين الذين لديهم `live_access = true` واشتراك نشط
-   **`access_type_label`**: نص عربي يشرح نوع الوصول (للعرض في الواجهة)

### `start_time` و `end_time`

-   **`start_time`**: تاريخ ووقت بداية البث (ISO 8601 format)
-   **`end_time`**: تاريخ ووقت نهاية البث (ISO 8601 format)
-   **`null`**: يعني أن البث متاح دائماً (إذا كان `start_time` null) أو لا ينتهي (إذا كان `end_time` null)

### `quality_options`

مصفوفة من خيارات الجودة المتاحة للبث:

```json
[
    {
        "label": "HD",
        "url": "https://example.com/hd.m3u8"
    },
    {
        "label": "SD",
        "url": "https://example.com/sd.m3u8"
    }
]
```

يمكن للمستخدم اختيار الجودة المناسبة حسب سرعة الإنترنت.

### `category`

-   **`match`**: مباراة
-   **`channel`**: قناة
-   **`event`**: حدث

يمكن استخدامها لتصنيف البثوث وعرضها في أقسام منفصلة.

---

## ⚠️ حالات الخطأ المحتملة

### 401 - Unauthorized

```json
{
    "success": false,
    "message": "يجب تسجيل الدخول لمشاهدة هذا البث"
}
```

**الحل**: توجيه المستخدم إلى صفحة تسجيل الدخول.

### 403 - Forbidden

```json
{
    "success": false,
    "message": "ليس لديك صلاحية لمشاهدة هذا البث. هذا البث متاح فقط لمشتركي البث المباشر"
}
```

**الحل**: إعلام المستخدم بأنه يحتاج إلى ترقية اشتراكه أو تفعيل صلاحية البث المباشر.

### 404 - Not Found

```json
{
    "success": false,
    "message": "البث غير متاح"
}
```

**الحل**: إعلام المستخدم بأن البث غير موجود أو غير نشط.

---

## 🎯 أفضل الممارسات

### 1. التخزين المؤقت (Caching)

```javascript
// تخزين البثوث في localStorage أو state management
const CACHE_KEY = "live_streams_cache";
const CACHE_DURATION = 60000; // 1 دقيقة

async function getCachedStreams() {
    const cached = localStorage.getItem(CACHE_KEY);
    if (cached) {
        const { data, timestamp } = JSON.parse(cached);
        if (Date.now() - timestamp < CACHE_DURATION) {
            return data;
        }
    }

    const streams = await fetchLiveStreams();
    localStorage.setItem(
        CACHE_KEY,
        JSON.stringify({
            data: streams,
            timestamp: Date.now(),
        })
    );

    return streams;
}
```

### 2. تحديث تلقائي للبثوث

```javascript
// تحديث البثوث كل 30 ثانية
useEffect(() => {
    const interval = setInterval(() => {
        loadStreams();
    }, 30000);

    return () => clearInterval(interval);
}, []);
```

### 3. معالجة البثوث المتعددة

```javascript
// عرض البثوث حسب الفئة
function groupStreamsByCategory(streams) {
    return streams.reduce((groups, stream) => {
        const category = stream.category || "other";
        if (!groups[category]) {
            groups[category] = [];
        }
        groups[category].push(stream);
        return groups;
    }, {});
}

// الاستخدام
const groupedStreams = groupStreamsByCategory(streams);
// النتيجة: { match: [...], channel: [...], event: [...] }
```

### 4. عرض البثوث المميزة أولاً

```javascript
// ترتيب البثوث: المميزة أولاً، ثم حسب sort_order
function sortStreams(streams) {
    return streams.sort((a, b) => {
        if (a.is_featured && !b.is_featured) return -1;
        if (!a.is_featured && b.is_featured) return 1;
        return (a.sort_order || 0) - (b.sort_order || 0);
    });
}
```

---

## 📱 مثال كامل: صفحة البثوث

```javascript
// LiveStreamsPage.jsx
import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";

function LiveStreamsPage() {
    const [streams, setStreams] = useState([]);
    const [loading, setLoading] = useState(true);
    const [selectedCategory, setSelectedCategory] = useState("");
    const [showFeaturedOnly, setShowFeaturedOnly] = useState(false);
    const navigate = useNavigate();

    useEffect(() => {
        loadStreams();
    }, [selectedCategory, showFeaturedOnly]);

    async function loadStreams() {
        setLoading(true);
        try {
            const token = localStorage.getItem("token");
            const params = new URLSearchParams();

            if (selectedCategory) params.append("category", selectedCategory);
            if (showFeaturedOnly) params.append("featured", "true");

            const response = await fetch(`/api/live-streams?${params}`, {
                headers: {
                    Authorization: token ? `Bearer ${token}` : "",
                },
            });

            const data = await response.json();

            if (data.success) {
                // ترتيب: المميزة أولاً
                const sorted = data.data.sort((a, b) => {
                    if (a.is_featured && !b.is_featured) return -1;
                    if (!a.is_featured && b.is_featured) return 1;
                    return (a.sort_order || 0) - (b.sort_order || 0);
                });
                setStreams(sorted);
            }
        } catch (error) {
            console.error("خطأ في جلب البثوث:", error);
        } finally {
            setLoading(false);
        }
    }

    function handleStreamClick(streamId) {
        navigate(`/streams/${streamId}`);
    }

    if (loading) {
        return <div className="loading">جاري التحميل...</div>;
    }

    return (
        <div className="live-streams-page">
            <h1>البثوث المباشرة</h1>

            {/* الفلاتر */}
            <div className="filters">
                <select
                    value={selectedCategory}
                    onChange={(e) => setSelectedCategory(e.target.value)}
                >
                    <option value="">جميع الفئات</option>
                    <option value="match">مباريات</option>
                    <option value="channel">قنوات</option>
                    <option value="event">أحداث</option>
                </select>

                <label>
                    <input
                        type="checkbox"
                        checked={showFeaturedOnly}
                        onChange={(e) => setShowFeaturedOnly(e.target.checked)}
                    />
                    المميزة فقط
                </label>
            </div>

            {/* قائمة البثوث */}
            {streams.length === 0 ? (
                <div className="no-streams">لا توجد بثوث متاحة حالياً</div>
            ) : (
                <div className="streams-grid">
                    {streams.map((stream) => (
                        <div
                            key={stream.id}
                            className={`stream-card ${
                                stream.is_featured ? "featured" : ""
                            }`}
                            onClick={() => handleStreamClick(stream.id)}
                        >
                            {stream.thumbnail && (
                                <img
                                    src={stream.thumbnail}
                                    alt={stream.title}
                                    className="thumbnail"
                                />
                            )}
                            <div className="content">
                                <h3>{stream.title}</h3>
                                {stream.description && (
                                    <p className="description">
                                        {stream.description}
                                    </p>
                                )}
                                <div className="meta">
                                    <span className="badge">
                                        {stream.access_type_label}
                                    </span>
                                    {stream.is_featured && (
                                        <span className="featured-badge">
                                            ⭐ مميز
                                        </span>
                                    )}
                                    {stream.start_time && (
                                        <span className="time">
                                            {formatDate(stream.start_time)}
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}

export default LiveStreamsPage;
```

---

## 🔄 التحديثات التلقائية

يمكنك استخدام WebSockets أو Polling لتحديث البثوث تلقائياً:

```javascript
// Polling Example
useEffect(() => {
    const interval = setInterval(() => {
        loadStreams();
    }, 30000); // كل 30 ثانية

    return () => clearInterval(interval);
}, []);
```

---

## 📝 ملاحظات مهمة

1. **الصلاحيات**: البثوث تُفلتر تلقائياً حسب صلاحيات المستخدم واشتراكه
2. **التوقيت**: البثوث التي لها `start_time` و `end_time` تُفلتر تلقائياً
3. **المدير**: يرى جميع البثوث بدون أي فلترة
4. **التخزين المؤقت**: يُنصح باستخدام تخزين مؤقت لتقليل عدد الطلبات
5. **معالجة الأخطاء**: يجب معالجة جميع حالات الخطأ بشكل مناسب

---

## 🎬 الخلاصة

-   استخدم `GET /api/live-streams` لجلب جميع البثوث المتاحة
-   استخدم `GET /api/live-streams/{id}` لجلب بث محدد
-   البثوث تُفلتر تلقائياً حسب صلاحيات المستخدم
-   يمكن أن يكون هناك عدة بثوث متاحة للمستخدم في نفس الوقت
-   استخدم الفلاتر (`category`, `featured`) لتحسين تجربة المستخدم
