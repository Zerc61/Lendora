<?php
// app/Http/Controllers/Admin/LocationController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLocationRequest;
use App\Http\Requests\UpdateLocationRequest;
use App\Models\Location;
use App\Models\Organization;

class LocationController extends Controller
{
    public function index()
    {
        $locations = Location::with(['parent', 'children'])->withCount('assets')->latest()->paginate(10);

        return view('admin.locations.index', compact('locations'));
    }

    public function create()
    {
        return view('admin.locations.create', $this->formOptions());
    }

    public function store(StoreLocationRequest $request)
    {
        Location::create($request->validated() + ['organization_id' => $this->organizationId()]);

        return redirect()->route('admin.locations.index')->with('success', 'Lokasi dibuat.');
    }

    public function edit(Location $location)
    {
        return view('admin.locations.edit', $this->formOptions() + compact('location'));
    }

    public function update(UpdateLocationRequest $request, Location $location)
    {
        $location->update($request->validated());

        return redirect()->route('admin.locations.index')->with('success', 'Lokasi diperbarui.');
    }

    public function destroy(Location $location)
    {
        $location->delete();

        return redirect()->route('admin.locations.index')->with('success', 'Lokasi dihapus (soft delete).');
    }

    private function formOptions(): array
    {
        return [
            'locations' => Location::orderBy('name')->get(),
        ];
    }

    private function organizationId(): int
    {
        return auth()->user()->organization_id ?? Organization::query()->value('id');
    }
}