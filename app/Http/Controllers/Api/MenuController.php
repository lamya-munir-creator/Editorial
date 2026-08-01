<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Http\Requests\StoreMenuRequest;
use App\Http\Requests\UpdateMenuRequest;
use Illuminate\Support\Str;

class MenuController extends Controller
{
    /**
     * 1. عرض جميع القوائم
     */
    public function index()
    {
        $menus = Menu::latest()->paginate(15);

        return response()->json([
            'status' => true,
            'data'   => $menus
        ], 200);
    }

    /**
     * 2. عرض قائمة محددة
     */
    public function show($id)
    {
        $menu = Menu::findOrFail($id);

        return response()->json([
            'status' => true,
            'data'   => $menu
        ], 200);
    }

    /**
     * 3. إنشاء قائمة جديدة
     */
    public function store(StoreMenuRequest $request)
    {
        $validatedData = $request->validated();

        $validatedData['uuid']       = (string) Str::uuid();
        $validatedData['status']     = $validatedData['status'] ?? 'active';
        $validatedData['created_by'] = auth()->id() ?? 1;

        $menu = Menu::create($validatedData);

        return response()->json([
            'status'  => true,
            'message' => 'تم إنشاء القائمة بنجاح.',
            'data'    => $menu
        ], 201);
    }

    /**
     * 4. تعديل قائمة
     */
    public function update(UpdateMenuRequest $request, $id)
    {
        $menu = Menu::findOrFail($id);
        $validatedData = $request->validated();

        $validatedData['updated_by'] = auth()->id() ?? 1;

        $menu->update($validatedData);

        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث القائمة بنجاح.',
            'data'    => $menu
        ], 200);
    }

    /**
     * 5. حذف قائمة
     */
    public function destroy($id)
    {
        $menu = Menu::findOrFail($id);
        $menu->delete();

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف القائمة بنجاح.'
        ], 200);
    }
}