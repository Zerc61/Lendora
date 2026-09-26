<?php
// app/Http/Requests/StoreReservationRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga middleware permission:reservation.create
    }

    public function rules(): array
    {
        return [
            'start_at' => ['required', 'date', 'after_or_equal:today'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'purpose' => ['required', 'string', 'min:10', 'max:1000'],
            'asset_ids' => ['required', 'array', 'min:1'],
            'asset_ids.*' => ['integer', 'exists:assets,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'purpose.min' => 'Tujuan peminjaman minimal 10 karakter.',
            'end_at.after' => 'Waktu selesai harus setelah waktu mulai.',
        ];
    }
}
