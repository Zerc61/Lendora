{{-- resources/views/partials/notification-btn.blade.php — tombol lonceng + dot unread --}}
@php
    $unread = auth()->user()->unreadNotifications()->count();
    $bellId = 'bell-menu-' . ($id ?? 'top');
@endphp
<div class="menu-anchor">
    <button type="button" class="icon-btn" data-menu="{{ $bellId }}" aria-label="Notifikasi ({{ $unread }} belum dibaca)">
        <x-icon name="bell" />
        @if ($unread)<span class="icon-btn__dot"></span>@endif
    </button>

    <div class="menu menu--right" id="{{ $bellId }}" hidden style="min-width:268px">
        <div class="menu__head">
            <b>Notifikasi</b>
            <span>{{ $unread ? $unread . ' belum dibaca' : 'Semua sudah dibaca' }}</span>
        </div>
        @php $recent = auth()->user()->notifications()->limit(5)->get(); @endphp
        @forelse ($recent as $notification)
            <a class="menu__item" href="{{ route('notifications.index') }}">
                <x-icon :name="$notification->data['tone'] ?? 'bell'" />
                <span class="truncate" style="flex:1">
                    {{ \Illuminate\Support\Str::limit($notification->data['title'] ?? 'Notifikasi', 34) }}
                </span>
                @unless ($notification->read_at)<span class="icon-btn__dot" style="position:static;box-shadow:none"></span>@endunless
            </a>
        @empty
            <div class="menu__item" style="cursor:default;color:var(--dim)">Belum ada notifikasi</div>
        @endforelse
        <div class="menu__sep"></div>
        <a class="menu__item" href="{{ route('notifications.index') }}"><x-icon name="external" /> Lihat semua</a>
    </div>
</div>
