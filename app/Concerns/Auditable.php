<?php

namespace App\Concerns;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

/**
 * Applied to the models the BRD calls out as needing a traceable audit trail
 * (payroll cycles, journal vouchers, statutory rate configs, exemptions).
 * Records a before/after snapshot on create/update/delete.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn ($model) => $model->writeAuditLog('created', null, $model->getAttributes()));

        static::updated(function ($model) {
            $model->writeAuditLog('updated', $model->getOriginal(), $model->getChanges());
        });

        static::deleted(fn ($model) => $model->writeAuditLog('deleted', $model->getAttributes(), null));
    }

    protected function writeAuditLog(string $action, ?array $before, ?array $after): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => static::class,
            'auditable_id' => $this->getKey(),
            'before' => $before,
            'after' => $after,
            'ip_address' => request()?->ip(),
        ]);
    }
}
