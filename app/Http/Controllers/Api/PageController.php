<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PageController extends Controller
{
    /**
     * عرض جميع الصفحات.
     */
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

    /**
     * إنشاء صفحة جديدة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'featured_image_id' => 'nullable|exists:media,id',
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:pages,slug',
            'content' => 'required|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'template' => 'nullable|string|max:100',
            'is_homepage' => 'sometimes|boolean',
            'status' => 'required|in:draft,published,archived',
            'published_at' => 'nullable|date',
        ]);

        $validated['uuid'] = Str::uuid();

        $validated['slug'] = isset($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['title']);

        $validated['created_by'] = auth()->id() ?? 1;
        $validated['updated_by'] = null;

        if (($validated['is_homepage'] ?? false) === true) {
            Page::where('is_homepage', true)
                ->update(['is_homepage' => false]);
        }

        if (
            $validated['status'] === 'published'
            && empty($validated['published_at'])
        ) {
            $validated['published_at'] = now();
        }

        if ($validated['status'] !== 'published') {
            $validated['published_at'] = null;
        }

        $page = Page::create($validated);

        return response()->json([
            'status' => true,
            'message' => 'تم إنشاء الصفحة بنجاح',
            'data' => $page->load([
                'featuredImage',
                'creator',
                'updater',
            ]),
        ], 201);
    }

    /**
     * عرض صفحة واحدة.
     */
    public function show(Page $page)
    {
        return response()->json([
            'status' => true,
            'data' => $page->load([
                'featuredImage',
                'creator',
                'updater',
            ]),
        ], 200);
    }

    /**
     * تحديث الصفحة.
     */
    public function update(Request $request, Page $page)
    {
        $validated = $request->validate([
            'featured_image_id' => 'sometimes|nullable|exists:media,id',
            'title' => 'sometimes|required|string|max:255',
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('pages', 'slug')->ignore($page->id),
            ],
            'content' => 'sometimes|required|string',
            'meta_title' => 'sometimes|nullable|string|max:255',
            'meta_description' => 'sometimes|nullable|string|max:500',
            'template' => 'sometimes|required|string|max:100',
            'is_homepage' => 'sometimes|boolean',
            'status' => 'sometimes|required|in:draft,published,archived',
            'published_at' => 'sometimes|nullable|date',
        ]);

        if (isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['slug']);
        } elseif (
            isset($validated['title'])
            && $validated['title'] !== $page->title
        ) {
            $validated['slug'] = $this->generateUniqueSlug(
                $validated['title'],
                $page->id
            );
        }

        $validated['updated_by'] = auth()->id() ?? 1;

        if (
            array_key_exists('is_homepage', $validated)
            && $validated['is_homepage'] === true
        ) {
            Page::where('is_homepage', true)
                ->where('id', '!=', $page->id)
                ->update(['is_homepage' => false]);
        }

        if (
            isset($validated['status'])
            && $validated['status'] === 'published'
            && !$page->published_at
            && empty($validated['published_at'])
        ) {
            $validated['published_at'] = now();
        }

        if (
            isset($validated['status'])
            && $validated['status'] !== 'published'
        ) {
            $validated['published_at'] = null;
        }

        $page->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث الصفحة بنجاح',
            'data' => $page->fresh()->load([
                'featuredImage',
                'creator',
                'updater',
            ]),
        ], 200);
    }

    /**
     * حذف الصفحة.
     */
    public function destroy(Page $page)
    {
        $page->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم حذف الصفحة بنجاح',
        ], 200);
    }

    /**
     * عرض الصفحة الرئيسية.
     */
    public function homepage()
    {
        $page = Page::with([
            'featuredImage',
            'creator',
            'updater',
        ])
            ->where('is_homepage', true)
            ->where('status', 'published')
            ->first();

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

    /**
     * عرض الصفحة باستخدام slug.
     */
    public function showBySlug(string $slug)
    {
        $page = Page::with([
            'featuredImage',
            'creator',
            'updater',
        ])
            ->where('slug', $slug)
            ->first();

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

    /**
     * إنشاء slug غير مكرر.
     */
    private function generateUniqueSlug(
        string $title,
        ?int $ignoreId = null
    ): string {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        while (
            Page::where('slug', $slug)
                ->when(
                    $ignoreId,
                    fn ($query) => $query->where('id', '!=', $ignoreId)
                )
                ->exists()
        ) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}