<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * قواعد التحقق لتحديث تعليق أو تغيير حالته (Validation Only).
     */
    public function rules(): array
    {
        return [
            'content' => 'sometimes|required|string|max:1000',
            'status'  => 'sometimes|required|in:pending,approved,rejected',
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'نص التعليق مطلوب.',
            'status.in'        => 'حالة التعليق غير صالحة.',
        ];
    }
}
