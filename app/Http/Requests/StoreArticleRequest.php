<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreArticleRequest extends FormRequest
{
    /**
     * تحديد ما إذا كان المستخدم مخولاً لإجراء هذا الطلب.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * قواعد التحقق المطلوبة عند إنشاء مقال جديد (بيانات نصية + صورة غلاف اختيارية).
     */
    public function rules(): array
    {
        return [
            // العنوان ومحتوى المقال
            'title'             => 'required|string|max:255',
            'content'           => 'required|string',
            'excerpt'           => 'nullable|string',
            
            // التعديل والربط
            'category_id'       => 'required|exists:categories,id',
            'tags'              => 'nullable|array',
            'tags.*'            => 'exists:tags,id',
            
            // رفع الصورة البارزة (إما رفع ملف صورة مباشر أو اختيار معرف ميديا موجود)
            'image'             => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'featured_image_id' => 'nullable|exists:media,id',
            
            // محركات البحث SEO والإحصائيات
            'meta_title'        => 'nullable|string|max:255',
            'meta_description'  => 'nullable|string|max:500',
            'reading_time'      => 'nullable|integer|min:1',
            'is_featured'       => 'nullable|boolean',
            'allow_comments'    => 'nullable|boolean',
            'status'            => 'required|in:draft,published,archived',
        ];
    }

    /**
     * رسائل الخطأ المخصصة باللغة العربية.
     */
    public function messages(): array
    {
        return [
            'title.required'       => 'عنوان المقال مطلوب.',
            'content.required'     => 'محتوى المقال مطلوب.',
            'category_id.required' => 'يرجى اختيار تصنيف صالح للمقال.',
            'category_id.exists'   => 'التصنيف المختار غير موجود.',
            'image.image'          => 'الملف المرفوع يجب أن يكون صورة.',
            'image.mimes'          => 'صيغة الصورة يجب أن تكون jpeg, png, jpg, أو webp.',
            'image.max'            => 'حجم الصورة يجب ألا يتجاوز 4 ميجابايت.',
            'status.required'      => 'حالة المقال مطلوبة.',
            'status.in'            => 'حالة المقال غير صالحة.',
        ];
    }
}
