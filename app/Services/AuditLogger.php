<?php
// app/Services/AuditLogger.php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    /**
     * PDF 4L: simpan actor, timestamp, action, target, metadata.
     * Aman dipanggil dari console (actor null).
     */
    public static function log(string $action, Model $subject, array $before = [], array $after = []): void
    {
        $actor = auth()->user();

        AuditLog::create([
            'organization_id' => $actor->organization_id ?? ($subject->organization_id ?? null),
            'actor_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'before_json' => $before ?: null,
            'after_json' => $after ?: null,
            'ip_address' => request()?->ip(),
            'user_agent' => substr((string) request()?->userAgent(), 0, 500),
        ]);
    }

    /** Payload aman untuk audit (buang kolom sensitif/besar) */
    public static function payload(Model $subject): array
    {
        return collect($subject->getAttributes())
            ->except(['password', 'remember_token', 'metadata'])
            ->all();
    }
}
