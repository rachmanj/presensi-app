<?php

namespace App\Jobs;

use App\Models\AttendanceRow;
use App\Models\AttendanceSheet;
use App\Models\FingerprintScan;
use App\Models\HeroEmployeeCache;
use App\Services\AttendanceCodeEngine;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateAttendanceSheet implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(public AttendanceSheet $sheet)
    {
        $this->onQueue('generate');
    }

    public function handle(AttendanceCodeEngine $engine): void
    {
        $this->sheet->update(['status' => 'processing']);

        try {
            $this->ensureRowsExist();
            $engine->generateForSheet($this->sheet);
            $this->sheet->update(['status' => 'review']);
        } catch (\Throwable $e) {
            Log::error('Attendance generation failed', [
                'sheet_id' => $this->sheet->id,
                'error' => $e->getMessage(),
            ]);

            $this->sheet->update(['status' => 'draft']);
            throw $e;
        }
    }

    private function ensureRowsExist(): void
    {
        $siteCode = $this->sheet->site_code;
        $period = $this->sheet->period;

        $niksFromScans = FingerprintScan::whereHas('import', function ($q) use ($period, $siteCode) {
            $q->where('period_id', $period->id)
                ->where('site_code', $siteCode)
                ->where('status', 'parsed');
        })
            ->whereNotNull('resolved_nik')
            ->distinct()
            ->pluck('resolved_nik');

        $employees = HeroEmployeeCache::where(function ($q) use ($siteCode, $niksFromScans) {
            $q->where('project_code', $siteCode)
                ->orWhereIn('nik', $niksFromScans);
        })
            ->where('is_active', true)
            ->get()
            ->unique('nik');

        $existingNiks = $this->sheet->rows()->pluck('nik')->toArray();

        foreach ($employees as $emp) {
            if (in_array($emp->nik, $existingNiks, true)) {
                continue;
            }

            AttendanceRow::create([
                'sheet_id' => $this->sheet->id,
                'nik' => $emp->nik,
                'employee_name' => $emp->fullname,
                'position' => $emp->position,
                'home_site_code' => $emp->project_code ?? $siteCode,
                'working_days' => 0,
                'summary' => null,
            ]);
        }

        $scansWithoutCache = FingerprintScan::whereHas('import', function ($q) use ($period, $siteCode) {
            $q->where('period_id', $period->id)
                ->where('site_code', $siteCode)
                ->where('status', 'parsed');
        })
            ->whereNotNull('resolved_nik')
            ->select('resolved_nik', 'raw_name')
            ->distinct()
            ->get();

        foreach ($scansWithoutCache as $scan) {
            if (in_array($scan->resolved_nik, $existingNiks, true)) {
                continue;
            }
            if ($this->sheet->rows()->where('nik', $scan->resolved_nik)->exists()) {
                continue;
            }

            $cache = HeroEmployeeCache::where('nik', $scan->resolved_nik)->first();

            AttendanceRow::create([
                'sheet_id' => $this->sheet->id,
                'nik' => $scan->resolved_nik,
                'employee_name' => $cache?->fullname ?? $scan->raw_name ?? $scan->resolved_nik,
                'position' => $cache?->position,
                'home_site_code' => $cache?->project_code ?? $siteCode,
                'working_days' => 0,
                'summary' => null,
            ]);
        }
    }
}
