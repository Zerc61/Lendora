{{-- resources/views/admin/audit-logs/index.blade.php — jejak audit read-only --}}
@extends('layouts.app')

@section('title', 'Audit Log')
@section('chrome', 'Audit Log')

@section('content')
@php
    $flatten = function ($value): string {
        if ($value === null) {
            return 'null';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_array($value)) {
            return \Illuminate\Support\Str::limit((string) json_encode($value, JSON_UNESCAPED_UNICODE), 42);
        }

        return \Illuminate\Support\Str::limit((string) $value, 42) ?: '—';
    };
@endphp

<x-page-head title="Audit Log"
             subtitle="Jejak perubahan data: siapa, kapan, dan objek apa yang diubah. Catatan dibuat otomatis oleh sistem." />

<x-filter-bar :keep="['action', 'actor_id', 'subject_type', 'from', 'to']"
              :reset="route('admin.audit-logs.index')" search="action"
              placeholder="Cari aksi: checkout, approved…">
    <x-slot:controls>
        <x-field name="actor_id" label="Pelaku">
            <x-slot:control>
                <select name="actor_id" data-autosubmit>
                    <option value="">Semua pelaku</option>
                    @foreach ($actors as $actor)
                        <option value="{{ $actor->id }}" @selected((string) request('actor_id') === (string) $actor->id)>
                            {{ $actor->name }}
                        </option>
                    @endforeach
                </select>
            </x-slot:control>
        </x-field>

        <x-field name="subject_type" label="Objek">
            <x-slot:control>
                <select name="subject_type" data-autosubmit>
                    <option value="">Semua objek</option>
                    @foreach ($subjects as $class => $label)
                        <option value="{{ $class }}" @selected(request('subject_type') === $class)>{{ $label }}</option>
                    @endforeach
                </select>
            </x-slot:control>
        </x-field>

        <x-field name="from" label="Dari Tanggal">
            <x-slot:control>
                <input type="date" name="from" id="f-from" data-autosubmit value="{{ request('from') }}">
            </x-slot:control>
        </x-field>

        <x-field name="to" label="Sampai Tanggal">
            <x-slot:control>
                <input type="date" name="to" id="f-to" data-autosubmit value="{{ request('to') }}">
            </x-slot:control>
        </x-field>
    </x-slot:controls>
</x-filter-bar>

<x-card flush icon="scroll" title="Aktivitas Tercatat"
        subtitle="Kolom perubahan menampilkan field yang berbeda antara nilai sebelum dan sesudah." :delay="0">
    <x-slot:actions>
        <span class="badge tone-muted">Read-only</span>
        <span class="badge tone-brand">{{ number_format($logs->total(), 0, ',', '.') }} catatan</span>
    </x-slot:actions>

    <div class="table-wrap table-wrap--scroll">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">Waktu</th>
                    <th scope="col">Pelaku</th>
                    <th scope="col">Aksi</th>
                    <th scope="col" class="hide-sm">Objek</th>
                    <th scope="col" class="hide-sm">IP</th>
                    <th scope="col">Perubahan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    @php
                        $before = $log->before_json ?: [];
                        $after = $log->after_json ?: [];
                        $changed = array_filter($after, fn ($value, $key) => ! array_key_exists($key, $before) || $before[$key] !== $value, ARRAY_FILTER_USE_BOTH);
                        $removed = array_filter($before, fn ($value, $key) => ! array_key_exists($key, $after) || $after[$key] !== $value, ARRAY_FILTER_USE_BOTH);
                    @endphp
                    <tr>
                        <td class="nowrap">
                            <b>{{ $log->created_at->format('d M Y H:i') }}</b>
                            <span class="table__sub">{{ $log->created_at->diffForHumans() }}</span>
                        </td>
                        <td>
                            <div class="cell-media">
                                <span class="avatar avatar--plain">{{ $log->actor?->initials() ?? '?' }}</span>
                                <div class="cell-media__body">
                                    <b>{{ $log->actor?->name ?? 'Sistem' }}</b>
                                    <span class="truncate">{{ $log->actor?->email ?? 'otomatis' }}</span>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge tone-brand badge--plain">{{ $log->action }}</span></td>
                        <td class="hide-sm">
                            @if ($log->subject_type)
                                {{ $subjects->get($log->subject_type) ?? class_basename($log->subject_type) }}
                                <span class="table__code">#{{ $log->subject_id }}</span>
                            @else
                                <span class="dim">—</span>
                            @endif
                        </td>
                        <td class="hide-sm">
                            <span class="table__code">{{ $log->ip_address ?? '—' }}</span>
                        </td>
                        <td>
                            @if ($changed || $removed)
                                <details>
                                    <summary class="strong tiny">Lihat perubahan</summary>
                                    <div class="stack" style="--gap:5px;margin-top:7px">
                                        @foreach ($changed as $field => $value)
                                            <div class="tiny">
                                                <span class="table__code">{{ $field }}</span>
                                                <span class="dim">→</span>
                                                <span>{{ $flatten($value) }}</span>
                                            </div>
                                        @endforeach
                                        @foreach ($removed as $field => $value)
                                            <div class="tiny" style="color:var(--bad)">
                                                <span class="table__code">{{ $field }}</span>
                                                <span class="dim">dihapus</span>
                                                <span class="dim">(semula {{ $flatten($value) }})</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </details>
                            @else
                                <span class="dim">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <x-empty icon="scroll" title="Belum ada aktivitas"
                                     text="Catatan muncul otomatis ketika ada data yang dibuat, diubah, atau diproses." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card__foot">{{ $logs->links() }}</div>
</x-card>
@endsection
