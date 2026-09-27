{{-- resources/views/admin/users/index.blade.php — Daftar pengguna
     Navbar per peran + filter bertingkat sekolah → jenjang → jurusan → kelas. --}}
@extends('layouts.app')
@section('title', 'Pengguna')
@section('chrome', 'Pengguna')

@section('content')
@php
    $roleMeta = [
        'super-admin' => 'Super Admin',
        'admin' => 'Admin',
        'staff' => 'Staff',
        'technician' => 'Teknisi',
        'borrower' => 'Borrower',
    ];
    $selectedOrg = request('organization_id') ? (int) request('organization_id') : null;
    $selectedLevel = request('level');
    $selectedProgram = request('program_id') ? (int) request('program_id') : null;
    $selectedClass = request('school_class_id') ? (int) request('school_class_id') : null;

    // Opsiturunan mengikuti filter di atasnya —=user tidak melihatIPA saat
    // memilih SMA.
    $levelOptions = collect(\App\Enums\EducationLevel::cases())
        ->filter(fn ($l) => ! $selectedOrg
            || $programs->contains(fn ($p) => $p->organization_id === $selectedOrg && $p->education_level === $l));

    $programOptions = $programs->filter(
        fn ($p) => (! $selectedOrg || $p->organization_id === $selectedOrg)
            && (! $selectedLevel || $p->education_level === $selectedLevel)
    );

    $classOptions = $schoolClasses->filter(
        fn ($c) => ! $selectedOrg || $c->program?->organization_id === $selectedOrg
    );
@endphp

<x-page-head title="Pengguna"
             subtitle="Kelola akun staff, teknisi, dan siswa. Setiap peran punya tab sendiri.">
    <x-btn :href="route('admin.users.create')" variant="primary" icon="plus">Tambah Pengguna</x-btn>
</x-page-head>

{{-- Navbar per peran: klik "Staff" untuk melihat hanya pengguna staff --}}
<nav class="role-nav" aria-label="Filter berdasarkan peran">
    <a href="{{ route('admin.users.index') }}"
       class="role-nav__item {{ $activeRole === null ? 'is-active' : '' }}"
       @if ($activeRole === null) aria-current="page" @endif>
        <x-icon name="grid" /> Semua
        <span class="role-nav__count">{{ array_sum($levelCounts) }}</span>
    </a>
    @foreach (\App\Http\Controllers\Admin\UserController::ROLE_TABS as $tab)
        <a href="{{ route('admin.users.byRole', $tab) }}"
           class="role-nav__item {{ $activeRole === $tab ? 'is-active' : '' }}"
           @if ($activeRole === $tab) aria-current="page" @endif>
            {{ $roleMeta[$tab] }}
            <span class="role-nav__count">{{ $levelCounts[$tab] ?? 0 }}</span>
        </a>
    @endforeach
</nav>

<x-filter-bar :keep="['role', 'search', 'status', 'organization_id', 'level', 'program_id', 'school_class_id']"
              :action="route('admin.users.index')"
              :reset="route('admin.users.index')"
              placeholder="Cari nama, email, atau NIS…">
    <x-slot:controls>
        <x-field name="status" label="Status">
            <x-slot:control>
                <select name="status" data-autosubmit>
                    <option value="">Semua status</option>
                    <option value="active" @selected(request('status') === 'active')>Aktif</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                </select>
            </x-slot:control>
        </x-field>

        <x-field name="organization_id" label="Sekolah">
            <x-slot:control>
                <select name="organization_id" data-autosubmit data-cascade="school">
                    <option value="">Semua sekolah</option>
                    @foreach ($organizations as $id => $name)
                        <option value="{{ $id }}" @selected($selectedOrg === $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </x-slot:control>
        </x-field>

        <x-field name="level" label="Jenjang">
            <x-slot:control>
                <select name="level" data-autosubmit data-cascade="school">
                    <option value="">Semua jenjang</option>
                    @foreach ($levelOptions as $level)
                        <option value="{{ $level->value }}" @selected($selectedLevel === $level->value)>
                            {{ $level->label() }}
                        </option>
                    @endforeach
                </select>
            </x-slot:control>
        </x-field>

        <x-field name="program_id" label="Jurusan">
            <x-slot:control>
                <select name="program_id" data-autosubmit data-cascade="school">
                    <option value="">Semua jurusan</option>
                    @foreach ($programOptions as $program)
                        <option value="{{ $program->id }}" @selected($selectedProgram === $program->id)>
                            {{ $program->name }}
                        </option>
                    @endforeach
                </select>
            </x-slot:control>
        </x-field>

        <x-field name="school_class_id" label="Kelas">
            <x-slot:control>
                <select name="school_class_id" data-autosubmit data-cascade="school">
                    <option value="">Semua kelas</option>
                    @foreach ($classOptions as $class)
                        <option value="{{ $class->id }}" @selected($selectedClass === $class->id)>
                            {{ $class->name }}
                        </option>
                    @endforeach
                </select>
            </x-slot:control>
        </x-field>
    </x-slot:controls>
</x-filter-bar>

{{-- Ringkasan filter sekolah yang aktif --}}
@if ($selectedLevel || $selectedProgram || $selectedClass)
    <div class="callout callout--brand" style="margin-bottom:16px">
        <x-icon name="users" />
        <div>
            <b>Filter kelas aktif</b>
            <p>
                @if ($selectedClass)
                    Kelas {{ $schoolClasses->firstWhere('id', $selectedClass)?->name }} ·
                @elseif ($selectedProgram)
                    Jurusan {{ $programOptions->firstWhere('id', $selectedProgram)?->name }} ·
                @elseif ($selectedLevel)
                    Jenjang {{ $levelOptions->firstWhere('value', $selectedLevel)?->label() }} ·
                @endif
                {{ number_format($users->total(), 0, ',', '.') }}
                {{ $activeRole === 'borrower' ? 'siswa' : 'pengguna' }} ditemukan.
            </p>
        </div>
    </div>
@endif

<div class="card card--flush">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">Pengguna</th>
                    <th scope="col" class="hide-sm">Sekolah / Kelas</th>
                    <th scope="col">Peran</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="hide-sm">Bergabung</th>
                    <th scope="col" class="col-actions">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>
                            <div class="cell-media">
                                <span class="avatar {{ $user->hasPhoto() ? '' : 'avatar--plain' }}">
                                    @if ($user->hasPhoto())
                                        <img src="{{ $user->photoUrl() }}" alt="Foto {{ $user->name }}">
                                    @else
                                        {{ $user->initials() }}
                                    @endif
                                </span>
                                <span class="cell-media__body">
                                    <b class="truncate">{{ $user->name }}</b>
                                    <span class="truncate">{{ $user->email }}</span>
                                </span>
                            </div>
                        </td>
                        <td class="hide-sm">
                            @if ($user->schoolClass)
                                <b>{{ $user->schoolClass->program->name }}</b>
                                <span class="table__sub">{{ $user->schoolClass->name }}</span>
                            @else
                                <span class="dim">{{ $user->organization?->name ?? '—' }}</span>
                            @endif
                        </td>
                        <td>
                            @foreach ($user->roles as $role)
                                <span class="badge tone-{{ $role->name === 'borrower' ? 'brand' : 'info' }}">{{ $role->name }}</span>
                            @endforeach
                        </td>
                        <td><x-status :status="$user->status" /></td>
                        <td class="hide-sm">
                            <span>{{ $user->created_at->locale('id')->translatedFormat('d M Y') }}</span>
                        </td>
                        <td class="col-actions">
                            <div class="btn-row btn-row--end">
                                <x-btn :href="route('admin.users.show', $user)" size="sm" variant="ghost" icon="eye"
                                        :aria-label="'Detail ' . $user->name" />
                                <x-btn :href="route('admin.users.edit', $user)" size="sm" variant="ghost" icon="edit"
                                        :aria-label="'Ubah ' . $user->name" />
                                <x-btn :href="route('admin.users.destroy', $user)" size="sm" variant="danger" icon="trash"
                                        confirm="Hapus pengguna {{ $user->name }}?"
                                        :aria-label="'Hapus ' . $user->name" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <x-empty icon="users"
                                     title="Tidak ada pengguna"
                                     text="Coba ubah filter atau tambahkan pengguna baru.">
                                <x-btn :href="route('admin.users.create')" variant="primary" size="sm">Tambah Pengguna</x-btn>
                            </x-empty>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($users->hasPages())
        <div class="card__foot">{{ $users->links() }}</div>
    @endif
</div>
@endsection
