<?php
// app/Enums/InspectionStage.php
namespace App\Enums;

enum InspectionStage: string
{
    case Checkout = 'checkout';
    case CheckIn = 'checkin';
}