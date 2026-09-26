{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Lendora')</title>
    <style>
        *{box-sizing:border-box}
        body{font-family:sans-serif;margin:0;display:flex;background:#0B0E14;color:#F5F7FA}
        aside{width:230px;background:#121722;min-height:100vh;padding:20px;border-right:1px solid #252B38}
        main{flex:1;padding:24px}
        a{color:#7C5CFC;text-decoration:none}
        aside a{display:block;margin:8px 0;padding:8px;border-radius:6px}
        aside a:hover{background:#252B38}
        h1{margin-top:0}
        .card{background:#121722;padding:16px;border-radius:10px;margin-bottom:14px;border:1px solid #252B38}
        .row{display:flex;justify-content:space-between;align-items:center;gap:10px}
        table{width:100%;border-collapse:collapse}
        td,th{padding:8px;border-bottom:1px solid #252B38;text-align:left;font-size:14px}
        label{color:#8B93A7;font-size:13px;display:block;margin-top:8px}
        input,select,textarea{width:100%;padding:9px;margin:4px 0 8px;background:#0B0E14;color:#F5F7FA;border:1px solid #252B38;border-radius:6px}
        button{background:#7C5CFC;color:#fff;border:0;padding:9px 14px;border-radius:6px;cursor:pointer}
        .alert-success{background:#14532d;color:#dcfce7;padding:10px;border-radius:6px;margin-bottom:12px}
        .alert-error{background:#7f1d1d;color:#fee2e2;padding:10px;border-radius:6px;margin-bottom:12px}
        .muted{color:#8B93A7}
        .badge{display:inline-block;padding:2px 8px;border-radius:99px;font-size:12px;border:1px solid #252B38}
        .b-available,.b-approved{background:#14532d}.b-reserved,.b-returned{background:#1e3a8a}
        .b-borrowed,.b-pending{background:#78350f}.b-maintenance,.b-fair{background:#713f12}
        .b-damaged,.b-rejected{background:#7f1d1d}.b-lost,.b-overdue{background:#450a0a}
        .b-retired,.b-cancelled,.b-fulfilled{background:#334155}
    </style>
</head>
<body>
<aside>
    <h2 style="color:#7C5CFC">Lendora</h2>
    <p class="muted" style="font-size:13px">
        {{ auth()->user()->name }}<br>
        <small>{{ auth()->user()->getRoleNames()->implode(', ') }}</small>
    </p>
    <a href="{{ route('dashboard') }}">📊 Dashboard</a>

    @can('viewAny', App\Models\Asset::class)
        <a href="{{ route('scan') }}">📷 Scan QR</a>
    @endcan

    @can('reservation.create')
        <a href="{{ route('my.reservations.create') }}">➕ Ajukan Reservasi</a>
        <a href="{{ route('my.reservations.index') }}">📅 Reservasi Saya</a>
        <a href="{{ route('my.borrowings.index') }}">📚 Peminjaman Saya</a>
    @endcan

    @can('viewAny', App\Models\Asset::class)
        <a href="{{ route('admin.assets.index') }}">📦 Aset</a>
    @endcan
    @can('reservation.approve')
        <a href="{{ route('admin.reservations.index') }}">🗓️ Reservasi</a>
    @endcan
    @can('borrowing.view')
        <a href="{{ route('admin.borrowings.index') }}">🤝 Peminjaman</a>
    @endcan
    @can('checkout.perform')
        <a href="{{ route('admin.checkout.index') }}">📤 Check-out</a>
    @endcan
    @can('checkin.perform')
        <a href="{{ route('admin.checkin.index') }}">📥 Check-in</a>
    @endcan
    @can('category.manage')
        <a href="{{ route('admin.categories.index') }}">🗂️ Kategori</a>
    @endcan
    @can('asset-type.manage')
        <a href="{{ route('admin.asset-types.index') }}">🧩 Tipe Aset</a>
    @endcan
    @can('location.manage')
        <a href="{{ route('admin.locations.index') }}">📍 Lokasi</a>
    @endcan
    @can('viewAny', App\Models\User::class)
        <a href="{{ route('admin.users.index') }}">👤 Pengguna</a>
    @endcan
    @can('viewAny', App\Models\Organization::class)
        <a href="{{ route('admin.organizations.index') }}">🏢 Organisasi</a>
    @endcan

    <form method="POST" action="{{ route('logout') }}" style="margin-top:20px">
        @csrf
        <button type="submit">Logout</button>
    </form>
</aside>
<main>
    @if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert-error"><ul style="margin:0;padding-left:18px">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
    @yield('content')
</main>
</body>
</html>
