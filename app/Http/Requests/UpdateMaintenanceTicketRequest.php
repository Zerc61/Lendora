<?php
// app/Http/Requests/UpdateMaintenanceTicketRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMaintenanceTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // via MaintenanceTicketPolicy@update (hanya status open)
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:preventive,corrective,inspection'],
            'priority' => ['required', 'in:low,medium,high,critical'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'scheduled_at' => ['nullable', 'date'],
        ];
    }
}
