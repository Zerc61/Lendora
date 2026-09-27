{{-- resources/views/admin/tickets/edit.blade.php — Edit tiket maintenance --}}
@extends('layouts.app')
@section('title', 'Edit Tiket')
@section('chrome', auth()->user()->primaryRole() === 'technician' ? 'Tiket Saya' : 'Maintenance')

@section('content')
<x-page-head :back="route('admin.tickets.show', $ticket)" :title="'Edit Tiket '.$ticket->code"
             :subtitle="'Unit '.$ticket->asset->asset_code.' · dibuat '.$ticket->created_at->format('d M Y')">
    <x-status :status="$ticket->status" class="tone-{{ $ticket->status->tone() }}" />
    @if ($ticket->status->value !== 'open')
        <x-btn :href="route('admin.tickets.show', $ticket)" variant="ghost" icon="arrow-left">Kembali</x-btn>
    @endif
</x-page-head>

<x-card title="Detail Pekerjaan" icon="wrench" tint>
    <div class="stack" style="--gap:16px">
        @if ($ticket->status->value !== 'open')
            <div class="inline-alert tone-warn">
                <x-icon name="alert" />
                <div>Tiket hanya dapat disunting saat statusnya masih <b>Dibuka</b>. Status saat ini
                    <b>{{ $ticket->status->label() }}</b> — simpan akan ditolak sistem.</div>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.tickets.update', $ticket) }}" class="form">
            @csrf
            @method('PUT')

            <div class="form-grid">
                <x-field name="type" label="Jenis Pekerjaan" required>
                    <x-slot:control>
                        <select name="type" id="f-type" required>
                            @foreach ($types as $t)
                                <option value="{{ $t->value }}" @selected(old('type', $ticket->type->value) === $t->value)>{{ $t->label() }}</option>
                            @endforeach
                        </select>
                    </x-slot:control>
                </x-field>

                <x-field name="priority" label="Prioritas" required>
                    <x-slot:control>
                        <select name="priority" id="f-priority" required>
                            @foreach ($priorities as $p)
                                <option value="{{ $p->value }}" @selected(old('priority', $ticket->priority->value) === $p->value)>{{ $p->label() }}</option>
                            @endforeach
                        </select>
                    </x-slot:control>
                </x-field>
            </div>

            <div class="form-grid">
                <x-field name="description" label="Rencana Kerja" required hint="Minimal 10 karakter.">
                    <x-slot:control>
                        <textarea name="description" id="f-description" rows="4" required
                                  placeholder="Contoh: bersihkan filter udara dan ganti roller printer.">{{ old('description', $ticket->description) }}</textarea>
                    </x-slot:control>
                </x-field>
            </div>

            <div class="form-grid">
                <x-field name="scheduled_at" label="Jadwal Pengerjaan" hint="Kosongkan bila belum ditentukan.">
                    <x-slot:control>
                        <input type="date" name="scheduled_at" id="f-scheduled_at"
                               value="{{ old('scheduled_at', $ticket->scheduled_at?->format('Y-m-d')) }}">
                    </x-slot:control>
                </x-field>
            </div>

            <div class="card__foot">
                <div class="btn-row btn-row--end">
                    <x-btn :href="route('admin.tickets.show', $ticket)" variant="ghost">Batal</x-btn>
                    <button type="submit" class="btn btn--primary"><x-icon name="check" /> Simpan Perubahan</button>
                </div>
            </div>
        </form>
    </div>
</x-card>
@endsection
