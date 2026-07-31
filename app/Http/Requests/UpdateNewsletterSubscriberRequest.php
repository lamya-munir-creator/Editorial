<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNewsletterSubscriberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // جلب معرف المشترك لاستثنائه عند التحقق من تكرار البريد الإلكتروني
        $subscriberId = $this->route('newsletter_subscriber') ?? $this->route('id');

        return [
            'email'     => 'sometimes|required|email|max:255|unique:newsletter_subscribers,email,' . $subscriberId,
            'full_name' => 'nullable|string|max:150',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email'    => 'يرجى إدخال بريد إلكتروني صحيح.',
            'email.max'      => 'البريد الإلكتروني يجب ألا يتجاوز 255 حرفاً.',
            'email.unique'   => 'هذا البريد الإلكتروني مُستخدم بالفعل لمشترك آخر.',
            'full_name.max'  => 'اسم المشترك يجب ألا يتجاوز 150 حرفاً.',
        ];
    }
}