{{-- resources/views/admin/tickets/index.blade.php — Antrean tiket maintenance --}}
@extends('layouts.app')
@section('title', 'Tiket Maintenance')
@section('chrome', auth()->user()->primaryRole() === 'technician' ? 'Tiket Saya' : 'Maintenance')

@section('content')
<x-page-head title="Tiket Maintenance"
             subtitle="Antrean perbaikan unit: dari penugasan teknisi sampai verifikasi kondisi akhir.">
    @can('maintenance.create')
        <x-btn href="{{ route('admin.tickets.create') }}" variant="primary" icon="plus">Buat Tiket</x-btn>
    @endcan
</x-page-head>

<x-filter-bar :keep="['status', 'type', 'technician_id']" reset="{{ route('admin.tickets.index') }}"
              placeholder="Cari kode tiket…">
    <x-slot:controls>
        <x-field name="status" label="Status">
            <x-slot:control>
                <select name="status" id="f-status" data-autosubmit>
                    <option value="">Semua status</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
                    @endforeach
                </select>
            </x-slot:control>
        </x-field>

        <x-field name="type" label="Jenis Pekerjaan">
            <x-slot:control>
                <select name="type" id="f-type" data-autosubmit>
                    <option value="">Semua jenis</option>
                    @foreach ($types as $t)
                        <option value="{{ $t->value }}" @selected(request('type') === $t->value)>{{ $t->label() }}</option>
                    @endforeach
                </select>
            </x-slot:control>
        </x-field>

        <x-field name="technician_id" label="Teknisi">
            <x-slot:control>
                <select name="technician_id" id="f-technician_id" data-autosubmit>
                    <option value="">Semua teknisi</option>
                    @foreach ($technicians as $t)
                        <option value="{{ $t->id }}" @selected(request('technician_id') == $t->id)>{{ $t->name }}</option>
                    @endforeach
                </select>
            </x-slot:control>
        </x-field>
    </x-slot:controls>
</x-filter-bar>

@php $filtered = request()->filled('status') || request()->filled('type') || request()->filled('technician_id'); @endphp

<div class="card card--flush">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">Tiket</th>
                    <th scope="col">Aset</th>
                    <th scope="col" class="hide-sm">Kondisi</th>
                    <th scope="col" class="hide-sm">Jenis</th>
                    <th scope="col">Prioritas</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="hide-sm">Teknisi</th>
                    <th scope="col" class="hide-sm">Dibuat</th>
                    <th scope="col" class="col-actions">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tickets as $ticket)
                    <tr>
                        <td>
                            <div class="cell-media">
                                <span class="thumb thumb--ph"><x-icon name="wrench" /></span>
                                <div class="cell-media__body">
                                    <b class="table__code">{{ $ticket->code }}</b>
                                    <span class="truncate">{{ Str::limit($ticket->description, 46) }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <a class="table__code" href="{{ route('admin.assets.show', $ticket->asset) }}">{{ $ticket->asset->asset_code }}</a>
                            <span class="table__sub">{{ $ticket->asset->assetType->name }}</span>
                        </td>
                        <td class="hide-sm"><x-status :status="$ticket->asset->condition" class="tone-{{ $ticket->asset->condition->tone() }}" /></td>
                        <td class="hide-sm"><x-status :status="$ticket->type" class="tone-{{ $ticket->type->tone() }}" /></td>
                        <td><x-status :status="$ticket->priority" class="tone-{{ $ticket->priority->tone() }}" /></td>
                        <td><x-status :status="$ticket->status" class="tone-{{ $ticket->status->tone() }}" /></td>
                        <td class="hide-sm">
                            @if ($ticket->technician)
                                <div class="cell-media">
                                    <span class="avatar avatar--plain">{{ Str::upper(Str::substr($ticket->technician->name, 0, 2)) }}</span>
                                    <div class="cell-media__body"><b>{{ $ticket->technician->name }}</b></div>
                                </div>
                            @else
                                <span class="badge tone-warn">Belum ditugaskan</span>
                            @endif
                        </td>
                        <td class="hide-sm tnum small nowrap">
                            {{ $ticket->created_at->format('d M Y') }}
                            <span class="table__sub">{{ $ticket->created_at->diffForHumans() }}</span>
                        </td>
                        <td class="col-actions">
                            <x-btn :href="route('admin.tickets.show', $ticket)" size="sm" variant="ghost"
                                   icon="eye" aria-label="Buka {{ $ticket->code }}" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <x-empty icon="check-circle" title="Tidak ada tiket di antrean"
                                     :text="$filtered ? 'Tidak ada tiket yang cocok dengan filter aktif.' : 'Semua pekerjaan maintenance sudah selesai. Tiket baru akan muncul di sini.'">
                                @can('maintenance.create')
                                    <x-btn href="{{ route('admin.tickets.create') }}" variant="primary" size="sm" icon="plus">Buat Tiket</x-btn>
                                @endcan
                            </x-empty>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card__foot">
        <div class="btn-row btn-row--between">
            <span class="tiny dim tnum">
                {{ $tickets->total() > 0 ? $tickets->firstItem().'–'.$tickets->lastItem() : 0 }}
                dari {{ number_format($tickets->total(), 0, ',', '.') }} tiket
            </span>
            {{ $tickets->links() }}
        </div>
    </div>
</div>
@endsection
