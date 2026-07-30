<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceRow extends Model
{
    protected $fillable = [
        'sheet_id', 'nik', 'employee_name', 'position',
        'home_site_code', 'working_days', 'summary',
    ];

    protected function casts(): array
    {
        return ['summary' => 'array'];
    }

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(AttendanceSheet::class, 'sheet_id');
    }

    public function cells(): HasMany
    {
        return $this->hasMany(AttendanceCell::class, 'row_id');
    }
}
