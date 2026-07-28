<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'slug'        => $this->slug,
            'content'     => $this->content,
            'status'      => $this->status,
            'views_count' => $this->views_count ?? 0,
            
            // جلب بيانات التصنيف والوسوم بشكل مرن
            'category'    => new CategoryResource($this->whenLoaded('category') ?? $this->category),
            'tags'        => TagResource::collection($this->whenLoaded('tags') ?? $this->tags),
            'author'      => $this->author ? [
                'id'   => $this->author->id,
                'name' => $this->author->name ?? $this->author->first_name,
            ] : null,
            
            'created_at'  => $this->created_at?->toDateTimeString(),
        ];
    }
}