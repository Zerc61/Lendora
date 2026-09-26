<?php
// app/Http/Requests/StoreMaintenanceTicketRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga permission:maintenance.create di route
    }

    public function rules(): array
    {
        return [
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
            'issue_id' => ['nullable', 'integer', 'exists:issues,id'],
            'type' => ['required', 'in:preventive,corrective,inspection'],
            'priority' => ['required', 'in:low,medium,high,critical'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'scheduled_at' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }
}
