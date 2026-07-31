<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSheet;
use App\Models\HeroEmployeeCache;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class AttendanceGridController extends Controller
{
    public function index(AttendanceSheet $sheet): JsonResponse
    {
        $period = $sheet->period;
        $template = $sheet->reportTemplate;
        $daysInMonth = Carbon::create($period->year, $period->month, 1)->daysInMonth;

        $leaveBalances = HeroEmployeeCache::whereIn('nik', $sheet->rows()->pluck('nik'))
            ->pluck('leave_balance', 'nik');

        $rows = $sheet->rows()
            ->with(['cells' => fn ($q) => $q->orderBy('day_of_month')])
            ->orderBy('employee_name')
            ->get()
            ->values()
            ->map(function ($row, $index) {
                return [
                    'id' => $row->id,
                    'no' => $index + 1,
                    'nik' => $row->nik,
                    'employee_name' => $row->employee_name,
                    'position' => $row->position,
                    'home_site_code' => $row->home_site_code,
                    'working_days' => $row->working_days,
                    'summary' => $row->summary,
                    'leave_balance' => $leaveBalances[$row->nik] ?? null,
                    'cells' => $row->cells->mapWithKeys(fn ($cell) => [
                        $cell->day_of_month => [
                            'id' => $cell->id,
                            'auto_code' => $cell->auto_code,
                            'final_code' => $cell->final_code,
                            'is_overridden' => $cell->is_overridden,
                            'day_type' => $cell->day_type,
                            'work_date' => $cell->work_date->format('Y-m-d'),
                        ],
                    ]),
                ];
            });

        return response()->json([
            'sheet' => $sheet->load('period'),
            'template' => $template,
            'days_in_month' => $daysInMonth,
            'rows' => $rows,
        ]);
    }
}
