<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $baseUrl = config('app.url', 'http://127.0.0.1:8000');

        /*
         * الكاتب الأصلي للمقال[cite: 2].
         */
        $author = $this->whenLoaded('author');

        /*
         * المستخدم الذي أنشأ المقال[cite: 2].
         */
        $creator = $this->whenLoaded('creator');

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,

            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->content,

            'meta_title' => $this->meta_title ?? $this->title,
            'meta_description' => $this->meta_description ?? $this->excerpt,

            'reading_time' => $this->reading_time ?? 1,
            'views_count' => $this->views_count ?? 0,

            'is_featured' => (bool) $this->is_featured,
            'allow_comments' => (bool) $this->allow_comments,

            'status' => $this->status,

            'published_at' => $this->published_at?->toDateTimeString(),

            /*
             * التصنيف[cite: 2].
             */
            'category' => new CategoryResource(
                $this->whenLoaded('category')
            ),

            /*
             * الوسوم[cite: 2].
             */
            'tags' => TagResource::collection(
                $this->whenLoaded('tags')
            ),

            /*
             * الصورة البارزة[cite: 2].
             */
            'featured_image' => $this->featuredImage
                ? [
                    'id' => $this->featuredImage->id,
                    'url' => asset(
                        'storage/' .
                        ($this->featuredImage->webp_path
                            ?? $this->featuredImage->path)
                    ),
                    'alt_text' => $this->featuredImage->alt_text,
                ]
                : null,

            /*
             * الكاتب أو المنشئ (إذا لم يوجد author يتم جلب بيانات creator تلقائياً ليظهر اسم الأدمن)[cite: 2].
             */
            'author' => $this->author
                ? [
                    'id' => $this->author->id,
                    'name' =>
                        $this->author->display_name
                        ?? $this->author->name
                        ?? 'غير معروف',
                    'slug' => $this->author->slug,
                ]
                : ($this->creator
                    ? [
                        'id' => $this->creator->id,
                        'name' => $this->creator->full_name
                            ?: ($this->creator->username
                                ?? $this->creator->email
                                ?? 'غير معروف'),
                        'slug' => null,
                    ]
                    : null
                ),

            /*
             * المستخدم الذي أنشأ المقال[cite: 2].
             */
            'creator' => $this->creator
                ? [
                    'id' => $this->creator->id,
                    'name' => $this->creator->full_name
                        ?: ($this->creator->username
                            ?? $this->creator->email
                            ?? 'غير معروف'),
                    'username' => $this->creator->username,
                    'email' => $this->creator->email,
                ]
                : null,

            /*
             * created_by مفيد للـ Frontend إذا احتجناه[cite: 2].
             */
            'created_by' => $this->created_by,

            'created_at' => $this->created_at?->toDateTimeString(),

            /*
             * Schema Markup[cite: 2].
             */
            'schema_markup' => [
                '@context' => 'https://schema.org',

                '@type' => $this->schema_type ?? 'NewsArticle',

                'headline' => $this->title,

                'description' =>
                    $this->meta_description
                    ?? $this->excerpt,

                'image' => $this->featuredImage
                    ? [
                        asset(
                            'storage/' .
                            ($this->featuredImage->webp_path
                                ?? $this->featuredImage->path)
                        ),
                    ]
                    : [],

                'datePublished' => $this->published_at
                    ? $this->published_at->toAtomString()
                    : $this->created_at?->toAtomString(),

                'dateModified' => $this->updated_at
                    ? $this->updated_at->toAtomString()
                    : $this->created_at?->toAtomString(),

                'mainEntityOfPage' => [
                    '@type' => 'WebPage',
                    '@id' => $baseUrl . '/articles/' . $this->slug,
                ],

                'author' => [
                    '@type' => 'Person',

                    'name' => $this->author
                        ? (
                            $this->author->display_name
                            ?? $this->author->name
                            ?? 'غير معروف'
                        )
                        : (
                            $this->creator
                                ? (
                                    $this->creator->full_name
                                    ?: (
                                        $this->creator->username
                                        ?? $this->creator->email
                                        ?? 'غير معروف'
                                    )
                                )
                                : 'غير معروف'
                        ),
                ],

                'publisher' => [
                    '@type' => 'Organization',
                    'name' => config(
                        'app.name',
                        'Editorial'
                    ),
                ],
            ],
        ];
    }
}