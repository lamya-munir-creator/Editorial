<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    /**
     * عرض جميع الإعدادات.
     */
    public function index()
    {
        $settings = Setting::with('updater')
            ->latest()
            ->paginate(10);

        return response()->json([
            'status' => true,
            'data' => $settings,
        ], 200);
    }

    /**
     * إنشاء إعداد جديد.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'setting_key' => [
                'required',
                'string',
                'max:150',
                'unique:settings,setting_key',
            ],
            'setting_value' => 'nullable|string',
            'group_name' => 'required|string|max:100',
            'value_type' => 'nullable|string|max:50',
            'is_public' => 'sometimes|boolean',
        ]);

        $validated['value_type'] =
            $validated['value_type'] ?? 'string';

        $validated['is_public'] =
            $validated['is_public'] ?? false;

        $validated['updated_by'] = auth()->id() ?? 1;

        $setting = Setting::create($validated);

        return response()->json([
            'status' => true,
            'message' => 'تم إنشاء الإعداد بنجاح',
            'data' => $setting,
        ], 201);
    }

    /**
     * عرض إعداد واحد.
     */
    public function show(Setting $setting)
    {
        return response()->json([
            'status' => true,
            'data' => $setting->load('updater'),
        ], 200);
    }

    /**
     * تحديث إعداد.
     */
    public function update(Request $request, Setting $setting)
    {
        $validated = $request->validate([
            'setting_key' => [
                'sometimes',
                'required',
                'string',
                'max:150',
                Rule::unique('settings', 'setting_key')
                    ->ignore($setting->id),
            ],
            'setting_value' => 'sometimes|nullable|string',
            'group_name' => 'sometimes|required|string|max:100',
            'value_type' => 'sometimes|required|string|max:50',
            'is_public' => 'sometimes|boolean',
        ]);

        $validated['updated_by'] = auth()->id() ?? 1;

        $setting->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث الإعداد بنجاح',
            'data' => $setting->fresh()->load('updater'),
        ], 200);
    }

    /**
     * حذف إعداد.
     */
    public function destroy(Setting $setting)
    {
        $setting->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم حذف الإعداد بنجاح',
        ], 200);
    }
}