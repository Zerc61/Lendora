{{-- resources/views/layouts/app.blade.php — dispatcher shell
     Memilih layout sesuai peran pengguna. Semua view lama tetap memakai
     @extends('layouts.app') dan otomatis mendapat shell yang benar.
     View yang sudah dimigrasikan memakai layout eksplisit (layouts.admin, dll). --}}
@php
    $shell = auth()->check() ? auth()->user()->primaryRole() : 'borrower';
@endphp
@include('layouts.' . $shell)
