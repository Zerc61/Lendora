<?php
// app/Http/Controllers/Admin/OrganizationController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Models\Organization;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function __construct()
    {
        // Semua method otomatis dicek lewat OrganizationPolicy
        $this->authorizeResource(Organization::class, 'organization');
    }

    public function index()
    {
        $organizations = Organization::withCount(['users', 'programs', 'schoolClasses', 'classrooms'])
            ->latest()
            ->paginate(10);

        return view('admin.organizations.index', compact('organizations'));
    }

    public function create()
    {
        return view('admin.organizations.create');
    }

    public function store(StoreOrganizationRequest $request)
    {
        $organization = Organization::create($request->validated());

        return redirect()
            ->route('admin.organizations.index')
            ->with('success', "Organisasi {$organization->name} berhasil dibuat.");
    }

    /** Detail: struktur sekolah (jenjang → jurusan → kelas) + ruang kelas. */
    public function show(Organization $organization)
    {
        // schoolSummary() sudah menghitung students_count sendiri, jadi eager
        // load di sini cukup untuk classroom saja.
        $organization->load(['classrooms']);

        return view('admin.organizations.show', [
            'organization' => $organization,
            'summary' => $organization->schoolSummary(),
        ]);
    }

    public function edit(Organization $organization)
    {
        return view('admin.organizations.edit', compact('organization'));
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization)
    {
        $organization->update($request->validated());

        return redirect()
            ->route('admin.organizations.index')
            ->with('success', 'Organisasi berhasil diperbarui.');
    }

    public function destroy(Organization $organization)
    {
        // Soft delete — histori transaksi tidak hilang (PDF bag. 14)
        $organization->delete();

        return redirect()
            ->route('admin.organizations.index')
            ->with('success', 'Organisasi dihapus (soft delete).');
    }
}