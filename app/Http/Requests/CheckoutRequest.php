<?php
// app/Http/Requests/CheckoutRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga middleware permission:checkout.perform
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array'],
            'items.*.condition_out' => ['required', 'in:excellent,good,fair,poor,broken'],
            'items.*.notes_out' => ['nullable', 'string', 'max:500'],
        ];
    }
}
