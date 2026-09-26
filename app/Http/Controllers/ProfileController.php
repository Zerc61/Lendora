<?php
// app/Http/Controllers/ProfileController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        return view('profile.index', ['user' => $request->user()]);
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        $request->user()->update(['password' => $request->validated('password')]); // auto-hash

        return back()->with('success', 'Password berhasil diperbarui.');
    }

    /** PDF 4N: user mengelola preferensi notifikasinya sendiri */
    public function updatePreferences(Request $request)
    {
        $preferences = $request->user()->notification_preferences ?? [];
        $preferences['email_enabled'] = $request->boolean('email_enabled');

        $request->user()->update(['notification_preferences' => $preferences]);

        return back()->with('success', 'Preferensi notifikasi disimpan.');
    }
}
