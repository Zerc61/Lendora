<?php
// app/Http/Requests/StoreIssueRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga permission:issue.create di route
    }

    public function rules(): array
    {
        return [
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
            'type' => ['required', 'in:damage,loss,other'],
            'severity' => ['required', 'in:low,medium,high,critical'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }
}
