<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\AuthorApplication;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Http\Requests\StoreAuthorApplicationRequest;
class AuthorApplicationController extends Controller
{
    /**
     * إرسال طلب جديد للانضمام ككاتب.
     */
public function store(StoreAuthorApplicationRequest $request): JsonResponse    {
    $this->authorize('create', AuthorApplication::class);
        $user = $request->user();

        // المستخدم لديه Author بالفعل
        if ($user->authorProfile) {
            return response()->json([
                'status' => false,
                'message' => 'لديك ملف كاتب بالفعل.',
            ], 422);
        }

        // يوجد طلب قيد المراجعة
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
            'application_message' =>
                $validatedData['application_message'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم إرسال طلب الانضمام ككاتب بنجاح.',
            'data' => $application,
        ], 201);
    }

    /**
 * عرض أحدث طلب للكاتب للمستخدم الحالي.
 *
 * GET /api/author-applications/my
 */
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

    /**
     * عرض جميع طلبات الكتاب للإدارة.
     */
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

    /**
     * عرض طلب محدد.
     */
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

    /**
     * الموافقة على طلب الكاتب.
     *
     * عند الموافقة:
     * 1. التأكد من أن الطلب pending.
     * 2. التأكد من عدم وجود Author للمستخدم.
     * 3. الحصول على Role الكاتب.
     * 4. إنشاء Author.
     * 5. تحديث role_id.
     * 6. مزامنة Spatie Role.
     * 7. تحديث حالة الطلب.
     */
    public function approve(
        
        Request $request,
        AuthorApplication $application
    ): JsonResponse {
        $this->authorize('review', $application);
        if ($application->status !== 'pending') {
            return response()->json([
                'status' => false,
                'message' => 'تمت مراجعة هذا الطلب مسبقًا.',
            ], 422);
        }

        $admin = $request->user();

        try {
            $result = DB::transaction(function () use (
                $application,
                $admin
            ) {
                $user = $application->user()
                    ->lockForUpdate()
                    ->firstOrFail();

                // حماية إضافية من إنشاء Author مكرر
                if ($user->authorProfile) {
                    throw new \RuntimeException(
                        'هذا المستخدم لديه ملف كاتب بالفعل.'
                    );
                }

                /*
                 * الحصول على دور الكاتب.
                 *
                 * نستخدم slug أولاً ثم name كاحتياط.
                 */
                $role = Role::where('slug', 'author')
                    ->orWhere('name', 'author')
                    ->first();

                if (!$role) {
                    throw new \RuntimeException(
                        'دور الكاتب غير موجود في قاعدة البيانات.'
                    );
                }

                /*
                 * إنشاء Slug فريد للكاتب.
                 */
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

                /*
                 * إنشاء ملف الكاتب.
                 */
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

                /*
                 * تحديث Role المستخدم.
                 *
                 * النظام الحالي لديك يستخدم:
                 * role_id + Spatie Roles
                 */
                $user->update([
                    'role_id' => $role->id,
                    'updated_by' => $admin->id,
                ]);

$user->assignRole($role->name);
                /*
                 * تحديث طلب الكاتب.
                 */
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

            return response()->json([
                'status' => true,
                'message' => 'تمت الموافقة على طلب الكاتب وإنشاء ملف الكاتب بنجاح.',
                'data' => [
                    'application' => $result['application'],
                    'author' => $result['author']->load('avatar'),
                   'user' => $result['user']
    ->load([
        'role:id,uuid,name,slug',
        'roles:id,uuid,name,slug',
    ])
    ->only([
        'id',
        'uuid',
        'role_id',
        'first_name',
        'last_name',
        'username',
        'email',
        'phone',
        'avatar_id',
        'locale',
        'status',
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

    /**
     * رفض طلب الكاتب.
     */
    public function reject(
        Request $request,
        AuthorApplication $application
    ): JsonResponse {
        $this->authorize('review', $application);
        if ($application->status !== 'pending') {
            return response()->json([
                'status' => false,
                'message' => 'تمت مراجعة هذا الطلب مسبقًا.',
            ], 422);
        }

        $validatedData = $request->validate([
            'admin_notes' => [
                'nullable',
                'string',
            ],
        ]);

        $application->update([
            'status' => 'rejected',
            'admin_notes' => $validatedData['admin_notes'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم رفض طلب الكاتب.',
            'data' => $application->fresh()->load('reviewer'),
        ], 200);
    }
}