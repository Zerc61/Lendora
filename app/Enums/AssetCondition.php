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
}