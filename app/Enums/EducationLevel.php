<?php

namespace App\Enums;

enum EducationLevel: string
{
    case SD = 'sd';
    case SMP = 'smp';
    case SMA = 'sma';
    case SMK = 'smk';
    case MA = 'ma';

    public function label(): string
    {
        return match ($this) {
            self::SD => 'SD',
            self::SMP => 'SMP',
            self::SMA => 'SMA',
            self::SMK => 'SMK',
            self::MA => 'MA',
        };
    }

    /** Rombongan kelas bernomor dipakai mulai SMP ke atas. */
    public function hasNumberedClasses(): bool
    {
        return $this !== self::SD;
    }

    public function tone(): string
    {
        return match ($this) {
            self::SD => 'accent',
            self::SMP => 'info',
            self::SMA => 'brand',
            self::SMK => 'orange',
            self::MA => 'muted',
        };
    }
}
