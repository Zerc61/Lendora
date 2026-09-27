{{-- resources/views/borrower/reservations/create.blade.php — Form wizard satu halaman: unit → periode → tujuan --}}
@extends('layouts.app')
@section('title', 'Ajukan Reservasi')
@section('chrome', 'Reservasi')

@section('content')
@php
    $terpilih = old('asset_ids', request('asset') ? [(int) request('asset')] : []);
@endphp

<x-page-head :back="route('my.reservations.index')" title="Ajukan Reservasi"
             subtitle="Satu formulir: tentukan periode pakai, pilih unit, lalu jelaskan tujuan peminjaman.">
    <span class="badge tone-brand">{{ number_format($assets->count(), 0, ',', '.') }} unit tersedia</span>
</x-page-head>

{{-- Peta langkah, satu halaman --}}
<div class="card" style="margin-bottom:16px">
    <div class="steps">
        <span class="step is-current">
            <span class="step__dot">1</span>
            <span class="step__label">Tentukan periode</span>
        </span>
        <span class="step__line"></span>
        <span class="step">
            <span class="step__dot">2</span>
            <span class="step__label">Pilih unit</span>
        </span>
        <span class="step__line"></span>
        <span class="step">
            <span class="step__dot">3</span>
            <span class="step__label">Tulis tujuan</span>
        </span>
    </div>
</div>

<form method="POST" action="{{ route('my.reservations.store') }}" id="form-reservasi" class="form">
    @csrf

    {{-- Langkah 1 --}}
    <x-card title="Periode Peminjaman" icon="clock" :delay="0">
        <x-slot:actions>
            <span class="badge tone-muted">Waktu lokal</span>
        </x-slot:actions>

        <div class="form-grid">
            <x-field name="start_at" label="Mulai Dipakai" required
                     hint="Tidak boleh sebelum hari ini.">
                <x-slot:control>
                    <input type="datetime-local" id="f-start_at" name="start_at"
                           value="{{ old('start_at') }}" required>
                </x-slot:control>
            </x-field>

            <x-field name="end_at" label="Selesai Dipakai" required
                     hint="Harus setelah waktu mulai.">
                <x-slot:control>
                    <input type="datetime-local" id="f-end_at" name="end_at"
                           value="{{ old('end_at') }}" required>
                </x-slot:control>
            </x-field>
        </div>

        <div class="inline-alert tone-brand" style="margin-top:14px">
            <x-icon name="info" />
            <div>Sistem menolak otomatis bila unit yang dipilih <b>sudah direservasi</b> pada rentang waktu yang sama.</div>
        </div>
    </x-card>

    {{-- Langkah 2 --}}
    <x-card title="Pilih Unit Aset" icon="box" :delay="60"
            subtitle="Centang satu atau lebih unit yang Anda perlukan. Kondisi dan lokasi tercantum di setiap unit.">
        <x-slot:actions>
            @if ($assets->isNotEmpty())
                <span class="badge tone-ok">Tersedia</span>
            @endif
        </x-slot:actions>

        @if ($assets->isEmpty())
            <x-empty icon="box" title="Belum ada unit tersedia"
                     text="Semua unit sedang dipinjam, direservasi, atau masuk perbaikan. Cek lagi nanti atau hubungi admin.">
                <x-btn :href="route('my.reservations.index')" variant="ghost" size="sm">Kembali</x-btn>
            </x-empty>
        @else
            <div class="list">
                @foreach ($assets as $i => $a)
                    <label class="list__row list__row--link check" for="aset-{{ $a->id }}"
                           style="--d:{{ min($i, 6) * 40 }}ms">
                        <input type="checkbox" id="aset-{{ $a->id }}" name="asset_ids[]" value="{{ $a->id }}"
                               @checked(in_array($a->id, $terpilih))>
                        <span class="list__main">
                            <b>
                                <span class="table__code">{{ $a->asset_code }}</span>
                                · {{ $a->assetType?->name ?? 'Tanpa tipe' }}
                            </b>
                            <small>
                                {{ $a->assetType?->category?->name ?? '—' }}
                                · {{ $a->location?->name ?? 'Tanpa lokasi' }}
                            </small>
                        </span>
                        <span class="list__side">
                            <span class="btn-row btn-row--end">
                                @if ($a->condition)<x-status :status="$a->condition" />@else<span class="dim">—</span>@endif
                                @if ($a->status)<x-status :status="$a->status" />@endif
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>
            @error('asset_ids')<span class="field__error">{{ $message }}</span>@enderror
        @endif
    </x-card>

    {{-- Langkah 3 --}}
    <x-card title="Tujuan Peminjaman" icon="clipboard" :delay="120">
        <x-field name="purpose" label="Untuk kegiatan apa?" required
                 hint="Minimal 10 karakter. Sebutkan nama kegiatan atau lab agar persetujuan lebih cepat.">
            <x-slot:control>
                <textarea id="f-purpose" name="purpose" rows="3" required
                          placeholder="Contoh: Praktikum pemodelan data untuk 24 mahasiswa.">{{ old('purpose') }}</textarea>
            </x-slot:control>
        </x-field>
    </x-card>

    @if ($assets->isNotEmpty())
        <div class="card">
            <div class="btn-row btn-row--end">
                <x-btn :href="route('my.reservations.index')" variant="ghost">Batal</x-btn>
                <button type="submit" class="btn btn--primary btn--lg">
                    <x-icon name="send" /> Ajukan Reservasi
                </button>
            </div>
        </div>
    @endif
</form>

@if ($assets->isNotEmpty())
    {{-- Aksi tetap di layar kecil --}}
    <div class="actionbar">
        <button type="submit" form="form-reservasi" class="btn btn--primary"><x-icon name="send" /> Ajukan</button>
        <a class="btn btn--ghost" href="{{ route('my.reservations.index') }}">Batal</a>
    </div>
@endif
@endsection
