<?php

// app/Models/SchoolClass.php
namespace App\Models;

use App\Enums\EducationLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Rombongan belajar (kelas) — anak dari Program.
 *
 * @property-read int $students_count
 */
class SchoolClass extends Model
{
    use SoftDeletes;

    protected $table = 'school_classes';

    protected $fillable = [
        'program_id', 'name', 'school_year', 'capacity', 'homeroom_teacher_id',
    ];

    protected function casts(): array
    {
        return ['capacity' => 'integer'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /** Kelas → program → organisasi. */
    public function organization(): HasOneThrough
    {
        return $this->hasOneThrough(
            Organization::class,
            Program::class,
            'id',              // FK di tabel programs
            'organization_id', // FK di tabel organizations
            'program_id',      // local key di school_classes
            'id',              // owner key di programs
        );
    }

    public function homeroomTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'homeroom_teacher_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** Jenjang diwarisi dari program; butuh relasi program sudah dimuat. */
    public function educationLevel(): ?EducationLevel
    {
        return $this->program?->education_level;
    }

    /** "12/30 siswa" untuk daftar & ringkasan. */
    public function occupancyLabel(): string
    {
        return ($this->students_count ?? 0).'/'.$this->capacity.' siswa';
    }
}
