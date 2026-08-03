<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Media;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * عرض قائمة المستخدمين مع البحث والتقسيم.
     */
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
            ->paginate(10);

        return response()->json([
            'status' => true,
            'message' => 'تم جلب المستخدمين بنجاح',
            'data' => $users,
        ], 200);
    }

    /**
     * إضافة مستخدم جديد مع رفع الصورة الشخصية.
     */
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
                    'width' => null,
                    'height' => null,
                    'duration' => null,
                    'alt_text' => $validatedData['first_name'],
                    'caption' => null,
                    'type' => 'image',
                    'visibility' => 'public',
                    'created_by' => $currentUserId,
                    'updated_by' => null,
                ]);

                $validatedData['avatar_id'] = $media->id;
            }

            unset(
                $validatedData['avatar'],
                $validatedData['password_confirmation']
            );

            $validatedData['created_by'] = $currentUserId;

            return User::create($validatedData);
        });

        return response()->json([
            'status' => true,
            'message' => 'تم إضافة المستخدم بنجاح',
            'data' => $user->load(['role', 'avatar']),
        ], 201);
    }

    /**
     * عرض مستخدم محدد.
     */
    public function show($id)
    {
        $user = User::with([
            'role',
            'avatar',
            'authorProfile',
        ])->findOrFail($id);

        return response()->json([
            'status' => true,
            'message' => 'تم جلب بيانات المستخدم بنجاح',
            'data' => $user,
        ], 200);
    }

    /**
     * تحديث بيانات المستخدم مع إمكانية تغيير الصورة.
     */
    public function update(UpdateUserRequest $request, $id)
    {
        $user = User::findOrFail($id);

        $validatedData = $request->validated();

        $user = DB::transaction(function () use (
            $request,
            $validatedData,
            $user
        ) {
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
                    'width' => null,
                    'height' => null,
                    'duration' => null,
                    'alt_text' => $validatedData['first_name']
                        ?? $user->first_name,
                    'caption' => null,
                    'type' => 'image',
                    'visibility' => 'public',
                    'created_by' => $currentUserId,
                    'updated_by' => null,
                ]);

                $validatedData['avatar_id'] = $media->id;
            }

            unset(
                $validatedData['avatar'],
                $validatedData['password_confirmation']
            );

            $validatedData['updated_by'] = $currentUserId;

            $user->update($validatedData);

            return $user;
        });

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث بيانات المستخدم بنجاح',
            'data' => $user->fresh()->load(['role', 'avatar']),
        ], 200);
    }

    /**
     * حذف المستخدم حذفًا منطقيًا.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        $user->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم حذف المستخدم بنجاح',
        ], 200);
    }
}