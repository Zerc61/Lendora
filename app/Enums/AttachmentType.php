<?php
// app/Enums/AttachmentType.php
namespace App\Enums;

enum AttachmentType: string
{
    case Photo = 'photo';
    case Manual = 'manual';
    case Document = 'document';
}