<?php
// app/Models/BorrowingItem.php
namespace App\Models;

use App\Enums\AssetCondition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BorrowingItem extends Model
{
    protected $fillable = [
        'borrowing_id', 'asset_id', 'quantity',
        'condition_out', 'condition_in', 'notes_out', 'notes_in',
    ];

    protected function casts(): array
    {
        return ['condition_out' => AssetCondition::class, 'condition_in' => AssetCondition::class];
    }

    public function borrowing(): BelongsTo { return $this->belongsTo(Borrowing::class); }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }

    public function inspectionCheckout(): HasOne
    {
        return $this->hasOne(AssetInspection::class, 'borrowing_id', 'borrowing_id')
            ->where('asset_id', $this->asset_id)
            ->where('stage', 'checkout');
    }
}