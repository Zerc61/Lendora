<?php
// app/Enums/MaintenancePriority.php
namespace App\Enums;

enum MaintenancePriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';
}