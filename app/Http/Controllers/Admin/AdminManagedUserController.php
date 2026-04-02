<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ElementType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminManagedUserController extends Controller
{
    public function index(Request $request): View
    {
        $authUser = auth()->user();

        $clients = $authUser->clients()
            ->where('clients.status', true)
            ->orderBy('clients.name')
            ->get(['clients.id', 'clients.name']);

        $clientIds = $clients->pluck('id')->all();
        $singleClient = $clients->count() === 1 ? $clients->first() : null;
        $showClientColumn = $clients->count() > 1;

        $assignableRoles = Role::query()
            ->whereIn('key', ['admin_cliente', 'inspector'])
            ->where('status', true)
            ->orderBy('name')
            ->get();

        $visibleRoles = Role::query()
            ->whereIn('key', ['admin', 'admin_cliente', 'inspector'])
            ->where('status', true)
            ->orderBy('name')
            ->get();

        $elementTypesByClient = ElementType::query()
            ->whereIn('client_id', $clientIds)
            ->where('status', true)
            ->orderBy('name')
            ->get()
            ->groupBy('client_id');

        $selectedClientIds = $showClientColumn
            ? collect($request->input('client_ids', []))->filter()->map(fn ($id) => (string) $id)->values()->all()
            : ($singleClient ? [(string) $singleClient->id] : []);

        $selectedNames = collect($request->input('names', []))
            ->filter()
            ->map(fn ($value) => (string) $value)
            ->values()
            ->all();

        $selectedRoleKeys = collect($request->input('role_keys', []))
            ->filter()
            ->map(fn ($value) => (string) $value)
            ->values()
            ->all();

        $selectedStatuses = collect($request->input('statuses', []))
            ->filter()
            ->map(fn ($value) => (string) $value)
            ->values()
            ->all();

        $baseQuery = User::query()
            ->with([
                'role',
                'clients',
                'allowedElementTypes',
            ])
            ->where(function ($query) use ($clientIds, $authUser) {
                $query->whereHas('clients', function ($subQuery) use ($clientIds) {
                    $subQuery->whereIn('clients.id', $clientIds);
                })->orWhere('id', $authUser->id);
            })
            ->whereHas('role', function ($query) {
                $query->whereIn('key', ['admin', 'admin_cliente', 'inspector']);
            });

        if (!empty($selectedClientIds)) {
            $baseQuery->where(function ($query) use ($selectedClientIds, $authUser) {
                $query->whereHas('clients', function ($subQuery) use ($selectedClientIds) {
                    $subQuery->whereIn('clients.id', $selectedClientIds);
                });

                if (in_array((string) $authUser->id, [(string) $authUser->id], true)) {
                    $query->orWhere('id', $authUser->id);
                }
            });
        }

        if (!empty($selectedNames)) {
            $baseQuery->whereIn('name', $selectedNames);
        }

        if (!empty($selectedRoleKeys)) {
            $baseQuery->whereHas('role', function ($query) use ($selectedRoleKeys) {
                $query->whereIn('key', $selectedRoleKeys);
            });
        }

        if (!empty($selectedStatuses)) {
            $baseQuery->whereIn('status', array_map(fn ($v) => (int) $v, $selectedStatuses));
        }

        $users = (clone $baseQuery)
            ->orderBy('name')
            ->paginate(8)
            ->withQueryString();

        $allVisibleUsers = (clone $baseQuery)->get();

        $clientFilterOptions = $showClientColumn
            ? $clients->map(fn ($client) => [
                'value' => (string) $client->id,
                'label' => $client->name,
            ])->values()
            : collect();

        $nameFilterOptions = $allVisibleUsers->pluck('name')
            ->filter()
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $roleFilterOptions = $visibleRoles->map(fn ($role) => [
            'value' => $role->key,
            'label' => $role->name,
        ])->values();

        $statusFilterOptions = collect([
            ['value' => '1', 'label' => 'Activo'],
            ['value' => '0', 'label' => 'Inactivo'],
        ]);

        $filterOptions = [
            'client_ids' => $clientFilterOptions,
            'names' => $nameFilterOptions,
            'role_keys' => $roleFilterOptions,
            'statuses' => $statusFilterOptions,
        ];

        $activeFilters = [
            'client_ids' => $selectedClientIds,
            'names' => $selectedNames,
            'role_keys' => $selectedRoleKeys,
            'statuses' => $selectedStatuses,
        ];

        return view('admin.managed-users.index', [
            'users' => $users,
            'clients' => $clients,
            'singleClient' => $singleClient,
            'showClientColumn' => $showClientColumn,
            'roles' => $assignableRoles,
            'visibleRoles' => $visibleRoles,
            'elementTypesByClient' => $elementTypesByClient,
            'filterOptions' => $filterOptions,
            'activeFilters' => $activeFilters,
            'authUserId' => $authUser->id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $authUser = auth()->user();

        $allowedClientIds = $authUser->clients()
            ->where('clients.status', true)
            ->pluck('clients.id')
            ->all();

        $assignableRoleIds = Role::query()
            ->whereIn('key', ['admin_cliente', 'inspector'])
            ->pluck('id')
            ->all();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'document' => ['nullable', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'password' => ['required', 'string', 'min:6'],
            'role_id' => ['required', Rule::in($assignableRoleIds)],
            'clients' => ['required', 'array', 'min:1'],
            'clients.*' => [Rule::in($allowedClientIds)],
            'element_type_permissions' => ['nullable', 'array'],
        ]);

        $role = Role::findOrFail($validated['role_id']);
        $clientIds = collect($validated['clients'])->map(fn ($id) => (int) $id)->unique()->values()->all();

        DB::transaction(function () use ($validated, $role, $clientIds) {
            $user = User::create([
                'name' => trim($validated['name']),
                'document' => $validated['document'] ? trim($validated['document']) : null,
                'username' => trim($validated['username']),
                'password' => Hash::make($validated['password']),
                'role_id' => $role->id,
                'status' => true,
            ]);

            $user->clients()->sync($clientIds);

            $this->syncElementTypePermissions(
                $user,
                $role->key,
                $clientIds,
                $validated['element_type_permissions'] ?? []
            );
        });

        return redirect()
            ->route('admin.managed-users.index', $this->buildRedirectQuery($request))
            ->with('success', 'Usuario creado correctamente.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $authUser = auth()->user();

        abort_unless($this->canViewUser($authUser, $user), 403);

        if ((int) $user->id === (int) $authUser->id) {
            $validated = $request->validate([
                'password' => ['required', 'string', 'min:6'],
            ], [
                'password.required' => 'Para tu propio usuario solo puedes cambiar la contraseña.',
            ]);

            $user->update([
                'password' => Hash::make($validated['password']),
            ]);

            return redirect()
                ->route('admin.managed-users.index', $this->buildRedirectQuery($request))
                ->with('success', 'Tu contraseña fue actualizada correctamente.');
        }

        abort_unless($this->canManageTargetUser($authUser, $user), 403);

        $allowedClientIds = $authUser->clients()
            ->where('clients.status', true)
            ->pluck('clients.id')
            ->all();

        $assignableRoleIds = Role::query()
            ->whereIn('key', ['admin_cliente', 'inspector'])
            ->pluck('id')
            ->all();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'document' => ['nullable', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'role_id' => ['required', Rule::in($assignableRoleIds)],
            'clients' => ['required', 'array', 'min:1'],
            'clients.*' => [Rule::in($allowedClientIds)],
            'element_type_permissions' => ['nullable', 'array'],
        ]);

        $role = Role::findOrFail($validated['role_id']);
        $clientIds = collect($validated['clients'])->map(fn ($id) => (int) $id)->unique()->values()->all();

        DB::transaction(function () use ($user, $validated, $role, $clientIds) {
            $payload = [
                'name' => trim($validated['name']),
                'document' => $validated['document'] ? trim($validated['document']) : null,
                'username' => trim($validated['username']),
                'role_id' => $role->id,
            ];

            if (!empty($validated['password'])) {
                $payload['password'] = Hash::make($validated['password']);
            }

            $user->update($payload);
            $user->clients()->sync($clientIds);

            $this->syncElementTypePermissions(
                $user,
                $role->key,
                $clientIds,
                $validated['element_type_permissions'] ?? []
            );
        });

        return redirect()
            ->route('admin.managed-users.index', $this->buildRedirectQuery($request))
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        $authUser = auth()->user();

        abort_unless($this->canManageTargetUser($authUser, $user), 403);
        abort_if((int) $user->id === (int) $authUser->id, 403);

        $user->update([
            'status' => !$user->status,
        ]);

        return redirect()
            ->route('admin.managed-users.index', $this->buildRedirectQuery($request))
            ->with('success', 'Estado del usuario actualizado correctamente.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort(403, 'La eliminación de usuarios no está permitida en este módulo.');
    }

    private function syncElementTypePermissions(User $user, string $roleKey, array $clientIds, array $permissions): void
    {
        DB::table('user_client_element_type')
            ->where('user_id', $user->id)
            ->delete();

        if (!in_array($roleKey, ['admin_cliente', 'inspector'], true)) {
            return;
        }

        $rows = [];

        foreach ($clientIds as $clientId) {
            $elementTypeIds = collect($permissions[$clientId] ?? [])
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            $validElementTypeIds = ElementType::query()
                ->where('client_id', $clientId)
                ->whereIn('id', $elementTypeIds)
                ->pluck('id');

            foreach ($validElementTypeIds as $elementTypeId) {
                $rows[] = [
                    'user_id' => $user->id,
                    'client_id' => $clientId,
                    'element_type_id' => $elementTypeId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (!empty($rows)) {
            DB::table('user_client_element_type')->insert($rows);
        }
    }

    private function canViewUser(User $authUser, User $targetUser): bool
    {
        if ((int) $authUser->id === (int) $targetUser->id) {
            return true;
        }

        return $targetUser->clients()
            ->whereIn('clients.id', $authUser->clients()->pluck('clients.id'))
            ->exists();
    }

    private function canManageTargetUser(User $authUser, User $targetUser): bool
    {
        if ((int) $authUser->id === (int) $targetUser->id) {
            return false;
        }

        $targetRoleKey = $targetUser->role?->key;

        if (!in_array($targetRoleKey, ['admin_cliente', 'inspector'], true)) {
            return false;
        }

        return $targetUser->clients()
            ->whereIn('clients.id', $authUser->clients()->pluck('clients.id'))
            ->exists();
    }

    private function buildRedirectQuery(Request $request): array
    {
        $query = [];

        foreach ((array) $request->input('redirect_client_ids', []) as $value) {
            if ($value !== null && $value !== '') {
                $query['client_ids'][] = $value;
            }
        }

        foreach ((array) $request->input('redirect_names', []) as $value) {
            if ($value !== null && $value !== '') {
                $query['names'][] = $value;
            }
        }

        foreach ((array) $request->input('redirect_role_keys', []) as $value) {
            if ($value !== null && $value !== '') {
                $query['role_keys'][] = $value;
            }
        }

        foreach ((array) $request->input('redirect_statuses', []) as $value) {
            if ($value !== null && $value !== '') {
                $query['statuses'][] = $value;
            }
        }

        if ($request->filled('redirect_page')) {
            $query['page'] = $request->input('redirect_page');
        }

        return $query;
    }
}
