<?php
namespace App\Http\Controllers;

use App\Models\Author;
use Illuminate\Http\Request;

class AuthorController extends Controller
{
    // جلب كل الكُتّاب
    public function index(Request $request)
    {
        $search = $request->input('search');
        
        $authors = Author::when($search, function ($query, $search) {
            return $query->where('name', 'like', "%{$search}%");
        })->latest()->paginate(10);

        return view('authors.index', compact('authors'));
    }

    // عرض تفاصيل كاتِب معين
    public function show($id)
    {
        $author = Author::with('articles')->findOrFail($id);
        
        return view('authors.show', compact('author'));
    }

    // تخزين/إضافة كاتِب جديد
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:authors,email',
            'bio' => 'nullable|string',
        ]);

        Author::create($validatedData);

        return redirect()->route('authors.index')->with('success', 'تم إضافة الكاتِب بنجاح');
    }

    // [إضافة جديدة] عرض صفحة أو نموذج تعديل الكاتِب
    public function edit($id)
    {
        $author = Author::findOrFail($id);
        
        return view('authors.edit', compact('author'));
    }

    // [إضافة جديدة] تحديث بيانات الكاتِب
    public function update(Request $request, $id)
    {
        $author = Author::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            // استثناء البريد الإلكتروني الخاص بالكاتب الحالي من شرط الـ unique
            'email' => 'required|email|unique:authors,email,' . $author->id,
            'bio' => 'nullable|string',
        ]);

        $author->update($validatedData);

        return redirect()->route('authors.index')->with('success', 'تم تحديث بيانات الكاتِب بنجاح');
    }

    // حذف كاتِب
    public function destroy($id)
    {
        $author = Author::findOrFail($id);
        $author->delete();

        return redirect()->route('authors.index')->with('success', 'تم حذف الكاتِب بنجاح');
    }
}