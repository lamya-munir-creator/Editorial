<?php
namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;

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

    // 2. [إضافة جديدة] عرض تفاصيل تعليق معَيّن
    public function show($id)
    {
        $comment = Comment::with(['user', 'article'])->findOrFail($id);

        return response()->json([
            'status' => true,
            'data'   => $comment
        ], 200);
    }

    // 3. إضافة تعليق جديد
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'article_id' => 'required|exists:articles,id',
            'body'       => 'required|string|max:1000',
        ]);

        $comment = Comment::create([
            'article_id' => $validatedData['article_id'],
            'user_id'    => auth()->id(), // أو $request->user()->id
            'body'       => $validatedData['body'],
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم إضافة تعليقك بنجاح',
            'data'    => $comment
        ], 201);
    }

    // 4. حذف تعليق
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