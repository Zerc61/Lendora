{{-- resources/views/admin/issues/show.blade.php — Detail issue + transisi status + tindak lanjut --}}
@extends('layouts.app')
@section('title', 'Detail Issue')
@section('chrome', auth()->user()->primaryRole() === 'technician' ? 'Issue Aset' : 'Issue')

@section('content')
<x-page-head :back="route('admin.issues.index')" :title="$issue->code"
             :subtitle="$issue->type->label().' · '.($issue->asset?->asset_code ?? 'Tanpa aset').' · dilaporkan '.$issue->created_at->diffForHumans()">
    <x-status :status="$issue->status" class="tone-{{ $issue->status->tone() }}" icon="flag" />
    <x-status :status="$issue->severity" class="tone-{{ $issue->severity->tone() }}" />
</x-page-head>

<div class="grid grid--main">
    {{-- Kolom kiri: data laporan --}}
    <div class="stack" style="--gap:18px">
        <x-card title="Rincian Laporan" icon="alert" :delay="0">
            <dl class="kv">
                <div class="kv__row">
                    <dt>Unit Aset</dt>
                    <dd>
                        @if ($issue->asset)
                            <a href="{{ route('admin.assets.show', $issue->asset) }}">{{ $issue->asset->asset_code }}</a>
                            — {{ $issue->asset->assetType->name }}
                            <x-status :status="$issue->asset->status" class="tone-{{ $issue->asset->status->tone() }}" />
                        @else
                            <span class="dim">Unit tidak terkait</span>
                        @endif
                    </dd>
                </div>
                <div class="kv__row">
                    <dt>Jenis Masalah</dt>
                    <dd><x-status :status="$issue->type" class="tone-{{ $issue->type->tone() }}" /></dd>
                </div>
                <div class="kv__row">
                    <dt>Tingkat</dt>
                    <dd><x-status :status="$issue->severity" class="tone-{{ $issue->severity->tone() }}" /></dd>
                </div>
                <div class="kv__row">
                    <dt>Pelapor</dt>
                    <dd>{{ $issue->reportedBy?->name ?? '—' }}</dd>
                </div>
                <div class="kv__row">
                    <dt>Dilaporkan</dt>
                    <dd class="tnum">{{ $issue->created_at->format('d M Y, H:i') }}
                        <span class="dim small">· {{ $issue->created_at->diffForHumans() }}</span></dd>
                </div>
                @if ($issue->borrowing)
                    <div class="kv__row">
                        <dt>Peminjaman</dt>
                        <dd><a href="{{ route('admin.borrowings.show', $issue->borrowing) }}">{{ $issue->borrowing->code }}</a></dd>
                    </div>
                @endif
            </dl>

            <div class="divider--label">Kronologi Masalah</div>
            <p style="margin-top:10px;color:var(--text-2)">{{ $issue->description }}</p>
        </x-card>

        @if ($issue->resolution)
            <x-card title="Hasil Penyelesaian" icon="check-circle" :delay="60">
                <div class="inline-alert tone-{{ $issue->status->tone() }}">
                    <x-icon name="check-circle" />
                    <div>{{ $issue->resolution }}</div>
                </div>
                <dl class="kv" style="margin-top:12px">
                    <div class="kv__row">
                        <dt>Diputuskan oleh</dt>
                        <dd>{{ $issue->resolvedBy?->name ?? '—' }}</dd>
                    </div>
                    <div class="kv__row">
                        <dt>Waktu</dt>
                        <dd class="tnum">{{ $issue->resolved_at?->format('d M Y, H:i') ?? '—' }}</dd>
                    </div>
                </dl>
            </x-card>
        @endif

        @if ($tickets->isNotEmpty())
            <x-card title="Tiket Maintenance" subtitle="Perbaikan yang dibuat dari issue ini" icon="wrench" :delay="120">
                <div class="list">
                    @foreach ($tickets as $ticket)
                        <a class="list__row list__row--link" href="{{ route('admin.tickets.show', $ticket) }}">
                            <span class="list__main">
                                <b class="table__code">{{ $ticket->code }}</b>
                                <span>{{ $ticket->type->label() }} · {{ \Illuminate\Support\Str::limit($ticket->description, 58) }}</span>
                            </span>
                            <span class="list__side"><x-status :status="$ticket->status" class="tone-{{ $ticket->status->tone() }}" /></span>
                            <x-icon name="chevron-right" style="width:15px;height:15px;color:var(--dim)" />
                        </a>
                    @endforeach
                </div>
            </x-card>
        @endif

        @if ($issue->type->value === 'damage' && $tickets->isEmpty() && $issue->status->value !== 'resolved')
            @can('maintenance.create')
                <x-card title="Tindak Lanjut" icon="wrench" tint :delay="160">
                    <x-slot:actions>
                        <x-btn href="{{ route('admin.tickets.create', ['issue' => $issue->id, 'asset' => $issue->asset_id]) }}"
                               variant="primary" size="sm" icon="plus">Buat Tiket</x-btn>
                    </x-slot:actions>
                    <p class="small muted" style="max-width:52ch">
                        Belum ada tiket untuk issue ini. Tiket maintenance mengunci unit dari peminjaman, menyediakan work log,
                        dan memisahkan perbaikan dari tahap investigasi.
                    </p>
                </x-card>
            @endcan
        @endif
    </div>

    {{-- Kolom kanan: alur + aksi transisi --}}
    <div class="stack" style="--gap:18px">
        <div class="sticky-panel">
            <x-card title="Alur Issue" icon="flag" :delay="0">
                @php
                    $stage = match ($issue->status) {
                        \App\Enums\IssueStatus::Open => 0,
                        \App\Enums\IssueStatus::Investigating => 1,
                        \App\Enums\IssueStatus::Resolved, \App\Enums\IssueStatus::Rejected => 2,
                        default => 3,
                    };
                    $stages = [
                        ['Terbuka', 'Laporan diterima, menunggu ditangani'],
                        ['Diselidiki', 'Petugas memeriksa unit & penyebab'],
                        ['Penyelesaian', 'Diputuskan selesai atau ditolak'],
                        ['Ditutup', 'Status final, issue ditutup'],
                    ];
                @endphp
                <div class="steps" style="flex-direction:column;align-items:stretch;gap:11px">
                    @foreach ($stages as $i => [$label, $note])
                        <div class="step {{ $i < $stage ? 'is-done' : ($i === $stage ? 'is-current' : '') }}" style="align-items:flex-start">
                            <span class="step__dot">{{ $i + 1 }}</span>
                            <span>
                                <b class="small">{{ $label }}</b>
                                <span class="tiny dim" style="display:block">{{ $note }}</span>
                            </span>
                        </div>
                    @endforeach
                </div>
            </x-card>
        </div>

        <x-card title="Aksi Status" icon="play" :delay="60">
            @can('issue.resolve')
                @if ($issue->status->value === 'open')
                    <div class="stack" style="--gap:12px">
                        <div class="inline-alert tone-warn">
                            <x-icon name="clock" />
                            <div>Laporan belum diselidiki. Mulai investigasi agar unit bisa dinilai sebelum diputuskan.</div>
                        </div>
                        <form method="POST" action="{{ route('admin.issues.transition', $issue) }}"
                              data-confirm="Ubah status issue {{ $issue->code }} menjadi Diselidiki?">
                            @csrf
                            <input type="hidden" name="action" value="investigate">
                            <button type="submit" class="btn btn--primary btn--block">
                                <x-icon name="search" /> Mulai Investigasi
                            </button>
                        </form>
                    </div>
                @elseif ($issue->status->value === 'investigating')
                    <div class="stack" style="--gap:16px">
                        <form method="POST" action="{{ route('admin.issues.transition', $issue) }}"
                              class="stack" style="--gap:10px"
                              data-confirm="Tandai {{ $issue->code }} sebagai Terselesaikan?">
                            @csrf
                            <input type="hidden" name="action" value="resolve">
                            <div class="field">
                                <label for="res-resolve">Catatan Hasil</label>
                                <textarea name="resolution" id="res-resolve" rows="2" required
                                          placeholder="Contoh: kabel power diganti, unit kembali normal."></textarea>
                                @error('resolution')<span class="field__error">{{ $message }}</span>@enderror
                            </div>
                            @if ($issue->type->value === 'loss')
                                <div class="stack" style="--gap:8px">
                                    <span class="label">Hasil Investigasi Unit Hilang</span>
                                    <label class="check">
                                        <input type="radio" name="loss_outcome" value="recovered" @checked(old('loss_outcome', 'recovered') === 'recovered')>
                                        <span>Pulih (recovered)<small>Aset kembali berstatus Tersedia</small></span>
                                    </label>
                                    <label class="check">
                                        <input type="radio" name="loss_outcome" value="retired" @checked(old('loss_outcome') === 'retired')>
                                        <span>Pensiun (retired)<small>Aset keluar dari siklus peminjaman</small></span>
                                    </label>
                                    @error('loss_outcome')<span class="field__error">{{ $message }}</span>@enderror
                                </div>
                            @endif
                            <button type="submit" class="btn btn--ok btn--block">
                                <x-icon name="check-circle" /> Tandai Terselesaikan
                            </button>
                        </form>

                        <div class="divider"></div>

                        <form method="POST" action="{{ route('admin.issues.transition', $issue) }}"
                              class="stack" style="--gap:10px"
                              data-confirm="Tolak laporan {{ $issue->code }}? Unit tidak bermasalah menurut pemeriksaan.">
                            @csrf
                            <input type="hidden" name="action" value="reject">
                            <div class="field">
                                <label for="res-reject">Alasan Penolakan</label>
                                <textarea name="resolution" id="res-reject" rows="2" required
                                          placeholder="Contoh: unit berfungsi normal saat diuji langsung."></textarea>
                                @error('resolution')<span class="field__error">{{ $message }}</span>@enderror
                            </div>
                            <button type="submit" class="btn btn--danger btn--block">
                                <x-icon name="x" /> Tolak Laporan
                            </button>
                        </form>

                        <div class="divider"></div>

                        <form method="POST" action="{{ route('admin.issues.transition', $issue) }}"
                              class="stack" style="--gap:10px"
                              data-confirm="Tutup issue {{ $issue->code }} tanpa memutuskan perbaikan?">
                            @csrf
                            <input type="hidden" name="action" value="close">
                            <div class="field">
                                <label for="res-close">Catatan Penutupan</label>
                                <textarea name="resolution" id="res-close" rows="2" required
                                          placeholder="Contoh: unit tidak ditemukan, sudah di luar cakupan lokasi."></textarea>
                                @error('resolution')<span class="field__error">{{ $message }}</span>@enderror
                            </div>
                            <button type="submit" class="btn btn--block">
                                <x-icon name="flag" /> Tutup Issue
                            </button>
                        </form>
                    </div>
                @else
                    <div class="inline-alert tone-{{ $issue->status->tone() }}">
                        <x-icon name="info" />
                        <div>Issue sudah berstatus <b>{{ $issue->status->label() }}</b> — status ini bersifat final.</div>
                    </div>
                @endif
            @else
                <div class="inline-alert tone-muted">
                    <x-icon name="info" />
                    <div>Anda tidak memiliki izin untuk mengubah status issue.</div>
                </div>
            @endcan
        </x-card>
    </div>
</div>
@endsection
