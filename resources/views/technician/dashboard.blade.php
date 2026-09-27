{{-- resources/views/technician/dashboard.blade.php — Beranda teknisi: meja kerja --}}
@extends('layouts.technician')
@section('title', 'Pekerjaan Saya')
@section('chrome', 'Pekerjaan Saya')

@section('content')
<x-page-head title="Tiket Prioritas Anda"
             subtitle="Urut berdasarkan status pengerjaan, lalu tingkat prioritas.">
    <x-btn href="{{ route('admin.assets.index') }}" variant="ghost" icon="box" class="hide-xs">Aset</x-btn>
    <x-btn href="{{ route('admin.tickets.create') }}" variant="primary" icon="plus">Tiket Baru</x-btn>
</x-page-head>

<div class="grid grid--stats" style="margin-bottom:18px">
    <x-stat label="Sedang Dikerjakan" :value="$stats['in_progress']" icon="wrench" tone="brand" :delay="0"
            :href="route('admin.tickets.index', ['status' => 'in_progress'])" />
    <x-stat label="Menunggu Part" :value="$stats['waiting_parts']" icon="box" :tone="$stats['waiting_parts'] > 0 ? 'warn' : 'muted'"
            :delay="60" :href="route('admin.tickets.index', ['status' => 'waiting_parts'])" />
    <x-stat label="Total Tiket Saya" :value="$stats['mine']" icon="clipboard" tone="accent" :delay="120"
            :href="route('admin.tickets.index')" />
    <x-stat label="Belum Ditugaskan" :value="$stats['unassigned']" icon="in" :tone="$stats['unassigned'] > 0 ? 'bad' : 'muted'"
            hint="Bisa Anda klaim" :delay="180" :href="route('admin.tickets.index', ['status' => 'open'])" />
</div>

<div class="grid grid--main">
    <div class="stack" style="--gap:18px">
        <x-card title="Antrean Pengerjaan" subtitle="Tiket yang ditugaskan ke Anda atau belum ada pemiliknya" icon="wrench" :delay="0">
            <x-slot:actions>
                <a class="btn btn--ghost btn--sm" href="{{ route('admin.tickets.index') }}">Semua tiket</a>
            </x-slot:actions>

            @forelse ($tickets as $i => $ticket)
                <a class="list__row list__row--link" href="{{ route('admin.tickets.show', $ticket) }}" style="--d:{{ $i * 50 }}ms">
                    <span class="deck__icon" style="width:34px;height:34px;--tone:{{ $ticket->priority->tone() === 'bad' ? 'var(--bad)' : 'var(--warn)' }};--tone-soft:transparent;--tone-line:var(--border)">
                        <x-icon name="wrench" style="width:16px;height:16px" />
                    </span>
                    <span class="list__main">
                        <b>{{ $ticket->code }} · <span class="muted" style="font-weight:600">{{ $ticket->asset?->asset_code ?? '—' }}</span></b>
                        <span class="truncate">{{ \Illuminate\Support\Str::limit($ticket->description, 54) ?: $ticket->type->label() }}</span>
                    </span>
                    <span class="list__side btn-row" style="justify-content:flex-end;flex-wrap:nowrap">
                        <x-status :status="$ticket->priority" />
                        <x-status :status="$ticket->status" />
                    </span>
                </a>
            @empty
                <x-empty icon="check-circle" title="Tidak ada tiket aktif"
                         text="Semua pekerjaan Anda selesai. Tiket baru akan muncul di sini secara otomatis.">
                    <x-btn href="{{ route('admin.tickets.index') }}" variant="ghost" size="sm">Lihat semua tiket</x-btn>
                </x-empty>
            @endforelse
        </x-card>

        <x-card title="Issue Aset Terakhir" subtitle="Laporan kerusakan yang mungkin butuh perbaikan" icon="alert" :delay="80">
            @forelse ($issues as $i => $issue)
                <a class="list__row list__row--link" href="{{ route('admin.issues.show', $issue) }}" style="--d:{{ $i * 45 }}ms">
                    <span class="list__main">
                        <b>{{ $issue->code }} · {{ $issue->asset?->asset_code ?? '—' }}</b>
                        <span class="clamp-2">{{ \Illuminate\Support\Str::limit($issue->description, 80) }}</span>
                    </span>
                    <span class="list__side btn-row" style="justify-content:flex-end;flex-wrap:nowrap">
                        <x-status :status="$issue->severity" />
                        <x-status :status="$issue->status" />
                    </span>
                </a>
            @empty
                <x-empty icon="check-circle" title="Tidak ada issue terbuka" text="Tidak ada laporan kerusakan baru." />
            @endforelse
        </x-card>
    </div>

    <div class="stack" style="--gap:18px">
        <x-card title="Alur Kerja Tiket" icon="play" :delay="0">
            <div class="steps" style="flex-direction:column;align-items:stretch;gap:10px">
                @php
                    $flow = [
                        ['Open', 'Diterima', 'Tiket baru masuk antrean'],
                        ['Assigned', 'Ditugaskan', 'Teknisi & jadwal ditentukan'],
                        ['InProgress', 'Dikerjakan', 'Catat diagnosis & pekerjaan'],
                        ['WaitingParts', 'Tunggu Part', 'Menunggu komponen datang'],
                        ['Completed', 'Selesai', 'Aset kembali bisa dipakai'],
                    ];
                @endphp
                @foreach ($flow as $i => [$key, $label, $note])
                    <div class="step" style="align-items:flex-start">
                        <span class="step__dot" @if ($i <= 1) style="background:var(--primary-soft);border-color:var(--primary-line);color:var(--primary-2)" @endif>{{ $i + 1 }}</span>
                        <span>
                            <b class="small">{{ $label }}</b>
                            <span class="tiny dim" style="display:block">{{ $note }}</span>
                        </span>
                    </div>
                @endforeach
            </div>
        </x-card>

        <x-card title="Catatan Praktis" icon="info" :delay="60">
            <div class="stack" style="--gap:11px">
                <div class="inline-alert tone-brand">
                    <x-icon name="clipboard" />
                    <div><b>Catat setiap pekerjaan.</b> Log pekerjaan + biaya membuat laporan maintenance akurat.</div>
                </div>
                <div class="inline-alert tone-warn">
                    <x-icon name="box" />
                    <div><b>Komponen lama?</b> Tandai <b>Menunggu Part</b> agar tidak menahan antrean.</div>
                </div>
            </div>
        </x-card>
    </div>
</div>
@endsection
