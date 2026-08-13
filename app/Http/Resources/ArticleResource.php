<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $baseUrl = config('app.url', 'http://127.0.0.1:8000');

        return [
            'id'               => $this->id,
            'uuid'             => $this->uuid,
            'title'            => $this->title,
            'slug'             => $this->slug,
            'excerpt'          => $this->excerpt,
            'content'          => $this->content,
            'meta_title'       => $this->meta_title ?? $this->title,
            'meta_description' => $this->meta_description ?? $this->excerpt,
            'reading_time'     => $this->reading_time ?? 1,
            'views_count'      => $this->views_count ?? 0,
            'is_featured'      => (bool) $this->is_featured,
            'allow_comments'   => (bool) $this->allow_comments,
            'status'           => $this->status,
            'published_at'     => $this->published_at?->toDateTimeString(),

            'category' => new CategoryResource(
                $this->whenLoaded('category')
            ),

            'tags' => TagResource::collection(
                $this->whenLoaded('tags')
            ),

            'featured_image' => $this->featuredImage ? [
                'id' => $this->featuredImage->id,

                'url' => url(
                    Storage::disk($this->featuredImage->disk)
                        ->url(
                            $this->featuredImage->webp_path
                            ?? $this->featuredImage->path
                        )
                ),

                'alt_text' => $this->featuredImage->alt_text,
            ] : null,

            'author' => $this->author ? [
                'id'         => $this->author->id,
                'name'       => $this->author->display_name ?? $this->author->name,
                'slug'       => $this->author->slug,
                'job_title'  => $this->author->job_title,
                'biography'  => $this->author->biography ?? $this->author->bio,
            ] : null,

            'created_at' => $this->created_at?->toDateTimeString(),

            'schema_markup' => [
                '@context'    => 'https://schema.org',
                '@type'       => $this->schema_type ?? 'NewsArticle',
                'headline'    => $this->title,
                'description' => $this->meta_description ?? $this->excerpt,

                'image' => $this->featuredImage
                    ? [
                        url(
                            Storage::disk($this->featuredImage->disk)
                                ->url(
                                    $this->featuredImage->webp_path
                                    ?? $this->featuredImage->path
                                )
                        )
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
                    '@id'   => $baseUrl . '/articles/' . $this->slug,
                ],

                'author' => [
                    '@type' => 'Person',
                    'name'  => $this->author->display_name
                        ?? $this->author->name
                        ?? 'المحرر',
                ],

                'publisher' => [
                    '@type' => 'Organization',
                    'name'  => config('app.name', 'Editorial'),
                ],
            ],
        ];
    }
}