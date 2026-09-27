{{-- resources/views/components/avatar.blade.php — avatar pengguna

     Satu-satunya tempat yang memutuskan "foto atau inisial". Sebelum komponen
     ini ada, keputusan itu diduplikasi di 2 view (admin/users/index &
     profile) dan HILANG di 5 view lain, sehingga user yang sudah mengunggah
     foto tetap tampil inisial di appbar, menu user, mobile rail, dan card
     "Data Akun" — padahal `hasPhoto()` dan `photoUrl()` sudah benar.

     Parameter:
       :user  User (boleh null → tampil "?"). Penentuannya `hasPhoto()`, bukan
              `photo_url()`: kolom itu hanya benar bila file-nya masih ada.
       size   sm | md | lg | xl  → kelas .avatar--<size>
       plain  Paksa gaya polos (dipakai di daftar padat agar avatar tidak
              bersaing dengan teks di sebelahnya)
--}}
@props(['user', 'size' => null, 'plain' => false])

@php
    $hasPhoto = (bool) ($user?->hasPhoto() ?? false);
    $initials = $user?->initials() ?? '?';
@endphp

<span @class([
        'avatar',
        $size ? 'avatar--' . $size : null,
        $plain && ! $hasPhoto ? 'avatar--plain' : null,
    ]) {{ $attributes }}>
    @if ($hasPhoto)
        <img src="{{ $user->photoUrl() }}" alt="Foto {{ $user->name }}"
             loading="lazy" decoding="async">
    @else
        {{ $initials }}
    @endif
</span>
