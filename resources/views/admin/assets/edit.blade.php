{{-- resources/views/admin/assets/edit.blade.php --}}
@extends('layouts.app')
@section('title', 'Edit Aset')
@section('content')
<h1>Edit Aset: {{ $asset->asset_code }}</h1>
<div class="card" style="max-width:560px">
    <form method="POST" action="{{ route('admin.assets.update', $asset) }}">
        @csrf @method('PUT')
        <label>Tipe Aset</label>
        <select name="asset_type_id" required>
            @foreach($typesGrouped as $catName => $types)
            <optgroup label="{{ $catName }}">
                @foreach($types as $t)
                <option value="{{ $t->id }}" {{ old('asset_type_id', $asset->asset_type_id) == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                @endforeach
            </optgroup>
            @endforeach
        </select>

        <label>Asset Code (unik)</label>
        <input name="asset_code" value="{{ old('asset_code', $asset->asset_code) }}" required>

        <label>Serial Number</label>
        <input name="serial_number" value="{{ old('serial_number', $asset->serial_number) }}">

        <div class="row">
            <div style="flex:1">
                <label>Status <span class="muted">(transisi divalidasi state machine)</span></label>
                <select name="status" required>
                    @foreach($allStatuses as $s)
                    <option value="{{ $s->value }}" {{ old('status', $asset->status->value) === $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div style="flex:1">
                <label>Kondisi</label>
                <select name="condition" required>
                    @foreach($conditions as $c)
                    <option value="{{ $c->value }}" {{ old('condition', $asset->condition->value) === $c->value ? 'selected' : '' }}>{{ $c->value }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <label>Lokasi</label>
        <select name="location_id">
            <option value="">— tidak ditentukan —</option>
            @foreach($locations as $loc)
            <option value="{{ $loc->id }}" {{ old('location_id', $asset->location_id) == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
            @endforeach
        </select>

        <div class="row">
            <div style="flex:1">
                <label>Tanggal Beli</label>
                <input type="date" name="purchase_date" value="{{ old('purchase_date', $asset->purchase_date?->format('Y-m-d')) }}">
            </div>
            <div style="flex:1">
                <label>Harga (Rp)</label>
                <input type="number" step="0.01" min="0" name="purchase_price" value="{{ old('purchase_price', $asset->purchase_price) }}">
            </div>
            <div style="flex:1">
                <label>Garansi s/d</label>
                <input type="date" name="warranty_until" value="{{ old('warranty_until', $asset->warranty_until?->format('Y-m-d')) }}">
            </div>
        </div>

        <label>Catatan</label>
        <textarea name="notes" rows="3">{{ old('notes', $asset->notes) }}</textarea>
        <button>Update</button>
        <a href="{{ route('admin.assets.show', $asset) }}" style="margin-left:8px">Batal</a>
    </form>
</div>
@endsection
