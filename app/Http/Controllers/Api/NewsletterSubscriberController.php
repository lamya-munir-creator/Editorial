<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use App\Models\ActivityLog;
use App\Http\Resources\NewsletterResource;
use App\Http\Requests\StoreNewsletterSubscriberRequest;
use App\Http\Requests\UpdateNewsletterSubscriberRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;

class NewsletterSubscriberController extends Controller
{
    private function getActionPrefix(): string
    {
        $user = auth()->user();
        $firstName = $user ? $user->first_name : '';
        $isFemale = $firstName && (mb_substr($firstName, -1) === 'ة' || mb_substr($firstName, -1) === 'ه');
        return $isFemale ? 'قامت بـ' : 'قام بـ';
    }

    /**
     * 1. عرض جميع المشتركين (خاص بالأدمن)
     */
    public function index()
    {
        Gate::authorize('manage-settings');

        $subscribers = NewsletterSubscriber::latest()->paginate(15);

        return NewsletterResource::collection($subscribers);
    }

    /**
     * 2. عرض تفاصيل مشترك واحد (خاص بالأدمن)
     */
    public function show($id)
    {
        Gate::authorize('manage-settings');

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

        // تسجيل النشاط (إذا كان المسجل مستخدماً مسجلاً دخوله أو زاراً)
        ActivityLog::create([
            'user_id' => auth()->id() ?? null,
            'action_type' => 'newsletter_subscribe',
            'action_label' => 'اشتراك جديد في النشرة البريدية',
            'target_name' => $subscriber->email,
            'target_url' => '/subscribers',
        ]);

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
        Gate::authorize('manage-settings');

        $subscriber = NewsletterSubscriber::findOrFail($id);
        $validatedData = $request->validated();

        if (array_key_exists('is_active', $validatedData) && !$validatedData['is_active']) {
            $validatedData['unsubscribed_at'] = now();
        } elseif (array_key_exists('is_active', $validatedData) && $validatedData['is_active']) {
            $validatedData['unsubscribed_at'] = null;
        }

        $subscriber->update($validatedData);

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'update_subscriber',
            'action_label' => $this->getActionPrefix() . 'تعديل بيانات مشترك النشرة',
            'target_name' => $subscriber->email,
            'target_url' => '/subscribers',
        ]);

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

        ActivityLog::create([
            'user_id' => auth()->id() ?? null,
            'action_type' => 'unsubscribe',
            'action_label' => 'إلغاء الاشتراك من النشرة البريدية',
            'target_name' => $subscriber->email,
            'target_url' => null,
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
        Gate::authorize('manage-settings');

        $subscriber = NewsletterSubscriber::findOrFail($id);
        $email = $subscriber->email;
        $subscriber->delete();

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'delete_subscriber',
            'action_label' => $this->getActionPrefix() . 'حذف مشترك من النشرة',
            'target_name' => $email,
            'target_url' => null,
        ]);

        return response()->json([
            'status'  => true,
            'message' => __('تم حذف المشترك بنجاح.')
        ], 200);
    }
}