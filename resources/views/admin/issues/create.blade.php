{{-- resources/views/admin/issues/create.blade.php — Form pelaporan kerusakan aset --}}
@extends('layouts.app')
@section('title', 'Lapor Kerusakan')
@section('chrome', auth()->user()->primaryRole() === 'technician' ? 'Issue Aset' : 'Issue')

@section('content')
@php
    $types = \App\Enums\IssueType::cases();
    $severities = \App\Enums\IssueSeverity::cases();
@endphp

<x-page-head :back="route('admin.issues.index')" title="Lapor Kerusakan Aset"
             subtitle="Catat kondisi unit yang tidak sesuai agar bisa diselidiki dan diperbaiki." />

<x-card title="Data Laporan" icon="clipboard" tint>
    <x-slot:actions>
        <span class="badge tone-muted">Status awal: Terbuka</span>
    </x-slot:actions>

    <div class="stack" style="--gap:16px">
        <div class="inline-alert tone-brand">
            <x-icon name="info" />
            <div>Laporan ini <b>tidak</b> mengubah status aset. Untuk perbaikan, buat tiket maintenance dari halaman
                detail issue — unit otomatis terkunci dari peminjaman sampai pekerjaan selesai.</div>
        </div>

        <form method="POST" action="{{ route('admin.issues.store') }}" class="form">
            @csrf

            <div class="form-grid">
                <x-field name="asset_id" label="Unit Aset" required
                         hint="Gunakan kode unit yang tercetak pada QR aset.">
                    <x-slot:control>
                        <select name="asset_id" id="f-asset_id" required>
                            <option value="">— pilih unit —</option>
                            @foreach ($assets as $a)
                                <option value="{{ $a->id }}" @selected(old('asset_id', $selectedAssetId) == $a->id)>
                                    {{ $a->asset_code }}
                                </option>
                            @endforeach
                        </select>
                    </x-slot:control>
                </x-field>

                <x-field name="type" label="Jenis Masalah" required>
                    <x-slot:control>
                        <select name="type" id="f-type" required>
                            @foreach ($types as $t)
                                <option value="{{ $t->value }}" @selected(old('type') === $t->value)>{{ $t->label() }}</option>
                            @endforeach
                        </select>
                    </x-slot:control>
                </x-field>

                <x-field name="severity" label="Tingkat Keparahan" required
                         hint="Kritis = unit tidak bisa dipakai sama sekali.">
                    <x-slot:control>
                        <select name="severity" id="f-severity" required>
                            @foreach ($severities as $s)
                                <option value="{{ $s->value }}" @selected(old('severity', 'medium') === $s->value)>{{ $s->label() }}</option>
                            @endforeach
                        </select>
                    </x-slot:control>
                </x-field>
            </div>

            <div class="form-grid">
                <x-field name="description" label="Deskripsi Masalah" required
                         hint="Tuliskan gejala, kapan terjadi, dan kondisi yang diamati. Minimal 10 karakter.">
                    <x-slot:control>
                        <textarea name="description" id="f-description" rows="4" required
                                  placeholder="Contoh: proyektor menyala lalu mati setelah 5 menit, kabelnya terkelupas.">{{ old('description') }}</textarea>
                    </x-slot:control>
                </x-field>
            </div>

            <div class="card__foot">
                <div class="btn-row btn-row--end">
                    <x-btn :href="route('admin.issues.index')" variant="ghost">Batal</x-btn>
                    <button type="submit" class="btn btn--primary"><x-icon name="send" /> Kirim Laporan</button>
                </div>
            </div>
        </form>
    </div>
</x-card>
@endsection
