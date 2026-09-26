<?php
// app/Models/Borrowing.php
namespace App\Models;

use App\Enums\BorrowingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Borrowing extends Model
{
    protected $fillable = [
        'code', 'organization_id', 'reservation_id', 'borrower_id', 'approved_by',
        'status', 'purpose', 'checked_out_at', 'due_at', 'returned_at',
        'checked_out_by', 'checked_in_by', 'checkin_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => BorrowingStatus::class,
            'checked_out_at' => 'datetime',
            'due_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function reservation(): BelongsTo { return $this->belongsTo(Reservation::class); }
    public function borrower(): BelongsTo { return $this->belongsTo(User::class, 'borrower_id'); }
    public function approvedBy(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function checkedOutBy(): BelongsTo { return $this->belongsTo(User::class, 'checked_out_by'); }
    public function checkedInBy(): BelongsTo { return $this->belongsTo(User::class, 'checked_in_by'); }
    public function items(): HasMany { return $this->hasMany(BorrowingItem::class); }
    public function inspections(): HasMany { return $this->hasMany(AssetInspection::class); }
    public function issues(): HasMany { return $this->hasMany(Issue::class); }

    public function isOverdue(): bool
    {
        return $this->status === BorrowingStatus::Borrowed
            && $this->due_at !== null
            && $this->due_at->isPast();
    }
}