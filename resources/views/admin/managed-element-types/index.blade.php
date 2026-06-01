@extends('layouts.admin')

@section('title', 'Tipos de activos')
@section('header_title', 'Tipos de activos')

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
        <div class="grid gap-8 xl:grid-cols-[340px_minmax(0,1fr)]">
            <div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Nuevo tipo de activo</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Registra un tipo de activo para uno de tus clientes.
                    </p>
                    <form
                        id="createElementTypeForm"
                        method="POST"
                        action="{{ route('admin.managed-element-types.store') }}"
                        class="mt-6 space-y-5"
                    >
                        @csrf

                        @if($singleClient)
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Cliente</label>
                                <input
                                    type="text"
                                    value="{{ $singleClient->name }}"
                                    disabled
                                    class="w-full rounded-xl border border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-700"
                                >
                                <input type="hidden" name="client_id" value="{{ $singleClient->id }}">
                            </div>
                        @else
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Cliente</label>
                                <div class="max-h-56 space-y-2 overflow-y-auto rounded-xl border border-slate-300 p-4">
                                    @foreach($clients as $client)
                                        <label class="flex items-center gap-3 text-sm text-slate-700">
                                            <input
                                                type="checkbox"
                                                name="client_id_checkbox"
                                                value="{{ $client->id }}"
                                                class="client-single-checkbox rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                                                @checked((string) old('client_id', $preferredClientId ?? '') === (string) $client->id)
                                                onchange="handleSingleClientSelection(this)"
                                            >
                                            {{ $client->name }}
                                        </label>
                                    @endforeach
                                </div>
                                <input
                                    type="hidden"
                                    name="client_id"
                                    id="selected_client_id"
                                    value="{{ old('client_id', $preferredClientId ?? '') }}"
                                >
                            </div>
                        @endif

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Nombre</label>
                            <input
                                type="text"
                                name="name"
                                id="element_type_name"
                                value="{{ old('name') }}"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                placeholder="Ej. Banda transportadora"
                            >
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <label class="flex items-start gap-3">
                                <input
                                    type="checkbox"
                                    name="has_semaphore"
                                    value="1"
                                    @checked(old('has_semaphore'))
                                    class="mt-1 rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                                >

                                <span>
                                    <span class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                                        <i data-lucide="calendar-days" class="h-4 w-4 text-[#d94d33]"></i>
                                        ¿Tiene semáforo semanal?
                                    </span>

                                    <span class="mt-1 block text-xs leading-5 text-slate-500">
                                        Activa esta opción si este tipo de activo debe aparecer en el semáforo de indicadores por semana.
                                    </span>
                                </span>
                            </label>
                        </div>

                        @foreach(($activeFilters['client_ids'] ?? []) as $value)
                            <input type="hidden" name="redirect_client_ids[]" value="{{ $value }}">
                        @endforeach
                        @foreach(($activeFilters['names'] ?? []) as $value)
                            <input type="hidden" name="redirect_names[]" value="{{ $value }}">
                        @endforeach
                        @foreach(($activeFilters['statuses'] ?? []) as $value)
                            <input type="hidden" name="redirect_statuses[]" value="{{ $value }}">
                        @endforeach
                        <input type="hidden" name="redirect_page" value="{{ request('page', 1) }}">

                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                        >
                            Guardar tipo de activo
                        </button>
                    </form>
                </div>
            </div>
            <div>
                <div
                    class="rounded-2xl border border-slate-200 bg-white shadow-sm"
                    data-element-types-index
                    data-index-url="{{ route('admin.managed-element-types.index') }}"
                >
                    <div class="border-b border-slate-200 px-6 py-4">
                        <div class="flex items-center justify-between gap-4">
                            <h3 class="text-lg font-semibold text-slate-900">Listado de tipos de activos</h3>

                            <a
                                href="{{ route('admin.managed-element-types.index') }}"
                                data-clear-filters
                                class="inline-flex items-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 {{ $hasAnyActiveFilter ? '' : 'hidden' }}"
                            >
                                Limpiar filtros
                            </a>
                        </div>
                    </div>

                    @include('admin.managed-element-types.partials.list', [
                        'elementTypes' => $elementTypes,
                        'activeFilters' => $activeFilters,
                        'showClientColumn' => $showClientColumn,
                    ])
                </div>
            </div>
        </div>
    </div>
    <div
        id="editElementTypeModal"
        class="fixed left-0 top-0 z-[9999] hidden h-[100dvh] w-[100vw] items-center justify-center overflow-y-auto bg-slate-950/60 px-3 py-4 backdrop-blur-sm sm:px-4 sm:py-6"
    >
        <div
            id="editElementTypeModalContent"
            class="flex w-full max-w-xl scale-95 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white opacity-0 shadow-2xl transition duration-200 ease-out"
            style="max-height: calc(100dvh - 2rem);"
        >
            <div class="flex shrink-0 items-center justify-between border-b border-slate-200 bg-white px-4 py-3 sm:px-5">
                <div>
                    <h3 class="text-base font-bold text-slate-900 sm:text-lg">
                        Editar tipo de activo
                    </h3>
                    <p class="mt-0.5 hidden text-xs text-slate-500 sm:block">
                        Actualiza la plantilla del tipo de activo seleccionado.
                    </p>
                </div>

                <button
                    type="button"
                    class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                    onclick="closeEditElementTypeModal()"
                    title="Cerrar"
                >
                    ✕
                </button>
            </div>

            <form id="editElementTypeForm" method="POST" class="flex min-h-0 flex-1 flex-col">
                @csrf
                @method('PUT')

                <div class="min-h-0 flex-1 overflow-y-auto px-4 py-3 sm:px-5 sm:py-4">
                    <div class="space-y-4">
                        @if($showClientColumn)
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-slate-700">Cliente</label>
                                <div class="max-h-32 space-y-2 overflow-y-auto rounded-xl border border-slate-300 bg-white p-3">
                                    @foreach($clients as $client)
                                        <label class="flex items-center gap-3 text-sm text-slate-700">
                                            <input
                                                type="checkbox"
                                                name="edit_client_id_checkbox"
                                                value="{{ $client->id }}"
                                                class="edit-client-single-checkbox rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                                                onchange="handleSingleClientSelectionEdit(this)"
                                            >
                                            {{ $client->name }}
                                        </label>
                                    @endforeach
                                </div>
                                <input type="hidden" name="client_id" id="edit_selected_client_id">
                            </div>
                        @else
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-slate-700">Cliente</label>
                                <input
                                    type="text"
                                    value="{{ $singleClient?->name }}"
                                    disabled
                                    class="w-full rounded-xl border border-slate-300 bg-slate-100 px-3 py-2 text-sm text-slate-700"
                                >
                                <input type="hidden" name="client_id" value="{{ $singleClient?->id }}" id="edit_selected_client_id">
                            </div>
                        @endif

                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Nombre</label>
                            <input
                                type="text"
                                name="name"
                                id="edit_element_type_name"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            >
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <label class="flex items-start gap-3">
                                <input
                                    type="checkbox"
                                    name="has_semaphore"
                                    id="edit_element_type_has_semaphore"
                                    value="1"
                                    class="mt-1 rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                                >

                                <span>
                                    <span class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                                        <i data-lucide="calendar-days" class="h-4 w-4 text-[#d94d33]"></i>
                                        ¿Tiene semáforo semanal?
                                    </span>

                                    <span class="mt-1 block text-xs leading-5 text-slate-500">
                                        Activa esta opción si este tipo de activo debe aparecer en el semáforo de indicadores por semana.
                                    </span>
                                </span>
                            </label>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Estado</label>
                            <select
                                name="status"
                                id="edit_element_type_status"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            >
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>

                @foreach(($activeFilters['client_ids'] ?? []) as $value)
                    <input type="hidden" name="redirect_client_ids[]" value="{{ $value }}">
                @endforeach
                @foreach(($activeFilters['names'] ?? []) as $value)
                    <input type="hidden" name="redirect_names[]" value="{{ $value }}">
                @endforeach
                @foreach(($activeFilters['statuses'] ?? []) as $value)
                    <input type="hidden" name="redirect_statuses[]" value="{{ $value }}">
                @endforeach
                <input type="hidden" name="redirect_page" value="{{ $elementTypes->currentPage() }}">

                <div class="shrink-0 border-t border-slate-200 bg-slate-50 px-4 py-3 sm:px-5">
                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
                        <button
                            type="button"
                            onclick="closeEditElementTypeModal()"
                            class="inline-flex w-full justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 sm:w-auto"
                        >
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            class="inline-flex w-full justify-center rounded-xl bg-[#d94d33] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#b83f29] sm:w-auto"
                        >
                            Actualizar tipo de activo
                        </button>
                    </div>
                </div>
            </form>
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
    <div id="elementTypeToastContainer" class="fixed bottom-5 right-5 z-[99999] space-y-3"></div>
<script>
    const filterOptions = {
        @if($showClientColumn)
        client_ids: {
            type: 'checklist_object',
            title: 'Cliente',
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
        statuses: {
            type: 'checklist_object',
            title: 'Estado',
            inputName: 'statuses',
            options: @json($filterOptions['statuses']),
        },
    };

    const activeFilters = @json($activeFilters);
    let currentPopoverKey = null;
    let currentPage = {{ $elementTypes->currentPage() }};

    function showElementTypeToast(message, type = 'success') {
        const container = document.getElementById('elementTypeToastContainer');

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

    async function loadElementTypesList(page = 1, updateHistory = true) {
        const container = document.querySelector('[data-element-types-index]');
        if (!container) return;

        const indexUrl = container.dataset.indexUrl;
        const params = new URLSearchParams();

        Object.entries(activeFilters).forEach(([key, value]) => {
            if (Array.isArray(value)) {
                value.filter(v => v !== null && v !== '').forEach(v => params.append(`${key}[]`, v));
            } else if (value !== null && value !== '') {
                params.set(key, value);
            }
        });

        if (page > 1) {
            params.set('page', page);
        }

        try {
            const url = params.toString() ? `${indexUrl}?${params.toString()}` : indexUrl;

            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                showElementTypeToast(data.message || 'Error al cargar el listado.', 'error');
                return;
            }

            const listContainer = document.getElementById('elementTypesListContainer');
            if (listContainer) {
                listContainer.outerHTML = data.list_html;
            }

            updateElementTypeFilterOptions(data.filter_options);
            currentPage = data.current_page || page;

            const clearBtn = document.querySelector('[data-clear-filters]');
            if (clearBtn) {
                clearBtn.classList.toggle('hidden', !data.has_any_active_filter);
            }

            if (window.lucide) {
                window.lucide.createIcons();
            }

            if (updateHistory) {
                const historyUrl = params.toString() ? `${indexUrl}?${params.toString()}` : indexUrl;
                window.history.pushState({ page }, '', historyUrl);
            }
        } catch (error) {
            showElementTypeToast('Error de red al cargar el listado.', 'error');
        }
    }

    function updateElementTypeFilterOptions(newOptions) {
        if (!newOptions) return;

        if (newOptions.client_ids && filterOptions.client_ids) {
            filterOptions.client_ids.options = newOptions.client_ids;
        }

        if (newOptions.names && filterOptions.names) {
            filterOptions.names.options = Array.isArray(newOptions.names) ? newOptions.names : [];
        }

        if (newOptions.statuses && filterOptions.statuses) {
            filterOptions.statuses.options = newOptions.statuses;
        }
    }

    async function toggleElementTypeSemaphore(button) {
        const url = button.dataset.url;
        if (!url || button.disabled) return;

        button.disabled = true;
        button.classList.add('opacity-60', 'cursor-wait');

        try {
            const response = await fetch(url, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await response.json();

            if (!response.ok || data.success === false) {
                showElementTypeToast(data.message || 'No fue posible cambiar el estado del semáforo.', 'error');
                return;
            }

            showElementTypeToast(data.message || 'Estado del semáforo actualizado.', 'success');
            await loadElementTypesList(currentPage, false);
        } catch (error) {
            showElementTypeToast('Ocurrió un error de red al actualizar el semáforo.', 'error');
        } finally {
            button.disabled = false;
            button.classList.remove('opacity-60', 'cursor-wait');
        }
    }

    async function toggleElementTypeStatus(button) {
        const url = button.dataset.url;
        if (!url || button.disabled) return;

        button.disabled = true;
        button.classList.add('opacity-60', 'cursor-wait');

        try {
            const response = await fetch(url, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await response.json();

            if (!response.ok || data.success === false) {
                showElementTypeToast(data.message || 'No fue posible cambiar el estado.', 'error');
                return;
            }

            showElementTypeToast(data.message || 'Estado actualizado correctamente.', 'success');
            await loadElementTypesList(currentPage, false);
        } catch (error) {
            showElementTypeToast('Ocurrió un error de red al actualizar el estado.', 'error');
        } finally {
            button.disabled = false;
            button.classList.remove('opacity-60', 'cursor-wait');
        }
    }

    function openEditElementTypeModalFromButton(button) {
        openEditElementTypeModal(
            button.dataset.id,
            button.dataset.clientId,
            button.dataset.name,
            button.dataset.hasSemaphore,
            button.dataset.status,
            button.dataset.action
        );
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
        closeFilterPopover();
        loadElementTypesList(1);
    }

    function applyCurrentFilter() {
        if (!currentPopoverKey) return;
        const config = filterOptions[currentPopoverKey];
        const values = Array.from(document.querySelectorAll('#filterPopover .filter-check:checked'))
            .map(cb => cb.value);
        activeFilters[config.inputName] = values;
        closeFilterPopover();
        loadElementTypesList(1);
    }

    function submitFilters() {
        closeFilterPopover();
        loadElementTypesList(1);
    }

    function escapeHtml(text) {
        return text
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function handleSingleClientSelection(checkbox) {
        const all = document.querySelectorAll('.client-single-checkbox');
        all.forEach(item => {
            if (item !== checkbox) {
                item.checked = false;
            }
        });

        const clientId = checkbox.checked ? checkbox.value : '';
        document.getElementById('selected_client_id').value = clientId;
    }

    function handleSingleClientSelectionEdit(checkbox) {
        const all = document.querySelectorAll('.edit-client-single-checkbox');
        all.forEach(item => {
            if (item !== checkbox) {
                item.checked = false;
            }
        });

        const clientId = checkbox.checked ? checkbox.value : '';
        document.getElementById('edit_selected_client_id').value = clientId;
    }

    function openEditElementTypeModal(id, clientId, name, hasSemaphore, status, actionUrl) {
        clearElementTypeAjaxErrors('editElementTypeAjaxErrors');

        document.getElementById('editElementTypeForm').action = actionUrl;
        document.getElementById('edit_selected_client_id').value = clientId ?? '';
        document.getElementById('edit_element_type_name').value = name ?? '';
        document.getElementById('edit_element_type_has_semaphore').checked = String(hasSemaphore) === '1';
        document.getElementById('edit_element_type_status').value = status ?? '1';

        document.querySelectorAll('.edit-client-single-checkbox').forEach(cb => {
            cb.checked = parseInt(cb.value) === parseInt(clientId);
        });

        const modal = document.getElementById('editElementTypeModal');
        const content = document.getElementById('editElementTypeModalContent');

        modal.classList.remove('hidden');
        modal.classList.add('flex');

        document.documentElement.classList.add('overflow-hidden');
        document.body.classList.add('overflow-hidden');

        setTimeout(() => {
            content?.classList.remove('scale-95', 'opacity-0');
            content?.classList.add('scale-100', 'opacity-100');
        }, 10);

        if (window.lucide) {
            window.lucide.createIcons();
        }
    }

    function closeEditElementTypeModal() {
        const modal = document.getElementById('editElementTypeModal');
        const content = document.getElementById('editElementTypeModalContent');

        clearElementTypeAjaxErrors('editElementTypeAjaxErrors');

        content?.classList.remove('scale-100', 'opacity-100');
        content?.classList.add('scale-95', 'opacity-0');

        setTimeout(() => {
            modal.classList.remove('flex');
            modal.classList.add('hidden');

            document.documentElement.classList.remove('overflow-hidden');
            document.body.classList.remove('overflow-hidden');
        }, 150);
    }

    function clearElementTypeAjaxErrors(containerId) {
        const box = document.getElementById(containerId);
        if (!box) return;

        box.classList.add('hidden');
        box.innerHTML = '';
    }

    function renderElementTypeAjaxErrors(containerId, errors) {
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

    function setElementTypeFormSubmittingState(form, isSubmitting, loadingText = 'Guardando...') {
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

    async function parseElementTypeJsonResponse(response) {
        const contentType = response.headers.get('content-type') || '';

        if (!contentType.includes('application/json')) {
            throw new Error('El servidor no devolvió JSON. Revisa sesión, permisos o respuesta del controlador.');
        }

        return await response.json();
    }

    async function handleCreateElementTypeSubmit(event) {
        event.preventDefault();

        const form = event.currentTarget;
        clearElementTypeAjaxErrors('createElementTypeAjaxErrors');
        setElementTypeFormSubmittingState(form, true, 'Guardando...');

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

            const data = await parseElementTypeJsonResponse(response);

            if (response.status === 422) {
                const msgs = Object.values(data.errors || {}).flat().join(' ');
                showElementTypeToast(msgs || data.message || 'Corrige los errores del formulario.', 'error');
                return;
            }

            if (!response.ok || data.success === false) {
                throw new Error(data.message || 'No fue posible crear el tipo de activo.');
            }

            resetCreateElementTypeForm();
            showElementTypeToast(data.message || 'Tipo de activo creado correctamente.', 'success');
            await loadElementTypesList(1);
        } catch (error) {
            showElementTypeToast(error.message || 'Ocurrió un error al crear el tipo de activo.', 'error');
        } finally {
            setElementTypeFormSubmittingState(form, false);
        }
    }

    async function handleEditElementTypeSubmit(event) {
        event.preventDefault();

        const form = event.currentTarget;
        clearElementTypeAjaxErrors('editElementTypeAjaxErrors');
        setElementTypeFormSubmittingState(form, true, 'Actualizando...');

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

            const data = await parseElementTypeJsonResponse(response);

            if (response.status === 422) {
                renderElementTypeAjaxErrors('editElementTypeAjaxErrors', data.errors || {});
                showElementTypeToast(data.message || 'Corrige los errores del formulario.', 'error');
                return;
            }

            if (!response.ok || data.success === false) {
                throw new Error(data.message || 'No fue posible actualizar el tipo de activo.');
            }

            closeEditElementTypeModal();
            showElementTypeToast(data.message || 'Tipo de activo actualizado correctamente.', 'success');
            await loadElementTypesList(currentPage);
        } catch (error) {
            showElementTypeToast(error.message || 'Ocurrió un error al actualizar el tipo de activo.', 'error');
        } finally {
            setElementTypeFormSubmittingState(form, false);
        }
    }

    async function deleteElementType(elementTypeId) {
        const confirmed = confirm('¿Seguro que deseas eliminar este tipo de activo?');
        if (!confirmed) return;

        const form = document.getElementById(`delete-element-type-form-${elementTypeId}`);

        if (!form) {
            showElementTypeToast('No se encontró el formulario de eliminación.', 'error');
            return;
        }

        const row = document.getElementById(`element-type-row-${elementTypeId}`);
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

            const data = await parseElementTypeJsonResponse(response);

            if (!response.ok || data.success === false) {
                throw new Error(data.message || 'No fue posible eliminar el tipo de activo.');
            }

            showElementTypeToast(data.message || 'Tipo de activo eliminado correctamente.', 'success');
            await loadElementTypesList(currentPage);
        } catch (error) {
            if (row) {
                row.classList.remove('opacity-60', 'pointer-events-none');
            }
            showElementTypeToast(error.message || 'Ocurrió un error al eliminar el tipo de activo.', 'error');
        }
    }

    function resetCreateElementTypeForm() {
        const form = document.getElementById('createElementTypeForm');
        if (!form) return;

        // Preservar cliente seleccionado
        const clientId = document.getElementById('selected_client_id')?.value ?? '';

        // Solo limpiar nombre y semáforo
        const nameInput = document.getElementById('element_type_name');
        if (nameInput) nameInput.value = '';

        const semaphoreCheck = form.querySelector('[name="has_semaphore"]');
        if (semaphoreCheck) semaphoreCheck.checked = false;

        clearElementTypeAjaxErrors('createElementTypeAjaxErrors');

        // Restaurar checkbox de cliente
        if (clientId) {
            document.querySelectorAll('.client-single-checkbox').forEach(cb => {
                cb.checked = String(cb.value) === String(clientId);
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const selectedClient = document.getElementById('selected_client_id');

        if (selectedClient && selectedClient.value) {
            document.querySelectorAll('.client-single-checkbox').forEach(cb => {
                cb.checked = parseInt(cb.value) === parseInt(selectedClient.value);
            });
        }

        const input = document.getElementById('element_type_name');

        if (input) {
            input.addEventListener('input', function () {
                let value = this.value;
                if (value.length === 0) return;
                this.value = value.charAt(0).toUpperCase() + value.slice(1);
            });
        }

        const createElementTypeForm = document.getElementById('createElementTypeForm');
        const editElementTypeForm = document.getElementById('editElementTypeForm');

        if (createElementTypeForm) {
            createElementTypeForm.addEventListener('submit', handleCreateElementTypeSubmit);
        }

        if (editElementTypeForm) {
            editElementTypeForm.addEventListener('submit', handleEditElementTypeSubmit);
        }
    });

    document.addEventListener('click', function (event) {
        const paginationLink = event.target.closest('[data-pagination-link]');
        if (paginationLink) {
            event.preventDefault();
            const href = paginationLink.getAttribute('href');
            if (!href || href === '#') return;
            const url = new URL(href, window.location.origin);
            const page = parseInt(url.searchParams.get('page') || '1');
            loadElementTypesList(page);
            return;
        }

        const clearBtn = event.target.closest('[data-clear-filters]');
        if (clearBtn) {
            event.preventDefault();
            Object.keys(activeFilters).forEach(key => {
                activeFilters[key] = [];
            });
            loadElementTypesList(1);
            return;
        }

        const popover = document.getElementById('filterPopover');
        if (!popover.classList.contains('hidden')) {
            if (!popover.contains(event.target) && !event.target.closest('button[onclick^="openFilterPopover"]')) {
                closeFilterPopover();
            }
        }

        const modal = document.getElementById('editElementTypeModal');
        if (modal.classList.contains('flex') && event.target === modal) {
            closeEditElementTypeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeFilterPopover();
            closeEditElementTypeModal();
        }
    });

    window.addEventListener('popstate', function () {
        const url = new URL(window.location.href);

        activeFilters.client_ids = url.searchParams.getAll('client_ids[]');
        activeFilters.names = url.searchParams.getAll('names[]');
        activeFilters.statuses = url.searchParams.getAll('statuses[]');

        const page = parseInt(url.searchParams.get('page') || '1');
        loadElementTypesList(page, false);
    });
</script>
@endsection
