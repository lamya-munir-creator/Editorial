<?php

namespace App\Http\Controllers\Api; 

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Article;
use App\Http\Resources\ArticleResource;
use Illuminate\Http\Request;

class AuthorController extends Controller
{
    /**
     * جلب قائمة الكُتّاب مع دعم البحث والتقسيم المالي (Pagination)
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $authors = Author::with('avatar')
            ->withCount('articles')
            ->when($search, function ($query, $search) {
                return $query->where('display_name', 'like', "%{$search}%")
                             ->orWhere('name', 'like', "%{$search}%")
                             ->orWhere('email', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10);

        return response()->json([
            'status'  => true,
            'message' => 'تم جلب قائمة الكُتّاب بنجاح',
            'data'    => $authors
        ], 200);
    }

    /**
     * عرض تفاصيل كاتب معين مع مقالاته وصورته عبر المعرف
     */
    public function show($id)
    {
        $author = Author::with(['avatar'])
            ->withCount('articles')
            ->findOrFail($id);

        $articles = Article::published()
            ->where('author_id', $author->id)
            ->with(['category', 'tags', 'featuredImage'])
            ->latest('published_at')
            ->paginate(10);

        return response()->json([
            'status'   => true,
            'author'   => $author,
            'articles' => ArticleResource::collection($articles),
            'meta'     => [
                'current_page' => $articles->currentPage(),
                'last_page'    => $articles->lastPage(),
                'per_page'     => $articles->perPage(),
                'total'        => $articles->total(),
            ]
        ], 200);
    }

    /**
     * عرض تفاصيل الكاتب ومقالاته بواسطة الـ Slug
     */
    public function showBySlug(string $slug)
    {
        $author = Author::with(['avatar'])
            ->withCount('articles')
            ->where('slug', $slug)
            ->first();

        if (!$author) {
            return response()->json([
                'status'  => false,
                'message' => 'الكاتب غير موجود'
            ], 404);
        }

        $articles = Article::published()
            ->where('author_id', $author->id)
            ->with(['category', 'tags', 'featuredImage'])
            ->latest('published_at')
            ->paginate(10);

        return response()->json([
            'status'   => true,
            'author'   => $author,
            'articles' => ArticleResource::collection($articles),
            'meta'     => [
                'current_page' => $articles->currentPage(),
                'last_page'    => $articles->lastPage(),
                'per_page'     => $articles->perPage(),
                'total'        => $articles->total(),
            ]
        ], 200);
    }

    /**
     * إضافة كاتب جديد
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'display_name' => 'required|string|max:255',
            'biography'    => 'nullable|string',
            'job_title'    => 'nullable|string|max:255',
            'status'       => 'nullable|in:active,inactive',
            'created_by'   => 'nullable|exists:users,id',
        ]);

        $validatedData['uuid'] = \Illuminate\Support\Str::uuid();
        $validatedData['slug'] = \Illuminate\Support\Str::slug($validatedData['display_name']) . '-' . \Illuminate\Support\Str::random(6);
        $validatedData['created_by'] = auth()->id() ?? $request->input('created_by', 1);

        $author = Author::create($validatedData);

        return response()->json([
            'status'  => true,
            'message' => 'تم إضافة الكاتِب بنجاح',
            'data'    => $author
        ], 201);
    }

    /**
     * تحديث بيانات كاتب
     */
    public function update(Request $request, $id)
    {
        $author = Author::findOrFail($id);

        $validatedData = $request->validate([
            'display_name' => 'sometimes|required|string|max:255',
            'biography'    => 'nullable|string',
            'job_title'    => 'nullable|string|max:255',
            'status'       => 'nullable|in:active,inactive',
        ]);

        $author->update($validatedData);

        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث بيانات الكاتِب بنجاح',
            'data'    => $author
        ], 200);
    }

    /**
     * حذف كاتب
     */
    public function destroy($id)
    {
        $author = Author::findOrFail($id);
        $author->delete();

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف الكاتِب بنجاح'
        ], 200);
    }
}