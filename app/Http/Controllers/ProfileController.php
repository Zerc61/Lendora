<?php
// app/Http/Controllers/ProfileController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        return view('profile.index', ['user' => $request->user()]);
    }

    /** Ganti foto profil: unggah gambar baru, atau hapus bila tombol "hapus" ditekan. */
    public function updatePhoto(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
        ], [
            'photo.mimes' => 'Foto harus berformat JPG, PNG, atau WebP.',
            'photo.max' => 'Ukuran foto maksimal 2 MB.',
        ]);

        // Hapus foto lama (dan file-nya) bila diminta atau diganti.
        if ($request->boolean('remove_photo')) {
            if ($user->photo_path) {
                Storage::disk('public')->delete($user->photo_path);
            }
            $user->update(['photo_path' => null]);

            return back()->with('success', 'Foto profil dihapus.');
        }

        if ($request->hasFile('photo')) {
            if ($user->photo_path) {
                Storage::disk('public')->delete($user->photo_path);
            }
            $user->update([
                'photo_path' => $request->file('photo')->store('avatars', 'public'),
            ]);

            return back()->with('success', 'Foto profil berhasil diperbarui.');
        }

        return back()->with('error', 'Pilih gambar terlebih dahulu.');
    }

    /** Data akun yang boleh diubah sendiri oleh pengguna. */
    public function updateIdentity(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'phone' => ['nullable', 'string', 'max:25'],
            'address' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:500'],
        ]);

        $user->update($data);

        return back()->with('success', 'Data profil berhasil diperbarui.');
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
