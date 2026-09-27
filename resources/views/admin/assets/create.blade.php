{{-- resources/views/admin/assets/create.blade.php — Formulir tambah aset --}}
@extends('layouts.app')
@section('title', 'Tambah Aset')
@section('chrome', 'Tambah Aset')

@section('content')
<x-page-head :back="route('admin.assets.index')" title="Tambah Aset"
             subtitle="Kode aset boleh dikosongkan — sistem akan membuatnya otomatis dari tipe unit yang dipilih." />

<form method="POST" action="{{ route('admin.assets.store') }}">
    @csrf
    <section class="card">
        <div class="form">
            <div class="divider--label">Identitas Unit</div>
            <div class="form-grid">
                <x-field name="asset_type_id" label="Tipe Aset" required
                         hint="Menentukan kategori dan kode otomatis.">
                    <x-slot:control>
                        <select id="f-asset_type_id" name="asset_type_id" required>
                            <option value="">— pilih tipe —</option>
                            @foreach ($typesGrouped as $catName => $types)
                                <optgroup label="{{ $catName }}">
                                    @foreach ($types as $t)
                                        <option value="{{ $t->id }}" @selected(old('asset_type_id') == $t->id)>{{ $t->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </x-slot:control>
                </x-field>

                <x-field name="asset_code" label="Kode Aset" hint="Format: LND-KAT-0001. Kosongkan untuk生成 otomatis.">
                    <x-slot:control>
                        <input id="f-asset_code" name="asset_code" value="{{ old('asset_code') }}"
                               placeholder="LND-LAP-0001" autocomplete="off">
                    </x-slot:control>
                </x-field>

                <x-field name="serial_number" label="Serial Number" hint="Nomor seri(unit pabrikan). Harus unik bila diisi.">
                    <x-slot:control>
                        <input id="f-serial_number" name="serial_number" value="{{ old('serial_number') }}" autocomplete="off">
                    </x-slot:control>
                </x-field>
            </div>

            <div class="divider--label">Kondisi & Lokasi</div>
            <div class="form-grid">
                <x-field name="status" label="Status Awal" required
                         hint="Status Direservasi & Dipinjam hanya lewat workflow peminjaman.">
                    <x-slot:control>
                        <select id="f-status" name="status" required>
                            @foreach ($initialStatuses as $s)
                                <option value="{{ $s->value }}" @selected(old('status') === $s->value)>{{ $s->label() }}</option>
                            @endforeach
                        </select>
                    </x-slot:control>
                </x-field>

                <x-field name="condition" label="Kondisi Fisik" required>
                    <x-slot:control>
                        <select id="f-condition" name="condition" required>
                            @foreach ($conditions as $c)
                                <option value="{{ $c->value }}" @selected(old('condition') === $c->value)>{{ $c->label() }}</option>
                            @endforeach
                        </select>
                    </x-slot:control>
                </x-field>

                <x-field name="location_id" label="Lokasi Penyimpanan">
                    <x-slot:control>
                        <select id="f-location_id" name="location_id">
                            <option value="">— tidak ditentukan —</option>
                            @foreach ($locations as $loc)
                                <option value="{{ $loc->id }}" @selected(old('location_id') == $loc->id)>{{ $loc->name }}</option>
                            @endforeach
                        </select>
                    </x-slot:control>
                </x-field>
            </div>

            <div class="divider--label">Pembelian</div>
            <div class="form-grid">
                <x-field name="purchase_date" label="Tanggal Beli">
                    <x-slot:control>
                        <input id="f-purchase_date" type="date" name="purchase_date" value="{{ old('purchase_date') }}">
                    </x-slot:control>
                </x-field>

                <x-field name="purchase_price" label="Harga (Rp)" hint="Tanpa titik ribuan.">
                    <x-slot:control>
                        <input id="f-purchase_price" type="number" step="0.01" min="0" name="purchase_price"
                               value="{{ old('purchase_price') }}">
                    </x-slot:control>
                </x-field>

                <x-field name="warranty_until" label="Garansi s/d">
                    <x-slot:control>
                        <input id="f-warranty_until" type="date" name="warranty_until" value="{{ old('warranty_until') }}">
                    </x-slot:control>
                </x-field>
            </div>

            <x-field name="notes" label="Catatan" hint="Keterangan tambahan(unit ini dipakai untuk apa, aksesori, dan sejenisnya).">
                <x-slot:control>
                    <textarea id="f-notes" name="notes" rows="3" placeholder="Contoh: proyektor portable untuk rapatExternal.">{{ old('notes') }}</textarea>
                </x-slot:control>
            </x-field>
        </div>

        <div class="card__foot btn-row btn-row--end">
            <x-btn :href="route('admin.assets.index')" variant="ghost">Batal</x-btn>
            <button type="submit" class="btn btn--primary"><x-icon name="check" /> Simpan Aset</button>
        </div>
    </section>
</form>
@endsection
