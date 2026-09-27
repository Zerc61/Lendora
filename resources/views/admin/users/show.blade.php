{{-- resources/views/admin/users/show.blade.php — Detail pengguna / siswa
     Untuk borrower menampilkan foto, identitas, kelas, dan riwayat. --}}
@extends('layouts.app')
@section('title', $user->name)
@section('chrome', 'Detail Pengguna')

@section('content')
<x-page-head :title="$user->name"
             :subtitle="$user->roleLabel().' · '.($user->organization?->name ?? 'Tanpa organisasi')"
             :crumbs="['Pengguna' => route('admin.users.index'), \Illuminate\Support\Str::limit($user->name, 24) => null]">
    @can('update', $user)
        <x-btn :href="route('admin.users.edit', $user)" variant="ghost" icon="edit">Ubah</x-btn>
    @endcan
</x-page-head>
<div class="grid grid--main">
    <div class="stack" style="--gap:18px">

        {{-- Identitas --}}
        <x-card title="Identitas" icon="user" :delay="0">
            <div class="cell-media profile-head">
                <span class="avatar avatar--xl {{ $user->hasPhoto() ? '' : 'avatar--plain' }}">
                    @if ($user->hasPhoto())
                        <img src="{{ $user->photoUrl() }}" alt="Foto {{ $user->name }}">
                    @else
                        {{ $user->initials() }}
                    @endif
                </span>
                <div class="cell-media__body">
                    <b class="truncate">{{ $user->name }}</b>
                    <span class="truncate">{{ $user->email }}</span>
                    <div class="btn-row" style="margin-top:6px">
                        @foreach ($user->roles as $role)
                            <span class="badge tone-{{ $role->name === 'borrower' ? 'brand' : 'info' }}">{{ $role->name }}</span>
                        @endforeach
                        <x-status :status="$user->status" />
                    </div>
                </div>
            </div>

            <dl class="kv" style="margin-top:16px">
                <div class="kv__row">
                    <dt>Nomor identitas</dt>
                    <dd>{{ $user->identity_number ?: '—' }}</dd>
                </div>
                <div class="kv__row">
                    <dt>Jenis kelamin</dt>
                    <dd>{{ $user->gender?->label() ?: '—' }}</dd>
                </div>
                <div class="kv__row">
                    <dt>Tanggal lahir</dt>
                    <dd>{{ $user->birth_date?->locale('id')->translatedFormat('d M Y') ?: '—' }}</dd>
                </div>
                <div class="kv__row">
                    <dt>Telepon</dt>
                    <dd>{{ $user->phone ?: '—' }}</dd>
                </div>
                <div class="kv__row">
                    <dt>Alamat</dt>
                    <dd>{{ $user->address ?: '—' }}</dd>
                </div>
                <div class="kv__row">
                    <dt>Bergabung</dt>
                    <dd>{{ $user->created_at->locale('id')->translatedFormat('d M Y') }}</dd>
                </div>
            </dl>

            @if ($user->bio)
                <div class="inline-alert tone-muted" style="margin-top:14px">
                    <x-icon name="info" />
                    <div>
                        <b>Deskripsi</b>
                        <p>{{ $user->bio }}</p>
                    </div>
                </div>
            @endif
        </x-card>

        {{-- Riwayat transaksi --}}
        <x-card title="Riwayat Peminjaman" icon="bag" subtitle="Transaksi yang pernah dibuat pengguna ini" :delay="60">
            <div class="list">
                @forelse ($user->borrowings->sortByDesc('created_at')->take(10) as $borrowing)
                    <div class="list__row">
                        <div class="list__main">
                            <b>{{ $borrowing->code }}</b>
                            <span>{{ $borrowing->created_at->locale('id')->translatedFormat('d M Y') }} · {{ $borrowing->items->count() }} unit</span>
                        </div>
                        <div class="list__side">
                            <x-status :status="$borrowing->status" />
                        </div>
                    </div>
                @empty
                    <div class="empty">
                        <x-icon name="bag" class="empty__icon" />
                        <b>Belum ada peminjaman</b>
                        <p>Pengguna ini belum pernah meminjam aset.</p>
                    </div>
                @endforelse
            </div>
        </x-card>
    </div>

    {{-- Kolom kanan: info sekolah --}}
    <div class="stack" style="--gap:18px">
        <x-card title="Sekolah & Kelas" icon="building" :delay="0">
            @if ($user->schoolClass)
                <div class="stack" style="--gap:12px">
                    <div>
                        <span class="badge tone-brand">{{ $user->schoolClass->program->education_level->label() }}</span>
                        <span class="badge tone-accent">{{ $user->schoolClass->program->name }}</span>
                    </div>
                    <dl class="kv">
                        <div class="kv__row">
                            <dt>Kelas</dt>
                            <dd>{{ $user->schoolClass->name }}</dd>
                        </div>
                        <div class="kv__row">
                            <dt>Tahun ajaran</dt>
                            <dd>{{ $user->schoolClass->school_year }}</dd>
                        </div>
                        <div class="kv__row">
                            <dt>Organisasi</dt>
                            <dd>{{ $user->organization?->name ?? '—' }}</dd>
                        </div>
                        @if ($user->schoolClass->homeroomTeacher)
                            <div class="kv__row">
                                <dt>Wali kelas</dt>
                                <dd>{{ $user->schoolClass->homeroomTeacher->name }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            @else
                <x-empty icon="building" title="Bukan siswa"
                         text="Pengguna ini tidak terdaftar pada kelas mana pun." />
            @endif
        </x-card>

        <x-card title="Ringkasan Reservasi" icon="calendar" :delay="60">
            <div class="grid grid--pair">
                <x-stat label="Total" :value="$user->reservations->count()" icon="clipboard" :delay="0" />
                <x-stat label="Aktif" :value="$user->borrowings->whereIn('status', ['borrowed', 'overdue'])->count()"
                        icon="clock" tone="warn" :delay="60" />
            </div>
        </x-card>
    </div>
</div>
@endsection
