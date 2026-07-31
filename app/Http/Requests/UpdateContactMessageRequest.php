<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'     => 'sometimes|required|in:new,read,replied,archived',
            'handled_by' => 'nullable|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'حالة الرسالة غير صالحة. يجب أن تكون: new, read, replied, أو archived.',
        ];
    }
}