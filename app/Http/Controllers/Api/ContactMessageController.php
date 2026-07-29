<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactMessageController extends Controller
{
    // 1. جلب جميع الرسائل
    public function index()
    {
        $messages = ContactMessage::latest()->paginate(15);
        
        return response()->json([
            'status' => true,
            'data'   => $messages
        ], 200);
    }

    // 2. عرض رسالة محددة
    public function show($id)
    {
        $message = ContactMessage::findOrFail($id);
        
        if (!$message->is_read) {
            $message->update(['is_read' => true]);
        }

        return response()->json([
            'status' => true,
            'data'   => $message
        ], 200);
    }

    // 3. استقبال رسالة جديدة
public function store(Request $request)
{
    $validatedData = $request->validate([
        'full_name' => 'required|string|max:255', // تم التعديل من name إلى full_name
        'email'     => 'required|email|max:255',
        'subject'   => 'nullable|string|max:255',
        'message'   => 'required|string|max:2000',
    ]);

    $message = ContactMessage::create($validatedData);

    return response()->json([
        'status'  => true,
        'message' => 'تم إرسال رسالتك بنجاح، شكراً لتواصلك معنا.',
        'data'    => $message
    ], 201);
}

    // 4. الرد على الرسالة عبر البريد
    public function reply(Request $request, $id)
    {
        $message = ContactMessage::findOrFail($id);

        $validatedData = $request->validate([
            'reply_message' => 'required|string|max:3000',
        ]);

        Mail::raw($validatedData['reply_message'], function ($mail) use ($message) {
            $mail->to($message->email)
                 ->subject('رد على استفسارك: ' . ($message->subject ?? 'إدارة الموقع'));
        });

        $message->update([
            'is_replied' => true,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم إرسال الرد بنجاح إلى البريد الإلكتروني للزائر.'
        ], 200);
    }

    // 5. حذف رسالة
    public function destroy($id)
    {
        $message = ContactMessage::findOrFail($id);
        $message->delete();

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف الرسالة بنجاح'
        ], 200);
    }
}