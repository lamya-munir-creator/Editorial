<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SeoController extends Controller
{
    /**
     * قائمة مفاتيح الـ SEO المعترف بها
     */
    private $seoKeys = [
        'seo_default_title',
        'seo_default_desc',
        'seo_og_title',
        'seo_og_desc',
        'seo_twitter_card',
        'seo_ga_id',
        'seo_gsc_tag',
        'seo_robots_txt',
        'seo_indexing_articles',
        'seo_indexing_categories',
        'seo_indexing_pages',
        'seo_indexing_authors',
        'seo_indexing_tags',
    ];

    /**
     * استرجاع جميع إعدادات الـ SEO
     */
    public function index()
    {
        $settings = Setting::whereIn('setting_key', $this->seoKeys)->pluck('setting_value', 'setting_key');
        
        // إعداد القيم الافتراضية في حال كانت فارغة
        $data = [
            'defaultTitle' => $settings['seo_default_title'] ?? 'مجلة لومين | المنصة الإخبارية التقنية',
            'defaultDesc'  => $settings['seo_default_desc'] ?? 'أحدث الأخبار التقنية، والمقالات العلمية، وتحليلات الأسواق.',
            'ogTitle'      => $settings['seo_og_title'] ?? 'مجلة لومين - محتوى يرتقي بك',
            'ogDesc'       => $settings['seo_og_desc'] ?? 'تابعنا لمعرفة أحدث مستجدات التقنية والعلوم.',
            'twitterCard'  => $settings['seo_twitter_card'] ?? 'summary_large_image',
            'gaId'         => $settings['seo_ga_id'] ?? '',
            'gscTag'       => $settings['seo_gsc_tag'] ?? '',
            'robotsTxt'    => $settings['seo_robots_txt'] ?? "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /search/",
            'indexing'     => [
                'articles'   => [ 'id' => 'articles', 'enabled' => filter_var($settings['seo_indexing_articles'] ?? true, FILTER_VALIDATE_BOOLEAN) ],
                'categories' => [ 'id' => 'categories', 'enabled' => filter_var($settings['seo_indexing_categories'] ?? true, FILTER_VALIDATE_BOOLEAN) ],
                'pages'      => [ 'id' => 'pages', 'enabled' => filter_var($settings['seo_indexing_pages'] ?? true, FILTER_VALIDATE_BOOLEAN) ],
                'authors'    => [ 'id' => 'authors', 'enabled' => filter_var($settings['seo_indexing_authors'] ?? false, FILTER_VALIDATE_BOOLEAN) ],
                'tags'       => [ 'id' => 'tags', 'enabled' => filter_var($settings['seo_indexing_tags'] ?? false, FILTER_VALIDATE_BOOLEAN) ],
            ]
        ];

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    /**
     * تحديث جميع إعدادات الـ SEO دفعة واحدة
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'defaultTitle' => 'nullable|string',
            'defaultDesc'  => 'nullable|string',
            'ogTitle'      => 'nullable|string',
            'ogDesc'       => 'nullable|string',
            'twitterCard'  => 'nullable|string',
            'gaId'         => 'nullable|string',
            'gscTag'       => 'nullable|string',
            'robotsTxt'    => 'nullable|string',
            'indexing'     => 'nullable|array',
        ]);

        $updates = [
            'seo_default_title' => $data['defaultTitle'] ?? '',
            'seo_default_desc'  => $data['defaultDesc'] ?? '',
            'seo_og_title'      => $data['ogTitle'] ?? '',
            'seo_og_desc'       => $data['ogDesc'] ?? '',
            'seo_twitter_card'  => $data['twitterCard'] ?? '',
            'seo_ga_id'         => $data['gaId'] ?? '',
            'seo_gsc_tag'       => $data['gscTag'] ?? '',
            'seo_robots_txt'    => $data['robotsTxt'] ?? '',
        ];

        if (isset($data['indexing'])) {
            $updates['seo_indexing_articles'] = isset($data['indexing']['articles']['enabled']) ? (string)$data['indexing']['articles']['enabled'] : '1';
            $updates['seo_indexing_categories'] = isset($data['indexing']['categories']['enabled']) ? (string)$data['indexing']['categories']['enabled'] : '1';
            $updates['seo_indexing_pages'] = isset($data['indexing']['pages']['enabled']) ? (string)$data['indexing']['pages']['enabled'] : '1';
            $updates['seo_indexing_authors'] = isset($data['indexing']['authors']['enabled']) ? (string)$data['indexing']['authors']['enabled'] : '0';
            $updates['seo_indexing_tags'] = isset($data['indexing']['tags']['enabled']) ? (string)$data['indexing']['tags']['enabled'] : '0';
        }

        foreach ($updates as $key => $value) {
            Setting::updateOrCreate(
                ['setting_key' => $key],
                [
                    'setting_value' => $value,
                    'group_name' => 'seo',
                    'updated_by' => auth()->id() ?? 1,
                    'is_public' => true
                ]
            );
        }

        return response()->json([
            'status' => true,
            'message' => 'تم حفظ إعدادات الـ SEO بنجاح'
        ]);
    }
}
