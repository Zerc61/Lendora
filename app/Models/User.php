<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'organization_id', 'school_class_id', 'name', 'email', 'password', 'status',
        'notification_preferences', 'photo_path', 'identity_number', 'birth_date',
        'gender', 'phone', 'address', 'bio',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'notification_preferences' => 'array',
            'birth_date' => 'date',
            'gender' => Gender::class,
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    /** Transaksi peminjaman milik pengguna ini (sebagai peminjam). */
    public function borrowings(): HasMany
    {
        return $this->hasMany(Borrowing::class, 'borrower_id');
    }

    /** Pengajuan reservasi milik pengguna ini. */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function isStudent(): bool
    {
        return $this->school_class_id !== null;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    /** PDF 4N: preference per user — default email aktif */
    public function wantsMail(): bool
    {
        return ($this->notification_preferences['email_enabled'] ?? true) === true;
    }

    /**
     * Role utama untuk kebutuhan layout & navigasi.
     * Super-admin memakai shell "admin" (konsol penuh).
     */
    public function primaryRole(): string
    {
        foreach (['super-admin', 'admin', 'staff', 'technician', 'borrower'] as $role) {
            if ($this->hasRole($role)) {
                return $role === 'super-admin' ? 'admin' : $role;
            }
        }

        return 'borrower';
    }

    public function roleLabel(): string
    {
        return match ($this->primaryRole()) {
            'admin' => $this->hasRole('super-admin') ? 'Super Admin' : 'Administrator',
            'staff' => 'Staf Operasional',
            'technician' => 'Teknisi',
            default => 'Peminjam',
        };
    }

    /** Inisial untuk avatar. */
    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $letters = mb_strtoupper(mb_substr($parts[0] ?? '?', 0, 1));

        if (count($parts) > 1) {
            $letters .= mb_strtoupper(mb_substr(end($parts), 0, 1));
        }

        return $letters;
    }

    /**
     * URL foto profil, atau null bila belum diunggah.
     *
     * Memakai disk default (Storage::url), bukan disk('public') hardcoded:
     * upload ditulis lewat disk default juga (lihat ProfileController,
     * UserController, TAHAP G) sehingga satu sumber kebenaran. Env
     * menentukan disk-nya: FILESYSTEM_DISK=public di lokal (diserve lewat
     * symlink public/storage) dan FILESYSTEM_DISK=s3 di produksi.
     */
    public function photoUrl(): ?string
    {
        return $this->photo_path ? Storage::url($this->photo_path) : null;
    }

    /**
     * Foto profil pernah diunggah (ada path-nya), tanpa stat filesystem.
     *
     * Sebelumnya fungsi ini memanggil Storage::exists() — satu panggilan
     * stat ke disk per render avatar. Di Vercel itu berarti akses filesystem
     * (atau HTTP ke object storage) per avatar di SETIAP halaman, padahal
     * nilainya jarang berubah. Sekadar mengecek path lebih murah dan hasilnya
     * setara dalam praktik: file dihapus lewat satu-satunya pintu (handler
     * hapus/ganti foto) yang sekaligus menghapus path-nya, jadi path basi
     * hanya bisa muncul bila storage pihak luar diutak-atik.
     */
    public function hasPhoto(): bool
    {
        return filled($this->photo_path);
    }
}
