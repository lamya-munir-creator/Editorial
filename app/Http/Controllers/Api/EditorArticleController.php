<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use Illuminate\Support\Str;
use App\Models\Media;
use App\Models\Article;
use Illuminate\Http\Request;

class EditorArticleController extends Controller
{
    /**
     * جلب جميع المقالات للمحرر
     */
       public function index(Request $request)
    {
        // المحرر يستطيع رؤية جميع المقالات
        $query = Article::with(['category', 'tags', 'author', 'featuredImage']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        
        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where('title', 'like', "%{$search}%");
        }

        // فلترة التصنيف
        if ($request->filled('category_slug')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->input('category_slug'));
            });
        }

        // فلترة الكاتب
        if ($request->filled('author_slug')) {
            $query->whereHas('author', function ($q) use ($request) {
                $q->where('slug', $request->input('author_slug'));
            });
        }

        $query->orderByDesc('created_at');

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
     * عرض مقال
     */
    public function show(Article $article)
    {
        $article->load(['category', 'tags', 'author', 'featuredImage', 'submittedForReviewBy', 'reviewer']);

        return response()->json([
            'status' => true,
            'data' => new ArticleResource($article),
        ]);
    }

    /**
     * إنشاء مقال (مدير/محرر)
     */
    public function store(StoreArticleRequest $request)
    {
        // $this->authorize('create', Article::class); // يمكن تفعيلها حسب السياسات

        $validated = $request->validated();
        $validated['created_by'] = auth()->id() ?? 1;
        
        $baseSlug = Str::slug($validated['title']);
        $slug = $baseSlug ?: 'article';
        $counter = 1;
        while (Article::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }
        $validated['slug'] = $slug;

        $validated['published_at'] = (isset($validated['status']) && $validated['status'] === 'published') ? now() : null;

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

        $article = Article::create($validated);

        if ($request->has('tags')) {
            $article->tags()->attach($request->tags);
        }

        return response()->json([
            'status'  => true,
            'message' => 'تم إنشاء المقال بنجاح',
            'data'    => new ArticleResource($article->load(['category', 'tags', 'author', 'featuredImage']))
        ], 201);
    }

    /**
     * تعديل المقال (مدير/محرر)
     */
    public function update(UpdateArticleRequest $request, Article $article)
    {
        // $this->authorize('update', $article); // يمكن تفعيلها حسب السياسات

        $validated = $request->validated();
        $validated['updated_by'] = auth()->id() ?? 1;

        if (isset($validated['title']) && $validated['title'] !== $article->title) {
            $baseSlug = Str::slug($validated['title']);
            $slug = $baseSlug ?: 'article';
            $counter = 1;
            while (Article::withTrashed()->where('slug', $slug)->where('id', '!=', $article->id)->exists()) {
                $slug = $baseSlug . '-' . $counter++;
            }
            $validated['slug'] = $slug;
        }

        if (isset($validated['status'])) {
            if ($validated['status'] === 'published' && !$article->published_at) {
                $validated['published_at'] = now();
            } elseif ($validated['status'] !== 'published') {
                $validated['published_at'] = null;
            }
        }

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
            'message' => 'تم تحديث المقال بنجاح',
            'data'    => new ArticleResource($article->fresh()->load(['category', 'tags', 'author', 'featuredImage']))
        ], 200);
    }

    /**
     * نشر المقال (الموافقة)
     */
    public function publish(Request $request, Article $article)
    {
        $this->authorize('publish', $article);

        if ($article->status !== 'pending_review' && $article->status !== 'draft') {
            return response()->json([
                'status' => false,
                'message' => 'يمكن نشر المقالات التي هي قيد المراجعة أو المسودات فقط.',
            ], 400);
        }

        $article->update([
            'status' => 'published',
            'published_at' => now(),
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'updated_by' => $request->user()->id,
        ]);

        if ($article->author && $article->author->user_id && $article->author->user_id !== $request->user()->id) {
            $article->author->user->notify(new \App\Notifications\SystemAlert(
                "تم قبول ونشر مقالك: {$article->title}",
                "success"
            ));
        }

        return response()->json([
            'status' => true,
            'message' => 'تم نشر المقال بنجاح.',
            'data' => new ArticleResource($article->fresh()),
        ]);
    }

    /**
     * طلب تعديلات (إعادة للمسودة)
     */
    public function requestChanges(Request $request, Article $article)
    {
        $this->authorize('review', $article);

        if ($article->status !== 'pending_review') {
            return response()->json([
                'status' => false,
                'message' => 'يمكن طلب تعديلات فقط للمقالات التي هي قيد المراجعة.',
            ], 400);
        }

        $article->update([
            'status' => 'draft',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'updated_by' => $request->user()->id,
        ]);

        if ($article->author && $article->author->user_id && $article->author->user_id !== $request->user()->id) {
            $article->author->user->notify(new \App\Notifications\SystemAlert(
                "تم قبول ونشر مقالك: {$article->title}",
                "success"
            ));
        }

        return response()->json([
            'status' => true,
            'message' => 'تم إعادة المقال كمسودة لطلب التعديلات.',
            'data' => new ArticleResource($article->fresh()),
        ]);
    }

    /**
     * أرشفة المقال
     */
    public function archive(Request $request, Article $article)
    {
        $this->authorize('archive', $article);

        if ($article->status === 'archived') {
            return response()->json([
                'status' => false,
                'message' => 'المقال مؤرشف بالفعل.',
            ], 400);
        }

        $article->update([
            'status' => 'archived',
            'updated_by' => $request->user()->id,
        ]);

        if ($article->author && $article->author->user_id && $article->author->user_id !== $request->user()->id) {
            $article->author->user->notify(new \App\Notifications\SystemAlert(
                "تم قبول ونشر مقالك: {$article->title}",
                "success"
            ));
        }

        return response()->json([
            'status' => true,
            'message' => 'تم أرشفة المقال بنجاح.',
            'data' => new ArticleResource($article->fresh()),
        ]);
    }

    /**
     * حذف المقال (فقط للمسودة أو قيد المراجعة)
     */
    public function destroy(Request $request, Article $article)
    {
        $this->authorize('delete', $article);

        $article->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم حذف المقال بنجاح.',
        ]);
    }
}
