<?php
// app/Enums/IssueSeverity.php
namespace App\Enums;

enum IssueSeverity: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Rendah',
            self::Medium => 'Sedang',
            self::High => 'Tinggi',
            self::Critical => 'Kritis',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Low => 'muted',
            self::Medium => 'warn',
            self::High => 'orange',
            self::Critical => 'bad',
        };
    }

    public function weight(): int
    {
        return match ($this) {
            self::Low => 1,
            self::Medium => 2,
            self::High => 3,
            self::Critical => 4,
        };
    }
}
