<?php
// app/Notifications/BorrowingOverdueNotification.php

namespace App\Notifications;

use App\Models\Borrowing;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BorrowingOverdueNotification extends Notification
{
    use Queueable;

    /** @param string $audience 'borrower'|'admin' — URL berbeda per penerima */
    public function __construct(private Borrowing $borrowing, private string $audience = 'borrower') {}

    public function via($notifiable): array
    {
        return $notifiable->wantsMail() ? ['mail', 'database'] : ['database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $admin = $this->audience === 'admin';

        return (new MailMessage)
            ->subject("TERLAMBAT: {$this->borrowing->code}")
            ->greeting("Halo {$notifiable->name},")
            ->line($admin
                ? "Peminjaman {$this->borrowing->code} oleh {$this->borrowing->borrower->name} telah melewati batas kembali."
                : "Peminjaman Anda {$this->borrowing->code} telah melewati batas kembali. Segera kembalikan unit.")
            ->action($admin ? 'Tindak Lanjuti' : 'Lihat Peminjaman',
                $admin
                    ? route('admin.borrowings.show', $this->borrowing)
                    : route('my.borrowings.show', $this->borrowing));
    }

    public function toArray($notifiable): array
    {
        $admin = $this->audience === 'admin';

        return [
            'title' => "🚨 Peminjaman {$this->borrowing->code} terlambat",
            'message' => $admin
                ? "Dipinjam oleh {$this->borrowing->borrower->name} — melewati batas kembali."
                : 'Segera kembalikan unit yang Anda pinjam.',
            'url' => $admin
                ? route('admin.borrowings.show', $this->borrowing)
                : route('my.borrowings.show', $this->borrowing),
            'borrowing_id' => $this->borrowing->id,
        ];
    }
}
