<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Comment;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        // 1. Basic Stats
        $totalArticles = Article::count();
        $publishedArticles = Article::where('status', 'published')->count();
        $draftArticles = Article::where('status', 'draft')->count();
        $scheduledArticles = Article::where('status', 'published')->where('published_at', '>', now())->count(); // Assuming scheduled means published but published_at is in future, or just return 0 if not handled
        $totalViews = Article::sum('views_count');
        $liveVisitors = DB::table('sessions')->where('last_activity', '>=', time() - 900)->count();


        // 2. Top Read Articles (Trending)
        $topArticles = Article::with('author.user:id,first_name,last_name')
            ->orderBy('views_count', 'desc')
            ->take(4)
            ->get()
            ->map(function ($article, $index) {
                return [
                    'rank' => $index + 1,
                    'title' => $article->title,
                    'author' => $article->author ? ($article->author->user?->first_name . ' ' . $article->author->user?->last_name) : 'مجهول',
                    'views' => number_format($article->views_count) . ' مشاهدة',
                    'trend' => 'trending_up', // Simplified
                    'trendColor' => 'text-success'
                ];
            });

                // 3. Latest Activities
        $latestActivities = \App\Models\ActivityLog::with('user')->orderBy('id', 'desc')->take(4)->get()->map(function($log) {

            
            // تحديد لون النقطة بناءً على نوع النشاط
            $dotBg = 'bg-stone-500'; // الافتراضي رمادي
            
            if (in_array($log->action_type, ['add_article', 'publish_article'])) {
                $dotBg = 'bg-emerald-500'; // أخضر لعمليات الإضافة والنشر
            } elseif (in_array($log->action_type, ['delete_article', 'delete_user'])) {
                $dotBg = 'bg-red-500'; // أحمر لعمليات الحذف
            } elseif (in_array($log->action_type, ['edit_article', 'edit_user'])) {
                $dotBg = 'bg-blue-500'; // أزرق للتعديلات
            } elseif ($log->action_type == 'system_settings') {
                $dotBg = 'bg-amber-500'; // أصفر للإعدادات
            } elseif (in_array($log->action_type, ['login', 'logout'])) {
                $dotBg = 'bg-[#8c6239]'; // بني لتسجيل الدخول والخروج
            }

            $userName = $log->user ? $log->user->first_name . ' ' . $log->user->last_name : 'مستخدم غير معروف';

            return [
                'title' => $log->action_label ?? 'نشاط جديد',
                'info' => $userName . ' • ' . $log->created_at->diffForHumans(),
                'dotBg' => $dotBg
            ];
        });


            return response()->json([
            'stats' => [
                'total_views' => $totalViews,
                'live_visitors' => $liveVisitors,
                'articles' => [
                    'published' => $publishedArticles,
                    'drafts' => $draftArticles,
                ]
            ],
            'top_articles' => $topArticles,
            'latest_activities' => $latestActivities
        ]);
    }
}
