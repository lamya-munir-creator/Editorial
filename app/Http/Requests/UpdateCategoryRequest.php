<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category');

        return [
            'parent_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:categories,id',
                Rule::notIn([$categoryId]),
            ],

            'image_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:media,id',
            ],

            'name' => [
                'sometimes',
                'required',
                'string',
                'min:2',
                'max:150',
            ],

            'description' => [
                'sometimes',
                'nullable',
                'string',
            ],

            'meta_title' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'meta_description' => [
                'sometimes',
                'nullable',
                'string',
                'max:500',
            ],

            'sort_order' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
            ],

            'status' => [
                'sometimes',
                'required',
                Rule::in(['active', 'inactive']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'parent_id.integer' => 'معرّف التصنيف الأب يجب أن يكون رقمًا صحيحًا.',
            'parent_id.exists' => 'التصنيف الأب المحدد غير موجود.',
            'parent_id.not_in' => 'لا يمكن أن يكون التصنيف تابعًا لنفسه.',

            'image_id.integer' => 'معرّف الصورة يجب أن يكون رقمًا صحيحًا.',
            'image_id.exists' => 'الصورة المحددة غير موجودة.',

            'name.required' => 'اسم التصنيف مطلوب.',
            'name.string' => 'اسم التصنيف يجب أن يكون نصًا.',
            'name.min' => 'اسم التصنيف يجب ألا يقل عن حرفين.',
            'name.max' => 'اسم التصنيف يجب ألا يزيد عن 150 حرفًا.',

            'description.string' => 'وصف التصنيف يجب أن يكون نصًا.',

            'meta_title.string' => 'عنوان SEO يجب أن يكون نصًا.',
            'meta_title.max' => 'عنوان SEO يجب ألا يزيد عن 255 حرفًا.',

            'meta_description.string' => 'وصف SEO يجب أن يكون نصًا.',
            'meta_description.max' => 'وصف SEO يجب ألا يزيد عن 500 حرف.',

            'sort_order.integer' => 'ترتيب التصنيف يجب أن يكون رقمًا صحيحًا.',
            'sort_order.min' => 'ترتيب التصنيف لا يمكن أن يكون سالبًا.',

            'status.required' => 'حالة التصنيف مطلوبة.',
            'status.in' => 'حالة التصنيف يجب أن تكون active أو inactive.',
        ];
    }
}