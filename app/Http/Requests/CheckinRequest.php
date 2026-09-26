<?php
// app/Http/Requests/CheckinRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga middleware permission:checkin.perform
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array'],
            'items.*.condition_in' => ['required', 'in:excellent,good,fair,poor,broken'],
            'items.*.outcome' => ['required', 'in:ok,damaged,lost'],
            'items.*.notes_in' => ['nullable', 'string', 'max:500', 'required_if:items.*.outcome,damaged,lost'],
            'checkin_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.*.notes_in.required_if' => 'Catatan wajib diisi jika unit rusak/hilang.',
        ];
    }
}
