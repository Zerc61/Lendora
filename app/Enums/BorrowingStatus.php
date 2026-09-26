<?php
// app/Enums/BorrowingStatus.php
namespace App\Enums;

enum BorrowingStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Borrowed = 'borrowed';
    case Returned = 'returned';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Overdue = 'overdue';
}