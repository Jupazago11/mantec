<?php

namespace App\Http\Controllers\Api\Personal;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

// Seccion 14.24: login API para la app Android del supervisor, calcado
// de App\Http\Controllers\Api\AuthApiController (Inspector/User) pero
// contra Employee — ver tambien PersonalAuthController (login web del
// mismo modelo, guard de sesion `personal`). Aqui no hay sesion, es
// Sanctum puro (bearer token).
class AuthApiController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $employee = Employee::query()
            ->with('personalRole:id,name,responsable_actividad')
            ->where('username', $credentials['username'])
            ->where('has_login', true)
            ->where('activo', true)
            ->first();

        if (! $employee || ! Hash::check($credentials['password'], $employee->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        $token = $employee->createToken('supervisor-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login correcto.',
            'token' => $token,
            'employee' => [
                'id' => $employee->id,
                'nombre' => $employee->nombre,
                'nickname' => $employee->nickname,
                'personal_role' => $employee->personalRole ? [
                    'id' => $employee->personalRole->id,
                    'name' => $employee->personalRole->name,
                    'responsable_actividad' => $employee->personalRole->responsable_actividad,
                ] : null,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}
