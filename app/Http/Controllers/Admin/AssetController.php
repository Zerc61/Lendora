<?php
// app/Http/Controllers/Admin/AssetController.php

namespace App\Http\Controllers\Admin;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Category;
use App\Models\Location;
use App\Models\Organization;
use App\Services\AssetCodeGenerator;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Asset::class, 'asset');
    }

    public function index(Request $request)
    {
        $assets = Asset::query()
            ->with(['assetType.category', 'location'])
            // Filter pencarian (PDF 4D: asset code, serial, nama tipe)
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('asset_code', 'like', "%{$request->search}%")
                ->orWhere('serial_number', 'like', "%{$request->search}%")
                ->orWhereHas('assetType', fn ($t) => $t->where('name', 'like', "%{$request->search}%"))))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('category_id'), fn ($q) => $q->whereHas('assetType', fn ($t) => $t->where('category_id', $request->category_id)))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.assets.index', [
            'assets' => $assets,
            'statuses' => collect(AssetStatus::cases()),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.assets.create', $this->formOptions());
    }

    public function store(StoreAssetRequest $request)
    {
        $data = $request->validated();
        $data['organization_id'] = $this->organizationId();

        // Asset code unik: auto-generate jika dikosongkan (Acceptance Criteria PDF bag. 13)
        if (empty($data['asset_code'])) {
            $type = AssetType::with('category')->findOrFail($data['asset_type_id']);
            $data['asset_code'] = app(AssetCodeGenerator::class)->generate($type);
        }

        $asset = Asset::create($data);

        return redirect()->route('admin.assets.show', $asset)->with('success', "Aset {$asset->asset_code} dibuat.");
    }

    public function show(Asset $asset)
    {
        $asset->load(['assetType.category', 'location', 'attachments.uploader']);

        return view('admin.assets.show', [
            'asset' => $asset,
            'inspections' => $asset->inspections()->with('inspector')->latest('inspected_at')->take(10)->get(),
        ]);
    }

    public function edit(Asset $asset)
    {
        return view('admin.assets.edit', $this->formOptions() + compact('asset'));
    }

    public function update(UpdateAssetRequest $request, Asset $asset)
    {
        $data = $request->validated();

        // ── State machine enforcement (PDF bag. 7 & 9) ──
        $newStatus = AssetStatus::from($data['status']);
        if ($newStatus !== $asset->status && ! $asset->status->canTransitionTo($newStatus)) {
            return back()
                ->withErrors(['status' => "Transisi status tidak valid: {$asset->status->label()} → {$newStatus->label()}."])
                ->withInput();
        }

        $asset->update($data);

        return redirect()->route('admin.assets.show', $asset)->with('success', "Aset {$asset->asset_code} diperbarui.");
    }

    public function destroy(Asset $asset)
    {
        // Aset sedang dalam siklus pinjam tidak boleh dihapus (integritas histori)
        if (in_array($asset->status, [AssetStatus::Reserved, AssetStatus::Borrowed], true)) {
            return back()->withErrors("Aset {$asset->asset_code} sedang reserved/borrowed — tidak dapat dihapus.");
        }

        $asset->delete(); // soft delete — histori transaksi tetap aman (PDF bag. 14)

        return redirect()->route('admin.assets.index')->with('success', 'Aset dihapus (soft delete).');
    }

    private function formOptions(): array
    {
        $allStatuses = collect(AssetStatus::cases());
        $initialStatuses = $allStatuses->reject(
            fn (AssetStatus $s) => in_array($s, [AssetStatus::Reserved, AssetStatus::Borrowed], true)
        )->values();

        return [
            'typesGrouped' => AssetType::with('category')->get()
                ->groupBy(fn (AssetType $t) => $t->category->name)
                ->sortKeys(),
            'locations' => Location::orderBy('name')->get(),
            'conditions' => collect(AssetCondition::cases()),
            'initialStatuses' => $initialStatuses,
            'allStatuses' => $allStatuses,
        ];
    }

    private function organizationId(): int
    {
        return auth()->user()->organization_id ?? Organization::query()->value('id');
    }
}