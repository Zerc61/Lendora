<?php
// app/Models/Issue.php
namespace App\Models;

use App\Enums\IssueSeverity;
use App\Enums\IssueStatus;
use App\Enums\IssueType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Issue extends Model
{
    protected $fillable = [
        'code', 'organization_id', 'asset_id', 'borrowing_id', 'reported_by',
        'type', 'severity', 'status', 'description', 'resolution', 'resolved_by', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => IssueType::class,
            'severity' => IssueSeverity::class,
            'status' => IssueStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function borrowing(): BelongsTo { return $this->belongsTo(Borrowing::class); }
    public function reportedBy(): BelongsTo { return $this->belongsTo(User::class, 'reported_by'); }
    public function resolvedBy(): BelongsTo { return $this->belongsTo(User::class, 'resolved_by'); }
}