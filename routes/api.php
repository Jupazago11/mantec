<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Inspector\InspectorReportController;
use App\Http\Controllers\Api\InspectorSyncController;
use App\Http\Controllers\Api\InspectorSyncFileController;
use App\Http\Controllers\Api\InspectorOfflineCatalogController;

Route::post('/login', [AuthApiController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthApiController::class, 'logout']);

    Route::prefix('inspector')->group(function () {
        Route::get('/offline-catalog', [InspectorOfflineCatalogController::class, 'show']);

        Route::get('/clients/{client}/areas', [InspectorReportController::class, 'getAreasByClient']);
        Route::get('/areas/{area}/elements', [InspectorReportController::class, 'getElementsByArea']);
        Route::get('/elements/{element}/components', [InspectorReportController::class, 'getComponentsByElement']);
        Route::get('/elements/{element}/conditions', [InspectorReportController::class, 'getConditionsByElement']);
        Route::get('/components/{component}/diagnostics', [InspectorReportController::class, 'getDiagnosticsByComponent']);
        Route::get('/elements/{element}/pending-diagnostics', [InspectorReportController::class, 'getPendingDiagnostics']);

        Route::post('/reports', [InspectorReportController::class, 'store']);
        Route::post('/reports/sync', [InspectorSyncController::class, 'store']);
        Route::post('/report-details/{reportDetail}/files', [InspectorSyncFileController::class, 'store']);

        Route::get('/elements/{element}/weekly-diagnostic-status', [InspectorReportController::class, 'getWeeklyDiagnosticStatus']);

    });
});
