{{-- resources/views/admin/checkin/show.blade.php --}}
@extends('layouts.app')
@section('title', 'Proses Check-in')
@section('content')
<h1>Check-in: {{ $borrowing->code }}</h1>
<div class="card">
    <table>
        <tr><th style="width:35%">Peminjam</th><td>{{ $borrowing->borrower->name }}</td></tr>
        <tr><th>Check-out</th><td>{{ $borrowing->checked_out_at?->format('d M Y H:i') ?? '—' }}</td></tr>
        <tr><th>Batas Kembali</th><td>{{ $borrowing->due_at?->format('d M Y H:i') ?? '—' }}
            @if($borrowing->isOverdue())<span class="badge b-overdue">Terlambat</span>@endif</td></tr>
    </table>
    <p class="muted" style="font-size:13px">Periksa kondisi setiap unit. Jika ditemukan rusak/hilang, sistem otomatis membuat Issue & mengeluarkan unit dari availability (PDF 4I, 5.3, 5.4).</p>
</div>
<div class="card">
    <form method="POST" action="{{ route('admin.checkin.store', $borrowing) }}">
        @csrf
        <table>
            <tr><th>Unit</th><th style="width:150px">Kondisi Akhir</th><th style="width:150px">Hasil</th><th>Catatan (wajib jika rusak/hilang)</th></tr>
            @foreach($borrowing->items as $item)
            <tr>
                <td>
                    <strong>{{ $item->asset->asset_code }}</strong><br>
                    <small class="muted">Kondisi keluar: {{ $item->condition_out?->value ?? '—' }}</small>
                </td>
                <td>
                    <select name="items[{{ $item->id }}][condition_in]" required>
                        @foreach(['excellent','good','fair','poor','broken'] as $c)
                        <option value="{{ $c }}">{{ $c }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <select name="items[{{ $item->id }}][outcome]" required>
                        <option value="ok">OK</option>
                        <option value="damaged">Rusak</option>
                        <option value="lost">Hilang</option>
                    </select>
                </td>
                <td><input name="items[{{ $item->id }}][notes_in]"></td>
            </tr>
            @endforeach
        </table>
        <label>Catatan Umum Check-in (opsional)</label>
        <textarea name="checkin_notes" rows="2"></textarea>
        <button style="margin-top:10px">📥 Terima Pengembalian</button>
        <a href="{{ route('admin.checkin.index') }}" style="margin-left:8px">Batal</a>
    </form>
</div>
@endsection
