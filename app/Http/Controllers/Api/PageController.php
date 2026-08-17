<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Media;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use App\Http\Requests\StorePageRequest;
use App\Http\Requests\UpdatePageRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PageController extends Controller
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
        $pages = Page::with([
            'featuredImage',
            'creator',
            'updater',
        ])
            ->latest()
            ->paginate(10);

        return response()->json([
            'status' => true,
            'data' => $pages->items(),
            'meta' => [
                'current_page' => $pages->currentPage(),
                'last_page' => $pages->lastPage(),
                'per_page' => $pages->perPage(),
                'total' => $pages->total(),
            ],
        ], 200);
    }

    public function store(StorePageRequest $request)
    {
        $validated = $request->validated();
        $validated['uuid'] = Str::uuid();
        $validated['slug'] = isset($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['title']);

        $userId = auth()->id() ?? 1;
        $validated['created_by'] = $userId;
        $validated['updated_by'] = null;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = $file->store('pages', 'public');

            $media = Media::create([
                'uuid'          => (string) Str::uuid(),
                'uploaded_by'   => $userId,
                'file_name'     => basename($path),
                'original_name' => $file->getClientOriginalName(),
                'disk'          => 'public',
                'path'          => $path,
                'webp_path'     => $path,
                'mime_type'     => $file->getClientMimeType(),
                'extension'     => $file->getClientOriginalExtension(),
                'file_size'     => $file->getSize(),
                'type'          => 'image',
                'visibility'    => 'public',
                'created_by'    => $userId,
            ]);

            $validated['featured_image_id'] = $media->id;
        }

        if (($validated['is_homepage'] ?? false) === true) {
            Page::where('is_homepage', true)->update(['is_homepage' => false]);
        }

        if ($validated['status'] === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        if ($validated['status'] !== 'published') {
            $validated['published_at'] = null;
        }

        $page = Page::create($validated);

        ActivityLog::create([
            'user_id' => $userId,
            'action_type' => 'add_page',
            'action_label' => $this->getActionPrefix() . 'إنشاء صفحة جديدة',
            'target_name' => $page->title,
            'target_url' => '/pages',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم إنشاء الصفحة بنجاح',
            'data' => $page->load(['featuredImage', 'creator', 'updater']),
        ], 201);
    }

    public function show(Page $page)
    {
        return response()->json([
            'status' => true,
            'data' => $page->load(['featuredImage', 'creator', 'updater']),
        ], 200);
    }

    public function update(UpdatePageRequest $request, Page $page)
    {
        $validated = $request->validated();

        if (isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['slug']);
        } elseif (isset($validated['title']) && $validated['title'] !== $page->title) {
            $validated['slug'] = $this->generateUniqueSlug($validated['title'], $page->id);
        }

        $userId = auth()->id() ?? 1;
        $validated['updated_by'] = $userId;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = $file->store('pages', 'public');

            $media = Media::create([
                'uuid'          => (string) Str::uuid(),
                'uploaded_by'   => $userId,
                'file_name'     => basename($path),
                'original_name' => $file->getClientOriginalName(),
                'disk'          => 'public',
                'path'          => $path,
                'webp_path'     => $path,
                'mime_type'     => $file->getClientMimeType(),
                'extension'     => $file->getClientOriginalExtension(),
                'file_size'     => $file->getSize(),
                'type'          => 'image',
                'visibility'    => 'public',
                'created_by'    => $userId,
            ]);

            $validated['featured_image_id'] = $media->id;
        }

        if (array_key_exists('is_homepage', $validated) && $validated['is_homepage'] === true) {
            Page::where('is_homepage', true)->where('id', '!=', $page->id)->update(['is_homepage' => false]);
        }

        if (isset($validated['status']) && $validated['status'] === 'published' && !$page->published_at && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        if (isset($validated['status']) && $validated['status'] !== 'published') {
            $validated['published_at'] = null;
        }

        $page->update($validated);

        ActivityLog::create([
            'user_id' => $userId,
            'action_type' => 'edit_page',
            'action_label' => $this->getActionPrefix() . 'تعديل الصفحة',
            'target_name' => $page->title,
            'target_url' => '/pages',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث الصفحة بنجاح',
            'data' => $page->fresh()->load(['featuredImage', 'creator', 'updater']),
        ], 200);
    }

    public function destroy(Page $page)
    {
        $title = $page->title;
        $userId = auth()->id() ?? 1;
        $page->delete();

        ActivityLog::create([
            'user_id' => $userId,
            'action_type' => 'delete_page',
            'action_label' => $this->getActionPrefix() . 'حذف الصفحة',
            'target_name' => $title,
            'target_url' => null,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم حذف الصفحة بنجاح',
        ], 200);
    }

    public function homepage()
    {
        $page = Page::with(['featuredImage', 'creator', 'updater'])->where('is_homepage', true)->where('status', 'published')->first();

        if (!$page) {
            return response()->json([
                'status' => false,
                'message' => 'لا توجد صفحة رئيسية منشورة',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $page,
        ], 200);
    }

    public function showBySlug(string $slug)
    {
        $page = Page::with(['featuredImage', 'creator', 'updater'])->where('slug', $slug)->first();

        if (!$page) {
            return response()->json([
                'status' => false,
                'message' => 'الصفحة غير موجودة',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $page,
        ], 200);
    }

    private function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        while (Page::where('slug', $slug)->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}