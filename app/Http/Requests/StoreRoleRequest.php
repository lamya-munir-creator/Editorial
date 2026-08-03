<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                'unique:roles,name',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'status' => [
                'required',
                Rule::in(['active', 'inactive']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم الدور مطلوب.',
            'name.string' => 'اسم الدور يجب أن يكون نصًا.',
            'name.max' => 'اسم الدور يجب ألا يزيد عن 100 حرف.',
            'name.unique' => 'اسم الدور مستخدم مسبقًا.',

            'description.string' => 'وصف الدور يجب أن يكون نصًا.',

            'status.required' => 'حالة الدور مطلوبة.',
            'status.in' => 'حالة الدور يجب أن تكون active أو inactive.',
        ];
    }
}