<?php

namespace App\Http\Controllers\Personal;

use App\Http\Controllers\Controller;
use App\Models\PersonalCategory;
use App\Models\PersonalRole;
use App\Support\PersonalGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Modulo "Roles y permisos" — exclusivo de superadmin, a proposito no
 * forma parte de los 6 permisos configurables (si un subrol pudiera
 * administrar permisos, podria autoasignarse acceso que no deberia
 * tener).
 */
class PersonalRoleController extends Controller
{
    private const PERMISOS = [
        'ver_empleados',
        'ver_programacion',
        'editar_programacion_sin_limite',
        'ver_diario_campo',
        'cerrar_diario_campo',
        'ver_bitacora',
    ];

    public function index(): View
    {
        abort_unless(PersonalGuard::isSuperadmin(), 403);

        return view('personal.roles.index', [
            // Jerarquia completa Rol -> Subrol: cada categoria trae ya
            // cargados sus subroles (con conteo de empleados) para la
            // pantalla agrupada.
            'categorias' => PersonalCategory::with(['roles' => function ($query) {
                $query->withCount('employees')->orderBy('name');
            }])->orderBy('name')->get(),
            'permisos' => self::PERMISOS,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        abort_unless(PersonalGuard::isSuperadmin(), 403);

        $validated = $this->validated($request);

        $role = PersonalRole::create($validated);
        // Sin esto, 'activo' viaja en el JSON de respuesta como false: el
        // modelo en memoria nunca recibe el default de BD (true) porque no
        // vino en $validated — mismo bug ya corregido en
        // EmployeeController::store().
        $role->refresh();

        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Subrol creado correctamente.',
                'role' => $this->serialize($role),
            ]);
        }

        return redirect()->route('personal.roles.index')->with('success', 'Subrol creado correctamente.');
    }

    public function update(Request $request, PersonalRole $personalRole): RedirectResponse|JsonResponse
    {
        abort_unless(PersonalGuard::isSuperadmin(), 403);

        $validated = $this->validated($request, $personalRole);

        $personalRole->update($validated);

        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Subrol actualizado correctamente.',
                'role' => $this->serialize($personalRole),
            ]);
        }

        return redirect()->route('personal.roles.index')->with('success', 'Subrol actualizado correctamente.');
    }

    // Reemplaza el borrado fisico anterior: mismo criterio que
    // Company::archived/Employee::activo (nunca eliminar de verdad).
    // Archivar sigue exigiendo 0 empleados asignados; reactivar no tiene
    // esa restriccion porque, por construccion, un rol archivado no puede
    // tener empleados (se archivo estando en cero).
    public function toggleActive(Request $request, PersonalRole $personalRole): RedirectResponse|JsonResponse
    {
        abort_unless(PersonalGuard::isSuperadmin(), 403);

        if ($personalRole->activo && $personalRole->employees()->exists()) {
            $message = 'No se puede archivar un subrol con empleados asignados. Reasígnalos primero desde Empleados.';

            if ($this->isAjaxRequest($request)) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return redirect()->route('personal.roles.index')->with('error', $message);
        }

        $personalRole->update(['activo' => ! $personalRole->activo]);

        $message = $personalRole->activo
            ? 'Subrol activado correctamente.'
            : 'Subrol archivado correctamente.';

        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'role' => $this->serialize($personalRole),
            ]);
        }

        return redirect()->route('personal.roles.index')->with('success', $message);
    }

    private function isAjaxRequest(Request $request): bool
    {
        return $request->expectsJson() || $request->ajax();
    }

    private function serialize(PersonalRole $role): array
    {
        $data = [
            'id' => $role->id,
            'name' => $role->name,
            'personal_category_id' => $role->personal_category_id,
            'activo' => (bool) $role->activo,
            'employees_count' => $role->employees()->count(),
            'disponible_en_programacion' => (bool) $role->disponible_en_programacion,
            'responsable_actividad' => (bool) $role->responsable_actividad,
        ];

        foreach (self::PERMISOS as $permiso) {
            $data[$permiso] = (bool) $role->{$permiso};
        }

        return $data;
    }

    private function validated(Request $request, ?PersonalRole $role = null): array
    {
        $nameRule = Rule::unique('personal_roles', 'name');
        if ($role) {
            $nameRule = $nameRule->ignore($role->id);
        }

        $rules = [
            'name' => ['required', 'string', 'max:100', $nameRule],
            'personal_category_id' => ['required', Rule::exists('personal_categories', 'id')->where('activo', true)],
            // No es un permiso de acceso (no pasa por PersonalGuard::can()),
            // es una regla de visibilidad para el selector de personas de
            // Programacion — se valida aparte de PERMISOS a proposito.
            'disponible_en_programacion' => ['sometimes', 'boolean'],
            // Igual criterio: no es permiso de acceso, es una regla de
            // elegibilidad para el selector de Responsable de Programacion.
            // Se combina con OR junto a personal_categories.responsable_actividad
            // en ActivityController — permite marcar solo este subrol sin
            // tener que activar el Rol completo (ver migracion
            // 2026_09_18_150000 y la del Rol, 2026_09_18_140000).
            'responsable_actividad' => ['sometimes', 'boolean'],
        ];
        foreach (self::PERMISOS as $permiso) {
            $rules[$permiso] = ['sometimes', 'boolean'];
        }

        $validated = $request->validate($rules);

        $validated['disponible_en_programacion'] = $request->boolean('disponible_en_programacion');
        $validated['responsable_actividad'] = $request->boolean('responsable_actividad');
        foreach (self::PERMISOS as $permiso) {
            $validated[$permiso] = $request->boolean($permiso);
        }

        return $validated;
    }
}
