<?php
// app/Console/Commands/SendWarrantyReminders.php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Models\User;
use App\Notifications\WarrantyExpiringNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SendWarrantyReminders extends Command
{
    protected $signature = 'lendora:send-warranty-reminders';
    protected $description = 'Reminder garansi aset yang habis dalam 30 hari (PDF 4K)';

    public function handle(): void
    {
        $admins = User::role(['admin', 'super-admin'])->get();
        $count = 0;

        Asset::whereNotNull('warranty_until')
            ->whereBetween('warranty_until', [today(), today()->addDays(30)])
            ->chunkById(100, function ($assets) use ($admins, &$count) {
                foreach ($assets as $asset) {
                    $alreadySent = DB::table('notifications')
                        ->where('type', WarrantyExpiringNotification::class)
                        ->where('data->asset_id', $asset->id)
                        ->whereDate('created_at', today())
                        ->exists();

                    if ($alreadySent) {
                        continue;
                    }

                    Notification::send($admins, new WarrantyExpiringNotification($asset));
                    $count++;
                }
            });

        $this->info("{$count} reminder warranty terkirim.");
    }
}
