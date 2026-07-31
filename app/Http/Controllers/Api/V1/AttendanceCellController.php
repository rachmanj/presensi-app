<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AttendanceCell;
use App\Models\AttendanceSheet;
use App\Services\AttendanceCodeEngine;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceCellController extends Controller
{
    public function show(AttendanceCell $cell): JsonResponse
    {
        return response()->json($cell->load(['traces', 'row']));
    }

    public function update(Request $request, AttendanceCell $cell, AuditLogService $audit): JsonResponse
    {
        $data = $request->validate([
            'final_code' => ['nullable', 'string', 'max:20'],
            'override_reason' => ['required_with:final_code', 'string', 'max:255'],
        ]);

        $old = ['final_code' => $cell->final_code, 'is_overridden' => $cell->is_overridden];

        $cell->update([
            'final_code' => $data['final_code'] ?? $cell->final_code,
            'is_overridden' => true,
            'override_by' => $request->user()?->name,
            'override_reason' => $data['override_reason'] ?? $cell->override_reason,
        ]);

        $audit->log(
            'cell.override',
            'AttendanceCell',
            $cell->id,
            $old,
            ['final_code' => $cell->final_code, 'override_reason' => $cell->override_reason],
            $data['override_reason'] ?? null,
            $request->user()
        );

        $row = $cell->row;
        $sheet = $row->sheet;
        app(AttendanceCodeEngine::class)->updateRowSummary($row, $sheet);

        return response()->json($cell->fresh()->load('traces'));
    }

    public function trace(AttendanceCell $cell): JsonResponse
    {
        return response()->json($cell->traces()->orderBy('created_at')->get());
    }

    public function bulkUpdate(Request $request, AttendanceSheet $sheet, AuditLogService $audit): JsonResponse
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
            $old = ['final_code' => $cell->final_code];
            $cell->update([
                'final_code' => $item['final_code'],
                'is_overridden' => true,
                'override_by' => $request->user()?->name,
                'override_reason' => $item['override_reason'],
            ]);
            $audit->log('cell.override', 'AttendanceCell', $cell->id, $old, ['final_code' => $cell->final_code], $item['override_reason'], $request->user());
            $updated[] = $cell;
        }

        return response()->json(['updated' => count($updated), 'cells' => $updated]);
    }
}
