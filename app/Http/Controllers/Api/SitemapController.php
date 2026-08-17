<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
use App\Models\Author;
use App\Models\Setting;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * OU+O'O O U^OOO_USO OrOUSOOc O U,U.U^U,O1 O U,O_USU+O U.USUUSOc Sitemap.xml
     */
    public function index()
    {
        // جلب الإعدادات الخاصة بالأرشفة
        $indexArticles = filter_var(Setting::get('seo_indexing_articles', true), FILTER_VALIDATE_BOOLEAN);
        $indexCategories = filter_var(Setting::get('seo_indexing_categories', true), FILTER_VALIDATE_BOOLEAN);
        $indexPages = filter_var(Setting::get('seo_indexing_pages', true), FILTER_VALIDATE_BOOLEAN);
        $indexAuthors = filter_var(Setting::get('seo_indexing_authors', false), FILTER_VALIDATE_BOOLEAN);

        $baseUrl = config('app.url', 'https://editorial.test');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        // 1. O U,OU?O-Oc O U,OOUSO3USOc
        $xml .= '<url>';
        $xml .= '<loc>' . htmlspecialchars($baseUrl) . '</loc>';
        $xml .= '<changefreq>daily</changefreq>';
        $xml .= '<priority>1.0</priority>';
        $xml .= '</url>';

        // 2. المقالات
        if ($indexArticles) {
            $articles = Article::published()->latest('published_at')->get();
            foreach ($articles as $article) {
                $xml .= '<url>';
                $xml .= '<loc>' . htmlspecialchars($baseUrl . '/articles/' . $article->slug) . '</loc>';
                $xml .= '<lastmod>' . ($article->updated_at ? $article->updated_at->toAtomString() : now()->toAtomString()) . '</lastmod>';
                $xml .= '<changefreq>weekly</changefreq>';
                $xml .= '<priority>0.8</priority>';
                $xml .= '</url>';
            }
        }

        // 3. التصنيفات
        if ($indexCategories) {
            $categories = Category::where('is_active', true)->get();
            foreach ($categories as $category) {
                $xml .= '<url>';
                $xml .= '<loc>' . htmlspecialchars($baseUrl . '/categories/' . $category->slug) . '</loc>';
                $xml .= '<changefreq>weekly</changefreq>';
                $xml .= '<priority>0.7</priority>';
                $xml .= '</url>';
            }
        }

        // 4. الصفحات الثابتة
        if ($indexPages) {
            $pages = Page::where('status', 'published')->get();
            foreach ($pages as $page) {
                $xml .= '<url>';
                $xml .= '<loc>' . htmlspecialchars($baseUrl . '/pages/' . $page->slug) . '</loc>';
                $xml .= '<lastmod>' . ($page->updated_at ? $page->updated_at->toAtomString() : now()->toAtomString()) . '</lastmod>';
                $xml .= '<changefreq>monthly</changefreq>';
                $xml .= '<priority>0.6</priority>';
                $xml .= '</url>';
            }
        }

        // 5. الكتاب
        if ($indexAuthors) {
            $authors = Author::all();
            foreach ($authors as $author) {
                $xml .= '<url>';
                $xml .= '<loc>' . htmlspecialchars($baseUrl . '/authors/' . $author->slug) . '</loc>';
                $xml .= '<changefreq>monthly</changefreq>';
                $xml .= '<priority>0.5</priority>';
                $xml .= '</url>';
            }
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }
}
