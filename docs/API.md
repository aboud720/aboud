# 📡 توثيق API - ABOUD Store

## معلومات عامة

### Base URL
```
https://api.aboud-store.com/api/v1
```

### Content-Type
```
application/json
```

### المصادقة
```
Authorization: Bearer {access_token}
```

---

## 🔐 المصادقة (Auth)

### تسجيل الدخول

```http
POST /auth/login
```

**الطلب:**
```json
{
    "email": "user@example.com",
    "password": "password123"
}
```

**الاستجابة الناجحة (200):**
```json
{
    "success": true,
    "message": "تم تسجيل الدخول بنجاح",
    "data": {
        "user": {
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "username": "ahmed",
            "email": "user@example.com",
            "role": "customer",
            "balance": 50000,
            "created_at": "2024-01-15T10:30:00Z"
        },
        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "Bearer",
        "expires_in": 3600
    }
}
```

**أخطاء محتملة:**
| الكود | الرسالة |
|-------|---------|
| `INVALID_CREDENTIALS` | بيانات الدخول غير صحيحة |
| `ACCOUNT_LOCKED` | الحساب مقفل مؤقتاً |
| `EMAIL_NOT_VERIFIED` | البريد غير مؤكد |
| `TOO_MANY_ATTEMPTS` | محاولات كثيرة |

---

### تسجيل جديد

```http
POST /auth/register
```

**الطلب:**
```json
{
    "username": "newuser",
    "email": "new@example.com",
    "password": "StrongP@ss123",
    "password_confirmation": "StrongP@ss123",
    "phone": "0501234567"
}
```

**الاستجابة الناجحة (200):**
```json
{
    "success": true,
    "message": "تم التسجيل بنجاح",
    "data": {
        "user": {
            "uuid": "...",
            "username": "newuser",
            "email": "new@example.com"
        }
    }
}
```

---

### تجديد Token

```http
POST /auth/refresh
```

**الطلب:**
```json
{
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
}
```

---

### الحصول على بيانات المستخدم

```http
GET /auth/me
Authorization: Bearer {token}
```

---

## 📦 المنتجات (Products)

### قائمة المنتجات

```http
GET /products
```

**المعاملات:**
| المعامل | النوع | الوصف |
|---------|------|-------|
| `page` | int | رقم الصفحة (افتراضي: 1) |
| `per_page` | int | عدد لكل صفحة (افتراضي: 15، أقصى: 50) |
| `category_id` | int | فلترة حسب الفئة |
| `type` | string | نوع المنتج |
| `featured` | bool | المنتجات المميزة فقط |

**مثال:**
```http
GET /products?category_id=1&per_page=10&page=1
```

**الاستجابة:**
```json
{
    "success": true,
    "data": [
        {
            "uuid": "...",
            "name_ar": "شحن PUBG Mobile UC",
            "name_en": "PUBG Mobile UC",
            "slug": "pubg-mobile-uc",
            "type": "in_game_currency",
            "image": "/images/products/pubg.png",
            "formatted_price": "3.99 ر.س",
            "is_on_sale": true,
            "discount_percent": 15,
            "in_stock": true,
            "rating": 4.8
        }
    ],
    "meta": {
        "total": 100,
        "per_page": 15,
        "current_page": 1,
        "last_page": 7,
        "from": 1,
        "to": 15
    }
}
```

---

### منتج واحد

```http
GET /products/{uuid}
```

**الاستجابة:**
```json
{
    "success": true,
    "data": {
        "uuid": "...",
        "name_ar": "شحن PUBG Mobile UC",
        "description_ar": "شحن UC لعبة ببجي موبايل...",
        "requires_player_id": true,
        "player_id_label": "Player ID",
        "requires_server": false,
        "variants": [
            {
                "uuid": "...",
                "name_ar": "60 UC",
                "price": 399,
                "formatted_price": "3.99 ر.س"
            },
            {
                "uuid": "...",
                "name_ar": "325 UC",
                "price": 1899
            }
        ],
        "category": {
            "name_ar": "ألعاب الموبايل",
            "slug": "mobile-games"
        }
    }
}
```

---

### المنتجات المميزة

```http
GET /products/featured?limit=8
```

---

### البحث

```http
GET /search?q=pubg&limit=10
```

---

## 📁 الفئات (Categories)

### قائمة الفئات

```http
GET /categories
```

**المعاملات:**
| المعامل | النوع | الوصف |
|---------|------|-------|
| `tree` | bool | إرجاع شجرة هرمية |

---

### فئة واحدة

```http
GET /categories/{slug}
```

---

### منتجات الفئة

```http
GET /categories/{slug}/products?page=1&per_page=15
```

---

## 🛒 الطلبات (Orders)

### قائمة الطلبات

```http
GET /orders
Authorization: Bearer {token}
```

**المعاملات:**
| المعامل | النوع | الوصف |
|---------|------|-------|
| `page` | int | رقم الصفحة |
| `per_page` | int | عدد لكل صفحة |
| `status` | string | فلترة حسب الحالة |

---

### إنشاء طلب

```http
POST /orders
Authorization: Bearer {token}
```

**الطلب:**
```json
{
    "items": [
        {
            "product_id": 1,
            "variant_id": 3,
            "quantity": 1,
            "player_id": "123456789"
        }
    ],
    "coupon_code": "WELCOME10",
    "payment_method": "wallet"
}
```

**الاستجابة الناجحة:**
```json
{
    "success": true,
    "message": "تم إنشاء الطلب بنجاح",
    "data": {
        "uuid": "...",
        "order_number": "ORD-20240115-ABC123",
        "status": "completed",
        "total_amount": 1899,
        "formatted_total": "18.99 ر.س",
        "items": [...],
        "delivered_codes": {
            "شحن PUBG Mobile UC - 325 UC": [
                "CODE-XXXX-XXXX-XXXX"
            ]
        }
    }
}
```

---

### تفاصيل طلب

```http
GET /orders/{uuid}
Authorization: Bearer {token}
```

---

### إلغاء طلب

```http
POST /orders/{uuid}/cancel
Authorization: Bearer {token}
```

---

## 💰 المحفظة (Wallet)

### الرصيد

```http
GET /wallet
Authorization: Bearer {token}
```

**الاستجابة:**
```json
{
    "success": true,
    "data": {
        "balance": 50000,
        "formatted_balance": "500.00 ر.س",
        "currency": "SAR"
    }
}
```

---

### المعاملات

```http
GET /wallet/transactions?page=1&type=purchase
Authorization: Bearer {token}
```

**الاستجابة:**
```json
{
    "success": true,
    "data": [
        {
            "uuid": "...",
            "type": "purchase",
            "type_label": "شراء",
            "amount": -1899,
            "formatted_amount": "-18.99 ر.س",
            "balance_after": 48101,
            "description": "طلب #ORD-20240115-ABC123",
            "created_at": "2024-01-15T12:30:00Z"
        }
    ],
    "meta": {
        "total": 25,
        "per_page": 15,
        "current_page": 1
    }
}
```

---

### طرق الإيداع

```http
GET /wallet/deposit-methods
Authorization: Bearer {token}
```

---

### إيداع

```http
POST /wallet/deposit
Authorization: Bearer {token}
```

**الطلب:**
```json
{
    "amount": 10000,
    "method": "mada"
}
```

---

## 🔔 الإشعارات (Notifications)

### قائمة الإشعارات

```http
GET /notifications?page=1
Authorization: Bearer {token}
```

---

### تعليم كمقروء

```http
POST /notifications/{uuid}/read
Authorization: Bearer {token}
```

---

### تعليم الكل كمقروء

```http
POST /notifications/read-all
Authorization: Bearer {token}
```

---

## ❌ أكواد الخطأ

| الكود | HTTP Status | الوصف |
|-------|-------------|-------|
| `VALIDATION_ERROR` | 422 | خطأ في البيانات المدخلة |
| `UNAUTHORIZED` | 401 | غير مصرح |
| `FORBIDDEN` | 403 | ممنوع |
| `NOT_FOUND` | 404 | غير موجود |
| `RATE_LIMIT_EXCEEDED` | 429 | تجاوز الحد المسموح |
| `INTERNAL_ERROR` | 500 | خطأ داخلي |

### مثال على خطأ:
```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "Validation failed",
        "details": {
            "email": "البريد الإلكتروني مستخدم مسبقاً",
            "password": "كلمة المرور ضعيفة"
        }
    }
}
```

---

## 📊 Rate Limiting

| Endpoint | الحد |
|----------|------|
| عام | 60 طلب/دقيقة |
| Login | 5 محاولات/15 دقيقة |
| API (مصادق) | 100 طلب/دقيقة |

**Headers:**
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 55
X-RateLimit-Reset: 1705312800
```

---

## 🧪 بيئة الاختبار

### Sandbox URL
```
https://sandbox.aboud-store.com/api/v1
```

### بيانات اختبار
```
Email: test@aboud-store.com
Password: Test@123456
```
