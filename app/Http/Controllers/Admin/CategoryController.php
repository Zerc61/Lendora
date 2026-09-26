<?php
// app/Http/Controllers/Admin/CategoryController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\Organization;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('assetTypes')->latest()->paginate(10);

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(StoreCategoryRequest $request)
    {
        $category = Category::create($request->validated() + ['organization_id' => $this->organizationId()]);

        return redirect()->route('admin.categories.index')->with('success', "Kategori {$category->name} dibuat.");
    }

    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $category->update($request->validated());

        return redirect()->route('admin.categories.index')->with('success', 'Kategori diperbarui.');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Kategori dihapus (soft delete).');
    }

    private function organizationId(): int
    {
        return auth()->user()->organization_id ?? Organization::query()->value('id');
    }
}