@extends('layouts.admin')

@section('title', 'Clientes')
@section('header_title', 'Clientes')

@section('content')
    <div class="space-y-8">
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

        <div class="grid gap-8 xl:grid-cols-3">
            <div class="xl:col-span-1">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Nuevo cliente</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Registra una nueva empresa cliente.
                    </p>

                    <div id="createClientAjaxErrors" class="mt-4 hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

                    <form
                        id="createClientForm"
                        method="POST"
                        action="{{ route('admin.clients.store') }}"
                        class="mt-6 space-y-5"
                    >
                        @csrf

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Nombre</label>
                            <input
                                type="text"
                                name="name"
                                id="create_client_name"
                                value="{{ old('name') }}"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                placeholder="Ej. CORONA"
                            >
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Observaciones</label>
                            <textarea
                                name="obs"
                                id="create_client_obs"
                                rows="4"
                                class="w-full resize-none rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                placeholder="Información adicional del cliente"
                            >{{ old('obs') }}</textarea>
                        </div>

                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                        >
                            Guardar cliente
                        </button>
                    </form>
                </div>
            </div>

            <div class="xl:col-span-2">
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <h3 class="text-lg font-semibold text-slate-900">Listado de clientes</h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Nombre</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Obs.</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Dependencias</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Estado</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Acciones</th>
                                </tr>
                            </thead>

                            <tbody id="clientsTableBody" class="divide-y divide-slate-200 bg-white">
                                @forelse($clients as $client)
                                    @php
                                        $dependencyCount =
                                            ($client->areas_count ?? 0) +
                                            ($client->users_count ?? 0) +
                                            ($client->element_types_count ?? 0) +
                                            ($client->components_count ?? 0) +
                                            ($client->diagnostics_count ?? 0) +
                                            ($client->conditions_count ?? 0);
                                    @endphp

                                    <tr class="hover:bg-slate-50" id="client-row-{{ $client->id }}">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900" id="client-name-{{ $client->id }}">
                                            {{ $client->name }}
                                        </td>

                                        <td class="px-6 py-4 text-sm text-slate-600" id="client-obs-{{ $client->id }}">
                                            {{ $client->obs ?: '—' }}
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700" id="client-dependencies-{{ $client->id }}">
                                            {{ $dependencyCount }}
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                                            <button
                                                type="button"
                                                data-status-toggle
                                                data-url="{{ route('admin.clients.toggle-status', $client) }}"
                                                data-enabled="{{ $client->status ? '1' : '0' }}"
                                                onclick="toggleClientStatus(this)"
                                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition {{ $client->status ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-red-100 text-red-700 hover:bg-red-200' }}"
                                                title="Clic para activar o inactivar"
                                            >
                                                <i data-lucide="{{ $client->status ? 'check-circle-2' : 'x-circle' }}" class="h-3.5 w-3.5"></i>
                                                <span>{{ $client->status ? 'Activo' : 'Inactivo' }}</span>
                                            </button>
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <button
                                                    type="button"
                                                    data-edit-client
                                                    data-id="{{ $client->id }}"
                                                    data-name="{{ $client->name }}"
                                                    data-obs="{{ $client->obs }}"
                                                    data-action="{{ route('admin.clients.update', $client) }}"
                                                    onclick="openEditClientModal(this)"
                                                    class="text-slate-400 transition hover:text-[#d94d33]"
                                                    title="Editar cliente"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M16.862 4.487l1.651-1.651a2.121 2.121 0 113 3l-1.651 1.651M4 20h4l10.586-10.586a2 2 0 00-2.828-2.828L5.172 17.172A2 2 0 004 18.586V20z" />
                                                    </svg>
                                                </button>

                                                @if($dependencyCount === 0)
                                                    <button
                                                        type="button"
                                                        onclick="deleteClient({{ $client->id }})"
                                                        class="text-red-500 transition hover:text-red-700"
                                                        title="Eliminar cliente"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M6 7h12M9 7V4h6v3M10 11v6M14 11v6M5 7l1 13a2 2 0 002 2h8a2 2 0 002-2l1-13" />
                                                        </svg>
                                                    </button>

                                                    <form
                                                        id="delete-client-form-{{ $client->id }}"
                                                        method="POST"
                                                        action="{{ route('admin.clients.destroy', $client) }}"
                                                        class="hidden"
                                                    >
                                                        @csrf
                                                        @method('DELETE')
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500">
                                            No hay clientes registrados todavía.
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


    {{-- MODAL EDITAR --}}
    <div
        id="editClientModal"
        class="fixed left-0 top-0 z-[9999] hidden h-[100dvh] w-[100vw] items-center justify-center overflow-y-auto bg-slate-950/60 px-3 py-4 backdrop-blur-sm sm:px-4 sm:py-6"
    >
        <div
            id="editClientModalContent"
            class="flex w-full max-w-2xl scale-95 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white opacity-0 shadow-2xl transition duration-200 ease-out"
            style="max-height: calc(100dvh - 2rem);"
        >
            <div class="flex shrink-0 items-center justify-between border-b border-slate-200 bg-white px-4 py-3 sm:px-5">
                <div>
                    <h3 class="text-base font-bold text-slate-900 sm:text-lg">
                        Editar cliente
                    </h3>
                    <p class="mt-0.5 hidden text-xs text-slate-500 sm:block">
                        Actualiza los datos del cliente seleccionado.
                    </p>
                </div>

                <button
                    type="button"
                    class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                    onclick="closeEditClientModal()"
                    title="Cerrar"
                >
                    ✕
                </button>
            </div>

            <form id="editClientForm" method="POST" class="flex min-h-0 flex-1 flex-col">
                @csrf
                @method('PUT')

                <div class="min-h-0 flex-1 overflow-y-auto px-4 py-3 sm:px-5 sm:py-4">
                    <div id="editClientAjaxErrors" class="mb-3 hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Nombre</label>
                            <input
                                type="text"
                                name="name"
                                id="edit_client_name"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            >
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Observaciones</label>
                            <textarea
                                name="obs"
                                id="edit_client_obs"
                                rows="4"
                                class="w-full resize-none rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <div class="shrink-0 border-t border-slate-200 bg-slate-50 px-4 py-3 sm:px-5">
                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
                        <button
                            type="button"
                            onclick="closeEditClientModal()"
                            class="inline-flex w-full justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 sm:w-auto"
                        >
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            class="inline-flex w-full justify-center rounded-xl bg-[#d94d33] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#b83f29] sm:w-auto"
                        >
                            Actualizar cliente
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="clientToastContainer" class="fixed bottom-5 right-5 z-[99999] space-y-3"></div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const createClientForm = document.getElementById('createClientForm');
        const editClientForm = document.getElementById('editClientForm');

        if (createClientForm) {
            createClientForm.addEventListener('submit', handleCreateClientSubmit);
        }

        if (editClientForm) {
            editClientForm.addEventListener('submit', handleEditClientSubmit);
        }

        const createNameInput = document.getElementById('create_client_name');

        if (createNameInput) {
            createNameInput.addEventListener('input', function () {
                const start = this.selectionStart;
                const end = this.selectionEnd;

                this.value = this.value.toUpperCase();
                this.setSelectionRange(start, end);
            });
        }

        const editNameInput = document.getElementById('edit_client_name');

        if (editNameInput) {
            editNameInput.addEventListener('input', function () {
                const start = this.selectionStart;
                const end = this.selectionEnd;

                this.value = this.value.toUpperCase();
                this.setSelectionRange(start, end);
            });
        }

        if (window.lucide) {
            window.lucide.createIcons();
        }
    });

    function openEditClientModal(button) {
        clearClientAjaxErrors('editClientAjaxErrors');

        document.getElementById('editClientForm').action = button.dataset.action;
        document.getElementById('edit_client_name').value = button.dataset.name ?? '';
        document.getElementById('edit_client_obs').value = button.dataset.obs ?? '';

        const modal = document.getElementById('editClientModal');
        const content = document.getElementById('editClientModalContent');

        modal.classList.remove('hidden');
        modal.classList.add('flex');

        document.documentElement.classList.add('overflow-hidden');
        document.body.classList.add('overflow-hidden');

        setTimeout(() => {
            content?.classList.remove('scale-95', 'opacity-0');
            content?.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeEditClientModal() {
        const modal = document.getElementById('editClientModal');
        const content = document.getElementById('editClientModalContent');

        clearClientAjaxErrors('editClientAjaxErrors');

        content?.classList.remove('scale-100', 'opacity-100');
        content?.classList.add('scale-95', 'opacity-0');

        setTimeout(() => {
            modal.classList.remove('flex');
            modal.classList.add('hidden');

            document.documentElement.classList.remove('overflow-hidden');
            document.body.classList.remove('overflow-hidden');
        }, 150);
    }

    async function handleCreateClientSubmit(event) {
        event.preventDefault();

        const form = event.currentTarget;
        clearClientAjaxErrors('createClientAjaxErrors');
        setClientFormSubmittingState(form, true, 'Guardando...');

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: new FormData(form),
            });

            const data = await parseClientJsonResponse(response);

            if (response.status === 422) {
                renderClientAjaxErrors('createClientAjaxErrors', data.errors || {});
                showClientToast(data.message || 'Corrige los errores del formulario.', 'error');
                return;
            }

            if (!response.ok || data.success === false) {
                throw new Error(data.message || 'No fue posible crear el cliente.');
            }

            insertClientRow(data.client);
            resetCreateClientForm();

            showClientToast(data.message || 'Cliente creado correctamente.', 'success');
        } catch (error) {
            showClientToast(error.message || 'Ocurrió un error al crear el cliente.', 'error');
        } finally {
            setClientFormSubmittingState(form, false);
        }
    }

    async function handleEditClientSubmit(event) {
        event.preventDefault();

        const form = event.currentTarget;
        clearClientAjaxErrors('editClientAjaxErrors');
        setClientFormSubmittingState(form, true, 'Actualizando...');

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: new FormData(form),
            });

            const data = await parseClientJsonResponse(response);

            if (response.status === 422) {
                renderClientAjaxErrors('editClientAjaxErrors', data.errors || {});
                showClientToast(data.message || 'Corrige los errores del formulario.', 'error');
                return;
            }

            if (!response.ok || data.success === false) {
                throw new Error(data.message || 'No fue posible actualizar el cliente.');
            }

            updateClientRow(data.client);
            closeEditClientModal();

            showClientToast(data.message || 'Cliente actualizado correctamente.', 'success');
        } catch (error) {
            showClientToast(error.message || 'Ocurrió un error al actualizar el cliente.', 'error');
        } finally {
            setClientFormSubmittingState(form, false);
        }
    }

    async function toggleClientStatus(button) {
        const url = button.dataset.url;

        if (!url || button.disabled) return;

        const originalHtml = button.innerHTML;
        const originalClass = button.className;

        button.disabled = true;
        button.classList.add('opacity-60', 'cursor-wait');

        try {
            const response = await fetch(url, {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            const data = await parseClientJsonResponse(response);

            if (!response.ok || data.success === false) {
                throw new Error(data.message || 'No fue posible cambiar el estado.');
            }

            renderClientStatusButton(button, Boolean(data.status));
            showClientToast(data.message || 'Estado actualizado correctamente.', 'success');
        } catch (error) {
            button.innerHTML = originalHtml;
            button.className = originalClass;
            showClientToast(error.message || 'Ocurrió un error al cambiar el estado.', 'error');
        } finally {
            button.disabled = false;
            button.classList.remove('opacity-60', 'cursor-wait');

            if (window.lucide) {
                window.lucide.createIcons();
            }
        }
    }

    async function deleteClient(clientId) {
        const confirmed = confirm('¿Seguro que deseas eliminar este cliente?');

        if (!confirmed) return;

        const row = document.getElementById(`client-row-${clientId}`);
        const form = document.getElementById(`delete-client-form-${clientId}`);

        if (!form) {
            showClientToast('No se encontró el formulario de eliminación.', 'error');
            return;
        }

        if (row) {
            row.classList.add('opacity-60', 'pointer-events-none');
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: new FormData(form),
            });

            const data = await parseClientJsonResponse(response);

            if (!response.ok || data.success === false) {
                throw new Error(data.message || 'No fue posible eliminar el cliente.');
            }

            if (row) {
                row.style.transition = 'opacity 180ms ease, transform 180ms ease';
                row.style.opacity = '0';
                row.style.transform = 'scale(0.98)';
                setTimeout(() => row.remove(), 180);
            }

            showClientToast(data.message || 'Cliente eliminado correctamente.', 'success');
        } catch (error) {
            if (row) {
                row.classList.remove('opacity-60', 'pointer-events-none');
            }

            showClientToast(error.message || 'Ocurrió un error al eliminar el cliente.', 'error');
        }
    }

    function renderClientStatusButton(button, enabled) {
        button.dataset.enabled = enabled ? '1' : '0';

        button.classList.remove(
            'bg-green-100',
            'text-green-700',
            'hover:bg-green-200',
            'bg-red-100',
            'text-red-700',
            'hover:bg-red-200'
        );

        if (enabled) {
            button.classList.add('bg-green-100', 'text-green-700', 'hover:bg-green-200');
        } else {
            button.classList.add('bg-red-100', 'text-red-700', 'hover:bg-red-200');
        }

        button.innerHTML = `
            <i data-lucide="${enabled ? 'check-circle-2' : 'x-circle'}" class="h-3.5 w-3.5"></i>
            <span>${enabled ? 'Activo' : 'Inactivo'}</span>
        `;

        if (window.lucide) {
            window.lucide.createIcons();
        }
    }

    function insertClientRow(client) {
        if (!client || !client.id) return;

        const tbody = document.getElementById('clientsTableBody');
        if (!tbody) return;

        const emptyRow = tbody.querySelector('td[colspan]');
        if (emptyRow) {
            emptyRow.closest('tr')?.remove();
        }

        const canDelete = Number(client.dependency_count || 0) === 0;

        const row = document.createElement('tr');
        row.id = `client-row-${client.id}`;
        row.className = 'hover:bg-slate-50';

        row.innerHTML = `
            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900" id="client-name-${client.id}">
                ${escapeHtml(client.name ?? '—')}
            </td>

            <td class="px-6 py-4 text-sm text-slate-600" id="client-obs-${client.id}">
                ${escapeHtml(client.obs_label ?? '—')}
            </td>

            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700" id="client-dependencies-${client.id}">
                ${escapeHtml(String(client.dependency_count ?? 0))}
            </td>

            <td class="whitespace-nowrap px-6 py-4 text-sm">
                <button
                    type="button"
                    data-status-toggle
                    data-url="${escapeHtml(client.toggle_status_url ?? '')}"
                    data-enabled="${client.status ? '1' : '0'}"
                    onclick="toggleClientStatus(this)"
                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition ${client.status
                        ? 'bg-green-100 text-green-700 hover:bg-green-200'
                        : 'bg-red-100 text-red-700 hover:bg-red-200'}"
                    title="Clic para activar o inactivar"
                >
                    <i data-lucide="${client.status ? 'check-circle-2' : 'x-circle'}" class="h-3.5 w-3.5"></i>
                    <span>${client.status ? 'Activo' : 'Inactivo'}</span>
                </button>
            </td>

            <td class="whitespace-nowrap px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-2">
                    <button
                        type="button"
                        data-edit-client
                        data-id="${escapeHtml(String(client.id))}"
                        data-name="${escapeHtml(client.name ?? '')}"
                        data-obs="${escapeHtml(client.obs ?? '')}"
                        data-action="${escapeHtml(client.update_url ?? '')}"
                        onclick="openEditClientModal(this)"
                        class="text-slate-400 transition hover:text-[#d94d33]"
                        title="Editar cliente"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16.862 4.487l1.651-1.651a2.121 2.121 0 113 3l-1.651 1.651M4 20h4l10.586-10.586a2 2 0 00-2.828-2.828L5.172 17.172A2 2 0 004 18.586V20z" />
                        </svg>
                    </button>

                    ${canDelete ? `
                        <button
                            type="button"
                            onclick="deleteClient(${client.id})"
                            class="text-red-500 transition hover:text-red-700"
                            title="Eliminar cliente"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6 7h12M9 7V4h6v3M10 11v6M14 11v6M5 7l1 13a2 2 0 002 2h8a2 2 0 002-2l1-13" />
                            </svg>
                        </button>

                        <form
                            id="delete-client-form-${client.id}"
                            method="POST"
                            action="${escapeHtml(client.destroy_url ?? '')}"
                            class="hidden"
                        >
                            <input type="hidden" name="_token" value="${escapeHtml(document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '')}">
                            <input type="hidden" name="_method" value="DELETE">
                        </form>
                    ` : ''}
                </div>
            </td>
        `;

        row.style.opacity = '0';
        row.style.transform = 'translateY(-6px)';
        row.style.transition = 'opacity 180ms ease, transform 180ms ease';

        tbody.prepend(row);

        requestAnimationFrame(() => {
            row.style.opacity = '1';
            row.style.transform = 'translateY(0)';
        });

        if (window.lucide) {
            window.lucide.createIcons();
        }
    }

    function updateClientRow(client) {
        if (!client || !client.id) return;

        const row = document.getElementById(`client-row-${client.id}`);
        const nameEl = document.getElementById(`client-name-${client.id}`);
        const obsEl = document.getElementById(`client-obs-${client.id}`);
        const dependenciesEl = document.getElementById(`client-dependencies-${client.id}`);
        const editButton = row?.querySelector('[data-edit-client]');
        const statusButton = row?.querySelector('[data-status-toggle]');

        if (nameEl) nameEl.textContent = client.name ?? '—';
        if (obsEl) obsEl.textContent = client.obs_label ?? '—';
        if (dependenciesEl) dependenciesEl.textContent = String(client.dependency_count ?? 0);

        if (editButton) {
            editButton.dataset.name = client.name ?? '';
            editButton.dataset.obs = client.obs ?? '';
            editButton.dataset.action = client.update_url ?? '';
        }

        if (statusButton) {
            statusButton.dataset.url = client.toggle_status_url ?? statusButton.dataset.url;
            renderClientStatusButton(statusButton, Boolean(client.status));
        }

        if (window.lucide) {
            window.lucide.createIcons();
        }
    }

    function resetCreateClientForm() {
        const form = document.getElementById('createClientForm');
        if (!form) return;

        form.reset();
        clearClientAjaxErrors('createClientAjaxErrors');
    }

    function showClientToast(message, type = 'success') {
        const container = document.getElementById('clientToastContainer');

        if (!container) {
            alert(message);
            return;
        }

        const toast = document.createElement('div');
        const styles = type === 'error'
            ? 'border-red-200 bg-red-50 text-red-700'
            : 'border-emerald-200 bg-emerald-50 text-emerald-700';

        toast.className = `w-[340px] rounded-2xl border px-4 py-3 text-sm font-semibold shadow-2xl ${styles}`;
        toast.textContent = message;

        container.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('opacity-0', 'translate-y-2', 'transition', 'duration-300');
            setTimeout(() => toast.remove(), 350);
        }, 3500);
    }

    function clearClientAjaxErrors(containerId) {
        const box = document.getElementById(containerId);
        if (!box) return;

        box.classList.add('hidden');
        box.innerHTML = '';
    }

    function renderClientAjaxErrors(containerId, errors) {
        const box = document.getElementById(containerId);
        if (!box) return;

        const messages = [];

        Object.values(errors || {}).forEach((fieldErrors) => {
            (fieldErrors || []).forEach((message) => messages.push(message));
        });

        if (messages.length === 0) {
            box.classList.add('hidden');
            box.innerHTML = '';
            return;
        }

        box.innerHTML = `
            <div class="font-semibold">Hay errores en el formulario.</div>
            <ul class="mt-2 list-disc pl-5">
                ${messages.map(message => `<li>${escapeHtml(String(message))}</li>`).join('')}
            </ul>
        `;

        box.classList.remove('hidden');
    }

    function setClientFormSubmittingState(form, isSubmitting, loadingText = 'Guardando...') {
        if (!form) return;

        const submitButton = form.querySelector('button[type="submit"]');
        if (!submitButton) return;

        if (isSubmitting) {
            submitButton.dataset.originalText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.classList.add('opacity-70', 'pointer-events-none');
            submitButton.innerHTML = loadingText;
        } else {
            submitButton.disabled = false;
            submitButton.classList.remove('opacity-70', 'pointer-events-none');
            submitButton.innerHTML = submitButton.dataset.originalText || submitButton.innerHTML;
        }
    }

    async function parseClientJsonResponse(response) {
        const contentType = response.headers.get('content-type') || '';

        if (!contentType.includes('application/json')) {
            throw new Error('El servidor no devolvió JSON. Revisa sesión, permisos o respuesta del controlador.');
        }

        return await response.json();
    }

    function escapeHtml(text) {
        return String(text ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    document.addEventListener('click', function (event) {
        const modal = document.getElementById('editClientModal');

        if (modal && modal.classList.contains('flex') && event.target === modal) {
            closeEditClientModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeEditClientModal();
        }
    });
</script>
@endsection