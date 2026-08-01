<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdvertisementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'           => 'sometimes|required|string|max:255',
            'destination_url' => 'sometimes|nullable|url|max:500',
            'image'           => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'image_id'        => 'sometimes|nullable|integer|exists:media,id',
            'position'        => 'sometimes|required|string|max:100',
            'display_order'   => 'sometimes|nullable|integer|min:0',
            'start_date'      => 'sometimes|nullable|date',
            'end_date'        => 'sometimes|nullable|date|after_or_equal:start_date',
            'is_internal'     => 'sometimes|nullable|boolean',
            'status'          => 'sometimes|nullable|in:draft,active,inactive,expired',
        ];
    }

    public function messages(): array
    {
        return [
            'destination_url.url' => 'يجب إدخال رابط إلكتروني صحيح.',
            'image.image'         => 'يجب أن يكون الملف المرفوع صورة صالحة.',
            'image.mimes'         => 'الصيغ المسموحة للصور هي: jpeg, png, jpg, webp.',
            'image.max'           => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت.',
        ];
    }
}