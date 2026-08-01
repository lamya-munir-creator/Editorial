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
            return $item;
        });

        return response()->json([
            'status' => true,
            'data'   => $media
        ], 200);
    }

    /**
     * رفع ملف جديد وحفظ كافه بياناته في جدول media
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

        // 3. قراءة أبعاد الصورة إن كان الملف صورة
        $width = null;
        $height = null;
        if ($type === 'image') {
            $imageSize = @getimagesize($file->getRealPath());
            if ($imageSize) {
                $width  = $imageSize[0];
                $height = $imageSize[1];
            }
        }

        // 4. إنشاء السجل في جدول media بجميع الحقول المطابقة لجدولك
        $media = Media::create([
            'uuid'          => (string) Str::uuid(),
            'uploaded_by'   => Auth::id() ?? 1,
            'file_name'     => basename($path),
            'original_name' => $file->getClientOriginalName(),
            'disk'          => 'public',
            'path'          => $path,
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

        return response()->json([
            'status'  => true,
            'message' => 'تم رفع الملف بنجاح.',
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

        $media->delete();

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف الملف بنجاح.'
        ], 200);
    }
}