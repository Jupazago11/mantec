<?php

namespace App\Http\Controllers\Personal;

use App\Http\Controllers\Controller;
use App\Models\PersonalRole;
use App\Support\PersonalGuard;
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
            'roles' => PersonalRole::withCount('employees')->orderBy('name')->get(),
            'permisos' => self::PERMISOS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(PersonalGuard::isSuperadmin(), 403);

        $validated = $this->validated($request);

        PersonalRole::create($validated);

        return redirect()->route('personal.roles.index')->with('success', 'Rol creado correctamente.');
    }

    public function update(Request $request, PersonalRole $personalRole): RedirectResponse
    {
        abort_unless(PersonalGuard::isSuperadmin(), 403);

        $validated = $this->validated($request, $personalRole);

        $personalRole->update($validated);

        return redirect()->route('personal.roles.index')->with('success', 'Rol actualizado correctamente.');
    }

    public function destroy(PersonalRole $personalRole): RedirectResponse
    {
        abort_unless(PersonalGuard::isSuperadmin(), 403);

        if ($personalRole->employees()->exists()) {
            return redirect()
                ->route('personal.roles.index')
                ->with('error', 'No se puede eliminar un rol con empleados asignados. Reasígnalos primero desde Empleados.');
        }

        $personalRole->delete();

        return redirect()->route('personal.roles.index')->with('success', 'Rol eliminado correctamente.');
    }

    private function validated(Request $request, ?PersonalRole $role = null): array
    {
        $nameRule = Rule::unique('personal_roles', 'name');
        if ($role) {
            $nameRule = $nameRule->ignore($role->id);
        }

        $rules = [
            'name' => ['required', 'string', 'max:100', $nameRule],
        ];
        foreach (self::PERMISOS as $permiso) {
            $rules[$permiso] = ['sometimes', 'boolean'];
        }

        $validated = $request->validate($rules);

        foreach (self::PERMISOS as $permiso) {
            $validated[$permiso] = $request->boolean($permiso);
        }

        return $validated;
    }
}
