<?php
// app/Models/Category.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = ['organization_id', 'name', 'description'];

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function assetTypes(): HasMany { return $this->hasMany(AssetType::class); }
}