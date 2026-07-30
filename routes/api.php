<?php

use App\Http\Controllers\Api\V1\AttendanceCellController;
use App\Http\Controllers\Api\V1\AttendanceGridController;
use App\Http\Controllers\Api\V1\AttendancePeriodController;
use App\Http\Controllers\Api\V1\AttendanceSheetController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\EmployeeMapController;
use App\Http\Controllers\Api\V1\FingerprintImportController;
use App\Http\Controllers\Api\V1\HolidayCalendarController;
use App\Http\Controllers\Api\V1\MatrixRuleController;
use App\Http\Controllers\Api\V1\ReportExportController;
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

    Route::get('employee-maps/unmatched', [EmployeeMapController::class, 'unmatched'])->name('api.employee-maps.unmatched');
    Route::get('employee-maps/suggest', [EmployeeMapController::class, 'suggest'])->name('api.employee-maps.suggest');
    Route::post('employee-maps/bulk', [EmployeeMapController::class, 'bulkStore'])->name('api.employee-maps.bulk');
    Route::apiResource('employee-maps', EmployeeMapController::class);

    Route::apiResource('periods', AttendancePeriodController::class)->only(['index', 'store', 'show']);
    Route::get('periods/{period}/sheets', [AttendanceSheetController::class, 'index'])->name('api.sheets.index');
    Route::post('periods/{period}/sheets', [AttendanceSheetController::class, 'store'])->name('api.sheets.store');

    Route::get('sheets/{sheet}', [AttendanceSheetController::class, 'show'])->name('api.sheets.show');
    Route::post('sheets/{sheet}/generate', [AttendanceSheetController::class, 'generate'])->name('api.sheets.generate');
    Route::get('sheets/{sheet}/generate-status', [AttendanceSheetController::class, 'generateStatus'])->name('api.sheets.generate-status');
    Route::post('sheets/{sheet}/finalize', [AttendanceSheetController::class, 'finalize'])->name('api.sheets.finalize');
    Route::post('sheets/{sheet}/reopen', [AttendanceSheetController::class, 'reopen'])->name('api.sheets.reopen');

    Route::get('sheets/{sheet}/imports', [FingerprintImportController::class, 'index'])->name('api.imports.index');
    Route::post('sheets/{sheet}/imports', [FingerprintImportController::class, 'store'])->name('api.imports.store');
    Route::get('imports/{import}', [FingerprintImportController::class, 'show'])->name('api.imports.show');
    Route::get('imports/{import}/preview', [FingerprintImportController::class, 'preview'])->name('api.imports.preview');
    Route::get('imports/{import}/errors', [FingerprintImportController::class, 'errors'])->name('api.imports.errors');
    Route::get('imports/{import}/status', [FingerprintImportController::class, 'status'])->name('api.imports.status');
    Route::post('imports/{import}/reparse', [FingerprintImportController::class, 'reparse'])->name('api.imports.reparse');
    Route::delete('imports/{import}', [FingerprintImportController::class, 'destroy'])->name('api.imports.destroy');

    Route::get('sheets/{sheet}/grid', [AttendanceGridController::class, 'index'])->name('api.grid.index');
    Route::get('cells/{cell}', [AttendanceCellController::class, 'show'])->name('api.cells.show');
    Route::put('cells/{cell}', [AttendanceCellController::class, 'update'])->name('api.cells.update');
    Route::get('cells/{cell}/trace', [AttendanceCellController::class, 'trace'])->name('api.cells.trace');
    Route::post('sheets/{sheet}/cells/bulk-update', [AttendanceCellController::class, 'bulkUpdate'])->name('api.cells.bulk-update');

    Route::get('sheets/{sheet}/export', [ReportExportController::class, 'export'])->name('api.export.download');
    Route::get('sheets/{sheet}/export/preview', [ReportExportController::class, 'preview'])->name('api.export.preview');
});
