<?php

namespace App\Http\Controllers\Personal;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use App\Support\PersonalGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PersonalAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('personal.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // 1. Empleados del modulo (supervisor/administrativo).
        $employee = Employee::where('username', $credentials['username'])
            ->where('has_login', true)
            ->where('activo', true)
            ->first();

        if ($employee && Hash::check($credentials['password'], $employee->password)) {
            Auth::guard('personal')->login($employee);
            $request->session()->regenerate();

            return redirect()->intended($this->destinoTrasLogin());
        }

        // 2. Superadmin: reusa el guard `web` existente, no tiene fila en
        // `employees` (ver seccion 3 del documento — invisible a los
        // catalogos de este modulo).
        $user = User::where('username', $credentials['username'])
            ->where('status', true)
            ->first();

        if ($user && $user->role?->key === 'superadmin' && Hash::check($credentials['password'], $user->password)) {
            Auth::guard('web')->login($user);
            $request->session()->regenerate();

            return redirect()->intended($this->destinoTrasLogin());
        }

        return back()
            ->withErrors(['login' => 'Credenciales incorrectas.'])
            ->withInput($request->only('username'));
    }

    public function logout(Request $request): RedirectResponse
    {
        if (Auth::guard('personal')->check()) {
            Auth::guard('personal')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('personal.login');
        }

        // Superadmin actuando via el guard `web`: no tocar esa sesion (el
        // panel admin real sigue abierto), solo salir del modulo.
        if (PersonalGuard::isSuperadmin()) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('personal.login');
    }

    // Primer modulo al que el actor tiene acceso (ver
    // PersonalGuard::firstAccessibleRoute) — superadmin siempre cae en
    // Empleados igual que antes; un empleado sin ningun permiso (ej. un
    // rol recien creado sin nada marcado) va a una pantalla explicativa
    // en vez de un 403 crudo justo despues de loguearse.
    private function destinoTrasLogin(): string
    {
        $ruta = PersonalGuard::firstAccessibleRoute();

        return route($ruta ?? 'personal.sin-acceso');
    }
}
