<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FingerprintImport extends Model
{
    protected $fillable = [
        'period_id', 'site_code', 'format', 'original_filename', 'stored_path',
        'status', 'rows_total', 'rows_matched', 'rows_unmatched',
        'parse_errors', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return ['parse_errors' => 'array'];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AttendancePeriod::class, 'period_id');
    }

    public function scans(): HasMany
    {
        return $this->hasMany(FingerprintScan::class, 'import_id');
    }
}
