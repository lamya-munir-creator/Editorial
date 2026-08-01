<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'menu_id'     => 'sometimes|required|exists:menus,id',
            'parent_id'   => 'nullable|exists:menu_items,id',
            'page_id'     => 'nullable|exists:pages,id',
            'article_id'  => 'nullable|exists:articles,id',
            'category_id' => 'nullable|exists:categories,id',
            'title'       => 'sometimes|required|string|max:255',
            'url'         => 'nullable|string|max:500',
            'target'      => 'nullable|string|max:20',
            'icon'        => 'nullable|string|max:100',
            'sort_order'  => 'nullable|integer',
            'is_active'   => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'menu_id.exists'    => 'القائمة المحددة غير موجودة.',
            'title.required'    => 'عنوان عنصر القائمة مطلوب.',
            'title.max'         => 'العنوان يجب ألا يتجاوز 255 حرفاً.',
            'url.max'           => 'الرابط يجب ألا يتجاوز 500 حرف.',
            'parent_id.exists'  => 'العنصر الأب المحدد غير موجود.',
            'page_id.exists'    => 'الصفحة المحددة غير موجودة.',
            'article_id.exists' => 'المقال المحدد غير موجود.',
            'category_id.exists'=> 'القسم المحدد غير موجود.',
        ];
    }
}