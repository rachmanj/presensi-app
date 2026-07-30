<?php

namespace App\Services;

use App\Models\HolidayCalendar;
use Carbon\Carbon;

class DayTypeService
{
    public function classify(Carbon $date, string $siteCode): string
    {
        if ($this->isHoliday($date)) {
            return 'holiday';
        }

        if ($date->isSunday()) {
            return 'sunday';
        }

        if ($date->isSaturday()) {
            return 'saturday';
        }

        // TODO: implement day6/day7 cycle rules for coal sites when spec confirmed
        if ($this->isDay7($date, $siteCode)) {
            return 'day7';
        }

        if ($this->isDay6($date, $siteCode)) {
            return 'day6';
        }

        return 'workday';
    }

    public function isHoliday(Carbon $date): bool
    {
        return HolidayCalendar::whereDate('date', $date->toDateString())->exists();
    }

    private function isDay6(Carbon $date, string $siteCode): bool
    {
        // TODO: coal site 6th working day cycle — stub for Fase 1 MVP
        return false;
    }

    private function isDay7(Carbon $date, string $siteCode): bool
    {
        // TODO: coal site 7th working day cycle — stub for Fase 1 MVP
        return false;
    }

    public function isWeekendOrHoliday(string $dayType): bool
    {
        return in_array($dayType, ['saturday', 'sunday', 'holiday', 'day7'], true);
    }
}
