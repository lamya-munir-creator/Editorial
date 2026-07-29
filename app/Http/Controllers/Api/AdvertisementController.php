<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use Illuminate\Http\Request;

class AdvertisementController extends Controller
{
    /**
     * جلب جميع الإعلانات
     */
    public function index()
    {
        $advertisements = Advertisement::latest()->paginate(10);

        return response()->json([
            'status' => true,
            'data'   => $advertisements
        ], 200);
    }

    /**
     * عرض تفاصيل إعلان معين
     */
    public function show($id)
    {
        $advertisement = Advertisement::findOrFail($id);

        return response()->json([
            'status' => true,
            'data'   => $advertisement
        ], 200);
    }

    /**
     * إضافة إعلان جديد
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title'           => 'required|string|max:255',
            'destination_url' => 'nullable|string',
            'link'            => 'nullable|string', 
            'position'        => 'nullable|string',
            'status'          => 'required|in:active,inactive',
            'start_date'      => 'nullable|date',
            'end_date'        => 'nullable|date|after_or_equal:start_date',
            'image_id'        => 'nullable|integer',
            'display_order'   => 'nullable|integer',
            'is_internal'     => 'nullable|boolean',
        ]);

        // ضبط اسم الرابط ليتوافق مع العمود في DB
        $validatedData['destination_url'] = $request->input('destination_url') ?? $request->input('link');
        unset($validatedData['link']);

        // قيم تلقائية للحقول المطلوبة في الجدول
        $validatedData['uuid']       = \Illuminate\Support\Str::uuid();
        $validatedData['image_id']   = $request->input('image_id', 1);
        $validatedData['created_by'] = auth()->id() ?? 1;

        $advertisement = Advertisement::create($validatedData);

        return response()->json([
            'status'  => true,
            'message' => 'تم إضافة الإعلان بنجاح',
            'data'    => $advertisement
        ], 201);
    }

    /**
     * تحديث إعلان
     */
    public function update(Request $request, $id)
    {
        $advertisement = Advertisement::findOrFail($id);

        $validatedData = $request->validate([
            'title'           => 'sometimes|required|string|max:255',
            'destination_url' => 'nullable|string',
            'link'            => 'nullable|string',
            'position'        => 'nullable|string',
            'status'          => 'sometimes|required|in:active,inactive',
            'start_date'      => 'nullable|date',
            'end_date'        => 'nullable|date|after_or_equal:start_date',
            'image_id'        => 'nullable|integer',
            'display_order'   => 'nullable|integer',
            'is_internal'     => 'nullable|boolean',
        ]);

        if ($request->has('destination_url') || $request->has('link')) {
            $validatedData['destination_url'] = $request->input('destination_url') ?? $request->input('link');
            unset($validatedData['link']);
        }

        $validatedData['updated_by'] = auth()->id() ?? 1;

        $advertisement->update($validatedData);

        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث الإعلان بنجاح',
            'data'    => $advertisement
        ], 200);
    }

    /**
     * حذف إعلان
     */
    public function destroy($id)
    {
        $advertisement = Advertisement::findOrFail($id);
        $advertisement->delete();

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف الإعلان بنجاح'
        ], 200);
    }
}