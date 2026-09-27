{{-- resources/views/admin/assets/edit.blade.php — Formulir ubah aset --}}
@extends('layouts.app')
@section('title', 'Edit Aset')
@section('chrome', 'Edit Aset')

@section('content')
<x-page-head :back="route('admin.assets.show', $asset)" :title="'Edit ' . $asset->asset_code"
             :subtitle="$asset->assetType->name . ' · ubah identitas, status, kondisi, dan data pembelian.'" />

<form method="POST" action="{{ route('admin.assets.update', $asset) }}">
    @csrf
    @method('PUT')
    <section class="card">
        <div class="form">
            <div class="divider--label">Identitas Unit</div>
            <div class="form-grid">
                <x-field name="asset_type_id" label="Tipe Aset" required>
                    <x-slot:control>
                        <select id="f-asset_type_id" name="asset_type_id" required>
                            @foreach ($typesGrouped as $catName => $types)
                                <optgroup label="{{ $catName }}">
                                    @foreach ($types as $t)
                                        <option value="{{ $t->id }}" @selected(old('asset_type_id', $asset->asset_type_id) == $t->id)>{{ $t->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </x-slot:control>
                </x-field>

                <x-field name="asset_code" label="Kode Aset" required hint="Kode harus unik antar unit.">
                    <x-slot:control>
                        <input id="f-asset_code" name="asset_code" value="{{ old('asset_code', $asset->asset_code) }}"
                               required autocomplete="off">
                    </x-slot:control>
                </x-field>

                <x-field name="serial_number" label="Serial Number" hint="Harus unik bila diisi.">
                    <x-slot:control>
                        <input id="f-serial_number" name="serial_number" value="{{ old('serial_number', $asset->serial_number) }}"
                               autocomplete="off">
                    </x-slot:control>
                </x-field>
            </div>

            <div class="divider--label">Kondisi & Lokasi</div>
            <div class="form-grid">
                <x-field name="status" label="Status" required
                         hint="Hanya transisi yang sah yang diterima — status saat ini: {{ $asset->status->label() }}.">
                    <x-slot:control>
                        <select id="f-status" name="status" required>
                            @foreach ($allStatuses as $s)
                                <option value="{{ $s->value }}" @selected(old('status', $asset->status->value) === $s->value)>{{ $s->label() }}</option>
                            @endforeach
                        </select>
                    </x-slot:control>
                </x-field>

                <x-field name="condition" label="Kondisi Fisik" required>
                    <x-slot:control>
                        <select id="f-condition" name="condition" required>
                            @foreach ($conditions as $c)
                                <option value="{{ $c->value }}" @selected(old('condition', $asset->condition->value) === $c->value)>{{ $c->label() }}</option>
                            @endforeach
                        </select>
                    </x-slot:control>
                </x-field>

                <x-field name="location_id" label="Lokasi Penyimpanan">
                    <x-slot:control>
                        <select id="f-location_id" name="location_id">
                            <option value="">— tidak ditentukan —</option>
                            @foreach ($locations as $loc)
                                <option value="{{ $loc->id }}" @selected(old('location_id', $asset->location_id) == $loc->id)>{{ $loc->name }}</option>
                            @endforeach
                        </select>
                    </x-slot:control>
                </x-field>
            </div>

            <div class="divider--label">Pembelian</div>
            <div class="form-grid">
                <x-field name="purchase_date" label="Tanggal Beli">
                    <x-slot:control>
                        <input id="f-purchase_date" type="date" name="purchase_date"
                               value="{{ old('purchase_date', $asset->purchase_date?->format('Y-m-d')) }}">
                    </x-slot:control>
                </x-field>

                <x-field name="purchase_price" label="Harga (Rp)" hint="Tanpa titik ribuan.">
                    <x-slot:control>
                        <input id="f-purchase_price" type="number" step="0.01" min="0" name="purchase_price"
                               value="{{ old('purchase_price', $asset->purchase_price) }}">
                    </x-slot:control>
                </x-field>

                <x-field name="warranty_until" label="Garansi s/d">
                    <x-slot:control>
                        <input id="f-warranty_until" type="date" name="warranty_until"
                               value="{{ old('warranty_until', $asset->warranty_until?->format('Y-m-d')) }}">
                    </x-slot:control>
                </x-field>
            </div>

            <x-field name="notes" label="Catatan" hint="Keterangan tambahan(unit ini dipakai untuk apa, aksesori, dan sejenisnya).">
                <x-slot:control>
                    <textarea id="f-notes" name="notes" rows="3">{{ old('notes', $asset->notes) }}</textarea>
                </x-slot:control>
            </x-field>
        </div>

        <div class="card__foot btn-row btn-row--end">
            <x-btn :href="route('admin.assets.show', $asset)" variant="ghost">Batal</x-btn>
            <button type="submit" class="btn btn--primary"><x-icon name="check" /> Simpan Perubahan</button>
        </div>
    </section>
</form>
@endsection
