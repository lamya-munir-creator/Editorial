<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * شروط التحقق للوسوم (Validation Only).
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:tags,name',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم الوسم مطلوب.',
            'name.unique'   => 'اسم الوسم مضاف سابقاً.',
        ];
    }
}
