<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use App\Http\Requests\StoreNewsletterSubscriberRequest;
use App\Http\Requests\UpdateNewsletterSubscriberRequest;
use Illuminate\Support\Str;

class NewsletterSubscriberController extends Controller
{
    /**
     * 1. عرض جميع المشتركين
     */
    public function index()
    {
        $subscribers = NewsletterSubscriber::latest()->paginate(15);

        return response()->json([
            'status' => true,
            'data'   => $subscribers
        ], 200);
    }

    /**
     * 2. عرض تفاصيل مشترك واحد
     */
    public function show($id)
    {
        $subscriber = NewsletterSubscriber::findOrFail($id);

        return response()->json([
            'status' => true,
            'data'   => $subscriber
        ], 200);
    }

    /**
     * 3. إضافة مشترك جديد (من الواجهة)
     */
    public function store(StoreNewsletterSubscriberRequest $request)
    {
        $validatedData = $request->validated();

        $validatedData['uuid']          = (string) Str::uuid();
        $validatedData['subscribed_at'] = now();
        $validatedData['is_active']     = $validatedData['is_active'] ?? true;

        $subscriber = NewsletterSubscriber::create($validatedData);

        return response()->json([
            'status'  => true,
            'message' => 'تم الاشتراك في النشرة البريدية بنجاح.',
            'data'    => $subscriber
        ], 201);
    }

    /**
     * 4. تعديل بيانات مشترك (من لوحة التحكم)
     */
    public function update(UpdateNewsletterSubscriberRequest $request, $id)
    {
        $subscriber = NewsletterSubscriber::findOrFail($id);
        $validatedData = $request->validated();

        // في حال تم تعطيل الحساب نحدث تاريخ إلغاء الاشتراك تلقائياً
        if (array_key_exists('is_active', $validatedData) && !$validatedData['is_active']) {
            $validatedData['unsubscribed_at'] = now();
        } elseif (array_key_exists('is_active', $validatedData) && $validatedData['is_active']) {
            $validatedData['unsubscribed_at'] = null;
        }

        $subscriber->update($validatedData);

        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث بيانات المشترك بنجاح.',
            'data'    => $subscriber
        ], 200);
    }

    /**
     * 5. إلغاء الاشتراك السريع
     */
    public function unsubscribe($id)
    {
        $subscriber = NewsletterSubscriber::findOrFail($id);

        $subscriber->update([
            'is_active'       => false,
            'unsubscribed_at' => now(),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم إلغاء الاشتراك بنجاح.'
        ], 200);
    }

    /**
     * 6. حذف مشترك نهائياً
     */
    public function destroy($id)
    {
        $subscriber = NewsletterSubscriber::findOrFail($id);
        $subscriber->delete();

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف المشترك بنجاح'
        ], 200);
    }
}