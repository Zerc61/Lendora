<?php
// app/Enums/IssueStatus.php
namespace App\Enums;

enum IssueStatus: string
{
    case Open = 'open';
    case Investigating = 'investigating';
    case Resolved = 'resolved';
    case Rejected = 'rejected';
    case Closed = 'closed';
}