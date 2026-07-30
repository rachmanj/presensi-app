<?php

namespace Database\Seeders;

use App\Models\SiteDaytypeCode;
use Illuminate\Database\Seeder;

class SiteDaytypeCodeSeeder extends Seeder
{
    public function run(): void
    {
        $codes = [
            ['017C', 'workday', 'pagi', '11'],
            ['017C', 'workday', 'malam', '11/NS'],
            ['017C', 'off', 'any', 'SCB'],
            ['017C', 'day6', 'pagi', '11A'],
            ['017C', 'day6', 'malam', '11/NSA'],
            ['017C', 'day7_holiday', 'pagi', '11B'],
            ['017C', 'day7_holiday', 'malam', '11/NSB'],
            ['017C', 'standby', 'any', '7'],
            ['021C', 'workday', 'any', '7'],
            ['021C', 'off', 'any', 'SCB'],
            ['021C', 'standby', 'any', '7'],
            ['022C', 'workday', 'any', '11'],
            ['022C', 'off', 'any', 'SCB'],
            ['022C', 'day6', 'any', '11A'],
            ['022C', 'day7_holiday', 'any', '11B'],
            ['022C', 'standby', 'any', '7'],
            ['023C', 'workday', 'any', '11'],
            ['023C', 'off', 'any', 'SCB'],
            ['023C', 'day6', 'any', '11A'],
            ['023C', 'day7_holiday', 'any', '11B'],
            ['023C', 'standby', 'any', '7'],
            ['APS', 'workday', 'any', '8'],
            ['APS', 'off', 'any', 'SCB'],
            ['APS', 'day6', 'any', '7B'],
            ['APS', 'day7_holiday', 'any', 'B'],
            ['APS', 'standby', 'any', '7'],
        ];

        foreach ($codes as [$site, $dayType, $shift, $code]) {
            SiteDaytypeCode::updateOrCreate(
                ['site_code' => $site, 'day_type' => $dayType, 'shift' => $shift],
                ['code' => $code]
            );
        }
    }
}
