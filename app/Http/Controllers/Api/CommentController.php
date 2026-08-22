<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use App\Notifications\SystemNotification;


class CommentController extends Controller
{
    private function getActionPrefix(): string
    {
        $user = auth()->user();
        $firstName = $user ? $user->first_name : '';
        $isFemale = $firstName && (mb_substr($firstName, -1) === 'ة' || mb_substr($firstName, -1) === 'ه');
        return $isFemale ? 'قامت بـ' : 'قام بـ';
    }

    public function index(Request $request, $article = null)
    {
        $query = Comment::with(['user.avatar', 'article'])->latest();

        if ($article) {
            $query->whereHas('article', function($q) use ($article) {
                $q->where('id', $article)->orWhere('slug', $article);
            });
            $query->where('status', 'approved');
        }

        if ($request->has('status') && $request->status !== 'all' && $request->status !== 'الكل' && $request->status !== 'جميع الحالات') {
            $status = $request->status;
            if ($status === 'pending' || $status === 'قيد الانتظار') $status = 'pending';
            if ($status === 'approved' || $status === 'مقبول') $status = 'approved';
            if ($status === 'rejected' || $status === 'مرفوض') $status = 'rejected';
            $query->where('status', $status);
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('content', 'like', "%{$search}%")
                  ->orWhere('guest_name', 'like', "%{$search}%")
                  ->orWhereHas('user', function($u) use ($search) {
                      $u->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $comments = $query->paginate(15);
        return CommentResource::collection($comments);
    }

    public function show($id)
    {
        $comment = Comment::with(['user', 'article'])->findOrFail($id);
        return new CommentResource($comment);
    }

    public function store(StoreCommentRequest $request)
    {
        $user = auth()->user();

        // التحقق مما إذا كان المستخدم مسجلاً وحسابه موقوفاً أو غير نشط
        if ($user && $user->status !== 'active') {
            return response()->json([
                'status'  => false,
                'message' => 'حسابك موقوف أو غير نشط، لا يمكنك إضافة تعليقات.',
            ], 403);
        }

        $validatedData = $request->validated();

        $comment = Comment::create([
            'article_id'  => $validatedData['article_id'],
            'parent_id'   => $validatedData['parent_id'] ?? null,
            'user_id'     => $validatedData['user_id'] ?? $user?->id,
            'guest_name'  => $validatedData['guest_name'] ?? null,
            'guest_email' => $validatedData['guest_email'] ?? null,
            'content'     => $validatedData['content'],
            'status'      => $validatedData['status'] ?? 'pending',
        ]);

        // --- نظام الإشعارات الجديد (إضافة تعليق) ---
        $adminsAndEditors = \App\Models\User::whereHas('role', function($q) { 
            $q->whereIn('name', ['admin', 'super-admin', 'editor']); 
        })->get();
        
        $commenterName = $user ? $user->name : ($validatedData['guest_name'] ?? 'زائر');
        $articleTitle = $comment->article ? $comment->article->title : 'مقال غير معروف';

        \Illuminate\Support\Facades\Notification::send(
            $adminsAndEditors, 
            new SystemNotification(
                "تعليق جديد على مقال ($articleTitle) من مستخدم ($commenterName)", 
                'success', 
                '/admin/comments'
            )
        );
        // ----------------------------------------

        ActivityLog::create([
            'user_id' => $user?->id ?? 1,
            'action_type' => 'add_comment',
            'action_label' => $this->getActionPrefix() . 'إضافة تعليق جديد',
            'target_name' => \Str::limit($comment->content, 30),
            'target_url' => '/comments',
        ]);

        return response()->json([
            'status'  => true,
            'message' => __('Comment created successfully and is pending review'),
            'data'    => $comment
        ], 201);
    }


        public function update(UpdateCommentRequest $request, $id)
    {
        Gate::authorize('approve-comment');
        
        $comment = Comment::findOrFail($id);
        $validatedData = $request->validated();

        $comment->update($validatedData);

        // --- نظام الإشعارات الجديد (تحديث حالة التعليق) ---
        if (isset($validatedData['status'])) {
            $status = $validatedData['status'];
            $modifier = auth()->user();
            
            // إشعار لصاحب التعليق (إذا كان مسجلاً)
            if ($comment->user) {
                if ($status === 'approved') {
                    $comment->user->notify(new SystemNotification('تمت الموافقة على تعليقك!', 'success', '/'));
                } elseif ($status === 'rejected') {
                    $comment->user->notify(new SystemNotification('تم رفض تعليقك.', 'danger', '/'));
                }
            }

            // إشعار للإدارة والمحررين بالعملية
            if ($status === 'approved' || $status === 'rejected') {
                $actionText = $status === 'approved' ? 'وافق على' : 'رفض';
                $type = $status === 'approved' ? 'success' : 'danger';
                $commenterName = $comment->user ? $comment->user->name : ($comment->guest_name ?? 'زائر');
                $articleTitle = $comment->article ? $comment->article->title : 'مقال غير معروف';
                
                // إذا كان الذي قام بالفعل هو المحرر -> نبلغ المدير
                if ($modifier->hasRole('editor')) {
                    $admins = \App\Models\User::whereHas('role', function($q) { 
                        $q->whereIn('name', ['admin', 'super-admin']); 
                    })->get();

                    \Illuminate\Support\Facades\Notification::send($admins, new SystemNotification(
                        "المحرر ({$modifier->name}) $actionText تعليق مستخدم ($commenterName)", 
                        $type, 
                        '/admin/comments'
                    ));
                } 
                // إذا كان الذي قام بالفعل هو المدير -> نبلغ المحرر
                elseif ($modifier->hasRole('admin') || $modifier->hasRole('super-admin')) {
                    $editors = \App\Models\User::whereHas('role', function($q) { 
                        $q->where('name', 'editor'); 
                    })->get();

                    \Illuminate\Support\Facades\Notification::send($editors, new SystemNotification(
                        "المدير تم $actionText تعليق مقال ($articleTitle) من المستخدم ($commenterName)", 
                        $type, 
                        '/admin/comments'
                    ));
                }
            }
        }
        // ----------------------------------------

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'edit_comment',
            'action_label' => $this->getActionPrefix() . 'تعديل أو قبول التعليق',
            'target_name' => \Str::limit($comment->content, 30),
            'target_url' => '/comments',
        ]);

        return response()->json([
            'status'  => true,
            'message' => __('Comment updated successfully'),
            'data'    => $comment
        ], 200);
    }


    public function updateStatus(UpdateCommentRequest $request, $id)
    {
        return $this->update($request, $id);
    }

    public function destroy($id)
    {
        Gate::authorize('delete-comment');
        
        $comment = Comment::findOrFail($id);
        $contentSnippet = \Str::limit($comment->content, 30);
        $comment->delete();

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'delete_comment',
            'action_label' => $this->getActionPrefix() . 'حذف التعليق',
            'target_name' => $contentSnippet,
            'target_url' => null,
        ]);

        return response()->json([
            'status'  => true,
            'message' => __('Comment deleted successfully')
        ], 200);
    }
}