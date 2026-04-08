@extends('layouts.admin')
@section('title', 'Usuarios')
@section('header_title', 'Usuarios')

@section('content')
@php
    $hasFilter = function ($key) use ($activeFilters) {
        $value = $activeFilters[$key] ?? null;

        if (is_array($value)) {
            return count(array_filter($value, fn ($item) => $item !== null && $item !== '')) > 0;
        }

        return $value !== null && $value !== '';
    };

    $hasAnyActiveFilter =
        collect($activeFilters)->contains(function ($value) {
            if (is_array($value)) {
                return count(array_filter($value, fn ($item) => $item !== null && $item !== '')) > 0;
            }

            return $value !== null && $value !== '';
        });
@endphp

<div class="space-y-8">
    <div>
        <h2 class="text-3xl font-bold tracking-tight text-slate-900">Gestión de usuarios</h2>
        <p class="mt-2 text-slate-600">
            Crea y administra administradores cliente, inspectores, observadores y observadores cliente. Los administradores empresa solo quedan en modo lectura.
        </p>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
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

    <div class="grid gap-8 xl:grid-cols-[340px_minmax(0,1fr)]">
        <div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900">Nuevo usuario</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Solo se pueden crear usuarios de nivel inferior.
                </p>

                <form method="POST" action="{{ route('admin.managed-users.store') }}" class="mt-6 space-y-5">
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

                                    <div class="grid gap-3">
                                        @forelse(($elementTypesByClient[$client->id] ?? collect()) as $elementType)
                                            <div
                                                class="rounded-xl border border-slate-200 p-3"
                                                data-create-element-type-card
                                                data-client-id="{{ $client->id }}"
                                                data-element-type-id="{{ $elementType->id }}"
                                            >
                                                <label class="flex items-center gap-3 text-sm text-slate-700">
                                                    <input
                                                        type="checkbox"
                                                        name="element_type_permissions[{{ $client->id }}][]"
                                                        value="{{ $elementType->id }}"
                                                        class="create-element-type-checkbox rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                                                        data-client-id="{{ $client->id }}"
                                                        data-element-type-id="{{ $elementType->id }}"
                                                        onchange="toggleAreaPermissionsByElementType('create')"
                                                        {{ in_array($elementType->id, old("element_type_permissions.$client->id", [])) ? 'checked' : '' }}
                                                    >
                                                    <span>{{ $elementType->name }}</span>
                                                </label>

                                                <div
                                                    class="create-area-permissions-block mt-3 hidden rounded-lg border border-slate-200 bg-slate-50 p-3"
                                                    data-client-id="{{ $client->id }}"
                                                    data-element-type-id="{{ $elementType->id }}"
                                                >
                                                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                        Áreas permitidas para esta especialidad
                                                    </p>

                                                    <div class="grid gap-2">
                                                        @forelse(($areasByClient[$client->id] ?? collect()) as $area)
                                                            <label class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700">
                                                                <input
                                                                    type="checkbox"
                                                                    name="area_permissions[{{ $client->id }}][{{ $elementType->id }}][]"
                                                                    value="{{ $area->id }}"
                                                                    class="create-area-checkbox rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                                                                    data-client-id="{{ $client->id }}"
                                                                    data-element-type-id="{{ $elementType->id }}"
                                                                    {{ in_array($area->id, old("area_permissions.$client->id.$elementType->id", [])) ? 'checked' : '' }}
                                                                >
                                                                <span>{{ $area->name }}</span>
                                                            </label>
                                                        @empty
                                                            <p class="text-sm text-slate-500">No hay áreas para este cliente.</p>
                                                        @endforelse
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <p class="text-sm text-slate-500">No hay tipos de activo para este cliente.</p>
                                        @endforelse
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>


                    @foreach(($activeFilters['client_ids'] ?? []) as $value)
                        <input type="hidden" name="redirect_client_ids[]" value="{{ $value }}">
                    @endforeach
                    @foreach(($activeFilters['names'] ?? []) as $value)
                        <input type="hidden" name="redirect_names[]" value="{{ $value }}">
                    @endforeach
                    @foreach(($activeFilters['role_keys'] ?? []) as $value)
                        <input type="hidden" name="redirect_role_keys[]" value="{{ $value }}">
                    @endforeach
                    @foreach(($activeFilters['statuses'] ?? []) as $value)
                        <input type="hidden" name="redirect_statuses[]" value="{{ $value }}">
                    @endforeach
                    <input type="hidden" name="redirect_page" value="{{ request('page', 1) }}">

                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                    >
                        Guardar usuario
                    </button>
                </form>
            </div>
        </div>

        <div>
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <div class="flex items-center justify-between gap-4">
                        <h3 class="text-lg font-semibold text-slate-900">Listado de usuarios</h3>

                        @if($hasAnyActiveFilter)
                            <a
                                href="{{ route('admin.managed-users.index') }}"
                                class="inline-flex items-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                            >
                                Limpiar filtros
                            </a>
                        @endif
                    </div>
                </div>

                <form id="filtersForm" method="GET" class="hidden"></form>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    <div class="flex items-center gap-2">
                                        <span>Nombre</span>
                                        <button
                                            type="button"
                                            onclick="openFilterPopover(event, 'names')"
                                            class="rounded p-1 transition hover:bg-slate-200 {{ $hasFilter('names') ? 'text-[#d94d33]' : 'text-slate-400' }}"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Usuario
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    <div class="flex items-center gap-2">
                                        <span>Rol</span>
                                        <button
                                            type="button"
                                            onclick="openFilterPopover(event, 'role_keys')"
                                            class="rounded p-1 transition hover:bg-slate-200 {{ $hasFilter('role_keys') ? 'text-[#d94d33]' : 'text-slate-400' }}"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </th>

                                @if($showClientColumn)
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        <div class="flex items-center gap-2">
                                            <span>Clientes</span>
                                            <button
                                                type="button"
                                                onclick="openFilterPopover(event, 'client_ids')"
                                                class="rounded p-1 transition hover:bg-slate-200 {{ $hasFilter('client_ids') ? 'text-[#d94d33]' : 'text-slate-400' }}"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </th>
                                @endif

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Especialidad
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    <div class="flex items-center gap-2">
                                        <span>Estado</span>
                                        <button
                                            type="button"
                                            onclick="openFilterPopover(event, 'statuses')"
                                            class="rounded p-1 transition hover:bg-slate-200 {{ $hasFilter('statuses') ? 'text-[#d94d33]' : 'text-slate-400' }}"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Acciones
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-200 bg-white">
                            @forelse($users as $user)
                                @php
                                    $roleKey = $user->role?->key;
                                    $specializedMap = $user->allowedElementTypes
                                        ->groupBy(fn($item) => $item->pivot->client_id);

                                    $isSelf = (int) $user->id === (int) $authUserId;
                                    $isProtectedAdmin = !$isSelf && $roleKey === 'admin';
                                    $canManage = in_array($roleKey, ['admin_cliente', 'inspector', 'observador', 'observador_cliente'], true);
                                @endphp

                                <tr class="hover:bg-slate-50">
                                    <td class="whitespace-nowrap px-5 py-3 text-sm font-medium text-slate-900">
                                        {{ $user->name }}
                                        @if($isSelf)
                                            <span class="ml-2 rounded-lg bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-600">Tú</span>
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700">
                                        {{ $user->username }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700">
                                        {{ $user->role?->name ?? '—' }}
                                    </td>

                                    @if($showClientColumn)
                                        <td class="px-5 py-3 text-sm text-slate-700">
                                            {{ $user->clients->pluck('name')->implode(', ') ?: '—' }}
                                        </td>
                                    @endif

                                    <td class="px-5 py-3 text-sm text-slate-700">
                                        @if(in_array($roleKey, ['inspector', 'admin_cliente', 'observador', 'observador_cliente']) && $specializedMap->isNotEmpty())
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
                                                    'area_permissions' => $user->allowedAreas
                                                        ->groupBy(fn ($item) => $item->pivot->client_id)
                                                        ->map(function ($groupByClient) {
                                                            return $groupByClient
                                                                ->groupBy(fn ($item) => $item->pivot->element_type_id)
                                                                ->map(fn ($groupByType) => $groupByType->pluck('id')->values()->toArray())
                                                                ->toArray();
                                                        })
                                                        ->toArray(),
                                                    'action' => route('admin.managed-users.update', $user),
                                                    'is_self' => $isSelf,
                                                    'is_protected_admin' => $isProtectedAdmin,
                                                    'can_manage' => $canManage,
                                                ];
                                            @endphp


                                            @if($isSelf)
                                                <button
                                                    type="button"
                                                    class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                                                    onclick='openEditUserModal(@json($editPayload))'
                                                >
                                                    Cambiar contraseña
                                                </button>
                                            @elseif($canManage)
                                                <button
                                                    type="button"
                                                    class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                                                    onclick='openEditUserModal(@json($editPayload))'
                                                >
                                                    Editar
                                                </button>

                                                <form
                                                    method="POST"
                                                    action="{{ route('admin.managed-users.toggle-status', $user) }}"
                                                >
                                                    @csrf
                                                    @method('PATCH')
                                                    @foreach(($activeFilters['client_ids'] ?? []) as $value)
                                                        <input type="hidden" name="redirect_client_ids[]" value="{{ $value }}">
                                                    @endforeach
                                                    @foreach(($activeFilters['names'] ?? []) as $value)
                                                        <input type="hidden" name="redirect_names[]" value="{{ $value }}">
                                                    @endforeach
                                                    @foreach(($activeFilters['role_keys'] ?? []) as $value)
                                                        <input type="hidden" name="redirect_role_keys[]" value="{{ $value }}">
                                                    @endforeach
                                                    @foreach(($activeFilters['statuses'] ?? []) as $value)
                                                        <input type="hidden" name="redirect_statuses[]" value="{{ $value }}">
                                                    @endforeach
                                                    <input type="hidden" name="redirect_page" value="{{ $users->currentPage() }}">

                                                    <button
                                                        type="submit"
                                                        class="rounded-lg px-3 py-2 text-xs font-semibold text-white transition {{ $user->status ? 'bg-amber-500 hover:bg-amber-600' : 'bg-green-600 hover:bg-green-700' }}"
                                                    >
                                                        {{ $user->status ? 'Inactivar' : 'Activar' }}
                                                    </button>
                                                </form>
                                            @else
                                                <span class="inline-flex items-center rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-500">
                                                    Solo lectura
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $showClientColumn ? 7 : 6 }}" class="px-5 py-10 text-center text-sm text-slate-500">
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

<div id="editUserModal" class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-black/50 px-4 py-6">
    <div class="flex min-h-full w-full items-start justify-center">
        <div class="w-full max-w-4xl max-h-[90vh] flex flex-col rounded-2xl bg-white shadow-2xl">
            <div class="shrink-0 flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Editar usuario</h3>
                <button type="button" class="text-slate-500 hover:text-slate-900" onclick="closeEditUserModal()">✕</button>
            </div>

            <form id="editUserForm" method="POST" class="flex-1 overflow-y-auto space-y-5 p-6">


                @csrf
                @method('PUT')

                <div id="edit_readonly_message" class="hidden rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Para tu propio usuario solo puedes cambiar la contraseña.
                </div>

                <x-form.input name="name" label="Nombre" id="edit_name" />
                <x-form.input name="document" label="Documento" id="edit_document" />
                <x-form.input name="username" label="Usuario" id="edit_username" />
                <x-form.input name="password" label="Contraseña" type="password" id="edit_password" placeholder="Dejar en blanco para no cambiar" />

                <div id="edit_role_wrapper">
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

                <div id="edit_clients_wrapper">
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

                                <div class="grid gap-3">
                                    @forelse(($elementTypesByClient[$client->id] ?? collect()) as $elementType)
                                        <div
                                            class="rounded-xl border border-slate-200 p-3"
                                            data-edit-element-type-card
                                            data-client-id="{{ $client->id }}"
                                            data-element-type-id="{{ $elementType->id }}"
                                        >
                                            <label class="flex items-center gap-3 text-sm text-slate-700">
                                                <input
                                                    type="checkbox"
                                                    name="element_type_permissions[{{ $client->id }}][]"
                                                    value="{{ $elementType->id }}"
                                                    class="edit-element-type-checkbox rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                                                    data-client-id="{{ $client->id }}"
                                                    data-element-type-id="{{ $elementType->id }}"
                                                    onchange="toggleAreaPermissionsByElementType('edit')"
                                                >
                                                <span>{{ $elementType->name }}</span>
                                            </label>

                                            <div
                                                class="edit-area-permissions-block mt-3 hidden rounded-lg border border-slate-200 bg-slate-50 p-3"
                                                data-client-id="{{ $client->id }}"
                                                data-element-type-id="{{ $elementType->id }}"
                                            >
                                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    Áreas permitidas para esta especialidad
                                                </p>

                                                <div class="grid gap-2">
                                                    @forelse(($areasByClient[$client->id] ?? collect()) as $area)
                                                        <label class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700">
                                                            <input
                                                                type="checkbox"
                                                                name="area_permissions[{{ $client->id }}][{{ $elementType->id }}][]"
                                                                value="{{ $area->id }}"
                                                                class="edit-area-checkbox rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                                                                data-client-id="{{ $client->id }}"
                                                                data-element-type-id="{{ $elementType->id }}"
                                                            >
                                                            <span>{{ $area->name }}</span>
                                                        </label>
                                                    @empty
                                                        <p class="text-sm text-slate-500">No hay áreas para este cliente.</p>
                                                    @endforelse
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-sm text-slate-500">No hay tipos de activo para este cliente.</p>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>


                @foreach(($activeFilters['client_ids'] ?? []) as $value)
                    <input type="hidden" name="redirect_client_ids[]" value="{{ $value }}">
                @endforeach
                @foreach(($activeFilters['names'] ?? []) as $value)
                    <input type="hidden" name="redirect_names[]" value="{{ $value }}">
                @endforeach
                @foreach(($activeFilters['role_keys'] ?? []) as $value)
                    <input type="hidden" name="redirect_role_keys[]" value="{{ $value }}">
                @endforeach
                @foreach(($activeFilters['statuses'] ?? []) as $value)
                    <input type="hidden" name="redirect_statuses[]" value="{{ $value }}">
                @endforeach
                <input type="hidden" name="redirect_page" value="{{ $users->currentPage() }}">

                <div class="flex justify-end gap-3 border-t border-slate-200 pt-4 shrink-0">
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
</div>

<div id="filterPopover" class="fixed z-50 hidden w-[340px] rounded-2xl border border-slate-200 bg-white shadow-2xl">
    <div class="border-b border-slate-200 px-4 py-3">
        <div class="flex items-center justify-between gap-3">
            <h3 id="filterPopoverTitle" class="text-sm font-semibold text-slate-900"></h3>
            <button type="button" onclick="closeFilterPopover()" class="text-slate-400 hover:text-slate-700">✕</button>
        </div>
    </div>

    <div id="filterPopoverBody" class="space-y-4 p-4"></div>

    <div class="flex justify-between border-t border-slate-200 px-4 py-3">
        <button
            type="button"
            onclick="clearCurrentFilter()"
            class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100"
        >
            Limpiar
        </button>

        <button
            type="button"
            onclick="applyCurrentFilter()"
            class="rounded-lg bg-[#d94d33] px-3 py-2 text-xs font-semibold text-white hover:bg-[#b83f29]"
        >
            Aplicar
        </button>
    </div>
</div>

<script>
    const filterOptions = {
        @if($showClientColumn)
        client_ids: {
            type: 'checklist_object',
            title: 'Clientes',
            inputName: 'client_ids',
            options: @json($filterOptions['client_ids']),
        },
        @endif
        names: {
            type: 'checklist',
            title: 'Nombre',
            inputName: 'names',
            options: @json($filterOptions['names']),
        },
        role_keys: {
            type: 'checklist_object',
            title: 'Rol',
            inputName: 'role_keys',
            options: @json($filterOptions['role_keys']),
        },
        statuses: {
            type: 'checklist_object',
            title: 'Estado',
            inputName: 'statuses',
            options: @json($filterOptions['statuses']),
        },
    };

    const activeFilters = @json($activeFilters);
    let currentPopoverKey = null;

    function getSelectedRoleKey(prefix) {
        const select = document.getElementById(`${prefix}_role_id`);
        const option = select.options[select.selectedIndex];
        return option ? option.dataset.roleKey : '';
    }



    function roleUsesSpecialization(roleKey) {
    return ['inspector', 'admin_cliente', 'observador', 'observador_cliente'].includes(roleKey);
}

    function roleUsesAreaPermissions(roleKey) {
        return roleKey === 'admin_cliente';
    }

    function toggleSpecializedPermissions(prefix) {
        const wrapper = document.getElementById(`${prefix}_specialized_permissions_wrapper`);
        const roleKey = getSelectedRoleKey(prefix);

        if (roleUsesSpecialization(roleKey)) {
            wrapper.classList.remove('hidden');
        } else {
            wrapper.classList.add('hidden');
        }

        toggleClientElementTypes(prefix);
        toggleAreaPermissionsByElementType(prefix);
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

        toggleAreaPermissionsByElementType(prefix);
    }

    function toggleAreaPermissionsByElementType(prefix) {
        const roleKey = getSelectedRoleKey(prefix);
        const useAreas = roleUsesAreaPermissions(roleKey);

        document.querySelectorAll(`.${prefix}-area-permissions-block`).forEach(block => {
            const clientId = parseInt(block.dataset.clientId);
            const elementTypeId = parseInt(block.dataset.elementTypeId);

            const elementTypeCheckbox = document.querySelector(
                `.${prefix}-element-type-checkbox[data-client-id="${clientId}"][data-element-type-id="${elementTypeId}"]`
            );

            const shouldShow = useAreas && !!elementTypeCheckbox && elementTypeCheckbox.checked;

            block.classList.toggle('hidden', !shouldShow);

            if (!shouldShow) {
                block.querySelectorAll(`.${prefix}-area-checkbox`).forEach(cb => {
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

        document.querySelectorAll('.edit-area-checkbox').forEach(cb => {
            cb.checked = false;
        });

        Object.entries(user.permissions || {}).forEach(([clientId, elementTypeIds]) => {
            elementTypeIds.forEach(elementTypeId => {
                const checkbox = document.querySelector(
                    `.edit-element-type-checkbox[data-client-id="${clientId}"][data-element-type-id="${elementTypeId}"]`
                );

                if (checkbox) {
                    checkbox.checked = true;
                }
            });
        });

        Object.entries(user.area_permissions || {}).forEach(([clientId, byType]) => {
            Object.entries(byType || {}).forEach(([elementTypeId, areaIds]) => {
                areaIds.forEach(areaId => {
                    const checkbox = document.querySelector(
                        `.edit-area-checkbox[data-client-id="${clientId}"][data-element-type-id="${elementTypeId}"][value="${areaId}"]`
                    );

                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            });
        });

        setEditReadOnlyMode(!!user.is_self);
        toggleSpecializedPermissions('edit');
        toggleClientElementTypes('edit');
        toggleAreaPermissionsByElementType('edit');

        const modal = document.getElementById('editUserModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }


   

    function setEditReadOnlyMode(isSelf) {
        const readonlyMessage = document.getElementById('edit_readonly_message');
        const roleWrapper = document.getElementById('edit_role_wrapper');
        const clientsWrapper = document.getElementById('edit_clients_wrapper');
        const nameInput = document.getElementById('edit_name');
        const documentInput = document.getElementById('edit_document');
        const usernameInput = document.getElementById('edit_username');
        const roleSelect = document.getElementById('edit_role_id');

        readonlyMessage.classList.toggle('hidden', !isSelf);
        roleWrapper.classList.toggle('hidden', isSelf);
        clientsWrapper.classList.toggle('hidden', isSelf);
        document.getElementById('edit_specialized_permissions_wrapper').classList.toggle('hidden', isSelf);

        nameInput.readOnly = isSelf;
        documentInput.readOnly = isSelf;
        usernameInput.readOnly = isSelf;

        if (isSelf) {
            nameInput.classList.add('bg-slate-100');
            documentInput.classList.add('bg-slate-100');
            usernameInput.classList.add('bg-slate-100');
            roleSelect.disabled = true;
            document.querySelectorAll('.edit-client-checkbox, .edit-element-type-checkbox').forEach(cb => cb.disabled = true);
        } else {
            nameInput.classList.remove('bg-slate-100');
            documentInput.classList.remove('bg-slate-100');
            usernameInput.classList.remove('bg-slate-100');
            roleSelect.disabled = false;
            document.querySelectorAll('.edit-client-checkbox, .edit-element-type-checkbox').forEach(cb => cb.disabled = false);
        }
    }

    

    function closeEditUserModal() {
        const modal = document.getElementById('editUserModal');
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }

    function buildFiltersForm() {
        const form = document.getElementById('filtersForm');
        form.innerHTML = '';

        const addHidden = (name, value) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value ?? '';
            form.appendChild(input);
        };

        Object.entries(activeFilters).forEach(([key, value]) => {
            if (Array.isArray(value)) {
                value.filter(item => item !== null && item !== '').forEach(item => {
                    addHidden(`${key}[]`, item);
                });
            } else if (value !== null && value !== '') {
                addHidden(key, value);
            }
        });
    }

    function closeFilterPopover() {
        const popover = document.getElementById('filterPopover');
        popover.classList.add('hidden');
        currentPopoverKey = null;
    }

    function openFilterPopover(event, key) {
        currentPopoverKey = key;

        const config = filterOptions[key];
        const popover = document.getElementById('filterPopover');
        const title = document.getElementById('filterPopoverTitle');
        const body = document.getElementById('filterPopoverBody');

        title.textContent = config.title;
        body.innerHTML = '';

        if (config.type === 'checklist') {
            const values = Array.isArray(activeFilters[config.inputName]) ? activeFilters[config.inputName] : [];
            renderChecklist(body, config, values, false);
        }

        if (config.type === 'checklist_object') {
            const values = Array.isArray(activeFilters[config.inputName]) ? activeFilters[config.inputName] : [];
            renderChecklist(body, config, values, true);
        }

        popover.classList.remove('hidden');

        const rect = event.currentTarget.getBoundingClientRect();
        const top = rect.bottom + window.scrollY + 8;
        const left = Math.max(16, Math.min(window.innerWidth - 360, rect.left + window.scrollX - 280));

        popover.style.top = `${top}px`;
        popover.style.left = `${left}px`;
    }

    function renderChecklist(body, config, selectedValues, objectMode = false) {
        const searchId = `search_${config.inputName}`;
        const listId = `list_${config.inputName}`;

        body.innerHTML = `
            <div>
                <input
                    type="text"
                    id="${searchId}"
                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                    placeholder="Buscar dentro de la lista"
                >
            </div>
            <div id="${listId}" class="max-h-72 space-y-2 overflow-y-auto rounded-xl border border-slate-200 p-3"></div>
        `;

        const list = document.getElementById(listId);
        const search = document.getElementById(searchId);

        const renderList = () => {
            const term = search.value.toLowerCase().trim();

            let items = config.options;

            if (objectMode) {
                items = items.filter(item => item.label.toLowerCase().includes(term));
            } else {
                items = items.filter(item => String(item).toLowerCase().includes(term));
            }

            if (items.length === 0) {
                list.innerHTML = `<p class="text-sm text-slate-500">No hay coincidencias.</p>`;
                return;
            }

            list.innerHTML = items.map(item => {
                const value = objectMode ? item.value : item;
                const label = objectMode ? item.label : item;
                const checked = selectedValues.includes(String(value)) || selectedValues.includes(value);

                return `
                    <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-3 text-sm text-slate-700">
                        <input
                            type="checkbox"
                            value="${escapeHtml(String(value))}"
                            class="filter-check mt-0.5 rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                            ${checked ? 'checked' : ''}
                        >
                        <span>${escapeHtml(String(label))}</span>
                    </label>
                `;
            }).join('');
        };

        renderList();
        search.addEventListener('input', renderList);
    }

    function clearCurrentFilter() {
        if (!currentPopoverKey) return;

        const config = filterOptions[currentPopoverKey];
        activeFilters[config.inputName] = [];
        submitFilters();
    }

    function applyCurrentFilter() {
        if (!currentPopoverKey) return;

        const config = filterOptions[currentPopoverKey];
        const values = Array.from(document.querySelectorAll('#filterPopover .filter-check:checked'))
            .map(cb => cb.value);

        activeFilters[config.inputName] = values;
        submitFilters();
    }

    function submitFilters() {
        buildFiltersForm();
        document.getElementById('filtersForm').submit();
    }

    function escapeHtml(text) {
        return text
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    document.addEventListener('DOMContentLoaded', function () {
        toggleSpecializedPermissions('create');
        toggleClientElementTypes('create');
        toggleAreaPermissionsByElementType('create');
    });


    document.addEventListener('click', function (event) {
        const popover = document.getElementById('filterPopover');

        if (popover.classList.contains('hidden')) return;

        if (!popover.contains(event.target) && !event.target.closest('button[onclick^="openFilterPopover"]')) {
            closeFilterPopover();
        }
    });
</script>
@endsection
