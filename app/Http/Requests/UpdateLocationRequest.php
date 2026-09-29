<?php

// app/Http/Requests/UpdateLocationRequest.php

namespace App\Http\Requests;

class UpdateLocationRequest extends StoreLocationRequest
{
    // Mewarisi rules Store + proteksi self-parent sudah lewat route('location')
}
