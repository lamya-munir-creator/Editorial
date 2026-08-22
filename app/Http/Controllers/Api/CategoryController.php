<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Article;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ArticleResource;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    private function getActionPrefix(): string
    {
        $user = auth()->user();
        $firstName = $user ? $user->first_name : '';
        $isFemale = $firstName && (mb_substr($firstName, -1) === 'Ø©' || mb_substr($firstName, -1) === 'Ù‡');
        return $isFemale ? 'Ù‚Ø§Ù…Øª Ø¨Ù€' : 'Ù‚Ø§Ù… Ø¨Ù€';
    }

        public function index(Request $request)
    {
        $query = Category::with('image')->withCount('articles');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $categories = $query
            ->orderBy('sort_order', 'asc') 
            ->latest()                     
            ->paginate($request->input('per_page', 10));


        return response()->json([
            'status' => true,
            'data'   => CategoryResource::collection($categories),
            'meta'   => [
                'current_page' => $categories->currentPage(),
                'last_page'    => $categories->lastPage(),
                'per_page'     => $categories->perPage(),
                'total'        => $categories->total(),
            ],
        ], 200);
    }


    public function store(StoreCategoryRequest $request)
    {
        $validatedData = $request->validated();

        $validatedData['slug'] = Str::slug($validatedData['name']);
        $validatedData['created_by'] = auth()->id() ?? 1;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = $file->store('categories', 'public');

            $media = \App\Models\Media::create([
                'uuid'          => (string) Str::uuid(),
                'uploaded_by'   => auth()->id() ?? 1,
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
                'created_by'    => auth()->id() ?? 1,
            ]);

            $validatedData['image_id'] = $media->id;
        }

        $remove_image = $request->input('remove_image');
            if ($remove_image === 'true' || $remove_image === '1' || $remove_image === true || $remove_image === 1) {
                $validatedData['image_id'] = null;
            }

            unset($validatedData['image']);

        $category = Category::create($validatedData);

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'add_category',
            'action_label' => $this->getActionPrefix() . 'Ø¥Ø¶Ø§ÙØ© ØªØµÙ†ÙŠÙ Ø¬Ø¯ÙŠØ¯',
            'target_name' => $category->name,
            'target_url' => '/categories',
        ]);

        return response()->json([
            'status'  => true,
            'message' => __('Category created successfully'),
            'data'    => new CategoryResource($category->load('image'))
        ], 201);
    }

    public function show(Category $category)
    {
        return response()->json([
            'status' => true,
            'data'   => new CategoryResource($category)
        ], 200);
    }

    public function showBySlug(string $slug)
    {
        $category = Category::where('slug', $slug)->first();

        if (!$category) {
            return response()->json([
                'status'  => false,
                'message' => __('Category not found')
            ], 404);
        }

        $articles = Article::published()
            ->where('category_id', $category->id)
            ->with(['tags', 'author', 'featuredImage'])
            ->latest('published_at')
            ->paginate(10);

        return response()->json([
            'status'   => true,
            'category' => new CategoryResource($category),
            'articles' => ArticleResource::collection($articles),
            'meta'     => [
                'current_page' => $articles->currentPage(),
                'last_page'    => $articles->lastPage(),
                'per_page'     => $articles->perPage(),
                'total'        => $articles->total(),
            ]
        ], 200);
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $validatedData = $request->validated();

        if (isset($validatedData['name'])) {
            $validatedData['slug'] = Str::slug($validatedData['name']);
        }

        $validatedData['updated_by'] = auth()->id() ?? 1;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = $file->store('categories', 'public');

            $media = \App\Models\Media::create([
                'uuid'          => (string) Str::uuid(),
                'uploaded_by'   => auth()->id() ?? 1,
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
                'created_by'    => auth()->id() ?? 1,
            ]);

            $validatedData['image_id'] = $media->id;
        }

        $category->fill($validatedData);
        $category->save();

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'edit_category',
            'action_label' => $this->getActionPrefix() . 'ØªØ¹Ø¯ÙŠÙ„ Ø§Ù„ØªØµÙ†ÙŠÙ',
            'target_name' => $category->name,
            'target_url' => '/categories',
        ]);

        return response()->json([
            'status'  => true,
            'message' => __('Category updated successfully'),
            'data'    => new CategoryResource($category->load('image'))
        ], 200);
    }

    public function destroy(Category $category)
    {
        $name = $category->name;
        $category->delete();

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'delete_category',
            'action_label' => $this->getActionPrefix() . 'Ø­Ø°Ù Ø§Ù„ØªØµÙ†ÙŠÙ',
            'target_name' => $name,
            'target_url' => null,
        ]);

        return response()->json([
            'status'  => true,
            'message' => __('Category deleted successfully')
        ], 200);
    }
}
