<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use App\Http\Resources\TagResource;

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

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:tags,name',
        ]);

        $tag = Tag::create([
            'name' => $request->name,
            'slug' => \Str::slug($request->name),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم إنشاء الوسم بنجاح',
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

    public function update(Request $request, Tag $tag)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:tags,name,' . $tag->id,
        ]);

        $tag->update([
            'name' => $request->name,
            'slug' => \Str::slug($request->name),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث الوسم بنجاح',
            'data'    => new TagResource($tag)
        ], 200);
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف الوسم بنجاح'
        ], 200);
    }
}