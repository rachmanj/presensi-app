<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AttendancePeriod;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendancePeriodController extends Controller
{
    public function index(): JsonResponse
    {
        $periods = AttendancePeriod::withCount('sheets')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        return response()->json($periods);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2099'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'label' => ['nullable', 'string', 'max:50'],
        ]);

        $months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        $period = AttendancePeriod::create([
            'year' => $data['year'],
            'month' => $data['month'],
            'label' => $data['label'] ?? $months[$data['month']].' '.$data['year'],
            'status' => 'draft',
        ]);

        return response()->json($period, 201);
    }

    public function show(AttendancePeriod $period): JsonResponse
    {
        return response()->json($period->load('sheets'));
    }

    public function finalize(Request $request, AttendancePeriod $period, AuditLogService $audit): JsonResponse
    {
        $old = ['status' => $period->status];
        $period->update(['status' => 'finalized', 'finalized_at' => now()]);
        $period->sheets()->update(['status' => 'finalized']);

        $audit->log('period.finalize', 'AttendancePeriod', $period->id, $old, ['status' => 'finalized'], null, $request->user());

        return response()->json($period->fresh());
    }

    public function reopen(Request $request, AttendancePeriod $period, AuditLogService $audit): JsonResponse
    {
        $old = ['status' => $period->status];
        $period->update(['status' => 'review', 'finalized_at' => null]);

        $audit->log('period.reopen', 'AttendancePeriod', $period->id, $old, ['status' => 'review'], null, $request->user());

        return response()->json($period->fresh());
    }
}
