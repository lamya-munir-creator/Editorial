<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\ActivityLog;
use App\Http\Requests\StoreMenuRequest;
use App\Http\Requests\UpdateMenuRequest;
use Illuminate\Support\Str;

class MenuController extends Controller
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
        $menus = Menu::latest()->paginate(15);

        return response()->json([
            'status' => true,
            'data'   => $menus
        ], 200);
    }

    public function show($id)
    {
        $menu = Menu::findOrFail($id);

        return response()->json([
            'status' => true,
            'data'   => $menu
        ], 200);
    }

    public function store(StoreMenuRequest $request)
    {
        $validatedData = $request->validated();

        $validatedData['uuid']       = (string) Str::uuid();
        $validatedData['status']     = $validatedData['status'] ?? 'active';
        $validatedData['created_by'] = auth()->id() ?? 1;

        $menu = Menu::create($validatedData);

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'add_menu',
            'action_label' => $this->getActionPrefix() . 'إنشاء قائمة جديدة',
            'target_name' => $menu->name ?? 'قائمة',
            'target_url' => '/menus',
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم إنشاء القائمة بنجاح.',
            'data'    => $menu
        ], 201);
    }

    public function update(UpdateMenuRequest $request, $id)
    {
        $menu = Menu::findOrFail($id);
        $validatedData = $request->validated();

        $validatedData['updated_by'] = auth()->id() ?? 1;

        $menu->update($validatedData);

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'edit_menu',
            'action_label' => $this->getActionPrefix() . 'تحديث القائمة',
            'target_name' => $menu->name ?? 'قائمة',
            'target_url' => '/menus',
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث القائمة بنجاح.',
            'data'    => $menu
        ], 200);
    }

    public function destroy($id)
    {
        $menu = Menu::findOrFail($id);
        $name = $menu->name ?? 'قائمة';
        $menu->delete();

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'delete_menu',
            'action_label' => $this->getActionPrefix() . 'حذف القائمة',
            'target_name' => $name,
            'target_url' => null,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف القائمة بنجاح.'
        ], 200);
    }
}