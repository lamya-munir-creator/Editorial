<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use App\Http\Resources\TagResource;
use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use Illuminate\Support\Str;

class TagController extends Controller
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

    public function store(StoreTagRequest $request)
    {
        $validated = $request->validated();

        $tag = Tag::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'add_tag',
            'action_label' => $this->getActionPrefix() . 'إضافة وسم جديد',
            'target_name' => $tag->name,
            'target_url' => '/tags',
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

    public function update(UpdateTagRequest $request, Tag $tag)
    {
        $validated = $request->validated();

        $tag->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'edit_tag',
            'action_label' => $this->getActionPrefix() . 'تعديل الوسم',
            'target_name' => $tag->name,
            'target_url' => '/tags',
        ]);

        return response()->json([
            'status'  => true,
            'message' => __('Tag updated successfully'),
            'data'    => new TagResource($tag)
        ], 200);
    }

    public function destroy(Tag $tag)
    {
        $name = $tag->name;
        $tag->delete();

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'delete_tag',
            'action_label' => $this->getActionPrefix() . 'حذف الوسم',
            'target_name' => $name,
            'target_url' => null,
        ]);

        return response()->json([
            'status'  => true,
            'message' => __('Tag deleted successfully')
        ], 200);
    }
}