<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use App\Http\Resources\NewsletterResource; // 1. تضمين الـ Resource
use App\Http\Requests\StoreNewsletterSubscriberRequest;
use App\Http\Requests\UpdateNewsletterSubscriberRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate; // 2. تضمين الـ Gate للصلاحيات

class NewsletterSubscriberController extends Controller
{
    /**
     * 1. عرض جميع المشتركين (خاص بالأدمن)
     */
    public function index()
    {
        Gate::authorize('manage-settings'); // حماية الدالة

        $subscribers = NewsletterSubscriber::latest()->paginate(15);

        // إرجاع البيانات منسقة عبر Resource بدلاً من الاستجابة الخام
        return NewsletterResource::collection($subscribers);
    }

    /**
     * 2. عرض تفاصيل مشترك واحد (خاص بالأدمن)
     */
    public function show($id)
    {
        Gate::authorize('manage-settings'); // حماية الدالة

        $subscriber = NewsletterSubscriber::findOrFail($id);

        return new NewsletterResource($subscriber);
    }

    /**
     * 3. إضافة مشترك جديد (متاح للجميع من الواجهة العامة)
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
            'message' => __('تم الاشتراك في النشرة البريدية بنجاح.'),
            'data'    => new NewsletterResource($subscriber)
        ], 201);
    }

    /**
     * 4. تحديث بيانات المشترك (خاص بالأدمن)
     */
    public function update(UpdateNewsletterSubscriberRequest $request, $id)
    {
        Gate::authorize('manage-settings'); // حماية الدالة

        $subscriber = NewsletterSubscriber::findOrFail($id);
        $validatedData = $request->validated();

        if (array_key_exists('is_active', $validatedData) && !$validatedData['is_active']) {
            $validatedData['unsubscribed_at'] = now();
        } elseif (array_key_exists('is_active', $validatedData) && $validatedData['is_active']) {
            $validatedData['unsubscribed_at'] = null;
        }

        $subscriber->update($validatedData);

        return response()->json([
            'status'  => true,
            'message' => __('تم تحديث بيانات المشترك بنجاح.'),
            'data'    => new NewsletterResource($subscriber)
        ], 200);
    }

    /**
     * 5. إلغاء الاشتراك السريع (متاح للمستخدم)
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
            'message' => __('تم إلغاء الاشتراك بنجاح.')
        ], 200);
    }

    /**
     * 6. حذف مشترك نهائياً (خاص بالأدمن)
     */
    public function destroy($id)
    {
        Gate::authorize('manage-settings'); // حماية الدالة

        $subscriber = NewsletterSubscriber::findOrFail($id);
        $subscriber->delete();

        return response()->json([
            'status'  => true,
            'message' => __('تم حذف المشترك بنجاح.')
        ], 200);
    }
}