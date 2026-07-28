<?php
namespace App\Http\Controllers;

use App\Models\Advertisement;
use Illuminate\Http\Request;

class AdvertisementController extends Controller
{
    // جلب جميع الإعلانات
    public function index()
    {
        $advertisements = Advertisement::latest()->paginate(10);
        
        return view('admin.advertisements.index', compact('advertisements'));
    }

    // عرض إعلان معين
    public function show($id)
    {
        $advertisement = Advertisement::findOrFail($id);
        
        return view('admin.advertisements.show', compact('advertisement'));
    }

    // إضافة إعلان جديد
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'link' => 'nullable|url',
            'status' => 'required|boolean',
        ]);

        // رفع الصورة ومعالجتها
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('ads', 'public');
            $validatedData['image'] = $imagePath;
        }

        Advertisement::create($validatedData);

        return redirect()->route('advertisements.index')->with('success', 'تم إضافـة الإعلان بنجاح');
    }

    // حذف إعلان
    public function destroy($id)
    {
        $advertisement = Advertisement::findOrFail($id);
        // يمكن هنا حذف ملف الصورة من التخزين إذا أردت
        $advertisement->delete();

        return redirect()->route('advertisements.index')->with('success', 'تم حذف الإعلان بنجاح');
    }
}