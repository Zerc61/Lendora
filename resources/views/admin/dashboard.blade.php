{{-- resources/views/admin/dashboard.blade.php --}}
@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<h1>Dashboard</h1>
<p class="muted">Halo, {{ $user->name }} — role: {{ $user->getRoleNames()->implode(', ') ?: '—' }}</p>

<div class="card">
    <table>
        @foreach($stats as $label => $value)
        <tr>
            <th style="width:60%">{{ $label }}</th>
            <td><strong>{{ $value }}</strong></td>
        </tr>
        @endforeach
    </table>
</div>
@endsection