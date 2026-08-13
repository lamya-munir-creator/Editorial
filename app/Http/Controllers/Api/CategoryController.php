<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Article;
use Illuminate\Http\Request;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ArticleResource;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    // عرض كل التصنيفات باستخدام CategoryResource (GET /api/categories)
 public function index(Request $request)
{
    $query = Category::with('image');

    // الفلترة حسب الحالة
    if ($request->filled('status')) {
        $query->where('status', $request->input('status'));
    }

    $categories = $query
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

    // إضافة تصنيف جديد (POST /api/categories)
    public function store(StoreCategoryRequest $request)
    {
        $validatedData = $request->validated();

        $validatedData['slug'] = Str::slug($validatedData['name']);
        $validatedData['created_by'] = auth()->id() ?? 1;

        $category = Category::create($validatedData);

        return response()->json([
            'status'  => true,
            'message' => __('Category created successfully'),
            'data'    => new CategoryResource($category)
        ], 201);
    }

    // عرض تصنيف محدد عبر الـ Route Model Binding
    public function show(Category $category)
    {
        return response()->json([
            'status' => true,
            'data'   => new CategoryResource($category)
        ], 200);
    }

    // عرض تصنيف محدد بناءً على الـ Slug مع مقالاته المنشورة
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

    // تحديث تصنيف (PUT/PATCH /api/categories/{id})
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $validatedData = $request->validated();

        if (isset($validatedData['name'])) {
            $validatedData['slug'] = Str::slug($validatedData['name']);
        }

        $validatedData['updated_by'] = auth()->id() ?? 1;

        $category->update($validatedData);

        return response()->json([
            'status'  => true,
            'message' => __('Category updated successfully'),
            'data'    => new CategoryResource($category)
        ], 200);
    }

    // حذف تصنيف (DELETE /api/categories/{id})
    public function destroy(Category $category)
    {
        $category->delete();

        return response()->json([
            'status'  => true,
            'message' => __('Category deleted successfully')
        ], 200);
    }
}