# 📰 Editorial CMS API - نظام إدارة المحتوى والمجلة الرقمية المتكاملة

[![Laravel Framework](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com)
[![PHP Version](https://img.shields.io/badge/PHP-8.3%2B-777BB4?style=for-the-badge&logo=php)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)
[![Google AdSense Ready](https://img.shields.io/badge/Google_AdSense-Ready-4285F4?style=for-the-badge&logo=googleads)](https://adsense.google.com)
[![SEO Optimized](https://img.shields.io/badge/SEO-Optimized-00C7B7?style=for-the-badge)](https://schema.org)

---

## 📌 نظرة عامة على المشروع (Overview)

مشروع **Editorial CMS API** هو نظام خلفية (Backend RESTful API) احترافي متكامل لإدارة الصحف، والمجلات الرقمية، ومواقع المقالات الإخبارية. تم تطويره باستخدام إطار العمل **Laravel 12** و **PHP 8.3** ليوفر منصة عالية الأداء وسريعة التحميل وقابلة للتوسع والتكامل مع أي واجهة أمامية (Next.js, Vue.js, React, Vanilla JS / Quick Frontend) أو تطبيقات الهواتف المحمولة.

تم تصميم النظام ليكون **مطابقاً ومؤهلاً 100% لمعايير القبول المباشر في برنامج Google AdSense** واستيفاء متطلبات محركات البحث (SEO Best Practices).

---

## ✨ المميزات والخصائص الرئيسية (Key Features)

- 📰 **إدارة كاملة للمقالات (Article Management):** لدعم حالات النشر (مسودة، منشور، مؤرشف)، تحديد المقالات المميزة، التوزيع الزمني للنشر (`published_at`)، وحساب وقت القراءة التلقائي.
- 🗂️ **تصنيفات ووسوم هرمية (Categories & Tags):** دعم الأقسام الرئيسية والفرعية مع الأيقونات والرتيب والـ Slugs الصديقة لمحركات البحث.
- ✍️ **إدارة الكُتّاب والصحفيين (Author Profiles):** ملفات تعريفية شاملة للكُتّاب تشمل السيرة الذاتية، الصورة الشخصية، ووسائل التواصل الاجتماعي.
- 💰 **إدارة المساحات الإعلانية (AdSense & Ads Management):** دعم شامل لمواقع الإعلانات (Header, Sidebar, In-Article, Footer) وأكواد AdSense وتتبع النقرات والانطباعات.
- 🔍 **محرك بحث وفلترة متطور:** فلترة المقالات بالكلمات المفتاحية (`?q=`)، و Slugs الأقسام، والكُتّاب، والوسوم، وطلب المقالات ذات الصلة (`Related Articles`).
- 🌐 **خريطة موقع ديناميكية (`Sitemap.xml`):** توليد تلقائي لخريطة XML خفيفة وسريعة لجميع المقالات، الأقسام، الصفحات، والكُتّاب لتقديمها في Google Search Console.
- 📊 **دعم البيانات المنظمة (`Schema.org / JSON-LD`):** إدراج هيكلية `NewsArticle` تلقائياً في استجابات المقالات للظهور في نتائج جوجل الغنية (Rich Snippets) وأخبار جوجل.
- 🖼️ **تحسين وحفظ الصور بصيغة `WebP`:** تحويل تلقائي للصور المرفوعة لصيغة خفيفة جداً لتسريع زمن التحميل ورفع تقييم Google PageSpeed.
- ✉️ **نظام التواصل والنشرة البريدية:** استقبال رسائل الزوار والرد البريدي المباشر، وإدارة المشتركين مع خيار إلغاء الاشتراك بنقرة واحدة.
- 🔒 **مصادقة وحماية متقدمة:** استخدام **Laravel Sanctum** واستخدام الـ UUIDs لعدم كشف معرفات قاعدة البيانات مباشرة.

---

## 🛠️ التقنيات ومتطلبات التشغيل (Tech Stack & Requirements)

### التقنيات المستخدمة:
- **Language:** PHP 8.3+
- **Framework:** Laravel 12.x
- **Authentication:** Laravel Sanctum
- **Database:** SQLite / MySQL / MariaDB
- **SEO & Markup:** Schema.org (JSON-LD), Dynamic XML Sitemap

### المتطلبات المسبقة (Prerequisites):
- PHP >= 8.3 مع التمديدات التالية: (`pdo`, `mbstring`, `openssl`, `tokenizer`, `xml`, `gd`)
- Composer >= 2.x
- Node.js >= 18.x & NPM
- MySQL أو SQLite

---

## 🚀 خطوات التثبيت والتشغيل المحلي (Installation & Setup)

اتبع الخطوات التالية لتشغيل المشروع في بيئة التطوير المحلية:

### 1. استنساخ المشروع (Clone Repository)
```bash
git clone https://github.com/lamya-munir-creator/Editorial.git
cd Editorial
```

### 2. تثبيت الاعتماديات (Install Dependencies)
```bash
composer install
npm install
```

### 3. إعداد ملف البيئة (Environment Configuration)
قم بإنشاء نسخة من ملف البيئة وتوليد مفتاح التطبيق:
```bash
cp .env.example .env
php artisan key:generate
```

قم بضبط إعدادات قاعدة البيانات في ملف `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=editorial
DB_USERNAME=root
DB_PASSWORD=
```

### 4. تشغيل الترحيلات وتغذية البيانات (Migrations & Seeders)
قم بتشغيل الترحيلات وإنشاء الجداول وتغذية إعدادات أدوات جوجل:
```bash
php artisan migrate --force
php artisan db:seed --class=SettingSeeder
```

### 5. إنشاء رابط مجلد التخزين للوسائط (Storage Link)
```bash
php artisan storage:link
```

### 6. تشغيل سيرفر التطوير (Run Development Server)
```bash
php artisan serve
```
سيكون التطبيق متاحاً على الرابط: `http://127.0.0.1:8000`

---

## 📂 هيكلية قاعدة البيانات والنماذج (Database Models)

يحتوي النظام على **15 نموذجاً رئيساً (Eloquent Models)**:

1. **`Article`:** إدارة المقالات (العنوان، Slug، المحتوى، الوقت المتوقع للقراءة، SEO Meta، حالة النشر، المشاهدات).
2. **`Category`:** إدارة الأقسام والتصنيفات الهرمية.
3. **`Tag`:** إدارة الوسوم.
4. **`Author`:** ملفات الكُتّاب والصحفيين وصورهم وروابطهم.
5. **`Comment`:** تعليقات القراء والردود الهرمية وحالات المراجعة (`pending`, `approved`).
6. **`Page`:** الصفحات الثابتة (عن الموقع، الخصوصية، الشروط، والصفحة الرئيسية `is_homepage`).
7. **`Advertisement`:** نظام المساحات الإعلانية وأكواد Google AdSense.
8. **`ContactMessage`:** رسائل التواصل وإمكانية الرد البريدي.
9. **`NewsletterSubscriber`:** مشتركو النشرة الإخبارية وتوكينات إلغاء الاشتراك.
10. **`Media`:** مركز إدارة الملفات والصور ونشر صيغة `WebP`.
11. **`Menu` & `MenuItem`:** القوائم التفاعلية للهيدر والفوتر.
12. **`Setting`:** الإعدادات الديناميكية للموقع وأكواد تتبع جوجل.
13. **`User` & `Role`:** المستخدمين والأدوار والتراخيص.

---

## 🌐 دليل نقاط النهاية للـ API (API Endpoints Documentation)

جميع نقاط النهاية تبدأ بالبادئة `/api`:

### 📰 1. المقالات (Articles)
- `GET /api/articles` - جلب المقالات مع دعم الفلترة والبحث (`?q=كلمة_البحث`, `?category_slug=...`, `?author_slug=...`, `?tag_slug=...`, `?featured=1`).
- `POST /api/articles` - إضافة مقال جديد.
- `GET /api/articles/{id}` - عرض تفاصيل المقال مع الـ Schema Markup وزيادة عدد المشاهدات تلقائياً.
- `PUT/PATCH /api/articles/{id}` - تحديث بيانات المقال والوسوم.
- `DELETE /api/articles/{id}` - حذف المقال مرناً (Soft Delete).
- `GET /api/articles/{id}/related` - جلب المقالات ذات الصلة بنفس التصنيف أو الوسوم.

### 🗂️ 2. الأقسام (Categories)
- `GET /api/categories` - عرض كافة الأقسام النشطة.
- `POST /api/categories` - إنشاء قسم جديد.
- `GET /api/categories/slug/{slug}` - جلب تفاصيل القسم ومقالاته المنشورة بواسطة الـ Slug.
- `PUT/PATCH /api/categories/{id}` - تحديث قسم.
- `DELETE /api/categories/{id}` - حذف قسم.

### ✍️ 3. الكُتّاب (Authors)
- `GET /api/authors` - جلب قائمة الكُتّاب مع البحث وعدد مقالات كل كاتب.
- `GET /api/authors/slug/{slug}` - جلب تفاصيل الكاتب ومقالاته المنشورة بواسطة الـ Slug.
- `POST /api/authors` - إضافة كاتب جديد.
- `PUT/PATCH /api/authors/{id}` - تحديث بيانات الكاتب.

### 📄 4. الصفحات والإعلانات (Pages & Advertisements)
- `GET /api/pages/homepage` - جلب بيانات الصفحة الرئيسية المنشورة.
- `GET /api/pages/slug/{slug}` - جلب الصفحة عن طريق الـ Slug.
- `GET /api/advertisements` - جلب المساحات الإعلانية المفعّلة وصورها/أكوادها.
- `POST /api/advertisements` - إضافة مساحة إعلانية جديدة.

### 🗺️ 5. خريطة الموقع والـ SEO (Sitemap & SEO)
- `GET /sitemap.xml` أو `GET /api/sitemap.xml` - خريطة الموقع الديناميكية بنمط XML لتوجيه محركات البحث.

### 📩 6. التواصل والنشرة البريدية (Contact & Newsletter)
- `POST /api/contact-messages` - إرسال رسالة تواصل من الزائر.
- `POST /api/contact-messages/{id}/reply` - إرسال رد بريدي مخصص للزائر وتغيير حالة الرسالة إلى `replied`.
- `POST /api/newsletter-subscribers` - الاشتراك في النشرة البريدية.
- `POST /api/newsletter-subscribers/unsubscribe` - إلغاء الاشتراك.

---

## 📈 الربط والتكامل مع خدمات Google (Google Integration)

تستطيع إدارة مفاتيح الربط مع خدمات جوجل مباشرة عبر لوحة التحكم أو جدول `settings`:

1. **Google Search Console:**
   - رابط خريطة الموقع الجاهز للارتباط هو: `https://your-domain.com/sitemap.xml`
2. **Google Analytics (GA4):**
   - حفظ المعرف الخاص بك في المفتاح `google_analytics_id` (مثل: `G-XXXXXXXXXX`).
3. **Google AdSense:**
   - حفظ معرف الناشر في المفتاح `google_adsense_client_id` (مثل: `ca-pub-XXXXXXXXXXXXXXXX`).
   - تفعيل الإعلانات التلقائية عبر المفتاح `google_adsense_auto_ads`.

---

## 🧪 التشغيل والاختبار الآلي (Testing)

لتشغيل مجموعة الاختبارات الآلية التأكد من سلامة النظام:
```bash
php artisan test
```

---

## 📄 الترخيص (License)

هذا المشروع مرخص تحت رخصة **[MIT License](LICENSE)**.
