<?php
// app/Enums/InspectionStage.php
namespace App\Enums;

enum InspectionStage: string
{
    case Checkout = 'checkout';
    case CheckIn = 'checkin';

    public function label(): string
    {
        return match ($this) {
            self::Checkout => 'Kondisi Awal',
            self::CheckIn => 'Kondisi Akhir',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Checkout => 'info',
            self::CheckIn => 'ok',
        };
    }
}
