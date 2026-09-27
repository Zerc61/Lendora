{{-- resources/views/components/filter-bar.blade.php — form filter GET
     @slot controls  field tambahan (select/tanggal/segmented)
     @props keep    nama parameter lain yang harus ikut dipertahankan (hidden) --}}
@props(['action' => null, 'reset' => null, 'search' => 'search', 'placeholder' => 'Cari…', 'keep' => []])
<form method="GET" action="{{ $action ?? request()->url() }}"
      {{ $attributes->merge(['class' => 'card', 'style' => 'margin-bottom:16px']) }}>
    <div class="filters">
        <div class="field" style="min-width:200px">
            <label for="filter-search">Pencarian</label>
            <div class="search" style="width:100%">
                <x-icon name="search" />
                <input id="filter-search" name="{{ $search }}" value="{{ request($search) }}"
                       placeholder="{{ $placeholder }}" data-search-input autocomplete="off">
            </div>
        </div>

        {{ $controls ?? '' }}

        <div class="filters__actions">
            <button class="btn btn--primary" type="submit">
                <x-icon name="filter" /> Terapkan
            </button>
            @if ($reset)
                <a class="btn btn--ghost" href="{{ $reset }}">Reset</a>
            @endif
        </div>
    </div>

    @foreach ((array) $keep as $key)
        @if (request()->filled($key))
            <input type="hidden" name="{{ $key }}" value="{{ request($key) }}">
        @endif
    @endforeach
</form>
