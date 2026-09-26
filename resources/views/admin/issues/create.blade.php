{{-- resources/views/admin/issues/create.blade.php --}}
@extends('layouts.app')
@section('title', 'Laporkan Masalah')
@section('content')
<h1>Laporkan Masalah Aset</h1>
<div class="card" style="max-width:520px">
    <form method="POST" action="{{ route('admin.issues.store') }}">
        @csrf
        <label>Unit Aset</label>
        <select name="asset_id" required>
            <option value="">— pilih unit —</option>
            @foreach($assets as $a)
            <option value="{{ $a->id }}" {{ old('asset_id', $selectedAssetId) == $a->id ? 'selected' : '' }}>{{ $a->asset_code }}</option>
            @endforeach
        </select>
        <label>Jenis Masalah</label>
        <select name="type">
            <option value="damage" {{ old('type') === 'damage' ? 'selected' : '' }}>Kerusakan</option>
            <option value="loss" {{ old('type') === 'loss' ? 'selected' : '' }}>Kehilangan</option>
            <option value="other" {{ old('type') === 'other' ? 'selected' : '' }}>Lainnya</option>
        </select>
        <label>Tingkat</label>
        <select name="severity">
            @foreach(['low','medium','high','critical'] as $s)
            <option value="{{ $s }}" {{ old('severity', 'medium') === $s ? 'selected' : '' }}>{{ $s }}</option>
            @endforeach
        </select>
        <label>Deskripsi (min. 10 karakter)</label>
        <textarea name="description" rows="4" required>{{ old('description') }}</textarea>
        <button>Kirim Laporan</button>
        <a href="{{ route('dashboard') }}" style="margin-left:8px">Batal</a>
    </form>
</div>
@endsection
