<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReplyContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reply_message' => 'required|string|max:3000',
        ];
    }

    public function messages(): array
    {
        return [
            'reply_message.required' => 'نص الرد مطلوب.',
            'reply_message.max'      => 'نص الرد يجب ألا يتجاوز 3000 حرف.',
        ];
    }
}