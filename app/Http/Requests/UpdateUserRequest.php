<?php
// app/Http/Requests/UpdateUserRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user')->id)],
            'password' => ['nullable', 'string', 'min:8'], // kosongkan = tidak ganti password
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'status' => ['required', 'in:active,inactive'],
            'role' => ['required', Rule::exists('roles', 'name')],
        ];
    }
}