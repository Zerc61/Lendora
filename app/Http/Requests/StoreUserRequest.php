<?php
// app/Http/Requests/StoreUserRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'status' => ['required', 'in:active,inactive'],
            'role' => ['required', Rule::exists('roles', 'name')],

            // Data siswa (opsional — hanya relevan untuk peran borrower)
            'school_class_id' => ['nullable', 'exists:school_classes,id'],
            'identity_number' => ['nullable', 'string', 'max:40'],
            'gender' => ['nullable', Rule::in(array_column(\App\Enums\Gender::cases(), 'value'))],
            'birth_date' => ['nullable', 'date'],
            'phone' => ['nullable', 'string', 'max:25'],
            'address' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:500'],
            'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.mimes' => 'Foto harus berformat JPG, PNG, atau WebP.',
            'photo.max' => 'Ukuran foto maksimal 2 MB.',
            'gender.in' => 'Jenis kelamin tidak valid.',
        ];
    }
}