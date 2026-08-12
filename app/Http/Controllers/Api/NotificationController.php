<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $notifications = $user->notifications()->take(50)->get()->map(function($notification) {
            return [
                'id' => $notification->id,
                'text' => $notification->data['message'] ?? 'إشعار جديد',
                'type' => $notification->data['type'] ?? 'info',
                'time' => $notification->created_at->diffForHumans(),
                'isRead' => $notification->read_at !== null,
                'created_at' => $notification->created_at
            ];
        });

        return response()->json($notifications);
    }

    /**
     * Mark a specific notification as read
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->find($id);
        
        if ($notification) {
            $notification->markAsRead();
            return response()->json(['message' => 'تم التحديد كمقروء']);
        }

        return response()->json(['message' => 'الإشعار غير موجود'], 404);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['message' => 'تم تحديد الكل كمقروء']);
    }
}
