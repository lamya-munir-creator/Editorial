<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        // المشرف (moderator) لا يمتلك أي صلاحية لإدارة المقالات نهائيًا
        if ($user->hasRole('moderator')) {
            return false;
        }

        return null;
    }

    /**
     * عرض قائمة المقالات العامة.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * عرض مقال عام.
     */
    public function view(?User $user, Article $article): bool
    {
        return true;
    }

    /**
     * إنشاء مقال.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('editor') || $user->authorProfile()->exists();
    }

    /**
     * تعديل المقال.
     */
    public function update(User $user, Article $article): bool
    {
        if ($user->hasRole('editor')) {
            return true;
        }

        $author = $user->authorProfile;
        if ($author) {
            return (int) $article->author_id === (int) $author->id;
        }

        return false;
    }

    /**
     * إدارة المقال (للعرض الخاص).
     */
    public function manage(User $user, Article $article): bool
    {
        return $this->update($user, $article);
    }

    /**
     * حذف المقال.
     */
    public function delete(User $user, Article $article): bool
    {
        if ($user->hasRole('editor')) {
            return in_array($article->status, ['draft', 'pending_review']);
        }

        $author = $user->authorProfile;
        if ($author && (int) $article->author_id === (int) $author->id) {
            return $article->status === 'draft';
        }

        return false;
    }

    /**
     * إرسال للمراجعة.
     */
    public function submitForReview(User $user, Article $article): bool
    {
        if ($user->hasRole('editor')) {
            return true;
        }

        $author = $user->authorProfile;
        if ($author && (int) $article->author_id === (int) $author->id) {
            return $article->status === 'draft';
        }

        return false;
    }

    /**
     * مراجعة المقال.
     */
    public function review(User $user, Article $article): bool
    {
        return $user->hasRole('editor');
    }

    /**
     * نشر المقال.
     */
    public function publish(User $user, Article $article): bool
    {
        return $user->hasRole('editor');
    }

    /**
     * أرشفة المقال.
     */
    public function archive(User $user, Article $article): bool
    {
        if ($user->hasRole('editor')) {
            return true;
        }

        $author = $user->authorProfile;
        if ($author && (int) $article->author_id === (int) $author->id) {
            return true;
        }

        return false;
    }
}