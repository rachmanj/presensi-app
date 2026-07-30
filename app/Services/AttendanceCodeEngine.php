<?php

namespace App\Services;

use App\Exceptions\MatrixRuleNotFoundException;
use App\Models\AttendanceCell;
use App\Models\AttendanceCellTrace;
use App\Models\AttendanceRow;
use App\Models\AttendanceSheet;
use App\Models\FingerprintScan;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AttendanceCodeEngine
{
    private const LEAVE_CODES = [
        'annual_leave' => '1901',
        'paid_permission' => '1902',
        'unpaid_permission' => '1903',
        'sick_paid' => '1904',
        'sick_unpaid' => '1905',
        'absent' => '1906',
        'cuti' => '1901',
        'izin' => '1902',
        'sakit' => '1904',
        'mangkir' => '1906',
    ];

    private const OVERTIME_CODES = ['HOS2', 'HOA2'];

    public function __construct(
        private DayTypeService $dayTypeService,
        private MatrixResolver $matrixResolver,
        private HeroApiClient $heroApiClient,
    ) {}

    public function generateForSheet(AttendanceSheet $sheet): void
    {
        $period = $sheet->period;
        $start = Carbon::create($period->year, $period->month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $rows = $sheet->rows()->get();

        foreach ($rows as $row) {
            foreach (CarbonPeriod::create($start, $end) as $date) {
                $this->generateForCell($row, $date, $sheet);
            }
            $this->updateRowSummary($row, $sheet);
        }
    }

    public function generateForCell(AttendanceRow $row, Carbon $date, ?AttendanceSheet $sheet = null): array
    {
        $sheet = $sheet ?? $row->sheet;
        $siteCode = $sheet->site_code;
        $dayType = $this->resolveDayType($siteCode, $date);

        $cell = AttendanceCell::firstOrNew([
            'row_id' => $row->id,
            'work_date' => $date->toDateString(),
        ]);

        if ($cell->exists && $cell->is_overridden) {
            $overrideCode = $this->resolveOverride($cell);

            return ['auto_code' => $overrideCode, 'trace' => [], 'skipped' => true];
        }

        $cell->day_of_month = $date->day;
        $cell->day_type = $dayType;
        $cell->save();

        $cell->traces()->delete();

        $traces = [];
        $heroActivity = $this->getHeroActivity($row->nik, $date, $sheet);
        $scan = $this->getScanForDate($row, $date, $sheet);
        $visitSite = $this->resolveVisitSite($row, $date, $scan, $heroActivity);

        if ($visitSite) {
            $cell->visit_site_code = $visitSite;
        }

        // Step 1: manual code from fingerprint
        if ($scan && $scan->manual_code) {
            $code = $scan->manual_code;
            $traces[] = $this->writeTrace($cell, 'fingerprint.manual_code', "Manual code {$code} from fingerprint import", [
                'manual_code' => $code,
                'scan_id' => $scan->id,
            ]);
            $this->saveCellCode($cell, $code);

            return ['auto_code' => $code, 'trace' => $traces];
        }

        // Step 2: leave/absent from HERO
        $leaveResult = $this->resolveLeave($row->nik, $date, $heroActivity);
        if ($leaveResult) {
            $traces[] = $this->writeTrace($cell, 'leave.'.$leaveResult['code'], $leaveResult['explanation'], $leaveResult['inputs']);
            $this->saveCellCode($cell, $leaveResult['code']);

            return ['auto_code' => $leaveResult['code'], 'trace' => $traces];
        }

        // Step 3: day-type (weekend/holiday) — skip only when no presence scan
        $hasPresence = $scan && ($scan->check_in || $scan->check_out || $scan->manual_code);

        if ($this->dayTypeService->isWeekendOrHoliday($dayType) && ! $hasPresence) {
            $overtimeResult = $this->resolveOvertimeUpgrade('', $heroActivity, $date, $row->home_site_code ?? $siteCode, $visitSite);
            if ($overtimeResult['code']) {
                $traces[] = $this->writeTrace($cell, 'overtime.'.$overtimeResult['code'], $overtimeResult['explanation'], $overtimeResult['inputs']);
                $this->saveCellCode($cell, $overtimeResult['code']);

                return ['auto_code' => $overtimeResult['code'], 'trace' => $traces];
            }

            $traces[] = $this->writeTrace($cell, 'daytype.'.$dayType, "Non-working day ({$dayType}) — no overtime", [
                'day_type' => $dayType,
            ]);
            $this->saveCellCode($cell, null);

            return ['auto_code' => null, 'trace' => $traces];
        }

        // Step 4: presence check
        if (! $hasPresence) {
            $traces[] = $this->writeTrace($cell, 'presence.absent', 'No fingerprint scan recorded — left blank for HR review', [
                'nik' => $row->nik,
                'date' => $date->toDateString(),
            ]);
            $this->saveCellCode($cell, null);

            return ['auto_code' => null, 'trace' => $traces];
        }

        // Step 5: matrix lookup
        $homeSite = $row->home_site_code ?? $siteCode;
        try {
            $matrixResult = $this->resolvePresenceCode($row, $date, $visitSite);
            $baseCode = $matrixResult['code'];
            $traces[] = $this->writeTrace($cell, 'matrix.visit', "Matrix {$homeSite} × ".($visitSite ?? $homeSite)." → {$baseCode}", [
                'home_site' => $homeSite,
                'visit_site' => $visitSite ?? $homeSite,
                'code' => $baseCode,
            ]);
        } catch (MatrixRuleNotFoundException $e) {
            $traces[] = $this->writeTrace($cell, 'matrix.missing', "No matrix rule for {$e->homeSite} × {$e->visitSite}", [
                'home_site' => $e->homeSite,
                'visit_site' => $e->visitSite,
            ]);
            $this->saveCellCode($cell, null);

            return ['auto_code' => null, 'trace' => $traces];
        }

        // Step 6: overtime upgrade
        $overtimeResult = $this->resolveOvertimeUpgrade($baseCode, $heroActivity, $date, $homeSite, $visitSite);
        $finalCode = $overtimeResult['code'] ?: $baseCode;

        if ($overtimeResult['code']) {
            $traces[] = $this->writeTrace($cell, 'overtime.'.$overtimeResult['code'], $overtimeResult['explanation'], $overtimeResult['inputs']);
        }

        $this->saveCellCode($cell, $finalCode);

        return ['auto_code' => $finalCode, 'trace' => $traces];
    }

    private function resolveOverride(AttendanceCell $cell): ?string
    {
        return $cell->final_code ?? $cell->auto_code;
    }

    private function resolveLeave(string $nik, Carbon $date, array $heroActivity): ?array
    {
        $dateStr = $date->toDateString();

        foreach ($heroActivity['leaves'] ?? [] as $leave) {
            $start = $leave['start_date'] ?? $leave['date_from'] ?? null;
            $end = $leave['end_date'] ?? $leave['date_to'] ?? $start;

            if (! $start) {
                continue;
            }

            if ($dateStr >= $start && $dateStr <= ($end ?? $start)) {
                $type = $leave['type'] ?? $leave['leave_type'] ?? 'annual_leave';
                $code = self::LEAVE_CODES[$type] ?? self::LEAVE_CODES[strtolower((string) $type)] ?? '1901';

                return [
                    'code' => $code,
                    'explanation' => "Leave ({$type}) from HERO activity",
                    'inputs' => ['leave' => $leave, 'type' => $type],
                ];
            }
        }

        foreach ($heroActivity['lots'] ?? [] as $lot) {
            $lotDate = $lot['date'] ?? $lot['start_date'] ?? null;
            if ($lotDate === $dateStr) {
                return null;
            }
        }

        return null;
    }

    private function resolveDayType(string $siteCode, Carbon $date): string
    {
        return $this->dayTypeService->classify($date, $siteCode);
    }

    private function resolveOvertimeUpgrade(
        string $baseCode,
        array $overtimeData,
        Carbon $date,
        string $homeSite,
        ?string $visitSite,
    ): array {
        $dateStr = $date->toDateString();
        $overtimes = $overtimeData['overtimes'] ?? $overtimeData['overtime'] ?? [];

        foreach ($overtimes as $ot) {
            $otDate = $ot['date'] ?? $ot['work_date'] ?? null;
            if ($otDate !== $dateStr) {
                continue;
            }

            $hours = (float) ($ot['hours'] ?? $ot['duration_hours'] ?? 0);
            if ($hours > 0 && $hours < 7) {
                continue;
            }

            $isVisit = $visitSite && $visitSite !== $homeSite;
            $code = $isVisit ? 'HOA2' : 'HOS2';

            return [
                'code' => $code,
                'explanation' => "Overtime verified ({$hours}h) — upgraded to {$code}",
                'inputs' => ['overtime' => $ot, 'base_code' => $baseCode],
            ];
        }

        return ['code' => null, 'explanation' => '', 'inputs' => []];
    }

    private function resolvePresenceCode(AttendanceRow $row, Carbon $date, ?string $visitSite): array
    {
        $homeSite = $row->home_site_code ?? $row->sheet->site_code;

        return $this->matrixResolver->resolve($homeSite, $visitSite, $date);
    }

    private function getHeroActivity(string $nik, Carbon $date, AttendanceSheet $sheet): array
    {
        $cached = \App\Models\HeroEmployeeCache::where('nik', $nik)->first();
        if ($cached?->raw && isset($cached->raw['activity'])) {
            return $cached->raw['activity'];
        }

        $period = $sheet->period;
        $activity = $this->heroApiClient->getActivity($nik, $period->year, $period->month);

        if (is_array($activity) && isset($activity['data'])) {
            return $activity['data'];
        }

        return is_array($activity) ? $activity : [];
    }

    private function getScanForDate(AttendanceRow $row, Carbon $date, AttendanceSheet $sheet): ?FingerprintScan
    {
        $period = $sheet->period;

        return FingerprintScan::where('scan_date', $date->toDateString())
            ->where('resolved_nik', $row->nik)
            ->whereHas('import', function ($q) use ($period, $sheet) {
                $q->where('period_id', $period->id)
                    ->where('site_code', $sheet->site_code)
                    ->where('status', 'parsed');
            })
            ->first();
    }

    private function resolveVisitSite(AttendanceRow $row, Carbon $date, ?FingerprintScan $scan, array $heroActivity): ?string
    {
        if ($scan?->extra) {
            foreach ($scan->extra as $key => $val) {
                if (str_contains(strtolower($key), 'visit') && $val) {
                    if (is_string($val) && preg_match('/\b(HO|APS|BO|017C|021C|022C|023C|025C)\b/', $val, $m)) {
                        return $m[1];
                    }
                }
            }
        }

        foreach ($heroActivity['lots'] ?? [] as $lot) {
            $lotDate = $lot['date'] ?? $lot['start_date'] ?? null;
            if ($lotDate === $date->toDateString()) {
                return $lot['destination_site'] ?? $lot['visit_site'] ?? $lot['project_code'] ?? null;
            }
        }

        return null;
    }

    private function writeTrace(AttendanceCell $cell, string $ruleKey, string $explanation, array $inputs): array
    {
        AttendanceCellTrace::create([
            'cell_id' => $cell->id,
            'rule_key' => $ruleKey,
            'explanation' => $explanation,
            'inputs' => $inputs,
        ]);

        return compact('ruleKey', 'explanation', 'inputs');
    }

    private function saveCellCode(AttendanceCell $cell, ?string $code): void
    {
        $cell->auto_code = $code;
        if (! $cell->is_overridden) {
            $cell->final_code = $code;
        }
        $cell->save();
    }

    public function updateRowSummary(AttendanceRow $row, AttendanceSheet $sheet): void
    {
        $cells = $row->cells()->get();
        $template = $sheet->reportTemplate;
        $summaryColumns = $this->getSummaryColumns($template);

        $counts = [];
        foreach ($summaryColumns as $col) {
            if (! in_array($col, ['TOTAL', 'HARI KERJA', 'Kosong'], true)) {
                $counts[$col] = 0;
            }
        }

        $total = 0;
        $workingDays = 0;
        $emptyCount = 0;
        $saturdayCount = 0;

        foreach ($cells as $cell) {
            $code = $cell->final_code ?? $cell->auto_code;

            if ($code === null || $code === '') {
                $emptyCount++;
                if ($cell->day_type === 'saturday') {
                    $saturdayCount++;
                }

                continue;
            }

            if (isset($counts[$code])) {
                $counts[$code]++;
            }

            if (in_array($code, self::OVERTIME_CODES, true)) {
                $total++;
            }

            if ($cell->day_type === 'workday' && ! in_array($code, ['1901', '1902', '1903', '1904', '1905', '1906'], true)) {
                $workingDays++;
            }
        }

        if (isset($counts['TOTAL'])) {
            $counts['TOTAL'] = ($counts['HOS2'] ?? 0) + ($counts['HOA2'] ?? 0);
        }

        if (isset($counts['Kosong'])) {
            $counts['Kosong'] = $emptyCount;
        }

        if (isset($counts['Sabtu'])) {
            $counts['Sabtu'] = $saturdayCount;
        }

        if (isset($counts['HARI KERJA'])) {
            $counts['HARI KERJA'] = $workingDays;
        }

        if (isset($counts['SC1'])) {
            // SC1 counted from cells if present
        }

        $row->update([
            'summary' => $counts,
            'working_days' => $workingDays,
        ]);
    }

    private function getSummaryColumns($template): array
    {
        if (! $template) {
            return [];
        }

        $columns = [];
        foreach ($template->column_layout['summary_groups'] ?? [] as $group) {
            foreach ($group['columns'] ?? [] as $col) {
                $columns[] = $col;
            }
        }

        return $columns;
    }
}
