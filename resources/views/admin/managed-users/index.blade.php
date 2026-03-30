@extends('layouts.admin')

@section('title', 'Usuarios')
@section('header_title', 'Usuarios')

@section('content')
    <div class="space-y-8">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Gestión de usuarios</h2>
            <p class="mt-2 text-slate-600">
                Gestiona inspectores y administradores cliente de los clientes que tienes asignados.
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

        <div class="grid gap-8 xl:grid-cols-3">
            <div class="xl:col-span-1">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Nuevo usuario</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Solo puedes crear inspectores y administradores cliente.
                    </p>

                    <form method="POST" action="{{ route('admin.managed-users.store') }}" class="mt-6 space-y-5">
                        @csrf

                        <x-form.input name="name" label="Nombre" placeholder="Ej. Juan Pérez" />
                        <x-form.input name="document" label="Documento" placeholder="Ej. 12345678" />
                        <x-form.input name="username" label="Usuario" placeholder="Ej. jperez" />
                        <x-form.input name="email" label="Correo" type="email" placeholder="Ej. correo@empresa.com" />
                        <x-form.input name="password" label="Contraseña" placeholder="Ej. 123456" />

                        <x-form.select name="role_id" label="Rol">
                            <option value="">Seleccione un rol</option>
                            @foreach($creatableRoles as $role)
                                <option value="{{ $role->id }}" @selected(old('role_id') == $role->id)>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </x-form.select>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Clientes asignados</label>
                            <div class="max-h-56 space-y-2 overflow-y-auto rounded-xl border border-slate-300 p-4">
                                @foreach($clients as $client)
                                    <label class="flex items-center gap-3 text-sm text-slate-700">
                                        <input
                                            type="checkbox"
                                            name="clients[]"
                                            value="{{ $client->id }}"
                                            class="rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                                            @checked(collect(old('clients', []))->contains($client->id))
                                        >
                                        {{ $client->name }}
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <x-form.select name="status" label="Estado">
                            <option value="1" @selected(old('status', '1') == '1')>Activo</option>
                            <option value="0" @selected(old('status') == '0')>Inactivo</option>
                        </x-form.select>

                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                        >
                            Guardar usuario
                        </button>
                    </form>
                </div>
            </div>

            <div class="xl:col-span-2">
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <h3 class="text-lg font-semibold text-slate-900">Listado de usuarios visibles</h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Nombre</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Usuario</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Rol</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Clientes</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Trazabilidad</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Estado</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Acciones</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-200 bg-white">
                                @forelse($users as $user)
                                    @php
                                        $isAdmin = $user->role?->key === 'admin';
                                        $hasTraceability = ($user->reports_count + $user->report_details_count) > 0;
                                    @endphp

                                    <tr class="hover:bg-slate-50">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">
                                            {{ $user->name }}
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                                            {{ $user->username }}
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                            {{ $user->role?->name ?? '—' }}
                                        </td>

                                        <td class="px-6 py-4 text-sm text-slate-600">
                                            {{ $user->clients->pluck('name')->implode(', ') ?: '—' }}
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                            {{ $user->reports_count + $user->report_details_count }}
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4 text-sm">
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

                                        <td class="whitespace-nowrap px-6 py-4 text-right">
                                            <div class="flex justify-end gap-2">
                                                @if(!$isAdmin)
                                                    <button
                                                        type="button"
                                                        class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                                                        data-name="{{ $user->name }}"
                                                        data-document="{{ $user->document }}"
                                                        data-username="{{ $user->username }}"
                                                        data-email="{{ $user->email }}"
                                                        data-role_id="{{ $user->role_id }}"
                                                        data-status="{{ $user->status ? 1 : 0 }}"
                                                        data-clients='@json($user->clients->pluck("id"))'
                                                        data-action="{{ route('admin.managed-users.update', $user) }}"
                                                        onclick="openEditUserModal(this)"
                                                    >
                                                        Editar
                                                    </button>

                                                    @if(!$hasTraceability)
                                                        <form
                                                            method="POST"
                                                            action="{{ route('admin.managed-users.destroy', $user) }}"
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
                                                    @else
                                                        <form method="POST" action="{{ route('admin.managed-users.toggle-status', $user) }}">
                                                            @csrf
                                                            @method('PATCH')

                                                            <button
                                                                type="submit"
                                                                class="rounded-lg px-3 py-2 text-xs font-semibold text-white transition {{ $user->status ? 'bg-amber-500 hover:bg-amber-600' : 'bg-green-600 hover:bg-green-700' }}"
                                                            >
                                                                {{ $user->status ? 'Inactivar' : 'Activar' }}
                                                            </button>
                                                        </form>
                                                    @endif
                                                @else
                                                    <span class="text-xs font-semibold text-slate-400">Solo lectura</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-10 text-center text-sm text-slate-500">
                                            No hay usuarios visibles todavía.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="editUserModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
        <div class="w-full max-w-3xl rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Editar usuario</h3>
                <button type="button" class="text-slate-500 hover:text-slate-900" onclick="closeEditUserModal()">✕</button>
            </div>

            <form id="editUserForm" method="POST" class="space-y-5 p-6">
                @csrf
                @method('PUT')

                <div class="grid gap-5 md:grid-cols-2">
                    <x-form.input name="name" label="Nombre" id="edit_user_name" />
                    <x-form.input name="document" label="Documento" id="edit_user_document" />
                    <x-form.input name="username" label="Usuario" id="edit_user_username" />
                    <x-form.input name="email" label="Correo" id="edit_user_email" type="email" />
                    <x-form.input name="password" label="Contraseña (solo si quieres cambiarla)" id="edit_user_password" />
                </div>

                <x-form.select name="role_id" label="Rol" id="edit_user_role_id">
                    @foreach($creatableRoles as $role)
                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                    @endforeach
                </x-form.select>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Clientes asignados</label>
                    <div class="max-h-56 space-y-2 overflow-y-auto rounded-xl border border-slate-300 p-4">
                        @foreach($clients as $client)
                            <label class="flex items-center gap-3 text-sm text-slate-700">
                                <input
                                    type="checkbox"
                                    name="clients[]"
                                    value="{{ $client->id }}"
                                    class="edit-client-checkbox rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                                >
                                {{ $client->name }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <x-form.select name="status" label="Estado" id="edit_user_status">
                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>
                </x-form.select>

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
        function openEditUserModal(btn) {
            const data = {
                name: btn.dataset.name,
                document: btn.dataset.document,
                username: btn.dataset.username,
                email: btn.dataset.email,
                role_id: btn.dataset.role_id,
                status: btn.dataset.status,
                clients: JSON.parse(btn.dataset.clients || '[]'),
                action: btn.dataset.action,
            };

            document.getElementById('editUserForm').action = data.action;
            document.getElementById('edit_user_name').value = data.name ?? '';
            document.getElementById('edit_user_document').value = data.document ?? '';
            document.getElementById('edit_user_username').value = data.username ?? '';
            document.getElementById('edit_user_email').value = data.email ?? '';
            document.getElementById('edit_user_password').value = '';
            document.getElementById('edit_user_role_id').value = data.role_id ?? '';
            document.getElementById('edit_user_status').value = data.status ?? '1';

            document.querySelectorAll('.edit-client-checkbox').forEach(cb => {
                cb.checked = data.clients.includes(parseInt(cb.value));
            });

            const modal = document.getElementById('editUserModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeEditUserModal() {
            const modal = document.getElementById('editUserModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }
    </script>
@endsection