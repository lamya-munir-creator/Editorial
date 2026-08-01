<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdvertisementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'           => 'required|string|max:255',
            'destination_url' => 'nullable|url|max:500',
            'image'           => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // عند رفع صورة جديدة
            'image_id'        => 'nullable|integer|exists:media,id', // أو في حال اختيار صورة موجودة مسبقاً
            'position'        => 'required|string|max:100',
            'display_order'   => 'nullable|integer|min:0',
            'start_date'      => 'nullable|date',
            'end_date'        => 'nullable|date|after_or_equal:start_date',
            'is_internal'     => 'nullable|boolean',
            'status'          => 'nullable|in:draft,active,inactive,expired',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'      => 'عنوان الإعلان مطلوب.',
            'destination_url.url' => 'يجب إدخال رابط إلكتروني صحيح.',
            'image.image'         => 'يجب أن يكون الملف المرفوع صورة صالحة.',
            'image.mimes'         => 'الصيغ المسموحة للصور هي: jpeg, png, jpg, webp.',
            'image.max'           => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت.',
            'position.required'   => 'موقع الإعلان مطلوب.',
            'status.in'           => 'حالة الإعلان غير صالحة.',
        ];
    }
}