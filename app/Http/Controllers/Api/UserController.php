<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Media;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Role;
use App\Http\Requests\ChangeUserRoleRequest;
use App\Http\Requests\ChangeUserStatusRequest;

class UserController extends Controller
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

        $users = User::with(['role', 'avatar'])
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(4);

        $stats = [
            'total' => User::count(),
            'active' => User::where('status', 'active')->count(),
            'suspended' => User::where('status', 'suspended')->count(),
            'inactive' => User::where('status', 'inactive')->count(),
            'admins' => User::where(function ($q) {
                $q->whereHas('role', fn($r) => $r->where('name', 'like', '%admin%'))
                   ->orWhereHas('roles', fn($r) => $r->where('name', 'like', '%admin%'));
            })->count(),
            'editors' => User::where(function ($q) {
                $q->whereHas('role', fn($r) => $r->where('name', 'like', '%editor%'))
                   ->orWhereHas('roles', fn($r) => $r->where('name', 'like', '%editor%'));
            })->count(),
            'authors' => User::where(function ($q) {
                $q->whereHas('role', fn($r) => $r->where('name', 'like', '%author%'))
                   ->orWhereHas('roles', fn($r) => $r->where('name', 'like', '%author%'));
            })->count(),
            'moderators' => User::where(function ($q) {
                $q->whereHas('role', fn($r) => $r->where('name', 'like', '%moderator%'))
                   ->orWhereHas('roles', fn($r) => $r->where('name', 'like', '%moderator%'));
            })->count(),
            'users' => User::where(function ($q) {
                $q->whereHas('role', fn($r) => $r->where('name', 'like', '%user%'))
                   ->orWhereHas('roles', fn($r) => $r->where('name', 'like', '%user%'));
            })->count(),
        ];

        return response()->json([
            'status' => true,
            'message' => 'تم جلب المستخدمين بنجاح',
            'data' => $users,
            'stats' => $stats,
        ], 200);
    }

    public function store(StoreUserRequest $request)
    {
        $validatedData = $request->validated();

        $user = DB::transaction(function () use ($request, $validatedData) {
            $currentUserId = auth()->id();

            if ($request->hasFile('avatar')) {
                $file = $request->file('avatar');
                $path = $file->store('users', 'public');

                $media = Media::create([
                    'uuid' => Str::uuid(),
                    'uploaded_by' => $currentUserId ?? 1,
                    'file_name' => basename($path),
                    'original_name' => $file->getClientOriginalName(),
                    'disk' => 'public',
                    'path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'extension' => $file->getClientOriginalExtension(),
                    'file_size' => $file->getSize(),
                    'alt_text' => $validatedData['first_name'],
                    'type' => 'image',
                    'visibility' => 'public',
                    'created_by' => $currentUserId,
                ]);

                $validatedData['avatar_id'] = $media->id;
            }

            unset($validatedData['avatar'], $validatedData['password_confirmation']);
            $validatedData['created_by'] = $currentUserId;

            $user = User::create($validatedData);
            if (isset($validatedData['role_id'])) {
                $roleName = \App\Models\Role::find($validatedData['role_id'])?->name;
                if ($roleName) {
                    $user->syncRoles([$roleName]);
                }
            }
            return $user;
        });

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'add_user',
            'action_label' => $this->getActionPrefix() . 'إضافة مستخدم جديد',
            'target_name' => $user->first_name . ' ' . $user->last_name,
            'target_url' => '/users',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم إضافة المستخدم بنجاح',
            'data' => $user->load(['role', 'avatar']),
        ], 201);
    }

    public function show($id)
    {
        $user = User::with(['role', 'avatar', 'authorProfile'])->findOrFail($id);

        return response()->json([
            'status' => true,
            'message' => 'تم جلب بيانات المستخدم بنجاح',
            'data' => $user,
        ], 200);
    }

    public function update(UpdateUserRequest $request, $id)
    {
        $user = User::findOrFail($id);
        $validatedData = $request->validated();

        $user = DB::transaction(function ($request, $validatedData, $user) {
            $currentUserId = auth()->id();

            if (request()->hasFile('avatar')) {
                $file = request()->file('avatar');
                $path = $file->store('users', 'public');

                $media = Media::create([
                    'uuid' => Str::uuid(),
                    'uploaded_by' => $currentUserId ?? 1,
                    'file_name' => basename($path),
                    'original_name' => $file->getClientOriginalName(),
                    'disk' => 'public',
                    'path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'extension' => $file->getClientOriginalExtension(),
                    'file_size' => $file->getSize(),
                    'alt_text' => $validatedData['first_name'] ?? $user->first_name,
                    'type' => 'image',
                    'visibility' => 'public',
                    'created_by' => $currentUserId,
                ]);

                $validatedData['avatar_id'] = $media->id;
            }

            unset($validatedData['avatar'], $validatedData['password_confirmation']);
            $validatedData['updated_by'] = $currentUserId;

            $user->update($validatedData);
            if (isset($validatedData['role_id'])) {
                $roleName = \App\Models\Role::find($validatedData['role_id'])?->name;
                if ($roleName) {
                    $user->syncRoles([$roleName]);
                }
            }

            return $user;
        });

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'edit_user',
            'action_label' => $this->getActionPrefix() . 'تعديل بيانات المستخدم',
            'target_name' => $user->first_name . ' ' . $user->last_name,
            'target_url' => '/users',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث بيانات المستخدم بنجاح',
            'data' => $user->fresh()->load(['role', 'avatar']),
        ], 200);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $name = $user->first_name . ' ' . $user->last_name;
        $user->delete();

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'delete_user',
            'action_label' => $this->getActionPrefix() . 'حذف المستخدم',
            'target_name' => $name,
            'target_url' => null,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم حذف المستخدم بنجاح',
        ], 200);
    }

    public function changeRole(ChangeUserRoleRequest $request, User $user)
    {
        $validated = $request->validated();
        $role = Role::findOrFail($validated['role_id']);

        DB::transaction(function () use ($user, $role) {
            $user->update([
                'role_id'    => $role->id,
                'updated_by' => auth()->id(),
            ]);

            $user->syncRoles([$role->name]);
        });

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'change_role',
            'action_label' => $this->getActionPrefix() . 'تغيير دور المستخدم',
            'target_name' => $user->first_name . ' (' . $role->name . ')',
            'target_url' => '/users',
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم تغيير دور المستخدم بنجاح.',
            'data'    => $user->fresh()->load(['role', 'roles']),
        ], 200);
    }

    public function changeStatus(ChangeUserStatusRequest $request, User $user)
    {
        $validated = $request->validated();

        if ($user->is(auth()->user()) && $validated['status'] === 'suspended') {
            return response()->json([
                'status'  => false,
                'message' => 'لا يمكنك تعليق حسابك الحالي.',
            ], 422);
        }

        $user->update([
            'status'     => $validated['status'],
            'updated_by' => auth()->id(),
        ]);

        if ($validated['status'] === 'suspended') {
            $user->tokens()->delete();
        }

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'change_status',
            'action_label' => $this->getActionPrefix() . 'تغيير حالة حساب المستخدم',
            'target_name' => $user->first_name . ' (' . $validated['status'] . ')',
            'target_url' => '/users',
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث حالة حساب المستخدم بنجاح.',
            'data'    => $user->fresh()->load('role'),
        ], 200);
    }
}