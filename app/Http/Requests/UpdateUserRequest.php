<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:roles,id',
            ],

            'first_name' => [
                'sometimes',
                'required',
                'string',
                'min:2',
                'max:100',
            ],

            'last_name' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'username' => [
                'sometimes',
                'required',
                'string',
                'min:3',
                'max:100',
                Rule::unique('users', 'username')->ignore($this->route('user')),
            ],

            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->route('user')),
            ],

            'password' => [
                'sometimes',
                'required',
                'string',
                'min:8',
                'confirmed',
            ],

            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:30',
            ],

            'avatar' => [
                'sometimes',
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:2048',
            ],

            'locale' => [
                'sometimes',
                'nullable',
                'string',
                'max:10',
            ],

            'timezone' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'status' => [
                'sometimes',
                'required',
                Rule::in(['active', 'inactive', 'suspended']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'role_id.required' => 'الدور مطلوب.',
            'role_id.exists' => 'الدور المحدد غير موجود.',

            'first_name.required' => 'الاسم الأول مطلوب.',
            'first_name.min' => 'الاسم الأول يجب ألا يقل عن حرفين.',

            'username.required' => 'اسم المستخدم مطلوب.',
            'username.unique' => 'اسم المستخدم مستخدم مسبقًا.',

            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'email.unique' => 'البريد الإلكتروني مستخدم مسبقًا.',

            'password.min' => 'كلمة المرور يجب ألا تقل عن 8 أحرف.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',

            'avatar.image' => 'الملف المرفوع يجب أن يكون صورة.',
            'avatar.mimes' => 'صيغة الصورة يجب أن تكون jpeg أو jpg أو png أو webp.',
            'avatar.max' => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت.',

            'status.in' => 'حالة المستخدم يجب أن تكون active أو inactive أو suspended.',
        ];
    }
}