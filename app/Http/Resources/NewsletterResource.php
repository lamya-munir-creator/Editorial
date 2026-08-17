<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NewsletterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            // إذا كان لديك حقل لحالة الاشتراك مثل status أو is_active يمكنك إضافته هنا
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i') : null,
        ];
    }
}