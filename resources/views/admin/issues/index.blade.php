{{-- resources/views/admin/issues/index.blade.php — Daftar laporan kerusakan aset --}}
@extends('layouts.app')
@section('title', 'Issue & Kerusakan')
@section('chrome', auth()->user()->primaryRole() === 'technician' ? 'Issue Aset' : 'Issue')

@section('content')
<x-page-head title="Issue & Kerusakan Aset"
             subtitle="Laporan kerusakan dan kehilangan unit, beserta status penanganannya.">
    @can('issue.create')
        <x-btn href="{{ route('admin.issues.create') }}" variant="primary" icon="alert">Lapor Kerusakan</x-btn>
    @endcan
</x-page-head>

<x-filter-bar :keep="['status', 'type', 'severity']" reset="{{ route('admin.issues.index') }}"
              placeholder="Cari kode issue…">
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

        <x-field name="type" label="Jenis Masalah">
            <x-slot:control>
                <select name="type" id="f-type" data-autosubmit>
                    <option value="">Semua jenis</option>
                    @foreach ($types as $t)
                        <option value="{{ $t->value }}" @selected(request('type') === $t->value)>{{ $t->label() }}</option>
                    @endforeach
                </select>
            </x-slot:control>
        </x-field>

        <x-field name="severity" label="Tingkat">
            <x-slot:control>
                <select name="severity" id="f-severity" data-autosubmit>
                    <option value="">Semua tingkat</option>
                    @foreach ($severities as $s)
                        <option value="{{ $s->value }}" @selected(request('severity') === $s->value)>{{ $s->label() }}</option>
                    @endforeach
                </select>
            </x-slot:control>
        </x-field>
    </x-slot:controls>
</x-filter-bar>

@php $filtered = request()->filled('status') || request()->filled('type') || request()->filled('severity'); @endphp

<div class="card card--flush">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">Issue</th>
                    <th scope="col">Aset</th>
                    <th scope="col" class="hide-sm">Jenis</th>
                    <th scope="col">Tingkat</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="hide-sm">Pelapor</th>
                    <th scope="col" class="col-actions">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($issues as $issue)
                    <tr>
                        <td>
                            <div class="cell-media">
                                <span class="thumb thumb--ph"><x-icon name="alert" /></span>
                                <div class="cell-media__body">
                                    <b class="table__code">{{ $issue->code }}</b>
                                    <span class="truncate">{{ Str::limit($issue->description, 52) }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if ($issue->asset)
                                <a class="table__code" href="{{ route('admin.assets.show', $issue->asset) }}">{{ $issue->asset->asset_code }}</a>
                                <span class="table__sub">{{ $issue->asset->assetType->name }}</span>
                            @else
                                <span class="dim">Tanpa aset</span>
                            @endif
                        </td>
                        <td class="hide-sm"><x-status :status="$issue->type" class="tone-{{ $issue->type->tone() }}" /></td>
                        <td><x-status :status="$issue->severity" class="tone-{{ $issue->severity->tone() }}" /></td>
                        <td><x-status :status="$issue->status" class="tone-{{ $issue->status->tone() }}" /></td>
                        <td class="hide-sm small">{{ $issue->reportedBy?->name ?? '—' }}</td>
                        <td class="col-actions">
                            <x-btn :href="route('admin.issues.show', $issue)" size="sm" variant="ghost"
                                   icon="eye" aria-label="Detail {{ $issue->code }}" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <x-empty icon="check-circle" title="Tidak ada laporan kerusakan"
                                     :text="$filtered ? 'Tidak ada issue yang cocok dengan filter aktif.' : 'Semua aset dalam kondisi baik. Laporan baru akan muncul di sini.'">
                                @can('issue.create')
                                    <x-btn href="{{ route('admin.issues.create') }}" variant="primary" size="sm" icon="plus">Lapor Kerusakan</x-btn>
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
                {{ $issues->total() > 0 ? $issues->firstItem().'–'.$issues->lastItem() : 0 }}
                dari {{ number_format($issues->total(), 0, ',', '.') }} issue
            </span>
            {{ $issues->links() }}
        </div>
    </div>
</div>
@endsection
