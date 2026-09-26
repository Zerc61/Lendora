{{-- resources/views/admin/tickets/edit.blade.php --}}
@extends('layouts.app')
@section('title', 'Edit Tiket')
@section('content')
<h1>Edit Tiket: {{ $ticket->code }}</h1>
<div class="card" style="max-width:560px">
    <form method="POST" action="{{ route('admin.tickets.update', $ticket) }}">
        @csrf @method('PUT')
        <label>Jenis Pekerjaan</label>
        <select name="type">
            @foreach($types as $t)
            <option value="{{ $t->value }}" {{ old('type', $ticket->type->value) === $t->value ? 'selected' : '' }}>{{ $t->value }}</option>
            @endforeach
        </select>
        <label>Prioritas</label>
        <select name="priority">
            @foreach($priorities as $p)
            <option value="{{ $p->value }}" {{ old('priority', $ticket->priority->value) === $p->value ? 'selected' : '' }}>{{ $p->value }}</option>
            @endforeach
        </select>
        <label>Deskripsi</label>
        <textarea name="description" rows="4" required>{{ old('description', $ticket->description) }}</textarea>
        <label>Jadwal</label>
        <input type="date" name="scheduled_at" value="{{ old('scheduled_at', $ticket->scheduled_at?->format('Y-m-d')) }}">
        <button>Update</button>
        <a href="{{ route('admin.tickets.show', $ticket) }}" style="margin-left:8px">Batal</a>
    </form>
</div>
@endsection
