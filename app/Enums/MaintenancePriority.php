<?php
// app/Enums/MaintenancePriority.php
namespace App\Enums;

enum MaintenancePriority: string
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
            self::Medium => 'info',
            self::High => 'warn',
            self::Critical => 'bad',
        };
    }
}
