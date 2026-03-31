@extends('layouts.admin')

@section('title', 'Usuarios')
@section('header_title', 'Usuarios')

@section('content')
@php
    $currentFilters = [
        'filter_client_id' => request('filter_client_id'),
        'filter_name' => request('filter_name'),
        'page' => request('page'),
    ];
@endphp

<div class="space-y-8">
    <div>
        <h2 class="text-3xl font-bold tracking-tight text-slate-900">Gestión de usuarios</h2>
        <p class="mt-2 text-slate-600">
            Crea y administra administradores, administradores cliente e inspectores.
        </p>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <div class="font-semibold">Hay errores en el formulario.</div>
            <ul class="mt-2 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-8 xl:grid-cols-12">
        <div class="xl:col-span-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900">Nuevo usuario</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Asigna rol, clientes y, si aplica, tipos de activo permitidos.
                </p>

                <form method="POST" action="{{ route('admin.managed-users.store', $currentFilters) }}" class="mt-6 space-y-5">
                    @csrf

                    <x-form.input name="name" label="Nombre" placeholder="Nombre completo" />
                    <x-form.input name="document" label="Documento" placeholder="Opcional" />
                    <x-form.input name="username" label="Usuario" placeholder="Nombre de usuario" />
                    <x-form.input name="password" label="Contraseña" type="password" placeholder="Mínimo 6 caracteres" />

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Rol</label>
                        <select
                            name="role_id"
                            id="create_role_id"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            onchange="toggleSpecializedPermissions('create')"
                        >
                            <option value="">Seleccione un rol</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" data-role-key="{{ $role->key }}" @selected(old('role_id') == $role->id)>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Clientes</label>
                        <div class="max-h-56 space-y-2 overflow-y-auto rounded-xl border border-slate-300 p-4">
                            @foreach($clients as $client)
                                <label class="flex items-center gap-3 text-sm text-slate-700">
                                    <input
                                        type="checkbox"
                                        name="clients[]"
                                        value="{{ $client->id }}"
                                        class="create-client-checkbox rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                                        {{ in_array($client->id, old('clients', [])) ? 'checked' : '' }}
                                        onchange="toggleClientElementTypes('create')"
                                    >
                                    {{ $client->name }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div id="create_specialized_permissions_wrapper" class="hidden">
                        <label class="mb-2 block text-sm font-medium text-slate-700">
                            Tipos de activo permitidos por cliente
                        </label>

                        <div class="space-y-4 rounded-xl border border-slate-300 p-4">
                            @foreach($clients as $client)
                                <div class="create-client-element-types-block hidden" data-client-id="{{ $client->id }}">
                                    <p class="mb-2 text-sm font-semibold text-slate-900">{{ $client->name }}</p>

                                    <div class="grid gap-2">
                                        @forelse(($elementTypesByClient[$client->id] ?? collect()) as $elementType)
                                            <label class="flex items-center gap-3 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700">
                                                <input
                                                    type="checkbox"
                                                    name="element_type_permissions[{{ $client->id }}][]"
                                                    value="{{ $elementType->id }}"
                                                    class="rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                                                    {{ in_array($elementType->id, old("element_type_permissions.$client->id", [])) ? 'checked' : '' }}
                                                >
                                                {{ $elementType->name }}
                                            </label>
                                        @empty
                                            <p class="text-sm text-slate-500">No hay tipos de activo para este cliente.</p>
                                        @endforelse
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                    >
                        Guardar usuario
                    </button>
                </form>
            </div>
        </div>

        <div class="xl:col-span-8">
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <h3 class="text-lg font-semibold text-slate-900">Listado de usuarios</h3>

                        <form method="GET" action="{{ route('admin.managed-users.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                            <div>
                                <label for="filter_client_id" class="mb-2 block text-sm font-medium text-slate-700">
                                    Cliente
                                </label>

                                <select
                                    name="filter_client_id"
                                    id="filter_client_id"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                >
                                    <option value="">Todos</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}" @selected(request('filter_client_id') == $client->id)>
                                            {{ $client->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="filter_name" class="mb-2 block text-sm font-medium text-slate-700">
                                    Nombre
                                </label>

                                <input
                                    type="text"
                                    name="filter_name"
                                    id="filter_name"
                                    value="{{ request('filter_name') }}"
                                    placeholder="Buscar usuario"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                >
                            </div>

                            <div class="flex gap-2">
                                <button
                                    type="submit"
                                    class="rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                                >
                                    Filtrar
                                </button>

                                <a
                                    href="{{ route('admin.managed-users.index') }}"
                                    class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                                >
                                    Limpiar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Nombre</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Usuario</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Rol</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Clientes</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Especialidad</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Estado</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Acciones</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-200 bg-white">
                            @forelse($users as $user)
                                @php
                                    $roleKey = $user->role?->key;
                                    $specializedMap = $user->allowedElementTypes
                                        ->groupBy(fn($item) => $item->pivot->client_id);

                                    $isProtectedAdmin = $roleKey === 'admin';
                                @endphp

                                <tr class="hover:bg-slate-50">
                                    <td class="whitespace-nowrap px-5 py-3 text-sm font-medium text-slate-900">
                                        {{ $user->name }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700">
                                        {{ $user->username }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700">
                                        {{ $user->role?->name ?? '—' }}
                                    </td>

                                    <td class="px-5 py-3 text-sm text-slate-700">
                                        {{ $user->clients->pluck('name')->implode(', ') ?: '—' }}
                                    </td>

                                    <td class="px-5 py-3 text-sm text-slate-700">
                                        @if(in_array($roleKey, ['inspector', 'admin_cliente']) && $specializedMap->isNotEmpty())
                                            <div class="space-y-1">
                                                @foreach($user->clients as $client)
                                                    @php
                                                        $types = $specializedMap->get($client->id, collect());
                                                    @endphp
                                                    @if($types->isNotEmpty())
                                                        <div>
                                                            <span class="font-semibold text-slate-900">{{ $client->name }}:</span>
                                                            {{ $types->pluck('name')->implode(', ') }}
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @else
                                            —
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-3 text-sm">
                                        @if($user->status)
                                            <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                                Activo
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                                                Inactivo
                                            </span>
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-3 text-right">
                                        <div class="flex justify-end gap-2">
                                            @php
                                                $editPayload = [
                                                    'id' => $user->id,
                                                    'name' => $user->name,
                                                    'document' => $user->document,
                                                    'username' => $user->username,
                                                    'role_id' => $user->role_id,
                                                    'role_key' => $user->role?->key,
                                                    'clients' => $user->clients->pluck('id')->values()->toArray(),
                                                    'permissions' => $user->allowedElementTypes
                                                        ->groupBy(fn ($item) => $item->pivot->client_id)
                                                        ->map(fn ($group) => $group->pluck('id')->values()->toArray())
                                                        ->toArray(),
                                                    'action' => route('admin.managed-users.update', array_merge(['user' => $user->id], $currentFilters)),
                                                ];
                                            @endphp

                                            <button
                                                type="button"
                                                class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                                                onclick='openEditUserModal(@json($editPayload))'
                                            >
                                                Editar
                                            </button>

                                            @unless($isProtectedAdmin)
                                                <form
                                                    method="POST"
                                                    action="{{ route('admin.managed-users.toggle-status', array_merge(['user' => $user->id], $currentFilters)) }}"
                                                >
                                                    @csrf
                                                    @method('PATCH')

                                                    <button
                                                        type="submit"
                                                        class="rounded-lg px-3 py-2 text-xs font-semibold text-white transition {{ $user->status ? 'bg-amber-500 hover:bg-amber-600' : 'bg-green-600 hover:bg-green-700' }}"
                                                    >
                                                        {{ $user->status ? 'Inactivar' : 'Activar' }}
                                                    </button>
                                                </form>

                                                <form
                                                    method="POST"
                                                    action="{{ route('admin.managed-users.destroy', array_merge(['user' => $user->id], $currentFilters)) }}"
                                                    onsubmit="return confirm('¿Seguro que deseas eliminar este usuario?');"
                                                >
                                                    @csrf
                                                    @method('DELETE')

                                                    <button
                                                        type="submit"
                                                        class="rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-red-700"
                                                    >
                                                        Eliminar
                                                    </button>
                                                </form>
                                            @endunless
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-10 text-center text-sm text-slate-500">
                                        No hay usuarios registrados todavía.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($users->hasPages())
                    <div class="border-t border-slate-200 px-5 py-4">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div id="editUserModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
    <div class="w-full max-w-4xl rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <h3 class="text-lg font-semibold text-slate-900">Editar usuario</h3>
            <button type="button" class="text-slate-500 hover:text-slate-900" onclick="closeEditUserModal()">✕</button>
        </div>

        <form id="editUserForm" method="POST" class="space-y-5 p-6">
            @csrf
            @method('PUT')

            <x-form.input name="name" label="Nombre" id="edit_name" />
            <x-form.input name="document" label="Documento" id="edit_document" />
            <x-form.input name="username" label="Usuario" id="edit_username" />
            <x-form.input name="password" label="Contraseña" type="password" id="edit_password" placeholder="Dejar en blanco para no cambiar" />

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Rol</label>
                <select
                    name="role_id"
                    id="edit_role_id"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                    onchange="toggleSpecializedPermissions('edit')"
                >
                    <option value="">Seleccione un rol</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" data-role-key="{{ $role->key }}">
                            {{ $role->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Clientes</label>
                <div class="max-h-56 space-y-2 overflow-y-auto rounded-xl border border-slate-300 p-4">
                    @foreach($clients as $client)
                        <label class="flex items-center gap-3 text-sm text-slate-700">
                            <input
                                type="checkbox"
                                name="clients[]"
                                value="{{ $client->id }}"
                                class="edit-client-checkbox rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                                onchange="toggleClientElementTypes('edit')"
                            >
                            {{ $client->name }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div id="edit_specialized_permissions_wrapper" class="hidden">
                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Tipos de activo permitidos por cliente
                </label>

                <div class="space-y-4 rounded-xl border border-slate-300 p-4">
                    @foreach($clients as $client)
                        <div class="edit-client-element-types-block hidden" data-client-id="{{ $client->id }}">
                            <p class="mb-2 text-sm font-semibold text-slate-900">{{ $client->name }}</p>

                            <div class="grid gap-2">
                                @forelse(($elementTypesByClient[$client->id] ?? collect()) as $elementType)
                                    <label class="flex items-center gap-3 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700">
                                        <input
                                            type="checkbox"
                                            name="element_type_permissions[{{ $client->id }}][]"
                                            value="{{ $elementType->id }}"
                                            class="edit-element-type-checkbox rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                                            data-client-id="{{ $client->id }}"
                                        >
                                        {{ $elementType->name }}
                                    </label>
                                @empty
                                    <p class="text-sm text-slate-500">No hay tipos de activo para este cliente.</p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            @foreach($currentFilters as $filterKey => $filterValue)
                @if($filterValue !== null && $filterValue !== '')
                    <input type="hidden" name="{{ $filterKey }}" value="{{ $filterValue }}">
                @endif
            @endforeach

            <div class="flex justify-end gap-3">
                <button
                    type="button"
                    onclick="closeEditUserModal()"
                    class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                >
                    Cancelar
                </button>

                <button
                    type="submit"
                    class="rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                >
                    Actualizar usuario
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function getSelectedRoleKey(prefix) {
        const select = document.getElementById(`${prefix}_role_id`);
        const option = select.options[select.selectedIndex];
        return option ? option.dataset.roleKey : '';
    }

    function roleUsesSpecialization(roleKey) {
        return ['inspector', 'admin_cliente'].includes(roleKey);
    }

    function toggleSpecializedPermissions(prefix) {
        const wrapper = document.getElementById(`${prefix}_specialized_permissions_wrapper`);
        const roleKey = getSelectedRoleKey(prefix);

        if (roleUsesSpecialization(roleKey)) {
            wrapper.classList.remove('hidden');
        } else {
            wrapper.classList.add('hidden');
        }
    }

    function toggleClientElementTypes(prefix) {
        const roleKey = getSelectedRoleKey(prefix);
        const selectedClientIds = Array.from(document.querySelectorAll(`.${prefix}-client-checkbox:checked`))
            .map(cb => parseInt(cb.value));

        document.querySelectorAll(`.${prefix}-client-element-types-block`).forEach(block => {
            const clientId = parseInt(block.dataset.clientId);
            const visible = roleUsesSpecialization(roleKey) && selectedClientIds.includes(clientId);

            block.classList.toggle('hidden', !visible);

            if (!visible) {
                block.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                    cb.checked = false;
                });
            }
        });
    }

    function openEditUserModal(user) {
        document.getElementById('editUserForm').action = user.action;
        document.getElementById('edit_name').value = user.name ?? '';
        document.getElementById('edit_document').value = user.document ?? '';
        document.getElementById('edit_username').value = user.username ?? '';
        document.getElementById('edit_password').value = '';
        document.getElementById('edit_role_id').value = user.role_id ?? '';

        document.querySelectorAll('.edit-client-checkbox').forEach(cb => {
            cb.checked = (user.clients || []).includes(parseInt(cb.value));
        });

        document.querySelectorAll('.edit-element-type-checkbox').forEach(cb => {
            cb.checked = false;
        });

        Object.entries(user.permissions || {}).forEach(([clientId, elementTypeIds]) => {
            elementTypeIds.forEach(elementTypeId => {
                const checkbox = document.querySelector(
                    `.edit-element-type-checkbox[data-client-id="${clientId}"][value="${elementTypeId}"]`
                );
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
        });

        toggleSpecializedPermissions('edit');
        toggleClientElementTypes('edit');

        const modal = document.getElementById('editUserModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeEditUserModal() {
        const modal = document.getElementById('editUserModal');
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }

    document.addEventListener('DOMContentLoaded', function () {
        toggleSpecializedPermissions('create');
        toggleClientElementTypes('create');
    });
</script>
@endsection
