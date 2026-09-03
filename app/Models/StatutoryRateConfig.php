<?php

namespace App\Models;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class StatutoryRateConfig extends Model
{
    use Auditable;

    protected $fillable = [
        'school_id', 'type', 'name', 'config', 'effective_from', 'effective_to', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * A school-specific rate (school_id matching $schoolId) wins over the
     * global default (school_id null) when both are active and in their
     * effective-date window for $asOfDate - same precedence pattern as
     * GlAccountMapping::resolve().
     */
    public static function activeFor(?int $schoolId, string $type, ?string $asOfDate = null): ?self
    {
        $asOfDate ??= now()->toDateString();

        return static::where('type', $type)
            ->where('is_active', true)
            ->where('effective_from', '<=', $asOfDate)
            ->where(function ($q) use ($asOfDate) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $asOfDate);
            })
            ->where(function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId)->orWhereNull('school_id');
            })
            ->orderByRaw('school_id IS NULL')
            ->orderByDesc('effective_from')
            ->first();
    }
}
