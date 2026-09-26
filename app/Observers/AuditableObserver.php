<?php
// app/Observers/AuditableObserver.php

namespace App\Observers;

use App\Models\Asset;
use App\Models\Borrowing;
use App\Models\Issue;
use App\Models\MaintenanceTicket;
use App\Models\Reservation;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;

class AuditableObserver
{
    /**
     * Pemetaan transisi status → nama aksi semantik (PDF 4L:
     * approve/reject, checkout, check-in, status changes).
     */
    private const SEMANTIC_ACTIONS = [
        Borrowing::class => [
            'pending>borrowed'  => 'checkout',
            'borrowed>returned' => 'checkin',
            'borrowed>overdue'  => 'overdue',
            'pending>approved'  => 'borrowing_approved',
            'pending>cancelled' => 'borrowing_cancelled',
        ],
        Reservation::class => [
            'pending>approved'   => 'reservation_approved',
            'pending>rejected'   => 'reservation_rejected',
            'pending>cancelled'  => 'reservation_cancelled',
            'approved>cancelled' => 'reservation_cancelled',
            'approved>expired'   => 'reservation_expired',
            'pending>expired'    => 'reservation_expired',
            'approved>fulfilled' => 'reservation_fulfilled',
        ],
        Asset::class => ['*' => 'asset_status_changed'],
        MaintenanceTicket::class => ['*' => 'ticket_status_changed'],
        Issue::class => ['*' => 'issue_status_changed'],
    ];

    public function created(Model $model): void
    {
        AuditLogger::log('created', $model, [], AuditLogger::payload($model));
    }

    public function updated(Model $model): void
    {
        $changes = collect($model->getChanges())->except(['updated_at']);

        if ($changes->isEmpty()) {
            return;
        }

        $action = 'updated';

        if ($model->wasChanged('status')) {
            $from = (string) $model->getRawOriginal('status');
            $to = $model->status instanceof \BackedEnum ? $model->status->value : (string) $model->status;
            $action = $this->semanticAction($model, "{$from}>{$to}");
        }

        $before = $changes->mapWithKeys(fn ($v, $k) => [$k => $model->getRawOriginal($k)])->all();

        AuditLogger::log($action, $model, $before, $changes->all());
    }

    public function deleted(Model $model): void
    {
        AuditLogger::log('deleted', $model, AuditLogger::payload($model), []);
    }

    public function restored(Model $model): void
    {
        AuditLogger::log('restored', $model, [], AuditLogger::payload($model));
    }

    private function semanticAction(Model $model, string $transition): string
    {
        $map = self::SEMANTIC_ACTIONS[$model::class] ?? [];

        return $map[$transition] ?? ($map['*'] ?? 'status_changed');
    }
}
