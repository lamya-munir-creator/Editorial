<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * =========================================================================
     * المهمة 1: تسجيل حساب جديد وتوليد التوكن (POST /api/register)
     * مع دعم الترجمة الديناميكية باللغتين العربية والإنجليزي
     * =========================================================================
     */
    public function register(RegisterRequest $request)
    {
        // 1. استقبال البيانات المتحقق منها من RegisterRequest
        $validated = $request->validated();

        // 2. تحديد الدور الافتراضي وتوليد بيانات اسم المستخدم والـ UUID
        $defaultRoleId = Role::where('name', 'user')->value('id') ?? 2;
        $firstName     = $request->input('first_name', $request->input('name'));
        $lastName      = $request->input('last_name');
        $username      = $request->input('username') 
            ?? Str::slug($firstName) . '-' . Str::random(4);

        // 3. إنشاء حساب المستخدم بجدول users
        $user = User::create([
            'uuid'       => (string) Str::uuid(),
            'role_id'    => $request->input('role_id', $defaultRoleId),
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'username'   => $username,
            'email'      => $validated['email'],
            'password'   => Hash::make($validated['password']),
            'status'     => 'active',
        ]);

        // 4. توليد توكن Sanctum للمستخدم الجديد مباشرة
        $token = $user->createToken('auth_token')->plainTextToken;

        // 5. إرجاع استجابة JSON مع التوكن وبنية بيانات المستخدم مترجمة
        return response()->json([
            'status'  => true,
            'message' => __('Account created successfully'),
            'data'    => [
                'user'         => $user->load('role'),
                'access_token' => $token,
                'token_type'   => 'Bearer',
            ]
        ], 201);
    }

    /**
     * =========================================================================
     * المهمة 2: تسجيل الدخول وإرجاع التوكن (POST /api/login)
     * مع دعم الترجمة الديناميكية باللغتين العربية والإنجليزي
     * =========================================================================
     */
    public function login(LoginRequest $request)
    {
        // 1. البحث عن المستخدم بالبريد الإلكتروني أو اسم المستخدم
        $loginInput = $request->input('email');

        $user = User::where('email', $loginInput)
            ->orWhere('username', $loginInput)
            ->first();

        // 2. التحقق من مطابقة بيانات الاعتماد وكلمة المرور
        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('The provided credentials do not match our records.')],
            ]);
        }

        // 3. التأكد من حالة الحساب إذا كان غير فعال أو معطل
        if ($user->status === 'inactive' || $user->status === 'suspended') {
            return response()->json([
                'status'  => false,
                'message' => __('Your account is currently inactive or suspended.'),
            ], 403);
        }

        // 4. تحديث تاريخ آخر تسجيل دخول للمستخدم
        $user->update(['last_login_at' => now()]);

        // 5. توليد توكن Sanctum جديد للمستخدم
        $token = $user->createToken('auth_token')->plainTextToken;

        // 6. إرجاع استجابة JSON مع التوكن مترجمة
        return response()->json([
            'status'  => true,
            'message' => __('Logged in successfully'),
            'data'    => [
                'user'         => $user->load('role'),
                'access_token' => $token,
                'token_type'   => 'Bearer',
            ]
        ], 200);
    }
}
