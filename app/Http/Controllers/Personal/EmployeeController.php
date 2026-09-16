<?php

namespace App\Http\Controllers\Personal;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PersonalRole;
use App\Support\PersonalGuard;
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
            'empleados' => Employee::orderBy('nombre')->get(),
            'empresas' => Company::orderBy('archived')->orderByDesc('is_default')->orderBy('name')->get(),
            'roles' => PersonalRole::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(PersonalGuard::can('ver_empleados'), 403);

        $validated = $this->validated($request);

        $employee = new Employee();
        $employee->fill($this->preparePayload($validated, true));
        $employee->save();

        return redirect()
            ->route('personal.empleados.index')
            ->with('success', 'Empleado creado correctamente.');
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        abort_unless(PersonalGuard::can('ver_empleados'), 403);

        $validated = $this->validated($request, $employee);

        $employee->fill($this->preparePayload($validated, false, $employee));
        $employee->save();

        return redirect()
            ->route('personal.empleados.index')
            ->with('success', 'Empleado actualizado correctamente.');
    }

    public function toggleStatus(Employee $employee): RedirectResponse
    {
        abort_unless(PersonalGuard::can('ver_empleados'), 403);

        $employee->update(['activo' => ! $employee->activo]);

        $message = $employee->activo
            ? 'Empleado activado correctamente.'
            : 'Empleado inactivado correctamente.';

        return redirect()->route('personal.empleados.index')->with('success', $message);
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
            'abreviatura' => ['nullable', 'string', 'max:10'],
            'categoria' => ['required', Rule::in(['Campo', 'Administrativos'])],
            'has_login' => ['sometimes', 'boolean'],
            'in_bitacora' => ['sometimes', 'boolean'],
            'personal_role_id' => ['nullable', Rule::exists('personal_roles', 'id')],
            'username' => ['nullable', 'string', 'max:50', $usernameRule],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        // Regla confirmada 2026-09-16: Campo nunca tiene usuario de acceso
        // ni rol — validacion de aplicacion, no constraint de base de
        // datos (para no bloquear una futura excepcion sin migracion).
        $validator->after(function ($validator) use ($request, $employee) {
            $categoria = $request->input('categoria');
            $hasLogin = $request->boolean('has_login');
            $role = $request->input('personal_role_id');

            if ($categoria === 'Campo' && ($hasLogin || filled($role))) {
                $validator->errors()->add(
                    'categoria',
                    'Los empleados de categoría Campo no pueden tener usuario de acceso ni rol.'
                );
            }

            if ($hasLogin && $categoria === 'Administrativos') {
                if (blank($request->input('username'))) {
                    $validator->errors()->add('username', 'El usuario es obligatorio si tiene acceso.');
                }

                if (blank($request->input('password')) && ! $employee) {
                    $validator->errors()->add('password', 'La contraseña es obligatoria al crear con acceso habilitado.');
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
            'abreviatura' => filled($validated['abreviatura'] ?? null) ? trim($validated['abreviatura']) : null,
            'categoria' => $validated['categoria'],
            'has_login' => $hasLogin,
            'in_bitacora' => (bool) ($validated['in_bitacora'] ?? false),
            'personal_role_id' => $hasLogin ? ($validated['personal_role_id'] ?? null) : null,
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
