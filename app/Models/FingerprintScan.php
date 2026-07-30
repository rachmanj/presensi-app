<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FingerprintScan extends Model
{
    protected $fillable = [
        'import_id', 'raw_pin', 'raw_nip', 'raw_name', 'scan_date',
        'check_in', 'check_out', 'manual_code', 'source_sheet',
        'extra', 'resolved_nik',
    ];

    protected function casts(): array
    {
        return [
            'scan_date' => 'date',
            'extra' => 'array',
        ];
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(FingerprintImport::class, 'import_id');
    }
}
