<?php
// app/Http/Controllers/ShowcaseController.php

namespace App\Http\Controllers;

use App\Models\Asset;

class ShowcaseController extends Controller
{
    /**
     * Halaman Produk — tujuan baru hasil scan QR.
     * Menampilkan cover, video, galeri foto, dan spesifikasi unit.
     * QR tetap bukan pengganti authorization (dicek policy view).
     */
    public function show(string $assetCode)
    {
        $asset = Asset::where('asset_code', $assetCode)->first();

        abort_unless($asset, 404, "Produk tidak ditemukan untuk kode: {$assetCode}");

        $this->authorize('view', $asset);

        $asset->load(['assetType.category', 'location', 'organization', 'attachments.uploader']);

        $photos = $asset->attachments->filter(fn ($a) => $this->isPhoto($a))->values();
        $videos = $asset->attachments->filter(fn ($a) => $this->isVideo($a))->values();
        $cover = $asset->attachments->firstWhere('is_cover', true)
            ?? $photos->first()
            ?? $videos->first();

        return view('showcase', compact('asset', 'cover', 'photos', 'videos'));
    }

    private function isPhoto($a): bool
    {
        return in_array(($a->metadata['mime'] ?? ''), ['image/jpeg', 'image/png', 'image/webp'], true)
            || ($a->type->value === 'photo' && ! str_starts_with(($a->metadata['mime'] ?? ''), 'video/'));
    }

    private function isVideo($a): bool
    {
        return str_starts_with(($a->metadata['mime'] ?? ''), 'video/')
            || $a->type->value === 'video';
    }
}
