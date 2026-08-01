<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * قواعد التحقق الخاصة بإنشاء صفحة ثابتة جديدة.
     */
    public function rules(): array
    {
        return [
            'title'             => 'required|string|max:255',
            'slug'              => 'nullable|string|max:255|unique:pages,slug',
            'content'           => 'required|string',
            
            // رفع صورة البنر/الهيدر البارزة للصفحة
            'image'             => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'featured_image_id' => 'nullable|exists:media,id',
            
            'meta_title'        => 'nullable|string|max:255',
            'meta_description'  => 'nullable|string|max:500',
            'template'          => 'nullable|string|max:100',
            'is_homepage'       => 'sometimes|boolean',
            'status'            => 'required|in:draft,published,archived',
            'published_at'      => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'   => 'عنوان الصفحة مطلوب.',
            'content.required' => 'محتوى الصفحة مطلوب.',
            'slug.unique'      => 'اسم الرابط (Slug) مستخدم بالفعل.',
            'image.image'      => 'الملف المرفوع يجب أن يكون صورة صالحة.',
            'status.required'  => 'حالة الصفحة مطلوبة.',
        ];
    }
}
