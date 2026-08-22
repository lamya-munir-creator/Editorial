<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\ActivityLog;
use App\Http\Requests\StoreContactMessageRequest;
use App\Http\Requests\UpdateContactMessageRequest;
use App\Http\Requests\ReplyContactMessageRequest;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Notifications\SystemAlert;
use Illuminate\Support\Facades\Notification;
use App\Notifications\SystemNotification;
use App\Models\User;

class ContactMessageController extends Controller
{
    private function getActionPrefix(): string
    {
        $user = auth()->user();
        $firstName = $user ? $user->first_name : '';
        $isFemale = $firstName && (mb_substr($firstName, -1) === 'ة' || mb_substr($firstName, -1) === 'ه');
        return $isFemale ? 'قامت بـ' : 'قام بـ';
    }

    public function index()
    {
        $perPage = request('per_page', 4);
        $query = ContactMessage::latest();
        
        if (request()->has('status') && request('status') !== 'all') {
            $query->where('status', request('status'));
        }
        
        if (request()->has('search') && !empty(request('search'))) {
            $searchStr = request('search');
            $query->where(function($q) use ($searchStr) {
                $q->where('full_name', 'like', "%{$searchStr}%")
                  ->orWhere('email', 'like', "%{$searchStr}%")
                  ->orWhere('subject', 'like', "%{$searchStr}%");
            });
        }

        $messages = $query->paginate($perPage);

        return response()->json([
            'status' => true,
            'data'   => $messages
        ], 200);
    }

    public function show($id)
    {
        $message = ContactMessage::findOrFail($id);

        if ($message->status === 'new') {
            $message->update(['status' => 'read']);
        }

        return response()->json([
            'status' => true,
            'data'   => $message
        ], 200);
    }

            public function store(StoreContactMessageRequest $request)
    {
        $validatedData = $request->validated();
        
        $validatedData['uuid']   = (string) Str::uuid();
        $validatedData['status'] = 'new';

        $message = ContactMessage::create($validatedData);

        // --- نظام الإشعارات الجديد (رسالة تواصل) ---
        // جلب أرقام (IDs) الصلاحيات الخاصة بمدراء النظام
        $adminRoleIds = \App\Models\Role::whereIn('name', ['admin', 'super-admin'])->pluck('id');
        
        // جلب المستخدمين الذين يملكون هذه الصلاحيات حصراً
        $admins = User::whereIn('role_id', $adminRoleIds)->get();

        Notification::send($admins, new \App\Notifications\SystemNotification(
            'رسالة واردة من (' . $message->full_name . ')', 
            'info', 
            '/admin/contact-messages' 
        ));
        // ----------------------------------------

        return response()->json([
            'status'  => true,
            'message' => 'تم إرسال رسالتك بنجاح. شكراً لتواصلك معنا.',
            'data'    => $message
        ], 201);
    }



    public function reply(ReplyContactMessageRequest $request, $id)
    {
        $message = ContactMessage::findOrFail($id);
        $validatedData = $request->validated();

        Mail::raw($validatedData['reply_message'], function ($mail) use ($message) {
            $mail->to($message->email)
                 ->subject('رد على استفسارك: ' . ($message->subject ?? 'إدارة الملومن'));
        });

        $message->update([
            'status'     => 'replied',
            'replied_at' => now(),
            'handled_by' => auth()->id() ?? 1,
        ]);

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'reply_contact',
            'action_label' => $this->getActionPrefix() . 'الرد على رسالة تواصل',
            'target_name' => $message->full_name ?? 'رسالة زائر',
            'target_url' => '/contact-messages',
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم إرسال الرد بنجاح إلى البريد الإلكتروني للزائر.'
        ], 200);
    }

    public function update(UpdateContactMessageRequest $request, $id)
    {
        $message = ContactMessage::findOrFail($id);
        $validatedData = $request->validated();

        $validatedData['handled_by'] = auth()->id() ?? 1;

        $message->update($validatedData);

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'update_contact',
            'action_label' => $this->getActionPrefix() . 'تحديث حالة رسالة التواصل',
            'target_name' => $message->full_name ?? 'رسالة زائر',
            'target_url' => '/contact-messages',
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث حالة الرسالة بنجاح',
            'data'    => $message
        ], 200);
    }

    public function destroy($id)
    {
        $message = ContactMessage::findOrFail($id);
        $name = $message->full_name ?? 'رسالة';
        $message->delete();

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'delete_contact',
            'action_label' => $this->getActionPrefix() . 'حذف رسالة تواصل',
            'target_name' => $name,
            'target_url' => null,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف الرسالة بنجاح'
        ], 200);
    }
}