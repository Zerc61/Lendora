<?php
// app/Models/Organization.php
namespace App\Models;

use App\Enums\OrganizationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
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

    // ── Struktur sekolah ──
    public function programs(): HasMany { return $this->hasMany(Program::class); }
    public function schoolClasses(): HasManyThrough
    {
        return $this->hasManyThrough(SchoolClass::class, Program::class);
    }
    public function classrooms(): HasMany { return $this->hasMany(Classroom::class); }

    /**
     * Ringkasan untuk halaman detail: jenjang → jumlah program & kelas.
     *
     * Penting: `withCount('students')` di dalam sini, bukan Expectations caller
     * — method ini menjalankan query sendiri sehingga relasi yang sudah
     * di-eager-load di luar akan terbuang dan jumlah siswa tampil 0.
     */
    public function schoolSummary(): array
    {
        $levels = $this->programs()
            ->with(['schoolClasses' => fn ($q) => $q->withCount('students')])
            ->get()
            ->groupBy(fn ($p) => $p->education_level->value);

        return $levels->map(fn ($programs, $level) => [
            'level' => $programs->first()->education_level,
            'programs' => $programs,
            'class_count' => $programs->sum(fn ($p) => $p->schoolClasses->count()),
            'student_count' => $programs->sum(fn ($p) => $p->schoolClasses->sum('students_count')),
        ])->values()->all();
    }
}