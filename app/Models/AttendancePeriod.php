<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendancePeriod extends Model
{
    protected $fillable = ['year', 'month', 'label', 'status', 'finalized_at'];

    protected function casts(): array
    {
        return ['finalized_at' => 'datetime'];
    }

    public function sheets(): HasMany
    {
        return $this->hasMany(AttendanceSheet::class, 'period_id');
    }

    public function fingerprintImports(): HasMany
    {
        return $this->hasMany(FingerprintImport::class, 'period_id');
    }
}
