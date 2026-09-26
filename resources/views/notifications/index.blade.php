{{-- resources/views/notifications/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Notifikasi')
@section('content')
<div class="card row">
    <h1 style="margin:0">🔔 Notifikasi @if($unreadCount)<span class="badge b-pending">{{ $unreadCount }} belum dibaca</span>@endif</h1>
    @if($unreadCount)
    <form method="POST" action="{{ route('notifications.readAll') }}">
        @csrf
        <button style="background:#334155">Tandai Semua Dibaca</button>
    </form>
    @endif
</div>
<div class="card">
    @forelse($notifications as $n)
    @php($data = $n->data)
    <div class="row" style="border-bottom:1px solid #252B38;padding:10px 0;{{ $n->unread() ? 'background:#161d2b;margin:0 -16px;padding-left:16px;padding-right:16px' : '' }}">
        <div>
            <a href="{{ $data['url'] ?? '#' }}"><strong>{{ $data['title'] ?? 'Notifikasi' }}</strong></a>
            <div class="muted" style="font-size:13px">{{ $data['message'] ?? '' }}</div>
            <span class="muted" style="font-size:12px">{{ $n->created_at->diffForHumans() }}</span>
        </div>
        @if($n->unread())
        <form method="POST" action="{{ route('notifications.read', $n) }}">
            @csrf
            <button style="background:#334155;padding:5px 10px;font-size:12px">Tandai dibaca</button>
        </form>
        @endif
    </div>
    @empty
    <p class="muted">Belum ada notifikasi.</p>
    @endforelse
    {{ $notifications->links() }}
</div>
@endsection
