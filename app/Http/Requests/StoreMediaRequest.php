<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file'        => 'required|file|mimes:jpeg,png,jpg,webp,mp4,pdf|max:10240', // 10MB كحد أقصى
            'alt_text'    => 'nullable|string|max:255',
            'caption'     => 'nullable|string',
            'visibility' => 'nullable|in:public,private',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'يرجى اختيار ملف لرفعه.',
            'file.mimes'    => 'نوع الملف غير مدعوم.',
            'file.max'      => 'حجم الملف كبير جداً (الحد الأقصى 10 ميجابايت).',
        ];
    }
}