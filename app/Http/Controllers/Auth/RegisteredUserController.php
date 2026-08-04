<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegisteredUserController extends Controller
{
    /**
     * معالجة طلب تسجيل مستخدم جديد واستخدام RegisterRequest الخاص بالـ API.
     */
    public function store(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $defaultRoleId = Role::where('name', 'user')->value('id') ?? 2;
        $firstName = $request->input('first_name', $request->input('name'));
        $lastName  = $request->input('last_name');
        $username  = $request->input('username') ?? Str::slug($firstName) . '-' . Str::random(4);

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

        event(new Registered($user));

        $token = $user->createToken('auth_token')->plainTextToken;

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
}
