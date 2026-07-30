<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AttendancePeriod;
use App\Models\Site;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function summary(): JsonResponse
    {
        return response()->json([
            'active_periods' => AttendancePeriod::whereIn('status', ['draft', 'processing', 'review'])->count(),
            'sites' => Site::where('active', true)->count(),
            'total_periods' => AttendancePeriod::count(),
        ]);
    }
}
