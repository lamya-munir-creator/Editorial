<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAuthorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'avatar' => [
                'sometimes',
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:2048',
            ],

            'display_name' => [
                'sometimes',
                'required',
                'string',
                'min:3',
                'max:150',
            ],

            'biography' => [
                'sometimes',
                'nullable',
                'string',
            ],

            'job_title' => [
                'sometimes',
                'nullable',
                'string',
                'max:150',
            ],

            'website' => [
                'sometimes',
                'nullable',
                'url',
                'max:255',
            ],

            'facebook' => [
                'sometimes',
                'nullable',
                'url',
                'max:255',
            ],

            'twitter' => [
                'sometimes',
                'nullable',
                'url',
                'max:255',
            ],

            'linkedin' => [
                'sometimes',
                'nullable',
                'url',
                'max:255',
            ],

            'instagram' => [
                'sometimes',
                'nullable',
                'url',
                'max:255',
            ],

            'youtube' => [
                'sometimes',
                'nullable',
                'url',
                'max:255',
            ],

            'gender' => [
                'sometimes',
                'required',
                'in:male,female,other',
            ],

            'status' => [
                'sometimes',
                'required',
                'in:active,inactive',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.integer' => 'معرّف المستخدم يجب أن يكون رقمًا صحيحًا.',
            'user_id.exists' => 'المستخدم المحدد غير موجود.',

            'avatar.image' => 'الملف المرفوع يجب أن يكون صورة.',
            'avatar.mimes' => 'صيغة الصورة يجب أن تكون jpeg أو jpg أو png أو webp.',
            'avatar.max' => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت.',

            'display_name.required' => 'اسم عرض المؤلف مطلوب.',
            'display_name.string' => 'اسم عرض المؤلف يجب أن يكون نصًا.',
            'display_name.min' => 'اسم عرض المؤلف يجب ألا يقل عن 3 أحرف.',
            'display_name.max' => 'اسم عرض المؤلف يجب ألا يزيد عن 150 حرفًا.',

            'biography.string' => 'السيرة الذاتية يجب أن تكون نصًا.',

            'job_title.string' => 'المسمى الوظيفي يجب أن يكون نصًا.',
            'job_title.max' => 'المسمى الوظيفي يجب ألا يزيد عن 150 حرفًا.',

            'website.url' => 'رابط الموقع غير صحيح.',
            'facebook.url' => 'رابط فيسبوك غير صحيح.',
            'twitter.url' => 'رابط تويتر غير صحيح.',
            'linkedin.url' => 'رابط لينكدإن غير صحيح.',
            'instagram.url' => 'رابط إنستغرام غير صحيح.',
            'youtube.url' => 'رابط يوتيوب غير صحيح.',

            'website.max' => 'رابط الموقع يجب ألا يزيد عن 255 حرفًا.',
            'facebook.max' => 'رابط فيسبوك يجب ألا يزيد عن 255 حرفًا.',
            'twitter.max' => 'رابط تويتر يجب ألا يزيد عن 255 حرفًا.',
            'linkedin.max' => 'رابط لينكدإن يجب ألا يزيد عن 255 حرفًا.',
            'instagram.max' => 'رابط إنستغرام يجب ألا يزيد عن 255 حرفًا.',
            'youtube.max' => 'رابط يوتيوب يجب ألا يزيد عن 255 حرفًا.',

            'gender.required' => 'الجنس مطلوب.',
            'gender.in' => 'قيمة الجنس يجب أن تكون male أو female أو other.',

            'status.required' => 'حالة المؤلف مطلوبة.',
            'status.in' => 'حالة المؤلف يجب أن تكون active أو inactive.',
        ];
    }
}