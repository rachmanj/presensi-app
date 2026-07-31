<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AttendanceCell;
use App\Models\AttendancePeriod;
use App\Models\AttendanceRow;
use App\Models\AttendanceSheet;
use App\Models\HeroEmployeeCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceComparisonController extends Controller
{
    public function employee(Request $request, string $nik): JsonResponse
    {
        $data = $request->validate([
            'periods' => ['required', 'string'],
            'site_code' => ['nullable', 'string', 'max:10'],
        ]);

        $periodIds = array_filter(explode(',', $data['periods']));
        $periods = AttendancePeriod::whereIn('id', $periodIds)->get();

        $employee = HeroEmployeeCache::where('nik', $nik)->first();
        $results = [];

        foreach ($periods as $period) {
            $sheetQuery = AttendanceSheet::where('period_id', $period->id);
            if ($data['site_code'] ?? null) {
                $sheetQuery->where('site_code', $data['site_code']);
            }

            $sheet = $sheetQuery->first();
            if (! $sheet) {
                $results[] = [
                    'period_id' => $period->id,
                    'period_label' => $period->label,
                    'found' => false,
                ];

                continue;
            }

            $row = AttendanceRow::where('sheet_id', $sheet->id)->where('nik', $nik)->with('cells')->first();
            if (! $row) {
                $results[] = [
                    'period_id' => $period->id,
                    'period_label' => $period->label,
                    'found' => false,
                ];

                continue;
            }

            $summary = $row->summary ?? [];
            $cells = $row->cells->map(fn ($c) => [
                'day' => $c->day_of_month,
                'code' => $c->final_code ?? $c->auto_code,
                'overtime_hours' => $c->overtime_hours,
            ]);

            $workdays = $row->cells->where('day_type', 'workday')->count();
            $present = $row->cells->filter(fn ($c) => ($c->final_code ?? $c->auto_code) && ! in_array($c->final_code ?? $c->auto_code, ['1901', '1902', '1903', '1904', '1905', '1906'], true))->count();
            $attendancePct = $workdays > 0 ? round(($present / $workdays) * 100, 1) : 0;

            $results[] = [
                'period_id' => $period->id,
                'period_label' => $period->label,
                'found' => true,
                'site_code' => $sheet->site_code,
                'summary' => [
                    'HOS2' => $summary['HOS2'] ?? 0,
                    'HOA2' => $summary['HOA2'] ?? 0,
                    '1901' => $summary['1901'] ?? 0,
                    '1902' => $summary['1902'] ?? 0,
                    '1903' => $summary['1903'] ?? 0,
                    '1904' => $summary['1904'] ?? 0,
                    '1905' => $summary['1905'] ?? 0,
                    '1906' => $summary['1906'] ?? 0,
                    'working_days' => $summary['HARI KERJA'] ?? $row->working_days ?? 0,
                    'attendance_percentage' => $attendancePct,
                ],
                'cells' => $cells,
            ];
        }

        return response()->json([
            'nik' => $nik,
            'employee_name' => $employee?->fullname,
            'periods' => $results,
        ]);
    }

    public function site(Request $request, string $siteCode): JsonResponse
    {
        $data = $request->validate([
            'periods' => ['required', 'string'],
        ]);

        $periodIds = array_filter(explode(',', $data['periods']));
        $periods = AttendancePeriod::whereIn('id', $periodIds)->get();
        $results = [];

        foreach ($periods as $period) {
            $sheet = AttendanceSheet::where('period_id', $period->id)
                ->where('site_code', $siteCode)
                ->first();

            if (! $sheet) {
                $results[] = [
                    'period_id' => $period->id,
                    'period_label' => $period->label,
                    'found' => false,
                ];

                continue;
            }

            $rowIds = AttendanceRow::where('sheet_id', $sheet->id)->pluck('id');
            $totalEmployees = $rowIds->count();

            $cells = AttendanceCell::whereIn('row_id', $rowIds)->get();
            $workdayCells = $cells->where('day_type', 'workday');
            $presentCells = $workdayCells->filter(fn ($c) => ($c->final_code ?? $c->auto_code) && ! in_array($c->final_code ?? $c->auto_code, ['1901', '1902', '1903', '1904', '1905', '1906'], true));

            $attendancePct = $workdayCells->count() > 0
                ? round(($presentCells->count() / $workdayCells->count()) * 100, 1)
                : 0;

            $overtimeHours = round((float) $cells->sum('overtime_hours'), 2);
            $leaveCount = $cells->filter(fn ($c) => in_array($c->final_code ?? $c->auto_code, ['1901', '1902', '1903', '1904', '1905'], true))->count();
            $absentCount = $cells->filter(fn ($c) => ($c->final_code ?? $c->auto_code) === '1906')->count();

            $results[] = [
                'period_id' => $period->id,
                'period_label' => $period->label,
                'found' => true,
                'total_employees' => $totalEmployees,
                'attendance_percentage' => $attendancePct,
                'overtime_hours' => $overtimeHours,
                'leave_count' => $leaveCount,
                'absent_count' => $absentCount,
            ];
        }

        return response()->json([
            'site_code' => $siteCode,
            'periods' => $results,
        ]);
    }
}
