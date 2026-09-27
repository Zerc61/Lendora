{{-- resources/views/components/field.blade.php — pembungkus form field
     @slot control  isi input/select/textarea
     @slot action   aksi tambahan di kanan label (mis. tombol toggle)          --}}
@props(['name' => null, 'label' => null, 'hint' => null, 'required' => false, 'for' => null])
@php $inputId = $for ?? ($name ? 'f-' . preg_replace('/[^\w-]/', '-', $name) : null); @endphp
<div {{ $attributes->merge(['class' => 'field']) }}>
    @if ($label)
        <label @if ($inputId) for="{{ $inputId }}" @endif>
            {{ $label }}@if ($required)<span class="req">*</span>@endif
        </label>
    @endif
    {{ $control }}
    @if ($hint)<span class="field__hint">{{ $hint }}</span>@endif
    @if ($name && $errors->has($name))
        <span class="field__error">{{ $errors->first($name) }}</span>
    @endif
    @isset($action)<div class="btn-row btn-row--end" style="margin-top:-2px">{{ $action }}</div>@endisset
</div>
