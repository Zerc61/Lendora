<?php
// app/Http/Requests/StoreAssetAttachmentRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssetAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga authorize('update', $asset) di controller
    }

    public function rules(): array
    {
        return [
            // Foto/dokumen maks 5MB; video maks 50MB (PDF bag. 12: tipe & ukuran dikontrol)
            'file' => ['required', 'file', 'max:51200', 'mimes:jpg,jpeg,png,webp,mp4,webm,mov,pdf'],
            'type' => ['required', 'in:photo,video,manual,document'],
            'title' => ['nullable', 'string', 'max:255'],
        ];
    }
}