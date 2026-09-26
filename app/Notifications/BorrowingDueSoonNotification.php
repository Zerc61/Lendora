<?php
// app/Notifications/BorrowingDueSoonNotification.php

namespace App\Notifications;

use App\Models\Borrowing;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BorrowingDueSoonNotification extends Notification
{
    use Queueable;

    public function __construct(private Borrowing $borrowing) {}

    public function via($notifiable): array
    {
        return $notifiable->wantsMail() ? ['mail', 'database'] : ['database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Pengingat: {$this->borrowing->code} jatuh tempo besok")
            ->greeting("Halo {$notifiable->name},")
            ->line('Peminjaman ' . $this->borrowing->code . ' harus dikembalikan pada '
                . $this->borrowing->due_at->format('d M Y H:i') . '.')
            ->action('Lihat Peminjaman', route('my.borrowings.show', $this->borrowing));
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => "⏰ Peminjaman {$this->borrowing->code} jatuh tempo besok",
            'message' => 'Batas kembali: ' . $this->borrowing->due_at->format('d M Y H:i'),
            'url' => route('my.borrowings.show', $this->borrowing),
            'borrowing_id' => $this->borrowing->id,
        ];
    }
}
