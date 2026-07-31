<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Http\Requests\StoreContactMessageRequest;
use App\Http\Requests\UpdateContactMessageRequest;
use App\Http\Requests\ReplyContactMessageRequest;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ContactMessageController extends Controller
{
    /**
     * 1. جلب جميع الرسائل
     */
    public function index()
    {
        $messages = ContactMessage::latest()->paginate(15);

        return response()->json([
            'status' => true,
            'data'   => $messages
        ], 200);
    }

    /**
     * 2. عرض رسالة محددة (وتحويل حالتها إلى read إذا كانت new)
     */
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

    /**
     * 3. استقبال رسالة جديدة من زائر الموقع
     */
    public function store(StoreContactMessageRequest $request)
    {
        $validatedData = $request->validated();
        
        // توليد الـ uuid والحالة الافتراضية
        $validatedData['uuid']   = (string) Str::uuid();
        $validatedData['status'] = 'new';

        $message = ContactMessage::create($validatedData);

        return response()->json([
            'status'  => true,
            'message' => 'تم إرسال رسالتك بنجاح، شكراً لتواصلك معنا.',
            'data'    => $message
        ], 201);
    }

    /**
     * 4. الرد على الرسالة عبر البريد الإلكتروني
     */
    public function reply(ReplyContactMessageRequest $request, $id)
    {
        $message = ContactMessage::findOrFail($id);
        $validatedData = $request->validated();

        // إرسال الإيميل
        Mail::raw($validatedData['reply_message'], function ($mail) use ($message) {
            $mail->to($message->email)
                 ->subject('رد على استفسارك: ' . ($message->subject ?? 'إدارة الموقع'));
        });

        // تحديث حالة الرسالة في قاعدة البيانات
        $message->update([
            'status'     => 'replied',
            'replied_at' => now(),
            'handled_by' => auth()->id() ?? 1,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم إرسال الرد بنجاح إلى البريد الإلكتروني للزائر.'
        ], 200);
    }

    /**
     * 5. تحديث حالة الرسالة (مثل أرشفة الرسالة)
     */
    public function update(UpdateContactMessageRequest $request, $id)
    {
        $message = ContactMessage::findOrFail($id);
        $validatedData = $request->validated();

        $validatedData['handled_by'] = auth()->id() ?? 1;

        $message->update($validatedData);

        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث حالة الرسالة بنجاح',
            'data'    => $message
        ], 200);
    }

    /**
     * 6. حذف رسالة
     */
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