<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * قواعد التحقق الخاصة بتحديث البيانات لصفحة ثابتة.
     */
    public function rules(): array
    {
        $pageId = $this->route('page') ? $this->route('page')->id : null;

        return [
            'title'             => 'sometimes|required|string|max:255',
            'slug'              => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('pages', 'slug')->ignore($pageId),
            ],
            'content'           => 'sometimes|required|string',
            
            // تحديث أو رفع صورة بارزة جديدة للصفحة
            'image'             => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'featured_image_id' => 'sometimes|nullable|exists:media,id',
            
            'meta_title'        => 'sometimes|nullable|string|max:255',
            'meta_description'  => 'sometimes|nullable|string|max:500',
            'template'          => 'sometimes|required|string|max:100',
            'is_homepage'       => 'sometimes|boolean',
            'status'            => 'sometimes|required|in:draft,published,archived',
            'published_at'      => 'sometimes|nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'   => 'عنوان الصفحة مطلوب.',
            'content.required' => 'محتوى الصفحة مطلوب.',
            'slug.unique'      => 'اسم الرابط (Slug) مستخدم بالفعل.',
            'image.image'      => 'الملف المرفوع يجب أن يكون صورة صالحة.',
        ];
    }
}
