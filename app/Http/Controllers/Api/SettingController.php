<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Http\Requests\StoreSettingRequest;
use App\Http\Requests\UpdateSettingRequest;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    /**
     * 1. عرض جميع الإعدادات (أو تجميعها حسب المجموعات)
     */
    public function index()
    {
        $settings = Setting::latest()->get();

        return response()->json([
            'status' => true,
            'data'   => $settings
        ], 200);
    }

    /**
     * 2. عرض إعداد واحد
     */
    public function show($id)
    {
        $setting = Setting::findOrFail($id);

        return response()->json([
            'status' => true,
            'data'   => $setting
        ], 200);
    }

    /**
     * 3. إضافة إعداد جديد (مع دعم رفع الملفات/الصور)
     */
    public function store(StoreSettingRequest $request)
    {
        $validatedData = $request->validated();

        // التعامل مع رفع الملف/الصورة إن وجد
        if ($request->hasFile('setting_value')) {
            $path = $request->file('setting_value')->store('settings', 'public');
            $validatedData['setting_value'] = $path;
        }

        $validatedData['is_public']  = $validatedData['is_public'] ?? false;
        $validatedData['updated_by'] = auth()->id() ?? 1;

        $setting = Setting::create($validatedData);

        return response()->json([
            'status'  => true,
            'message' => 'تم إنشاء الإعداد بنجاح.',
            'data'    => $setting
        ], 201);
    }

    /**
     * 4. تعديل إعداد (مع تبديل الملف القديم بالجديد إن وُجد)
     */
    public function update(UpdateSettingRequest $request, $id)
    {
        $setting = Setting::findOrFail($id);
        $validatedData = $request->validated();

        // التعامل مع رفع الملف الجديد وتفريغ القديم
        if ($request->hasFile('setting_value')) {
            if ($setting->setting_value && Storage::disk('public')->exists($setting->setting_value)) {
                Storage::disk('public')->delete($setting->setting_value);
            }

            $path = $request->file('setting_value')->store('settings', 'public');
            $validatedData['setting_value'] = $path;
        }

        $validatedData['updated_by'] = auth()->id() ?? 1;

        $setting->update($validatedData);

        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث الإعداد بنجاح.',
            'data'    => $setting
        ], 200);
    }

    /**
     * 5. حذف إعداد مع ملفه المرفق
     */
    public function destroy($id)
    {
        $setting = Setting::findOrFail($id);

        // حذف الملف المرفق إن كان مخزناً
        if ($setting->setting_value && Storage::disk('public')->exists($setting->setting_value)) {
            Storage::disk('public')->delete($setting->setting_value);
        }

        $setting->delete();

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف الإعداد بنجاح.'
        ], 200);
    }
}