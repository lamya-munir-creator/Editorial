<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    /**
     * جلب المقالات العامة مع البحث والفلاتر والترتيب.
     */
    public function index(Request $request)
    {
        $query = Article::with([
            'category',
            'tags',
            'author',
            'featuredImage',
        ]);

        // البحث
        if ($request->filled('q')) {
            $search = $request->input('q');

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // التصنيف
        if ($request->filled('category_slug')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->input('category_slug'));
            });
        }

        // الكاتب
        if ($request->filled('author_slug')) {
            $query->whereHas('author', function ($q) use ($request) {
                $q->where('slug', $request->input('author_slug'));
            });
        }

        // الوسم
        if ($request->filled('tag_slug')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('slug', $request->input('tag_slug'));
            });
        }

        // الحالة
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // المميزة
        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        // الترتيب
        $sort = $request->input('sort', 'latest');

        switch ($sort) {
            case 'popular':
                $query->orderByDesc('views_count');
                break;

            case 'alphabetical':
                $query->orderBy('title');
                break;

            case 'oldest':
                $query->orderBy('published_at');
                break;

            case 'latest':
            default:
                $query->orderByDesc('published_at');
                break;
        }

        $articles = $query->paginate(
            min((int) $request->input('per_page', 10), 100)
        );

        return response()->json([
            'status' => true,
            'data' => ArticleResource::collection($articles),
            'meta' => [
                'current_page' => $articles->currentPage(),
                'last_page' => $articles->lastPage(),
                'per_page' => $articles->perPage(),
                'total' => $articles->total(),
            ],
        ]);
    }

    /**
     * عرض مقال واحد.
     */
    public function show(Article $article)
    {
        $article->increment('views_count');

        $article->load([
            'category',
            'tags',
            'author',
            'featuredImage',
        ]);

        return response()->json([
            'status' => true,
            'data' => new ArticleResource($article),
        ]);
    }

    /**
     * المقالات ذات الصلة.
     */
    public function related(Article $article)
    {
        $tagIds = $article->tags->pluck('id')->toArray();

        $relatedArticles = Article::published()
            ->where('id', '!=', $article->id)
            ->where(function ($query) use ($article, $tagIds) {
                $query->where('category_id', $article->category_id);

                if (!empty($tagIds)) {
                    $query->orWhereHas('tags', function ($q) use ($tagIds) {
                        $q->whereIn('tags.id', $tagIds);
                    });
                }
            })
            ->with([
                'category',
                'tags',
                'author',
                'featuredImage',
            ])
            ->latest('published_at')
            ->take(4)
            ->get();

        return response()->json([
            'status' => true,
            'data' => ArticleResource::collection($relatedArticles),
        ]);
    }
}