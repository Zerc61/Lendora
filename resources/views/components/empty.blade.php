{{-- resources/views/components/empty.blade.php --}}
@props(['icon' => 'box', 'title' => 'Belum ada data', 'text' => null])
<div {{ $attributes->merge(['class' => 'empty']) }}>
    <span class="empty__icon"><x-icon :name="$icon" /></span>
    <b>{{ $title }}</b>
    @if ($text)<p>{{ $text }}</p>@endif
    @if (! $slot->isEmpty())<div class="btn-row" style="justify-content:center">{{ $slot }}</div>@endif
</div>
