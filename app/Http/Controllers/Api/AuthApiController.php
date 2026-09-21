<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthApiController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->with([
                'role:id,name,key',
                'clients:id,name',
                'allowedElementTypes:id,name,client_id',
            ])
            ->where('username', $credentials['username'])
            ->where('status', true)
            ->first();

        if ($user && Hash::check($credentials['password'], $user->password)) {
            return $this->respondWithUser($user);
        }

        // Login unico para Inspector (User) y Supervisor (Employee): si
        // las credenciales no matchean un User activo, se prueba contra
        // Employee antes de rechazar. Evita que la app tenga que elegir
        // de antemano contra que tabla autenticar (ver LOGIN_Y_ROLES.md).
        $employee = Employee::query()
            ->with('personalRole:id,name,responsable_actividad')
            ->where('username', $credentials['username'])
            ->where('has_login', true)
            ->where('activo', true)
            ->first();

        if ($employee && Hash::check($credentials['password'], $employee->password)) {
            return $this->respondWithEmployee($employee);
        }

        return response()->json([
            'success' => false,
            'message' => 'Credenciales incorrectas.',
        ], 401);
    }

    private function respondWithUser(User $user): JsonResponse
    {
        $token = $user->createToken('android-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login correcto.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => [
                    'id' => $user->role?->id,
                    'name' => $user->role?->name,
                    'key' => $user->role?->key,
                ],
                'clients' => $user->clients->map(fn ($client) => [
                    'id' => $client->id,
                    'name' => $client->name,
                ])->values(),
                'allowed_element_types' => $user->allowedElementTypes->map(fn ($type) => [
                    'id' => $type->id,
                    'name' => $type->name,
                    'client_id' => $type->client_id,
                ])->values(),
            ],
        ]);
    }

    private function respondWithEmployee(Employee $employee): JsonResponse
    {
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
