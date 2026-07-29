<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use App\Http\Resources\ArticleResource;

class ArticleController extends Controller
{
    public function index()
    {
        // استخدام Eager Loading لتحسين الأداء وتجنب N+1 Problem
        $articles = Article::with(['category', 'tags', 'author'])->latest()->paginate(10);

        return response()->json([
            'status' => true,
            'data'   => ArticleResource::collection($articles),
            'meta'   => [
                'current_page' => $articles->currentPage(),
                'last_page'    => $articles->lastPage(),
                'total'        => $articles->total(),
            ]
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'content'     => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'tags'        => 'nullable|array',
            'tags.*'      => 'exists:tags,id',
        ]);

        $validated['author_id'] = auth()->id() ?? 1; // مؤقتاً لحين ربط المصادقة
        $validated['slug'] = \Str::slug($request->title);

        $article = Article::create($validated);

        if ($request->has('tags')) {
            $article->tags()->attach($request->tags);
        }

        return response()->json([
            'status'  => true,
            'message' => 'تم نشر المقال بنجاح',
            'data'    => new ArticleResource($article->load(['category', 'tags']))
        ], 201);
    }

    public function show(Article $article)
    {
        return response()->json([
            'status' => true,
            'data'   => new ArticleResource($article->load(['category', 'tags', 'author']))
        ], 200);
    }

    public function update(Request $request, Article $article)
    {
        $validated = $request->validate([
            'title'       => 'sometimes|required|string|max:255',
            'content'     => 'sometimes|required|string',
            'category_id' => 'sometimes|required|exists:categories,id',
            'tags'        => 'nullable|array',
            'tags.*'      => 'exists:tags,id',
        ]);

        if (isset($validated['title'])) {
            $validated['slug'] = \Str::slug($validated['title']);
        }

        $article->update($validated);

        if ($request->has('tags')) {
            $article->tags()->sync($request->tags);
        }

        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث المقال بنجاح',
            'data'    => new ArticleResource($article->load(['category', 'tags']))
        ], 200);
    }

    public function destroy(Article $article)
    {
        $article->tags()->detach(); // فك ارتباط الوسوم أولاً
        $article->delete();

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف المقال بنجاح'
        ], 200);
    }
}