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
        $activities = collect();

        // Get latest 2 articles
        $latestArticles = Article::with('author.user')->latest()->take(2)->get();
        foreach ($latestArticles as $article) {
            $authorName = $article->author ? ($article->author->user?->first_name . ' ' . $article->author->user?->last_name) : 'مجهول';
            $activities->push([
                'title' => $article->status === 'published' ? 'تم نشر مقال جديد' : 'تم إضافة مسودة مقال',
                'info' => "المقال: \"{$article->title}\" • " . $article->created_at->diffForHumans(),
                'dotBg' => $article->status === 'published' ? 'bg-[#8c6239]' : 'bg-amber-500',
                'created_at' => $article->created_at
            ]);
        }

        // Get latest 2 contact messages
        $latestMessages = ContactMessage::latest()->take(2)->get();
        foreach ($latestMessages as $message) {
            $activities->push([
                'title' => 'رسالة تواصل جديدة',
                'info' => "من {$message->name} • " . $message->created_at->diffForHumans(),
                // تغيير اللون هنا
                'dotBg' => 'bg-emerald-500',
                'created_at' => $message->created_at
            ]);
        }

        // Get latest 2 comments
        $latestComments = Comment::with('user')->latest()->take(2)->get();
        foreach ($latestComments as $comment) {
            $userName = $comment->user ? $comment->user->first_name : 'زائر';
            $activities->push([
                'title' => 'تعليق جديد',
                'info' => "بواسطة {$userName} • " . $comment->created_at->diffForHumans(),
                'dotBg' => 'bg-stone-500',
                'created_at' => $comment->created_at
            ]);
        }


        // Sort activities by date descending and take top 4
        $latestActivities = $activities->sortByDesc('created_at')->take(4)->values()->map(function ($item) {
            unset($item['created_at']);
            return $item;
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
