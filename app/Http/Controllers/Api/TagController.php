<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use App\Http\Resources\TagResource;
use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function index()
    {
        $tags = Tag::latest()->paginate(15);

        return response()->json([
            'status' => true,
            'data'   => TagResource::collection($tags),
            'meta'   => [
                'current_page' => $tags->currentPage(),
                'last_page'    => $tags->lastPage(),
                'total'        => $tags->total(),
            ]
        ], 200);
    }

    /**
     * إنشاء وسم جديد مع التحقق عبر StoreTagRequest (Validation Only).
     */
    public function store(StoreTagRequest $request)
    {
        // استقبال البيانات بعد التحقق عبر FormRequest
        $validated = $request->validated();

        $tag = Tag::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        return response()->json([
            'status'  => true,
            'message' => __('Tag created successfully'),
            'data'    => new TagResource($tag)
        ], 201);
    }

    public function show(Tag $tag)
    {
        return response()->json([
            'status' => true,
            'data'   => new TagResource($tag)
        ], 200);
    }

    /**
     * تحديث وسم موجود مع التحقق عبر UpdateTagRequest (Validation Only).
     */
    public function update(UpdateTagRequest $request, Tag $tag)
    {
        // استقبال البيانات بعد التحقق عبر FormRequest
        $validated = $request->validated();

        $tag->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        return response()->json([
            'status'  => true,
            'message' => __('Tag updated successfully'),
            'data'    => new TagResource($tag)
        ], 200);
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();

        return response()->json([
            'status'  => true,
            'message' => __('Tag deleted successfully')
        ], 200);
    }
}