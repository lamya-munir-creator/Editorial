<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
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
     *
     * الكاتب المعتمد أو المحرر أو الأدمن
     * يحتاج إلى صلاحية create-article.
     */
    public function create(User $user): bool
    {
        return $user->can('create-article');
    }

    /**
     * تعديل المقال.
     *
     * الأدمن والمحرر يستطيعان تعديل أي مقال.
     * الكاتب يستطيع تعديل مقاله فقط.
     */
    public function update(User $user, Article $article): bool
    {
        if (! $user->can('edit-article')) {
            return false;
        }

        // الأدمن والمحرر يستطيعان تعديل أي مقال
        if ($user->hasAnyRole(['admin', 'editor'])) {
            return true;
        }

        // الكاتب يستطيع تعديل مقاله فقط
        $author = $user->authorProfile;

        if (! $author) {
            return false;
        }

        return (int) $article->author_id === (int) $author->id;
    }
public function manage(User $user, Article $article): bool
{
    if ($user->hasAnyRole(['admin', 'editor'])) {
        return true;
    }

    $author = $user->authorProfile;

    if (! $author) {
        return false;
    }

    return (int) $article->author_id === (int) $author->id;
}
    /**
     * حذف المقال.
     *
     * الأدمن والمحرر يستطيعان حذف أي مقال.
     * الكاتب يستطيع حذف مقاله فقط.
     */
    public function delete(User $user, Article $article): bool
    {
        if (! $user->can('delete-article')) {
            return false;
        }

        // الأدمن والمحرر يستطيعان حذف أي مقال
        if ($user->hasAnyRole(['admin', 'editor'])) {
            return true;
        }

        // الكاتب يستطيع حذف مقاله فقط
        $author = $user->authorProfile;

        if (! $author) {
            return false;
        }

        return (int) $article->author_id === (int) $author->id;
    }

    /**
     * نشر المقال مباشرة.
     *
     * الأدمن والمحرر فقط حسب صلاحية publish-article.
     */
    public function publish(User $user): bool
    {
        return $user->can('publish-article');
    }
}