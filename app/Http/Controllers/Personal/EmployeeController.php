<?php

namespace App\Http\Controllers\Personal;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PersonalCategory;
use App\Models\PersonalRole;
use App\Support\PersonalGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(): View
    {
        abort_unless(PersonalGuard::can('ver_empleados'), 403);

        return view('personal.empleados.index', [
            'empleados' => Employee::with('personalCategory:id,name')->orderBy('nombre')->get(),
            'empresas' => Company::orderBy('archived')->orderByDesc('is_default')->orderBy('name')->get(),
            'categorias' => PersonalCategory::where('activo', true)->orderBy('name')->get(),
            'roles' => PersonalRole::where('activo', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        abort_unless(PersonalGuard::can('ver_empleados'), 403);

        $validated = $this->validated($request);

        $employee = new Employee();
        $employee->fill($this->preparePayload($validated, true));
        $employee->save();

        // 'activo' no se fija arriba (queda al default de BD = true) —
        // refrescar para no devolver un valor en memoria desactualizado
        // (serialize() leeria null -> false por el cast boolean).
        $employee->refresh();

        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Empleado creado correctamente.',
                'employee' => $this->serialize($employee),
            ]);
        }

        return redirect()
            ->route('personal.empleados.index')
            ->with('success', 'Empleado creado correctamente.');
    }

    public function update(Request $request, Employee $employee): RedirectResponse|JsonResponse
    {
        abort_unless(PersonalGuard::can('ver_empleados'), 403);

        $validated = $this->validated($request, $employee);

        $employee->fill($this->preparePayload($validated, false, $employee));
        $employee->save();

        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Empleado actualizado correctamente.',
                'employee' => $this->serialize($employee),
            ]);
        }

        return redirect()
            ->route('personal.empleados.index')
            ->with('success', 'Empleado actualizado correctamente.');
    }

    public function toggleStatus(Request $request, Employee $employee): RedirectResponse|JsonResponse
    {
        abort_unless(PersonalGuard::can('ver_empleados'), 403);

        $employee->update(['activo' => ! $employee->activo]);

        $message = $employee->activo
            ? 'Empleado activado correctamente.'
            : 'Empleado inactivado correctamente.';

        if ($this->isAjaxRequest($request)) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'employee' => $this->serialize($employee),
            ]);
        }

        return redirect()->route('personal.empleados.index')->with('success', $message);
    }

    // Mismo criterio que AdminManagedUserController::isAjaxRequest — el
    // fetch() del frontend manda Accept: application/json.
    private function isAjaxRequest(Request $request): bool
    {
        return $request->expectsJson() || $request->ajax();
    }

    private function serialize(Employee $employee): array
    {
        $employee->loadMissing('personalRole:id,name', 'personalCategory:id,name');

        return [
            'id' => $employee->id,
            'nombre' => $employee->nombre,
            'nickname' => $employee->nickname,
            'personal_category_id' => $employee->personal_category_id,
            'categoria' => $employee->personalCategory->name,
            'has_login' => (bool) $employee->has_login,
            'username' => $employee->username,
            'personal_role_id' => $employee->personal_role_id,
            'personal_role_name' => $employee->personalRole?->name,
            'in_bitacora' => (bool) $employee->in_bitacora,
            'activo' => (bool) $employee->activo,
        ];
    }

    private function validated(Request $request, ?Employee $employee = null): array
    {
        $usernameRule = Rule::unique('employees', 'username');
        if ($employee) {
            $usernameRule = $usernameRule->ignore($employee->id);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => ['required', 'string', 'max:150'],
            'nickname' => ['required', 'string', 'max:50'],
            'personal_category_id' => ['required', Rule::exists('personal_categories', 'id')->where('activo', true)],
            'has_login' => ['sometimes', 'boolean'],
            'in_bitacora' => ['sometimes', 'boolean'],
            'personal_role_id' => ['nullable', Rule::exists('personal_roles', 'id')],
            'username' => ['nullable', 'string', 'max:50', $usernameRule],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        // Confirmado 2026-09-17: se quita la regla anterior de que "Campo
        // nunca tiene usuario de acceso" — cualquier Rol/categoria puede
        // tener empleados con login, es el subrol (y sus permisos, o la
        // ausencia de ellos) el que decide que puede hacer cada quien.
        $validator->after(function ($validator) use ($request, $employee) {
            $hasLogin = $request->boolean('has_login');

            if ($hasLogin) {
                if (blank($request->input('username'))) {
                    $validator->errors()->add('username', 'El usuario es obligatorio si tiene acceso.');
                }

                if (blank($request->input('password')) && ! $employee) {
                    $validator->errors()->add('password', 'La contraseña es obligatoria al crear con acceso habilitado.');
                }
            }

            // Consistencia: un subrol pertenece a una categoria especifica
            // (personal_roles.personal_category_id) — no se puede asignar
            // el subrol "Supervisor" (de Administrativos) a un empleado de
            // categoria "Campo", por ejemplo.
            $roleId = $request->input('personal_role_id');
            $categoryId = $request->input('personal_category_id');

            if (filled($roleId) && filled($categoryId)) {
                $role = PersonalRole::find($roleId);

                if ($role && (int) $role->personal_category_id !== (int) $categoryId) {
                    $validator->errors()->add(
                        'personal_role_id',
                        'El subrol seleccionado no pertenece a la categoría (Rol) elegida.'
                    );
                }
            }
        });

        return $validator->validate();
    }

    private function preparePayload(array $validated, bool $creating, ?Employee $employee = null): array
    {
        $hasLogin = (bool) ($validated['has_login'] ?? false);

        $payload = [
            'nombre' => trim($validated['nombre']),
            'nickname' => trim($validated['nickname']),
            'personal_category_id' => $validated['personal_category_id'],
            'has_login' => $hasLogin,
            'in_bitacora' => (bool) ($validated['in_bitacora'] ?? false),
            // El subrol ya no depende de tener login: se elige junto con el
            // Rol (categoria) sin importar si el empleado tiene acceso o no
            // (ej. un supervisor de Campo sin usuario igual necesita su
            // subrol para aparecer/no aparecer en Programacion).
            'personal_role_id' => $validated['personal_role_id'] ?? null,
            'username' => $hasLogin ? trim($validated['username']) : null,
        ];

        if ($hasLogin && filled($validated['password'] ?? null)) {
            $payload['password'] = Hash::make($validated['password']);
        } elseif (! $hasLogin) {
            $payload['password'] = null;
        }
        // Si $hasLogin=true y no llego password nueva (edicion), no se
        // toca — conserva la actual, mismo patron que
        // AdminManagedUserController::update.

        return $payload;
    }
}
