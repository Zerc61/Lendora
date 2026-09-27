<?php

// app/Models/Program.php
namespace App\Models;

use App\Enums\EducationLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Program extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id', 'education_level', 'name', 'code', 'description',
    ];

    protected function casts(): array
    {
        return [
            'education_level' => EducationLevel::class,
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function schoolClasses(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(User::class, 'school_class_id', 'id')
            ->whereHas('schoolClass');
    }

    /** "SMA · IPA" — dipakai di breadcrumb & filter. */
    public function fullLabel(): string
    {
        return $this->education_level->label().' · '.$this->name;
    }
}
