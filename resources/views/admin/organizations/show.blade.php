{{-- resources/views/admin/organizations/show.blade.php — Detail organisasi
     Menampilkan struktur sekolah: jenjang → jurusan → kelas, dan ruang kelas. --}}
@extends('layouts.app')
@section('title', $organization->name)
@section('chrome', 'Detail Organisasi')

@section('content')
<x-page-head :title="$organization->name"
             :subtitle="$organization->description ?: 'Organisasi '.($organization->code).' — belum ada deskripsi.'"
             :crumbs="['Organisasi' => route('admin.organizations.index'), \Illuminate\Support\Str::limit($organization->name, 24) => null]">
    @can('update', $organization)
        <x-btn :href="route('admin.organizations.edit', $organization)" variant="ghost" icon="edit">Ubah</x-btn>
    @endcan
</x-page-head>

{{-- Ringkasan angka --}}
<div class="grid grid--stats" style="margin-bottom:18px">
    <x-stat label="Pengguna" :value="$organization->users()->count()" icon="users" tone="brand" :delay="0"
            :href="route('admin.users.index', ['organization_id' => $organization->id])" />
    <x-stat label="Jurusan" :value="$organization->programs()->count()" icon="layers" tone="accent" :delay="60" />
    <x-stat label="Kelas" :value="$organization->schoolClasses()->count()" icon="grid" tone="info" :delay="120" />
    <x-stat label="Ruang Kelas" :value="$organization->classrooms()->count()" icon="building" tone="ok" :delay="180" />
</div>

<div class="grid grid--main">
    <div class="stack" style="--gap:18px">
        {{-- Struktur per jenjang --}}
        <x-card title="Struktur Sekolah" icon="layers"
                subtitle="Jurusan dan kelas yang tersedia di organisasi ini" :delay="0">
            @forelse ($summary as $row)
                <div class="level-block">
                    <div class="level-block__head">
                        <span class="badge tone-{{ $row['level']->tone() }}">{{ $row['level']->label() }}</span>
                        <span class="table__sub" style="margin:0">
                            {{ count($row['programs']) }} jurusan · {{ $row['class_count'] }} kelas
                        </span>
                    </div>

                    @foreach ($row['programs'] as $program)
                        <div class="level-block__program">
                            <b>{{ $program->name }}</b>
                            <span class="tiny dim">{{ $program->code }}</span>
                        </div>

                        <div class="class-chips">
                            @forelse ($program->schoolClasses->sortBy('name') as $class)
                                <a class="class-chip"
                                   href="{{ route('admin.users.index', ['school_class_id' => $class->id]) }}"
                                   title="Lihat siswa kelas {{ $class->name }}">
                                    <b>{{ $class->name }}</b>
                                    <span>{{ $class->students_count ?? 0 }}/{{ $class->capacity }}</span>
                                </a>
                            @empty
                                <span class="tiny dim">Belum ada kelas.</span>
                            @endforelse
                        </div>
                    @endforeach
                </div>
            @empty
                <x-empty icon="layers" title="Belum ada struktur sekolah"
                         text="Jurusan dan kelas belum dibuat untuk organisasi ini." />
            @endforelse
        </x-card>
    </div>

    <div class="stack" style="--gap:18px">
        {{-- Ruang kelas --}}
        <x-card title="Ruang Kelas" icon="building" :delay="0">
            <div class="list">
                @forelse ($organization->classrooms()->orderBy('name')->get() as $room)
                    <div class="list__row">
                        <div class="list__main">
                            <b>{{ $room->name }}</b>
                            <span>Kapasitas {{ $room->capacity }} orang</span>
                        </div>
                        <div class="list__side">
                            <span class="badge tone-muted">{{ $room->capacity }} kursi</span>
                        </div>
                    </div>
                @empty
                    <x-empty icon="building" title="Belum ada ruang kelas" />
                @endforelse
            </div>
        </x-card>

        <x-card title="Status" icon="info" :delay="60">
            <dl class="kv">
                <div class="kv__row">
                    <dt>Kode</dt>
                    <dd>{{ $organization->code }}</dd>
                </div>
                <div class="kv__row">
                    <dt>Status</dt>
                    <dd><x-status :status="$organization->status" /></dd>
                </div>
                <div class="kv__row">
                    <dt>Dibuat</dt>
                    <dd>{{ $organization->created_at->locale('id')->translatedFormat('d M Y') }}</dd>
                </div>
            </dl>
        </x-card>
    </div>
</div>
@endsection
