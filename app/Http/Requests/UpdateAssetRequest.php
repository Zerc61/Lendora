<?php
// app/Http/Requests/UpdateAssetRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_type_id' => ['required', 'exists:asset_types,id'],
            'asset_code' => ['required', 'string', 'max:50', Rule::unique('assets', 'asset_code')->ignore($this->route('asset')->id)],
            'serial_number' => ['nullable', 'string', 'max:100', Rule::unique('assets', 'serial_number')->ignore($this->route('asset')->id)],
            // semua status boleh dipilih, TAPI transisinya divalidasi state machine di controller
            'status' => ['required', 'in:available,reserved,borrowed,maintenance,damaged,lost,retired'],
            'condition' => ['required', 'in:excellent,good,fair,poor,broken'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'purchase_date' => ['nullable', 'date', 'before_or_equal:today'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'warranty_until' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}