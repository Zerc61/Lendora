<?php
// app/Http/Requests/StoreAssetTypeRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssetTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}