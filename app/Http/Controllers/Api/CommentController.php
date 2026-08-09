<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    // 1. جلب كافة التعليقات (مع بيانات المستخدم والمقالة والصفحات)
    public function index()
    {
        $comments = Comment::with(['user', 'article'])->latest()->paginate(15);
        
        // استخدام الـ Resource بدلاً من الاستجابة الخام
        return CommentResource::collection($comments);
    }

    // 2. عرض تفاصيل تعليق معَيّن
    public function show($id)
    {
        $comment = Comment::with(['user', 'article'])->findOrFail($id);

        // استخدام الـ Resource لعنصر واحد
        return new CommentResource($comment);
    }

    /**
     * 3. إضافة تعليق جديد مع التحقق عبر StoreCommentRequest (Validation Only).
     */
    public function store(StoreCommentRequest $request)
    {
        // استقبال البيانات الموفقة عبر FormRequest
        $validatedData = $request->validated();

        $comment = Comment::create([
            'article_id'  => $validatedData['article_id'],
            'parent_id'   => $validatedData['parent_id'] ?? null,
            'user_id'     => $validatedData['user_id'] ?? auth()->id(),
            'guest_name'  => $validatedData['guest_name'] ?? null,
            'guest_email' => $validatedData['guest_email'] ?? null,
            'content'     => $validatedData['content'],
            'status'      => $validatedData['status'] ?? 'pending',
        ]);

        return response()->json([
            'status'  => true,
            'message' => __('Comment created successfully and is pending review'),
            'data'    => $comment
        ], 201);
    }

    /**
     * تحديث التعليق بشكل عام
     */
    public function update(UpdateCommentRequest $request, $id)
    {
        Gate::authorize('approve-comment');
        
        $comment = Comment::findOrFail($id);
        $validatedData = $request->validated();

        $comment->update($validatedData);

        return response()->json([
            'status'  => true,
            'message' => __('Comment updated successfully'),
            'data'    => $comment
        ], 200);
    }

    /**
     * تحديث حالة التعليق (توجيهها مباشرة لدالة update أو معالجتها)
     */
    public function updateStatus(Request $request, $id)
    {
        return $this->update($request, $id);
    }

    // 5. حذف تعليق
    public function destroy($id)
    {
        Gate::authorize('delete-comment');
        
        $comment = Comment::findOrFail($id);
        $comment->delete();

        return response()->json([
            'status'  => true,
            'message' => __('Comment deleted successfully')
        ], 200);
    }
}