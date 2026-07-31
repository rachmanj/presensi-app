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
                'role' => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'supervisor@arka.local'],
            [
                'name' => 'HR Supervisor',
                'password' => bcrypt('password'),
                'role' => 'hr_supervisor',
            ]
        );

        User::updateOrCreate(
            ['email' => 'staff@arka.local'],
            [
                'name' => 'HR Staff',
                'password' => bcrypt('password'),
                'role' => 'hr_staff',
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
