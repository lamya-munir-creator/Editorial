<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => 'required|string|max:150',
            'location' => 'required|in:header,footer,sidebar',
            'status'   => 'nullable|in:active,inactive',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'اسم القائمة مطلوب.',
            'name.max'          => 'اسم القائمة يجب ألا يتجاوز 150 حرفاً.',
            'location.required' => 'موقع القائمة مطلوب.',
            'location.in'       => 'موقع القائمة غير صالح. القيم المسموحة هي: header, footer, sidebar.',
            'status.in'         => 'حالة القائمة غير صالحة. القيم المسموحة هي: active, inactive.',
        ];
    }
}