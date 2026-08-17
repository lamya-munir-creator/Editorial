<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
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