<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AttendanceCell;
use App\Models\AttendanceSheet;
use App\Services\AttendanceCodeEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceCellController extends Controller
{
    public function show(AttendanceCell $cell): JsonResponse
    {
        return response()->json($cell->load(['traces', 'row']));
    }

    public function update(Request $request, AttendanceCell $cell): JsonResponse
    {
        $data = $request->validate([
            'final_code' => ['nullable', 'string', 'max:20'],
            'override_reason' => ['required_with:final_code', 'string', 'max:255'],
        ]);

        $cell->update([
            'final_code' => $data['final_code'] ?? $cell->final_code,
            'is_overridden' => true,
            'override_by' => $request->user()?->name,
            'override_reason' => $data['override_reason'] ?? $cell->override_reason,
        ]);

        $row = $cell->row;
        $sheet = $row->sheet;
        app(AttendanceCodeEngine::class)->updateRowSummary($row, $sheet);

        return response()->json($cell->fresh()->load('traces'));
    }

    public function trace(AttendanceCell $cell): JsonResponse
    {
        return response()->json($cell->traces()->orderBy('created_at')->get());
    }

    public function bulkUpdate(Request $request, AttendanceSheet $sheet): JsonResponse
    {
        $data = $request->validate([
            'updates' => ['required', 'array', 'min:1'],
            'updates.*.cell_id' => ['required', 'exists:attendance_cells,id'],
            'updates.*.final_code' => ['nullable', 'string', 'max:20'],
            'updates.*.override_reason' => ['required', 'string', 'max:255'],
        ]);

        $updated = [];
        foreach ($data['updates'] as $item) {
            $cell = AttendanceCell::find($item['cell_id']);
            $cell->update([
                'final_code' => $item['final_code'],
                'is_overridden' => true,
                'override_by' => $request->user()?->name,
                'override_reason' => $item['override_reason'],
            ]);
            $updated[] = $cell;
        }

        return response()->json(['updated' => count($updated), 'cells' => $updated]);
    }
}
