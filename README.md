# Aboud - متجر شحن العاب وتطبيقات

نظام متجر إلكتروني لشحن الألعاب والتطبيقات

## متطلبات التشغيل

- PHP 7.4 أو أحدث
- MySQL 5.7 أو أحدث
- PDO MySQL Extension

## التثبيت والإعداد

### 1. إعداد قاعدة البيانات

قم بإنشاء قاعدة بيانات MySQL جديدة:

```sql
CREATE DATABASE alraqawi_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. إعداد ملف الاتصال بقاعدة البيانات

انسخ ملف القالب وقم بتحديث بيانات الاتصال:

```bash
cp config/database.example.php config/database.php
```

ثم قم بتعديل `config/database.php` وأدخل بيانات قاعدة البيانات الخاصة بك.

### 3. البدء في التطوير

لمزيد من المعلومات حول إعداد قاعدة البيانات، راجع [config/README.md](config/README.md)

## البنية الأساسية

```
/workspace
├── config/              # ملفات الإعداد
│   ├── database.php     # اتصال قاعدة البيانات (مستثنى من Git)
│   ├── database.example.php  # قالب إعداد قاعدة البيانات
│   └── README.md        # وثائق الإعداد
├── .gitignore
├── LICENSE
└── README.md
```

## الأمان

- ملف `config/database.php` مستثنى من نظام التحكم بالنسخ لحماية بيانات الاعتماد
- يستخدم النظام PDO Prepared Statements للحماية من SQL Injection
- يُنصح بتغيير بيانات الاعتماد الافتراضية قبل النشر

## الترخيص

راجع ملف [LICENSE](LICENSE) لمزيد من التفاصيل.
