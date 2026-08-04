<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // السماح للجميع بطلب التسجيل
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['nullable', 'string', 'max:100'],
            'username'   => ['required', 'string', 'max:100', 'lowercase', 'unique:users,username'],
            'email'      => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'   => ['required', 'string', 'min:8', 'confirmed'], // يتطلب إرسال password_confirmation
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'الاسم الأول مطلوب.',
            'username.required'   => 'اسم المستخدم مطلوب.',
            'username.unique'     => 'اسم المستخدم مستخدم بالفعل.',
            'email.required'      => 'البريد الإلكتروني مطلوب.',
            'email.unique'        => 'البريد الإلكتروني مسجل سابقاً.',
'password.confirmed' => 'كلمة المرور وتأكيد كلمة المرور غير متطابقين.',
            'password.min'        => 'كلمة المرور يجب ألا تقل عن 8 أحرف.',
        ];
    }
}
