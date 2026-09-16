<?php

namespace App\Http\Controllers\Personal;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Support\PersonalGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        abort_unless(PersonalGuard::can('ver_empleados'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('companies', 'name')],
        ]);

        $company = Company::create([
            'name' => trim($validated['name']),
            'is_default' => false,
            'archived' => false,
        ]);

        return response()->json(['success' => true, 'company' => $company]);
    }

    public function update(Request $request, Company $company): JsonResponse
    {
        abort_unless(PersonalGuard::can('ver_empleados'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('companies', 'name')->ignore($company->id)],
        ]);

        $company->update(['name' => trim($validated['name'])]);

        return response()->json(['success' => true, 'company' => $company]);
    }

    public function toggleArchived(Company $company): JsonResponse
    {
        abort_unless(PersonalGuard::can('ver_empleados'), 403);

        $company->archived = ! $company->archived;

        // Una empresa archivada no puede seguir siendo la de seleccion por
        // defecto en Programacion.
        if ($company->archived && $company->is_default) {
            $company->is_default = false;
        }

        $company->save();

        return response()->json(['success' => true, 'company' => $company]);
    }

    public function markDefault(Company $company): JsonResponse
    {
        abort_unless(PersonalGuard::can('ver_empleados'), 403);

        if ($company->archived) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede marcar por defecto una empresa archivada.',
            ], 422);
        }

        DB::transaction(function () use ($company) {
            Company::where('id', '!=', $company->id)->update(['is_default' => false]);
            $company->update(['is_default' => true]);
        });

        return response()->json(['success' => true, 'company' => $company->fresh()]);
    }
}
