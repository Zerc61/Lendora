{{-- resources/views/layouts/blank.blade.php — shell minimal (QR label cetak, dsb.) --}}
<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
</head>
<body>
@yield('content')
@stack('scripts')
</body>
</html>
