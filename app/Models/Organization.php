<?php
// app/Models/Organization.php
namespace App\Models;

use App\Enums\OrganizationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'code', 'status', 'description'];

    protected function casts(): array
    {
        return ['status' => OrganizationStatus::class];
    }

    public function users(): HasMany { return $this->hasMany(User::class); }
    public function categories(): HasMany { return $this->hasMany(Category::class); }
    public function assetTypes(): HasMany { return $this->hasMany(AssetType::class); }
    public function assets(): HasMany { return $this->hasMany(Asset::class); }
    public function locations(): HasMany { return $this->hasMany(Location::class); }
    public function reservations(): HasMany { return $this->hasMany(Reservation::class); }
    public function borrowings(): HasMany { return $this->hasMany(Borrowing::class); }
    public function maintenanceTickets(): HasMany { return $this->hasMany(MaintenanceTicket::class); }
    public function issues(): HasMany { return $this->hasMany(Issue::class); }
    public function auditLogs(): HasMany { return $this->hasMany(AuditLog::class); }
}