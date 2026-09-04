<?php

namespace Tests\Feature;

use App\Models\AttendancePeriod;
use App\Models\FingerprintImport;
use App\Models\FingerprintScan;
use App\Models\HeroEmployeeCache;
use App\Models\Site;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.hero.base_url' => 'http://hero.test',
            'services.hero.api_key' => 'test-key',
        ]);

        Carbon::setTestNow('2026-06-15');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_today_summary_returns_counts_without_hero_http_calls(): void
    {
        Http::fake();

        Site::create([
            'code' => '017C',
            'name' => 'Site 017C',
            'profile' => 'coal',
            'base_present_code' => 'H',
            'active' => true,
        ]);

        $today = '2026-06-15';

        HeroEmployeeCache::create([
            'nik' => '10001',
            'fullname' => 'Employee On Leave',
            'project_code' => '017C',
            'is_active' => true,
            'raw' => [
                'activity' => [
                    'leaves' => [['start_date' => $today, 'end_date' => $today]],
                    'lots' => [],
                ],
            ],
        ]);

        HeroEmployeeCache::create([
            'nik' => '10002',
            'fullname' => 'Employee Present',
            'project_code' => '017C',
            'is_active' => true,
        ]);

        HeroEmployeeCache::create([
            'nik' => '10003',
            'fullname' => 'Employee On Lot',
            'project_code' => '017C',
            'is_active' => true,
            'raw' => [
                'activity' => [
                    'leaves' => [],
                    'lots' => [['date' => $today]],
                ],
            ],
        ]);

        $period = AttendancePeriod::create([
            'year' => 2026,
            'month' => 6,
            'label' => 'June 2026',
            'status' => 'draft',
        ]);

        $import = FingerprintImport::create([
            'period_id' => $period->id,
            'site_code' => '017C',
            'format' => 'format1_scanlog',
            'original_filename' => 'scan.xls',
            'stored_path' => 'imports/scan.xls',
            'status' => 'parsed',
        ]);

        FingerprintScan::create([
            'import_id' => $import->id,
            'raw_pin' => '1',
            'raw_nip' => '1',
            'scan_date' => $today,
            'check_in' => '07:30:00',
            'resolved_nik' => '10002',
        ]);

        FingerprintScan::create([
            'import_id' => $import->id,
            'raw_pin' => '2',
            'raw_nip' => '2',
            'scan_date' => $today,
            'check_in' => '08:30:00',
            'resolved_nik' => '10003',
        ]);

        $summary = app(DashboardService::class)->todaySummary('017C');

        $this->assertArrayHasKey('total_employees', $summary);
        $this->assertArrayHasKey('present', $summary);
        $this->assertArrayHasKey('absent', $summary);
        $this->assertArrayHasKey('late', $summary);
        $this->assertSame(3, $summary['total_employees']);
        $this->assertSame(2, $summary['present']);
        $this->assertSame(1, $summary['late']);

        Http::assertNothingSent();
    }

    public function test_today_summary_with_zero_employees(): void
    {
        Http::fake();

        $summary = app(DashboardService::class)->todaySummary('017C');

        $this->assertArrayHasKey('total_employees', $summary);
        $this->assertArrayHasKey('present', $summary);
        $this->assertArrayHasKey('absent', $summary);
        $this->assertArrayHasKey('late', $summary);
        $this->assertSame(0, $summary['total_employees']);
        $this->assertSame(0, $summary['present']);
        $this->assertSame(0, $summary['absent']);
        $this->assertSame(0, $summary['late']);

        Http::assertNothingSent();
    }
}
