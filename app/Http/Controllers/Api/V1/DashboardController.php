<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboardService) {}

    public function summary(Request $request): JsonResponse
    {
        $siteCode = $request->query('site_code');

        return response()->json($this->dashboardService->todaySummary($siteCode));
    }

    public function overtime(Request $request): JsonResponse
    {
        $siteCode = $request->query('site_code');

        return response()->json($this->dashboardService->monthlyOvertime($siteCode));
    }

    public function attendanceTrend(Request $request): JsonResponse
    {
        $siteCode = $request->query('site_code');
        $days = min((int) $request->query('days', 7), 30);

        return response()->json($this->dashboardService->attendanceTrend($siteCode, $days));
    }
}
