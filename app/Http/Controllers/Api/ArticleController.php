<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Author;
use App\Models\Media;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use App\Http\Resources\ArticleResource;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    private function getActionPrefix(): string
    {
        $user = auth()->user();
        $firstName = $user ? $user->first_name : '';
        $isFemale = $firstName && (mb_substr($firstName, -1) === 'ة' || mb_substr($firstName, -1) === 'ه');
        return $isFemale ? 'قامت بـ' : 'قام بـ';
    }

    public function index(Request $request)
    {
        $query = Article::with(['category', 'tags', 'author', 'featuredImage']);

        if ($request->filled('q')) {
            $search = $request->input('q');

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_slug')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->input('category_slug'));
            });
        }

        if ($request->filled('author_slug')) {
            $query->whereHas('author', function ($q) use ($request) {
                $q->where('slug', $request->input('author_slug'));
            });
        }

        if ($request->filled('tag_slug')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('slug', $request->input('tag_slug'));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

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

        $articles = $query->paginate($request->input('per_page', 10));

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

    public function store(StoreArticleRequest $request)
    {
        $validated = $request->validated();

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

        ActivityLog::create([
            'user_id' => $user->id ?? 1,
            'action_type' => $article->status === 'published' ? 'publish_article' : 'add_article',
            'action_label' => $article->status === 'published' ? $this->getActionPrefix() . 'نشر المقال' : $this->getActionPrefix() . 'إنشاء مقال جديد (مسودة)',
            'target_name' => $article->title,
            'target_url' => '/articles',
        ]);

        return response()->json([
            'status'  => true,
            'message' => __('Article created successfully'),
            'data'    => new ArticleResource($article->load(['category', 'tags', 'author', 'featuredImage']))
        ], 201);
    }

    public function show(Article $article)
    {
        $article->increment('views_count');

        return response()->json([
            'status' => true,
            'data'   => new ArticleResource($article->load(['category', 'tags', 'author', 'featuredImage']))
        ], 200);
    }

    public function update(UpdateArticleRequest $request, Article $article)
    {
        $this->authorize('update', $article);

        $validated = $request->validated();

        if (isset($validated['title'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $validated['updated_by'] = auth()->id() ?? 1;

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

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'edit_article',
            'action_label' => $this->getActionPrefix() . 'تعديل المقال',
            'target_name' => $article->title,
            'target_url' => '/articles',
        ]);

        return response()->json([
            'status'  => true,
            'message' => __('Article updated successfully'),
            'data'    => new ArticleResource($article->fresh()->load(['category', 'tags', 'author', 'featuredImage']))
        ], 200);
    }

    public function destroy(Article $article)
    {
        $this->authorize('delete', $article);

        $title = $article->title;
        $article->tags()->detach();
        $article->delete();

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'delete_article',
            'action_label' => $this->getActionPrefix() . 'حذف المقال',
            'target_name' => $title,
            'target_url' => null,
        ]);

        return response()->json([
            'status'  => true,
            'message' => __('Article deleted successfully')
        ], 200);
    }

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