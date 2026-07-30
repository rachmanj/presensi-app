<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@arka.local'],
            [
                'name' => 'HR Admin',
                'password' => bcrypt('password'),
            ]
        );

        $this->call([
            SiteSeeder::class,
            MatrixRuleSeeder::class,
            SiteDaytypeCodeSeeder::class,
            HolidayCalendarSeeder::class,
            ReportTemplateSeeder::class,
        ]);
    }
}
