<?php
// app/Enums/AssetCondition.php
namespace App\Enums;

enum AssetCondition: string
{
    case Excellent = 'excellent';
    case Good = 'good';
    case Fair = 'fair';
    case Poor = 'poor';
    case Broken = 'broken';

    public function label(): string
    {
        return match ($this) {
            self::Excellent => 'Sangat Baik',
            self::Good => 'Baik',
            self::Fair => 'Cukup Baik',
            self::Poor => 'Buruk',
            self::Broken => 'Rusak Berat',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Excellent, self::Good => 'ok',
            self::Fair => 'warn',
            self::Poor, self::Broken => 'bad',
        };
    }

    /** Skala 0–100 untuk indikator kesehatan aset. */
    public function score(): int
    {
        return match ($this) {
            self::Excellent => 100,
            self::Good => 82,
            self::Fair => 60,
            self::Poor => 34,
            self::Broken => 0,
        };
    }
}
