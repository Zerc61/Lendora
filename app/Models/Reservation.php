<?php
// app/Models/Reservation.php
namespace App\Models;

use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model
{
    protected $fillable = [
        'organization_id', 'user_id', 'code', 'start_at', 'end_at',
        'status', 'purpose', 'approved_by', 'approved_at', 'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReservationStatus::class,
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function approvedBy(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function items(): HasMany { return $this->hasMany(ReservationItem::class); }
    public function borrowing(): HasOne { return $this->hasOne(Borrowing::class); }

    /** Overlap waktu — dipakai conflict detection (PDF bag. 5.2) */
    public function scopeOverlapping(Builder $query, string $startAt, string $endAt): Builder
    {
        return $query->where('start_at', '<', $endAt)->where('end_at', '>', $startAt);
    }
}