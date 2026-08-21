<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        // التحقق إذا كان المستخدم مسجلاً دخوله
        if (auth()->check()) {
            $user = auth()->user();

            // فحص حالة الحساب (سواء كان في جدول users أو جدول authors إذا كان مرتبطاً)
            // إذا كان حقل status يساوي inactive
            if (isset($user->status) && $user->status === 'inactive') {
                
                // تسجيل خروج المستخدم فوراً وحذف الـ Tokens
                auth()->user()->tokens()->delete();
                auth()->logout();

                return response()->json([
                    'status' => false,
                    'message' => 'عذراً، تم تعطيل حسابك من قبل الإدارة. يرجى مراجعة الدعم الفني.'
                ], 403);
            }
        }

        return $next($request);
    }
}