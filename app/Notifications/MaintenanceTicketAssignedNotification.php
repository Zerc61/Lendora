<?php
// app/Notifications/MaintenanceTicketAssignedNotification.php

namespace App\Notifications;

use App\Models\MaintenanceTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MaintenanceTicketAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(private MaintenanceTicket $ticket) {}

    public function via($notifiable): array
    {
        return $notifiable->wantsMail() ? ['mail', 'database'] : ['database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Tiket {$this->ticket->code} ditugaskan kepada Anda")
            ->greeting("Halo {$notifiable->name},")
            ->line("Anda ditugaskan mengerjakan {$this->ticket->code} untuk unit {$this->ticket->asset->asset_code}.")
            ->action('Buka Tiket', route('admin.tickets.show', $this->ticket));
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => "🔧 Tiket {$this->ticket->code} ditugaskan ke Anda",
            'message' => "Unit {$this->ticket->asset->asset_code} — {$this->ticket->type->value}.",
            'url' => route('admin.tickets.show', $this->ticket),
        ];
    }
}
