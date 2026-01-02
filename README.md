# 🎮 ABOUD Store - متجر شحن ألعاب وتطبيقات

<div align="center">

![ABOUD Store](https://img.shields.io/badge/ABOUD-Store-6366f1?style=for-the-badge)
![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?style=for-the-badge&logo=php)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)

**نظام متجر شحن ألعاب وتطبيقات احترافي ومتكامل**

[المميزات](#-المميزات) •
[التثبيت](#-التثبيت) •
[الهيكلية](#-هيكلية-المشروع) •
[API](#-توثيق-api) •
[الأمان](#-الأمان)

</div>

---

## 📋 نظرة عامة

ABOUD Store هو نظام متجر إلكتروني متكامل لبيع وشحن بطاقات الألعاب والتطبيقات. تم بناؤه بأعلى معايير الأمان والأداء، وهو جاهز للإنتاج والتوسع.

## ✨ المميزات

### 🎯 المميزات الرئيسية
- ✅ **نظام شحن فوري** - توصيل الأكواد تلقائياً
- ✅ **محفظة إلكترونية** - إيداع وسحب وإدارة الرصيد
- ✅ **بوابات دفع متعددة** - Stripe, PayPal, مدى, STC Pay, Apple Pay
- ✅ **نظام كوبونات** - خصومات نسبية وثابتة
- ✅ **لوحة تحكم متكاملة** - إدارة كاملة للمتجر
- ✅ **API متكامل** - REST API مع JWT Authentication
- ✅ **دعم RTL** - واجهة عربية كاملة

### 🔒 مميزات الأمان
- ✅ **Argon2ID Password Hashing** - أقوى خوارزمية تشفير كلمات المرور
- ✅ **AES-256-GCM Encryption** - تشفير البيانات الحساسة
- ✅ **SQL Injection Protection** - Prepared Statements فقط
- ✅ **XSS Protection** - تنظيف المدخلات والمخرجات
- ✅ **CSRF Protection** - حماية من طلبات التزوير
- ✅ **Rate Limiting** - حماية من هجمات Brute Force
- ✅ **Secure Sessions** - إدارة جلسات آمنة
- ✅ **JWT Authentication** - مصادقة API آمنة

### 🚀 مميزات الأداء
- ✅ **Query Optimization** - استعلامات محسّنة مع Indexing
- ✅ **Lazy Loading** - تحميل الموارد عند الحاجة
- ✅ **Caching Ready** - جاهز للتخزين المؤقت
- ✅ **Database Transactions** - ضمان سلامة البيانات

## 📦 التثبيت

### المتطلبات
- PHP 8.1 أو أحدث
- MySQL 8.0 أو أحدث
- Apache/Nginx مع mod_rewrite
- Composer (اختياري)

### خطوات التثبيت

```bash
# 1. استنساخ المشروع
git clone https://github.com/your-username/aboud-store.git
cd aboud-store

# 2. إعداد ملف البيئة
cp .env.example .env
# قم بتحرير .env وإضافة بيانات الاتصال

# 3. إنشاء قاعدة البيانات
mysql -u root -p < database/migrations/001_create_database.sql

# 4. إعداد الصلاحيات
chmod -R 755 storage/
chmod -R 755 public/uploads/

# 5. توجيه الخادم إلى مجلد public/
```

### إعداد Apache

```apache
<VirtualHost *:80>
    ServerName aboud-store.local
    DocumentRoot /path/to/aboud-store/public
    
    <Directory /path/to/aboud-store/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### إعداد Nginx

```nginx
server {
    listen 80;
    server_name aboud-store.local;
    root /path/to/aboud-store/public;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## 📁 هيكلية المشروع

```
aboud-store/
├── api/                    # API Controllers
│   ├── AuthApiController.php
│   ├── OrderApiController.php
│   ├── ProductApiController.php
│   └── WalletApiController.php
├── config/                 # ملفات التكوين
│   ├── app.php            # الإعدادات الرئيسية
│   ├── database.php       # إعدادات قاعدة البيانات
│   └── routes.php         # تعريف المسارات
├── controllers/            # Web Controllers
├── core/                   # النواة الأساسية
│   ├── Application.php    # حاوية التطبيق
│   ├── Database.php       # اتصال قاعدة البيانات
│   ├── Router.php         # نظام التوجيه
│   ├── Security.php       # خدمات الأمان
│   ├── Session.php        # إدارة الجلسات
│   ├── CsrfProtection.php # حماية CSRF
│   ├── RateLimiter.php    # تحديد المعدل
│   ├── JWT.php            # JSON Web Tokens
│   ├── Validator.php      # التحقق من المدخلات
│   ├── Model.php          # نموذج قاعدي
│   └── Response.php       # بناء الاستجابات
├── database/
│   ├── migrations/        # ملفات الترحيل
│   └── seeds/             # بيانات تجريبية
├── middleware/             # طبقات الوسيطة
│   ├── AuthMiddleware.php
│   ├── ApiAuthMiddleware.php
│   ├── CsrfMiddleware.php
│   └── AdminMiddleware.php
├── models/                 # نماذج البيانات
│   ├── User.php
│   ├── Product.php
│   ├── Order.php
│   └── Category.php
├── public/                 # الملفات العامة
│   ├── index.php          # نقطة الدخول
│   ├── css/
│   ├── js/
│   └── images/
├── services/               # خدمات الأعمال
│   └── AuthService.php
├── storage/                # الملفات المُنشأة
│   ├── logs/
│   ├── cache/
│   └── sessions/
├── views/                  # القوالب
│   ├── layouts/
│   ├── pages/
│   ├── auth/
│   └── errors/
├── .env.example
├── .gitignore
└── README.md
```

## 📡 توثيق API

### المصادقة

#### تسجيل الدخول
```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123"
}
```

**الاستجابة:**
```json
{
  "success": true,
  "message": "تم تسجيل الدخول بنجاح",
  "data": {
    "user": { ... },
    "access_token": "eyJ...",
    "refresh_token": "eyJ...",
    "token_type": "Bearer",
    "expires_in": 3600
  }
}
```

#### تسجيل جديد
```http
POST /api/v1/auth/register
Content-Type: application/json

{
  "username": "newuser",
  "email": "user@example.com",
  "password": "StrongP@ss123",
  "password_confirmation": "StrongP@ss123"
}
```

### المنتجات

#### قائمة المنتجات
```http
GET /api/v1/products?page=1&per_page=15&category_id=1
```

#### منتج واحد
```http
GET /api/v1/products/{uuid}
```

### الطلبات (تتطلب مصادقة)

#### إنشاء طلب
```http
POST /api/v1/orders
Authorization: Bearer {token}
Content-Type: application/json

{
  "items": [
    {
      "product_id": 1,
      "quantity": 2,
      "player_id": "123456789"
    }
  ],
  "payment_method": "wallet"
}
```

### المحفظة

#### الرصيد
```http
GET /api/v1/wallet
Authorization: Bearer {token}
```

#### المعاملات
```http
GET /api/v1/wallet/transactions?page=1
Authorization: Bearer {token}
```

## 🔒 الأمان

### كلمات المرور
نستخدم **Argon2ID** لتشفير كلمات المرور:
- مقاوم لهجمات GPU
- مقاوم للهجمات الجانبية
- يستهلك ذاكرة عالية لمنع هجمات ASIC

### التشفير
البيانات الحساسة (مثل أكواد الشحن) مشفرة بـ **AES-256-GCM**:
- تشفير مع مصادقة
- يضمن السرية وسلامة البيانات

### Rate Limiting
حماية من الهجمات:
- 60 طلب/دقيقة للـ API العام
- 5 محاولات تسجيل دخول / 15 دقيقة
- قفل الحساب بعد 5 محاولات فاشلة

### Headers الأمان
```
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
X-Content-Type-Options: nosniff
Content-Security-Policy: ...
Referrer-Policy: strict-origin-when-cross-origin
```

## 📊 قاعدة البيانات

### الجداول الرئيسية

| الجدول | الوصف |
|--------|-------|
| `users` | المستخدمين مع التحقق الثنائي |
| `categories` | فئات المنتجات (هرمية) |
| `products` | المنتجات والألعاب |
| `product_variants` | فئات المنتج (100 UC, 500 UC) |
| `stock_codes` | أكواد الشحن (مشفرة) |
| `orders` | الطلبات |
| `order_items` | عناصر الطلب |
| `transactions` | معاملات المحفظة |
| `coupons` | الكوبونات |
| `rate_limits` | تتبع Rate Limiting |
| `activity_logs` | سجل النشاطات |

### العلاقات
- User → Orders (1:N)
- Category → Products (1:N)
- Product → Variants (1:N)
- Order → Items (1:N)

## 🛠️ التطوير

### إضافة Controller جديد

```php
<?php
namespace Controllers;

use Controllers\BaseController;

class NewController extends BaseController
{
    public function index()
    {
        $data = ['title' => 'صفحة جديدة'];
        return $this->view('pages.new', $data);
    }
}
```

### إضافة Model جديد

```php
<?php
namespace Models;

use Core\Model;

class NewModel extends Model
{
    protected static string $table = 'table_name';
    protected static array $fillable = ['field1', 'field2'];
    protected static bool $softDeletes = true;
}
```

### إضافة Route جديد

```php
// في config/routes.php
$router->get('/new-route', 'NewController@index');
$router->post('/new-route', 'NewController@store')->middleware('AuthMiddleware');
```

## 📝 الترخيص

هذا المشروع مرخص تحت [MIT License](LICENSE).

## 👥 المساهمة

المساهمات مرحب بها! يرجى فتح Issue أو Pull Request.

## 📞 الدعم

للدعم والاستفسارات:
- 📧 Email: support@aboud-store.com
- 💬 Discord: discord.gg/aboud-store
- 🐦 Twitter: @AboudStore

---

<div align="center">
صُنع بـ ❤️ في السعودية
</div>
