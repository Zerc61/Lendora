<?php

namespace App\Enums;

enum Gender: string
{
    case Male = 'male';
    case Female = 'female';

    public function label(): string
    {
        return match ($this) {
            self::Male => 'Laki-laki',
            self::Female => 'Perempuan',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Male => 'L',
            self::Female => 'P',
        };
    }
}
