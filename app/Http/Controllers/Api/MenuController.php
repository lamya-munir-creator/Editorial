<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MenuController extends Controller
{
    /**
     * عرض جميع القوائم.
     */
    public function index()
    {
        $menus = Menu::with([
            'creator',
            'updater',
            'items',
        ])
            ->latest()
            ->paginate(10);

        return response()->json([
            'status' => true,
            'data' => $menus->items(),
            'meta' => [
                'current_page' => $menus->currentPage(),
                'last_page' => $menus->lastPage(),
                'per_page' => $menus->perPage(),
                'total' => $menus->total(),
            ],
        ], 200);
    }

    /**
     * إنشاء قائمة جديدة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'location' => 'required|in:header,footer,sidebar',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['uuid'] = Str::uuid();
        $validated['created_by'] = auth()->id() ?? 1;
        $validated['updated_by'] = null;

        $menu = Menu::create($validated);

        return response()->json([
            'status' => true,
            'message' => 'تم إنشاء القائمة بنجاح',
            'data' => $menu->load([
                'creator',
                'updater',
                'items',
            ]),
        ], 201);
    }

    /**
     * عرض قائمة واحدة.
     */
    public function show(Menu $menu)
    {
        return response()->json([
            'status' => true,
            'data' => $menu->load([
                'creator',
                'updater',
                'items',
            ]),
        ], 200);
    }

    /**
     * تحديث القائمة.
     */
    public function update(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:150',
            'location' => 'sometimes|required|in:header,footer,sidebar',
            'status' => 'sometimes|required|in:active,inactive',
        ]);

        $validated['updated_by'] = auth()->id() ?? 1;

        $menu->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث القائمة بنجاح',
            'data' => $menu->fresh()->load([
                'creator',
                'updater',
                'items',
            ]),
        ], 200);
    }

    /**
     * حذف القائمة.
     */
    public function destroy(Menu $menu)
    {
        $menu->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم حذف القائمة بنجاح',
        ], 200);
    }

}