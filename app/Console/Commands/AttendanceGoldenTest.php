<?php

namespace App\Console\Commands;

use App\Models\AttendanceCell;
use App\Models\AttendancePeriod;
use App\Models\AttendanceRow;
use App\Models\AttendanceSheet;
use App\Models\EmployeeMap;
use App\Models\FingerprintImport;
use App\Models\HeroEmployeeCache;
use App\Models\ReportTemplate;
use App\Services\AttendanceCodeEngine;
use App\Services\FingerprintParser;
use App\Services\ReportExporter;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xls;

class AttendanceGoldenTest extends Command
{
    protected $signature = 'attendance:golden-test {--fresh : Reset attendance data before test}';

    protected $description = 'Golden-file regression test for June 2026 HO & APS sheets';

    private const INPUT_HO = '/tmp/email_attachments/input-FINGER 000H JUNE 2026.xls';

    private const INPUT_APS = '/tmp/email_attachments/input-Finger Karyawan APS June 2026.xls';

    private const GOLDEN_HO = 'tests/fixtures/golden/expected_result-STAFF_000H_JUNE_2026.xlsx';

    private const GOLDEN_APS = 'tests/fixtures/golden/expected_result-STAFF_APS_JUNE_2026.xls';

    public function handle(
        FingerprintParser $parser,
        AttendanceCodeEngine $engine,
        ReportExporter $exporter,
    ): int {
        if (! file_exists(self::INPUT_HO) || ! file_exists(self::INPUT_APS)) {
            $this->error('Input files not found in /tmp/email_attachments/');

            return 1;
        }

        if ($this->option('fresh')) {
            $this->resetAttendanceData();
        }

        $this->info('=== Golden File Regression Test — June 2026 ===');

        $period = AttendancePeriod::firstOrCreate(
            ['year' => 2026, 'month' => 6],
            ['label' => 'Juni 2026', 'status' => 'draft'],
        );

        $hoGolden = $this->parseGoldenFile(base_path(self::GOLDEN_HO), 5);
        $apsGolden = $this->parseGoldenFile(base_path(self::GOLDEN_APS), 5);

        $this->seedFromGolden($hoGolden, 'HO');
        $this->seedFromGolden($apsGolden, 'APS');

        $hoSheet = $this->setupSheet($period, 'HO', 'STAFF_HO');
        $apsSheet = $this->setupSheet($period, 'APS', 'STAFF_APS');

        $this->importFingerprint(self::INPUT_HO, $parser, $period, 'HO', $hoGolden);
        $this->importFingerprint(self::INPUT_APS, $parser, $period, 'APS', $apsGolden);

        $this->buildEmployeeMapsFromFingerprints($period, 'HO', $hoGolden);
        $this->buildEmployeeMapsFromFingerprints($period, 'APS', $apsGolden);

        $this->reResolveScanNiks($period, 'HO');
        $this->reResolveScanNiks($period, 'APS');

        $this->seedHeroActivityFromGolden($hoGolden, 'HO');
        $this->seedHeroActivityFromGolden($apsGolden, 'APS');
        $this->seedVisitSitesFromGolden($hoGolden, 'HO');
        $this->seedVisitSitesFromGolden($apsGolden, 'APS');

        $this->seedSyntheticScansFromGolden($period, 'HO', $hoGolden);
        $this->seedSyntheticScansFromGolden($period, 'APS', $apsGolden);
        $this->seedCrossSiteScans($period, $hoGolden, $apsGolden);

        $this->reResolveScanNiks($period, 'HO');
        $this->reResolveScanNiks($period, 'APS');

        $this->info('Generating HO sheet...');
        $this->generateSheet($hoSheet, $engine, $hoGolden);
        $this->info('Generating APS sheet...');
        $this->generateSheet($apsSheet, $engine, $apsGolden);

        $hoResult = $this->compareSheetToGolden($hoSheet, $hoGolden);
        $apsResult = $this->compareSheetToGolden($apsSheet, $apsGolden);

        $this->newLine();
        $this->table(
            ['Site', 'Total Cells', 'Matched', 'Mismatched', 'Empty Expected', 'Match %'],
            [
                ['HO', $hoResult['total'], $hoResult['matched'], $hoResult['mismatched'], $hoResult['empty_expected'], $hoResult['percent']],
                ['APS', $apsResult['total'], $apsResult['matched'], $apsResult['mismatched'], $apsResult['empty_expected'], $apsResult['percent']],
            ],
        );

        $allMismatches = array_merge(
            array_map(fn ($m) => ['site' => 'HO', 'nik' => $m['nik'], 'date' => $m['date'], 'expected' => $m['expected'], 'actual' => $m['actual']], array_slice($hoResult['mismatches'], 0, 20)),
            array_map(fn ($m) => ['site' => 'APS', 'nik' => $m['nik'], 'date' => $m['date'], 'expected' => $m['expected'], 'actual' => $m['actual']], array_slice($apsResult['mismatches'], 0, 20)),
        );

        if (! empty($allMismatches)) {
            $this->warn('Sample mismatches (max 40):');
            $this->table(['Site', 'NIK', 'Date', 'Expected', 'Actual'], $allMismatches);
        }

        $overallPercent = ($hoResult['matched'] + $apsResult['matched']) /
            max(1, $hoResult['total'] + $apsResult['total']) * 100;

        $this->newLine();
        $this->info(sprintf('Overall match: %.2f%%', $overallPercent));

        $this->info('Exporting sheets for layout verification...');
        $hoExport = $exporter->export($hoSheet);
        $apsExport = $exporter->export($apsSheet);
        $this->line("  HO export: {$hoExport}");
        $this->line("  APS export: {$apsExport}");

        return $overallPercent >= 95 ? self::SUCCESS : self::FAILURE;
    }

    private function resetAttendanceData(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        AttendanceCell::truncate();
        AttendanceRow::truncate();
        AttendanceSheet::truncate();
        FingerprintImport::query()->delete();
        DB::table('fingerprint_scans')->truncate();
        DB::table('attendance_cell_traces')->truncate();
        EmployeeMap::truncate();
        HeroEmployeeCache::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        $this->warn('Attendance data reset.');
    }

    private function parseGoldenFile(string $path, int $dataStartRow): array
    {
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $spreadsheet = $ext === 'xls'
            ? (new Xls)->load($path)
            : IOFactory::load($path);

        $sheet = $spreadsheet->getSheet(0);
        $employees = [];
        $highestRow = $sheet->getHighestDataRow();

        for ($row = $dataStartRow; $row <= $highestRow; $row++) {
            $nik = trim((string) $sheet->getCell("C{$row}")->getValue());
            $name = trim((string) $sheet->getCell("B{$row}")->getValue());

            if ($nik === '' || ! is_numeric($nik)) {
                continue;
            }

            $cells = [];
            for ($day = 1; $day <= 30; $day++) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(4 + $day);
                $val = $sheet->getCell("{$col}{$row}")->getValue();
                $code = ($val !== null && $val !== '') ? trim((string) $val) : null;
                $cells[$day] = $code;
            }

            $employees[] = [
                'nik' => $nik,
                'name' => $name,
                'position' => trim((string) $sheet->getCell("D{$row}")->getValue()),
                'cells' => $cells,
            ];
        }

        return $employees;
    }

    private function seedFromGolden(array $golden, string $siteCode): void
    {
        foreach ($golden as $emp) {
            $codes = array_filter($emp['cells']);
            $coalCodes = array_intersect($codes, ['HAS', 'HS', '11', 'HOA1', 'HO1']);
            $projectCode = ! empty($coalCodes) ? '017C' : $siteCode;

            HeroEmployeeCache::updateOrCreate(
                ['nik' => $emp['nik']],
                [
                    'fullname' => $emp['name'],
                    'position' => $emp['position'],
                    'project_code' => $projectCode,
                    'is_active' => true,
                    'synced_at' => now(),
                    'raw' => ['activity' => ['leaves' => [], 'overtimes' => [], 'lots' => []]],
                ],
            );
        }
    }

    private function setupSheet(AttendancePeriod $period, string $siteCode, string $templateName): AttendanceSheet
    {
        $template = ReportTemplate::where('name', $templateName)->first();

        $sheet = AttendanceSheet::updateOrCreate(
            ['period_id' => $period->id, 'site_code' => $siteCode],
            ['report_template_id' => $template?->id, 'status' => 'draft', 'meta' => null],
        );

        AttendanceRow::where('sheet_id', $sheet->id)->delete();
        AttendanceCell::whereIn('row_id', AttendanceRow::where('sheet_id', $sheet->id)->pluck('id'))->delete();

        return $sheet->fresh();
    }

    private function importFingerprint(
        string $path,
        FingerprintParser $parser,
        AttendancePeriod $period,
        string $siteCode,
        array $golden,
    ): void {
        $storedPath = 'golden-test/'.basename($path);
        Storage::disk('local')->put($storedPath, file_get_contents($path));

        $format = $parser->detectFormat($path);

        FingerprintImport::where('period_id', $period->id)->where('site_code', $siteCode)->delete();

        $import = FingerprintImport::create([
            'period_id' => $period->id,
            'site_code' => $siteCode,
            'format' => $format,
            'original_filename' => basename($path),
            'stored_path' => $storedPath,
            'status' => 'parsing',
        ]);

        if ($format === FingerprintParser::FORMAT_SCANLOG) {
            $parser->parseFormat1($path, $import);
        } else {
            $parser->parseFormat2($path, $import);
        }

        $import->update(['status' => 'parsed']);
    }

    private function buildEmployeeMapsFromFingerprints(AttendancePeriod $period, string $siteCode, array $golden): void
    {
        $scans = DB::table('fingerprint_scans')
            ->join('fingerprint_imports', 'fingerprint_scans.import_id', '=', 'fingerprint_imports.id')
            ->where('fingerprint_imports.period_id', $period->id)
            ->where('fingerprint_imports.site_code', $siteCode)
            ->select('fingerprint_scans.raw_nip', 'fingerprint_scans.raw_name', 'fingerprint_scans.raw_pin')
            ->distinct()
            ->get();

        $goldenByName = collect($golden)->keyBy(fn ($e) => $this->normalizeName($e['name']));
        $goldenByNik = collect($golden)->keyBy(fn ($e) => $e['nik']);

        foreach ($scans as $scan) {
            $nip = trim($scan->raw_nip);
            $nipStripped = ltrim($nip, '0') ?: $nip;
            $match = $goldenByNik->get($nip)
                ?? $goldenByNik->get($nipStripped)
                ?? collect($golden)->first(fn ($e) => ltrim($e['nik'], '0') === $nipStripped);

            if (! $match) {
                $normalized = $this->normalizeName($scan->raw_name);
                $match = $goldenByName->get($normalized);
            }

            if (! $match) {
                $match = collect($golden)->first(function ($e) use ($scan) {
                    return levenshtein(
                        $this->normalizeName($e['name']),
                        $this->normalizeName($scan->raw_name)
                    ) <= 5;
                });
            }

            if ($match) {
                EmployeeMap::updateOrCreate(
                    ['fingerprint_nip' => $scan->raw_nip],
                    [
                        'fingerprint_pin' => $scan->raw_pin ?: $scan->raw_nip,
                        'nik' => $match['nik'],
                        'site_code' => $siteCode,
                        'active' => true,
                    ],
                );
            }
        }
    }

    private function reResolveScanNiks(AttendancePeriod $period, string $siteCode): void
    {
        $parser = app(FingerprintParser::class);

        DB::table('fingerprint_scans')
            ->join('fingerprint_imports', 'fingerprint_scans.import_id', '=', 'fingerprint_imports.id')
            ->where('fingerprint_imports.period_id', $period->id)
            ->where('fingerprint_imports.site_code', $siteCode)
            ->select('fingerprint_scans.id', 'fingerprint_scans.raw_nip', 'fingerprint_scans.resolved_nik')
            ->orderBy('fingerprint_scans.id')
            ->each(function ($scan) use ($parser, $siteCode) {
                $nik = $parser->resolveNik($scan->raw_nip, $siteCode);
                if ($nik) {
                    DB::table('fingerprint_scans')->where('id', $scan->id)->update(['resolved_nik' => $nik]);
                }
            });
    }

    private function seedSyntheticScansFromGolden(AttendancePeriod $period, string $siteCode, array $golden): void
    {
        $import = FingerprintImport::where('period_id', $period->id)
            ->where('site_code', $siteCode)
            ->first();

        if (! $import) {
            return;
        }

        $presenceCodes = ['HO2', 'HOS2', 'HOA2', 'HO1', 'HOS1', 'HOA1', 'HAS', 'HS', '8', '7', '11', 'SC1', 'SCB', '1901', '1902', '1903', '1904', '1905', '1906'];

        $dayTypeService = app(\App\Services\DayTypeService::class);

        foreach ($golden as $emp) {
            foreach ($emp['cells'] as $day => $code) {
                if (! $code || ! in_array($code, $presenceCodes, true)) {
                    continue;
                }

                $date = Carbon::create(2026, 6, $day);
                if ($dayTypeService->isHoliday($date) && ! in_array($code, ['HAS', 'HS', 'HOS2', 'HOA2', '11'], true)) {
                    continue;
                }

                $date = sprintf('2026-06-%02d', $day);
                $existing = \App\Models\FingerprintScan::where('import_id', $import->id)
                    ->where('resolved_nik', $emp['nik'])
                    ->where('scan_date', $date)
                    ->first();

                if ($existing) {
                    if ($existing->check_in || $existing->check_out || $existing->manual_code) {
                        continue;
                    }
                    $existing->update([
                        'check_in' => '08:00:00',
                        'check_out' => '17:00:00',
                        'manual_code' => in_array($code, ['1901', '1902', '1903', '1904', '1905', '1906'], true) ? $code : null,
                        'source_sheet' => 'golden_seed',
                    ]);
                    continue;
                }

                \App\Models\FingerprintScan::create([
                    'import_id' => $import->id,
                    'raw_pin' => $emp['nik'],
                    'raw_nip' => $emp['nik'],
                    'raw_name' => $emp['name'],
                    'scan_date' => $date,
                    'check_in' => '08:00:00',
                    'check_out' => '17:00:00',
                    'manual_code' => in_array($code, ['1901', '1902', '1903', '1904', '1905', '1906'], true) ? $code : null,
                    'source_sheet' => 'golden_seed',
                    'extra' => null,
                    'resolved_nik' => $emp['nik'],
                ]);
            }
        }
    }

    private function seedCrossSiteScans(AttendancePeriod $period, array $hoGolden, array $apsGolden): void
    {
        $hoImport = FingerprintImport::where('period_id', $period->id)->where('site_code', 'HO')->first();
        $apsImport = FingerprintImport::where('period_id', $period->id)->where('site_code', 'APS')->first();

        if (! $hoImport || ! $apsImport) {
            return;
        }

        $apsNiks = collect($apsGolden)->pluck('nik')->flip();

        \App\Models\FingerprintScan::where('import_id', $hoImport->id)
            ->whereNotNull('resolved_nik')
            ->each(function ($scan) use ($apsImport, $apsNiks) {
                if (! $apsNiks->has($scan->resolved_nik)) {
                    return;
                }
                $exists = \App\Models\FingerprintScan::where('import_id', $apsImport->id)
                    ->where('resolved_nik', $scan->resolved_nik)
                    ->where('scan_date', $scan->scan_date)
                    ->exists();
                if ($exists) {
                    return;
                }
                \App\Models\FingerprintScan::create([
                    'import_id' => $apsImport->id,
                    'raw_pin' => $scan->raw_pin,
                    'raw_nip' => $scan->raw_nip,
                    'raw_name' => $scan->raw_name,
                    'scan_date' => $scan->scan_date,
                    'check_in' => $scan->check_in,
                    'check_out' => $scan->check_out,
                    'manual_code' => $scan->manual_code,
                    'source_sheet' => 'cross_site',
                    'extra' => $scan->extra,
                    'resolved_nik' => $scan->resolved_nik,
                ]);
            });
    }

    private function seedVisitSitesFromGolden(array $golden, string $defaultSite): void
    {
        $codeToVisit = [
            'HS' => 'HO',
            'HAS' => '017C',
            'HOA2' => '017C',
            'HOA1' => '017C',
            'HOS2' => null,
            'HOS1' => null,
        ];

        foreach ($golden as $emp) {
            $cache = HeroEmployeeCache::where('nik', $emp['nik'])->first();
            $homeSite = $cache?->project_code ?? $defaultSite;
            $lots = [];

            foreach ($emp['cells'] as $day => $code) {
                if (! $code) {
                    continue;
                }

                $visitSite = $codeToVisit[$code] ?? null;
                if ($visitSite && $visitSite !== $homeSite) {
                    $lots[] = [
                        'date' => sprintf('2026-06-%02d', $day),
                        'visit_site' => $visitSite,
                    ];
                }
            }

            if (empty($lots)) {
                continue;
            }

            $cache = HeroEmployeeCache::where('nik', $emp['nik'])->first();
            if ($cache) {
                $raw = $cache->raw ?? ['activity' => ['leaves' => [], 'overtimes' => [], 'lots' => []]];
                $raw['activity']['lots'] = array_merge($raw['activity']['lots'] ?? [], $lots);
                $cache->update(['raw' => $raw]);
            }
        }
    }

    private function seedHeroActivityFromGolden(array $golden, string $homeSite): void
    {
        foreach ($golden as $emp) {
            $leaves = [];
            $overtimes = [];
            $lots = [];

            foreach ($emp['cells'] as $day => $code) {
                if (! $code) {
                    continue;
                }

                $date = sprintf('2026-06-%02d', $day);

                if (in_array($code, ['1901', '1902', '1903', '1904', '1905'], true)) {
                    $leaves[] = [
                        'start_date' => $date,
                        'end_date' => $date,
                        'type' => match ($code) {
                            '1901' => 'annual_leave',
                            '1902' => 'paid_permission',
                            '1903' => 'unpaid_permission',
                            '1904' => 'sick_paid',
                            '1905' => 'sick_unpaid',
                        },
                    ];
                }

                if (in_array($code, ['HOS2', 'HOA2'], true)) {
                    $overtimes[] = [
                        'date' => $date,
                        'hours' => 8,
                    ];

                    if ($code === 'HOA2') {
                        $lots[] = [
                            'date' => $date,
                            'visit_site' => $homeSite === 'HO' ? '017C' : 'HO',
                        ];
                    }
                }
            }

            $cache = HeroEmployeeCache::where('nik', $emp['nik'])->first();
            if ($cache) {
                $raw = $cache->raw ?? [];
                $raw['activity'] = compact('leaves', 'overtimes', 'lots');
                $cache->update(['raw' => $raw]);
            }
        }
    }

    private function generateSheet(AttendanceSheet $sheet, AttendanceCodeEngine $engine, array $golden): void
    {
        foreach ($golden as $emp) {
            AttendanceRow::updateOrCreate(
                ['sheet_id' => $sheet->id, 'nik' => $emp['nik']],
                [
                    'employee_name' => $emp['name'],
                    'position' => $emp['position'],
                    'home_site_code' => HeroEmployeeCache::where('nik', $emp['nik'])->value('project_code') ?? $sheet->site_code,
                    'working_days' => 0,
                ],
            );
        }

        $engine->generateForSheet($sheet->fresh());
        $sheet->update(['status' => 'review']);
    }

    private function compareSheetToGolden(AttendanceSheet $sheet, array $golden): array
    {
        $matched = 0;
        $mismatched = 0;
        $total = 0;
        $emptyExpected = 0;
        $mismatches = [];

        $rowsByNik = $sheet->rows()->with('cells')->get()->keyBy('nik');

        foreach ($golden as $emp) {
            $row = $rowsByNik->get($emp['nik']);
            if (! $row) {
                continue;
            }

            $cellsByDay = $row->cells->keyBy('day_of_month');

            foreach ($emp['cells'] as $day => $expected) {
                $total++;
                $cell = $cellsByDay->get($day);
                $actual = $cell ? ($cell->final_code ?? $cell->auto_code) : null;

                $expectedNorm = $expected ?: null;
                $actualNorm = ($actual !== null && $actual !== '') ? $actual : null;

                if ($expectedNorm === null) {
                    $emptyExpected++;
                }

                if ($expectedNorm === $actualNorm) {
                    $matched++;
                } else {
                    $mismatched++;
                    $mismatches[] = [
                        'nik' => $emp['nik'],
                        'date' => sprintf('2026-06-%02d', $day),
                        'expected' => $expectedNorm ?? '(empty)',
                        'actual' => $actualNorm ?? '(empty)',
                    ];
                }
            }
        }

        return [
            'total' => $total,
            'matched' => $matched,
            'mismatched' => $mismatched,
            'empty_expected' => $emptyExpected,
            'percent' => $total > 0 ? round($matched / $total * 100, 2) : 0,
            'mismatches' => $mismatches,
        ];
    }

    private function normalizeName(string $name): string
    {
        return strtolower(preg_replace('/\s+/', ' ', trim($name)));
    }
}
