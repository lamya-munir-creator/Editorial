<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;

class CommentController extends Controller
{
    // 1. جلب كافة التعليقات (مع بيانات المستخدم والمقالة والصفحات)
    public function index()
    {
        $comments = Comment::with(['user', 'article'])->latest()->paginate(15);
        
        return response()->json([
            'status' => true,
            'data'   => $comments
        ], 200);
    }

    // 2. عرض تفاصيل تعليق معَيّن
    public function show($id)
    {
        $comment = Comment::with(['user', 'article'])->findOrFail($id);

        return response()->json([
            'status' => true,
            'data'   => $comment
        ], 200);
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
            'message' => 'تم إضافة التعليق بنجاح وهو قيد المراجعة',
            'data'    => $comment
        ], 201);
    }

    /**
     * 4. تحديث تعليق موجود أو تغيير حالته عبر UpdateCommentRequest (Validation Only).
     */
    public function update(UpdateCommentRequest $request, $id)
    {
        $comment = Comment::findOrFail($id);
        $validatedData = $request->validated();

        $comment->update($validatedData);

        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث التعليق بنجاح',
            'data'    => $comment
        ], 200);
    }

    // 5. حذف تعليق
    public function destroy($id)
    {
        $comment = Comment::findOrFail($id);
        $comment->delete();

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف التعليق بنجاح'
        ], 200);
    }
}