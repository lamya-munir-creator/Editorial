<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAuthorApplicationRequest extends FormRequest
{
    /**
     * تحديد ما إذا كان المستخدم مخولًا لإرسال الطلب.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * قواعد التحقق من طلب الانضمام ككاتب.
     */
    public function rules(): array
    {
        return [
            'display_name' => [
                'required',
                'string',
                'min:3',
                'max:150',
            ],

            'job_title' => [
                'nullable',
                'string',
                'max:150',
            ],

            'biography' => [
                'nullable',
                'string',
            ],

            'website' => [
                'nullable',
                'url',
                'max:255',
            ],

            'application_message' => [
                'nullable',
                'string',
            ],
        ];
    }

    /**
     * رسائل التحقق باللغة العربية.
     */
    public function messages(): array
    {
        return [
            'display_name.required' =>
                'اسم العرض مطلوب.',

            'display_name.string' =>
                'اسم العرض يجب أن يكون نصًا.',

            'display_name.min' =>
                'اسم العرض يجب ألا يقل عن 3 أحرف.',

            'display_name.max' =>
                'اسم العرض يجب ألا يزيد عن 150 حرفًا.',

            'job_title.string' =>
                'المسمى الوظيفي يجب أن يكون نصًا.',

            'job_title.max' =>
                'المسمى الوظيفي يجب ألا يزيد عن 150 حرفًا.',

            'biography.string' =>
                'السيرة الذاتية يجب أن تكون نصًا.',

            'website.url' =>
                'رابط الموقع غير صحيح.',

            'website.max' =>
                'رابط الموقع يجب ألا يزيد عن 255 حرفًا.',

            'application_message.string' =>
                'رسالة التقديم يجب أن تكون نصًا.',
        ];
    }
}