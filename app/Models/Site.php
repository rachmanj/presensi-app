<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    protected $fillable = ['code', 'name', 'profile', 'base_present_code', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function matrixRulesAsHome(): HasMany
    {
        return $this->hasMany(MatrixRule::class, 'home_site_code', 'code');
    }

    public function siteDaytypeCodes(): HasMany
    {
        return $this->hasMany(SiteDaytypeCode::class, 'site_code', 'code');
    }

    public function employeeMaps(): HasMany
    {
        return $this->hasMany(EmployeeMap::class, 'site_code', 'code');
    }

    public function attendanceSheets(): HasMany
    {
        return $this->hasMany(AttendanceSheet::class, 'site_code', 'code');
    }
}
