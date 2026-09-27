{{-- resources/views/notifications/index.blade.php — Daftar notifikasi in-app pengguna --}}
@extends('layouts.app')
@section('title', 'Notifikasi')
@section('chrome', 'Notifikasi')

@section('content')
@php
    /** Ikon + warna per jenis notifikasi (diturunkan dari kelas notifikasi). */
    $gayaNotifikasi = [
        'BorrowingOverdueNotification' => ['icon' => 'alert', 'tone' => 'bad'],
        'BorrowingDueSoonNotification' => ['icon' => 'clock', 'tone' => 'warn'],
        'ReservationStatusNotification' => ['icon' => 'calendar', 'tone' => 'brand'],
        'NewReservationNotification' => ['icon' => 'calendar', 'tone' => 'info'],
        'MaintenanceTicketAssignedNotification' => ['icon' => 'wrench', 'tone' => 'orange'],
        'WarrantyExpiringNotification' => ['icon' => 'shield', 'tone' => 'accent'],
    ];
@endphp

<x-page-head title="Notifikasi"
             subtitle="Persetujuan reservasi, pengingat tenggat, dan informasi aset yang masuk ke akun Anda.">
    @if ($unreadCount > 0)
        <form method="POST" action="{{ route('notifications.readAll') }}"
              data-confirm="Tandai semua notifikasi sebagai sudah dibaca?">
            @csrf
            <button type="submit" class="btn btn--primary"><x-icon name="check" /> Tandai Semua Dibaca</button>
        </form>
    @endif
</x-page-head>

@if ($unreadCount > 0)
    <div class="inline-alert tone-brand" style="margin-bottom:16px">
        <x-icon name="bell" />
        <div>
            <b>{{ number_format($unreadCount, 0, ',', '.') }} notifikasi belum dibaca.</b>
            Buka detail notifikasi untuk melihat transactinya.
        </div>
    </div>
@endif

<x-card title="Kotak Masuk" icon="bell"
        :subtitle="'Menampilkan ' . number_format($notifications->count(), 0, ',', '.') . ' dari ' . number_format($notifications->total(), 0, ',', '.') . ' notifikasi'" :delay="0">
    <x-slot:actions>
        @if ($unreadCount > 0)
            <span class="badge tone-brand">{{ number_format($unreadCount, 0, ',', '.') }} belum dibaca</span>
        @else
            <span class="badge tone-ok">Semua dibaca</span>
        @endif
    </x-slot:actions>

    @forelse ($notifications as $i => $n)
        @php
            $data = $n->data;
            $gaya = $gayaNotifikasi[class_basename($n->type)] ?? ['icon' => 'info', 'tone' => 'muted'];
            $url = $data['url'] ?? null;
        @endphp
        <div class="list__row" style="--d:{{ $i * 40 }}ms">
            <span class="badge {{ 'tone-' . $gaya['tone'] }}"><x-icon :name="$gaya['icon']" /></span>

            <span class="list__main">
                @if ($url)
                    <a href="{{ $url }}"><b>{{ $data['title'] ?? 'Notifikasi' }}</b></a>
                @else
                    <b>{{ $data['title'] ?? 'Notifikasi' }}</b>
                @endif

                @if (! empty($data['message']))
                    <span>{{ $data['message'] }}</span>
                @endif

                <span class="tiny dim">
                    <time datetime="{{ $n->created_at->toIso8601String() }}">{{ $n->created_at->locale('id')->diffForHumans() }}</time>
                    · {{ $n->created_at->format('d M Y H:i') }}
                    @if ($n->unread())<span class="badge tone-brand">Baru</span>@endif
                </span>
            </span>

            <span class="list__side">
                @if ($n->unread())
                    <form method="POST" action="{{ route('notifications.read', $n) }}">
                        @csrf
                        <button type="submit" class="btn btn--ghost btn--sm">Tandai dibaca</button>
                    </form>
                @elseif ($url)
                    <x-btn :href="$url" size="sm" variant="ghost" icon="chevron-right"
                           :aria-label="'Buka notifikasi ' . ($data['title'] ?? '')" />
                @endif
            </span>
        </div>
    @empty
        <x-empty icon="bell" title="Belum ada notifikasi"
                 text="Persetujuan reservasi, pengingat tenggat, dan informasi aset akan muncul di sini." />
    @endforelse

    @if ($notifications->hasPages())
        <div class="card__foot">{{ $notifications->links() }}</div>
    @endif
</x-card>
@endsection
