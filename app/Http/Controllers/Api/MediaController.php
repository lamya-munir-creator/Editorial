<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Http\Requests\StoreMediaRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class MediaController extends Controller
{
    /**
     * عرض قائمة الوسائط المرفوعة
     */
    public function index()
    {
        $media = Media::latest()->paginate(20);

        // إضافة رابط الملف المباشر url لكل عنصر
        $media->getCollection()->transform(function ($item) {
            $item->file_url = asset('storage/' . $item->path);
            $item->webp_url = $item->webp_path ? asset('storage/' . $item->webp_path) : $item->file_url;
            return $item;
        });

        return response()->json([
            'status' => true,
            'data'   => $media
        ], 200);
    }

    /**
     * رفع ملف جديد وحفظ كافه بياناته في جدول media مع تحسين WebP
     */
    public function store(StoreMediaRequest $request)
    {
        $file = $request->file('file');

        // 1. تحديد نوع الملف (image, video, document, audio)
        $mime = $file->getClientMimeType();
        $type = 'document';
        if (str_contains($mime, 'image')) {
            $type = 'image';
        } elseif (str_contains($mime, 'video')) {
            $type = 'video';
        } elseif (str_contains($mime, 'audio')) {
            $type = 'audio';
        }

        // 2. رفع الملف إلى مجلد storage/app/public/media
        $path = $file->store('media', 'public');
        $webpPath = $path; // افتراضي

        // 3. قراءة أبعاد الصورة وتحسين صيغة WebP للصور
        $width = null;
        $height = null;
        if ($type === 'image') {
            $imageSize = @getimagesize($file->getRealPath());
            if ($imageSize) {
                $width  = $imageSize[0];
                $height = $imageSize[1];
            }

            // إذا أمكن إنشاء نسخة WebP باستخدام GD Library
            if (function_exists('imagecreatefromstring') && function_exists('imagewebp')) {
                try {
                    $contents = file_get_contents($file->getRealPath());
                    $img = @imagecreatefromstring($contents);
                    if ($img !== false) {
                        $webpFileName = 'media/' . pathinfo($path, PATHINFO_FILENAME) . '.webp';
                        $fullPath = storage_path('app/public/' . $webpFileName);
                        @imagewebp($img, $fullPath, 80);
                        @imagedestroy($img);
                        if (file_exists($fullPath)) {
                            $webpPath = $webpFileName;
                        }
                    }
                } catch (\Throwable $e) {
                    // في حال التعذر استخدام المسار الأصلي
                    $webpPath = $path;
                }
            }
        }

        // 4. إنشاء السجل في جدول media
        $media = Media::create([
            'uuid'          => (string) Str::uuid(),
            'uploaded_by'   => Auth::id() ?? 1,
            'file_name'     => basename($path),
            'original_name' => $file->getClientOriginalName(),
            'disk'          => 'public',
            'path'          => $path,
            'webp_path'     => $webpPath,
            'mime_type'     => $mime,
            'extension'     => $file->getClientOriginalExtension(),
            'file_size'     => $file->getSize(),
            'width'         => $width,
            'height'        => $height,
            'alt_text'      => $request->input('alt_text'),
            'caption'       => $request->input('caption'),
            'type'          => $type,
            'visibility'    => $request->input('visibility', 'public'),
            'created_by'    => Auth::id() ?? 1,
        ]);

        $media->file_url = asset('storage/' . $media->path);
        $media->webp_url = asset('storage/' . $media->webp_path);

        return response()->json([
            'status'  => true,
            'message' => 'تم رفع الملف بنجاح وتحسين الصيغة.',
            'data'    => $media
        ], 201);
    }

    /**
     * حذف ملف ووسيط
     */
    public function destroy($id)
    {
        $media = Media::findOrFail($id);

        if ($media->path && Storage::disk('public')->exists($media->path)) {
            Storage::disk('public')->delete($media->path);
        }

        if ($media->webp_path && $media->webp_path !== $media->path && Storage::disk('public')->exists($media->webp_path)) {
            Storage::disk('public')->delete($media->webp_path);
        }

        $media->delete();

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف الملف بنجاح.'
        ], 200);
    }
}