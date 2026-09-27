{{-- resources/views/admin/tickets/show.blade.php — Workbench tiket maintenance --}}
@extends('layouts.app')
@section('title', 'Detail Tiket')
@section('chrome', auth()->user()->primaryRole() === 'technician' ? 'Tiket Saya' : 'Maintenance')

@section('content')
@php
    $order = ['open' => 0, 'assigned' => 1, 'in_progress' => 2, 'waiting_parts' => 3, 'completed' => 4, 'verified' => 5];
    $stage = $order[$ticket->status->value] ?? -1;
    $terminal = in_array($ticket->status->value, ['verified', 'cancelled'], true);
    $workable = in_array($ticket->status->value, ['assigned', 'in_progress', 'waiting_parts'], true);
    $transition = route('admin.tickets.transition', $ticket);
    $stages = [
        ['Dibuka', 'Tiket masuk antrean perbaikan'],
        ['Ditetapkan', 'Teknisi dan jadwal ditentukan'],
        ['Dikerjakan', 'Diagnosis dan pekerjaan dicatat'],
        ['Menunggu Sparepart', 'Menunggu komponen tiba'],
        ['Selesai', 'Pekerjaan utama tuntas'],
        ['Terverifikasi', 'Unit kembali bisa dipinjam'],
    ];
@endphp

<x-page-head :back="route('admin.tickets.index')" :title="$ticket->code"
             :subtitle="$ticket->asset->asset_code.' — '.$ticket->asset->assetType->name.' · '.$ticket->type->label()">
    <x-status :status="$ticket->priority" class="tone-{{ $ticket->priority->tone() }}" icon="flag" />
    <x-status :status="$ticket->status" class="tone-{{ $ticket->status->tone() }}" />
    @can('update', $ticket)
        <x-btn :href="route('admin.tickets.edit', $ticket)" variant="ghost" icon="edit">Edit</x-btn>
    @endcan
</x-page-head>

<div class="grid grid--stats" style="margin-bottom:18px">
    <x-stat label="Kondisi Unit" :value="$ticket->asset->condition->label()" :tone="$ticket->asset->condition->tone()"
            icon="shield" :delay="0" :hint="'Status aset: <b>'.$ticket->asset->status->label().'</b>'" />
    <x-stat label="Total Biaya (Rp)" :value="$totalCost" icon="chart" tone="orange" :delay="60"
            :hint="'Akumulasi <b>'.$ticket->logs->count().'</b> entri work log'" />
    <x-stat label="Work Log" :value="$ticket->logs->count()" icon="clipboard" tone="brand" :delay="120"
            :hint="$ticket->completed_at ? 'Selesai '.$ticket->completed_at->format('d M Y') : 'Belum ditandai selesai'" />
</div>

<div class="grid grid--main">
    {{-- Kolom kiri: detail, diagnosis, work log --}}
    <div class="stack" style="--gap:18px">
        <x-card title="Detail Tiket" icon="wrench" :delay="0">
            <dl class="kv">
                <div class="kv__row"><dt>Unit Aset</dt>
                    <dd><a href="{{ route('admin.assets.show', $ticket->asset) }}">{{ $ticket->asset->asset_code }}</a>
                        — {{ $ticket->asset->assetType->name }}
                        <x-status :status="$ticket->asset->status" class="tone-{{ $ticket->asset->status->tone() }}" /></dd></div>
                @if ($ticket->issue)
                    <div class="kv__row"><dt>Issue Terkait</dt>
                        <dd><a href="{{ route('admin.issues.show', $ticket->issue) }}">{{ $ticket->issue->code }}</a>
                            <span class="dim small">· {{ $ticket->issue->type->label() }}</span></dd></div>
                @endif
                <div class="kv__row"><dt>Jenis Pekerjaan</dt>
                    <dd><x-status :status="$ticket->type" class="tone-{{ $ticket->type->tone() }}" /></dd></div>
                <div class="kv__row"><dt>Prioritas</dt>
                    <dd><x-status :status="$ticket->priority" class="tone-{{ $ticket->priority->tone() }}" /></dd></div>
                <div class="kv__row"><dt>Pelapor</dt>
                    <dd>{{ $ticket->reportedBy?->name ?? '—' }}
                        <span class="dim small">· {{ $ticket->created_at->format('d M Y, H:i') }}</span></dd></div>
                <div class="kv__row"><dt>Teknisi</dt>
                    <dd>{{ $ticket->technician?->name ?? 'Belum ditugaskan' }}</dd></div>
                <div class="kv__row"><dt>Jadwal</dt>
                    <dd class="tnum">{{ $ticket->scheduled_at?->format('d M Y') ?? 'Belum dijadwalkan' }}</dd></div>
                <div class="kv__row"><dt>Selesai</dt>
                    <dd class="tnum">{{ $ticket->completed_at?->format('d M Y, H:i') ?? '—' }}</dd></div>
                <div class="kv__row"><dt>Total Biaya</dt>
                    <dd class="tnum"><b>Rp {{ number_format($totalCost, 0, ',', '.') }}</b></dd></div>
            </dl>

            <div class="divider--label">Rencana Kerja</div>
            <p style="margin-top:10px;color:var(--text-2)">{{ $ticket->description }}</p>
        </x-card>

        <x-card title="Diagnosis" icon="search" :delay="60">
            @if ($ticket->diagnosis)
                <div class="inline-alert tone-brand"><x-icon name="info" />
                    <div>{{ $ticket->diagnosis }}</div>
                </div>
            @else
                <div class="inline-alert tone-muted"><x-icon name="clipboard" />
                    <div>Diagnosis diisi saat tiket ditandai selesai, sebagai ringkasan penyebab dan tindakan perbaikan.</div>
                </div>
            @endif
        </x-card>

        @can('work', $ticket)
            @if ($workable)
                <x-card title="Tambah Work Log" subtitle="Catatan pekerjaan dan biaya per aktivitas" icon="plus" :delay="120">
                    <form method="POST" action="{{ route('admin.tickets.logs.store', $ticket) }}" class="form">
                        @csrf

                        <div class="form-grid">
                            <x-field name="action" label="Pekerjaan" required>
                                <x-slot:control>
                                    <input type="text" name="action" id="f-action" required maxlength="100"
                                           value="{{ old('action') }}" placeholder="Ganti lampu proyektor">
                                </x-slot:control>
                            </x-field>

                            <x-field name="cost" label="Biaya (Rp)" hint="Isi 0 bila tanpa biaya.">
                                <x-slot:control>
                                    <input type="number" step="0.01" min="0" name="cost" id="f-cost"
                                           value="{{ old('cost', 0) }}">
                                </x-slot:control>
                            </x-field>

                            <x-field name="performed_at" label="Tanggal" hint="Default hari ini.">
                                <x-slot:control>
                                    <input type="date" name="performed_at" id="f-performed_at" value="{{ old('performed_at') }}">
                                </x-slot:control>
                            </x-field>

                            <x-field name="note" label="Catatan / Sparepart">
                                <x-slot:control>
                                    <input type="text" name="note" id="f-note" maxlength="500"
                                           value="{{ old('note') }}" placeholder="Sparepart yang dipakai, kelainan yang ditemukan.">
                                </x-slot:control>
                            </x-field>
                        </div>

                        <div class="card__foot">
                            <div class="btn-row btn-row--end">
                                <button type="submit" class="btn btn--primary"><x-icon name="plus" /> Catat Work Log</button>
                            </div>
                        </div>
                    </form>
                </x-card>
            @endif
        @endcan
    </div>

    {{-- Kolom kanan: alur, aksi, riwayat --}}
    <div class="stack" style="--gap:18px">
        <div class="sticky-panel">
            <x-card title="Alur Status" icon="play" :delay="0">
                <div class="steps" style="flex-direction:column;align-items:stretch;gap:10px">
                    @foreach ($stages as $i => [$label, $note])
                        <div class="step {{ $i < $stage ? 'is-done' : ($i === $stage ? 'is-current' : '') }}" style="align-items:flex-start">
                            <span class="step__dot">{{ $i + 1 }}</span>
                            <span><b class="small">{{ $label }}</b>
                                <span class="tiny dim" style="display:block">{{ $note }}</span></span>
                        </div>
                    @endforeach
                </div>
            </x-card>
        </div>

        <x-card title="Aksi" icon="wrench" :delay="60">
            @if ($ticket->status->value === 'verified')
                <div class="inline-alert tone-ok"><x-icon name="check-circle" />
                    <div>Tiket terverifikasi. Unit <b>{{ $ticket->asset->asset_code }}</b> kembali berstatus
                        {{ $ticket->asset->status->label() }}.</div>
                </div>
            @elseif ($ticket->status->value === 'cancelled')
                <div class="inline-alert tone-muted"><x-icon name="info" />
                    <div>Tiket dibatalkan. Unit dikembalikan ke status sebelumnya.</div>
                </div>
            @endif

            <div class="stack" style="--gap:16px">
                @can('maintenance.create')
                    @if ($ticket->status->value === 'open')
                        <form method="POST" action="{{ $transition }}" class="stack" style="--gap:10px"
                              data-confirm="Tugaskan tiket {{ $ticket->code }} ke teknisi terpilih?">
                            @csrf <input type="hidden" name="action" value="assign">
                            <div class="field">
                                <label for="assign-technician">Teknisi Pelaksana</label>
                                <select name="technician_id" id="assign-technician" required>
                                    <option value="">— pilih teknisi —</option>
                                    @foreach ($technicians as $tech)
                                        <option value="{{ $tech->id }}" @selected(old('technician_id') == $tech->id)>{{ $tech->name }}</option>
                                    @endforeach
                                </select>
                                @error('technician_id')<span class="field__error">{{ $message }}</span>@enderror
                            </div>
                            <button type="submit" class="btn btn--primary btn--block">
                                <x-icon name="user" /> Tugaskan Teknisi
                            </button>
                        </form>
                    @endif

                    @if ($ticket->status->value === 'completed')
                        <form method="POST" action="{{ $transition }}" class="stack" style="--gap:10px"
                              data-confirm="Verifikasi tiket {{ $ticket->code }}? Unit akan kembali bisa dipinjam.">
                            @csrf <input type="hidden" name="action" value="verify">
                            <div class="field">
                                <label for="final-condition">Kondisi Akhir Unit</label>
                                <select name="final_condition" id="final-condition" required>
                                    @foreach (\App\Enums\AssetCondition::cases() as $c)
                                        <option value="{{ $c->value }}" @selected(old('final_condition') === $c->value)>{{ $c->label() }}</option>
                                    @endforeach
                                </select>
                                @error('final_condition')<span class="field__error">{{ $message }}</span>@enderror
                            </div>
                            <button type="submit" class="btn btn--ok btn--block">
                                <x-icon name="check-circle" /> Verifikasi &amp; Kembalikan Unit
                            </button>
                        </form>
                    @endif
                @endcan

                @can('work', $ticket)
                    @if ($ticket->status->value === 'assigned')
                        <form method="POST" action="{{ $transition }}" data-confirm="Mulai mengerjakan tiket {{ $ticket->code }}?">
                            @csrf <input type="hidden" name="action" value="start">
                            <button type="submit" class="btn btn--primary btn--block">
                                <x-icon name="play" /> Mulai Kerjakan
                            </button>
                        </form>
                    @elseif ($ticket->status->value === 'in_progress')
                        <form method="POST" action="{{ $transition }}" data-confirm="Tandai tiket {{ $ticket->code }} menunggu sparepart?">
                            @csrf <input type="hidden" name="action" value="wait_parts">
                            <button type="submit" class="btn btn--block"><x-icon name="pause" /> Tunggu Sparepart</button>
                        </form>
                    @endif

                    @if ($workable)
                        <form method="POST" action="{{ $transition }}" class="stack" style="--gap:10px"
                              data-confirm="Tandai tiket {{ $ticket->code }} selesai dikerjakan?">
                            @csrf <input type="hidden" name="action" value="complete">
                            <div class="field">
                                <label for="complete-diagnosis">Diagnosis &amp; Hasil</label>
                                <textarea name="diagnosis" id="complete-diagnosis" rows="3" maxlength="1000"
                                          placeholder="Contoh: modul laser pecah karena panas, diganti modul baru dan unit stabil.">{{ old('diagnosis') }}</textarea>
                                @error('diagnosis')<span class="field__error">{{ $message }}</span>@enderror
                            </div>
                            <button type="submit" class="btn btn--ok btn--block">
                                <x-icon name="check" /> Tandai Selesai
                            </button>
                        </form>
                    @endif
                @endcan

                @can('maintenance.create')
                    @unless ($terminal)
                        <form method="POST" action="{{ $transition }}"
                              data-confirm="Batalkan tiket {{ $ticket->code }}? Unit kembali ke status sebelumnya.">
                            @csrf <input type="hidden" name="action" value="cancel">
                            <button type="submit" class="btn btn--danger btn--block"><x-icon name="x" /> Batalkan Tiket</button>
                        </form>
                    @endunless
                @endcan
            </div>
        </x-card>

        <x-card title="Riwayat Pekerjaan" icon="scroll" :delay="120">
            <x-slot:actions>
                <span class="badge tone-muted count-badge">{{ $ticket->logs->count() }} entri</span>
            </x-slot:actions>

            @if ($ticket->logs->isEmpty())
                <x-empty icon="clipboard" title="Belum ada riwayat" text="Setiap aksi dan catatan pekerjaan otomatis tersimpan di sini." />
            @else
                <div class="timeline">
                    @foreach ($ticket->logs->sortByDesc('performed_at') as $log)
                        <div class="timeline__item {{ (float) $log->cost > 0 ? 'timeline__item--ok' : 'timeline__item--brand' }}"
                             style="--d:{{ min($loop->index * 50, 300) }}ms">
                            <b>{{ $log->action }}</b>
                            <time datetime="{{ $log->performed_at->toIso8601String() }}">{{ $log->performed_at->format('d M Y, H:i') }}
                                · {{ $log->user?->name ?? 'Sistem' }}</time>
                            @if ($log->note)<p>{{ $log->note }}</p>@endif
                            @if ((float) $log->cost > 0)<span class="badge tone-orange">Rp {{ number_format($log->cost, 0, ',', '.') }}</span>@endif
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>
</div>
@endsection
