<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Models\Media;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Notification;
use App\Notifications\SystemNotification;
use App\Models\User;


class AuthorArticleController extends Controller
{
    /**
     * جلب مقالات الكاتب الحالي.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'يجب تسجيل الدخول أولاً.',
            ], 401);
        }

        $author = $user->authorProfile;

        if (!$author) {
            return response()->json([
                'status' => false,
                'message' => 'لا يوجد ملف كاتب معتمد لهذا الحساب.',
            ], 403);
        }

        $query = Article::with([
            'category',
            'tags',
            'author',
            'featuredImage',
        ])->where('author_id', $author->id);

        // البحث داخل مقالات الكاتب فقط
        if ($request->filled('q')) {
            $search = $request->input('q');

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // الحالة
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // التصنيف
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // الترتيب
        $sort = $request->input('sort', 'latest');

        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at');
                break;

            case 'popular':
                $query->orderByDesc('views_count');
                break;

            case 'alphabetical':
                $query->orderBy('title');
                break;

            case 'latest':
            default:
                $query->orderByDesc('created_at');
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
     * إنشاء مقال جديد للكاتب الحالي.
     */
        public function store(StoreArticleRequest $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'يجب تسجيل الدخول أولاً.',
            ], 401);
        }

        $author = $user->authorProfile;

        if (!$author) {
            return response()->json([
                'status' => false,
                'message' => 'لا يوجد ملف كاتب معتمد لهذا الحساب.',
            ], 403);
        }

        $this->authorize('create', Article::class);

        $validated = $request->validated();

        return DB::transaction(function () use (
            $request,
            $validated,
            $user,
            $author
        ) {
            $validated['author_id'] = $author->id;
            $validated['created_by'] = $user->id;

            $validated['slug'] = $this->generateUniqueSlug(
                $validated['title']
            );

            $validated['published_at'] =
                $validated['status'] === 'published'
                    ? now()
                    : null;

            if ($request->hasFile('image')) {
                $media = $this->createArticleMedia(
                    $request->file('image'),
                    $user->id
                );

                $validated['featured_image_id'] = $media->id;
            }

            $article = Article::create($validated);

            if ($request->has('tags')) {
                $article->tags()->sync(
                    $request->input('tags', [])
                );
            }

            $article->load([
                'category',
                'tags',
                'author',
                'featuredImage',
            ]);

            // --- نظام الإشعارات (إضافة مقال من كاتب) ---
            $adminsAndEditors = User::whereHas('roles', function($q) { 
                $q->whereIn('name', ['admin', 'super-admin', 'editor']); 
            })->orWhereHas('role', function($q) { 
                $q->whereIn('name', ['admin', 'super-admin', 'editor']); 
            })->get();

            $authorName = $author->display_name ?? $user->first_name;

            Notification::send($adminsAndEditors, new SystemNotification(
                'قام الكاتب (' . $authorName . ') بإضافة مقال جديد: ' . $article->title, 
                'info', 
                '/admin/articles'
            ));
            // ----------------------------------------

            return response()->json([
                'status' => true,
                'message' => 'تم إنشاء المقال بنجاح.',
                'data' => new ArticleResource($article),
            ], 201);
        });
    }

public function submitForReview(
    Request $request,
    Article $article
): JsonResponse {

    $this->authorize('submitForReview', $article);

    $article->update([
        'status' => 'pending_review',
        'submitted_for_review_by' => $request->user()->id,
        'submitted_for_review_at' => now(),
    ]);

    ActivityLog::create([
        'user_id' => $request->user()->id,
        'action_type' => 'submit_article_for_review',
        'action_label' => 'إرسال المقال للمراجعة',
        'target_name' => $article->title,
        'target_url' => '/author/articles',
    ]);

    // --- نظام الإشعارات (طلب مراجعة مقال من كاتب) ---
    $adminsAndEditors = User::whereHas('roles', function($q) { 
        $q->whereIn('name', ['admin', 'super-admin', 'editor']); 
    })->orWhereHas('role', function($q) { 
        $q->whereIn('name', ['admin', 'super-admin', 'editor']); 
    })->get();

    $authorName = $request->user()->authorProfile->display_name ?? $request->user()->first_name;

    Notification::send($adminsAndEditors, new SystemNotification(
        'طلب الكاتب (' . $authorName . ') مراجعة مقال: ' . $article->title, 
        'warning', 
        '/admin/articles'
    ));
    // ----------------------------------------

    return response()->json([
        'status' => true,
        'message' => 'تم إرسال المقال للمراجعة بنجاح.',
        'data' => $article->fresh(),
    ]);
}

    /**
     * عرض مقال من مقالات الكاتب الحالي.
     */
    public function show(Request $request, Article $article)
    {
$this->authorize('manage', $article);
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
     * تعديل مقال يملكه الكاتب الحالي.
     */
    public function update(
        UpdateArticleRequest $request,
        Article $article
    ) {
        $this->authorize('update', $article);

        $validated = $request->validated();

        return DB::transaction(function () use (
            $request,
            $validated,
            $article
        ) {
            if (isset($validated['title'])) {
                $validated['slug'] = $this->generateUniqueSlug(
                    $validated['title'],
                    $article->id
                );
            }

            $validated['updated_by'] = $request->user()->id;

            /*
             * معالجة حالة النشر.
             */
            if (
                isset($validated['status'])
                && $validated['status'] === 'published'
                && !$article->published_at
            ) {
                $validated['published_at'] = now();
            }

            if (
                isset($validated['status'])
                && $validated['status'] !== 'published'
            ) {
                $validated['published_at'] = null;
            }

            /*
             * رفع صورة جديدة.
             */
            if ($request->hasFile('image')) {
                $media = $this->createArticleMedia(
                    $request->file('image'),
                    $request->user()->id
                );

                $validated['featured_image_id'] = $media->id;
            }

            $article->update($validated);

            if ($request->has('tags')) {
                $article->tags()->sync(
                    $request->input('tags', [])
                );
            }

            $article->fresh()->load([
                'category',
                'tags',
                'author',
                'featuredImage',
            ]);

            return response()->json([
                'status' => true,
                'message' => 'تم تحديث المقال بنجاح.',
                'data' => new ArticleResource($article),
            ]);
        });
    }

    /**
     * حذف مقال الكاتب.
     */
        public function destroy(Request $request, Article $article)
    {
        $this->authorize('delete', $article);

        $articleTitle = $article->title; // نحفظ العنوان قبل الحذف
        $article->tags()->detach();

        // Soft Delete بسبب استخدام SoftDeletes في Article
        $article->delete();

        // --- نظام الإشعارات (حذف مقال من كاتب) ---
        $adminsAndEditors = User::whereHas('roles', function($q) { 
            $q->whereIn('name', ['admin', 'super-admin', 'editor']); 
        })->orWhereHas('role', function($q) { 
            $q->whereIn('name', ['admin', 'super-admin', 'editor']); 
        })->get();

        $authorName = $request->user()->authorProfile->display_name ?? $request->user()->first_name;

        Notification::send($adminsAndEditors, new SystemNotification(
            'قام الكاتب (' . $authorName . ') بحذف مقال: ' . $articleTitle, 
            'danger', 
            '/admin/articles'
        ));
        // ----------------------------------------

        return response()->json([
            'status' => true,
            'message' => 'تم حذف المقال بنجاح.',
        ]);
    }


    /**
     * إنشاء Media للمقال.
     */
    private function createArticleMedia($file, int $userId): Media
    {
        $path = $file->store('articles', 'public');

        return Media::create([
            'uuid' => (string) Str::uuid(),
            'uploaded_by' => $userId,
            'file_name' => basename($path),
            'original_name' => $file->getClientOriginalName(),
            'disk' => 'public',
            'path' => $path,
            'webp_path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'extension' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
            'type' => 'image',
            'visibility' => 'public',
            'created_by' => $userId,
        ]);
    }

    /**
     * إنشاء Slug فريد.
     */
    private function generateUniqueSlug(
        string $title,
        ?int $ignoreArticleId = null
    ): string {
        $baseSlug = Str::slug($title);

        /*
         * Str::slug قد يعيد قيمة فارغة لبعض العناوين العربية.
         */
        if (!$baseSlug) {
            $baseSlug = 'article';
        }

        $slug = $baseSlug;
        $counter = 1;

        while (
            Article::withTrashed()
                ->where('slug', $slug)
                ->when(
                    $ignoreArticleId,
                    fn ($query) =>
                        $query->where('id', '!=', $ignoreArticleId)
                )
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}