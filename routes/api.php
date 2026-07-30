<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\HolidayCalendarController;
use App\Http\Controllers\Api\V1\MatrixRuleController;
use App\Http\Controllers\Api\V1\ReportTemplateController;
use App\Http\Controllers\Api\V1\SiteController;
use App\Http\Controllers\Api\V1\SiteDaytypeCodeController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('api.auth.login');
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('api.auth.logout');
        Route::get('me', [AuthController::class, 'me'])->name('api.auth.me');
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('dashboard/summary', [DashboardController::class, 'summary'])->name('api.dashboard.summary');

    Route::apiResource('sites', SiteController::class);
    Route::get('matrix-rules/grid', [MatrixRuleController::class, 'grid'])->name('api.matrix-rules.grid');
    Route::apiResource('matrix-rules', MatrixRuleController::class);
    Route::apiResource('site-daytype-codes', SiteDaytypeCodeController::class);
    Route::apiResource('holiday-calendars', HolidayCalendarController::class);
    Route::apiResource('report-templates', ReportTemplateController::class);
});
