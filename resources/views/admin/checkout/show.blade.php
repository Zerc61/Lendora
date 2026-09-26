{{-- resources/views/admin/checkout/show.blade.php --}}
@extends('layouts.app')
@section('title', 'Proses Check-out')
@section('content')
<h1>Check-out: {{ $borrowing->code }}</h1>
<div class="card">
    <table>
        <tr><th style="width:35%">Peminjam</th><td>{{ $borrowing->borrower->name }} ({{ $borrowing->borrower->email }})</td></tr>
        <tr><th>Tujuan</th><td>{{ $borrowing->purpose }}</td></tr>
        <tr><th>Batas Kembali</th><td>{{ $borrowing->due_at?->format('d M Y H:i') ?? '—' }}</td></tr>
    </table>
    <p class="muted" style="font-size:13px">Verifikasi identitas peminjam & unit fisik, lalu catat kondisi awal setiap unit (PDF 4H).</p>
</div>
<div class="card">
    <form method="POST" action="{{ route('admin.checkout.store', $borrowing) }}">
        @csrf
        <table>
            <tr><th>Unit</th><th style="width:180px">Kondisi Awal</th><th>Catatan Kondisi</th></tr>
            @foreach($borrowing->items as $item)
            <tr>
                <td><strong>{{ $item->asset->asset_code }}</strong><br><small class="muted">{{ $item->asset->assetType->name }}</small></td>
                <td>
                    <select name="items[{{ $item->id }}][condition_out]" required>
                        @foreach(['excellent','good','fair','poor','broken'] as $c)
                        <option value="{{ $c }}" {{ $item->asset->condition->value === $c ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </td>
                <td><input name="items[{{ $item->id }}][notes_out]" placeholder="opsional"></td>
            </tr>
            @endforeach
        </table>
        <button style="margin-top:10px">📤 Serahkan & Set Status Dipinjam</button>
        <a href="{{ route('admin.checkout.index') }}" style="margin-left:8px">Batal</a>
    </form>
</div>
@endsection
