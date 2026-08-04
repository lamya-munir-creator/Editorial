<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAuthorRequest;
use App\Http\Requests\UpdateAuthorRequest;
use App\Models\Author;
use App\Models\Media;
use App\Models\Article;
use App\Http\Resources\ArticleResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
    public function store(StoreAuthorRequest $request)
    {
        $validatedData = $request->validated();

        $author = DB::transaction(function () use ($request, $validatedData) {
            $userId = auth()->id() ?? 1;

            if ($request->hasFile('avatar')) {
                $file = $request->file('avatar');

                $path = $file->store('authors', 'public');

                $media = Media::create([
                    'uuid' => Str::uuid(),
                    'uploaded_by' => $userId,
                    'file_name' => basename($path),
                    'original_name' => $file->getClientOriginalName(),
                    'disk' => 'public',
                    'path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'extension' => $file->getClientOriginalExtension(),
                    'file_size' => $file->getSize(),
                    'width' => null,
                    'height' => null,
                    'duration' => null,
                    'alt_text' => $validatedData['display_name'],
                    'caption' => null,
                    'type' => 'image',
                    'visibility' => 'public',
                    'created_by' => $userId,
                    'updated_by' => null,
                ]);

                $validatedData['avatar_id'] = $media->id;
            }

            unset($validatedData['avatar']);

            $validatedData['uuid'] = Str::uuid();

            $validatedData['slug'] =
                Str::slug($validatedData['display_name'])
                . '-'
                . Str::random(6);

            $validatedData['created_by'] = $userId;

            return Author::create($validatedData);
        });

        return response()->json([
            'status' => true,
            'message' => 'تم إضافة الكاتب بنجاح',
            'data' => $author->load('avatar'),
        ], 201);
    }

    /**
     * تحديث بيانات كاتب
     */
    public function update(UpdateAuthorRequest $request, $id)
    {
        $author = Author::findOrFail($id);

        $validatedData = $request->validated();

        $author = DB::transaction(function () use ($request, $validatedData, $author) {
            $userId = auth()->id() ?? 1;

            if ($request->hasFile('avatar')) {
                $file = $request->file('avatar');

                $path = $file->store('authors', 'public');

                $media = Media::create([
                    'uuid' => Str::uuid(),
                    'uploaded_by' => $userId,
                    'file_name' => basename($path),
                    'original_name' => $file->getClientOriginalName(),
                    'disk' => 'public',
                    'path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'extension' => $file->getClientOriginalExtension(),
                    'file_size' => $file->getSize(),
                    'width' => null,
                    'height' => null,
                    'duration' => null,
                    'alt_text' => $validatedData['display_name']
                        ?? $author->display_name,
                    'caption' => null,
                    'type' => 'image',
                    'visibility' => 'public',
                    'created_by' => $userId,
                    'updated_by' => null,
                ]);

                $validatedData['avatar_id'] = $media->id;
            }

            unset($validatedData['avatar']);

            $validatedData['updated_by'] = $userId;

            if (isset($validatedData['display_name'])) {
                $validatedData['slug'] =
                    Str::slug($validatedData['display_name'])
                    . '-'
                    . Str::random(6);
            }

            $author->update($validatedData);

            return $author;
        });

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث بيانات الكاتِب بنجاح',
            'data' => $author->fresh()->load('avatar'),
        ], 200);
    }

    /**
     * حذف كاتب
     */
    public function destroy($id)
    {
        $author = Author::findOrFail($ID);
        $author->delete();

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف الكاتِب بنجاح'
        ], 200);
    }
}