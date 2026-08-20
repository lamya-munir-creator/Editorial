<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\ActivityLog;
use App\Http\Requests\StoreSettingRequest;
use App\Http\Requests\UpdateSettingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    private function getActionPrefix(): string
    {
        $user = auth()->user();
        $firstName = $user ? $user->first_name : '';
        $isFemale = $firstName && (mb_substr($firstName, -1) === 'ة' || mb_substr($firstName, -1) === 'ه');
        return $isFemale ? 'قامت بـ' : 'قام بـ';
    }

    public function getPublicSettings()
    {
        $settings = Setting::where('is_public', true)->get();

        $data = [];

        foreach ($settings as $setting) {
            $key = $setting->setting_key ?? $setting->key;
            if (!$key) {
                continue;
            }

            $raw = $setting->setting_value;
            $type = strtolower($setting->value_type ?? 'string');

            switch ($type) {
                case 'integer':
                case 'int':
                    $data[$key] = (int) $raw;
                    break;

                case 'boolean':
                case 'bool':
                    if (is_bool($raw)) {
                        $data[$key] = $raw;
                    } else {
                        $lower = strtolower(trim((string) $raw));
                        $data[$key] = in_array($lower, ['1', 'true', 'on', 'yes'], true);
                    }
                    break;

                case 'json':
                case 'array':
                    if (is_array($raw) || is_object($raw)) {
                        $data[$key] = $raw;
                    } else {
                        $decoded = json_decode((string) $raw, true);
                        $data[$key] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $raw;
                    }
                    break;

                case 'string':
                default:
                    $data[$key] = (string) $raw;
                    break;
            }
        }

        return response()->json([
            'status' => true,
            'data'   => (object) $data,
        ], 200);
    }

    public function index()
    {
        $settings = Setting::latest()->get();

        return response()->json([
            'status' => true,
            'data'   => $settings
        ], 200);
    }

    public function show($id)
    {
        $setting = Setting::findOrFail($id);

        return response()->json([
            'status' => true,
            'data'   => $setting
        ], 200);
    }

    public function store(StoreSettingRequest $request)
    {
        $validatedData = $request->validated();

        if ($request->hasFile('setting_value')) {
            $path = $request->file('setting_value')->store('settings', 'public');
            $validatedData['setting_value'] = '/storage/' . $path;
        }

        $validatedData['is_public']  = $validatedData['is_public'] ?? false;
        $validatedData['updated_by'] = auth()->id() ?? 1;

        $setting = Setting::create($validatedData);

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'system_settings',
            'action_label' => $this->getActionPrefix() . 'إنشاء إعداد جديد',
            'target_name' => $setting->setting_key ?? 'إعداد نظام',
            'target_url' => '/settings',
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم إنشاء الإعداد بنجاح.',
            'data'    => $setting
        ], 201);
    }

    /**
     * تحديث إعداد فردي أو تحديث جماعي للإعدادات (شامل رفع الشعار والأيقونة WebP)
     */
    public function update(Request $request, $id = null)
    {
        // إذا تم إرسال طلب تحديث جماعي للإعدادات عبر المفاتيح (مثل site_logo و site_name)
        if (!$id && $request->hasAny(Setting::pluck('setting_key')->toArray())) {
            $data = $request->except('_token', '_method');
            
            foreach ($data as $key => $value) {
                $setting = Setting::where('setting_key', $key)->first();
                if ($setting) {
                    if ($request->hasFile($key)) {
                        // حذف الصورة القديمة إن وجدت
                        if ($setting->setting_value && str_starts_with($setting->setting_value, '/storage/')) {
                            $oldPath = str_replace('/storage/', '', $setting->setting_value);
                            if (Storage::disk('public')->exists($oldPath)) {
                                Storage::disk('public')->delete($oldPath);
                            }
                        }
                        $path = $request->file($key)->store('settings', 'public');
                        $setting->update([
                            'setting_value' => '/storage/' . $path,
                            'updated_by' => auth()->id() ?? 1
                        ]);
                    } elseif ($value !== null && $value !== 'null') {
                        $setting->update([
                            'setting_value' => $value,
                            'updated_by' => auth()->id() ?? 1
                        ]);
                    }
                }
            }

            ActivityLog::create([
                'user_id' => auth()->id() ?? 1,
                'action_type' => 'system_settings',
                'action_label' => $this->getActionPrefix() . 'تعديل إعدادات النظام والهوية',
                'target_name' => 'إعدادات النظام',
                'target_url' => '/settings',
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'تم حفظ الإعدادات والهوية البصرية بنجاح.'
            ], 200);
        }

        // التحديث التقليدي بإرسال الـ ID
        $setting = Setting::findOrFail($id);
        
        if ($request->hasFile('setting_value')) {
            if ($setting->setting_value && str_starts_with($setting->setting_value, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $setting->setting_value);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $path = $request->file('setting_value')->store('settings', 'public');
            $setting->setting_value = '/storage/' . $path;
        } elseif ($request->has('setting_value')) {
            $setting->setting_value = $request->input('setting_value');
        }

        $setting->updated_by = auth()->id() ?? 1;
        $setting->save();

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'system_settings',
            'action_label' => $this->getActionPrefix() . 'تعديل إعداد',
            'target_name' => $setting->setting_key ?? 'إعدادات النظام',
            'target_url' => '/settings',
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث الإعداد بنجاح.',
            'data'    => $setting
        ], 200);
    }

    public function destroy($id)
    {
        $setting = Setting::findOrFail($id);
        $keyName = $setting->setting_key ?? 'إعداد نظام';

        if ($setting->setting_value && str_starts_with($setting->setting_value, '/storage/')) {
            $oldPath = str_replace('/storage/', '', $setting->setting_value);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $setting->delete();

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'system_settings',
            'action_label' => $this->getActionPrefix() . 'حذف إعداد',
            'target_name' => $keyName,
            'target_url' => null,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف الإعداد بنجاح'
        ], 200);
    }
}