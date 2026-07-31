<?php

namespace App\Services;

use App\Models\AttendanceCell;
use App\Models\AttendancePeriod;
use App\Models\AttendanceRow;
use App\Models\AttendanceSheet;
use App\Models\FingerprintScan;
use App\Models\HeroEmployeeCache;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    private const LATE_THRESHOLD = '08:00:00';

  private const LEAVE_CODES = ['1901', '1902', '1903', '1904', '1905'];

    public function __construct(private HeroApiClient $heroApiClient) {}

    public function todaySummary(?string $siteCode = null): array
    {
        $today = Carbon::today();
        $employees = $this->getActiveEmployees($siteCode);
        $totalEmployees = $employees->count();
        $niks = $employees->pluck('nik')->all();

        $scansToday = $this->getScansForDate($today, $siteCode, $niks);
        $presentNiks = $scansToday->filter(fn ($s) => $s->check_in)->pluck('resolved_nik')->unique();
        $presentCount = $presentNiks->count();

        $lateCount = $scansToday->filter(function ($scan) {
            if (! $scan->check_in) {
                return false;
            }

            return $scan->check_in > self::LATE_THRESHOLD;
        })->count();

        $onLeaveCount = 0;
        $onLotCount = 0;
        $leaveNiks = collect();

        foreach ($employees as $emp) {
            $activity = $this->getEmployeeActivity($emp->nik, $today);
            if ($this->isOnLeave($activity, $today)) {
                $onLeaveCount++;
                $leaveNiks->push($emp->nik);
            }
            if ($this->isOnLot($activity, $today)) {
                $onLotCount++;
            }
        }

        $absentCount = $employees->filter(function ($emp) {
            return ! $presentNiks->contains($emp->nik);
        })->filter(function ($emp) use ($leaveNiks, $today) {
            if ($leaveNiks->contains($emp->nik)) {
                return false;
            }

            $activity = $this->getEmployeeActivity($emp->nik, $today);

            return ! $this->isOnLot($activity, $today);
        })->count();

        return [
            'date' => $today->toDateString(),
            'site_code' => $siteCode,
            'total_employees' => $totalEmployees,
            'present' => $presentCount,
            'absent' => $absentCount,
            'late' => $lateCount,
            'on_leave' => $onLeaveCount,
            'on_lot' => $onLotCount,
        ];
    }

    public function monthlyOvertime(?string $siteCode = null): array
    {
        $now = Carbon::now();
        $period = AttendancePeriod::where('year', $now->year)
            ->where('month', $now->month)
            ->first();

        if (! $period) {
            return ['period' => null, 'sites' => []];
        }

        $sheetsQuery = AttendanceSheet::where('period_id', $period->id);
        if ($siteCode) {
            $sheetsQuery->where('site_code', $siteCode);
        }

        $sites = [];
        foreach ($sheetsQuery->get() as $sheet) {
            $rowIds = AttendanceRow::where('sheet_id', $sheet->id)->pluck('id');
            $totalHours = AttendanceCell::whereIn('row_id', $rowIds)
                ->sum('overtime_hours');

            $hos2Count = AttendanceCell::whereIn('row_id', $rowIds)
                ->whereIn('final_code', ['HOS2', 'HOA2'])
                ->count();

            $sites[] = [
                'site_code' => $sheet->site_code,
                'overtime_hours' => round((float) $totalHours, 2),
                'overtime_days' => $hos2Count,
            ];
        }

        return [
            'period' => [
                'id' => $period->id,
                'label' => $period->label,
                'year' => $period->year,
                'month' => $period->month,
            ],
            'sites' => $sites,
        ];
    }

    public function attendanceTrend(?string $siteCode = null, int $days = 7): array
    {
        $employees = $this->getActiveEmployees($siteCode);
        $totalEmployees = max($employees->count(), 1);
        $niks = $employees->pluck('nik')->all();
        $trend = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $scans = $this->getScansForDate($date, $siteCode, $niks);
            $present = $scans->filter(fn ($s) => $s->check_in)->pluck('resolved_nik')->unique()->count();
            $percentage = round(($present / $totalEmployees) * 100, 1);

            $trend[] = [
                'date' => $date->toDateString(),
                'label' => $date->format('D, M j'),
                'present' => $present,
                'total' => $totalEmployees,
                'percentage' => $percentage,
            ];
        }

        return $trend;
    }

    private function getActiveEmployees(?string $siteCode): Collection
    {
        $query = HeroEmployeeCache::where('is_active', true);

        if ($siteCode) {
            $query->where('project_code', $siteCode);
        }

        return $query->get();
    }

    private function getScansForDate(Carbon $date, ?string $siteCode, array $niks): Collection
    {
        if (empty($niks)) {
            return collect();
        }

        $query = FingerprintScan::where('scan_date', $date->toDateString())
            ->whereIn('resolved_nik', $niks)
            ->whereHas('import', function ($q) use ($siteCode) {
                $q->where('status', 'parsed');
                if ($siteCode) {
                    $q->where('site_code', $siteCode);
                }
            });

        return $query->get();
    }

    private function getEmployeeActivity(string $nik, Carbon $date): array
    {
        $cached = HeroEmployeeCache::where('nik', $nik)->first();
        if ($cached?->raw && isset($cached->raw['activity'])) {
            return $cached->raw['activity'];
        }

        return $this->heroApiClient->getActivity($nik, $date->year, $date->month);
    }

    private function isOnLeave(array $activity, Carbon $date): bool
    {
        $dateStr = $date->toDateString();

        foreach ($activity['leaves'] ?? [] as $leave) {
            $start = $leave['start_date'] ?? $leave['date_from'] ?? null;
            $end = $leave['end_date'] ?? $leave['date_to'] ?? $start;

            if ($start && $dateStr >= $start && $dateStr <= ($end ?? $start)) {
                return true;
            }
        }

        return false;
    }

    private function isOnLot(array $activity, Carbon $date): bool
    {
        $dateStr = $date->toDateString();

        foreach ($activity['lots'] ?? [] as $lot) {
            $lotDate = $lot['date'] ?? $lot['start_date'] ?? null;
            if ($lotDate === $dateStr) {
                return true;
            }
        }

        return false;
    }
}
