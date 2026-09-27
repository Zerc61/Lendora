{{-- resources/views/admin/reservations/index.blade.php — Antrean persetujuan reservasi --}}
@extends('layouts.app')
@section('title', 'Reservasi')
@section('chrome', 'Reservasi')

@section('content')
<x-page-head title="Reservasi"
             subtitle="Antrean keputusan peminjam. Persetujuan langsung membuat transaksi peminjaman baru.">
    <x-btn :href="route('admin.borrowings.index')" variant="ghost" icon="bag">Peminjaman</x-btn>
</x-page-head>

{{-- filter status cepat (GET link) --}}
<div class="card">
    <div class="btn-row">
        <a class="chip {{ request('status') ? '' : 'is-active' }}" href="{{ route('admin.reservations.index') }}">Semua</a>
        @foreach ($statuses as $s)
            <a class="chip {{ request('status') === $s->value ? 'is-active' : '' }}"
               href="{{ route('admin.reservations.index', ['status' => $s->value]) }}">{{ $s->label() }}</a>
        @endforeach
        @if (request()->filled('status'))
            <a class="btn btn--ghost btn--sm" href="{{ route('admin.reservations.index') }}">
                <x-icon name="refresh" /> Reset
            </a>
        @endif
    </div>
</div>

<x-card flush title="Daftar Reservasi"
          :subtitle="'Menampilkan ' . number_format($reservations->count(), 0, ',', '.') . ' dari ' . number_format($reservations->total(), 0, ',', '.') . ' reservasi'">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">Kode &amp; Unit</th>
                    <th scope="col">Pemohon</th>
                    <th scope="col" class="hide-sm">Rentang Peminjaman</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="col-actions">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($reservations as $r)
                @php
                    $codes = $r->items->map(fn ($i) => $i->asset?->asset_code)->filter()->values();
                    $sisa = $codes->count() - 2;
                @endphp
                <tr>
                    <td>
                        <a href="{{ route('admin.reservations.show', $r) }}" class="table__code">{{ $r->code }}</a>
                        <span class="table__sub">
                            @if ($codes->isEmpty())
                                <span class="dim">Tanpa unit</span>
                            @else
                                {{ $codes->take(2)->implode(', ') }}
                                @if ($sisa > 0)<span class="badge tone-muted">+{{ $sisa }}</span>@endif
                            @endif
                        </span>
                    </td>
                    <td>
                        <b class="truncate">{{ $r->user?->name ?? 'Peminjam dihapus' }}</b>
                        <span class="table__sub">diajukan {{ $r->created_at->locale('id')->diffForHumans() }}</span>
                    </td>
                    <td class="hide-sm nowrap">
                        <span>{{ $r->start_at->format('d M Y H:i') }}</span>
                        <span class="table__sub">s/d {{ $r->end_at->format('d M Y H:i') }}</span>
                    </td>
                    <td><x-status :status="$r->status" /></td>
                    <td class="col-actions">
                        <div class="btn-row btn-row--end">
                            <x-btn :href="route('admin.reservations.show', $r)" size="sm" variant="ghost" icon="eye"
                                    :aria-label="'Detail ' . $r->code" />
                            @if ($r->status->value === 'pending')
                                <form method="POST" action="{{ route('admin.reservations.approve', $r) }}"
                                      data-confirm="Setujui reservasi {{ $r->code }}? Transaksi peminjaman akan dibuat otomatis.">
                                    @csrf
                                    <button type="submit" class="btn btn--ok btn--sm"><x-icon name="check" /> Setujui</button>
                                </form>
                                <form class="btn-row" method="POST" action="{{ route('admin.reservations.reject', $r) }}"
                                      data-confirm="Tolak reservasi {{ $r->code }}? Pemohon akan menerima alasan ini.">
                                    @csrf
                                    <input class="input" name="reason" required placeholder="Alasan penolakan"
                                           aria-label="Alasan penolakan {{ $r->code }}"
                                           style="width:132px;min-height:31px;height:31px;padding:0 9px;font-size:.76rem">
                                    <button type="submit" class="btn btn--danger btn--sm"><x-icon name="x" /> Tolak</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        @if (request()->filled('status'))
                            <x-empty icon="search" title="Tidak ada reservasi pada status ini"
                                     text="Coba pilih status lain atau reset filter.">
                                <x-btn :href="route('admin.reservations.index')" variant="ghost" size="sm">Reset filter</x-btn>
                            </x-empty>
                        @else
                            <x-empty icon="calendar" title="Belum ada reservasi"
                                     text="Reservasi yang diajukan peminjam akan masuk ke antrean ini." />
                        @endif
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if ($reservations->hasPages())
        <div class="card__foot">{{ $reservations->links() }}</div>
    @endif
</x-card>
@endsection
