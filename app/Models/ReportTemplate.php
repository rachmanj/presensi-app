<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportTemplate extends Model
{
    protected $fillable = ['name', 'site_profile', 'column_layout', 'footer_config', 'signature_config'];

    protected function casts(): array
    {
        return [
            'column_layout' => 'array',
            'footer_config' => 'array',
            'signature_config' => 'array',
        ];
    }

    public function attendanceSheets(): HasMany
    {
        return $this->hasMany(AttendanceSheet::class);
    }
}
