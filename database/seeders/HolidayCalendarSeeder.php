<?php

namespace Database\Seeders;

use App\Models\HolidayCalendar;
use Illuminate\Database\Seeder;

class HolidayCalendarSeeder extends Seeder
{
    public function run(): void
    {
        $holidays = [
            ['date' => '2026-01-01', 'type' => 'national_holiday', 'description' => 'Tahun Baru Masehi', 'year' => 2026],
            ['date' => '2026-01-16', 'type' => 'national_holiday', 'description' => 'Isra Mikraj Nabi Muhammad SAW', 'year' => 2026],
            ['date' => '2026-01-29', 'type' => 'national_holiday', 'description' => 'Tahun Baru Imlek', 'year' => 2026],
            ['date' => '2026-03-19', 'type' => 'national_holiday', 'description' => 'Hari Raya Nyepi', 'year' => 2026],
            ['date' => '2026-04-03', 'type' => 'national_holiday', 'description' => 'Wafat Isa Almasih', 'year' => 2026],
            ['date' => '2026-04-18', 'type' => 'national_holiday', 'description' => 'Hari Raya Idul Fitri', 'year' => 2026],
            ['date' => '2026-04-19', 'type' => 'joint_leave', 'description' => 'Cuti Bersama Idul Fitri', 'year' => 2026],
            ['date' => '2026-05-01', 'type' => 'national_holiday', 'description' => 'Hari Buruh Internasional', 'year' => 2026],
            ['date' => '2026-05-14', 'type' => 'national_holiday', 'description' => 'Kenaikan Isa Almasih', 'year' => 2026],
            ['date' => '2026-05-27', 'type' => 'national_holiday', 'description' => 'Hari Raya Waisak', 'year' => 2026],
            ['date' => '2026-06-01', 'type' => 'national_holiday', 'description' => 'Hari Lahir Pancasila', 'year' => 2026],
            ['date' => '2026-06-27', 'type' => 'national_holiday', 'description' => 'Hari Raya Idul Adha', 'year' => 2026],
            ['date' => '2026-07-16', 'type' => 'national_holiday', 'description' => 'Tahun Baru Islam 1 Muharram', 'year' => 2026],
            ['date' => '2026-08-17', 'type' => 'national_holiday', 'description' => 'Hari Kemerdekaan RI', 'year' => 2026],
            ['date' => '2026-08-25', 'type' => 'national_holiday', 'description' => 'Maulid Nabi Muhammad SAW', 'year' => 2026],
            ['date' => '2026-12-25', 'type' => 'national_holiday', 'description' => 'Hari Raya Natal', 'year' => 2026],
        ];

        foreach ($holidays as $holiday) {
            HolidayCalendar::updateOrCreate(['date' => $holiday['date']], $holiday);
        }
    }
}
