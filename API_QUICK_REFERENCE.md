# ⚡ مرجع سريع لـ API Routes

## 🔐 الصلاحيات

- **👤 User (0)**: مستخدم عادي
- **💰 Accountant (1)**: محاسب (يمكنه إدارة طلبات الدفع)
- **👑 Admin (2)**: مدير (جميع الصلاحيات)

---

## 🌐 Routes عامة (Public)

| Route | Method | الوصف |
|-------|--------|-------|
| `/api/auth/login` | POST | تسجيل الدخول |
| `/api/auth/register` | POST | التسجيل |
| `/api/payment-methods` | GET | طرق الدفع |
| `/api/slides` | GET | السلايدات |
| `/api/live-streams` | GET | قائمة البث |

---

## 🔒 Routes محمية (auth:sanctum)

### Auth
| Route | Method | User | Accountant | Admin |
|-------|--------|------|------------|-------|
| `/api/auth/me` | GET | ✅ | ✅ | ✅ |
| `/api/auth/logout` | POST | ✅ | ✅ | ✅ |

### User
| Route | Method | User | Accountant | Admin |
|-------|--------|------|------------|-------|
| `/api/user/profile` | GET | ✅ | ✅ | ✅ |
| `/api/user/profile` | PUT | ✅ | ✅ | ✅ |
| `/api/user/sync-subscription` | POST | ✅ | ✅ | ✅ |
| `/api/user/subscription-from-radius` | GET | ✅ | ✅ | ✅ |

### Payment Requests
| Route | Method | User | Accountant | Admin |
|-------|--------|------|------------|-------|
| `/api/payment-requests` | GET | ✅ (خاص به) | ✅ (جميع) | ✅ (جميع) |
| `/api/payment-requests` | POST | ✅ | ✅ | ✅ |
| `/api/payment-requests/{id}` | GET | ✅ (خاص به) | ✅ (جميع) | ✅ (جميع) |

### Live Streams
| Route | Method | User | Accountant | Admin |
|-------|--------|------|------------|-------|
| `/api/live-streams/{id}` | GET | ✅* | ✅* | ✅ |

*يحتاج `live_access = true`

### Slides
| Route | Method | User | Accountant | Admin |
|-------|--------|------|------------|-------|
| `/api/slides/{id}/track-click` | POST | ✅ | ✅ | ✅ |

### Notifications
| Route | Method | User | Accountant | Admin |
|-------|--------|------|------------|-------|
| `/api/notifications` | GET | ✅ | ✅ | ✅ |
| `/api/notifications/unread-count` | GET | ✅ | ✅ | ✅ |
| `/api/notifications/{id}/mark-as-read` | POST | ✅ | ✅ | ✅ |
| `/api/notifications/mark-all-as-read` | POST | ✅ | ✅ | ✅ |

---

## 👨‍💼 Routes للمحاسب والمدير (role:admin,accountant)

| Route | Method | Accountant | Admin |
|-------|--------|------------|-------|
| `/api/admin/payment-requests` | GET | ✅ | ✅ |
| `/api/admin/payment-requests/{id}/status` | PUT | ✅ | ✅ |

---

## 👑 Routes للمدير فقط (role:admin)

| Route | Method | Admin |
|-------|--------|-------|
| `/api/admin/live-streams` | POST | ✅ |
| `/api/admin/live-streams/{id}` | PUT | ✅ |
| `/api/admin/live-streams/{id}` | DELETE | ✅ |
| `/api/admin/slides` | POST | ✅ |
| `/api/admin/slides/{id}` | PUT | ✅ |
| `/api/admin/slides/{id}` | DELETE | ✅ |
| `/api/admin/notifications` | POST | ✅ |

---

## 📚 للمزيد من التفاصيل

- [API Routes Documentation](./API_ROUTES_DOCUMENTATION.md) - توثيق شامل لكل route
- [Permissions Matrix](./PERMISSIONS_MATRIX.md) - مصفوفة الصلاحيات الكاملة

