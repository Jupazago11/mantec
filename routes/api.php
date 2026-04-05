<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Inspector\InspectorReportController;

Route::post('/login', [AuthApiController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthApiController::class, 'logout']);

    Route::prefix('inspector')->group(function () {
        Route::get('/clients/{client}/areas', [InspectorReportController::class, 'getAreasByClient']);
        Route::get('/clients/{client}/conditions', [InspectorReportController::class, 'getConditionsByClient']);
        Route::get('/areas/{area}/elements', [InspectorReportController::class, 'getElementsByArea']);
        Route::get('/elements/{element}/components', [InspectorReportController::class, 'getComponentsByElement']);
        Route::get('/components/{component}/diagnostics', [InspectorReportController::class, 'getDiagnosticsByComponent']);
        Route::get('/elements/{element}/pending-diagnostics', [InspectorReportController::class, 'getPendingDiagnostics']);
        Route::post('/reports', [InspectorReportController::class, 'store']);
    });
});
