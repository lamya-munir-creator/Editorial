<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\AuthorApplication;
use App\Models\Role;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\User;
use App\Notifications\SystemAlert;
use Illuminate\Support\Facades\Notification;
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

        $application = $user->authorApplications()->create([
            'uuid' => (string) Str::uuid(),
            'display_name' => $validatedData['display_name'],
            'job_title' => $validatedData['job_title'] ?? null,
            'biography' => $validatedData['biography'] ?? null,
            'website' => $validatedData['website'] ?? null,
            'application_message' => $validatedData['application_message'] ?? null,
            'status' => 'pending',
        ]);

        $admins = User::whereHas('role', function($q) { $q->whereIn('name', ['admin', 'super-admin']); })->get();
        Notification::send($admins, new SystemAlert('طلب انضمام كاتب جديد من ' . $application->display_name, 'info', '/admin/author-applications'));

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
            'data' => $application,
        ], 201);
    }

    public function mine(Request $request): JsonResponse
    {
        $application = $request->user()
            ->authorApplications()
            ->with('reviewer:id,first_name,last_name,username')
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

                if ($user->authorProfile) {
                    throw new \RuntimeException('هذا المستخدم لديه ملف كاتب بالفعل.');
                }

                $role = Role::where('slug', 'author')
                    ->orWhere('name', 'author')
                    ->first();

                if (!$role) {
                    throw new \RuntimeException('دور الكاتب غير موجود في قاعدة البيانات.');
                }

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

                $author = Author::create([
                    'uuid' => (string) Str::uuid(),
                    'user_id' => $user->id,
                    'display_name' => $application->display_name,
                    'slug' => $slug,
                    'biography' => $application->biography,
                    'job_title' => $application->job_title,
                    'website' => $application->website,
                    'status' => 'active',
                    'created_by' => $admin->id,
                ]);

                $user->update([
                    'role_id' => $role->id,
                    'updated_by' => $admin->id,
                ]);

                $user->assignRole($role->name);

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

            $application->user->notify(new SystemAlert('تمت الموافقة على طلب انضمامك ككاتب!', 'success', '/author/dashboard'));
            
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

        $application->user->notify(new SystemAlert('تم رفض طلب انضمامك ككاتب.', 'error', '/'));

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