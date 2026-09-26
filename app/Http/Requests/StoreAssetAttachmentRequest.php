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
            // Batasan tipe & ukuran (PDF bag. 12): maks 5MB, tipe terkontrol
            'file' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf'],
            'type' => ['required', 'in:photo,manual,document'],
            'title' => ['nullable', 'string', 'max:255'],
        ];
    }
}