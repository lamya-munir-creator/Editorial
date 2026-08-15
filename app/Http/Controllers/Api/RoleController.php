<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Role;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    private function getActionPrefix(): string
    {
        $user = auth()->user();
        $firstName = $user ? $user->first_name : '';
        $isFemale = $firstName && (mb_substr($firstName, -1) === 'ة' || mb_substr($firstName, -1) === 'ه');
        return $isFemale ? 'قامت بـ' : 'قام بـ';
    }

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

    public function store(StoreRoleRequest $request)
    {
        $validatedData = $request->validated();
        $validatedData['slug'] = Str::slug($validatedData['name']);

        $role = Role::create($validatedData);

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'add_role',
            'action_label' => $this->getActionPrefix() . 'إنشاء دور إداري جديد',
            'target_name' => $role->name,
            'target_url' => '/roles',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم إنشاء الدور بنجاح',
            'data' => $role,
        ], 201);
    }

    public function show(Role $role)
    {
        return response()->json([
            'status' => true,
            'data' => $role,
        ], 200);
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        $validatedData = $request->validated();

        if (isset($validatedData['name'])) {
            $validatedData['slug'] = Str::slug($validatedData['name']);
        }

        $role->update($validatedData);

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'edit_role',
            'action_label' => $this->getActionPrefix() . 'تعديل الدور الإداري',
            'target_name' => $role->name,
            'target_url' => '/roles',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث الدور بنجاح',
            'data' => $role,
        ], 200);
    }

    public function destroy(Role $role)
    {
        $name = $role->name;
        $role->delete();

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'delete_role',
            'action_label' => $this->getActionPrefix() . 'حذف الدور الإداري',
            'target_name' => $name,
            'target_url' => null,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم حذف الدور بنجاح',
        ], 200);
    }
}