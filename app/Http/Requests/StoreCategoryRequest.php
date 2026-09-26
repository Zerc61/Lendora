<?php
// app/Http/Requests/StoreCategoryRequest.php

namespace App\Http\Requests;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga permission middleware di route
    }

    public function rules(): array
    {
        $orgId = auth()->user()->organization_id ?? Organization::query()->value('id');

        return [
            'name' => ['required', 'string', 'max:255',
                Rule::unique('categories', 'name')->where(fn ($q) => $q->where('organization_id', $orgId))],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}