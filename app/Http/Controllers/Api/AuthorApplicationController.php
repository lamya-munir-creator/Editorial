<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\AuthorApplication;
use App\Models\Role;
use App\Models\ActivityLog;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\User;
use App\Notifications\SystemAlert;
use Illuminate\Support\Facades\Notification;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Http\Requests\StoreAuthorApplicationRequest;

class AuthorApplicationController extends Controller
{
    private function getActionPrefix(): string
    {
        $user = auth()->user();
        $firstName = $user ? $user->first_name : '';
        $isFemale = $firstName && (mb_substr($firstName, -1) === 'ة' || mb_substr($firstName, -1) === 'ه');
        return $isFemale ? 'قامت بـ' : 'قام بـ';
    }

        public function store(StoreAuthorApplicationRequest $request): JsonResponse
    {
        $this->authorize('create', AuthorApplication::class);
        $user = $request->user();

        if ($user->authorProfile) {
            return response()->json([
                'status' => false,
                'message' => 'لديك ملف كاتب بالفعل.',
            ], 422);
        }

        $hasPendingApplication = $user->authorApplications()
            ->where('status', 'pending')
            ->exists();

        if ($hasPendingApplication) {
            return response()->json([
                'status' => false,
                'message' => 'لديك طلب كاتب قيد المراجعة بالفعل.',
            ], 422);
        }

        $validatedData = $request->validated();

        $avatarId = null;
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $path = $file->store('author-applications', 'public');

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
                'alt_text' => $validatedData['display_name'],
                'type' => 'image',
                'visibility' => 'public',
                'created_by' => $user->id,
            ]);

            $avatarId = $media->id;
        }

        $application = $user->authorApplications()->create([
            'uuid' => (string) Str::uuid(),
            'display_name' => $validatedData['display_name'],
            'job_title' => $validatedData['job_title'] ?? null,
            'biography' => $validatedData['biography'] ?? null,
            'website' => $validatedData['website'] ?? null,
            'application_message' => $validatedData['application_message'] ?? null,
            'gender' => $validatedData['gender'] ?? 'other',
            'avatar_id' => $avatarId,
            'status' => 'pending',
        ]);

        $admins = User::whereHas('roles', function($q) { $q->whereIn('name', ['admin', 'super-admin']); })
            ->orWhereHas('role', function($q) { $q->whereIn('name', ['admin', 'super-admin']); })
            ->get();
            
        // --- نظام الإشعارات الجديد (طلب انضمام) ---
        Notification::send($admins, new \App\Notifications\SystemNotification(
            'هناك طلب انضمام للمستخدم (' . $application->display_name . ')', 
            'info', 
            '/admin/author-applications'
        ));
        // ----------------------------------------

        ActivityLog::create([
            'user_id' => $user->id,
            'action_type' => 'apply_author',
            'action_label' => $this->getActionPrefix() . 'تقديم طلب انضمام ككاتب',
            'target_name' => $application->display_name,
            'target_url' => '/author-applications',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم إرسال طلب الانضمام ككاتب بنجاح.',
            'data' => $application->load('avatar'),
        ], 201);
    }


    public function mine(Request $request): JsonResponse
    {
        $application = $request->user()
            ->authorApplications()
            ->with(['reviewer:id,first_name,last_name,username', 'avatar'])
            ->latest()
            ->first();

        return response()->json([
            'status' => true,
            'message' => $application
                ? 'تم جلب طلب الكاتب بنجاح.'
                : 'لا يوجد طلب كاتب.',
            'data' => $application,
        ], 200);
    }

    public function index(Request $request): JsonResponse
    {
        $query = AuthorApplication::with([
            'user:id,first_name,last_name,username,email',
            'reviewer:id,first_name,last_name,username',
            'avatar',
        ])->latest();

        if ($request->filled('status')) {
            $request->validate([
                'status' => [
                    'string',
                    'in:pending,approved,rejected',
                ],
            ]);

            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('display_name', 'like', "%{$search}%")
                  ->orWhere('job_title', 'like', "%{$search}%")
                  ->orWhereHas('user', function($u) use ($search) {
                      $u->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $applications = $query->paginate(20);

        return response()->json([
            'status' => true,
            'message' => 'تم جلب طلبات الكتاب بنجاح.',
            'data' => $applications,
        ], 200);
    }

    public function show(AuthorApplication $application): JsonResponse
    {
        $application->load([
            'user:id,uuid,role_id,first_name,last_name,username,email,phone,avatar_id,locale,status',
            'reviewer:id,uuid,first_name,last_name,username',
            'avatar',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم جلب طلب الكاتب بنجاح.',
            'data' => $application,
        ], 200);
    }

            public function approve(Request $request, AuthorApplication $application): JsonResponse
    {
        $this->authorize('review', $application);
        if ($application->status !== 'pending') {
            return response()->json([
                'status' => false,
                'message' => 'تمت مراجعة هذا الطلب مسبقًا.',
            ], 422);
        }

        $admin = $request->user();

        try {
            $result = DB::transaction(function () use ($application, $admin) {
                $user = $application->user()
                    ->lockForUpdate()
                    ->firstOrFail();

                // تم حذف الشرط المزعج هنا الذي كان يمنع الموافقة

                // البحث الآمن عن دور الكاتب
                $role = Role::whereRaw('LOWER(name) = ?', ['author'])
                    ->orWhereRaw('LOWER(slug) = ?', ['author'])
                    ->first();

                if (!$role) {
                    throw new \RuntimeException('دور الكاتب غير موجود في قاعدة البيانات.');
                }

                // تنظيف وإزالة أي طلبات معلقة أخرى قديمة لنفس المستخدم
                $user->authorApplications()
                    ->where('id', '!=', $application->id)
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'rejected',
                        'admin_notes' => 'تم رفضه تلقائياً بسبب الموافقة على طلب أحدث.',
                        'reviewed_by' => $admin->id,
                        'reviewed_at' => now(),
                    ]);

                $baseSlug = Str::slug($application->display_name);
                if ($baseSlug === '') {
                    $baseSlug = 'author';
                }

                $slug = $baseSlug;
                $counter = 1;

                while (Author::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }

                // إنشاء بروفايل الكاتب (سيقوم بالتحديث إن وجد ملف سابق)
                $author = Author::updateOrCreate(
                    ['user_id' => $user->id], // المفتاح الفريد للبحث
                    [
                        'uuid' => (string) Str::uuid(),
                        'display_name' => $application->display_name,
                        'slug' => $slug,
                        'biography' => $application->biography,
                        'job_title' => $application->job_title,
                        'website' => $application->website,
                        'status' => 'active',
                        'created_by' => $admin->id,
                    ]
                );

                // تحديث جدول users وإزالة الأدوار القديمة تماماً وإعطاء دور الكاتب
                $user->update([
                    'role_id' => $role->id,
                    'updated_by' => $admin->id,
                ]);

                // استبدال أي أدوار سابقة بدور الكاتب نظيفاً عبر Spatie
                $user->syncRoles([$role->name]);

                $application->update([
                    'status' => 'approved',
                    'reviewed_by' => $admin->id,
                    'reviewed_at' => now(),
                ]);

                return [
                    'author' => $author,
                    'user' => $user->fresh(),
                    'application' => $application->fresh(),
                ];
            });

            // --- نظام الإشعارات الجديد (موافقة) ---
            $application->user->notify(new \App\Notifications\SystemNotification(
                'تمت الموافقة على طلب انضمامك ككاتب!', 
                'success', 
                '/author/dashboard'
            ));
            // ----------------------------------------
            
            ActivityLog::create([
                'user_id' => $admin->id,
                'action_type' => 'approve_author',
                'action_label' => $this->getActionPrefix() . 'الموافقة على طلب الكاتب',
                'target_name' => $application->display_name,
                'target_url' => '/authors',
            ]);

            return response()->json([
                'status' => true,
                'message' => 'تمت الموافقة على طلب الكاتب وإنشاء ملف الكاتب بنجاح.',
                'data' => [
                    'application' => $result['application'],
                    'author' => $result['author']->load('avatar'),
                    'user' => $result['user']->load(['role:id,uuid,name,slug', 'roles:id,uuid,name,slug'])->only([
                        'id', 'uuid', 'role_id', 'first_name', 'last_name', 'username', 'email', 'phone', 'avatar_id', 'locale', 'status',
                    ]),
                ],
            ], 200);
        } catch (\RuntimeException $exception) {
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }


        public function reject(Request $request, AuthorApplication $application): JsonResponse
    {
        $this->authorize('review', $application);
        if ($application->status !== 'pending') {
            return response()->json([
                'status' => false,
                'message' => 'تمت مراجعة هذا الطلب مسبقًا.',
            ], 422);
        }

        $validatedData = $request->validate([
            'admin_notes' => ['nullable', 'string'],
        ]);

        $application->update([
            'status' => 'rejected',
            'admin_notes' => $validatedData['admin_notes'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        // --- نظام الإشعارات الجديد (رفض) ---
        $application->user->notify(new \App\Notifications\SystemNotification(
            'تم رفض طلب انضمامك ككاتب.', 
            'danger', 
            '/'
        ));
        // ----------------------------------------

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action_type' => 'reject_author',
            'action_label' => $this->getActionPrefix() . 'رفض طلب الكاتب',
            'target_name' => $application->display_name,
            'target_url' => '/author-applications',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم رفض طلب الكاتب.',
            'data' => $application->fresh()->load('reviewer'),
        ], 200);
    }
}