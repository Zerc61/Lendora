<?php

// app/Http/Controllers/HomeController.php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

/**
 * Pengalihan root "/" ke dashboard bila sudah masuk, ke login bila belum.
 *
 * Dipisah dari closure langsung di routes/web.php karena `php artisan
 * route:cache` tidak bisa melakukan serialisasi route yang memakai Closure —
 *oni build akan gagal tepat saat membuat image container.
 */
class HomeController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        return auth()->check()
            ? redirect()->route('dashboard')
            : redirect()->route('login');
    }
}
