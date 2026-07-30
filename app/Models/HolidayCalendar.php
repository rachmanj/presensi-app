<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HolidayCalendar extends Model
{
    protected $fillable = ['date', 'type', 'description', 'year'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }
}
