<?php
// app/Console/Commands/MarkOverdueBorrowings.php

namespace App\Console\Commands;

use App\Enums\BorrowingStatus;
use App\Models\Borrowing;
use App\Models\User;
use App\Notifications\BorrowingOverdueNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class MarkOverdueBorrowings extends Command
{
    protected $signature = 'lendora:mark-overdue-borrowings';
    protected $description = 'Tandai peminjaman melewati due_at sebagai overdue + notifikasi (PDF 4N, bag. 7)';

    public function handle(): void
    {
        $borrowings = Borrowing::where('status', BorrowingStatus::Borrowed)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->with('borrower')
            ->get();

        if ($borrowings->isEmpty()) {
            $this->info('Tidak ada peminjaman overdue.');

            return;
        }

        $admins = User::role(['admin', 'super-admin'])->get();

        foreach ($borrowings as $borrowing) {
            $borrowing->update(['status' => BorrowingStatus::Overdue]); // observer → audit 'overdue'
            $borrowing->borrower->notify(new BorrowingOverdueNotification($borrowing, 'borrower'));
        }

        // Kirim per-peminjaman ke admin agar konteks jelas
        $borrowings->each(fn ($b) => Notification::send($admins, new BorrowingOverdueNotification($b, 'admin')));

        $this->info("{$borrowings->count()} peminjaman ditandai overdue.");
    }
}
