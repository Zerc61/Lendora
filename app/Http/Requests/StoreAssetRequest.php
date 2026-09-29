<?php

// app/Http/Requests/StoreAssetRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // via AssetPolicy
    }

    public function rules(): array
    {
        return [
            'asset_type_id' => ['required', 'exists:asset_types,id'],
            'asset_code' => ['nullable', 'string', 'max:50', 'unique:assets,asset_code'], // kosong = auto-generate
            'serial_number' => ['nullable', 'string', 'max:100', 'unique:assets,serial_number'],
            // reserved & borrowed TIDAK boleh dipilih saat create — hanya lewat workflow reservasi/checkout (state machine)
            'status' => ['required', 'in:available,maintenance,damaged,lost,retired'],
            'condition' => ['required', 'in:excellent,good,fair,poor,broken'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'purchase_date' => ['nullable', 'date', 'before_or_equal:today'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'warranty_until' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
