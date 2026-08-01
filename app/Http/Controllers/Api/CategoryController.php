<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Article;
use Illuminate\Http\Request;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ArticleResource;

class CategoryController extends Controller
{
    // عرض كل التصنيفات باستخدام CategoryResource (GET /api/categories)
    public function index()
    {
        $categories = Category::where('is_active', true)->latest()->paginate(10);

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
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
        ]);

        $category = Category::create($request->all());

        return response()->json([
            'status'  => true,
            'message' => 'تم إنشاء التصنيف بنجاح',
            'data'    => new CategoryResource($category)
        ], 201);
    }

    // عرض تصنيف محدد باستخدام CategoryResource (GET /api/categories/{id})
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
                'message' => 'التصنيف غير موجود'
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
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
        ]);

        $category->update($request->all());

        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث التصنيف بنجاح',
            'data'    => new CategoryResource($category)
        ], 200);
    }

    // حذف تصنيف (DELETE /api/categories/{id})
    public function destroy(Category $category)
    {
        $category->delete();

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف التصنيف بنجاح'
        ], 200);
    }
}