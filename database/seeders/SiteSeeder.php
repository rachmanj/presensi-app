<?php

namespace Database\Seeders;

use App\Models\Site;
use Illuminate\Database\Seeder;

class SiteSeeder extends Seeder
{
    public function run(): void
    {
        $sites = [
            ['code' => '017C', 'name' => 'Site 017C', 'profile' => 'coal', 'base_present_code' => 'HAS'],
            ['code' => '021C', 'name' => 'Site 021C', 'profile' => 'coal', 'base_present_code' => 'HO1'],
            ['code' => '022C', 'name' => 'Site 022C', 'profile' => 'coal', 'base_present_code' => 'HAS'],
            ['code' => '023C', 'name' => 'Site 023C', 'profile' => 'coal', 'base_present_code' => 'HAS'],
            ['code' => '025C', 'name' => 'Site 025C', 'profile' => 'coal', 'base_present_code' => 'HO1'],
            ['code' => 'BO', 'name' => 'Branch Office', 'profile' => 'office', 'base_present_code' => 'HO1'],
            ['code' => 'HO', 'name' => 'Head Office', 'profile' => 'office', 'base_present_code' => 'HO2'],
            ['code' => 'APS', 'name' => 'Arka Project Support', 'profile' => 'support', 'base_present_code' => 'HO2'],
        ];

        foreach ($sites as $site) {
            Site::updateOrCreate(['code' => $site['code']], $site);
        }
    }
}
