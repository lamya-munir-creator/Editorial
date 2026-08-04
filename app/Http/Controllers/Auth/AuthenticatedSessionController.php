<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * معالجة طلب تسجيل الدخول وتوليد التوكن مطابقاً لهيكلية المشروع.
     */
    public function store(LoginRequest $request): JsonResponse
    {
        $request->ensureIsNotRateLimited();

        $loginInput = $request->input('email');
        $user = User::where('email', $loginInput)
            ->orWhere('username', $loginInput)
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('بيانات الاعتماد المقدمة غير صحيحة.')],
            ]);
        }

        if ($user->status === 'inactive' || $user->status === 'suspended') {
            return response()->json([
                'status'  => false,
                'message' => __('حسابك غير مفعل أو معطل حالياً.'),
            ], 403);
        }

        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status'  => true,
            'message' => __('تم تسجيل الدخول بنجاح'),
            'data'    => [
                'user'         => $user->load('role'),
                'access_token' => $token,
                'token_type'   => 'Bearer',
            ]
        ], 200);
    }

    /**
     * إنهاء الجلسة وتسجيل الخروج وحذف التوكن الحالي.
     */
    public function destroy(Request $request): JsonResponse
    {
        if ($request->user() && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'status'  => true,
            'message' => __('تم تسجيل الخروج بنجاح'),
        ], 200);
    }
}
