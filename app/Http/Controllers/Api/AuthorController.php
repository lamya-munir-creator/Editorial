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
        $isFemale = $firstName && (mb_substr($firstName, -1) === 'ة' || mb_substr($firstName, -1) === 'ه');
        return $isFemale ? 'قامت بـ' : 'قام بـ';
    }

       /**
     * جلب قائمة الكُتّاب مع دعم البحث والتقسيم المالي (Pagination)
     */
    public function index(Request $request)
    {
        // ندعم كلاً من search و q لضمان التوافق التام مع الواجهة الأمامية
        $search = $request->input('search', $request->input('q'));

        $authors = Author::with(['avatar', 'user.role'])
            ->withCount('articles')
            ->when($search, function ($query, $search) {
                return $query->where(function($qBuilder) use ($search) {
                    $qBuilder->where('display_name', 'like', "%{$search}%")
                             ->orWhere('job_title', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($request->input('per_page', 10)); // السماح للواجهة بتحديد العدد

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
                'message' => __('الكاتب غير موجود')
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
     * إضافة كاتب جديد مع حساب مستخدم وتوليد كلمة مرور تلقائية وإرسالها عبر البريد
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name'   => 'required|string|max:100',
            'last_name'    => 'required|string|max:100',
            'username'     => 'required|string|max:100|unique:users,username',
            'email'        => 'required|email|max:255|unique:users,email',
            'password'     => 'nullable|string|min:6',
            'role'         => 'required|string',
            'job_title'    => 'nullable|string|max:150',
            'website'      => 'nullable|string|max:255',
            'display_name' => 'required|string|max:255',
            'biography'    => 'nullable|string',
            'gender'       => 'required|in:male,female,other',
            'status'       => 'required|in:active,inactive',
        ]);

        $author = DB::transaction(function () use ($request) {
            $currentUserId = auth()->id() ?? 1;

            $plainPassword = $request->filled('password') ? $request->password : Str::random(10);

            $role = \App\Models\Role::where('name', $request->role)->first();
            $roleId = $role ? $role->id : 5;

            $user = \App\Models\User::create([
                'uuid'       => Str::uuid(),
                'role_id'    => $roleId,
                'first_name' => $request->first_name,
                'last_name'  => $request->last_name,
                'username'   => $request->username,
                'email'      => $request->email,
                'password'   => \Illuminate\Support\Facades\Hash::make($plainPassword),
                'status'     => $request->status,
                'created_by' => $currentUserId,
            ]);

            $user->syncRoles([$request->role]);

            $avatarId = null;
            if ($request->hasFile('avatar')) {
                $file = $request->file('avatar');
                $path = $file->store('authors', 'public');

                $media = Media::create([
                    'uuid' => Str::uuid(),
                    'uploaded_by' => $currentUserId,
                    'file_name' => basename($path),
                    'original_name' => $file->getClientOriginalName(),
                    'disk' => 'public',
                    'path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'extension' => $file->getClientOriginalExtension(),
                    'file_size' => $file->getSize(),
                    'alt_text' => $request->display_name,
                    'type' => 'image',
                    'visibility' => 'public',
                    'created_by' => $currentUserId,
                ]);

                $avatarId = $media->id;
            }

            $author = Author::create([
                'uuid'         => Str::uuid(),
                'user_id'      => $user->id,
                'display_name' => $request->display_name,
                'slug'         => Str::slug($request->display_name) . '-' . Str::random(6),
                'job_title'    => $request->job_title,
                'website'      => $request->website,
                'biography'    => $request->biography,
                'gender'       => $request->gender,
                'status'       => $request->status,
                'avatar_id'    => $avatarId,
                'created_by'   => $currentUserId,
            ]);

            try {
                \Illuminate\Support\Facades\Mail::raw(
                    "مرحباً {$request->first_name},\n\nتم إنشاء حسابك بنجاح في منصة لومين.\nبريدك الإلكتروني: {$request->email}\nكلمة المرور الخاصة بك هي: {$plainPassword}\n\nيمكنك تسجيل الدخول وتغيير كلمة المرور الخاصة بك في أي وقت.",
                    function ($message) use ($request) {
                        $message->to($request->email)
                                ->subject('بيانات حسابك الجديد في منصة لومين');
                    }
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send author password email: ' . $e->getMessage());
            }

            return $author;
        });

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'add_author',
            'action_label' => $this->getActionPrefix() . 'إضافة كاتب جديد مع حساب مستخدم',
            'target_name' => $author->display_name,
            'target_url' => '/authors',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم إضافة الكاتب وإنشاء حسابه وتوليد كلمة المرور وإرسالها إلى بريده بنجاح',
            'data' => $author->load(['avatar', 'user.role']),
        ], 201);
    }

    /**
     * تحديث بيانات الكاتب أو حالته
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
                    'alt_text' => $validatedData['display_name'] ?? $author->display_name,
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

            unset($validatedData['avatar'], $validatedData['role']);
            $validatedData['updated_by'] = $userId;

            if (isset($validatedData['display_name'])) {
                $validatedData['slug'] = Str::slug($validatedData['display_name']) . '-' . Str::random(6);
            }

            if ($request->has('job_title')) {
                $validatedData['job_title'] = $request->job_title;
            }

            $author->update($validatedData);

            // تحديث بيانات المستخدم المرتبط (بما فيها الاسم الأول واسم العائلة والدور والحالة)
            if ($author->user) {
                $userData = [];
                
                if ($request->filled('first_name')) {
                    $userData['first_name'] = $request->first_name;
                }
                if ($request->filled('last_name')) {
                    $userData['last_name'] = $request->last_name;
                }
                if ($request->filled('username')) {
                    $userData['username'] = $request->username;
                }
                if ($request->filled('email')) {
                    $userData['email'] = $request->email;
                }
                if (isset($validatedData['status'])) {
                    $userData['status'] = $validatedData['status'];
                }

                if ($request->filled('role')) {
                    $roleName = $request->role;
                    $role = \App\Models\Role::where('name', $roleName)->first();
                    if ($role) {
                        $userData['role_id'] = $role->id;
                    }
                    if (method_exists($author->user, 'syncRoles')) {
                        $author->user->syncRoles([$roleName]);
                    }
                }

                if (!empty($userData)) {
                    $author->user->update($userData);
                }
            }

            if ($request->filled('role') && strtolower($request->role) === 'author') {
                \App\Models\Article::where('created_by', $author->user_id)
                    ->whereNull('author_id')
                    ->update(['author_id' => $author->id]);
            }

            return $author;
        });

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'edit_author',
            'action_label' => $this->getActionPrefix() . 'تعديل بيانات الكاتب',
            'target_name' => $author->display_name,
            'target_url' => '/authors',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث بيانات الكاتب بنجاح',
            'data' => $author->fresh()->load(['avatar', 'user.role']),
        ], 200);
    }

    /**
     * جلب بروفايل الكاتب الخاص بالمستخدم المسجل دخوله حاليًا.
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
                'message' => 'لا يوجد لديك بروفايل كاتب بعد.',
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'تم جلب ملفك الشخصي كـ كاتب بنجاح',
            'data'    => $author,
        ], 200);
    }

    /**
     * تحديث بروفايل الكاتب الخاص بالمستخدم المسجل دخوله حاليًا.
     */
    public function updateMe(UpdateAuthorRequest $request)
    {
        $user = $request->user();
        $author = Author::where('user_id', $user->id)->first();

        if (! $author) {
            return response()->json([
                'status'  => false,
                'message' => 'لا يوجد لديك بروفايل كاتب بعد.',
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
            'action_label' => $this->getActionPrefix() . 'تعديل الملف الشخصي للكاتب',
            'target_name' => $author->display_name,
            'target_url' => '/authors',
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث ملفك الشخصي كـ كاتب بنجاح',
            'data'    => $author->fresh()->load('avatar'),
        ], 200);
    }

    /**
     * حذف كاتب
     */
    public function destroy($id)
    {
        $author = Author::findOrFail($id);
        $name = $author->display_name;
        $author->delete();

        ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action_type' => 'delete_author',
            'action_label' => $this->getActionPrefix() . 'حذف الكاتب',
            'target_name' => $name,
            'target_url' => null,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف الكاتب بنجاح'
        ], 200);
    }
}