<?php
// app/Enums/OrganizationStatus.php
namespace App\Enums;

enum OrganizationStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Inactive => 'Nonaktif',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Active => 'ok',
            self::Inactive => 'muted',
        };
    }
}
