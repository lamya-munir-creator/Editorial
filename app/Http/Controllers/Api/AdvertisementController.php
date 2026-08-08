<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Models\Media; // في حال كان لديكِ موديل للميديا
use App\Http\Requests\StoreAdvertisementRequest;
use App\Http\Requests\UpdateAdvertisementRequest;
use App\Http\Resources\AdvertisementResource;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AdvertisementController extends Controller
{
   /**
     * عرض جميع الإعلانات
     */
    public function index()
    {
        // يفضل استخدام paginate بدلاً من get() إذا كان عدد الإعلانات كبيراً
        $advertisements = Advertisement::with('image')->latest()->paginate(10);

        // استخدام الـ Resource
        return AdvertisementResource::collection($advertisements);
    }

    /**
     * عرض إعلان محدد
     */
    public function show($id)
    {
        $ad = Advertisement::with('image')->findOrFail($id);

        return new AdvertisementResource($ad);
    }

    /**
     * إنشاء إعلان جديد
     */
    public function store(StoreAdvertisementRequest $request)
    {
        Gate::authorize('manage-ads');

        $data = $request->validated();

        // 1. توليد الـ UUID إجبارياً للجدول
        $data['uuid'] = (string) Str::uuid();

        // 2. تعيين منشئ الإعلان
        $data['created_by'] = Auth::id() ?? 1;

        // 3. معالجة رفع الصورة إذا تم إرسال ملف صورة
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('advertisements', 'public');

            // إذا يوجد جدول ميديا مستقل، نقوم بإنشاء سجل فيه أوالحفظ المباشر
            if (class_exists(Media::class)) {
                $media = Media::create([
                    'file_path' => $path,
                    'file_name' => $request->file('image')->getClientOriginalName(),
                ]);
                $data['image_id'] = $media->id;
            }
        }

        $ad = Advertisement::create($data);

        return response()->json([
            'status'  => true,
            'message' => __('Advertisement created successfully'),
            'data'    => $ad->load('image')
        ], 201);
    }

    /**
     * تحديث إعلان
     */
    public function update(UpdateAdvertisementRequest $request, $id)
    {
        Gate::authorize('manage-ads');

        $ad = Advertisement::findOrFail($id);
        $data = $request->validated();

        $data['updated_by'] = Auth::id() ?? 1;

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('advertisements', 'public');

            if (class_exists(Media::class)) {
                $media = Media::create([
                    'file_path' => $path,
                    'file_name' => $request->file('image')->getClientOriginalName(),
                ]);
                $data['image_id'] = $media->id;
            }
        }

        $ad->update($data);

        return response()->json([
            'status'  => true,
            'message' => __('Advertisement updated successfully'),
            'data'    => $ad->load('image')
        ], 200);
    }

    /**
     * حذف إعلان
     */
    public function destroy($id)
    {
        Gate::authorize('manage-ads');

        $ad = Advertisement::findOrFail($id);
        $ad->delete();

        return response()->json([
            'status'  => true,
            'message' => __('Advertisement deleted successfully')
        ], 200);
    }
}