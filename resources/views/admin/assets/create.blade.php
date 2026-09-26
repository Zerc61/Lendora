{{-- resources/views/admin/assets/create.blade.php --}}
@extends('layouts.app')
@section('title', 'Tambah Aset')
@section('content')
<h1>Tambah Aset</h1>
<div class="card" style="max-width:560px">
    <form method="POST" action="{{ route('admin.assets.store') }}">
        @csrf
        <label>Tipe Aset</label>
        <select name="asset_type_id" required>
            <option value="">— pilih tipe —</option>
            @foreach($typesGrouped as $catName => $types)
            <optgroup label="{{ $catName }}">
                @foreach($types as $t)
                <option value="{{ $t->id }}" {{ old('asset_type_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                @endforeach
            </optgroup>
            @endforeach
        </select>

        <label>Asset Code (kosongkan = otomatis)</label>
        <input name="asset_code" value="{{ old('asset_code') }}" placeholder="LND-XXX-0001">

        <label>Serial Number</label>
        <input name="serial_number" value="{{ old('serial_number') }}">

        <div class="row">
            <div style="flex:1">
                <label>Status Awal</label>
                <select name="status" required>
                    @foreach($initialStatuses as $s)
                    <option value="{{ $s->value }}" {{ old('status') === $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div style="flex:1">
                <label>Kondisi</label>
                <select name="condition" required>
                    @foreach($conditions as $c)
                    <option value="{{ $c->value }}" {{ old('condition') === $c->value ? 'selected' : '' }}>{{ $c->value }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <label>Lokasi</label>
        <select name="location_id">
            <option value="">— tidak ditentukan —</option>
            @foreach($locations as $loc)
            <option value="{{ $loc->id }}" {{ old('location_id') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
            @endforeach
        </select>

        <div class="row">
            <div style="flex:1">
                <label>Tanggal Beli</label>
                <input type="date" name="purchase_date" value="{{ old('purchase_date') }}">
            </div>
            <div style="flex:1">
                <label>Harga (Rp)</label>
                <input type="number" step="0.01" min="0" name="purchase_price" value="{{ old('purchase_price') }}">
            </div>
            <div style="flex:1">
                <label>Garansi s/d</label>
                <input type="date" name="warranty_until" value="{{ old('warranty_until') }}">
            </div>
        </div>

        <label>Catatan</label>
        <textarea name="notes" rows="3">{{ old('notes') }}</textarea>
        <button>Simpan</button>
        <a href="{{ route('admin.assets.index') }}" style="margin-left:8px">Batal</a>
    </form>
</div>
@endsection
