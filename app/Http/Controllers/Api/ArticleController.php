<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Models\Media;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use Illuminate\Support\Str;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\Notification;

class ArticleController extends Controller
{
    private function getActionPrefix(): string
    {
        $user = auth()->user();

        if ($user?->hasRole('admin')) {
            return 'المدير: ';
        }

        if ($user?->hasRole('editor')) {
            return 'المحرر: ';
        }

        if ($user?->hasRole('author')) {
            return 'الكاتب: ';
        }

        return '';
    }

    public function index(Request $request)
    {
        // استخدام الـ Scope المدمج في الموديل لإخفاء مقالات المعطلين
        $query = Article::with([
            'category',
            'tags',
            'author',
            'creator',
            'featuredImage',
        ])->withActiveAuthorOrCreator();

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
                $query->orderBy('title', 'asc');
                break;

            case 'oldest':
                $query->orderBy('published_at', 'asc');
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

        public function store(StoreArticleRequest $request)
    {
        $validated = $request->validated();
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'يجب تسجيل الدخول لإضافة مقال.',
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'status' => false,
                'message' => 'حسابك موقوف أو غير نشط، لا يمكنك نشر مقالات جديدة.',
            ], 403);
        }

        $authorProfile = \App\Models\Author::where('user_id', $user->id)->first();
        if ($authorProfile && $authorProfile->status !== 'active') {
            return response()->json([
                'status' => false,
                'message' => 'بروفايل الكاتب الخاص بك معطل، لا يمكنك إضافة مقالات.',
            ], 403);
        }

        $validated['created_by'] = $user->id;

        if (!array_key_exists('author_id', $validated)) {
            $validated['author_id'] = null;
        }

        $validated['slug'] = Str::slug($request->title) . '-' . Str::random(5);

        $validated['published_at'] =
            ($validated['status'] ?? 'published') === 'published'
                ? now()
                : null;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = $file->store('articles', 'public');

            $media = Media::create([
                'uuid' => (string) Str::uuid(),
                'uploaded_by' => $user->id,
                'file_name' => basename($path),
                'original_name' => $file->getClientOriginalName(),
                'disk' => 'public',
                'path' => $path,
                'webp_path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'extension' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize(),
                'type' => 'image',
                'created_by' => $user->id,
            ]);

            $validated['featured_image_id'] = $media->id;
        }

        $article = Article::create($validated);

        if ($request->has('tags')) {
            $article->tags()->attach($request->tags);
        }

        // --- نظام الإشعارات الجديد (الإضافة) ---
        $roleName = $user->hasRole('editor') ? 'المحرر' : 'الكاتب';
        
        // إذا كان المضيف ليس مديراً (يعني إما محرر أو كاتب)، نرسل إشعار للإدارة والمحررين
        if (!$user->hasRole('admin') && !$user->hasRole('super-admin')) {
            $adminsAndEditors = \App\Models\User::whereHas('role', function ($q) {
                $q->whereIn('name', ['admin', 'super-admin', 'editor']);
            })->where('id', '!=', $user->id)->get();

            Notification::send(
                $adminsAndEditors,
                new SystemNotification(
                    "$roleName ({$user->name}) تم اضافة مقال: {$article->title}",
                    'success',
                    '/admin/articles'
                )
            );
        }
        // ----------------------------------------

        ActivityLog::create([
            'user_id' => $user->id,
            'action_type' =>
                $article->status === 'published'
                    ? 'publish_article'
                    : 'add_article',
            'action_label' =>
                $article->status === 'published'
                    ? $this->getActionPrefix() . 'قام بنشر مقال'
                    : $this->getActionPrefix() . 'أضاف مقال جديد (مسودة)',
            'target_name' => $article->title,
            'target_url' => '/articles',
        ]);

        return response()->json([
            'status' => true,
            'message' => __('Article created successfully'),
            'data' => new ArticleResource(
                $article->load([
                    'category',
                    'tags',
                    'author',
                    'creator',
                    'featuredImage',
                ])
            ),
        ], 201);
    }


    public function show(Article $article)
    {
        $author = $article->author;
        $creator = $article->creator;

        if (($author && $author->status !== 'active') || ($creator && $creator->status !== 'active')) {
            return response()->json([
                'status' => false,
                'message' => 'عذراً، هذا المقال غير متاح حالياً.',
            ], 404);
        }

        $article->increment('views_count');

        $article->load([
            'category',
            'tags',
            'author',
            'creator',
            'featuredImage',
        ]);

        return response()->json([
            'status' => true,
            'data' => new ArticleResource($article),
        ]);
    }

        public function update(UpdateArticleRequest $request, Article $article)
    {
        $this->authorize('update', $article);

        $validated = $request->validated();
        $user = auth()->user();

        if (isset($validated['title'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $validated['updated_by'] = $user->id;

        if ($request->hasFile('image')) {
            // ... (نفس كود رفع الصورة السابق) ...
            $file = $request->file('image');
            $path = $file->store('articles', 'public');

            $media = Media::create([
                'uuid' => (string) Str::uuid(),
                'uploaded_by' => $user->id,
                'file_name' => basename($path),
                'original_name' => $file->getClientOriginalName(),
                'disk' => 'public',
                'path' => $path,
                'webp_path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'extension' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize(),
                'type' => 'image',
                'created_by' => $user->id,
            ]);

            $validated['featured_image_id'] = $media->id;
        }

        $article->update($validated);

        if ($request->has('tags')) {
            $article->tags()->sync($request->tags);
        }

        // --- نظام الإشعارات الجديد (التعديل) ---
        $isModifierAdmin = $user->hasRole('admin') || $user->hasRole('super-admin');
        $isModifierEditor = $user->hasRole('editor');
        $roleName = $isModifierEditor ? 'المحرر' : ($isModifierAdmin ? 'المدير' : 'الكاتب');
        $articleOwner = $article->author ? $article->author->user : null;

        // 1. إذا كان المعدل هو الإدارة أو المحرر، نبلغ الكاتب الأصلي
        if ($articleOwner && $user->id !== $articleOwner->id) {
            $articleOwner->notify(
                new SystemNotification(
                    "$roleName ({$user->name}) تم التعديل على مقالك: {$article->title}",
                    'warning',
                    '/author/dashboard/articles'
                )
            );
        }

        // 2. إذا كان المعدل محرر أو كاتب، نبلغ المدير (أو المحررين الآخرين)
        if (!$isModifierAdmin) {
            $adminsAndEditors = \App\Models\User::whereHas('role', function ($q) {
                $q->whereIn('name', ['admin', 'super-admin', 'editor']);
            })->where('id', '!=', $user->id)->get();

            // للكاتب الأصل: إذا كان المعدل محرر، نذكره
            $authorName = $articleOwner ? $articleOwner->name : 'غير معروف';

            Notification::send(
                $adminsAndEditors,
                new SystemNotification(
                    $isModifierEditor 
                        ? "المحرر ({$user->name}) قام بتعديل مقال: {$article->title} للكاتب ($authorName)"
                        : "الكاتب ({$user->name}) تم تعديل مقال: {$article->title}",
                    'warning',
                    '/admin/articles'
                )
            );
        }
        // ----------------------------------------

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action_type' => 'edit_article',
            'action_label' => $this->getActionPrefix() . 'تعديل مقال',
            'target_name' => $article->title,
            'target_url' => '/articles',
        ]);

        return response()->json([
            'status' => true,
            'message' => __('Article updated successfully'),
            'data' => new ArticleResource(
                $article->fresh()->load([
                    'category',
                    'tags',
                    'author',
                    'creator',
                    'featuredImage',
                ])
            ),
        ], 200);
    }

        public function publish(Article $article)
    {
        // يمكنك تفعيل التحقق من الصلاحيات إذا أردت
        // $this->authorize('update', $article);

        $article->update([
            'status' => 'published',
            'published_at' => $article->published_at ?? now(), // تعيين وقت النشر إذا لم يكن منشوراً من قبل
        ]);

        // إشعار للكاتب بأن مقاله تم نشره
        $articleOwner = $article->author ? $article->author->user : null;
        if ($articleOwner && auth()->id() !== $articleOwner->id) {
            $articleOwner->notify(
                new SystemNotification(
                    "تم نشر مقالك: {$article->title} بنجاح!",
                    'success',
                    '/author/dashboard/articles'
                )
            );
        }

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action_type' => 'publish_article',
            'action_label' => $this->getActionPrefix() . 'قام بنشر مقال',
            'target_name' => $article->title,
            'target_url' => '/articles',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم نشر المقال بنجاح.',
            'data' => new ArticleResource($article->fresh()->load(['category', 'tags', 'author', 'creator', 'featuredImage'])),
        ], 200);
    }

        public function destroy(Article $article)
    {
        $this->authorize('delete', $article);

        $title = $article->title;
        $user = auth()->user();
        $articleOwner = $article->author ? $article->author->user : null;

        $article->tags()->detach();
        $article->delete();

        // --- نظام الإشعارات الجديد (الحذف) ---
        $isModifierAdmin = $user->hasRole('admin') || $user->hasRole('super-admin');
        $isModifierEditor = $user->hasRole('editor');
        $roleName = $isModifierEditor ? 'المحرر' : 'الكاتب';
        
        // 1. إذا حذفه المحرر أو المدير، نبلغ الكاتب
        if ($articleOwner && $user->id !== $articleOwner->id) {
            $articleOwner->notify(
                new SystemNotification(
                    "تم حذف مقالك: $title", // كما طلبت، بدون ذكر من حذفه
                    'danger',
                    '/author/dashboard/articles'
                )
            );
        }

        // 2. إذا حذفه الكاتب أو المحرر، نبلغ المدير والمحررين
        if (!$isModifierAdmin) {
            $adminsAndEditors = \App\Models\User::whereHas('role', function ($q) {
                $q->whereIn('name', ['admin', 'super-admin', 'editor']);
            })->where('id', '!=', $user->id)->get();

            $authorName = $articleOwner ? $articleOwner->name : 'غير معروف';

            Notification::send(
                $adminsAndEditors,
                new SystemNotification(
                    $isModifierEditor 
                        ? "المحرر ({$user->name}) قام بحذف مقال ($title) للكاتب ($authorName)"
                        : "الكاتب ({$user->name}) حذف مقال: $title",
                    'danger',
                    '/admin/articles'
                )
            );
        }
        // ----------------------------------------

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action_type' => 'delete_article',
            'action_label' => $this->getActionPrefix() . 'حذف مقال',
            'target_name' => $title,
            'target_url' => null,
        ]);

        return response()->json([
            'status' => true,
            'message' => __('Article deleted successfully'),
        ], 200);
    }


    public function related(Article $article)
    {
        $tagIds = $article->tags->pluck('id')->toArray();

        $relatedArticles = Article::published()
            ->withActiveAuthorOrCreator()
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
                'creator',
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