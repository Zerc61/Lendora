{{-- resources/views/admin/checkout/show.blade.php — Form serah-terima (check-out) --}}
@extends('layouts.app')
@section('title', 'Check-out ' . $borrowing->code)
@section('chrome', 'Check-out')

@section('content')
@php $sisaHari = $borrowing->due_at ? (int) now()->diffInDays($borrowing->due_at, false) : null; @endphp

<x-page-head :back="route('admin.checkout.index')" :title="'Serahkan ' . $borrowing->code"
             :subtitle="($borrowing->borrower?->name ?? 'Peminjam dihapus') . ' · ' . $borrowing->items->count() . ' unit · batas kembali ' . ($borrowing->due_at?->format('d M Y H:i') ?? '—')">
    <x-status :status="$borrowing->status" />
</x-page-head>

<form id="form-checkout" method="POST" action="{{ route('admin.checkout.store', $borrowing) }}"
      data-confirm="Serahkan {{ $borrowing->items->count() }} unit dan set status menjadi Dipinjam?">
    @csrf

    <div class="grid grid--main">
        <div class="stack" style="--gap:18px">
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
                        <dt>Reservasi asal</dt>
                        <dd>
                            @if ($borrowing->reservation)
                                <a href="{{ route('admin.reservations.show', $borrowing->reservation) }}">{{ $borrowing->reservation->code }}</a>
                                <span class="table__sub">diajukan {{ $borrowing->reservation->created_at->locale('id')->diffForHumans() }}</span>
                            @else
                                <span class="dim">Pinjaman langsung</span>
                            @endif
                        </dd>
                    </div>
                    <div class="kv__row">
                        <dt>Batas pengembalian</dt>
                        <dd class="nowrap">
                            {{ $borrowing->due_at?->format('d M Y H:i') ?? '—' }}
                            @if ($sisaHari !== null && $sisaHari > 0)
                                <span class="table__sub">sisa {{ $sisaHari }} hari setelah diserahkan</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </x-card>

            <x-card title="Kondisi Awal Setiap Unit" subtitle="Cocokkan kode fisik dengan unit di bawah, lalu catat kondisinya sebelum diserahkan." icon="clipboard" :delay="60">
                @forelse ($borrowing->items as $i => $item)
                    <div class="cell-media">
                        <span class="thumb thumb--ph"><x-icon name="box" /></span>
                        <div class="cell-media__body">
                            <b>{{ $item->asset?->asset_code ?? '—' }}</b>
                            <span>{{ $item->asset?->assetType?->name ?? '—' }} · tercatat {{ $item->created_at->locale('id')->diffForHumans() }}</span>
                            <span>
                                kondisi saat ini {{ $item->asset?->condition?->label() ?? '—' }}
                                @if ($item->asset?->status) · <x-status :status="$item->asset->status" /> @endif
                            </span>
                        </div>
                    </div>

                    <div class="form-grid" style="--gap:10px;padding:8px 0 0">
                        <x-field name="items.{{ $item->id }}.condition_out" :for="'co-' . $item->id"
                                 label="Kondisi Awal" required>
                            <x-slot:control>
                                <select id="co-{{ $item->id }}" name="items[{{ $item->id }}][condition_out]" required>
                                    @foreach (\App\Enums\AssetCondition::cases() as $c)
                                        <option value="{{ $c->value }}"
                                            @selected(old('items.' . $item->id . '.condition_out', $item->asset?->condition?->value) === $c->value)>{{ $c->label() }}</option>
                                    @endforeach
                                </select>
                            </x-slot:control>
                        </x-field>

                        <x-field name="items.{{ $item->id }}.notes_out" :for="'no-' . $item->id"
                                 label="Catatan Kondisi" hint="Opsional — tulis kondisi fisik yang perlu diketahui.">
                            <x-slot:control>
                                <input id="no-{{ $item->id }}" name="items[{{ $item->id }}][notes_out]"
                                       value="{{ old('items.' . $item->id . '.notes_out') }}"
                                       placeholder="Contoh: goresan kecil di sudut">
                            </x-slot:control>
                        </x-field>
                    </div>

                    @if (! $loop->last)<div class="divider"></div>@endif
                @empty
                    <x-empty icon="box" title="Tidak ada unit" text="Transaksi ini tidak punya aset untuk diserahkan." />
                @endforelse
            </x-card>
        </div>

        <div class="stack" style="--gap:18px">
            <x-card title="Alur Serah-terima" icon="layers" :delay="0">
                <div class="steps">
                    <span class="step is-current">
                        <span class="step__dot">1</span>
                        <span class="step__label">Verifikasi peminjam &amp; unit</span>
                    </span>
                    <span class="step__line"></span>
                    <span class="step">
                        <span class="step__dot">2</span>
                        <span class="step__label">Catat kondisi awal</span>
                    </span>
                    <span class="step__line"></span>
                    <span class="step">
                        <span class="step__dot">3</span>
                        <span class="step__label">Serahkan aset</span>
                    </span>
                </div>

                <div class="divider"></div>

                <div class="stack" style="--gap:11px">
                    <div class="inline-alert tone-accent">
                        <x-icon name="qr" />
                        <div><b>Scan QR mempercepat verifikasi.</b> Buka halaman aset langsung saat memeriksa fisik unit.</div>
                    </div>
                    <div class="inline-alert tone-brand">
                        <x-icon name="clipboard" />
                        <div><b>Catat kondisi apa adanya.</b> Kerusakan saat keluar harus tercatat di sini.</div>
                    </div>
                </div>
            </x-card>

            <x-card title="Selesaikan" icon="check-circle" tint :delay="60">
                <p class="small muted">
                    Setelah dikirim, {{ $borrowing->items->count() }} aset berstatus <b>Dipinjam</b>, batas pengembalian
                    dihitung, dan satu inspeksi kondisi awal tersimpan per unit.
                </p>

                <div class="card__foot">
                    <button type="submit" class="btn btn--primary btn--lg btn--block">
                        <x-icon name="out" /> Serahkan &amp; Set Status Dipinjam
                    </button>
                    <a class="btn btn--ghost btn--block" href="{{ route('admin.checkout.index') }}" style="margin-top:8px">
                        Batal, kembali ke antrean
                    </a>
                </div>
            </x-card>
        </div>
    </div>
</form>

{{-- Aksi tetap di layar kecil --}}
<div class="actionbar">
    <button type="submit" form="form-checkout" class="btn btn--primary"><x-icon name="out" /> Serahkan</button>
    <a class="btn btn--ghost" href="{{ route('admin.checkout.index') }}">Batal</a>
</div>
@endsection
