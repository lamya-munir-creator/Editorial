<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Mail\SendOtpCode;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $user = $this->createUserFromRequest($request, $request->validated());
        $this->sendOtpCode($user);

        return response()->json([
            'status' => true,
            'message' => 'Please verify your email with the OTP code sent to your inbox.',
            'data' => [
                'user' => $user->load('role', 'roles'),
            ],
        ], 201);
    }

    public function sendOtp(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'username' => ['nullable', 'string', 'max:100'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $this->findUserByEmail($data['email']);

        if (! $user) {
            $user = $this->createUserFromRequest($request, $data, true);
        } else {
            $this->updateUserFromRequest($user, $data);
        }

        $this->sendOtpCode($user);

        return response()->json([
            'status' => true,
            'message' => 'Please verify your email with the OTP code sent to your inbox.',
            'data' => [
                'user' => $user->load('role', 'roles'),
            ],
        ], 200);
    }

    public function verifyEmailOtp(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $this->findUserByEmail($data['email']);

        if (! $user || ! $this->isValidOtp($user, $data['code'])) {
            return response()->json([
                'status' => false,
                'message' => 'The provided OTP code is invalid or has expired.',
            ], 422);
        }

        $user->update([
            'email_verified_at' => now(),
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Email verified successfully.',
            'data' => [
                'user' => $user->load('role', 'roles'),
                'access_token' => $user->createToken('auth_token')->plainTextToken,
                'token_type' => 'Bearer',
            ],
        ], 200);
    }

    public function resendOtp(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $user = $this->findUserByEmail($data['email']);

        if (! $user) {
            return response()->json([
                'status' => false,
                'message' => 'No user found for the provided email.',
            ], 404);
        }

        $this->sendOtpCode($user);

        return response()->json([
            'status' => true,
            'message' => 'A new OTP code has been sent to your email.',
            'data' => [
                'user' => $user->load('role', 'roles'),
            ],
        ], 200);
    }

    public function login(LoginRequest $request)
    {
        $loginInput = $request->input('email');
        $user = User::query()
            ->where('email', $loginInput)
            ->orWhere('username', $loginInput)
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('The provided credentials do not match our records.')],
            ]);
        }

        if ($user->status === 'inactive' || $user->status === 'suspended') {
            return response()->json([
                'status' => false,
                'message' => __('Your account is currently inactive or suspended.'),
            ], 403);
        }

        $user->update(['last_login_at' => now()]);

        return response()->json([
            'status' => true,
            'message' => __('Logged in successfully'),
            'data' => [
                'user' => $user->load('role'),
                'access_token' => $user->createToken('auth_token')->plainTextToken,
                'token_type' => 'Bearer',
            ],
        ], 200);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user instanceof User) {
            $user->tokens()->delete();
        }

        return response()->json([
            'status' => true,
            'message' => __('Logged out successfully'),
        ], 200);
    }

    public function user(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => true,
            'data' => [
                'user' => $user->load('role', 'roles'),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ],
        ], 200);
    }

    private function findUserByEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }

    private function updateUserFromRequest(User $user, array $data): void
    {
        if (! empty($data['first_name'])) {
            $user->first_name = $data['first_name'];
        }

        if (! empty($data['last_name'])) {
            $user->last_name = $data['last_name'];
        }

        if (! empty($data['username'])) {
            $user->username = $data['username'];
        }

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();
    }

    private function createUserFromRequest(Request $request, array $validated, bool $isOtpFlow = false): User
    {
        $defaultRoleId = Role::query()->where('name', 'user')->value('id') ?? 1;
        $firstName = $request->input('first_name', $request->input('name', 'User'));
        $lastName = $request->input('last_name');
        $username = $request->input('username')
            ?? $validated['username']
            ?? $this->generateUsername($validated['email'] ?? $request->input('email'));
        $email = $validated['email'] ?? $request->input('email');
        $password = $validated['password'] ?? $request->input('password');

        $user = User::create([
            'uuid' => (string) Str::uuid(),
            'role_id' => $request->input('role_id', $defaultRoleId),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'username' => $username,
            'email' => $email,
            'password' => Hash::make($password ?? Str::random(16)),
            'status' => $isOtpFlow ? 'inactive' : 'active',
        ]);

        $targetRole = $request->input('role');
        if (! $targetRole && $request->filled('role_id')) {
            $targetRole = Role::query()->where('id', $request->role_id)->value('name');
        }

        if ($targetRole && Role::query()->where('name', $targetRole)->exists()) {
            $user->assignRole($targetRole);
        }

        return $user;
    }

    private function generateUsername(string $email): string
    {
        $base = Str::slug(explode('@', $email)[0]);
        $username = $base.'-'.Str::random(4);

        while (User::query()->where('username', $username)->exists()) {
            $username = $base.'-'.Str::random(4);
        }

        return $username;
    }

    private function sendOtpCode(User $user): void
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->forceFill([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
        ])->save();

        Mail::to($user->email)->send(new SendOtpCode($otp, $user->email));
    }

    private function isValidOtp(User $user, string $code): bool
    {
        if ($user->otp_code === null || $user->otp_expires_at === null) {
            return false;
        }

        return hash_equals((string) $user->otp_code, $code) && now()->lessThanOrEqualTo($user->otp_expires_at);
    }
}


