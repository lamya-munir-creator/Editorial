<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Author;
use App\Models\Media;
use Illuminate\Http\Request;
use App\Http\Resources\ArticleResource;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
   public function index(Request $request)
{
    $query = Article::with(['category', 'tags', 'author', 'featuredImage']);

    // 1. البحث النصي
    if ($request->filled('q')) {
        $search = $request->input('q');

        $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('excerpt', 'like', "%{$search}%")
              ->orWhere('content', 'like', "%{$search}%");
        });
    }

    // 2. حسب التصنيف
    if ($request->filled('category_slug')) {
        $query->whereHas('category', function ($q) use ($request) {
            $q->where('slug', $request->input('category_slug'));
        });
    }

    // 3. حسب الكاتب
    if ($request->filled('author_slug')) {
        $query->whereHas('author', function ($q) use ($request) {
            $q->where('slug', $request->input('author_slug'));
        });
    }

    // 4. حسب الوسم
    if ($request->filled('tag_slug')) {
        $query->whereHas('tags', function ($q) use ($request) {
            $q->where('slug', $request->input('tag_slug'));
        });
    }

    // 5. حسب الحالة
    if ($request->filled('status')) {
        $query->where('status', $request->input('status'));
    }

    // 6. المقالات المميزة فقط
    if ($request->boolean('featured')) {
        $query->where('is_featured', true);
    }
// 7. مقالات الكاتب الحالي
if ($request->boolean('mine')) {
    $user = auth()->user();

    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => 'يجب تسجيل الدخول أولاً.'
        ], 401);
    }

    $author = $user->authorProfile;

    if (!$author) {
        return response()->json([
            'status' => true,
            'data' => [],
            'meta' => [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $request->input('per_page', 10),
                'total' => 0,
            ]
        ], 200);
    }

    $query->where('author_id', $author->id);
}
    $articles = $query
        ->latest('published_at')
        ->paginate($request->input('per_page', 10));

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

    /**
     * إنشاء وحفظ مقال جديد مع التحقق عبر StoreArticleRequest ومعالجة رفع صورة الغلاف.
     */
    public function store(StoreArticleRequest $request)
    {
        // 1. التحقق من صلاحيات إنشاء مقال عبر ArticlePolicy
        // $this->authorize('create', Article::class);

        // استقبال البيانات بعد التحقق الآلي في StoreArticleRequest
        $validated = $request->validated();

        // 2. إيجاد أو إنشاء سجل الكاتب بجدول authors للربط الصحيح مع قاعدة البيانات
        $user = auth()->user();
$author = $user?->authorProfile;

if (!$author) {
    return response()->json([
        'status' => false,
        'message' => 'لا يوجد ملف كاتب معتمد لهذا الحساب.'
    ], 403);
}

$validated['author_id']  = $author->id;
$validated['created_by'] = $user->id;
$validated['slug']       = Str::slug($request->title) . '-' . Str::random(5);
        $validated['published_at'] = ($validated['status'] === 'published') ? now() : null;

        // =========================================================================
        // إضافة جديدة: معالجة رفع صورة الغلاف تلقائياً إن أرسلت كملف (image file)
        // =========================================================================
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = $file->store('articles', 'public');
            
            // إنشاء سجل في جدول الميديا وربطه بالمقال
            $media = Media::create([
                'uuid'          => (string) Str::uuid(),
                'uploaded_by'   => auth()->id() ?? 1,
                'file_name'     => basename($path),
                'original_name' => $file->getClientOriginalName(),
                'disk'          => 'public',
                'path'          => $path,
                'webp_path'     => $path,
                'mime_type'     => $file->getClientMimeType(),
                'extension'     => $file->getClientOriginalExtension(),
                'file_size'     => $file->getSize(),
                'type'          => 'image',
                'visibility'    => 'public',
                'created_by'    => auth()->id() ?? 1,
            ]);

            $validated['featured_image_id'] = $media->id;
        }

        $article = Article::create($validated);

        if ($request->has('tags')) {
            $article->tags()->attach($request->tags);
        }

        return response()->json([
            'status'  => true,
            'message' => __('Article created successfully'),
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

    /**
     * تحديث مقال مع التحقق عبر UpdateArticleRequest ومعالجة تحديث صورة الغلاف.
     */
    public function update(UpdateArticleRequest $request, Article $article)
    {
        // 1. التحقق من سياسة الملكية والصلاحية للمقال قبل التعديل
        $this->authorize('update', $article);

        // استقبال البيانات بعد التحقق من UpdateArticleRequest
        $validated = $request->validated();

        if (isset($validated['title'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $validated['updated_by'] = auth()->id() ?? 1;

        // =========================================================================
        // إضافة جديدة: معالجة تحديث أو رفع صورة غلاف جديدة إن أرسلت كملف (image)
        // =========================================================================
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = $file->store('articles', 'public');

            $media = Media::create([
                'uuid'          => (string) Str::uuid(),
                'uploaded_by'   => auth()->id() ?? 1,
                'file_name'     => basename($path),
                'original_name' => $file->getClientOriginalName(),
                'disk'          => 'public',
                'path'          => $path,
                'webp_path'     => $path,
                'mime_type'     => $file->getClientMimeType(),
                'extension'     => $file->getClientOriginalExtension(),
                'file_size'     => $file->getSize(),
                'type'          => 'image',
                'visibility'    => 'public',
                'created_by'    => auth()->id() ?? 1,
            ]);

            $validated['featured_image_id'] = $media->id;
        }

        $article->update($validated);

        if ($request->has('tags')) {
            $article->tags()->sync($request->tags);
        }

        return response()->json([
            'status'  => true,
            'message' => __('Article updated successfully'),
            'data'    => new ArticleResource($article->fresh()->load(['category', 'tags', 'author', 'featuredImage']))
        ], 200);
    }

    public function destroy(Article $article)
    {
        // 1. التحقق من سياسة الحذف عبر ArticlePolicy
        $this->authorize('delete', $article);

        $article->tags()->detach();
        $article->delete();

        return response()->json([
            'status'  => true,
            'message' => __('Article deleted successfully')
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