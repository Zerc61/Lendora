<?php
// app/Enums/OrganizationStatus.php
namespace App\Enums;

enum OrganizationStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}