<?php
// app/Http/Controllers/Admin/AssetTypeController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssetTypeRequest;
use App\Http\Requests\UpdateAssetTypeRequest;
use App\Models\AssetType;
use App\Models\Category;
use App\Models\Organization;

class AssetTypeController extends Controller
{
    public function index()
    {
        $assetTypes = AssetType::with('category')->withCount('assets')->latest()->paginate(10);

        return view('admin.asset-types.index', compact('assetTypes'));
    }

    public function create()
    {
        return view('admin.asset-types.create', $this->formOptions());
    }

    public function store(StoreAssetTypeRequest $request)
    {
        AssetType::create($request->validated() + ['organization_id' => $this->organizationId()]);

        return redirect()->route('admin.asset-types.index')->with('success', 'Tipe aset dibuat.');
    }

    public function edit(AssetType $assetType)
    {
        return view('admin.asset-types.edit', $this->formOptions() + compact('assetType'));
    }

    public function update(UpdateAssetTypeRequest $request, AssetType $assetType)
    {
        $assetType->update($request->validated());

        return redirect()->route('admin.asset-types.index')->with('success', 'Tipe aset diperbarui.');
    }

    public function destroy(AssetType $assetType)
    {
        $assetType->delete();

        return redirect()->route('admin.asset-types.index')->with('success', 'Tipe aset dihapus (soft delete).');
    }

    private function formOptions(): array
    {
        return [
            'categories' => Category::orderBy('name')->get(),
        ];
    }

    private function organizationId(): int
    {
        return auth()->user()->organization_id ?? Organization::query()->value('id');
    }
}