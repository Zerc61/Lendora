<?php
// app/Http/Controllers/Admin/AssetAttachmentController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssetAttachmentRequest;
use App\Models\Asset;
use App\Models\AssetAttachment;
use Illuminate\Support\Facades\Storage;

class AssetAttachmentController extends Controller
{
    public function store(StoreAssetAttachmentRequest $request, Asset $asset)
    {
        // Upload attachment = modifikasi aset → butuh asset.update (PDF bag. 12)
        $this->authorize('update', $asset);

        $file = $request->file('file');
        $path = $file->store("attachments/{$asset->id}", 'public');

        $asset->attachments()->create([
            'uploaded_by' => auth()->id(),
            'file_path' => $path,
            'type' => $request->validated('type'),
            'title' => $request->validated('title') ?: $file->getClientOriginalName(),
            'metadata' => [
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ],
        ]);

        return back()->with('success', 'Attachment berhasil diunggah.');
    }

    public function destroy(AssetAttachment $attachment)
    {
        $this->authorize('update', $attachment->asset);

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return back()->with('success', 'Attachment dihapus.');
    }

    /** Jadikan foto/video ini cover halaman produk */
    public function cover(AssetAttachment $attachment)
    {
        $this->authorize('update', $attachment->asset);

        AssetAttachment::where('asset_id', $attachment->asset_id)->update(['is_cover' => false]);
        $attachment->update(['is_cover' => true]);

        return back()->with('success', "Cover halaman produk: {$attachment->title}.");
    }
}