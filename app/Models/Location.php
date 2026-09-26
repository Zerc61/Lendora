<?php
// app/Models/Location.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use SoftDeletes;

    protected $fillable = ['organization_id', 'parent_id', 'name', 'description'];

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function parent(): BelongsTo { return $this->belongsTo(Location::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(Location::class, 'parent_id'); }
    public function assets(): HasMany { return $this->hasMany(Asset::class); }
}
