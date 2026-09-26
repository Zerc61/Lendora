<?php
// app/Http/Requests/StoreLocationRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:locations,id', Rule::notIn([$this->route('location')?->id ?? 0])], // tidak boleh jadi parent dirinya sendiri
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}