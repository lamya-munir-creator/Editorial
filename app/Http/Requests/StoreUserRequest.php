<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role_id' => [
                'required',
                'integer',
                'exists:roles,id',
            ],

            'first_name' => [
                'required',
                'string',
                'min:2',
                'max:100',
            ],

            'last_name' => [
                'nullable',
                'string',
                'max:100',
            ],

            'username' => [
                'required',
                'string',
                'min:3',
                'max:100',
                'unique:users,username',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'avatar' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:2048',
            ],

            'locale' => [
                'nullable',
                'string',
                'max:10',
            ],

            'timezone' => [
                'nullable',
                'string',
                'max:100',
            ],

            'status' => [
                'required',
                Rule::in([
                    'active',
                    'inactive',
                    'suspended',
                ]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'role_id.required' => 'الدور مطلوب.',
            'role_id.integer' => 'معرّف الدور يجب أن يكون رقمًا صحيحًا.',
            'role_id.exists' => 'الدور المحدد غير موجود.',

            'first_name.required' => 'الاسم الأول مطلوب.',
            'first_name.string' => 'الاسم الأول يجب أن يكون نصًا.',
            'first_name.min' => 'الاسم الأول يجب ألا يقل عن حرفين.',
            'first_name.max' => 'الاسم الأول يجب ألا يزيد عن 100 حرف.',

            'last_name.string' => 'اسم العائلة يجب أن يكون نصًا.',
            'last_name.max' => 'اسم العائلة يجب ألا يزيد عن 100 حرف.',

            'username.required' => 'اسم المستخدم مطلوب.',
            'username.string' => 'اسم المستخدم يجب أن يكون نصًا.',
            'username.min' => 'اسم المستخدم يجب ألا يقل عن 3 أحرف.',
            'username.max' => 'اسم المستخدم يجب ألا يزيد عن 100 حرف.',
            'username.unique' => 'اسم المستخدم مستخدم مسبقًا.',

            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'email.max' => 'البريد الإلكتروني يجب ألا يزيد عن 255 حرفًا.',
            'email.unique' => 'البريد الإلكتروني مستخدم مسبقًا.',

            'password.required' => 'كلمة المرور مطلوبة.',
            'password.string' => 'كلمة المرور يجب أن تكون نصًا.',
            'password.min' => 'كلمة المرور يجب ألا تقل عن 8 أحرف.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',

            'phone.string' => 'رقم الهاتف يجب أن يكون نصًا.',
            'phone.max' => 'رقم الهاتف يجب ألا يزيد عن 30 حرفًا.',

            'avatar.image' => 'الملف المرفوع يجب أن يكون صورة.',
            'avatar.mimes' => 'صيغة الصورة يجب أن تكون jpeg أو jpg أو png أو webp.',
            'avatar.max' => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت.',

            'locale.string' => 'اللغة يجب أن تكون نصًا.',
            'locale.max' => 'رمز اللغة يجب ألا يزيد عن 10 أحرف.',

            'timezone.string' => 'المنطقة الزمنية يجب أن تكون نصًا.',
            'timezone.max' => 'المنطقة الزمنية يجب ألا تزيد عن 100 حرف.',

            'status.required' => 'حالة المستخدم مطلوبة.',
            'status.in' => 'حالة المستخدم يجب أن تكون active أو inactive أو suspended.',
        ];
    }
}