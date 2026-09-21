<?php

namespace App\Http\Controllers\Personal;

use App\Http\Controllers\Controller;
use App\Models\PersonalCategory;
use App\Support\PersonalGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * "Rol" superior de la jerarquia Rol -> Subrol (pedido por el usuario
 * 2026-09-17): antes `employees.categoria` era un string fijo
 * (Campo/Administrativos) sin ningun CRUD detras. Vive en la misma
 * pantalla que PersonalRoleController (personal.roles.index le pasa
 * ambos niveles), exclusivo de superadmin igual que el resto del modulo.
 */
class PersonalCategoryController extends Controller
{
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        abort_unless(PersonalGuard::isSuperadmin(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('personal_categories', 'name')],
            'responsable_actividad' => ['sometimes', 'boolean'],
        ]);

        $category = PersonalCategory::create([
            'name' => trim($validated['name']),
            'activo' => true,
            'responsable_actividad' => $request->boolean('responsable_actividad'),
        ]);

        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Rol creado correctamente.',
                'category' => $this->serialize($category),
            ]);
        }

        return redirect()->route('personal.roles.index')->with('success', 'Rol creado correctamente.');
    }

    public function update(Request $request, PersonalCategory $personalCategory): RedirectResponse|JsonResponse
    {
        abort_unless(PersonalGuard::isSuperadmin(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('personal_categories', 'name')->ignore($personalCategory->id)],
            'responsable_actividad' => ['sometimes', 'boolean'],
        ]);

        $personalCategory->update([
            'name' => trim($validated['name']),
            'responsable_actividad' => $request->boolean('responsable_actividad'),
        ]);

        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Rol actualizado correctamente.',
                'category' => $this->serialize($personalCategory),
            ]);
        }

        return redirect()->route('personal.roles.index')->with('success', 'Rol actualizado correctamente.');
    }

    // Mismo criterio que PersonalRole/Company/Employee: nunca se elimina
    // de verdad. Archivar exige 0 empleados asignados directamente a este
    // Rol (sin importar el subrol) — reactivar no tiene esa restriccion.
    public function toggleActive(Request $request, PersonalCategory $personalCategory): RedirectResponse|JsonResponse
    {
        abort_unless(PersonalGuard::isSuperadmin(), 403);

        if ($personalCategory->activo && $personalCategory->employees()->exists()) {
            $message = 'No se puede archivar un rol con empleados asignados. Reasígnalos primero desde Empleados.';

            if ($this->isAjaxRequest($request)) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return redirect()->route('personal.roles.index')->with('error', $message);
        }

        $personalCategory->update(['activo' => ! $personalCategory->activo]);

        $message = $personalCategory->activo
            ? 'Rol activado correctamente.'
            : 'Rol archivado correctamente.';

        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'category' => $this->serialize($personalCategory),
            ]);
        }

        return redirect()->route('personal.roles.index')->with('success', $message);
    }

    private function isAjaxRequest(Request $request): bool
    {
        return $request->expectsJson() || $request->ajax();
    }

    private function serialize(PersonalCategory $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'activo' => (bool) $category->activo,
            'employees_count' => $category->employees()->count(),
            'responsable_actividad' => (bool) $category->responsable_actividad,
        ];
    }
}
