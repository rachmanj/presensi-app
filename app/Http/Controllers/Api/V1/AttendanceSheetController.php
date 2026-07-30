<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateAttendanceSheet;
use App\Models\AttendancePeriod;
use App\Models\AttendanceSheet;
use App\Models\ReportTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AttendanceSheetController extends Controller
{
    public function index(AttendancePeriod $period): JsonResponse
    {
        return response()->json(
            $period->sheets()->with('reportTemplate')->get()
        );
    }

    public function store(Request $request, AttendancePeriod $period): JsonResponse
    {
        $data = $request->validate([
            'site_code' => ['required', 'string', 'max:10', 'exists:sites,code'],
            'report_template_id' => ['nullable', 'exists:report_templates,id'],
        ]);

        $templateId = $data['report_template_id'] ?? $this->defaultTemplateForSite($data['site_code']);

        $sheet = AttendanceSheet::create([
            'period_id' => $period->id,
            'site_code' => $data['site_code'],
            'report_template_id' => $templateId,
            'status' => 'draft',
            'meta' => null,
        ]);

        return response()->json($sheet->load('reportTemplate'), 201);
    }

    public function show(AttendanceSheet $sheet): JsonResponse
    {
        return response()->json($sheet->load(['period', 'reportTemplate']));
    }

    public function generate(AttendanceSheet $sheet): JsonResponse
    {
        if ($sheet->status === 'finalized') {
            return response()->json(['message' => 'Cannot regenerate finalized sheet'], 422);
        }

        Cache::put("sheet:{$sheet->id}:generate_status", 'queued', 3600);
        GenerateAttendanceSheet::dispatch($sheet);

        return response()->json(['message' => 'Generation queued', 'sheet_id' => $sheet->id]);
    }

    public function generateStatus(AttendanceSheet $sheet): JsonResponse
    {
        return response()->json([
            'sheet_id' => $sheet->id,
            'status' => $sheet->fresh()->status,
            'rows_count' => $sheet->rows()->count(),
        ]);
    }

    public function finalize(AttendanceSheet $sheet): JsonResponse
    {
        $sheet->update(['status' => 'finalized']);

        return response()->json($sheet);
    }

    public function reopen(AttendanceSheet $sheet): JsonResponse
    {
        $sheet->update(['status' => 'review']);

        return response()->json($sheet);
    }

    private function defaultTemplateForSite(string $siteCode): ?int
    {
        $name = match ($siteCode) {
            'HO', 'BO' => 'STAFF_HO',
            'APS' => 'STAFF_APS',
            default => null,
        };

        if (! $name) {
            return null;
        }

        return ReportTemplate::where('name', $name)->value('id');
    }
}
