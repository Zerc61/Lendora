<?php
// app/Http/Requests/UpdateCategoryRequest.php

namespace App\Http\Requests;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $orgId = auth()->user()->organization_id ?? Organization::query()->value('id');

        return [
            'name' => ['required', 'string', 'max:255',
                Rule::unique('categories', 'name')
                    ->where(fn ($q) => $q->where('organization_id', $orgId))
                    ->ignore($this->route('category')->id)],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}