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
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
            'status'      => $this->status,    
            'sort_order'  => $this->sort_order, 
            
            // 👇 السطر الجديد الذي أضفناه لجلب عدد المقالات 👇
            'articles_count' => $this->articles_count ?? 0,

            // رابط الصورة المباشر والصحيح المتوافق مع الـ MediaController
            'image_url'   => $this->image ? asset('storage/' . $this->image->path) : null,

            'image' => $this->image ? [
                'id'       => $this->image->id,
                'url'      => asset('storage/' . $this->image->path),
                'alt_text' => $this->image->alt_text,
            ] : null,
        ];
    }
}
