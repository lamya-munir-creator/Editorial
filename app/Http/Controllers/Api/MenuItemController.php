<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Http\Requests\StoreMenuItemRequest;
use App\Http\Requests\UpdateMenuItemRequest;

class MenuItemController extends Controller
{
    /**
     * 1. عرض جميع عناصر القوائم (مع ترتيبها بـ sort_order)
     */
    public function index()
    {
        $menuItems = MenuItem::orderBy('sort_order', 'asc')->get();

        return response()->json([
            'status' => true,
            'data'   => $menuItems
        ], 200);
    }

    /**
     * 2. عرض عنصر قائمة محدد
     */
    public function show($id)
    {
        $menuItem = MenuItem::findOrFail($id);

        return response()->json([
            'status' => true,
            'data'   => $menuItem
        ], 200);
    }

    /**
     * 3. إضافة عنصر قائمة جديد
     */
    public function store(StoreMenuItemRequest $request)
    {
        $validatedData = $request->validated();

        // تعيين القيم الافتراضية للترتيب والحالة إن لم تُرسل
        $validatedData['target']     = $validatedData['target'] ?? '_self';
        $validatedData['sort_order'] = $validatedData['sort_order'] ?? 0;
        $validatedData['is_active']  = $validatedData['is_active'] ?? true;

        $menuItem = MenuItem::create($validatedData);

        return response()->json([
            'status'  => true,
            'message' => 'تم إضافة عنصر القائمة بنجاح.',
            'data'    => $menuItem
        ], 201);
    }

    /**
     * 4. تعديل عنصر قائمة
     */
    public function update(UpdateMenuItemRequest $request, $id)
    {
        $menuItem = MenuItem::findOrFail($id);
        $validatedData = $request->validated();

        $menuItem->update($validatedData);

        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث عنصر القائمة بنجاح.',
            'data'    => $menuItem
        ], 200);
    }

    /**
     * 5. حذف عنصر قائمة
     */
    public function destroy($id)
    {
        $menuItem = MenuItem::findOrFail($id);
        $menuItem->delete();

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف عنصر القائمة بنجاح.'
        ], 200);
    }
}