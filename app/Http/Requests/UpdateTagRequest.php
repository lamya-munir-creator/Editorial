<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * شروط التحقق لتحديث وسم موجود (Validation Only).
     */
    public function rules(): array
    {
        $tagId = $this->route('tag') ? $this->route('tag')->id : null;

        return [
            'name' => 'required|string|max:255|unique:tags,name,' . $tagId,
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم الوسم مطلوب.',
            'name.unique'   => 'اسم الوسم مستخدم بالفعل.',
        ];
    }
}
