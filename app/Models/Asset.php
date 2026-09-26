<?php
// app/Models/Asset.php
namespace App\Models;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id', 'asset_type_id', 'location_id', 'asset_code',
        'serial_number', 'status', 'condition', 'purchase_date',
        'purchase_price', 'warranty_until', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => AssetStatus::class,
            'condition' => AssetCondition::class,
            'purchase_date' => 'date',
            'warranty_until' => 'date',
            'purchase_price' => 'decimal:2',
        ];
    }

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function assetType(): BelongsTo { return $this->belongsTo(AssetType::class); }
    public function location(): BelongsTo { return $this->belongsTo(Location::class); }
    public function reservationItems(): HasMany { return $this->hasMany(ReservationItem::class); }
    public function borrowingItems(): HasMany { return $this->hasMany(BorrowingItem::class); }
    public function inspections(): HasMany { return $this->hasMany(AssetInspection::class); }
    public function attachments(): HasMany { return $this->hasMany(AssetAttachment::class); }
    public function maintenanceTickets(): HasMany { return $this->hasMany(MaintenanceTicket::class); }
    public function issues(): HasMany { return $this->hasMany(Issue::class); }

    // ── Helper bisnis ───────────────────────────────────────

    public function isAvailable(): bool
    {
        return $this->status === AssetStatus::Available;
    }

    /** Asset maintenance/damaged/lost/retired TIDAK boleh dipinjam (PDF bag. 13) */
    public function isBorrowable(): bool
    {
        return in_array($this->status, [AssetStatus::Available, AssetStatus::Reserved], true);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', AssetStatus::Available);
    }

    public function scopeInOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }
}