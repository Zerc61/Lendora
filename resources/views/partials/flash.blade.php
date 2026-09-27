{{-- resources/views/partials/flash.blade.php — toast session + error validasi --}}
<div class="flash-stack" role="status" aria-live="polite">
    @foreach (['success' => 'ok', 'status' => 'info'] as $key => $tone)
        @if (session($key))
            <div class="alert tone-{{ $tone }}" data-autoclose="6500">
                <x-icon name="{{ $tone === 'ok' ? 'check-circle' : 'info' }}" />
                <div class="alert__body"><b>{{ $key === 'success' ? 'Berhasil' : 'Informasi' }}</b><p>{{ session($key) }}</p></div>
                <button class="alert__close" type="button" data-close aria-label="Tutup"><x-icon name="x" /></button>
            </div>
        @endif
    @endforeach

    @if (session('error'))
        <div class="alert tone-bad" data-autoclose="8000">
            <x-icon name="alert" />
            <div class="alert__body"><b>Gagal</b><p>{{ session('error') }}</p></div>
            <button class="alert__close" type="button" data-close aria-label="Tutup"><x-icon name="x" /></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert tone-bad" data-autoclose="9000">
            <x-icon name="alert" />
            <div class="alert__body">
                <b>Periksa kembali isian Anda</b>
                <ul>
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
            <button class="alert__close" type="button" data-close aria-label="Tutup"><x-icon name="x" /></button>
        </div>
    @endif
</div>
