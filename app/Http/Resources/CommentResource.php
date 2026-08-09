<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'status' => $this->status,
            'user' => [
                'id' => $this->user_id,
                'name' => $this->user ? $this->user->name : 'مستخدم',
            ],
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i') : null,
        ];
    }
}