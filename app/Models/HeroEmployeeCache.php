<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeroEmployeeCache extends Model
{
    protected $fillable = [
        'nik', 'hero_employee_uuid', 'fullname', 'position',
        'department', 'project_code', 'is_active', 'synced_at', 'raw',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'synced_at' => 'datetime',
            'raw' => 'array',
        ];
    }
}
