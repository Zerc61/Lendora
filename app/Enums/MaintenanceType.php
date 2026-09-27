<?php
// app/Enums/MaintenanceType.php
namespace App\Enums;

enum MaintenanceType: string
{
    case Preventive = 'preventive';
    case Corrective = 'corrective';
    case Inspection = 'inspection';

    public function label(): string
    {
        return match ($this) {
            self::Preventive => 'Preventif',
            self::Corrective => 'Korektif',
            self::Inspection => 'Inspeksi',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Preventive => 'accent',
            self::Corrective => 'bad',
            self::Inspection => 'info',
        };
    }
}
