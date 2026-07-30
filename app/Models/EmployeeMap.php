<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeMap extends Model
{
    protected $fillable = [
        'fingerprint_pin', 'fingerprint_nip', 'nik', 'hero_employee_uuid',
        'site_code', 'active', 'note',
    ];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_code', 'code');
    }
}
