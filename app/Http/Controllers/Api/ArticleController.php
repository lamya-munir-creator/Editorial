<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use App\Http\Resources\ArticleResource;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $query = Article::with(['category', 'tags', 'author', 'featuredImage']);

        // 1. البحث النصي بالكلمات المفتاحية في العنوان والمحتوى والملخص
        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // 2. الفلترة حسب Slug التصنيف
        if ($request->filled('category_slug')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->input('category_slug'));
            });
        }

        // 3. الفلترة حسب Slug الكاتب
        if ($request->filled('author_slug')) {
            $query->whereHas('author', function ($q) use ($request) {
                $q->where('slug', $request->input('author_slug'));
            });
        }

        // 4. الفلترة حسب Slug الوسم
        if ($request->filled('tag_slug')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('slug', $request->input('tag_slug'));
            });
        }

        // 5. الفلترة حسب المقالات المميزة فقط
        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        $articles = $query->latest('published_at')->paginate($request->input('per_page', 10));

        return response()->json([
            'status' => true,
            'data'   => ArticleResource::collection($articles),
            'meta'   => [
                'current_page' => $articles->currentPage(),
                'last_page'    => $articles->lastPage(),
                'per_page'     => $articles->perPage(),
                'total'        => $articles->total(),
            ]
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'             => 'required|string|max:255',
            'content'           => 'required|string',
            'excerpt'           => 'nullable|string',
            'category_id'       => 'required|exists:categories,id',
            'featured_image_id' => 'nullable|exists:media,id',
            'meta_title'        => 'nullable|string|max:255',
            'meta_description'  => 'nullable|string|max:500',
            'reading_time'      => 'nullable|integer',
            'is_featured'       => 'nullable|boolean',
            'allow_comments'    => 'nullable|boolean',
            'status'            => 'required|in:draft,published,archived',
            'tags'              => 'nullable|array',
            'tags.*'            => 'exists:tags,id',
        ]);

        $validated['author_id'] = auth()->id() ?? 1;
        $validated['created_by'] = auth()->id() ?? 1;
        $validated['slug'] = Str::slug($request->title);
        $validated['published_at'] = ($validated['status'] === 'published') ? now() : null;

        $article = Article::create($validated);

        if ($request->has('tags')) {
            $article->tags()->attach($request->tags);
        }

        return response()->json([
            'status'  => true,
            'message' => 'تم نشر المقال بنجاح',
            'data'    => new ArticleResource($article->load(['category', 'tags', 'author', 'featuredImage']))
        ], 201);
    }

    public function show(Article $article)
    {
        // زيادة عدد المشاهدات عند قراءة التفاصيل
        $article->increment('views_count');

        return response()->json([
            'status' => true,
            'data'   => new ArticleResource($article->load(['category', 'tags', 'author', 'featuredImage']))
        ], 200);
    }

    public function update(Request $request, Article $article)
    {
        $validated = $request->validate([
            'title'             => 'sometimes|required|string|max:255',
            'content'           => 'sometimes|required|string',
            'excerpt'           => 'sometimes|nullable|string',
            'category_id'       => 'sometimes|required|exists:categories,id',
            'featured_image_id' => 'sometimes|nullable|exists:media,id',
            'meta_title'        => 'sometimes|nullable|string|max:255',
            'meta_description'  => 'sometimes|nullable|string|max:500',
            'reading_time'      => 'sometimes|nullable|integer',
            'is_featured'       => 'sometimes|nullable|boolean',
            'allow_comments'    => 'sometimes|nullable|boolean',
            'status'            => 'sometimes|required|in:draft,published,archived',
            'tags'              => 'nullable|array',
            'tags.*'            => 'exists:tags,id',
        ]);

        if (isset($validated['title'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $validated['updated_by'] = auth()->id() ?? 1;

        $article->update($validated);

        if ($request->has('tags')) {
            $article->tags()->sync($request->tags);
        }

        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث المقال بنجاح',
            'data'    => new ArticleResource($article->fresh()->load(['category', 'tags', 'author', 'featuredImage']))
        ], 200);
    }

    public function destroy(Article $article)
    {
        $article->tags()->detach();
        $article->delete();

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف المقال بنجاح'
        ], 200);
    }

    /**
     * جلب المقالات ذات الصلة في ذات التصنيف أو تشترك في الوسوم
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
            ->with(['category', 'tags', 'author', 'featuredImage'])
            ->latest('published_at')
            ->take(4)
            ->get();

        return response()->json([
            'status' => true,
            'data'   => ArticleResource::collection($relatedArticles)
        ], 200);
    }
}