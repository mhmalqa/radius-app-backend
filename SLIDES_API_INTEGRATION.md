# 🎨 API: ربط السلايدات (Slides) لتطبيق المستخدم

## 📍 الروابط

### عرض السلايدات
```
GET /api/slides?target_audience=all
```

### تتبع النقر على السلايد
```
POST /api/slides/{id}/track-click
Authorization: Bearer {token} (اختياري)
```

---

## 📤 Request

### عرض السلايدات

**Headers:**
```
Authorization: Bearer {token} (اختياري - للمستخدمين المسجلين)
```

**Query Parameters (اختيارية):**
- `target_audience`: `all` | `active_users` | `expired_users`

**مثال:**
```javascript
// جلب جميع السلايدات
const response = await fetch('https://api.example.com/api/slides');

// جلب سلايدات للمستخدمين النشطين فقط
const response = await fetch('https://api.example.com/api/slides?target_audience=active_users');
```

---

## 📥 Response

### ✅ Success Response (200)

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "عرض خاص",
      "image_path": "https://api.example.com/storage/slides/slide1.jpg",
      "image_mobile": "https://api.example.com/storage/slides/slide1-mobile.jpg",
      "image_desktop": "https://api.example.com/storage/slides/slide1-desktop.jpg",
      "link_url": "https://example.com/offer",
      "is_active": true,
      "target_audience": "all",
      "sort_order": 1,
      "start_at": "2024-01-01T00:00:00.000000Z",
      "end_at": "2024-12-31T23:59:59.000000Z",
      "click_count": 150,
      "created_at": "2024-01-01T10:00:00.000000Z",
      "updated_at": "2024-01-15T14:30:00.000000Z"
    }
  ]
}
```

---

## 📊 شرح الحقول

| الحقل | النوع | الوصف |
|------|------|-------|
| `id` | number | معرف السلايد |
| `title` | string | عنوان السلايد |
| `image_path` | string | رابط الصورة الأساسية |
| `image_mobile` | string | رابط الصورة للموبايل (اختياري) |
| `image_desktop` | string | رابط الصورة للديسكتوب (اختياري) |
| `link_url` | string | رابط عند النقر (اختياري) |
| `is_active` | boolean | هل السلايد نشط |
| `target_audience` | string | الجمهور المستهدف: `all`, `active_users`, `expired_users` |
| `sort_order` | number | ترتيب العرض (الأقل أولاً) |
| `start_at` | string | تاريخ بداية العرض (اختياري) |
| `end_at` | string | تاريخ نهاية العرض (اختياري) |
| `click_count` | number | عدد النقرات |

---

## 🎯 تتبع النقر

### Request
```
POST /api/slides/{id}/track-click
Authorization: Bearer {token} (اختياري)
```

### Response
```json
{
  "success": true,
  "message": "تم تسجيل النقرة"
}
```

---

## 💡 أمثلة الاستخدام

### React Native / Expo
```javascript
import { useState, useEffect } from 'react';
import { Image, TouchableOpacity, Linking } from 'react-native';

function SlidesCarousel() {
  const [slides, setSlides] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchSlides();
  }, []);

  const fetchSlides = async () => {
    try {
      const response = await fetch('https://api.example.com/api/slides');
      const data = await response.json();
      if (data.success) {
        setSlides(data.data);
      }
    } catch (error) {
      console.error('Error fetching slides:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleSlideClick = async (slide) => {
    // تتبع النقر
    if (slide.id) {
      try {
        await fetch(`https://api.example.com/api/slides/${slide.id}/track-click`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${userToken}` // اختياري
          }
        });
      } catch (error) {
        console.error('Error tracking click:', error);
      }
    }

    // فتح الرابط إن وجد
    if (slide.link_url) {
      Linking.openURL(slide.link_url);
    }
  };

  if (loading) return <LoadingIndicator />;

  return (
    <ScrollView horizontal pagingEnabled>
      {slides.map((slide) => (
        <TouchableOpacity
          key={slide.id}
          onPress={() => handleSlideClick(slide)}
          activeOpacity={0.9}
        >
          <Image
            source={{ 
              uri: Platform.OS === 'ios' 
                ? (slide.image_mobile || slide.image_path)
                : (slide.image_mobile || slide.image_path)
            }}
            style={{ width: screenWidth, height: 200 }}
            resizeMode="cover"
          />
        </TouchableOpacity>
      ))}
    </ScrollView>
  );
}
```

### React Web
```jsx
import { useState, useEffect } from 'react';
import Slider from 'react-slick';

function SlidesCarousel() {
  const [slides, setSlides] = useState([]);

  useEffect(() => {
    fetch('https://api.example.com/api/slides')
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          setSlides(data.data);
        }
      });
  }, []);

  const handleSlideClick = async (slide) => {
    // تتبع النقر
    if (slide.id) {
      fetch(`https://api.example.com/api/slides/${slide.id}/track-click`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });
    }

    // فتح الرابط
    if (slide.link_url) {
      window.open(slide.link_url, '_blank');
    }
  };

  const settings = {
    dots: true,
    infinite: true,
    speed: 500,
    slidesToShow: 1,
    slidesToScroll: 1,
    autoplay: true,
    autoplaySpeed: 3000,
  };

  return (
    <Slider {...settings}>
      {slides.map((slide) => (
        <div key={slide.id} onClick={() => handleSlideClick(slide)}>
          <img
            src={slide.image_desktop || slide.image_path}
            alt={slide.title}
            style={{ width: '100%', height: '400px', objectFit: 'cover', cursor: 'pointer' }}
          />
        </div>
      ))}
    </Slider>
  );
}
```

### Flutter
```dart
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:url_launcher/url_launcher.dart';

class SlidesService {
  static Future<List<Slide>> fetchSlides() async {
    final response = await http.get(
      Uri.parse('https://api.example.com/api/slides'),
    );

    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      if (data['success']) {
        return (data['data'] as List)
            .map((slide) => Slide.fromJson(slide))
            .toList();
      }
    }
    throw Exception('Failed to load slides');
  }

  static Future<void> trackClick(int slideId, String? token) async {
    try {
      await http.post(
        Uri.parse('https://api.example.com/api/slides/$slideId/track-click'),
        headers: token != null ? {'Authorization': 'Bearer $token'} : {},
      );
    } catch (e) {
      print('Error tracking click: $e');
    }
  }

  static Future<void> openLink(String? url) async {
    if (url != null && await canLaunch(url)) {
      await launch(url);
    }
  }
}

class Slide {
  final int id;
  final String title;
  final String imagePath;
  final String? imageMobile;
  final String? imageDesktop;
  final String? linkUrl;

  Slide({
    required this.id,
    required this.title,
    required this.imagePath,
    this.imageMobile,
    this.imageDesktop,
    this.linkUrl,
  });

  factory Slide.fromJson(Map<String, dynamic> json) {
    return Slide(
      id: json['id'],
      title: json['title'],
      imagePath: json['image_path'],
      imageMobile: json['image_mobile'],
      imageDesktop: json['image_desktop'],
      linkUrl: json['link_url'],
    );
  }
}
```

---

## 🎨 نصائح للعرض الاحترافي

### 1. اختيار الصورة المناسبة
```javascript
// استخدم الصورة المناسبة حسب الجهاز
const getImageUrl = (slide) => {
  if (Platform.OS === 'web') {
    return slide.image_desktop || slide.image_path;
  } else {
    return slide.image_mobile || slide.image_path;
  }
};
```

### 2. تصفية السلايدات حسب الجمهور
```javascript
const getFilteredSlides = (slides, userSubscription) => {
  return slides.filter(slide => {
    if (slide.target_audience === 'all') return true;
    if (slide.target_audience === 'active_users' && userSubscription?.is_active) return true;
    if (slide.target_audience === 'expired_users' && !userSubscription?.is_active) return true;
    return false;
  });
};
```

### 3. التحقق من تاريخ الصلاحية
```javascript
const isSlideActive = (slide) => {
  const now = new Date();
  const startAt = slide.start_at ? new Date(slide.start_at) : null;
  const endAt = slide.end_at ? new Date(slide.end_at) : null;
  
  if (startAt && now < startAt) return false;
  if (endAt && now > endAt) return false;
  
  return slide.is_active;
};
```

### 4. ترتيب السلايدات
```javascript
// ترتيب حسب sort_order
const sortedSlides = slides.sort((a, b) => a.sort_order - b.sort_order);
```

---

## ✅ Checklist للربط

- [ ] جلب السلايدات عند تحميل الشاشة
- [ ] عرض الصورة المناسبة حسب الجهاز (mobile/desktop)
- [ ] تطبيق التصفية حسب `target_audience`
- [ ] التحقق من `is_active` و `start_at` / `end_at`
- [ ] ترتيب السلايدات حسب `sort_order`
- [ ] تتبع النقر عند الضغط على السلايد
- [ ] فتح `link_url` عند النقر (إن وجد)
- [ ] معالجة الأخطاء بشكل صحيح
- [ ] إضافة Loading state
- [ ] إضافة Error state

---

## 📝 ملاحظات مهمة

1. **المصادقة اختيارية**: يمكن جلب السلايدات بدون token، لكن تتبع النقر قد يتطلب token
2. **الصور المتعددة**: استخدم `image_mobile` للموبايل و `image_desktop` للديسكتوب
3. **التصفية التلقائية**: السيرفر يعيد فقط السلايدات النشطة والمتاحة زمنياً
4. **الترتيب**: استخدم `sort_order` لعرض السلايدات بالترتيب الصحيح
5. **الجمهور المستهدف**: راعِ `target_audience` لعرض السلايدات المناسبة لكل مستخدم
