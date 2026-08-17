<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\ActivityLog;
use App\Http\Requests\StoreMediaRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class MediaController extends Controller
{
    private function getActionPrefix(): string
    {
        $user = auth()->user();
        $firstName = $user ? $user->first_name : '';
        $isFemale = $firstName && (mb_substr($firstName, -1) === 'ة' || mb_substr($firstName, -1) === 'ه');
        return $isFemale ? 'قامت بـ' : 'قام بـ';
    }

    public function index()
    {
        $media = Media::latest()->paginate(20);

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

    public function store(StoreMediaRequest $request)
    {
        $file = $request->file('file');

        $mime = $file->getClientMimeType();
        $type = 'document';
        if (str_contains($mime, 'image')) {
            $type = 'image';
        } elseif (str_contains($mime, 'video')) {
            $type = 'video';
        } elseif (str_contains($mime, 'audio')) {
            $type = 'audio';
        }

        $path = $file->store('media', 'public');
        $webpPath = $path;

        $width = null;
        $height = null;
        if ($type === 'image') {
            $imageSize = @getimagesize($file->getRealPath());
            if ($imageSize) {
                $width  = $imageSize[0];
                $height = $imageSize[1];
            }

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
                    $webpPath = $path;
                }
            }
        }

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

        ActivityLog::create([
            'user_id' => Auth::id() ?? 1,
            'action_type' => 'upload_media',
            'action_label' => $this->getActionPrefix() . 'رفع ملف جديد',
            'target_name' => $media->original_name,
            'target_url' => '/media',
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم رفع الملف بنجاح وتحسين الصيغة.',
            'data'    => $media
        ], 201);
    }

    public function destroy($id)
    {
        $media = Media::findOrFail($id);
        $fileName = $media->original_name;

        if ($media->path && Storage::disk('public')->exists($media->path)) {
            Storage::disk('public')->delete($media->path);
        }

        if ($media->webp_path && $media->webp_path !== $media->path && Storage::disk('public')->exists($media->webp_path)) {
            Storage::disk('public')->delete($media->webp_path);
        }

        $media->delete();

        ActivityLog::create([
            'user_id' => Auth::id() ?? 1,
            'action_type' => 'delete_media',
            'action_label' => $this->getActionPrefix() . 'حذف ملف',
            'target_name' => $fileName,
            'target_url' => null,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف الملف بنجاح.'
        ], 200);
    }
}