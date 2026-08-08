<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
    /**
     * إمكانية استعراض قائمة المقالات (عامة للجميع)
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * إمكانية استعراض مقال محدد (عامة للجميع)
     */
    public function view(?User $user, Article $article): bool
    {
        return true;
    }

    /**
     * إمكانية إنشاء مقال جديد
     * يتطلب صلاحية 'create-article' (المحرر، الأدمن، والكاتب)
     */
    public function create(User $user): bool
    {
        return $user->can('create-article');
    }

    /**
     * إمكانية تعديل المقال
     * - يتطلب صلاحية 'edit-article'
     * - المحرر والمدير يمكنهما تعديل أي مقال
     * - الكاتب يمكنه تعديل المقال الخاص به فقط
     */
    public function update(User $user, Article $article): bool
    {
        if (! $user->can('edit-article')) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'editor'])) {
            return true;
        }

        // الكاتب يتفقد ملكية المقال عبر created_by أو ملف الكاتب المربوط
        return $article->created_by === $user->id 
            || ($article->author && $article->author->user_id === $user->id);
    }

    /**
     * إمكانية حذف المقال
     * - يتطلب صلاحية 'delete-article'
     * - المحرر والمدير يمكنهما حذف أي مقال
     * - الكاتب يمكنه حذف مقاله الخاص فقط
     */
    public function delete(User $user, Article $article): bool
    {
        if (! $user->can('delete-article')) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'editor'])) {
            return true;
        }

        return $article->created_by === $user->id 
            || ($article->author && $article->author->user_id === $user->id);
    }

    /**
     * إمكانية نشر المقال مباشرة
     * يتطلب صلاحية 'publish-article' (المحرر والمدير فقط)
     */
    public function publish(User $user): bool
    {
        return $user->can('publish-article');
    }
}
