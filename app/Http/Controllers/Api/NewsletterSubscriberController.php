<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NewsletterSubscriberController extends Controller
{
    /**
     * عرض جميع المشتركين.
     */
    public function index()
    {
        $subscribers = NewsletterSubscriber::latest()
            ->paginate(10);

        return response()->json([
            'status' => true,
            'data' => $subscribers->items(),
            'meta' => [
                'current_page' => $subscribers->currentPage(),
                'last_page' => $subscribers->lastPage(),
                'total' => $subscribers->total(),
            ],
        ], 200);
    }

    /**
     * إضافة مشترك جديد.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255|unique:newsletter_subscribers,email',
            'full_name' => 'nullable|string|max:150',
        ]);

        $subscriber = NewsletterSubscriber::create([
            'uuid' => Str::uuid(),
            'email' => $validated['email'],
            'full_name' => $validated['full_name'] ?? null,
            'is_active' => true,
            'subscribed_at' => now(),
            'unsubscribed_at' => null,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم الاشتراك في النشرة البريدية بنجاح',
            'data' => $subscriber,
        ], 201);
    }

    /**
     * عرض مشترك واحد.
     */
    public function show(NewsletterSubscriber $newsletterSubscriber)
    {
        return response()->json([
            'status' => true,
            'data' => $newsletterSubscriber,
        ], 200);
    }

    /**
     * تحديث بيانات المشترك.
     */
    public function update(
        Request $request,
        NewsletterSubscriber $newsletterSubscriber
    ) {
        $validated = $request->validate([
            'email' => 'sometimes|required|email|max:255|unique:newsletter_subscribers,email,'
                . $newsletterSubscriber->id,
            'full_name' => 'nullable|string|max:150',
            'is_active' => 'sometimes|boolean',
        ]);

        if (
            array_key_exists('is_active', $validated)
            && $validated['is_active'] === false
        ) {
            $validated['unsubscribed_at'] = now();
        }

        if (
            array_key_exists('is_active', $validated)
            && $validated['is_active'] === true
        ) {
            $validated['subscribed_at'] = now();
            $validated['unsubscribed_at'] = null;
        }

        $newsletterSubscriber->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث بيانات المشترك بنجاح',
            'data' => $newsletterSubscriber->fresh(),
        ], 200);
    }

    /**
     * حذف المشترك.
     */
    public function destroy(NewsletterSubscriber $newsletterSubscriber)
    {
        $newsletterSubscriber->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم حذف المشترك بنجاح',
        ], 200);
    }

    /**
     * إلغاء الاشتراك عن طريق البريد.
     */
    public function unsubscribe(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:newsletter_subscribers,email',
        ]);

        $subscriber = NewsletterSubscriber::where(
            'email',
            $validated['email']
        )->firstOrFail();

        $subscriber->update([
            'is_active' => false,
            'unsubscribed_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم إلغاء الاشتراك بنجاح',
        ], 200);
    }
}