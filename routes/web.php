<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Inspector\InspectorReportController;

// Global
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\UserController;

// Operativos compartidos
use App\Http\Controllers\Admin\AdminManagedUserController;
use App\Http\Controllers\Admin\AdminAreaController;
use App\Http\Controllers\Admin\AdminElementTypeController;
use App\Http\Controllers\Admin\AdminDiagnosticController;
use App\Http\Controllers\Admin\AdminConditionController;
use App\Http\Controllers\Admin\AdminComponentController;
use App\Http\Controllers\Admin\AdminComponentDiagnosticController;
use App\Http\Controllers\Admin\AdminElementController;
use App\Http\Controllers\Admin\AdminPreventiveReportController;
use App\Http\Controllers\Admin\AdminReportEvidenceController;

Route::get('/', function () {
    return view('public.home');
})->name('home');

Route::get('/test-r2', function () {
    $path = 'test/prueba.txt';
    Storage::disk('r2')->put($path, 'Hola desde Laravel');

    return Storage::disk('r2')->exists($path)
        ? 'OK: archivo subido'
        : 'ERROR: no se subió';
});

Route::get('/php-upload-check', function () {
    return response()->json([
        'loaded_php_ini' => php_ini_loaded_file(),
        'file_uploads' => ini_get('file_uploads'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'max_file_uploads' => ini_get('max_file_uploads'),
        'memory_limit' => ini_get('memory_limit'),
    ]);
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */
    Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    /*
    |--------------------------------------------------------------------------
    | ADMIN - CLIENTES Y USUARIOS GLOBALES
    |--------------------------------------------------------------------------
    | Uso esperado:
    | - superadmin
    | - admin_global
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->name('admin.')->group(function () {
        // Clientes
        Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
        Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
        Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
        Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');
        Route::patch('/clients/{client}/toggle-status', [ClientController::class, 'toggleStatus'])->name('clients.toggle-status');

        // Usuarios globales
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | ADMIN - MÓDULOS OPERATIVOS COMPARTIDOS
    |--------------------------------------------------------------------------
    | Uso esperado:
    | - superadmin
    | - admin_global
    | - admin
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->name('admin.')->group(function () {
        // Usuarios gestionados
        Route::get('/managed-users', [AdminManagedUserController::class, 'index'])->name('managed-users.index');
        Route::post('/managed-users', [AdminManagedUserController::class, 'store'])->name('managed-users.store');
        Route::put('/managed-users/{user}', [AdminManagedUserController::class, 'update'])->name('managed-users.update');
        Route::delete('/managed-users/{user}', [AdminManagedUserController::class, 'destroy'])->name('managed-users.destroy');
        Route::patch('/managed-users/{user}/toggle-status', [AdminManagedUserController::class, 'toggleStatus'])->name('managed-users.toggle-status');

        // Áreas
        Route::get('/managed-areas', [AdminAreaController::class, 'index'])->name('managed-areas.index');
        Route::post('/managed-areas', [AdminAreaController::class, 'store'])->name('managed-areas.store');
        Route::put('/managed-areas/{area}', [AdminAreaController::class, 'update'])->name('managed-areas.update');
        Route::delete('/managed-areas/{area}', [AdminAreaController::class, 'destroy'])->name('managed-areas.destroy');
        Route::patch('/managed-areas/{area}/toggle-status', [AdminAreaController::class, 'toggleStatus'])->name('managed-areas.toggle-status');

        // Tipos de activos
        Route::get('/managed-element-types', [AdminElementTypeController::class, 'index'])->name('managed-element-types.index');
        Route::post('/managed-element-types', [AdminElementTypeController::class, 'store'])->name('managed-element-types.store');
        Route::put('/managed-element-types/{elementType}', [AdminElementTypeController::class, 'update'])->name('managed-element-types.update');
        Route::delete('/managed-element-types/{elementType}', [AdminElementTypeController::class, 'destroy'])->name('managed-element-types.destroy');
        Route::patch('/managed-element-types/{elementType}/toggle-status', [AdminElementTypeController::class, 'toggleStatus'])->name('managed-element-types.toggle-status');

        // Diagnósticos
        Route::get('/managed-diagnostics', [AdminDiagnosticController::class, 'index'])->name('managed-diagnostics.index');
        Route::post('/managed-diagnostics', [AdminDiagnosticController::class, 'store'])->name('managed-diagnostics.store');
        Route::put('/managed-diagnostics/{diagnostic}', [AdminDiagnosticController::class, 'update'])->name('managed-diagnostics.update');
        Route::delete('/managed-diagnostics/{diagnostic}', [AdminDiagnosticController::class, 'destroy'])->name('managed-diagnostics.destroy');
        Route::patch('/managed-diagnostics/{diagnostic}/toggle-status', [AdminDiagnosticController::class, 'toggleStatus'])->name('managed-diagnostics.toggle-status');

        // Condiciones
        Route::get('/managed-conditions', [AdminConditionController::class, 'index'])->name('managed-conditions.index');
        Route::post('/managed-conditions', [AdminConditionController::class, 'store'])->name('managed-conditions.store');
        Route::put('/managed-conditions/{condition}', [AdminConditionController::class, 'update'])->name('managed-conditions.update');
        Route::delete('/managed-conditions/{condition}', [AdminConditionController::class, 'destroy'])->name('managed-conditions.destroy');
        Route::patch('/managed-conditions/{condition}/toggle-status', [AdminConditionController::class, 'toggleStatus'])->name('managed-conditions.toggle-status');

        // Componentes
        Route::get('/managed-components', [AdminComponentController::class, 'index'])->name('managed-components.index');
        Route::post('/managed-components', [AdminComponentController::class, 'store'])->name('managed-components.store');
        Route::put('/managed-components/{component}', [AdminComponentController::class, 'update'])->name('managed-components.update');
        Route::delete('/managed-components/{component}', [AdminComponentController::class, 'destroy'])->name('managed-components.destroy');
        Route::patch('/managed-components/{component}/toggle-status', [AdminComponentController::class, 'toggleStatus'])->name('managed-components.toggle-status');

        // Componente - Diagnóstico
        Route::get('/managed-component-diagnostics', [AdminComponentDiagnosticController::class, 'index'])->name('managed-component-diagnostics.index');
        Route::post('/managed-component-diagnostics', [AdminComponentDiagnosticController::class, 'store'])->name('managed-component-diagnostics.store');
        Route::get('/cd/clients/{client}/element-types', [AdminComponentDiagnosticController::class, 'getElementTypes'])->name('cd.clients.element-types');
        Route::get('/cd/element-types/{elementType}/components', [AdminComponentDiagnosticController::class, 'getComponents'])->name('cd.element-types.components');
        Route::get('/cd/clients/{client}/diagnostics', [AdminComponentDiagnosticController::class, 'getDiagnostics'])->name('cd.clients.diagnostics');
        Route::get('/cd/components/{component}/assigned', [AdminComponentDiagnosticController::class, 'getAssigned'])->name('cd.components.assigned');

        // Activos
        Route::get('/managed-elements', [AdminElementController::class, 'index'])->name('managed-elements.index');
        Route::post('/managed-elements', [AdminElementController::class, 'store'])->name('managed-elements.store');
        Route::put('/managed-elements/{element}', [AdminElementController::class, 'update'])->name('managed-elements.update');
        Route::post('/managed-elements/{element}/components', [AdminElementController::class, 'syncComponents'])->name('managed-elements.components.sync');
        Route::delete('/managed-elements/{element}', [AdminElementController::class, 'destroy'])->name('managed-elements.destroy');
        Route::patch('/managed-elements/{element}/toggle-status', [AdminElementController::class, 'toggleStatus'])->name('managed-elements.toggle-status');

        // AJAX compartido
        Route::get('/clients/{client}/areas', [AdminElementController::class, 'getAreasByClient'])->name('clients.areas');
        Route::get('/clients/{client}/element-types', [AdminComponentController::class, 'getElementTypesByClient'])->name('clients.element-types');

        // Reportes preventivos / evidencias
        Route::get('/preventive-reports/general/{client}', [AdminPreventiveReportController::class, 'general'])->name('preventive-reports.general');
        Route::get('/preventive-reports/{client}/{elementType}', [AdminPreventiveReportController::class, 'show'])->name('preventive-reports.show');
        Route::patch('/preventive-reports/report-details/{reportDetail}/toggle-execution', [AdminPreventiveReportController::class, 'toggleExecution'])->name('preventive-reports.toggle-execution');
        Route::get('/preventive-reports/report-details/{reportDetail}/evidence', [AdminPreventiveReportController::class, 'evidence'])->name('preventive-reports.evidence');
        Route::get('/report-evidence/{file}/open', [AdminReportEvidenceController::class, 'open'])->name('report-evidence.open');
    });

    /*
    |--------------------------------------------------------------------------
    | INSPECTOR
    |--------------------------------------------------------------------------
    */
    Route::prefix('inspector')->name('inspector.')->group(function () {
        Route::get('/reports', [InspectorReportController::class, 'index'])->name('reports.index');
        Route::post('/reports', [InspectorReportController::class, 'store'])->name('reports.store');

        Route::get('/clients/{client}/areas', [InspectorReportController::class, 'getAreasByClient'])->name('clients.areas');
        Route::get('/areas/{area}/elements', [InspectorReportController::class, 'getElementsByArea'])->name('areas.elements');
        Route::get('/elements/{element}/components', [InspectorReportController::class, 'getComponentsByElement'])->name('elements.components');
        Route::get('/elements/{element}/conditions', [InspectorReportController::class, 'getConditionsByElement'])->name('elements.conditions');
        Route::get('/components/{component}/diagnostics', [InspectorReportController::class, 'getDiagnosticsByComponent'])->name('components.diagnostics');
        Route::get('/elements/{element}/pending-diagnostics', [InspectorReportController::class, 'getPendingDiagnostics'])->name('elements.pending-diagnostics');
    });
});
