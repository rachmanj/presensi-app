<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ParseFingerprintImport;
use App\Models\AttendanceSheet;
use App\Models\FingerprintImport;
use App\Services\FingerprintParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FingerprintImportController extends Controller
{
    public function index(AttendanceSheet $sheet): JsonResponse
    {
        $imports = FingerprintImport::where('period_id', $sheet->period_id)
            ->where('site_code', $sheet->site_code)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($imports);
    }

    public function store(Request $request, AttendanceSheet $sheet): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xls,xlsx', 'max:20480'],
        ]);

        $file = $request->file('file');
        $path = $file->store('fingerprint-imports', 'local');

        $parser = app(FingerprintParser::class);
        $fullPath = Storage::disk('local')->path($path);
        $format = $parser->detectFormat($fullPath);

        $import = FingerprintImport::create([
            'period_id' => $sheet->period_id,
            'site_code' => $sheet->site_code,
            'format' => $format,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'status' => 'uploaded',
            'uploaded_by' => $request->user()?->name,
        ]);

        ParseFingerprintImport::dispatch($import);

        return response()->json($import, 201);
    }

    public function show(FingerprintImport $import): JsonResponse
    {
        return response()->json($import->load('scans'));
    }

    public function preview(FingerprintImport $import): JsonResponse
    {
        $scans = $import->scans()
            ->orderBy('scan_date')
            ->orderBy('raw_nip')
            ->limit(100)
            ->get();

        return response()->json([
            'import' => $import,
            'preview' => $scans,
            'total' => $import->scans()->count(),
        ]);
    }

    public function errors(FingerprintImport $import): JsonResponse
    {
        $unmatched = $import->scans()
            ->whereNull('resolved_nik')
            ->select('raw_nip', 'raw_name')
            ->distinct()
            ->get();

        return response()->json([
            'parse_errors' => $import->parse_errors,
            'unmatched_nips' => $unmatched,
            'rows_unmatched' => $import->rows_unmatched,
        ]);
    }

    public function status(FingerprintImport $import): JsonResponse
    {
        return response()->json([
            'id' => $import->id,
            'status' => $import->status,
            'rows_total' => $import->rows_total,
            'rows_matched' => $import->rows_matched,
            'rows_unmatched' => $import->rows_unmatched,
        ]);
    }

    public function reparse(FingerprintImport $import): JsonResponse
    {
        $import->update(['status' => 'uploaded']);
        ParseFingerprintImport::dispatch($import);

        return response()->json(['message' => 'Reparse queued', 'import' => $import]);
    }

    public function destroy(FingerprintImport $import): JsonResponse
    {
        Storage::disk('local')->delete($import->stored_path);
        $import->delete();

        return response()->json(null, 204);
    }
}
