{{-- resources/views/admin/audit-logs/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Audit Log')
@section('content')
<h1>📜 Audit Trail <span class="muted" style="font-size:14px">(read-only — PDF 4L)</span></h1>
<div class="card">
    <form method="GET" class="row">
        <div style="flex:1"><label>Aksi</label><input name="action" value="{{ request('action') }}" placeholder="checkout, approved, ..."></div>
        <div style="flex:1">
            <label>Pelaku</label>
            <select name="actor_id">
                <option value="">Semua</option>
                @foreach($actors as $a)
                <option value="{{ $a->id }}" {{ request('actor_id') == $a->id ? 'selected' : '' }}>{{ $a->name }}</option>
                @endforeach
            </select>
        </div>
        <div style="flex:1">
            <label>Objek</label>
            <select name="subject_type">
                <option value="">Semua</option>
                @foreach($subjects as $class => $label)
                <option value="{{ $class }}" {{ request('subject_type') === $class ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div style="align-self:flex-end"><button>Filter</button> <a href="{{ route('admin.audit-logs.index') }}">Reset</a></div>
    </form>
</div>
<div class="card">
    <table>
        <tr><th>Waktu</th><th>Pelaku</th><th>Aksi</th><th>Objek</th><th>IP</th><th>Detail</th></tr>
        @forelse($logs as $log)
        <tr>
            <td>{{ $log->created_at->format('d M Y H:i') }}</td>
            <td>{{ $log->actor?->name ?? 'Sistem' }}</td>
            <td><strong>{{ $log->action }}</strong></td>
            <td>{{ class_basename($log->subject_type) }} #{{ $log->subject_id }}</td>
            <td class="muted">{{ $log->ip_address ?? '—' }}</td>
            <td>
                @if($log->before_json || $log->after_json)
                <details>
                    <summary style="cursor:pointer;color:#7C5CFC">lihat</summary>
                    <pre style="font-size:11px;white-space:pre-wrap;max-width:340px">{{ trim(($log->before_json ? 'BEFORE: '.json_encode($log->before_json, JSON_UNESCAPED_UNICODE)."\n" : '').($log->after_json ? 'AFTER: '.json_encode($log->after_json, JSON_UNESCAPED_UNICODE) : '')) }}</pre>
                </details>
                @else
                <span class="muted">—</span>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="6" class="muted">Belum ada aktivitas tercatat.</td></tr>
        @endforelse
    </table>
    {{ $logs->links() }}
</div>
@endsection
