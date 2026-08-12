<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;


class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,

            'image' => $this->image ? [
                'id' => $this->image->id,
                'url' => url(Storage::disk($this->image->disk)->url($this->image->path)),
                'alt_text' => $this->image->alt_text,
            ] : null,
        ];
    }
}