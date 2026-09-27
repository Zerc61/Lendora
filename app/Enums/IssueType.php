<?php
// app/Enums/IssueType.php
namespace App\Enums;

enum IssueType: string
{
    case Damage = 'damage';
    case Loss = 'loss';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Damage => 'Kerusakan',
            self::Loss => 'Kehilangan',
            self::Other => 'Lainnya',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Damage => 'bad',
            self::Loss => 'orange',
            self::Other => 'muted',
        };
    }
}
