<?php
// app/Models/MaintenanceTicket.php
namespace App\Models;

use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceStatus;
use App\Enums\MaintenanceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceTicket extends Model
{
    protected $fillable = [
        'code', 'organization_id', 'asset_id', 'issue_id', 'reported_by', 'technician_id',
        'type', 'priority', 'status', 'description', 'diagnosis', 'cost', 'scheduled_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => MaintenanceType::class,
            'priority' => MaintenancePriority::class,
            'status' => MaintenanceStatus::class,
            'cost' => 'decimal:2',
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function issue(): BelongsTo { return $this->belongsTo(Issue::class); }
    public function reportedBy(): BelongsTo { return $this->belongsTo(User::class, 'reported_by'); }
    public function technician(): BelongsTo { return $this->belongsTo(User::class, 'technician_id'); }
    public function logs(): HasMany { return $this->hasMany(MaintenanceLog::class); }
}