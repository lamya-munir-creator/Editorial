<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use Illuminate\Http\Request;

class AuthorCommentController extends Controller
{
    /**
     * جلب التعليقات الموجودة على مقالات الكاتب الحالي.
     *
     * GET /api/author/comments
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

        $query = Comment::query()
            ->with([
                'user',
                'article',
            ])
            ->whereHas('article', function ($query) use ($author) {
                $query->where('author_id', $author->id);
            });

        // فلترة حسب حالة التعليق
        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->input('status')
            );
        }

        // فلترة حسب مقال معين
        if ($request->filled('article_id')) {
            $query->where(
                'article_id',
                $request->input('article_id')
            );
        }

        // البحث في نص التعليق
        if ($request->filled('q')) {
            $search = $request->input('q');

            $query->where(function ($query) use ($search) {
                $query
                    ->where('content', 'like', "%{$search}%")
                    ->orWhere('guest_name', 'like', "%{$search}%");
            });
        }

        $comments = $query
            ->latest()
            ->paginate(
                min(
                    (int) $request->input('per_page', 10),
                    100
                )
            );

        return response()->json([
            'status' => true,
            'message' => 'تم جلب تعليقات مقالات الكاتب بنجاح.',
            'data' => CommentResource::collection($comments),
            'meta' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
            ],
        ]);
    }
}