<?php

namespace Tests\Feature;

use App\Models\AttendancePeriod;
use App\Models\AttendanceRow;
use App\Models\AttendanceSheet;
use App\Models\FingerprintImport;
use App\Models\FingerprintScan;
use App\Models\HeroEmployeeCache;
use App\Models\MatrixRule;
use App\Models\Site;
use App\Services\AttendanceCodeEngine;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCodeEngineHomeSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_home_site_falls_back_to_sheet_site_for_matrix_lookup(): void
    {
        Site::create([
            'code' => 'HO',
            'name' => 'Head Office',
            'profile' => 'office',
            'base_present_code' => 'HO2',
            'active' => true,
        ]);

        MatrixRule::create([
            'home_site_code' => 'HO',
            'visit_site_code' => 'HO',
            'code' => 'HO2',
            'priority' => 0,
            'effective_from' => '2025-01-01',
            'effective_to' => null,
        ]);

        $period = AttendancePeriod::create([
            'year' => 2026,
            'month' => 6,
            'label' => 'June 2026',
            'status' => 'draft',
        ]);

        $sheet = AttendanceSheet::create([
            'period_id' => $period->id,
            'site_code' => 'HO',
            'status' => 'draft',
        ]);

        $row = AttendanceRow::create([
            'sheet_id' => $sheet->id,
            'nik' => '19999',
            'employee_name' => 'Test Employee',
            'position' => 'Staff',
            'home_site_code' => 'NOTASITE',
            'working_days' => 0,
        ]);

        HeroEmployeeCache::create([
            'nik' => '19999',
            'fullname' => 'Test Employee',
            'project_code' => 'NOTASITE',
            'is_active' => true,
            'raw' => [
                'activity' => [
                    'leaves' => [],
                    'overtimes' => [],
                    'lots' => [],
                ],
            ],
        ]);

        $import = FingerprintImport::create([
            'period_id' => $period->id,
            'site_code' => 'HO',
            'format' => 'format1_scanlog',
            'original_filename' => 'scan.xls',
            'stored_path' => 'imports/scan.xls',
            'status' => 'parsed',
        ]);

        FingerprintScan::create([
            'import_id' => $import->id,
            'raw_pin' => '1',
            'raw_nip' => '1',
            'scan_date' => '2026-06-10',
            'check_in' => '08:00:00',
            'check_out' => '17:00:00',
            'resolved_nik' => '19999',
        ]);

        $workDate = Carbon::parse('2026-06-10');
        $result = app(AttendanceCodeEngine::class)->generateForCell($row, $workDate, $sheet);

        $this->assertSame('HO2', $result['auto_code']);
        $this->assertFalse($result['skipped'] ?? false);

        $cell = $row->cells()->whereDate('work_date', '2026-06-10')->first();
        $this->assertNotNull($cell);
        $this->assertSame('HO2', $cell->auto_code);
        $this->assertFalse(
            $cell->traces()->where('rule_key', 'matrix.missing')->exists()
        );
    }
}
