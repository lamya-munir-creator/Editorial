<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $settingId = $this->route('setting') ?? $this->route('id');

        $valueRule = 'nullable';
        
        if ($this->hasFile('setting_value')) {
            if ($this->input('value_type') === 'image') {
                $valueRule = 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048';
            } else {
                $valueRule = 'nullable|file|max:10240';
            }
        }

        return [
            'setting_key'   => 'sometimes|required|string|max:150|unique:settings,setting_key,' . $settingId,
            'setting_value' => $valueRule,
            'group_name'    => 'sometimes|required|string|max:100',
            'value_type'    => 'sometimes|required|string|max:50',
            'is_public'     => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'setting_key.unique'  => 'مفتاح الإعداد هذا مستخدم بالفعل.',
            'setting_value.image' => 'يجب أن يكون الملف صورة صالحة.',
            'setting_value.mimes' => 'الصيغ المسموحة للصور هي: jpeg, png, jpg, webp.',
            'setting_value.max'   => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت.',
        ];
    }
}