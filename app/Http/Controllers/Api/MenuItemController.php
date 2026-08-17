<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\ActivityLog;
use App\Http\Requests\StoreMenuItemRequest;
use App\Http\Requests\UpdateMenuItemRequest;

class MenuItemController extends Controller
{
    private function getActionPrefix(): string
    {
        $user = auth()->user();
        $firstName = $user ? $user->first_name : '';
        $isFemale = $firstName && (mb_substr($firstName, -1) === 'ة' || mb_substr($firstName, -1) === 'ه');
        return $isFemale ? 'قامت بـ' : 'قام بـ';
    }

    public function index()
    {
        $menuItems = MenuItem::orderBy('sort_order', 'asc')->get();

        return response()->json([
            'status' => true,
            'data'   => $menuItems
        ], 200);
    }

    public function show($id)
    {
        $menuItem = MenuItem::findOrFail($id);

        return response()->json([
            'status' => true,
            'data'   => $menuItem
        ], 200);
    }

    public function store(StoreMenuItemRequest $request)
    {
        $validatedData = $request->validated();

        $validatedData['target']     = $validatedData['target'] ?? '_self';
        $validatedData['sort_order'] = $validatedData['sort_order'] ?? 0;
        $validatedData['is_active']  = $validatedData['is_active'] ?? true;

        $menuItem = MenuItem::create($validatedData);

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'add_menu_item',
            'action_label' => $this->getActionPrefix() . 'إضافة عنصر للقائمة',
            'target_name' => $menuItem->title ?? 'عصر قائمة',
            'target_url' => '/menus',
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم إضافة عنصر القائمة بنجاح.',
            'data'    => $menuItem
        ], 201);
    }

    public function update(UpdateMenuItemRequest $request, $id)
    {
        $menuItem = MenuItem::findOrFail($id);
        $validatedData = $request->validated();

        $menuItem->update($validatedData);

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'edit_menu_item',
            'action_label' => $this->getActionPrefix() . 'تحديث عنصر القائمة',
            'target_name' => $menuItem->title ?? 'عنصر قائمة',
            'target_url' => '/menus',
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث عنصر القائمة بنجاح.',
            'data'    => $menuItem
        ], 200);
    }

    public function destroy($id)
    {
        $menuItem = MenuItem::findOrFail($id);
        $title = $menuItem->title ?? 'عنصر قائمة';
        $menuItem->delete();

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'delete_menu_item',
            'action_label' => $this->getActionPrefix() . 'حذف عنصر القائمة',
            'target_name' => $title,
            'target_url' => null,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف عنصر القائمة بنجاح.'
        ], 200);
    }
}