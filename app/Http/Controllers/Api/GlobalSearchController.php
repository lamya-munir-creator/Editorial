<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\User;
use App\Models\Category;
use App\Models\Comment;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->query('q');

        if (!$query || strlen($query) < 2) {
            return response()->json([
                'articles' => [],
                'users' => [],
                'categories' => [],
                'comments' => [],
                'messages' => []
            ]);
        }

        // 1. Search Articles
        $articles = Article::where('title', 'like', "%{$query}%")
            ->orWhere('content', 'like', "%{$query}%")
            ->select('id', 'title', 'slug', 'status')
            ->take(5)
            ->get();

        // 2. Search Users
        $users = User::where('first_name', 'like', "%{$query}%")
            ->orWhere('last_name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->select('id', 'first_name', 'last_name', 'email')
            ->take(5)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'email' => $user->email
                ];
            });

        // 3. Search Categories
        $categories = Category::where('name', 'like', "%{$query}%")
            ->select('id', 'name', 'slug')
            ->take(3)
            ->get();

        // 4. Search Comments
        $comments = Comment::where('content', 'like', "%{$query}%")
            ->select('id', 'content', 'article_id')
            ->with('article:id,title')
            ->take(3)
            ->get()
            ->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'content' => \Str::limit($comment->content, 50),
                    'article' => $comment->article ? $comment->article->title : 'مقال محذوف'
                ];
            });

        // 5. Search Messages
        $messages = ContactMessage::where('full_name', 'like', "%{$query}%")
            ->orWhere('subject', 'like', "%{$query}%")
            ->orWhere('message', 'like', "%{$query}%")
            ->select('id', 'full_name', 'subject')
            ->take(3)
            ->get()
            ->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'subject' => $msg->subject,
                    'name' => $msg->full_name
                ];
            });

        return response()->json([
            'articles' => $articles,
            'users' => $users,
            'categories' => $categories,
            'comments' => $comments,
            'messages' => $messages,
        ]);
    }
}
