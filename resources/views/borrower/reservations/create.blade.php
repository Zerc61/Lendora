{{-- resources/views/borrower/reservations/create.blade.php --}}
@extends('layouts.app')
@section('title', 'Ajukan Reservasi')
@section('content')
<h1>Ajukan Reservasi</h1>
<div class="card" style="max-width:720px">
    <form method="POST" action="{{ route('my.reservations.store') }}">
        @csrf
        <div class="row">
            <div style="flex:1">
                <label>Mulai</label>
                <input type="datetime-local" name="start_at" value="{{ old('start_at') }}" required>
            </div>
            <div style="flex:1">
                <label>Selesai</label>
                <input type="datetime-local" name="end_at" value="{{ old('end_at') }}" required>
            </div>
        </div>
        <label>Tujuan Peminjaman (min. 10 karakter)</label>
        <textarea name="purpose" rows="2" required>{{ old('purpose') }}</textarea>
        <label>Pilih Unit Aset (hanya yang tersedia)</label>
        <table>
            <tr><th></th><th>Kode</th><th>Tipe</th><th>Kondisi</th><th>Lokasi</th></tr>
            @forelse($assets as $a)
            <tr>
                <td><input type="checkbox" name="asset_ids[]" value="{{ $a->id }}" style="width:auto"
                    {{ in_array($a->id, old('asset_ids', [])) ? 'checked' : '' }}></td>
                <td><strong>{{ $a->asset_code }}</strong></td>
                <td>{{ $a->assetType->name }}</td>
                <td>{{ $a->condition->value }}</td>
                <td>{{ $a->location?->name ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="muted">Tidak ada aset tersedia saat ini.</td></tr>
            @endforelse
        </table>
        <p class="muted" style="font-size:13px">Sistem otomatis menolak jika unit sudah direservasi pada rentang waktu yang sama.</p>
        <button> Ajukan Reservasi</button>
        <a href="{{ route('my.reservations.index') }}" style="margin-left:8px">Batal</a>
    </form>
</div>
@endsection
