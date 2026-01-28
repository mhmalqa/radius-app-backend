# ✅ دليل جاهز: تشغيل البث داخل تطبيق المستخدم (Next.js + Capacitor APK) بسلاسة وبدون تقطيع

هذا الملف مكتوب ليُرسل كما هو إلى ذكاء اصطناعي/مطور الواجهة لتنفيذ تشغيل البث داخل **Next.js** المدمج داخل **Capacitor** (Android/iOS).

> **مبدأ الأمان الأساسي**: تطبيق المستخدم **لا يجب أن يرى** رابط البث الحقيقي (`stream_url`). التشغيل يتم عبر رابط محمي مؤقت (Proxy) صادر من السيرفر.

---

## 1) ما الذي يوفّره السيرفر (Backend) بالفعل؟

### 1.1 جلب البثوث

-   **GET** `/api/live-streams`
-   Auth: اختياري (لكن مع تسجيل الدخول سيرجع للمستخدم `stream_url` على شكل رابط محمي Runtime).

### 1.2 إنشاء رابط تشغيل محمي قبل التشغيل مباشرة (الموصى به)

-   **POST** `/api/live-streams/{liveStreamId}/playback`
-   Headers: `Authorization: Bearer {token}`
-   Response:

```json
{
    "success": true,
    "data": {
        "playback_url": "https://YOUR_DOMAIN.com/api/live-streams/12/secure?token=....",
        "expires_at": "2026-01-27T12:00:00Z"
    }
}
```

### 1.3 رابط التشغيل المحمي (Proxy)

-   **GET** `/api/live-streams/{liveStreamId}/secure?token=...`
-   هذا المسار **لا يحتاج Authorization** لأنه مخصص للمشغّل الذي لا يرسل Headers بسهولة.

### 1.4 ملاحظة مهمة جدًا عن “عدم التقطيع”

-   السيرفر يقوم **بتمديد صلاحية التوكن تلقائيًا أثناء تشغيل HLS** عند تحميل الـ playlist والـ segments.
-   لذلك على تطبيق المستخدم: **لا تعمل refresh دوري للرابط**. فقط:
    -   اطلب `POST /playback` عند بدء التشغيل
    -   وإذا حصل فشل (403 أو توقف) اطلب `POST /playback` مرة أخرى وأعد التشغيل

### 1.5 إعداد مدة التوكن (TTL)

في `.env` على السيرفر:

```env
LIVE_STREAM_TOKEN_TTL_SECONDS=3300
```

> 3300 ثانية = 55 دقيقة.

---

## 2) متطلبات الواجهة (User App) — قواعد ذهبية

-   **لا تُظهر زر “نسخ الرابط”**.
-   **لا تفتح البث بمتصفح خارجي**.
-   **لا تسجّل `playback_url` في logs / analytics / crash reports**.
-   عند الضغط “تشغيل” اطلب توكن جديد عبر `POST /playback` ثم شغّل `playback_url`.
-   عند الفشل: جدّد التوكن وأعد التشغيل (Retry Logic).

---

## 3) دعم المصادر (HLS/MP4/YouTube/Encoder) — ما الذي يعمل فعليًا داخل WebView؟

### 3.1 يعمل بشكل ممتاز داخل Next.js + Capacitor

-   **HLS**: `.m3u8` (أفضل خيار للبث المباشر)
-   **MP4**: روابط مباشرة Progressive

### 3.2 YouTube

-   يُشغّل عبر **YouTube IFrame داخل WebView** (ليس عبر hls.js).

### 3.3 “أغلب الصيغ” (RTSP/RTMP/DASH/…)

-   داخل WebView عادة **لن تعمل** بشكل ثابت.
-   إن كان مطلوب دعمها: لديك خياران:
    1. **تحويل المصدر إلى HLS** على السيرفر/الـ encoder (مُفضل).
    2. استخدام **مشغّل Native/VLC** عبر Capacitor Plugin (أثقل).

> توصية تشغيلية: اجعل الـ encoder يخرج HLS `.m3u8` دائمًا لتوحيد تجربة التشغيل.

---

## 4) تنفيذ Next.js (داخل Capacitor) — هيكلة موصى بها

### 4.1 متغيرات البيئة في Next.js

ضع عنوان API (الدومين) في:

```env
NEXT_PUBLIC_API_BASE_URL=https://YOUR_DOMAIN.com
```

> داخل Capacitor لا تعتمد على relative URL إلا إذا كنت تستضيف الواجهة على نفس الدومين.

### 4.2 دوال API (مثال TypeScript)

```ts
// lib/api.ts
export const API_BASE = process.env.NEXT_PUBLIC_API_BASE_URL!;

export async function apiGet<T>(path: string, token?: string): Promise<T> {
    const res = await fetch(`${API_BASE}${path}`, {
        headers: token ? { Authorization: `Bearer ${token}` } : undefined,
    });
    const json = await res.json();
    if (!res.ok) throw new Error(json?.message || `HTTP ${res.status}`);
    return json as T;
}

export async function apiPost<T>(
    path: string,
    token: string,
    body?: any
): Promise<T> {
    const res = await fetch(`${API_BASE}${path}`, {
        method: "POST",
        headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
        },
        body: body ? JSON.stringify(body) : undefined,
    });
    const json = await res.json();
    if (!res.ok) throw new Error(json?.message || `HTTP ${res.status}`);
    return json as T;
}
```

### 4.3 طلب رابط التشغيل قبل التشغيل

```ts
type PlaybackResponse = {
    success: boolean;
    data: { playback_url: string; expires_at: string };
};

export async function createPlaybackUrl(liveStreamId: number, token: string) {
    const r = await apiPost<PlaybackResponse>(
        `/api/live-streams/${liveStreamId}/playback`,
        token
    );
    return r.data.playback_url;
}
```

---

## 5) مشغّل Web “سلس” داخل Capacitor (HLS + MP4) + Retry بدون تقطيع

### 5.1 التبعيات

استخدم:

-   `hls.js` لتشغيل HLS على Android WebView.

```bash
npm i hls.js
```

### 5.2 مكوّن Player (React) — منطق التشغيل

هذا المثال:

-   يشغّل HLS على iOS مباشرة عبر `<video src>` (إذا مدعوم)
-   ويستخدم `hls.js` على Android/غيره
-   عند فشل Fatal/Error: يطلب `POST /playback` ويعيد التشغيل (بدون تقطيع)

```tsx
// components/LivePlayer.tsx
import Hls from "hls.js";
import React, { useEffect, useRef, useState } from "react";
import { createPlaybackUrl } from "@/lib/playback";

type Props = {
    liveStreamId: number;
    authToken: string;
};

export function LivePlayer({ liveStreamId, authToken }: Props) {
    const videoRef = useRef<HTMLVideoElement | null>(null);
    const hlsRef = useRef<Hls | null>(null);
    const [loading, setLoading] = useState(true);
    const retryRef = useRef({ attempts: 0, lastAt: 0 });

    async function startPlayback() {
        setLoading(true);
        const video = videoRef.current;
        if (!video) return;

        // cleanup old
        if (hlsRef.current) {
            hlsRef.current.destroy();
            hlsRef.current = null;
        }
        video.pause();
        video.removeAttribute("src");
        video.load();

        const playbackUrl = await createPlaybackUrl(liveStreamId, authToken);

        // iOS Safari/WKWebView often supports HLS natively
        const canNativeHls =
            video.canPlayType("application/vnd.apple.mpegurl") !== "";
        if (canNativeHls) {
            video.src = playbackUrl;
            await video.play();
            setLoading(false);
            return;
        }

        // Use hls.js (Android WebView)
        if (Hls.isSupported()) {
            const hls = new Hls({
                // Smoothness defaults (tune if needed)
                lowLatencyMode: true,
                backBufferLength: 30,
                maxBufferLength: 30,
                liveSyncDurationCount: 3,
                liveMaxLatencyDurationCount: 10,
            });
            hlsRef.current = hls;

            hls.on(Hls.Events.ERROR, async (_, data) => {
                // Recoverable errors: try built-in recovery
                if (!data.fatal) return;

                // Prevent infinite loops
                const now = Date.now();
                if (now - retryRef.current.lastAt < 1500) return;
                retryRef.current.lastAt = now;
                retryRef.current.attempts += 1;

                if (retryRef.current.attempts > 6) {
                    console.error("Playback failed too many times", data);
                    return;
                }

                // Refresh playback token/url then restart
                try {
                    await startPlayback();
                } catch (e) {
                    console.error("Retry startPlayback failed", e);
                }
            });

            hls.loadSource(playbackUrl);
            hls.attachMedia(video);

            hls.on(Hls.Events.MANIFEST_PARSED, async () => {
                try {
                    await video.play();
                    setLoading(false);
                } catch (e) {
                    // Autoplay restrictions may require user gesture; in Capacitor usually ok after click.
                    console.error(e);
                }
            });
            return;
        }

        // Fallback: direct src (may fail on Android)
        video.src = playbackUrl;
        await video.play();
        setLoading(false);
    }

    useEffect(() => {
        retryRef.current = { attempts: 0, lastAt: 0 };
        startPlayback().catch(console.error);
        return () => {
            if (hlsRef.current) hlsRef.current.destroy();
            hlsRef.current = null;
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [liveStreamId]);

    return (
        <div>
            {loading ? <div>جاري التحميل...</div> : null}
            <video
                ref={videoRef}
                controls
                playsInline
                autoPlay
                style={{ width: "100%", background: "#000" }}
            />
        </div>
    );
}
```

### 5.3 قواعد UX لتقليل الانقطاع

-   عند دخول شاشة المشغّل: التشغيل يحصل بعد **gesture** (click) أفضل لتجنب autoplay block.
-   عند خروج المستخدم من الشاشة: أوقف الفيديو ودمّر hls instance.
-   عند رجوع المستخدم بعد مدة وظهور خطأ: `startPlayback()` سيجدد الرابط ويعيد التشغيل.

---

## 6) دعم YouTube داخل نفس الشاشة (بدون فتح خارجي)

### 6.1 مكوّن YouTube IFrame

```tsx
// components/YouTubePlayer.tsx
import React from "react";

export function YouTubePlayer({ videoId }: { videoId: string }) {
    const src = `https://www.youtube-nocookie.com/embed/${videoId}?playsinline=1&autoplay=1&controls=1&rel=0`;
    return (
        <iframe
            src={src}
            style={{
                width: "100%",
                aspectRatio: "16 / 9",
                border: 0,
                background: "#000",
            }}
            allow="autoplay; encrypted-media; picture-in-picture"
            allowFullScreen
        />
    );
}
```

### 6.2 كيف أعرف أن هذا البث YouTube؟

**مهم**: السيرفر حاليًا يخفي `stream_url` الحقيقي عن المستخدم العادي، لذلك تطبيق المستخدم لا يستطيع اكتشاف YouTube من الرابط الحقيقي.

لحل ذلك بشكل صحيح وأمن:

-   أضف في `LiveStream` حقولًا مثل:
    -   `source_type`: `hls | mp4 | youtube`
    -   `youtube_video_id` (عند المصدر YouTube)
-   واجعل `LiveStreamResource` يعيد هذه الحقول للمستخدم العادي (بدون كشف الرابط الحقيقي).
-   وعند `POST /playback`:
    -   إذا `source_type=youtube` أعد:
        -   `{ type: "youtube", youtube_video_id: "..." }`
    -   غير ذلك أعد `playback_url` كالعادة.

---

## 7) إعدادات Capacitor اللازمة (مهم جدًا)

### 7.1 السماح بالدومينات (Android/iOS)

في `capacitor.config.ts` (أو json):

-   أضف دومين الـ API
-   وأضف دومينات يوتيوب إذا ستستخدم YouTube داخل التطبيق

مثال:

-   `YOUR_DOMAIN.com`
-   `*.youtube.com`
-   `*.youtube-nocookie.com`
-   `*.ytimg.com`
-   `*.googlevideo.com`

### 7.2 CSP في Next.js (إذا كان عندك Content-Security-Policy)

يجب أن تسمح:

-   `frame-src` ليوتيوب
-   `media-src` لـ `https:` و `blob:` (مهم لـ hls.js)
-   `connect-src` للـ API domain

---

## 8) مشاكل شائعة وحلولها

### 8.1 مشكلة CORS على Android مع hls.js

إذا ظهرت أخطاء CORS في console:

-   تأكد أن `config/cors.php` في السيرفر يسمح بأصل Capacitor:
    -   `capacitor://localhost`
    -   `http://localhost`
    -   `ionic://localhost` (إن وجد)
        ويسمح بـ GET للـ `/api/live-streams/*/secure`.

> ملاحظة: `<video src>` قد يعمل بدون CORS في بعض الحالات، لكن hls.js يحتاج CORS لأنه يستخدم XHR/fetch.

### 8.2 توقف مفاجئ بعد مدة

-   يجب الاعتماد على Retry Logic: عند error/403 اطلب `POST /playback` من جديد.
-   لا تعمل refresh دوري عشوائي.

### 8.3 “أغلب الصيغ” لا تعمل

-   الحل الصحيح: توحيد المصادر إلى HLS.
-   أو استخدام مشغّل Native/VLC عبر Plugin.

---

## 9) معايير نجاح (Acceptance Criteria)

-   تشغيل بث HLS داخل APK بدون تقطيع طويل المدى.
-   عدم كشف الرابط الحقيقي للمستخدم.
-   عدم فتح الرابط خارج التطبيق.
-   عند انتهاء توكن/خطأ 403: التطبيق يجدد `playback_url` تلقائيًا ويكمل التشغيل.
-   دعم YouTube عبر IFrame (بدون فتح خارجي) عند توفر `youtube_video_id` من السيرفر.
