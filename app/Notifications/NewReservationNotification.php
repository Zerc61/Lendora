<?php
// app/Notifications/NewReservationNotification.php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewReservationNotification extends Notification
{
    use Queueable;

    public function __construct(private Reservation $reservation) {}

    public function via($notifiable): array
    {
        return $notifiable->wantsMail() ? ['mail', 'database'] : ['database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Pengajuan Reservasi Baru: {$this->reservation->code}")
            ->greeting("Halo {$notifiable->name},")
            ->line("{$this->reservation->user->name} mengajukan reservasi {$this->reservation->code}.")
            ->line('Perlu persetujuan Anda.')
            ->action('Review Reservasi', route('admin.reservations.show', $this->reservation));
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => "🗓️ Pengajuan baru: {$this->reservation->code}",
            'message' => "{$this->reservation->user->name} mengajukan reservasi — menunggu persetujuan Anda.",
            'url' => route('admin.reservations.show', $this->reservation),
        ];
    }
}
