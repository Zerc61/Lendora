<?php
// app/Notifications/WarrantyExpiringNotification.php

namespace App\Notifications;

use App\Models\Asset;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WarrantyExpiringNotification extends Notification
{
    use Queueable;

    public function __construct(private Asset $asset) {}

    public function via($notifiable): array
    {
        return $notifiable->wantsMail() ? ['mail', 'database'] : ['database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Garansi {$this->asset->asset_code} segera habis")
            ->greeting("Halo {$notifiable->name},")
            ->line('Garansi unit ' . $this->asset->asset_code . ' (' . $this->asset->assetType->name . ') berakhir pada '
                . $this->asset->warranty_until->format('d M Y') . '.')
            ->action('Detail Aset', route('admin.assets.show', $this->asset));
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => "🛡️ Garansi {$this->asset->asset_code} segera habis",
            'message' => 'Berakhir ' . $this->asset->warranty_until->format('d M Y') . ' (PDF 4K: reminder warranty).',
            'url' => route('admin.assets.show', $this->asset),
            'asset_id' => $this->asset->id,
        ];
    }
}
