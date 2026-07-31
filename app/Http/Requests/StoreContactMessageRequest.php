<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => 'required|string|max:150',
            'email'     => 'required|email|max:255',
            'phone'     => 'nullable|string|max:30',
            'subject'   => 'nullable|string|max:255',
            'message'   => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'الاسم الكامل مطلوب.',
            'full_name.max'      => 'الاسم يجب ألا يتجاوز 150 حرفاً.',
            'email.required'    => 'البريد الإلكتروني مطلوب.',
            'email.email'       => 'يرجى إدخال بريد إلكتروني صحيح.',
            'phone.max'         => 'رقم الهاتف يجب ألا يتجاوز 30 حرفاً.',
            'message.required'  => 'نص الرسالة مطلوب.',
        ];
    }
}