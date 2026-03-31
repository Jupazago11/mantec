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

class AdminManagedUserController extends Controller
{
    public function index(Request $request): View
    {
        $authUser = auth()->user();

        $allowedClientIds = $authUser->clients()
            ->pluck('clients.id')
            ->toArray();

        $clients = Client::whereIn('id', $allowedClientIds)
            ->where('status', true)
            ->orderBy('name')
            ->get();

        $roles = Role::whereIn('key', ['admin', 'admin_cliente', 'inspector'])
            ->where('status', true)
            ->orderBy('name')
            ->get();

        $users = User::with(['role', 'clients', 'allowedElementTypes'])
            ->whereHas('clients', function ($query) use ($allowedClientIds) {
                $query->whereIn('clients.id', $allowedClientIds);
            })
            ->when($request->filled('filter_name'), function ($query) use ($request) {
                $query->where('name', 'ilike', '%' . trim($request->filter_name) . '%');
            })
            ->when($request->filled('filter_client_id'), function ($query) use ($request, $allowedClientIds) {
                $clientId = (int) $request->filter_client_id;

                if (in_array($clientId, $allowedClientIds)) {
                    $query->whereHas('clients', function ($sub) use ($clientId) {
                        $sub->where('clients.id', $clientId);
                    });
                }
            })
            ->orderByDesc('id')
            ->paginate(8)
            ->withQueryString();

        $elementTypesByClient = ElementType::whereIn('client_id', $allowedClientIds)
            ->where('status', true)
            ->orderBy('name')
            ->get()
            ->groupBy('client_id');

        return view('admin.managed-users.index', compact(
            'users',
            'clients',
            'roles',
            'elementTypesByClient'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $authUser = auth()->user();
        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'document' => ['nullable', 'string', 'max:50'],
            'username' => ['required', 'string', 'max:100', 'unique:users,username'],
            'password' => ['required', 'string', 'min:6'],
            'role_id' => [
                'required',
                Rule::exists('roles', 'id')->where(function ($query) {
                    $query->whereIn('key', ['admin', 'admin_cliente', 'inspector']);
                }),
            ],
            'clients' => ['required', 'array', 'min:1'],
            'clients.*' => ['integer', Rule::in($allowedClientIds)],
            'element_type_permissions' => ['nullable', 'array'],
        ]);

        $role = Role::findOrFail($validated['role_id']);

        $user = User::create([
            'name' => $validated['name'],
            'document' => $validated['document'] ?? null,
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'role_id' => $validated['role_id'],
            'status' => true,
        ]);

        $clientIds = collect($validated['clients'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();

        $user->clients()->sync($clientIds);

        $this->syncSpecializedElementTypes(
            user: $user,
            roleKey: $role->key,
            allowedClientIds: $allowedClientIds,
            selectedClientIds: $clientIds,
            rawPermissions: $request->input('element_type_permissions', [])
        );

        return redirect()
            ->route('admin.managed-users.index', $this->filterQuery($request))
            ->with('success', 'Usuario creado correctamente.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $authUser = auth()->user();
        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        $this->abortIfUserOutsideScope($user, $allowedClientIds);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'document' => ['nullable', 'string', 'max:50'],
            'username' => ['required', 'string', 'max:100', Rule::unique('users', 'username')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'role_id' => [
                'required',
                Rule::exists('roles', 'id')->where(function ($query) {
                    $query->whereIn('key', ['admin', 'admin_cliente', 'inspector']);
                }),
            ],
            'clients' => ['required', 'array', 'min:1'],
            'clients.*' => ['integer', Rule::in($allowedClientIds)],
            'element_type_permissions' => ['nullable', 'array'],
        ]);

        $role = Role::findOrFail($validated['role_id']);

        $payload = [
            'name' => $validated['name'],
            'document' => $validated['document'] ?? null,
            'username' => $validated['username'],
            'role_id' => $validated['role_id'],
        ];

        if (!empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);

        $clientIds = collect($validated['clients'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();

        $user->clients()->sync($clientIds);

        $this->syncSpecializedElementTypes(
            user: $user,
            roleKey: $role->key,
            allowedClientIds: $allowedClientIds,
            selectedClientIds: $clientIds,
            rawPermissions: $request->input('element_type_permissions', [])
        );

        return redirect()
            ->route('admin.managed-users.index', $this->filterQuery($request))
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $authUser = auth()->user();
        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        $this->abortIfUserOutsideScope($user, $allowedClientIds);
        $this->abortIfProtectedAdmin($user);

        $hasDependencies =
            method_exists($user, 'reportDetails') && $user->reportDetails()->exists();

        if ($hasDependencies) {
            return redirect()
                ->route('admin.managed-users.index', $this->filterQuery($request))
                ->with('success', 'El usuario tiene registros asociados y no puede eliminarse.');
        }

        $user->clients()->detach();
        $user->allowedElementTypes()->detach();
        $user->delete();

        return redirect()
            ->route('admin.managed-users.index', $this->filterQuery($request))
            ->with('success', 'Usuario eliminado correctamente.');
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        $authUser = auth()->user();
        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        $this->abortIfUserOutsideScope($user, $allowedClientIds);
        $this->abortIfProtectedAdmin($user);

        $user->update([
            'status' => !$user->status,
        ]);

        return redirect()
            ->route('admin.managed-users.index', $this->filterQuery($request))
            ->with('success', 'Estado del usuario actualizado correctamente.');
    }

    private function syncSpecializedElementTypes(
        User $user,
        string $roleKey,
        array $allowedClientIds,
        array $selectedClientIds,
        array $rawPermissions
    ): void {
        if (!in_array($roleKey, ['inspector', 'admin_cliente'])) {
            $user->allowedElementTypes()->detach();
            return;
        }

        $validElementTypes = ElementType::whereIn('client_id', $selectedClientIds)
            ->whereIn('client_id', $allowedClientIds)
            ->pluck('client_id', 'id');

        $syncData = [];

        foreach ($rawPermissions as $clientId => $elementTypeIds) {
            $clientId = (int) $clientId;

            if (!in_array($clientId, $selectedClientIds) || !in_array($clientId, $allowedClientIds)) {
                continue;
            }

            foreach ((array) $elementTypeIds as $elementTypeId) {
                $elementTypeId = (int) $elementTypeId;

                if ((int) ($validElementTypes[$elementTypeId] ?? 0) === $clientId) {
                    $syncData[$elementTypeId] = ['client_id' => $clientId];
                }
            }
        }

        $user->allowedElementTypes()->sync($syncData);
    }

    private function abortIfUserOutsideScope(User $user, array $allowedClientIds): void
    {
        $hasAccess = $user->clients()
            ->whereIn('clients.id', $allowedClientIds)
            ->exists();

        if (!$hasAccess) {
            abort(403, 'No autorizado para gestionar este usuario.');
        }
    }

    private function abortIfProtectedAdmin(User $user): void
    {
        if ($user->role?->key === 'admin') {
            abort(403, 'No puedes eliminar ni inactivar administradores.');
        }
    }

    private function filterQuery(Request $request): array
    {
        return $request->only(['filter_client_id', 'filter_name', 'page']);
    }
}
