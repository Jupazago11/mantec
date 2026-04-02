<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\AreaController;
use App\Http\Controllers\Admin\ElementTypeController;
use App\Http\Controllers\Admin\ComponentController;
use App\Http\Controllers\Admin\DiagnosticController;
use App\Http\Controllers\Admin\ComponentDiagnosticController;
use App\Http\Controllers\Admin\ConditionController;
use App\Http\Controllers\Admin\ElementController;
use App\Http\Controllers\Admin\ElementComponentController;
use App\Http\Controllers\Admin\UserController;
//Admin2
use App\Http\Controllers\Admin\AdminManagedUserController;
use App\Http\Controllers\Admin\AdminAreaController;
use App\Http\Controllers\Admin\AdminElementTypeController;
use App\Http\Controllers\Admin\AdminDiagnosticController;
use App\Http\Controllers\Admin\AdminComponentDiagnosticController;
use App\Http\Controllers\Admin\AdminPreventiveReportController;
use App\Http\Controllers\Admin\AdminElementController;
use App\Http\Controllers\Admin\AdminReportEvidenceController;

use App\Http\Controllers\Inspector\InspectorReportController;
use App\Http\Controllers\Admin\AdminConditionController;
use App\Http\Controllers\Admin\AdminComponentController;

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
    Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/admin/clients', [ClientController::class, 'index'])->name('admin.clients.index');
    Route::post('/admin/clients', [ClientController::class, 'store'])->name('admin.clients.store');
    Route::put('/admin/clients/{client}', [ClientController::class, 'update'])->name('admin.clients.update');
    Route::delete('/admin/clients/{client}', [ClientController::class, 'destroy'])->name('admin.clients.destroy');
    Route::patch('/admin/clients/{client}/toggle-status', [ClientController::class, 'toggleStatus'])->name('admin.clients.toggle-status');

    Route::get('/admin/areas', [AreaController::class, 'index'])->name('admin.areas.index');
    Route::post('/admin/areas', [AreaController::class, 'store'])->name('admin.areas.store');
    Route::put('/admin/areas/{area}', [AreaController::class, 'update'])->name('admin.areas.update');
    Route::delete('/admin/areas/{area}', [AreaController::class, 'destroy'])->name('admin.areas.destroy');

    Route::get('/admin/element-types', [ElementTypeController::class, 'index'])->name('admin.element-types.index');
    Route::post('/admin/element-types', [ElementTypeController::class, 'store'])->name('admin.element-types.store');
    Route::put('/admin/element-types/{elementType}', [ElementTypeController::class, 'update'])->name('admin.element-types.update');
    Route::delete('/admin/element-types/{elementType}', [ElementTypeController::class, 'destroy'])->name('admin.element-types.destroy');

    Route::get('/admin/components', [ComponentController::class, 'index'])->name('admin.components.index');
    Route::post('/admin/components', [ComponentController::class, 'store'])->name('admin.components.store');
    Route::put('/admin/components/{component}', [ComponentController::class, 'update'])->name('admin.components.update');
    Route::patch('/admin/components/{component}/toggle-status', [ComponentController::class, 'toggleStatus'])
    ->name('admin.components.toggle-status');
    //Route::delete('/admin/components/{component}', [ComponentController::class, 'destroy'])->name('admin.components.destroy');

    Route::get('/admin/diagnostics', [DiagnosticController::class, 'index'])->name('admin.diagnostics.index');
    Route::post('/admin/diagnostics', [DiagnosticController::class, 'store'])->name('admin.diagnostics.store');
    Route::put('/admin/diagnostics/{diagnostic}', [DiagnosticController::class, 'update'])->name('admin.diagnostics.update');
    Route::delete('/admin/diagnostics/{diagnostic}', [DiagnosticController::class, 'destroy'])->name('admin.diagnostics.destroy');

    Route::get('/admin/component-diagnostics', [ComponentDiagnosticController::class, 'index'])->name('admin.component-diagnostics.index');
    Route::post('/admin/component-diagnostics', [ComponentDiagnosticController::class, 'store'])->name('admin.component-diagnostics.store');
    Route::put('/admin/component-diagnostics/{componentDiagnostic}', [ComponentDiagnosticController::class, 'update'])->name('admin.component-diagnostics.update');
    Route::delete('/admin/component-diagnostics/{componentDiagnostic}', [ComponentDiagnosticController::class, 'destroy'])->name('admin.component-diagnostics.destroy');

    Route::get('/admin/conditions', [ConditionController::class, 'index'])->name('admin.conditions.index');
    Route::post('/admin/conditions', [ConditionController::class, 'store'])->name('admin.conditions.store');
    Route::put('/admin/conditions/{condition}', [ConditionController::class, 'update'])->name('admin.conditions.update');
    Route::delete('/admin/conditions/{condition}', [ConditionController::class, 'destroy'])->name('admin.conditions.destroy');
    Route::patch('/admin/conditions/{condition}/toggle-status', [ConditionController::class, 'toggleStatus'])->name('admin.conditions.toggle-status');

    Route::get('/admin/elements', [ElementController::class, 'index'])->name('admin.elements.index');
    Route::post('/admin/elements', [ElementController::class, 'store'])->name('admin.elements.store');
    Route::put('/admin/elements/{element}', [ElementController::class, 'update'])->name('admin.elements.update');
    Route::delete('/admin/elements/{element}', [ElementController::class, 'destroy'])->name('admin.elements.destroy');

    Route::get('/admin/element-components', [ElementComponentController::class, 'index'])->name('admin.element-components.index');
    Route::post('/admin/element-components', [ElementComponentController::class, 'store'])->name('admin.element-components.store');
    Route::put('/admin/element-components/{elementComponent}', [ElementComponentController::class, 'update'])->name('admin.element-components.update');
    Route::delete('/admin/element-components/{elementComponent}', [ElementComponentController::class, 'destroy'])->name('admin.element-components.destroy');

    Route::get('/admin/users', [UserController::class, 'index'])->name('admin.users.index');
    Route::post('/admin/users', [UserController::class, 'store'])->name('admin.users.store');
    Route::put('/admin/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
    Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');


    

    Route::get('/inspector/reports', [InspectorReportController::class, 'index'])->name('inspector.reports.index');
    Route::post('/inspector/reports', [InspectorReportController::class, 'store'])->name('inspector.reports.store');

    Route::get('/inspector/clients/{client}/areas', [InspectorReportController::class, 'getAreasByClient'])->name('inspector.clients.areas');
    Route::get('/inspector/clients/{client}/conditions', [InspectorReportController::class, 'getConditionsByClient'])->name('inspector.clients.conditions');

    Route::get('/inspector/areas/{area}/elements', [InspectorReportController::class, 'getElementsByArea'])->name('inspector.areas.elements');
    Route::get('/inspector/elements/{element}/components', [InspectorReportController::class, 'getComponentsByElement'])->name('inspector.elements.components');
    Route::get('/inspector/components/{component}/diagnostics', [InspectorReportController::class, 'getDiagnosticsByComponent'])->name('inspector.components.diagnostics');
    Route::get('/inspector/elements/{element}/pending-diagnostics', [InspectorReportController::class, 'getPendingDiagnostics'])->name('inspector.elements.pending-diagnostics');

    //Admin2
    Route::get('/admin/managed-users', [AdminManagedUserController::class, 'index'])->name('admin.managed-users.index');
    Route::post('/admin/managed-users', [AdminManagedUserController::class, 'store'])->name('admin.managed-users.store');
    Route::put('/admin/managed-users/{user}', [AdminManagedUserController::class, 'update'])->name('admin.managed-users.update');
    Route::delete('/admin/managed-users/{user}', [AdminManagedUserController::class, 'destroy'])->name('admin.managed-users.destroy');
    Route::patch('/admin/managed-users/{user}/toggle-status', [AdminManagedUserController::class, 'toggleStatus'])->name('admin.managed-users.toggle-status');


    Route::get('/admin/managed-areas', [AdminAreaController::class, 'index'])->name('admin.managed-areas.index');
    Route::post('/admin/managed-areas', [AdminAreaController::class, 'store'])->name('admin.managed-areas.store');
    Route::put('/admin/managed-areas/{area}', [AdminAreaController::class, 'update'])->name('admin.managed-areas.update');
    Route::delete('/admin/managed-areas/{area}', [AdminAreaController::class, 'destroy'])->name('admin.managed-areas.destroy');
    Route::patch('/admin/managed-areas/{area}/toggle-status', [AdminAreaController::class, 'toggleStatus'])->name('admin.managed-areas.toggle-status');

    Route::get('/admin/managed-element-types', [AdminElementTypeController::class, 'index'])->name('admin.managed-element-types.index');
    Route::post('/admin/managed-element-types', [AdminElementTypeController::class, 'store'])->name('admin.managed-element-types.store');
    Route::put('/admin/managed-element-types/{elementType}', [AdminElementTypeController::class, 'update'])->name('admin.managed-element-types.update');
    Route::delete('/admin/managed-element-types/{elementType}', [AdminElementTypeController::class, 'destroy'])->name('admin.managed-element-types.destroy');
    Route::patch('/admin/managed-element-types/{elementType}/toggle-status', [AdminElementTypeController::class, 'toggleStatus'])->name('admin.managed-element-types.toggle-status');

    Route::get('/admin/managed-diagnostics', [AdminDiagnosticController::class, 'index'])->name('admin.managed-diagnostics.index');
    Route::post('/admin/managed-diagnostics', [AdminDiagnosticController::class, 'store'])->name('admin.managed-diagnostics.store');
    Route::put('/admin/managed-diagnostics/{diagnostic}', [AdminDiagnosticController::class, 'update'])->name('admin.managed-diagnostics.update');
    Route::delete('/admin/managed-diagnostics/{diagnostic}', [AdminDiagnosticController::class, 'destroy'])->name('admin.managed-diagnostics.destroy');
    Route::patch('/admin/managed-diagnostics/{diagnostic}/toggle-status', [AdminDiagnosticController::class, 'toggleStatus'])->name('admin.managed-diagnostics.toggle-status');

    Route::get('/admin/managed-conditions', [AdminConditionController::class, 'index'])->name('admin.managed-conditions.index');
    Route::post('/admin/managed-conditions', [AdminConditionController::class, 'store'])->name('admin.managed-conditions.store');
    Route::put('/admin/managed-conditions/{condition}', [AdminConditionController::class, 'update'])->name('admin.managed-conditions.update');
    Route::delete('/admin/managed-conditions/{condition}', [AdminConditionController::class, 'destroy'])->name('admin.managed-conditions.destroy');
    Route::patch('/admin/managed-conditions/{condition}/toggle-status', [AdminConditionController::class, 'toggleStatus'])->name('admin.managed-conditions.toggle-status');

    Route::get('/admin/managed-components', [AdminComponentController::class, 'index'])->name('admin.managed-components.index');
    Route::post('/admin/managed-components', [AdminComponentController::class, 'store'])->name('admin.managed-components.store');
    Route::put('/admin/managed-components/{component}', [AdminComponentController::class, 'update'])->name('admin.managed-components.update');
    Route::delete('/admin/managed-components/{component}', [AdminComponentController::class, 'destroy'])->name('admin.managed-components.destroy');
    Route::patch('/admin/managed-components/{component}/toggle-status', [AdminComponentController::class, 'toggleStatus'])->name('admin.managed-components.toggle-status');
    Route::get('/admin/clients/{client}/element-types', [AdminComponentController::class, 'getElementTypesByClient'])->name('admin.clients.element-types');

    Route::get('/admin/component-diagnostics', [AdminComponentDiagnosticController::class, 'index'])->name('admin.component-diagnostics.index');
    Route::post('/admin/component-diagnostics', [AdminComponentDiagnosticController::class, 'store'])->name('admin.component-diagnostics.store');
    Route::get('/admin/cd/clients/{client}/element-types', [AdminComponentDiagnosticController::class, 'getElementTypes']);
    Route::get('/admin/cd/element-types/{elementType}/components', [AdminComponentDiagnosticController::class, 'getComponents']);
    Route::get('/admin/cd/clients/{client}/diagnostics', [AdminComponentDiagnosticController::class, 'getDiagnostics']);
    Route::get('/admin/cd/components/{component}/assigned', [AdminComponentDiagnosticController::class, 'getAssigned']);

   
    Route::get('/admin/managed-elements', [AdminElementController::class, 'index'])->name('admin.managed-elements.index');
    Route::post('/admin/managed-elements', [AdminElementController::class, 'store'])->name('admin.managed-elements.store');
    Route::put('/admin/managed-elements/{element}', [AdminElementController::class, 'update'])->name('admin.managed-elements.update');
    Route::post('/admin/managed-elements/{element}/components', [AdminElementController::class, 'syncComponents'])->name('admin.managed-elements.components.sync');
    Route::delete('/admin/managed-elements/{element}', [AdminElementController::class, 'destroy'])->name('admin.managed-elements.destroy');
    Route::patch('/admin/managed-elements/{element}/toggle-status', [AdminElementController::class, 'toggleStatus'])->name('admin.managed-elements.toggle-status');

    Route::get('/admin/clients/{client}/areas', [AdminElementController::class, 'getAreasByClient'])->name('admin.clients.areas');
    Route::get('/admin/clients/{client}/element-types', [AdminElementController::class, 'getElementTypesByClient'])->name('admin.clients.element-types');

    //--
    Route::get('/admin/preventive-reports/general/{client}', [AdminPreventiveReportController::class, 'general'])->name('admin.preventive-reports.general');


    Route::get('/admin/preventive-reports/{client}/{elementType}', [AdminPreventiveReportController::class, 'show'])->name('admin.preventive-reports.show');

    Route::patch('/admin/preventive-reports/report-details/{reportDetail}/toggle-execution', [AdminPreventiveReportController::class, 'toggleExecution'])->name('admin.preventive-reports.toggle-execution');

    Route::get('/admin/report-evidence/{file}/open', [AdminReportEvidenceController::class, 'open'])->name('admin.report-evidence.open');


});