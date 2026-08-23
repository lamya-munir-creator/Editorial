<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
        public function index(Request $request)
    {
        // 1. دعم search و q معاً
        $search = $request->input('search', $request->input('q'));
        $action = $request->input('action');
        $date = $request->input('date');

        $logs = ActivityLog::with(['user'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('target_name', 'like', "%{$search}%")
                      ->orWhere('action_label', 'like', "%{$search}%")
                      ->orWhereHas('user', function ($qu) use ($search) {
                          // 2. البحث في الأعمدة الصحيحة للمستخدم بدلاً من name
                          $qu->where('first_name', 'like', "%{$search}%")
                             ->orWhere('last_name', 'like', "%{$search}%")
                             ->orWhere('username', 'like', "%{$search}%");
                      });
                });
            })
            ->when($action && $action !== 'all', function ($query) use ($action) {
                if ($action === 'add') {
                    $query->where(function ($q) {
                        $q->where('action_type', 'like', '%add%')->orWhere('action_type', 'like', '%publish%');
                    });
                } elseif ($action === 'edit') {
                    $query->where('action_type', 'like', '%edit%');
                } elseif ($action === 'delete') {
                    $query->where('action_type', 'like', '%delete%');
                } elseif ($action === 'system') {
                    $query->where('action_type', 'system_settings');
                } elseif ($action === 'auth') {
                    $query->whereIn('action_type', ['login', 'logout']);
                } else {
                    $query->where('action_type', $action);
                }
            })
            ->when($date, function ($query, $date) {
                $query->whereDate('created_at', $date);
            })
            ->latest()
            ->paginate($request->get('per_page', 5));

        // تنسيق البيانات لتتوافق تماماً مع واجهة الفرونت إند
        $logs->getCollection()->transform(function ($log) {
            // تجميع الاسم الأول والأخير أو استخدام اليوزرنيم
            $fullName = trim(($log->user?->first_name ?? '') . ' ' . ($log->user?->last_name ?? ''));
            if (empty($fullName)) {
                $fullName = $log->user?->username ?? $log->user?->name ?? 'مستخدم نظام';
            }

            return [
                'id' => $log->id,
                'user' => [
                    'id' => $log->user?->id,
                    'name' => $fullName,
                    'avatar' => $log->user?->avatar?->url ?? 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=100&h=100&fit=crop',
                    'role' => $log->user?->role ?? 'مدير',
                ],
                'action_type' => $log->action_type,
                'action_label' => $log->action_label,
                'target_name' => $log->target_name,
                'target_url' => $log->target_url,
                'timestamp' => $log->created_at?->toISOString() ?? now()->toISOString(),
            ];
        });

        return response()->json($logs);
    }

}