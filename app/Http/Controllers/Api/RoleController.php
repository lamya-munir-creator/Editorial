<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    /**
     * عرض جميع الأدوار.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $roles = Role::when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10);

        return response()->json([
            'status' => true,
            'message' => 'تم جلب الأدوار بنجاح',
            'data' => $roles,
        ], 200);
    }

    /**
     * إضافة دور جديد.
     */
    public function store(StoreRoleRequest $request)
    {
        $validatedData = $request->validated();

        $validatedData['slug'] = Str::slug($validatedData['name']);

        $role = Role::create($validatedData);

        return response()->json([
            'status' => true,
            'message' => 'تم إنشاء الدور بنجاح',
            'data' => $role,
        ], 201);
    }

    /**
     * عرض دور محدد.
     */
    public function show(Role $role)
    {
        return response()->json([
            'status' => true,
            'data' => $role,
        ], 200);
    }

    /**
     * تحديث دور.
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        $validatedData = $request->validated();

        if (isset($validatedData['name'])) {
            $validatedData['slug'] = Str::slug($validatedData['name']);
        }

        $role->update($validatedData);

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث الدور بنجاح',
            'data' => $role,
        ], 200);
    }

    /**
     * حذف دور.
     */
    public function destroy(Role $role)
    {
        $role->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم حذف الدور بنجاح',
        ], 200);
    }
}