<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSheet extends Model
{
    protected $fillable = ['period_id', 'site_code', 'report_template_id', 'status', 'meta'];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AttendancePeriod::class, 'period_id');
    }

    public function reportTemplate(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class);
    }

    public function rows(): HasMany
    {
        return $this->hasMany(AttendanceRow::class, 'sheet_id');
    }
}
