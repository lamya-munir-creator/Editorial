<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Comment; // Assuming Comment model exists

class ModeratorCommentController extends Controller
{
    /**
     * عرض جميع التعليقات
     */
    public function index(Request $request)
    {
        $query = Comment::with(['article', 'user', 'approver'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $comments = $query->paginate(min((int) $request->input('per_page', 10), 100));

        return response()->json([
            'status' => true,
            'data' => $comments->items(),
            'meta' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
            ],
        ]);
    }

    /**
     * عرض تعليق محدد
     */
    public function show($id)
    {
        $comment = Comment::with(['article', 'user', 'approver', 'replies'])->findOrFail($id);
        
        return response()->json([
            'status' => true,
            'data' => $comment,
        ]);
    }

    /**
     * الموافقة على التعليق
     */
    public function approve(Request $request, $id)
    {
        $comment = Comment::findOrFail($id);
        
        if ($comment->status === 'approved') {
            return response()->json(['status' => false, 'message' => 'التعليق موافق عليه مسبقاً.'], 400);
        }

        $comment->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return response()->json([
            'status' => true, 
            'message' => 'تمت الموافقة على التعليق بنجاح.',
            'data' => $comment->fresh()
        ]);
    }

    /**
     * رفض التعليق
     */
    public function reject(Request $request, $id)
    {
        $comment = Comment::findOrFail($id);
        
        if ($comment->status === 'rejected') {
            return response()->json(['status' => false, 'message' => 'التعليق مرفوض مسبقاً.'], 400);
        }

        $comment->update([
            'status' => 'rejected',
            // optional: clear approved details if it was previously approved
            // 'approved_by' => null,
            // 'approved_at' => null,
        ]);

        return response()->json([
            'status' => true, 
            'message' => 'تم رفض التعليق.',
            'data' => $comment->fresh()
        ]);
    }

    /**
     * حذف التعليق
     */
    public function destroy($id)
    {
        $comment = Comment::findOrFail($id);
        $comment->delete();

        return response()->json([
            'status' => true, 
            'message' => 'تم حذف التعليق بنجاح.'
        ]);
    }
}
