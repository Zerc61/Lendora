{{-- resources/views/admin/tickets/create.blade.php --}}
@extends('layouts.app')
@section('title', 'Buat Tiket Maintenance')
@section('content')
<h1>Buat Tiket Maintenance</h1>
<div class="card" style="max-width:560px">
    <p class="muted" style="font-size:13px">Tiket hanya dapat dibuat untuk unit berstatus <strong>Tersedia</strong> atau <strong>Rusak</strong>. Unit otomatis dikunci dari peminjaman selama maintenance (PDF 4J).</p>
    <form method="POST" action="{{ route('admin.tickets.store') }}">
        @csrf
        <label>Unit Aset</label>
        <select name="asset_id" required>
            <option value="">— pilih unit —</option>
            @foreach($assets as $a)
            <option value="{{ $a->id }}" {{ old('asset_id', $selectedAssetId) == $a->id ? 'selected' : '' }}>
                {{ $a->asset_code }} — {{ $a->assetType->name }} [{{ $a->status->value }}]
            </option>
            @endforeach
        </select>

        <label>Terhubung ke Issue (opsional)</label>
        <select name="issue_id">
            <option value="">— tidak terhubung issue —</option>
            @foreach($issues as $i)
            <option value="{{ $i->id }}" {{ old('issue_id', $selectedIssueId) == $i->id ? 'selected' : '' }}>
                {{ $i->code }} — {{ Str::limit($i->description, 40) }}
            </option>
            @endforeach
        </select>

        <div class="row">
            <div style="flex:1">
                <label>Jenis Pekerjaan</label>
                <select name="type">
                    @foreach($types as $t)
                    <option value="{{ $t->value }}" {{ old('type', 'corrective') === $t->value ? 'selected' : '' }}>{{ $t->value }}</option>
                    @endforeach
                </select>
            </div>
            <div style="flex:1">
                <label>Prioritas</label>
                <select name="priority">
                    @foreach($priorities as $p)
                    <option value="{{ $p->value }}" {{ old('priority', 'medium') === $p->value ? 'selected' : '' }}>{{ $p->value }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <label>Deskripsi Masalah / Rencana Kerja (min. 10 karakter)</label>
        <textarea name="description" rows="4" required>{{ old('description') }}</textarea>

        <label>Jadwal (opsional)</label>
        <input type="date" name="scheduled_at" value="{{ old('scheduled_at') }}">

        <button>Simpan Tiket</button>
        <a href="{{ route('admin.tickets.index') }}" style="margin-left:8px">Batal</a>
    </form>
</div>
@endsection
