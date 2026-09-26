<?php
// app/Models/AssetType.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetType extends Model
{
    use SoftDeletes;

    protected $fillable = ['organization_id', 'category_id', 'name', 'brand', 'model', 'description'];

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function assets(): HasMany { return $this->hasMany(Asset::class); }
}