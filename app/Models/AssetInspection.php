<?php
// app/Models/AssetInspection.php
namespace App\Models;

use App\Enums\AssetCondition;
use App\Enums\InspectionStage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetInspection extends Model
{
    protected $fillable = ['asset_id', 'borrowing_id', 'inspector_id', 'stage', 'condition', 'checklist', 'notes', 'inspected_at'];

    protected function casts(): array
    {
        return [
            'stage' => InspectionStage::class,
            'condition' => AssetCondition::class,
            'checklist' => 'array',
            'inspected_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function borrowing(): BelongsTo { return $this->belongsTo(Borrowing::class); }
    public function inspector(): BelongsTo { return $this->belongsTo(User::class, 'inspector_id'); }
}