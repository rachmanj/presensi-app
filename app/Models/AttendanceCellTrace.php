<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCellTrace extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['cell_id', 'rule_key', 'explanation', 'inputs'];

    protected function casts(): array
    {
        return ['inputs' => 'array'];
    }

    public function cell(): BelongsTo
    {
        return $this->belongsTo(AttendanceCell::class, 'cell_id');
    }
}
