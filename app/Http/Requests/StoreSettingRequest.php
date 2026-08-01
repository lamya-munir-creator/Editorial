<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $valueRule = 'nullable';
        
        // تطبيق شروط المدربة المحددة في السلايد إذا كانت القيمة صورة
        if ($this->input('value_type') === 'image') {
            $valueRule = 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048';
        } elseif ($this->input('value_type') === 'file') {
            $valueRule = 'nullable|file|max:10240';
        }

        return [
            'setting_key'   => 'required|string|max:150|unique:settings,setting_key',
            'setting_value' => $valueRule,
            'group_name'    => 'required|string|max:100',
            'value_type'    => 'required|string|max:50',
            'is_public'     => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'setting_key.required' => 'مفتاح الإعداد مطلوب.',
            'setting_key.unique'   => 'مفتاح الإعداد هذا مستخدم بالفعل.',
            'group_name.required'  => 'اسم المجموعة مطلوب.',
            'value_type.required'  => 'نوع القيمة مطلوب.',
            'setting_value.image'  => 'يجب أن يكون الملف صورة صالحة.',
            'setting_value.mimes'  => 'الصيغ المسموحة للصور هي: jpeg, png, jpg, webp.',
            'setting_value.max'    => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت.',
        ];
    }
}