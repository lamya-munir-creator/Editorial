<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Article;
use App\Models\User;
use App\Models\Category;
use App\Models\Comment;
use App\Models\ContactMessage;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    public function globalSearch(Request $request)
    {
        $q = $request->query('q');

        // إذا كان نص البحث فارغاً أو أقل من حرفين
        if (!$q || mb_strlen($q) < 2) {
            return response()->json([
                'articles' => [], 'users' => [], 'categories' => [], 'comments' => [], 'messages' => []
            ]);
        }

        // 1. بحث المقالات
        $articles = Article::where('title', 'LIKE', "%{$q}%")
            ->orWhere('content', 'LIKE', "%{$q}%")
            ->select('id', 'title', 'status')
            ->take(5)->get()
            ->map(function ($article) {
                $statusMap = ['published' => 'منشور', 'draft' => 'مسودة', 'archived' => 'مؤرشف'];
                $article->status = $statusMap[$article->status] ?? $article->status;
                return $article;
            });

        // 2. بحث المستخدمين (الكُتاب)
        $users = User::where('first_name', 'LIKE', "%{$q}%")
            ->orWhere('last_name', 'LIKE', "%{$q}%")
            ->orWhere('email', 'LIKE', "%{$q}%")
            ->take(5)->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => trim($user->first_name . ' ' . $user->last_name),
                    'email' => $user->email
                ];
            });

        // 3. بحث التصنيفات
        $categories = Category::where('name', 'LIKE', "%{$q}%")
            ->select('id', 'name')
            ->take(5)->get();

        // 4. بحث التعليقات
        $comments = Comment::with('article:id,title')->where('content', 'LIKE', "%{$q}%")
            ->take(5)->get()
            ->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'content' => Str::limit($comment->content, 50),
                    'article' => $comment->article ? $comment->article->title : 'مقال محذوف'
                ];
            });

        // 5. بحث الرسائل
        $messages = ContactMessage::where('subject', 'LIKE', "%{$q}%")
            ->orWhere('full_name', 'LIKE', "%{$q}%")
            ->orWhere('email', 'LIKE', "%{$q}%")
            ->take(5)->get()
            ->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'subject' => Str::limit($msg->subject, 50),
                    'name' => $msg->full_name
                ];
            });

        // إرسال النتائج الشاملة كـ JSON للفرونت إند
        return response()->json([
            'articles' => $articles,
            'users' => $users,
            'categories' => $categories,
            'comments' => $comments,
            'messages' => $messages
        ]);
    }
}
