{{-- resources/views/admin/checkin/show.blade.php — Form penerimaan pengembalian (check-in) --}}
@extends('layouts.app')
@section('title', 'Check-in ' . $borrowing->code)
@section('chrome', 'Check-in')

@section('content')
@php
    $terlambat = $borrowing->status->value === 'overdue' || $borrowing->isOverdue();
    $sisaHari = $borrowing->due_at ? (int) now()->diffInDays($borrowing->due_at, false) : null;
@endphp

<x-page-head :back="route('admin.checkin.index')" :title="'Terima Pengembalian ' . $borrowing->code"
             :subtitle="($borrowing->borrower?->name ?? 'Peminjam dihapus') . ' · ' . $borrowing->items->count() . ' unit · batas kembali ' . ($borrowing->due_at?->format('d M Y H:i') ?? '—')">
    <x-status :status="$borrowing->status" />
    @if ($terlambat)<span class="badge tone-bad">Terlambat</span>@endif
</x-page-head>

<form id="form-checkin" method="POST" action="{{ route('admin.checkin.store', $borrowing) }}"
      data-confirm="Terima pengembalian {{ $borrowing->items->count() }} unit? Unit rusak atau hilang akan dibuatkan issue otomatis.">
    @csrf

    <div class="grid grid--main">
        <div class="stack" style="--gap:18px">
            @if ($terlambat)
                <div class="inline-alert tone-bad">
                    <x-icon name="alert" />
                    <div>
                        <b>Pengembalian terlambat.</b>
                        @if ($sisaHari !== null && $sisaHari < 0)
                            Sudah lewat {{ abs($sisaHari) }} hari dari batas pengembalian
                            ({{ $borrowing->due_at->format('d M Y H:i') }}).
                        @else
                            Batas pengembalian {{ $borrowing->due_at?->format('d M Y H:i') }}.
                        @endif
                        Tetap proses pengembalian di sini agar aset kembali tercatat.
                    </div>
                </div>
            @endif

            <x-card title="Ringkasan Peminjam" icon="user" :delay="0">
                <dl class="kv">
                    <div class="kv__row">
                        <dt>Nama peminjam</dt>
                        <dd>{{ $borrowing->borrower?->name ?? '—' }}<span class="table__sub">{{ $borrowing->borrower?->email }}</span></dd>
                    </div>
                    <div class="kv__row">
                        <dt>Tujuan peminjaman</dt>
                        <dd>{{ $borrowing->purpose ?: 'Tidak ada catatan' }}</dd>
                    </div>
                    <div class="kv__row">
                        <dt>Check-out</dt>
                        <dd class="nowrap">
                            {{ $borrowing->checked_out_at?->format('d M Y H:i') ?? '—' }}
                            <span class="table__sub">
                                @if ($borrowing->checked_out_at)
                                    dipinjam {{ $borrowing->checked_out_at->locale('id')->diffForHumans($borrowing->due_at, ['syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) }} hingga tenggat
                                @endif
                            </span>
                        </dd>
                    </div>
                    <div class="kv__row">
                        <dt>Batas pengembalian</dt>
                        <dd class="nowrap">
                            {{ $borrowing->due_at?->format('d M Y H:i') ?? '—' }}
                            @if ($sisaHari !== null)
                                <span class="table__sub">
                                    @if ($sisaHari > 0) masih ada sisa {{ $sisaHari }} hari
                                    @elseif ($sisaHari === 0) jatuh tempo hari ini
                                    @else <span style="color:var(--bad);font-weight:800">terlambat {{ abs($sisaHari) }} hari</span>
                                    @endif
                                </span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </x-card>

            <x-card title="Hasil Pemeriksaan Unit" subtitle="Bandingkan dengan kondisi saat keluar, lalu catat kondisi akhir tiap unit." icon="clipboard" :delay="60">
                @forelse ($borrowing->items as $i => $item)
                    <div class="cell-media">
                        <span class="thumb thumb--ph"><x-icon name="box" /></span>
                        <div class="cell-media__body">
                            <b>{{ $item->asset?->asset_code ?? '—' }}</b>
                            <span>{{ $item->asset?->assetType?->name ?? '—' }} · {{ $item->created_at->locale('id')->diffForHumans() }}</span>
                            <span>
                                kondisi saat keluar:
                                <b>{{ $item->inspectionCheckout?->condition?->label() ?? $item->condition_out?->label() ?? 'tidak tercatat' }}</b>
                                @if ($item->notes_out) · {{ $item->notes_out }} @endif
                            </span>
                        </div>
                    </div>

                    <div class="form-grid" style="--gap:10px;padding:8px 0 0">
                        <x-field name="items.{{ $item->id }}.condition_in" :for="'ci-' . $item->id"
                                 label="Kondisi Akhir" required>
                            <x-slot:control>
                                <select id="ci-{{ $item->id }}" name="items[{{ $item->id }}][condition_in]" required>
                                    @foreach (\App\Enums\AssetCondition::cases() as $c)
                                        <option value="{{ $c->value }}"
                                            @selected(old('items.' . $item->id . '.condition_in') === $c->value)>{{ $c->label() }}</option>
                                    @endforeach
                                </select>
                            </x-slot:control>
                        </x-field>

                        <x-field name="items.{{ $item->id }}.outcome" :for="'oc-' . $item->id"
                                 label="Hasil" required hint="Rusak / hilang membuat issue otomatis.">
                            <x-slot:control>
                                <select id="oc-{{ $item->id }}" name="items[{{ $item->id }}][outcome]" required>
                                    <option value="ok" @selected(old('items.' . $item->id . '.outcome') === 'ok')>OK — kembali baik</option>
                                    <option value="damaged" @selected(old('items.' . $item->id . '.outcome') === 'damaged')>Rusak</option>
                                    <option value="lost" @selected(old('items.' . $item->id . '.outcome') === 'lost')>Hilang</option>
                                </select>
                            </x-slot:control>
                        </x-field>

                        <x-field name="items.{{ $item->id }}.notes_in" :for="'ni-' . $item->id"
                                 label="Catatan" hint="Wajib jika rusak / hilang.">
                            <x-slot:control>
                                <input id="ni-{{ $item->id }}" name="items[{{ $item->id }}][notes_in]"
                                       value="{{ old('items.' . $item->id . '.notes_in') }}"
                                       placeholder="Contoh: layar retak, tidak bisa dinyalakan">
                            </x-slot:control>
                        </x-field>
                    </div>

                    @if (! $loop->last)<div class="divider"></div>@endif
                @empty
                    <x-empty icon="box" title="Tidak ada unit" text="Transaksi ini tidak punya aset untuk diperiksa." />
                @endforelse
            </x-card>

            <x-card title="Catatan Umum Check-in" subtitle="Opsional — konteks tambahan untuk petugas berikutnya." icon="edit" :delay="120">
                <x-field name="checkin_notes" label="Catatan Umum" hint="Maksimal 1000 karakter.">
                    <x-slot:control>
                        <textarea id="f-checkin_notes" name="checkin_notes" rows="3"
                                  placeholder="Contoh: peminjam mengantar 2 unit, sisanya menyusul">{{ old('checkin_notes') }}</textarea>
                    </x-slot:control>
                </x-field>
            </x-card>
        </div>

        <div class="stack" style="--gap:18px">
            <x-card title="Alur Pengembalian" icon="layers" :delay="0">
                <div class="steps">
                    <span class="step is-current">
                        <span class="step__dot">1</span>
                        <span class="step__label">Periksa fisik unit</span>
                    </span>
                    <span class="step__line"></span>
                    <span class="step">
                        <span class="step__dot">2</span>
                        <span class="step__label">Catat kondisi &amp; hasil</span>
                    </span>
                    <span class="step__line"></span>
                    <span class="step">
                        <span class="step__dot">3</span>
                        <span class="step__label">Terima &amp; kembalikan aset</span>
                    </span>
                </div>

                <div class="divider"></div>

                <div class="stack" style="--gap:11px">
                    <div class="inline-alert tone-warn">
                        <x-icon name="alert" />
                        <div><b>Rusak atau hilang?</b> Pilih hasil yang sesuai — sistem membuat issue dan menurunkan unit dari ketersediaan.</div>
                    </div>
                    <div class="inline-alert tone-bad">
                        <x-icon name="clipboard" />
                        <div><b>Catatan wajib diisi</b> untuk unit berstatus rusak atau hilang agar kronologi masalah jelas.</div>
                    </div>
                </div>
            </x-card>

            <x-card title="Selesaikan" icon="check-circle" tint :delay="60">
                <p class="small muted">
                    Setelah dikirim, {{ $borrowing->items->count() }} aset kembali ke pool ketersediaan dan transaksi ditandai
                    <b>Dikembalikan</b>.
                </p>

                <div class="card__foot">
                    <button type="submit" class="btn btn--primary btn--lg btn--block">
                        <x-icon name="in" /> Terima Pengembalian
                    </button>
                    <a class="btn btn--ghost btn--block" href="{{ route('admin.checkin.index') }}" style="margin-top:8px">
                        Batal, kembali ke antrean
                    </a>
                </div>
            </x-card>
        </div>
    </div>
</form>

{{-- Aksi tetap di layar kecil --}}
<div class="actionbar">
    <button type="submit" form="form-checkin" class="btn btn--primary"><x-icon name="in" /> Terima</button>
    <a class="btn btn--ghost" href="{{ route('admin.checkin.index') }}">Batal</a>
</div>
@endsection
