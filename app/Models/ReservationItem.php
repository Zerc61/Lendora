<?php
// app/Models/ReservationItem.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationItem extends Model
{
    protected $fillable = ['reservation_id', 'asset_id', 'asset_type_id', 'quantity'];

    public function reservation(): BelongsTo { return $this->belongsTo(Reservation::class); }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function assetType(): BelongsTo { return $this->belongsTo(AssetType::class); }
}