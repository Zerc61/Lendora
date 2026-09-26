<?php
// app/Enums/IssueType.php
namespace App\Enums;

enum IssueType: string
{
    case Damage = 'damage';
    case Loss = 'loss';
    case Other = 'other';
}