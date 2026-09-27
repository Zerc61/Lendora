<?php

namespace App\Models;

use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected $fillable = [
        'organization_id', 'name', 'email', 'password', 'status', 'notification_preferences',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'notification_preferences' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
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
}
