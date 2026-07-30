<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceCell extends Model
{
    protected $fillable = [
        'row_id', 'work_date', 'day_of_month', 'day_type',
        'auto_code', 'final_code', 'is_overridden',
        'override_by', 'override_reason', 'visit_site_code',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'is_overridden' => 'boolean',
        ];
    }

    public function row(): BelongsTo
    {
        return $this->belongsTo(AttendanceRow::class, 'row_id');
    }

    public function traces(): HasMany
    {
        return $this->hasMany(AttendanceCellTrace::class, 'cell_id');
    }
}
