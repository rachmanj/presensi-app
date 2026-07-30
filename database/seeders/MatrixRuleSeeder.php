<?php

namespace Database\Seeders;

use App\Models\MatrixRule;
use Illuminate\Database\Seeder;

class MatrixRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            ['017C', '017C', 'HAS'], ['017C', 'BO', 'HS'], ['017C', 'HO', 'HS'], ['017C', 'APS', 'HS'],
            ['017C', '021C', 'HS'], ['017C', '022C', 'HAS'], ['017C', '023C', 'HAS'], ['017C', '025C', 'HS'],
            ['021C', '021C', 'HO1'], ['021C', 'BO', 'HO1'], ['021C', 'HO', 'HOS1'], ['021C', 'APS', 'HOS1'],
            ['021C', '017C', 'HOA1'], ['021C', '022C', 'HOA1'], ['021C', '023C', 'HOA1'], ['021C', '025C', 'HOS1'],
            ['022C', '022C', 'HAS'], ['022C', 'BO', 'HS'], ['022C', 'HO', 'HS'], ['022C', 'APS', 'HS'],
            ['022C', '021C', 'HS'], ['022C', '017C', 'HAS'], ['022C', '023C', 'HAS'], ['022C', '025C', 'HS'],
            ['023C', '023C', 'HAS'], ['023C', 'BO', 'HS'], ['023C', 'HO', 'HS'], ['023C', 'APS', 'HS'],
            ['023C', '021C', 'HS'], ['023C', '017C', 'HAS'], ['023C', '022C', 'HAS'], ['023C', '025C', 'HS'],
            ['BO', 'BO', 'HO1'], ['BO', 'HO', 'HOS1'], ['BO', 'APS', 'HOS1'], ['BO', '017C', 'HOA1'],
            ['BO', '021C', 'HO1'], ['BO', '022C', 'HOA1'], ['BO', '023C', 'HOA1'], ['BO', '025C', 'HOS1'],
            ['HO', 'HO', 'HO2'], ['HO', 'BO', 'HOS2'], ['HO', 'APS', 'HO2'], ['HO', '017C', 'HOA2'],
            ['HO', '021C', 'HOS2'], ['HO', '022C', 'HOA2'], ['HO', '023C', 'HOA2'], ['HO', '025C', 'HOS2'],
            ['APS', 'APS', 'HO2'], ['APS', 'BO', 'HOS2'], ['APS', 'HO', 'HO2'], ['APS', '017C', 'HOA2'],
            ['APS', '021C', 'HOS2'], ['APS', '022C', 'HOA2'], ['APS', '023C', 'HOA2'], ['APS', '025C', 'HOS2'],
            ['025C', '025C', 'HO1'], ['025C', 'BO', 'HOS1'], ['025C', 'HO', 'HOS1'], ['025C', 'APS', 'HOS1'],
            ['025C', '017C', 'HOA1'], ['025C', '021C', 'HOS1'], ['025C', '022C', 'HOA1'], ['025C', '023C', 'HOA1'],
        ];

        foreach ($rules as [$home, $visit, $code]) {
            MatrixRule::updateOrCreate(
                [
                    'home_site_code' => $home,
                    'visit_site_code' => $visit,
                    'effective_from' => '2025-01-01',
                ],
                [
                    'code' => $code,
                    'priority' => 0,
                    'effective_to' => null,
                ]
            );
        }
    }
}
