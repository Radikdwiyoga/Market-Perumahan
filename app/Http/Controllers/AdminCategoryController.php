<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminCategoryController extends Controller
{
    public function index(): View
    {
        $this->ensureAdmin();

        return view('admin.categories.index', ['categories' => Category::query()->withCount('products')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        $this->ensureAdmin();

        return view('admin.categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdmin();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $category = Category::create($validated);
        AuditLogger::log('CATEGORY_CREATED', 'Category', $category->id, ['name' => $category->name]);

        return redirect()->route('admin.categories.index')->with('status', 'Kategori berhasil ditambahkan.');
    }

    public function edit(Category $category): View
    {
        $this->ensureAdmin();

        return view('admin.categories.edit', ['category' => $category]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $this->ensureAdmin();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name,'.$category->id],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $category->update($validated);
        AuditLogger::log('CATEGORY_UPDATED', 'Category', $category->id, ['name' => $category->name, 'status' => $category->status]);

        return redirect()->route('admin.categories.index')->with('status', 'Kategori berhasil diperbarui.');
    }

    public function toggle(Category $category): RedirectResponse
    {
        $this->ensureAdmin();
        $category->update(['status' => $category->status === 'active' ? 'inactive' : 'active']);
        AuditLogger::log('CATEGORY_STATUS_UPDATED', 'Category', $category->id, ['status' => $category->status]);

        return back()->with('status', 'Status kategori berhasil diperbarui.');
    }

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }
}
