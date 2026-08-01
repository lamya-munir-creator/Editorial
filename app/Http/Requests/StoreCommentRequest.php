<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * قواعد التحقق لإنشاء تعليق جديد (Validation Only).
     */
    public function rules(): array
    {
        return [
            'article_id'  => 'required|exists:articles,id',
            'parent_id'   => 'nullable|exists:comments,id',
            'user_id'     => 'nullable|exists:users,id',
            'guest_name'  => 'nullable|string|max:100',
            'guest_email' => 'nullable|email|max:150',
            'content'     => 'required|string|max:1000',
            'status'      => 'nullable|in:pending,approved,rejected',
        ];
    }

    public function messages(): array
    {
        return [
            'article_id.required' => 'المقال المرتبط بالتعليق مطلوب.',
            'article_id.exists'   => 'المقال غير موجود.',
            'content.required'    => 'نص التعليق مطلوب.',
            'content.max'         => 'التعليق يجب ألا يتجاوز 1000 حرف.',
            'guest_email.email'   => 'البريد الإلكتروني للزائر غير صحيح.',
        ];
    }
}
