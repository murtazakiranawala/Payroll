<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeSyncException extends Model
{
    protected $fillable = [
        'employee_sync_log_id', 'external_employee_code', 'payload', 'error_message', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    public function syncLog()
    {
        return $this->belongsTo(EmployeeSyncLog::class, 'employee_sync_log_id');
    }
}
