<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $allowedRoleKeys = ['admin', 'admin_cliente', 'inspector'];

        $users = User::with(['role', 'clients'])
            ->whereHas('role', function ($query) use ($allowedRoleKeys) {
                $query->whereIn('key', $allowedRoleKeys);
            })
            ->orderByDesc('id')
            ->get();

        $roles = Role::whereIn('key', $allowedRoleKeys)
            ->where('status', true)
            ->orderBy('name')
            ->get();

        $clients = Client::where('status', true)
            ->orderBy('name')
            ->get();

        return view('admin.users.index', compact('users', 'roles', 'clients'));
    }

    public function store(Request $request): RedirectResponse
    {
        $allowedRoleKeys = ['admin', 'admin_cliente', 'inspector'];
        $rolesRequiringClients = ['admin', 'admin_cliente', 'inspector'];

        $allowedRoleIds = Role::whereIn('key', $allowedRoleKeys)->pluck('id')->toArray();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'document' => ['nullable', 'string', 'max:50'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'max:255'],
            'role_id' => ['required', Rule::in($allowedRoleIds)],
            'status' => ['required', 'boolean'],
            'clients' => ['nullable', 'array'],
            'clients.*' => ['exists:clients,id'],
        ]);

        $role = Role::findOrFail($validated['role_id']);

        if (in_array($role->key, $rolesRequiringClients) && empty($validated['clients'])) {
            return back()
                ->withErrors([
                    'clients' => 'Debes asignar al menos un cliente para este rol.',
                ])
                ->withInput();
        }

        $user = User::create([
            'name' => $validated['name'],
            'document' => $validated['document'] ?? null,
            'username' => $validated['username'],
            'email' => $validated['email'] ?? null,
            'password' => $validated['password'],
            'role_id' => $validated['role_id'],
            'status' => $validated['status'],
        ]);

        if (in_array($role->key, $rolesRequiringClients)) {
            $user->clients()->sync($validated['clients'] ?? []);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $allowedRoleKeys = ['admin', 'admin_cliente', 'inspector'];
        $rolesRequiringClients = ['admin', 'admin_cliente', 'inspector'];

        if (!in_array($user->role?->key, $allowedRoleKeys)) {
            abort(403, 'No autorizado para editar este usuario.');
        }

        $allowedRoleIds = Role::whereIn('key', $allowedRoleKeys)->pluck('id')->toArray();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'document' => ['nullable', 'string', 'max:50'],
            'username' => [
                'required',
                'string',
                'max:50',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['nullable', 'string', 'max:255'],
            'role_id' => ['required', Rule::in($allowedRoleIds)],
            'status' => ['required', 'boolean'],
            'clients' => ['nullable', 'array'],
            'clients.*' => ['exists:clients,id'],
        ]);

        $role = Role::findOrFail($validated['role_id']);

        if (in_array($role->key, $rolesRequiringClients) && empty($validated['clients'])) {
            return back()
                ->withErrors([
                    'clients' => 'Debes asignar al menos un cliente para este rol.',
                ])
                ->withInput();
        }

        $data = [
            'name' => $validated['name'],
            'document' => $validated['document'] ?? null,
            'username' => $validated['username'],
            'email' => $validated['email'] ?? null,
            'role_id' => $validated['role_id'],
            'status' => $validated['status'],
        ];

        if (!empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        $user->update($data);

        if (in_array($role->key, $rolesRequiringClients)) {
            $user->clients()->sync($validated['clients'] ?? []);
        } else {
            $user->clients()->sync([]);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $allowedRoleKeys = ['admin', 'admin_cliente', 'inspector'];

        if (!in_array($user->role?->key, $allowedRoleKeys)) {
            abort(403, 'No autorizado para eliminar este usuario.');
        }

        $user->clients()->detach();
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }
}