<?php
// app/Notifications/ReservationStatusNotification.php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationStatusNotification extends Notification
{
    use Queueable;

    public function __construct(private Reservation $reservation, private string $status, private ?string $reason = null) {}

    public function via($notifiable): array
    {
        return $notifiable->wantsMail() ? ['mail', 'database'] : ['database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $approved = $this->status === 'approved';

        return (new MailMessage)
            ->subject($approved ? "Reservasi {$this->reservation->code} Disetujui" : "Reservasi {$this->reservation->code} Ditolak")
            ->greeting("Halo {$notifiable->name},")
            ->line($approved
                ? 'Reservasi Anda telah disetujui. Silakan datang ke lokasi untuk proses check-out.'
                : 'Mohon maaf, reservasi Anda ditolak.')
            ->when($this->reason, fn ($mail) => $mail->line("Alasan: {$this->reason}"))
            ->action('Lihat Reservasi', route('my.reservations.show', $this->reservation));
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => $this->status === 'approved'
                ? "✅ Reservasi {$this->reservation->code} disetujui"
                : "❌ Reservasi {$this->reservation->code} ditolak",
            'message' => $this->reason ?: 'Silakan cek detail reservasi Anda.',
            'url' => route('my.reservations.show', $this->reservation),
        ];
    }
}
