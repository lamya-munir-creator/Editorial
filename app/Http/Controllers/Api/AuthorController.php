<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAuthorRequest;
use App\Http\Requests\UpdateAuthorRequest;
use App\Models\Author;
use App\Models\Media;
use App\Models\Article;
use App\Models\ActivityLog;
use App\Http\Resources\ArticleResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthorController extends Controller
{
    private function getActionPrefix(): string
    {
        $user = auth()->user();
        $firstName = $user ? $user->first_name : '';
        $isFemale = $firstName && (mb_substr($firstName, -1) === 'Ø©' || mb_substr($firstName, -1) === 'Ù‡');
        return $isFemale ? 'Ù‚Ø§Ù…Øª Ø¨Ù€' : 'Ù‚Ø§Ù… Ø¨Ù€';
    }

    /**
     * Ø¬Ù„Ø¨ Ù‚Ø§Ø¦Ù…Ø© Ø§Ù„ÙƒÙØªÙ‘Ø§Ø¨ Ù…Ø¹ Ø¯Ø¹Ù… Ø§Ù„Ø¨Ø­Ø« ÙˆØ§Ù„ØªÙ‚Ø³ÙŠÙ… Ø§Ù„Ù…Ø§Ù„ÙŠ (Pagination)
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $authors = Author::with(['avatar', 'user'])
            ->withCount('articles')
            ->when($search, function ($query, $search) {
                return $query->where('display_name', 'like', "%{$search}%")
                             ->orWhere('job_title', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10);

        return response()->json([
            'status'  => true,
            'message' => 'ØªÙ… Ø¬Ù„Ø¨ Ù‚Ø§Ø¦Ù…Ø© Ø§Ù„ÙƒÙØªÙ‘Ø§Ø¨ Ø¨Ù†Ø¬Ø§Ø­',
            'data'    => $authors
        ], 200);
    }

    /**
     * Ø¹Ø±Ø¶ ØªÙØ§ØµÙŠÙ„ ÙƒØ§ØªØ¨ Ù…Ø¹ÙŠÙ† Ù…Ø¹ Ù…Ù‚Ø§Ù„Ø§ØªÙ‡ ÙˆØµÙˆØ±ØªÙ‡ Ø¹Ø¨Ø± Ø§Ù„Ù…Ø¹Ø±Ù
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
     * Ø¹Ø±Ø¶ ØªÙØ§ØµÙŠÙ„ Ø§Ù„ÙƒØ§ØªØ¨ ÙˆÙ…Ù‚Ø§Ù„Ø§ØªÙ‡ Ø¨ÙˆØ§Ø³Ø·Ø© Ø§Ù„Ù€ Slug
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
                'message' => __('Ø§Ù„ÙƒØ§ØªØ¨ ØºÙŠØ± Ù…ÙˆØ¬ÙˆØ¯')
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
     * Ø¥Ø¶Ø§ÙØ© ÙƒØ§ØªØ¨ Ø¬Ø¯ÙŠØ¯
     */
    public function store(StoreAuthorRequest $request)
    {
        $validatedData = $request->validated();

        if (!isset($validatedData['user_id'])) {
            $validatedData['user_id'] = auth()->id() ?? 1;
        }

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
                    'alt_text' => $validatedData['display_name'],
                    'type' => 'image',
                    'visibility' => 'public',
                    'created_by' => $userId,
                ]);

                $validatedData['avatar_id'] = $media->id;
            }

            $remove_avatar = $request->input('remove_avatar');
            if ($remove_avatar === 'true' || $remove_avatar === '1' || $remove_avatar === true || $remove_avatar === 1) {
                $validatedData['avatar_id'] = null;
            }

            unset($validatedData['avatar']);
            $validatedData['uuid'] = Str::uuid();
            $validatedData['slug'] = Str::slug($validatedData['display_name']) . '-' . Str::random(6);
            $validatedData['created_by'] = $userId;

            return Author::create($validatedData);
        });

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'add_author',
            'action_label' => $this->getActionPrefix() . 'Ø¥Ø¶Ø§ÙØ© ÙƒØ§ØªØ¨ Ø¬Ø¯ÙŠØ¯',
            'target_name' => $author->display_name,
            'target_url' => '/authors',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'ØªÙ… Ø¥Ø¶Ø§ÙØ© Ø§Ù„ÙƒØ§ØªØ¨ Ø¨Ù†Ø¬Ø§Ø­',
            'data' => $author->load('avatar'),
        ], 201);
    }

    /**
     * ØªØ­Ø¯ÙŠØ« Ø¨ÙŠØ§Ù†Ø§Øª ÙƒØ§ØªØ¨
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
                    'alt_text' => $validatedData['display_name'] ?? $author->display_name,
                    'caption' => null,
                    'type' => 'image',
                    'visibility' => 'public',
                    'created_by' => $userId,
                    'updated_by' => null,
                ]);

                $validatedData['avatar_id'] = $media->id;
            }

            $remove_avatar = $request->input('remove_avatar');
            if ($remove_avatar === 'true' || $remove_avatar === '1' || $remove_avatar === true || $remove_avatar === 1) {
                $validatedData['avatar_id'] = null;
            }

            unset($validatedData['avatar']);
            $validatedData['updated_by'] = $userId;

            if (isset($validatedData['display_name'])) {
                $validatedData['slug'] = Str::slug($validatedData['display_name']) . '-' . Str::random(6);
            }

            $author->update($validatedData);

            return $author;
        });

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'edit_author',
            'action_label' => $this->getActionPrefix() . 'ØªØ¹Ø¯ÙŠÙ„ Ø¨ÙŠØ§Ù†Ø§Øª Ø§Ù„ÙƒØ§ØªØ¨',
            'target_name' => $author->display_name,
            'target_url' => '/authors',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'ØªÙ… ØªØ­Ø¯ÙŠØ« Ø¨ÙŠØ§Ù†Ø§Øª Ø§Ù„ÙƒØ§ØªÙØ¨ Ø¨Ù†Ø¬Ø§Ø­',
            'data' => $author->fresh()->load('avatar'),
        ], 200);
    }

    /**
     * Ø¬Ù„Ø¨ Ø¨Ø±ÙˆÙØ§ÙŠÙ„ Ø§Ù„ÙƒØ§ØªØ¨ Ø§Ù„Ø®Ø§Øµ Ø¨Ø§Ù„Ù…Ø³ØªØ®Ø¯Ù… Ø§Ù„Ù…Ø³Ø¬Ù„ Ø¯Ø®ÙˆÙ„Ù‡ Ø­Ø§Ù„ÙŠÙ‹Ø§.
     */
    public function me(Request $request)
    {
        $author = Author::with('avatar')
            ->withCount('articles')
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $author) {
            return response()->json([
                'status'  => false,
                'message' => 'Ù„Ø§ ÙŠÙˆØ¬Ø¯ Ù„Ø¯ÙŠÙƒ Ø¨Ø±ÙˆÙØ§ÙŠÙ„ ÙƒØ§ØªØ¨ Ø¨Ø¹Ø¯.',
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'ØªÙ… Ø¬Ù„Ø¨ Ù…Ù„ÙÙƒ Ø§Ù„Ø´Ø®ØµÙŠ ÙƒÙ€ ÙƒØ§ØªØ¨ Ø¨Ù†Ø¬Ø§Ø­',
            'data'    => $author,
        ], 200);
    }

    /**
     * ØªØ­Ø¯ÙŠØ« Ø¨Ø±ÙˆÙØ§ÙŠÙ„ Ø§Ù„ÙƒØ§ØªØ¨ Ø§Ù„Ø®Ø§Øµ Ø¨Ø§Ù„Ù…Ø³ØªØ®Ø¯Ù… Ø§Ù„Ù…Ø³Ø¬Ù„ Ø¯Ø®ÙˆÙ„Ù‡ Ø­Ø§Ù„ÙŠÙ‹Ø§.
     */
    public function updateMe(UpdateAuthorRequest $request)
    {
        $user = $request->user();
        $author = Author::where('user_id', $user->id)->first();

        if (! $author) {
            return response()->json([
                'status'  => false,
                'message' => 'Ù„Ø§ ÙŠÙˆØ¬Ø¯ Ù„Ø¯ÙŠÙƒ Ø¨Ø±ÙˆÙØ§ÙŠÙ„ ÙƒØ§ØªØ¨ Ø¨Ø¹Ø¯.',
            ], 404);
        }

        $validatedData = $request->validated();
        unset($validatedData['user_id'], $validatedData['status']);

        $author = DB::transaction(function () use ($request, $validatedData, $author, $user) {
            if ($request->hasFile('avatar')) {
                $file = $request->file('avatar');
                $path = $file->store('authors', 'public');

                $media = Media::create([
                    'uuid' => Str::uuid(),
                    'uploaded_by' => $user->id,
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
                    'alt_text' => $validatedData['display_name'] ?? $author->display_name,
                    'caption' => null,
                    'type' => 'image',
                    'visibility' => 'public',
                    'created_by' => $user->id,
                    'updated_by' => null,
                ]);

                $validatedData['avatar_id'] = $media->id;
            }

            $remove_avatar = $request->input('remove_avatar');
            if ($remove_avatar === 'true' || $remove_avatar === '1' || $remove_avatar === true || $remove_avatar === 1) {
                $validatedData['avatar_id'] = null;
            }

            unset($validatedData['avatar']);
            $validatedData['updated_by'] = $user->id;

            if (isset($validatedData['display_name'])) {
                $validatedData['slug'] = Str::slug($validatedData['display_name']) . '-' . Str::random(6);
            }

            $author->update($validatedData);

            return $author;
        });

        ActivityLog::create([
            'user_id' => $user->id,
            'action_type' => 'edit_author_profile',
            'action_label' => $this->getActionPrefix() . 'ØªØ¹Ø¯ÙŠÙ„ Ø§Ù„Ù…Ù„Ù Ø§Ù„Ø´Ø®ØµÙŠ Ù„Ù„ÙƒØ§ØªØ¨',
            'target_name' => $author->display_name,
            'target_url' => '/authors',
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'ØªÙ… ØªØ­Ø¯ÙŠØ« Ù…Ù„ÙÙƒ Ø§Ù„Ø´Ø®ØµÙŠ ÙƒÙ€ ÙƒØ§ØªØ¨ Ø¨Ù†Ø¬Ø§Ø­',
            'data'    => $author->fresh()->load('avatar'),
        ], 200);
    }

    /**
     * Ø­Ø°Ù ÙƒØ§ØªØ¨
     */
    public function destroy($id)
    {
        $author = Author::findOrFail($id);
        $name = $author->display_name;
        $author->delete();

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'delete_author',
            'action_label' => $this->getActionPrefix() . 'Ø­Ø°Ù Ø§Ù„ÙƒØ§ØªØ¨',
            'target_name' => $name,
            'target_url' => null,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'ØªÙ… Ø­Ø°Ù Ø§Ù„ÙƒØ§ØªÙØ¨ Ø¨Ù†Ø¬Ø§Ø­'
        ], 200);
    }
}
