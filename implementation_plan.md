# خطة تنفيذ التحديثات والتحسينات لمشروع Editorial CMS

بناءً على نتائج المقارنة في ملف `PIXELMIND_COMPARISON.md` وتوجيهات الاستكمال، تقدم هذه الخطة الخطوات البرمجية لإعادة هيكلة بعض النماذج، إضافة المهاجرات (Migrations)، إنشاء وتحديث المتحكمات (Controllers)، وتحديث نقاط النهاية (API Endpoints).

## User Review Required

> [!IMPORTANT]
> - سنقوم بإضافة ترحيلات جديدة (Migrations) لإضافة حقول SEO وصيغة WebP للوسائط دون المساس بالبيانات الحالية.
> - سيتم تزويد الـ APIs بمسارات جديدة متوافقة مع الـ Slugs واستجابة JSON-LD لدعم محركات البحث و Google AdSense.

## Proposed Changes

### 1. Database & Migrations (قواعد البيانات والترحيلات)

#### [NEW] [2026_08_01_000001_add_seo_and_webp_fields_to_tables.php](file:///c:/Users/admin/Herd/Editorial/database/migrations/2026_08_01_000001_add_seo_and_webp_fields_to_tables.php)
- إضافة حقل `webp_path` في جدول `media` لحفظ المسار المحسّن للصورة.
- إضافة حقل `schema_type` و `is_indexable` في جدول `articles`.

#### [MODIFY] [SettingSeeder.php](file:///c:/Users/admin/Herd/Editorial/database/seeders/SettingSeeder.php)
- إضافة بيانات أولية لمفاتيح خدمات جوجل (`google_analytics_id`, `google_search_console_code`, `google_adsense_client_id`).

---

### 2. Controllers & Resources (المتحكمات والـ Resources)

#### [NEW] [SitemapController.php](file:///c:/Users/admin/Herd/Editorial/app/Http/Controllers/Api/SitemapController.php)
- إنشاء متحكم لخريطة الموقع يولد استجابة XML ديناميكية تضم المقالات والأقسام والصفحات والكُتّاب.

#### [MODIFY] [ArticleController.php](file:///c:/Users/admin/Herd/Editorial/app/Http/Controllers/Api/ArticleController.php)
- إضافة البحث النصي بالكلمات المفتاحية عبر `?q=`.
- إضافة الفلترة بـ `category_slug`, `author_slug`, `tag_slug`.
- إضافة دالة `related(Article $article)` لاسترجاع المقالات ذات الصلة.

#### [MODIFY] [CategoryController.php](file:///c:/Users/admin/Herd/Editorial/app/Http/Controllers/Api/CategoryController.php)
- إضافة دالة `showBySlug(string $slug)` لإعادة التصنيف ومقالاته المنشورة.

#### [MODIFY] [AuthorController.php](file:///c:/Users/admin/Herd/Editorial/app/Http/Controllers/Api/AuthorController.php)
- إضافة دالة `showBySlug(string $slug)` لإعادة الكاتب ومقالاته المنشورة.

#### [NEW / MODIFY] [ArticleResource.php](file:///c:/Users/admin/Herd/Editorial/app/Http/Resources/ArticleResource.php)
- تضمين بيانات `schema_markup` وفق معيار `Schema.org / JSON-LD` للنتائج الغنية في جوجل.

#### [MODIFY] [MediaController.php](file:///c:/Users/admin/Herd/Editorial/app/Http/Controllers/Api/MediaController.php)
- إضافة معالجة الصور لتحويلها وحفظها بصيغة WebP لتسريع تحميل صفحات الموقع.

---

### 3. Routes & Configurations (المسارات والتكامل)

#### [MODIFY] [routes/api.php](file:///c:/Users/admin/Herd/Editorial/routes/api.php)
- إضافة المسارات الجديدة:
  - `GET /api/articles/{article}/related`
  - `GET /api/categories/slug/{slug}`
  - `GET /api/authors/slug/{slug}`
  - `GET /sitemap.xml`

---

## Verification Plan

### Automated Tests
- تشغيل اختبارات Laravel للتأكد من عدم وجود أخطاء:
  `php artisan test`

### Manual Verification
- تجربة نقاط النهاية الجديدة في الـ API والتأكد من إرجاع استجابات JSON صحيحة.
- فحص مخرجات `GET /sitemap.xml` للتحقق من صحة هيكل الـ XML.
