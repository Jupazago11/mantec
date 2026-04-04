<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ElementType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $allowedRoleKeys = [
            'admin',
            'admin_cliente',
            'inspector',
            'observador',
            'observador_cliente',
        ];

        $users = User::with([
                'role',
                'clients',
                'allowedElementTypes',
            ])
            ->whereHas('role', function ($query) use ($allowedRoleKeys) {
                $query->whereIn('key', $allowedRoleKeys);
            })
            ->orderByDesc('id')
            ->paginate(10);

        $roles = Role::whereIn('key', $allowedRoleKeys)
            ->where('status', true)
            ->orderBy('name')
            ->get();

        $clients = Client::where('status', true)
            ->orderBy('name')
            ->get();

        $elementTypesByClient = ElementType::where('status', true)
            ->orderBy('name')
            ->get()
            ->groupBy('client_id');

        return view('admin.users.index', [
            'users' => $users,
            'roles' => $roles,
            'clients' => $clients,
            'elementTypesByClient' => $elementTypesByClient,
            'showClientColumn' => true,
            'authUserId' => auth()->id(),
            'filterOptions' => [
                'client_ids' => $clients->map(fn ($client) => [
                    'value' => (string) $client->id,
                    'label' => $client->name,
                ])->values(),

                'names' => User::whereHas('role', function ($query) use ($allowedRoleKeys) {
                        $query->whereIn('key', $allowedRoleKeys);
                    })
                    ->pluck('name')
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values(),

                'role_keys' => $roles->map(fn ($role) => [
                    'value' => $role->key,
                    'label' => $role->name,
                ])->values(),

                'statuses' => collect([
                    ['value' => '1', 'label' => 'Activo'],
                    ['value' => '0', 'label' => 'Inactivo'],
                ]),
            ],
            'activeFilters' => [
                'client_ids' => [],
                'names' => [],
                'role_keys' => [],
                'statuses' => [],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $allowedRoleKeys = [
            'admin',
            'admin_cliente',
            'inspector',
            'observador',
            'observador_cliente',
        ];

        $rolesRequiringClients = [
            'admin',
            'admin_cliente',
            'inspector',
            'observador',
            'observador_cliente',
        ];

        $rolesRequiringSpecialties = [
            'admin_cliente',
            'inspector',
            'observador',
            'observador_cliente',
        ];

        $allowedRoleIds = Role::whereIn('key', $allowedRoleKeys)->pluck('id')->toArray();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'document' => ['nullable', 'string', 'max:50'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'max:255'],
            'role_id' => ['required', Rule::in($allowedRoleIds)],
            'clients' => ['nullable', 'array'],
            'clients.*' => ['exists:clients,id'],
            'status' => ['required', 'boolean'],
            'element_type_permissions' => ['nullable', 'array'],
        ]);

        $role = Role::findOrFail($validated['role_id']);

        if (in_array($role->key, $rolesRequiringClients, true) && empty($validated['clients'])) {
            return back()
                ->withErrors([
                    'clients' => 'Debes asignar al menos un cliente para este rol.',
                ])
                ->withInput();
        }

        if (in_array($role->key, $rolesRequiringSpecialties, true)) {
            $permissions = $request->input('element_type_permissions', []);
            $hasAnyPermission = false;

            foreach (($validated['clients'] ?? []) as $clientId) {
                if (!empty($permissions[$clientId] ?? [])) {
                    $hasAnyPermission = true;
                    break;
                }
            }

            if (!$hasAnyPermission) {
                return back()
                    ->withErrors([
                        'element_type_permissions' => 'Debes asignar al menos una especialidad para los clientes seleccionados.',
                    ])
                    ->withInput();
            }
        }

        $user = User::create([
            'name' => trim($validated['name']),
            'document' => $validated['document'] ? trim($validated['document']) : null,
            'username' => trim($validated['username']),
            'email' => $validated['email'] ? trim($validated['email']) : null,
            'password' => Hash::make($validated['password']),
            'role_id' => $validated['role_id'],
            'status' => (bool) $validated['status'],
        ]);

        if (in_array($role->key, $rolesRequiringClients, true)) {
            $user->clients()->sync($validated['clients'] ?? []);
        }

        $this->syncElementTypePermissions(
            $user,
            $role->key,
            $validated['clients'] ?? [],
            $request->input('element_type_permissions', [])
        );

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $allowedRoleKeys = [
            'admin',
            'admin_cliente',
            'inspector',
            'observador',
            'observador_cliente',
        ];

        $rolesRequiringClients = [
            'admin',
            'admin_cliente',
            'inspector',
            'observador',
            'observador_cliente',
        ];

        $rolesRequiringSpecialties = [
            'admin_cliente',
            'inspector',
            'observador',
            'observador_cliente',
        ];

        if (!in_array($user->role?->key, $allowedRoleKeys, true)) {
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
            'password' => ['nullable', 'string', 'min:6', 'max:255'],
            'role_id' => ['required', Rule::in($allowedRoleIds)],
            'clients' => ['nullable', 'array'],
            'clients.*' => ['exists:clients,id'],
            'status' => ['required', 'boolean'],
            'element_type_permissions' => ['nullable', 'array'],
        ]);

        $role = Role::findOrFail($validated['role_id']);

        if (in_array($role->key, $rolesRequiringClients, true) && empty($validated['clients'])) {
            return back()
                ->withErrors([
                    'clients' => 'Debes asignar al menos un cliente para este rol.',
                ])
                ->withInput();
        }

        if (in_array($role->key, $rolesRequiringSpecialties, true)) {
            $permissions = $request->input('element_type_permissions', []);
            $hasAnyPermission = false;

            foreach (($validated['clients'] ?? []) as $clientId) {
                if (!empty($permissions[$clientId] ?? [])) {
                    $hasAnyPermission = true;
                    break;
                }
            }

            if (!$hasAnyPermission) {
                return back()
                    ->withErrors([
                        'element_type_permissions' => 'Debes asignar al menos una especialidad para los clientes seleccionados.',
                    ])
                    ->withInput();
            }
        }

        $data = [
            'name' => trim($validated['name']),
            'document' => $validated['document'] ? trim($validated['document']) : null,
            'username' => trim($validated['username']),
            'email' => $validated['email'] ? trim($validated['email']) : null,
            'role_id' => $validated['role_id'],
            'status' => (bool) $validated['status'],
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        if (in_array($role->key, $rolesRequiringClients, true)) {
            $user->clients()->sync($validated['clients'] ?? []);
        } else {
            $user->clients()->sync([]);
        }

        $this->syncElementTypePermissions(
            $user,
            $role->key,
            $validated['clients'] ?? [],
            $request->input('element_type_permissions', [])
        );

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $allowedRoleKeys = [
            'admin',
            'admin_cliente',
            'inspector',
            'observador',
            'observador_cliente',
        ];

        if (!in_array($user->role?->key, $allowedRoleKeys, true)) {
            abort(403, 'No autorizado para eliminar este usuario.');
        }

        $user->clients()->detach();
        $user->allowedElementTypes()->detach();
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }

    private function syncElementTypePermissions(User $user, string $roleKey, array $clientIds, array $permissionsByClient): void
    {
        $rolesRequiringSpecialties = [
            'admin_cliente',
            'inspector',
            'observador',
            'observador_cliente',
        ];

        if (!in_array($roleKey, $rolesRequiringSpecialties, true)) {
            $user->allowedElementTypes()->detach();
            return;
        }

        $syncData = [];

        foreach ($clientIds as $clientId) {
            $elementTypeIds = $permissionsByClient[$clientId] ?? [];

            foreach ($elementTypeIds as $elementTypeId) {
                $syncData[$elementTypeId] = [
                    'client_id' => $clientId,
                ];
            }
        }

        $user->allowedElementTypes()->sync($syncData);
    }
}
