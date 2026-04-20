@extends('layouts.admin')

@section('title', 'Configuración de módulos')
@section('header_title', 'Configuración de módulos')

@section('content')
    <div class="space-y-8">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Configuración de módulos</h2>
            <p class="mt-2 text-slate-600">
                Define qué cliente y qué tipo de activo tendrán habilitado cada módulo operativo.
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

        <div class="grid gap-8 xl:grid-cols-[320px_minmax(0,1fr)]">
            {{-- Formulario --}}
            <div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Nueva configuración</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Registra una combinación cliente + tipo de activo + módulo.
                    </p>

                    <div id="createModuleAjaxErrors" class="mt-4 hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

                    <form
                        id="createModuleConfigForm"
                        method="POST"
                        action="{{ route('admin.client-element-type-modules.store') }}"
                        class="mt-6 space-y-5"
                    >
                        @csrf

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Cliente</label>
                            <select
                                name="client_id"
                                id="client_id"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            >
                                <option value="">Seleccione un cliente</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" @selected(old('client_id') == $client->id)>
                                        {{ $client->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Tipo de activo</label>
                            <select
                                name="element_type_id"
                                id="element_type_id"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            >
                                <option value="">Seleccione un tipo de activo</option>
                                @foreach($elementTypes as $type)
                                    <option
                                        value="{{ $type->id }}"
                                        data-client-id="{{ $type->client_id }}"
                                        @selected(old('element_type_id') == $type->id)
                                    >
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Módulo</label>
                            <select
                                name="system_module_id"
                                id="system_module_id"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            >
                                <option value="">Seleccione un módulo</option>
                                @foreach($modules as $module)
                                    <option value="{{ $module->id }}" @selected(old('system_module_id') == $module->id)>
                                        {{ $module->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                        >
                            Guardar configuración
                        </button>
                    </form>
                </div>
            </div>

            {{-- Tabla --}}
            <div>
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Listado de configuraciones</h3>
                                <p class="mt-1 text-sm text-slate-500">
                                    Administra la visibilidad y la creación de registros por cliente, tipo de activo y módulo.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Cliente
                                    </th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Tipo de activo
                                    </th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Módulo
                                    </th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Activos relacionados
                                    </th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Estado módulo
                                    </th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Crear registros
                                    </th>
                                </tr>
                            </thead>

                            <tbody id="moduleConfigsTableBody" class="divide-y divide-slate-200 bg-white">
                                @forelse($rows as $row)
                                    <tr class="hover:bg-slate-50" id="module-config-row-{{ $row['id'] }}">
                                        <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-700">
                                            {{ $row['client_name'] }}
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-700">
                                            <span class="inline-flex rounded-full bg-[#d94d33]/10 px-3 py-1 text-xs font-semibold text-[#d94d33]">
                                                {{ $row['element_type_name'] }}
                                            </span>
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-900">
                                            {{ $row['module_name'] }}
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-700">
                                            {{ $row['related_elements_count'] }}
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-4 text-sm" id="module-cell-{{ $row['id'] }}">
                                            <button
                                                type="button"
                                                onclick="toggleModule({{ $row['id'] }})"
                                                class="inline-flex rounded-full px-3 py-1 text-xs font-semibold transition {{ $row['module_enabled'] ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-red-100 text-red-700 hover:bg-red-200' }}"
                                            >
                                                {{ $row['module_enabled'] ? 'Activo' : 'Inactivo' }}
                                            </button>
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-4 text-sm" id="creation-cell-{{ $row['id'] }}">
                                            @if($row['module_enabled'])
                                                <button
                                                    type="button"
                                                    onclick="toggleCreate({{ $row['id'] }})"
                                                    class="inline-flex rounded-full px-3 py-1 text-xs font-semibold transition {{ $row['creation_enabled'] ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}"
                                                >
                                                    {{ $row['creation_enabled'] ? 'Habilitado' : 'Deshabilitado' }}
                                                </button>
                                            @else
                                                <span class="text-slate-400">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="module-configs-empty-row">
                                        <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">
                                            No hay configuraciones registradas todavía.
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

    <script>
        function filterElementTypesByClient(clientId) {
            const elementTypeSelect = document.getElementById('element_type_id');
            if (!elementTypeSelect) return;

            Array.from(elementTypeSelect.options).forEach((option, index) => {
                if (index === 0) {
                    option.hidden = false;
                    return;
                }

                const optionClientId = option.dataset.clientId ?? '';
                option.hidden = clientId !== '' && optionClientId !== String(clientId);
            });

            const selectedOption = elementTypeSelect.options[elementTypeSelect.selectedIndex];
            if (selectedOption && selectedOption.hidden) {
                elementTypeSelect.value = '';
            }
        }

        function showCrudToast(message, type = 'success') {
            const toastId = 'crudInlineToast';
            let toast = document.getElementById(toastId);

            if (!toast) {
                toast = document.createElement('div');
                toast.id = toastId;
                document.body.appendChild(toast);
            }

            toast.className =
                'fixed bottom-6 right-6 z-[80] rounded-2xl px-4 py-3 text-sm font-semibold shadow-xl transition ' +
                (type === 'success'
                    ? 'border border-green-200 bg-green-100 text-green-700'
                    : 'border border-red-200 bg-red-100 text-red-700');

            toast.textContent = message;
            toast.classList.remove('hidden');

            clearTimeout(window.__crudToastTimeout);
            window.__crudToastTimeout = setTimeout(() => {
                toast.classList.add('hidden');
            }, 2200);
        }

        function renderAjaxErrors(containerId, errors) {
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

        function clearAjaxErrors(containerId) {
            const box = document.getElementById(containerId);
            if (!box) return;

            box.classList.add('hidden');
            box.innerHTML = '';
        }

        function setFormSubmittingState(form, isSubmitting, loadingText = 'Guardando...') {
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

        function escapeHtml(text) {
            return text
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function resetCreateModuleConfigForm() {
            const form = document.getElementById('createModuleConfigForm');
            if (!form) return;

            form.reset();
            clearAjaxErrors('createModuleAjaxErrors');

            const clientSelect = document.getElementById('client_id');
            filterElementTypesByClient(clientSelect?.value ?? '');
        }

        function renderModuleCell(row) {
            return `
                <button
                    type="button"
                    onclick="toggleModule(${row.id})"
                    class="inline-flex rounded-full px-3 py-1 text-xs font-semibold transition ${row.module_enabled
                        ? 'bg-green-100 text-green-700 hover:bg-green-200'
                        : 'bg-red-100 text-red-700 hover:bg-red-200'}"
                >
                    ${row.module_enabled ? 'Activo' : 'Inactivo'}
                </button>
            `;
        }

        function renderCreationCell(row) {
            if (!row.module_enabled) {
                return `<span class="text-slate-400">—</span>`;
            }

            return `
                <button
                    type="button"
                    onclick="toggleCreate(${row.id})"
                    class="inline-flex rounded-full px-3 py-1 text-xs font-semibold transition ${row.creation_enabled
                        ? 'bg-green-100 text-green-700 hover:bg-green-200'
                        : 'bg-slate-100 text-slate-700 hover:bg-slate-200'}"
                >
                    ${row.creation_enabled ? 'Habilitado' : 'Deshabilitado'}
                </button>
            `;
        }

        function buildModuleRow(row) {
            return `
                <tr class="hover:bg-slate-50" id="module-config-row-${row.id}">
                    <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-700">
                        ${escapeHtml(row.client_name ?? '—')}
                    </td>

                    <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-700">
                        <span class="inline-flex rounded-full bg-[#d94d33]/10 px-3 py-1 text-xs font-semibold text-[#d94d33]">
                            ${escapeHtml(row.element_type_name ?? '—')}
                        </span>
                    </td>

                    <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-900">
                        ${escapeHtml(row.module_name ?? '—')}
                    </td>

                    <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-700">
                        ${escapeHtml(String(row.related_elements_count ?? 0))}
                    </td>

                    <td class="whitespace-nowrap px-5 py-4 text-sm" id="module-cell-${row.id}">
                        ${renderModuleCell(row)}
                    </td>

                    <td class="whitespace-nowrap px-5 py-4 text-sm" id="creation-cell-${row.id}">
                        ${renderCreationCell(row)}
                    </td>
                </tr>
            `;
        }

        function insertModuleRow(row) {
            if (!row || !row.id) return;

            const tbody = document.getElementById('moduleConfigsTableBody');
            if (!tbody) return;

            const emptyRow = document.getElementById('module-configs-empty-row');
            if (emptyRow) {
                emptyRow.remove();
            }

            const wrapper = document.createElement('tbody');
            wrapper.innerHTML = buildModuleRow(row);

            const realRow = wrapper.firstElementChild;
            realRow.style.opacity = '0';
            realRow.style.transform = 'translateY(-6px)';
            realRow.style.transition = 'opacity 180ms ease, transform 180ms ease';

            tbody.prepend(realRow);

            requestAnimationFrame(() => {
                realRow.style.opacity = '1';
                realRow.style.transform = 'translateY(0)';
            });
        }

        function updateModuleRow(id, data) {
            const moduleCell = document.getElementById(`module-cell-${id}`);
            const creationCell = document.getElementById(`creation-cell-${id}`);
            if (!moduleCell || !creationCell) return;

            const row = {
                id: id,
                module_enabled: !!data.module_enabled,
                creation_enabled: !!data.creation_enabled,
            };

            moduleCell.innerHTML = renderModuleCell(row);
            creationCell.innerHTML = renderCreationCell(row);
        }

        async function handleCreateModuleConfigSubmit(event) {
            event.preventDefault();

            const form = event.currentTarget;
            clearAjaxErrors('createModuleAjaxErrors');
            setFormSubmittingState(form, true, 'Guardando...');

            try {
                const formData = new FormData(form);

                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: formData,
                });

                const data = await response.json();

                if (response.status === 422) {
                    renderAjaxErrors('createModuleAjaxErrors', data.errors || {});
                    showCrudToast('Corrige los errores del formulario.', 'error');
                    return;
                }

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'No fue posible crear la configuración.');
                }

                insertModuleRow(data.row);
                resetCreateModuleConfigForm();
                showCrudToast(data.message || 'Configuración creada correctamente.', 'success');
            } catch (error) {
                showCrudToast(error.message || 'Ocurrió un error al crear la configuración.', 'error');
            } finally {
                setFormSubmittingState(form, false);
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const clientSelect = document.getElementById('client_id');
            const createForm = document.getElementById('createModuleConfigForm');

            if (clientSelect) {
                filterElementTypesByClient(clientSelect.value);

                clientSelect.addEventListener('change', function () {
                    filterElementTypesByClient(this.value);
                });
            }

            if (createForm) {
                createForm.addEventListener('submit', handleCreateModuleConfigSubmit);
            }
        });

        function toggleModule(id) {
            fetch(`/admin/client-element-type-modules/${id}/toggle-module-enabled`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            })
            .then(async res => {
                const data = await res.json();

                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'No fue posible actualizar el estado del módulo.');
                }

                updateModuleRow(id, data);
                showCrudToast(data.message || 'Estado del módulo actualizado correctamente.', 'success');
            })
            .catch(error => {
                showCrudToast(error.message || 'Ocurrió un error al actualizar el estado del módulo.', 'error');
            });
        }

        function toggleCreate(id) {
            fetch(`/admin/client-element-type-modules/${id}/toggle-creation-enabled`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            })
            .then(async res => {
                const data = await res.json();

                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'No fue posible actualizar el estado de creación.');
                }

                updateModuleRow(id, data);
                showCrudToast(data.message || 'Estado de creación actualizado correctamente.', 'success');
            })
            .catch(error => {
                showCrudToast(error.message || 'Ocurrió un error al actualizar el estado de creación.', 'error');
            });
        }
    </script>
@endsection