<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatrixRule extends Model
{
    protected $fillable = [
        'home_site_code', 'visit_site_code', 'code', 'priority',
        'effective_from', 'effective_to',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    public function homeSite(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'home_site_code', 'code');
    }

    public function visitSite(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'visit_site_code', 'code');
    }

    public function scopeCurrent(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query
            ->where('effective_from', '<=', $today)
            ->where(fn (Builder $q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $today));
    }
}
