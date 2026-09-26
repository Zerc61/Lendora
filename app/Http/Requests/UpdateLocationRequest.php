<?php
// app/Http/Requests/UpdateLocationRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLocationRequest extends StoreLocationRequest
{
    // Mewarisi rules Store + proteksi self-parent sudah lewat route('location')
}