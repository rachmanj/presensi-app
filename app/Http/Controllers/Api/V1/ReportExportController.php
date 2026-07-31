<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSheet;
use App\Services\PdfExporter;
use App\Services\ReportExporter;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportExportController extends Controller
{
    public function export(AttendanceSheet $sheet, ReportExporter $exporter): BinaryFileResponse
    {
        $path = $exporter->export($sheet);
        $filename = "attendance_{$sheet->site_code}_{$sheet->period->year}_{$sheet->period->month}.xlsx";

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    public function exportPdf(AttendanceSheet $sheet, PdfExporter $exporter): BinaryFileResponse
    {
        $path = $exporter->export($sheet);
        $filename = "attendance_{$sheet->site_code}_{$sheet->period->year}_{$sheet->period->month}.pdf";

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    public function preview(AttendanceSheet $sheet): JsonResponse
    {
        $rows = $sheet->rows()->with('cells')->get();

        $summary = [
            'total_employees' => $rows->count(),
            'total_cells' => $rows->sum(fn ($r) => $r->cells->count()),
            'overridden_cells' => $rows->sum(fn ($r) => $r->cells->where('is_overridden', true)->count()),
        ];

        return response()->json([
            'sheet' => $sheet->load(['period', 'reportTemplate']),
            'summary' => $summary,
        ]);
    }
}
