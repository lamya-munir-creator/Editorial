<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateArticleRequest extends FormRequest
{
    /**
     * تحديد ما إذا كان المستخدم مخولاً لإجراء هذا الطلب.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * قواعد التحقق المطلوبة عند تحديث المقال.
     */
    public function rules(): array
    {
        return [
            'title'             => 'sometimes|required|string|max:255',
            'content'           => 'sometimes|required|string',
            'excerpt'           => 'sometimes|nullable|string',
            'category_id'       => 'sometimes|required|exists:categories,id',
            'tags'              => 'nullable|array',
            'tags.*'            => 'exists:tags,id',
            
            // رفع أو تحديث صورة الغلاف
            'image'             => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'featured_image_id' => 'sometimes|nullable|exists:media,id',
            
            'meta_title'        => 'sometimes|nullable|string|max:255',
            'meta_description'  => 'sometimes|nullable|string|max:500',
            'reading_time'      => 'sometimes|nullable|integer|min:1',
            'is_featured'       => 'sometimes|nullable|boolean',
            'allow_comments'    => 'sometimes|nullable|boolean',
'status' => 'sometimes|in:draft,published,archived',        ];
    }

    /**
     * رسائل الخطأ المخصصة باللغة العربية.
     */
    public function messages(): array
    {
        return [
            'title.required'       => 'عنوان المقال مطلوب.',
            'content.required'     => 'محتوى المقال مطلوب.',
            'category_id.exists'   => 'التصنيف المختار غير موجود.',
            'image.image'          => 'الملف المرفوع يجب أن يكون صورة.',
            'image.mimes'          => 'صيغة الصورة يجب أن تكون jpeg, png, jpg, أو webp.',
            'image.max'            => 'حجم الصورة يجب ألا يتجاوز 4 ميجابايت.',
        ];
    }
}
