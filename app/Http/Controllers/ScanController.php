<?php
// app/Http/Controllers/ScanController.php

namespace App\Http\Controllers;

use App\Models\Asset;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\Request;

class ScanController extends Controller
{
    /** Halaman scan: kamera (html5-qrcode) + input manual */
    public function index(Request $request)
    {
        // Fallback input manual: langsung resolve
        if ($code = trim((string) $request->query('code'))) {
            return redirect()->route('scan.resolve', ['assetCode' => $code]);
        }

        return view('scan');
    }

    /**
     * Target URL yang di-encode di QR code.
     * QR TIDAK menggantikan authorization — view tetap dicek policy (PDF 4E).
     */
    public function resolve(Request $request, string $assetCode)
    {
        $asset = $this->findAsset($assetCode);

        abort_unless($asset, 404, "Aset tidak ditemukan untuk kode: {$assetCode}");

        $this->authorize('view', $asset);

        return redirect()
            ->route('admin.assets.show', $asset)
            ->with('success', "Hasil scan: {$asset->asset_code}");
    }

    /** Gambar QR (PNG) untuk <img> di halaman & label cetak */
    public function png(Request $request, string $assetCode)
    {
        $asset = $this->findAsset($assetCode);

        abort_unless($asset, 404);

        $this->authorize('view', $asset);

        $options = new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'eccLevel'        => EccLevel::M,
            'scale'           => 8,
            'outputBase64'    => false,
        ]);

        $png = (new QRCode($options))->render(
            route('scan.resolve', ['assetCode' => $asset->asset_code]) // URL absolut
        );

        return response($png, 200, [
            'Content-Type'  => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /** Label QR siap cetak untuk ditempel di unit fisik */
    public function label(Request $request, Asset $asset)
    {
        $this->authorize('view', $asset);

        $asset->load(['assetType.category', 'organization']);

        return view('admin.assets.qr-label', [
            'asset' => $asset,
        ]);
    }

    /** Resolve by asset_code (utama) atau id (konvenien input manual) */
    private function findAsset(string $codeOrId): ?Asset
    {
        return Asset::query()
            ->when(
                ctype_digit($codeOrId),
                fn ($q) => $q->where('id', $codeOrId),
                fn ($q) => $q->where('asset_code', $codeOrId)
            )
            ->first();
    }
}
