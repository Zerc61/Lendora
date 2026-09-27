{{-- resources/views/admin/tickets/create.blade.php — Form pembuatan tiket maintenance --}}
@extends('layouts.app')
@section('title', 'Buat Tiket Maintenance')
@section('chrome', auth()->user()->primaryRole() === 'technician' ? 'Tiket Saya' : 'Maintenance')

@section('content')
<x-page-head :back="route('admin.tickets.index')" title="Buat Tiket Maintenance"
             subtitle="Tiket mengunci unit dari peminjaman sampai kondisi akhir diverifikasi." />

<x-card title="Detail Pekerjaan" icon="wrench" tint>
    <x-slot:actions>
        <span class="badge tone-warn">Status awal: Dibuka</span>
    </x-slot:actions>

    <div class="stack" style="--gap:16px">
        <div class="inline-alert tone-warn">
            <x-icon name="alert" />
            <div>Unit yang dipilih otomatis berstatus <b>Maintenance</b> dan tidak bisa dipinjam sampai tiket
                diverifikasi atau dibatalkan.</div>
        </div>

        <form method="POST" action="{{ route('admin.tickets.store') }}" class="form">
            @csrf

            <div class="form-grid">
                <x-field name="asset_id" label="Unit Aset" required
                         hint="Tersedia {{ $assets->count() }} unit berstatus Tersedia atau Rusak.">
                    <x-slot:control>
                        <select name="asset_id" id="f-asset_id" required>
                            <option value="">— pilih unit —</option>
                            @foreach ($assets as $a)
                                <option value="{{ $a->id }}" @selected(old('asset_id', $selectedAssetId) == $a->id)>
                                    {{ $a->asset_code }} — {{ $a->assetType->name }} ({{ $a->status->label() }})
                                </option>
                            @endforeach
                        </select>
                    </x-slot:control>
                </x-field>

                <x-field name="issue_id" label="Issue Terkait" hint="Opsional — hubungkan ke laporan kerusakan yang belum punya tiket.">
                    <x-slot:control>
                        <select name="issue_id" id="f-issue_id">
                            <option value="">— tidak terhubung issue —</option>
                            @foreach ($issues as $i)
                                <option value="{{ $i->id }}" @selected(old('issue_id', $selectedIssueId) == $i->id)>
                                    {{ $i->code }} — {{ $i->asset?->asset_code }} · {{ Str::limit($i->description, 40) }}
                                </option>
                            @endforeach
                        </select>
                    </x-slot:control>
                </x-field>
            </div>

            <div class="form-grid">
                <x-field name="type" label="Jenis Pekerjaan" required>
                    <x-slot:control>
                        <select name="type" id="f-type" required>
                            @foreach ($types as $t)
                                <option value="{{ $t->value }}" @selected(old('type', 'corrective') === $t->value)>{{ $t->label() }}</option>
                            @endforeach
                        </select>
                    </x-slot:control>
                </x-field>

                <x-field name="priority" label="Prioritas" required>
                    <x-slot:control>
                        <select name="priority" id="f-priority" required>
                            @foreach ($priorities as $p)
                                <option value="{{ $p->value }}" @selected(old('priority', 'medium') === $p->value)>{{ $p->label() }}</option>
                            @endforeach
                        </select>
                    </x-slot:control>
                </x-field>
            </div>

            <div class="form-grid">
                <x-field name="description" label="Rencana Kerja" required
                         hint="Tuliskan pekerjaan yang akan dilakukan dan gejala yang diamati. Minimal 10 karakter.">
                    <x-slot:control>
                        <textarea name="description" id="f-description" rows="4" required
                                  placeholder="Contoh: ganti modul laser proyektor dan uji selama 30 menit.">{{ old('description') }}</textarea>
                    </x-slot:control>
                </x-field>
            </div>

            <div class="form-grid">
                <x-field name="scheduled_at" label="Jadwal Pengerjaan" hint="Opsional — tidak boleh lebih awal dari hari ini.">
                    <x-slot:control>
                        <input type="date" name="scheduled_at" id="f-scheduled_at" value="{{ old('scheduled_at') }}">
                    </x-slot:control>
                </x-field>
            </div>

            <div class="card__foot">
                <div class="btn-row btn-row--end">
                    <x-btn :href="route('admin.tickets.index')" variant="ghost">Batal</x-btn>
                    <button type="submit" class="btn btn--primary"><x-icon name="wrench" /> Simpan Tiket</button>
                </div>
            </div>
        </form>
    </div>
</x-card>
@endsection
