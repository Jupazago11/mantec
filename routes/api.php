<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Inspector\InspectorReportController;
use App\Http\Controllers\Api\InspectorSyncController;
use App\Http\Controllers\Api\InspectorSyncFileController;
use App\Http\Controllers\Api\InspectorOfflineCatalogController;
use App\Http\Controllers\Api\InspectorMeasurementThicknessController;
use App\Http\Controllers\Api\Personal\AuthApiController as PersonalAuthApiController;
use App\Http\Controllers\Api\Personal\ActivityController as PersonalActivityController;
use App\Http\Controllers\Api\Personal\ActivityEvidenceController as PersonalActivityEvidenceController;

Route::post('/login', [AuthApiController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthApiController::class, 'logout']);

    Route::prefix('inspector')->group(function () {

        Route::prefix('measurements')->group(function () {
            Route::get('/element-types', [InspectorMeasurementThicknessController::class, 'elementTypes']);
            Route::get('/element-types/{elementType}/areas',[InspectorMeasurementThicknessController::class, 'areasByElementType']);
            Route::get('/areas/{area}/element-types/{elementType}/elements',[InspectorMeasurementThicknessController::class, 'elementsByAreaAndElementType']);
            Route::get('/elements/{element}/thickness',[InspectorMeasurementThicknessController::class, 'showThickness']);
            Route::post('/elements/{element}/thickness/draft/sync',[InspectorMeasurementThicknessController::class, 'syncDraft']);
        });

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
        Route::get('/areas/{area}/weekly-elements-status', [InspectorReportController::class, 'getWeeklyElementsStatus']);

    });
});

// Seccion 14.24 — API del supervisor (app Android), guard Sanctum
// separado del Inspector: token de Employee, no de User. El login no
// requiere token (obviamente); todo lo demas exige ademas
// personal.api.employee para que un token de Inspector (User) no pueda
// llamar estas rutas (Sanctum es polimorfico, auth:sanctum por si solo
// aceptaria cualquier tokenable valido).
Route::post('/personal/login', [PersonalAuthApiController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('api.personal.login');

Route::middleware(['auth:sanctum', 'personal.api.employee'])->prefix('personal')->name('api.personal.')->group(function () {
    Route::post('/logout', [PersonalAuthApiController::class, 'logout'])->name('logout');

    Route::get('/actividades', [PersonalActivityController::class, 'index'])->name('actividades.index');
    Route::post('/actividades/{activity}', [PersonalActivityController::class, 'store'])->name('actividades.store');

    Route::post('/actividades/{activity}/evidencias', [PersonalActivityEvidenceController::class, 'store'])->name('actividades.evidencias.store');
    Route::get('/actividades/{activity}/evidencias/{evidence}', [PersonalActivityEvidenceController::class, 'show'])->name('actividades.evidencias.show');
    Route::delete('/actividades/{activity}/evidencias/{evidence}', [PersonalActivityEvidenceController::class, 'destroy'])->name('actividades.evidencias.destroy');
});
