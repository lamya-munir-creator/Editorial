<?php
namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
 // لاستخدام خدمة البريد الإلكتروني

class ContactMessageController extends Controller
{
    // جلب رسائل اتصل بنا (في لوحة التحكم)
    public function index()
    {
        $messages = ContactMessage::latest()->paginate(15);
        
        return view('admin.contacts.index', compact('messages'));
    }

    // عرض رسالة محددة بالتفصيل
    public function show($id)
    {
        $message = ContactMessage::findOrFail($id);
        
        // تغيير حالة الرسالة إلى "مقروءة" عند فتحها لأول مرة
        if (!$message->is_read) {
            $message->update(['is_read' => true]);
        }

        return view('admin.contacts.show', compact('message'));
    }

    // استقبال رسالة جديدة من قِبل زائر الموقع
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        ContactMessage::create($validatedData);

        return back()->with('success', 'تم إرسال رسالتك بنجاح، شكراً لتواصلك معنا.');
    }

    // [إضافة جديدة] الرد على رسالة الزائر وإرسالها عبر البريد
    public function reply(Request $request, $id)
    {
        $message = ContactMessage::findOrFail($id);

        $validatedData = $request->validate([
            'reply_message' => 'required|string|max:3000',
        ]);

        // إرسال البريد الإلكتروني للزائر
        Mail::raw($validatedData['reply_message'], function ($mail) use ($message) {
            $mail->to($message->email)
                 ->subject('رد على استفسارك: ' . ($message->subject ?? 'إدارة الموقع'));
        });

        // تحديث حالة الرسالة في قاعدة البيانات لتصبح "تم الرد" (تأكد من وجود عمود is_replied أو status في جدولك)
        $message->update([
            'is_replied' => true,
        ]);

        return back()->with('success', 'تم إرسال الرد بنجاح إلى البريد الإلكتروني للزائر.');
    }

    // حذف رسالة
    public function destroy($id)
    {
        $message = ContactMessage::findOrFail($id);
        $message->delete();

        return redirect()->route('contacts.index')->with('success', 'تم حذف الرسالة بنجاح');
    }
}