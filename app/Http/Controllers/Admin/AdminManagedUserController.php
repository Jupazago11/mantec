<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminManagedUserController extends Controller
{
    public function index(): View
    {
        $authUser = Auth::user();
        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        $manageableRoleKeys = ['admin_cliente', 'inspector'];
        $visibleRoleKeys = ['admin', 'admin_cliente', 'inspector'];

        $users = User::with(['role', 'clients'])
            ->whereHas('role', function ($query) use ($visibleRoleKeys) {
                $query->whereIn('key', $visibleRoleKeys);
            })
            ->whereHas('clients', function ($query) use ($allowedClientIds) {
                $query->whereIn('clients.id', $allowedClientIds);
            })
            ->withCount(['reports', 'reportDetails'])
            ->orderByDesc('id')
            ->get();

        $creatableRoles = Role::whereIn('key', $manageableRoleKeys)
            ->where('status', true)
            ->orderBy('name')
            ->get();

        $clients = Client::whereIn('id', $allowedClientIds)
            ->where('status', true)
            ->orderBy('name')
            ->get();

        return view('admin.managed-users.index', compact('users', 'creatableRoles', 'clients'));
    }

    public function store(Request $request): RedirectResponse
    {
        $authUser = Auth::user();
        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        $manageableRoleKeys = ['admin_cliente', 'inspector'];
        $manageableRoleIds = Role::whereIn('key', $manageableRoleKeys)->pluck('id')->toArray();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'document' => ['nullable', 'string', 'max:50'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'max:255'],
            'role_id' => ['required', Rule::in($manageableRoleIds)],
            'status' => ['required', 'boolean'],
            'clients' => ['required', 'array', 'min:1'],
            'clients.*' => ['integer', Rule::in($allowedClientIds)],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'document' => $validated['document'] ?? null,
            'username' => $validated['username'],
            'email' => $validated['email'] ?? null,
            'password' => $validated['password'],
            'role_id' => $validated['role_id'],
            'status' => $validated['status'],
        ]);

        $user->clients()->sync($validated['clients']);

        return redirect()
            ->route('admin.managed-users.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $authUser = Auth::user();
        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        $manageableRoleKeys = ['admin_cliente', 'inspector'];
        $manageableRoleIds = Role::whereIn('key', $manageableRoleKeys)->pluck('id')->toArray();

        $this->abortIfNotManageableByAdmin($user, $allowedClientIds);

        if (!in_array($user->role?->key, $manageableRoleKeys)) {
            abort(403, 'No autorizado para editar este usuario.');
        }

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
            'role_id' => ['required', Rule::in($manageableRoleIds)],
            'status' => ['required', 'boolean'],
            'clients' => ['required', 'array', 'min:1'],
            'clients.*' => ['integer', Rule::in($allowedClientIds)],
        ]);

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
        $user->clients()->sync($validated['clients']);

        return redirect()
            ->route('admin.managed-users.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $authUser = Auth::user();
        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        $this->abortIfNotManageableByAdmin($user, $allowedClientIds);

        if (!in_array($user->role?->key, ['admin_cliente', 'inspector'])) {
            abort(403, 'No autorizado para eliminar este usuario.');
        }

        if ($user->hasTraceability()) {
            return redirect()
                ->route('admin.managed-users.index')
                ->with('success', 'El usuario tiene registros relacionados y no puede eliminarse.');
        }

        $user->clients()->detach();
        $user->delete();

        return redirect()
            ->route('admin.managed-users.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        $authUser = Auth::user();
        $allowedClientIds = $authUser->clients()->pluck('clients.id')->toArray();

        $this->abortIfNotManageableByAdmin($user, $allowedClientIds);

        if (!in_array($user->role?->key, ['admin_cliente', 'inspector'])) {
            abort(403, 'No autorizado para cambiar estado de este usuario.');
        }

        if (!$user->hasTraceability()) {
            return redirect()
                ->route('admin.managed-users.index')
                ->with('success', 'Este usuario no tiene trazabilidad. Puedes eliminarlo si lo deseas.');
        }

        $user->update([
            'status' => !$user->status,
        ]);

        return redirect()
            ->route('admin.managed-users.index')
            ->with('success', 'Estado del usuario actualizado correctamente.');
    }

    private function abortIfNotManageableByAdmin(User $user, array $allowedClientIds): void
    {
        $sharesClient = $user->clients()->whereIn('clients.id', $allowedClientIds)->exists();

        if (!$sharesClient) {
            abort(403, 'No autorizado para gestionar este usuario.');
        }
    }
}