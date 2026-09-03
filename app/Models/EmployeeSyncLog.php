<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeSyncLog extends Model
{
    protected $fillable = [
        'school_id', 'run_type', 'sync_mode', 'status', 'records_fetched',
        'records_created', 'records_updated', 'records_failed',
        'started_at', 'finished_at', 'triggered_by', 'error_message',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function triggeredBy()
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function exceptions()
    {
        return $this->hasMany(EmployeeSyncException::class);
    }
}
