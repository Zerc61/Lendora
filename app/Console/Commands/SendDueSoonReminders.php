<?php
// app/Console/Commands/SendDueSoonReminders.php

namespace App\Console\Commands;

use App\Enums\BorrowingStatus;
use App\Models\Borrowing;
use App\Notifications\BorrowingDueSoonNotification;
use Illuminate\Console\Command;

class SendDueSoonReminders extends Command
{
    protected $signature = 'lendora:send-due-reminders';
    protected $description = 'Reminder H-1 untuk peminjaman yang akan jatuh tempo (maks 1x/hari)';

    public function handle(): void
    {
        $count = 0;

        Borrowing::where('status', BorrowingStatus::Borrowed)
            ->whereBetween('due_at', [now(), now()->addDay()])
            ->with('borrower')
            ->chunkById(100, function ($borrowings) use (&$count) {
                foreach ($borrowings as $borrowing) {
                    // Dedupe: sudah dikirim hari ini untuk borrowing ini?
                    $alreadySent = $borrowing->borrower->notifications()
                        ->where('type', BorrowingDueSoonNotification::class)
                        ->where('data->borrowing_id', $borrowing->id)
                        ->whereDate('created_at', today())
                        ->exists();

                    if ($alreadySent) {
                        continue;
                    }

                    $borrowing->borrower->notify(new BorrowingDueSoonNotification($borrowing));
                    $count++;
                }
            });

        $this->info("{$count} reminder due-soon terkirim.");
    }
}
