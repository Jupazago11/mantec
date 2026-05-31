@extends('layouts.admin')
@section('title', 'Componentes')
@section('header_title', 'Componentes')

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

        <div class="grid gap-8 xl:grid-cols-[320px_minmax(0,1fr)]">
            <div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Nuevo componente</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Registra un componente para uno de tus clientes.
                    </p>
                    <div id="createDiagnosticAjaxErrors" class="mt-4 hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>
                    <form
                        id="createDiagnosticForm"
                        method="POST"
                        action="{{ route('admin.managed-diagnostics.store') }}"
                        class="mt-6 space-y-5"
                    >
                        @csrf

                        @if($singleClient)
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">
                                    Cliente
                                </label>

                                <div class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                    {{ $singleClient->name }}
                                </div>

                                <input
                                    type="hidden"
                                    name="client_id"
                                    id="create_client_id"
                                    value="{{ $singleClient->id }}"
                                >

                                @error('client_id')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @else
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">
                                    Cliente
                                </label>

                                <div class="max-h-56 space-y-2 overflow-y-auto rounded-xl border border-slate-300 p-4">
                                    @foreach($clients as $client)
                                        <label class="flex items-center gap-3 text-sm text-slate-700">
                                            <input
                                                type="checkbox"
                                                name="client_id_checkbox"
                                                value="{{ $client->id }}"
                                                class="create-client-single-checkbox rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                                                @checked((string) old('client_id', $preferredClientId ?? '') === (string) $client->id)
                                                onchange="handleCreateDiagnosticClientSelection(this)"
                                            >
                                            {{ $client->name }}
                                        </label>
                                    @endforeach
                                </div>

                                <input
                                    type="hidden"
                                    name="client_id"
                                    id="create_client_id"
                                    value="{{ old('client_id', $preferredClientId ?? '') }}"
                                >

                                @error('client_id')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">
                                Tipo de activo
                            </label>

                            <select
                                name="element_type_id"
                                id="create_element_type_id"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                required
                            >
                                <option value="">Seleccione un tipo de activo</option>

                                @foreach($elementTypes as $elementType)
                                    <option
                                        value="{{ $elementType->id }}"
                                        data-client-id="{{ $elementType->client_id }}"
                                        @selected((string) old('element_type_id', $preferredElementTypeId ?? '') === (string) $elementType->id)
                                    >
                                        {{ $elementType->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('element_type_id')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">
                                Nombre del componente
                            </label>

                            <input
                                type="text"
                                name="name"
                                id="create_diagnostic_name"
                                value="{{ old('name') }}"
                                placeholder="Ej: Alineación, Temperatura..."
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                required
                            >

                            @error('name')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="pt-2">
                            <button
                                type="submit"
                                class="w-full rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b63c28]"
                            >
                                Crear componente
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div>
                <div
                    class="rounded-2xl border border-slate-200 bg-white shadow-sm"
                    data-diagnostics-index
                    data-index-url="{{ route('admin.managed-diagnostics.index') }}"
                >
                    <div class="border-b border-slate-200 px-6 py-4">
                        <div class="flex items-center justify-between gap-4">
                            <h3 class="text-lg font-semibold text-slate-900">
                                Listado de componentes
                            </h3>

                            <a
                                href="{{ route('admin.managed-diagnostics.index') }}"
                                data-clear-filters
                                class="inline-flex items-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 {{ $hasAnyActiveFilter ? '' : 'hidden' }}"
                            >
                                Limpiar filtros
                            </a>
                        </div>
                    </div>

                    @include('admin.managed-diagnostics.partials.list', [
                        'diagnostics' => $diagnostics,
                        'activeFilters' => $activeFilters,
                        'showClientColumn' => $showClientColumn,
                    ])
                </div>
            </div>
        </div>
    </div>

<div
    id="editDiagnosticModal"
    class="fixed left-0 top-0 z-[9999] hidden h-[100dvh] w-[100vw] items-center justify-center overflow-y-auto bg-slate-950/60 px-3 py-4 backdrop-blur-sm sm:px-4 sm:py-6"
>
    <div
        id="editDiagnosticModalContent"
        class="flex w-full max-w-2xl scale-95 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white opacity-0 shadow-2xl transition duration-200 ease-out"
        style="max-height: calc(100dvh - 2rem);"
    >
        <div class="flex shrink-0 items-center justify-between border-b border-slate-200 bg-white px-4 py-3 sm:px-5">
            <div>
                <h3 class="text-base font-bold text-slate-900 sm:text-lg">
                    Editar componente
                </h3>
                <p class="mt-0.5 hidden text-xs text-slate-500 sm:block">
                    Actualiza el componente seleccionado.
                </p>
            </div>

            <button
                type="button"
                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                onclick="closeEditDiagnosticModal()"
                title="Cerrar"
            >
                ✕
            </button>
        </div>

        <form id="editDiagnosticForm" method="POST" class="flex min-h-0 flex-1 flex-col">
            @csrf
            @method('PUT')

            <div class="min-h-0 flex-1 overflow-y-auto px-4 py-3 sm:px-5 sm:py-4">
                <div id="editDiagnosticAjaxErrors" class="mb-3 hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

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
                            <input type="hidden" name="client_id" id="edit_diagnostic_client_id">
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
                            <input type="hidden" name="client_id" value="{{ $singleClient?->id }}" id="edit_diagnostic_client_id">
                        </div>
                    @endif

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Tipo de activo</label>
                        <select
                            name="element_type_id"
                            id="edit_diagnostic_element_type_id"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            required
                        >
                            <option value="">Seleccione un tipo de activo</option>
                            @foreach($elementTypes as $elementType)
                                <option
                                    value="{{ $elementType->id }}"
                                    data-client-id="{{ $elementType->client_id }}"
                                >
                                    @if($showClientColumn)
                                        {{ $elementType->client?->name }} - {{ $elementType->name }}
                                    @else
                                        {{ $elementType->name }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Nombre</label>
                        <input
                            type="text"
                            name="name"
                            id="edit_diagnostic_name"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                        >
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Estado</label>
                        <select
                            name="status"
                            id="edit_diagnostic_status"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                        >
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="shrink-0 border-t border-slate-200 bg-slate-50 px-4 py-3 sm:px-5">
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
                    <button
                        type="button"
                        onclick="closeEditDiagnosticModal()"
                        class="inline-flex w-full justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 sm:w-auto"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="inline-flex w-full justify-center rounded-xl bg-[#d94d33] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#b83f29] sm:w-auto"
                    >
                        Actualizar diagnóstico
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
    <div id="diagnosticToastContainer" class="fixed bottom-5 right-5 z-[99999] space-y-3"></div>

<script>
    let filterOptions = {
        @if($showClientColumn)
        client_ids: {
            type: 'checklist_object',
            title: 'Cliente',
            inputName: 'client_ids',
            options: @json($filterOptions['client_ids']),
        },
        @endif
        element_type_ids: {
            type: 'checklist_object',
            title: 'Tipo de activo',
            inputName: 'element_type_ids',
            options: @json($filterOptions['element_type_ids']),
        },
        diagnostic_names: {
            type: 'checklist',
            title: 'Nombre',
            inputName: 'diagnostic_names',
            options: @json($filterOptions['diagnostic_names']),
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
    let currentPage = {{ $diagnostics->currentPage() }};

    async function loadDiagnosticsList(page = 1, updateHistory = true) {
        const container = document.querySelector('[data-diagnostics-index]');
        const indexUrl = container ? container.dataset.indexUrl : window.location.pathname;

        const params = new URLSearchParams();
        Object.entries(activeFilters).forEach(([key, value]) => {
            if (Array.isArray(value)) {
                value.filter(v => v !== null && v !== '').forEach(v => params.append(`${key}[]`, v));
            }
        });
        if (page > 1) params.set('page', page);

        const queryString = params.toString();
        const url = queryString ? `${indexUrl}?${queryString}` : indexUrl;
        const historyUrl = url;

        try {
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await response.json();

            if (!response.ok || !data.success) return;

            const listContainer = document.getElementById('diagnosticsListContainer');
            if (listContainer) {
                listContainer.outerHTML = data.list_html;
            }

            updateDiagnosticFilterOptions(data.filter_options);
            currentPage = data.current_page || page;

            const clearBtn = document.querySelector('[data-clear-filters]');
            if (clearBtn) clearBtn.classList.toggle('hidden', !data.has_any_active_filter);

            if (window.lucide) window.lucide.createIcons();

            if (updateHistory) {
                window.history.pushState({ page }, '', historyUrl);
            }
        } catch (e) {
            // silent
        }
    }

    function updateDiagnosticFilterOptions(newOptions) {
        if (!newOptions) return;

        if (newOptions.client_ids && filterOptions.client_ids) {
            filterOptions.client_ids.options = newOptions.client_ids;
        }
        if (newOptions.element_type_ids && filterOptions.element_type_ids) {
            filterOptions.element_type_ids.options = newOptions.element_type_ids;
        }
        if (newOptions.diagnostic_names && filterOptions.diagnostic_names) {
            filterOptions.diagnostic_names.options = newOptions.diagnostic_names;
        }
        if (newOptions.statuses && filterOptions.statuses) {
            filterOptions.statuses.options = newOptions.statuses;
        }
    }

    function closeFilterPopover() {
        const popover = document.getElementById('filterPopover');
        popover.classList.add('hidden');
        currentPopoverKey = null;
    }

    function filterCreateDiagnosticElementTypesByClient(clientId, selectedElementTypeId = '') {
        const elementTypeSelect = document.getElementById('create_element_type_id');
        if (!elementTypeSelect) return;

        Array.from(elementTypeSelect.options).forEach(option => {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            const matchesClient = clientId
                ? String(option.dataset.clientId) === String(clientId)
                : false;

            option.hidden = !matchesClient;

            if (!matchesClient) {
                option.selected = false;
            }
        });

        if (selectedElementTypeId) {
            const selectedOption = Array.from(elementTypeSelect.options).find(option =>
                option.value &&
                String(option.value) === String(selectedElementTypeId) &&
                String(option.dataset.clientId) === String(clientId)
            );

            if (selectedOption) {
                elementTypeSelect.value = String(selectedElementTypeId);
                return;
            }
        }

        const firstVisibleOption = Array.from(elementTypeSelect.options).find(option =>
            option.value && !option.hidden
        );

        elementTypeSelect.value = firstVisibleOption ? firstVisibleOption.value : '';
    }

    function filterEditDiagnosticElementTypesByClient(clientId) {
        const elementTypeSelect = document.getElementById('edit_diagnostic_element_type_id');
        if (!elementTypeSelect) return;

        const currentValue = elementTypeSelect.value;

        Array.from(elementTypeSelect.options).forEach(option => {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            option.hidden = clientId
                ? String(option.dataset.clientId) !== String(clientId)
                : false;
        });

        if (currentValue) {
            const selectedOption = elementTypeSelect.querySelector(`option[value="${currentValue}"]`);
            if (selectedOption && selectedOption.hidden) {
                elementTypeSelect.value = '';
            }
        }
    }

    function handleCreateDiagnosticClientSelection(checkbox) {
        const all = document.querySelectorAll('.create-client-single-checkbox');

        all.forEach(item => {
            if (item !== checkbox) {
                item.checked = false;
            }
        });

        const clientId = checkbox.checked ? checkbox.value : '';
        const hiddenClientInput = document.getElementById('create_client_id');

        if (hiddenClientInput) {
            hiddenClientInput.value = clientId;
        }

        filterCreateDiagnosticElementTypesByClient(clientId, '');
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
        loadDiagnosticsList(1);
    }

    function applyCurrentFilter() {
        if (!currentPopoverKey) return;

        const config = filterOptions[currentPopoverKey];
        const values = Array.from(document.querySelectorAll('#filterPopover .filter-check:checked'))
            .map(cb => cb.value);

        activeFilters[config.inputName] = values;
        closeFilterPopover();
        loadDiagnosticsList(1);
    }

    function escapeHtml(text) {
        return text
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function handleSingleClientSelectionEdit(checkbox) {
        const all = document.querySelectorAll('.edit-client-single-checkbox');
        all.forEach(item => {
            if (item !== checkbox) {
                item.checked = false;
            }
        });

        const clientId = checkbox.checked ? checkbox.value : '';
        document.getElementById('edit_diagnostic_client_id').value = clientId;

        filterEditDiagnosticElementTypesByClient(clientId);
    }

    function openEditDiagnosticModal(btn) {
        clearDiagnosticAjaxErrors('editDiagnosticAjaxErrors');

        const clientId = btn.dataset.client_id ?? '';
        const elementTypeId = btn.dataset.element_type_id ?? '';

        document.getElementById('editDiagnosticForm').action = btn.dataset.action;
        document.getElementById('edit_diagnostic_client_id').value = clientId;
        document.getElementById('edit_diagnostic_name').value = btn.dataset.name ?? '';
        document.getElementById('edit_diagnostic_status').value = btn.dataset.status ?? '1';

        document.querySelectorAll('.edit-client-single-checkbox').forEach(cb => {
            cb.checked = parseInt(cb.value) === parseInt(clientId);
        });

        filterEditDiagnosticElementTypesByClient(clientId);

        const elementTypeSelect = document.getElementById('edit_diagnostic_element_type_id');
        if (elementTypeSelect) {
            elementTypeSelect.value = elementTypeId ?? '';
        }

        const modal = document.getElementById('editDiagnosticModal');
        const content = document.getElementById('editDiagnosticModalContent');

        modal.classList.remove('hidden');
        modal.classList.add('flex');

        document.documentElement.classList.add('overflow-hidden');
        document.body.classList.add('overflow-hidden');

        setTimeout(() => {
            content?.classList.remove('scale-95', 'opacity-0');
            content?.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeEditDiagnosticModal() {
        const modal = document.getElementById('editDiagnosticModal');
        const content = document.getElementById('editDiagnosticModalContent');

        clearDiagnosticAjaxErrors('editDiagnosticAjaxErrors');

        content?.classList.remove('scale-100', 'opacity-100');
        content?.classList.add('scale-95', 'opacity-0');

        setTimeout(() => {
            modal.classList.remove('flex');
            modal.classList.add('hidden');

            document.documentElement.classList.remove('overflow-hidden');
            document.body.classList.remove('overflow-hidden');
        }, 150);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const hiddenClientInput = document.getElementById('create_client_id');
        const preferredElementTypeId = @json(old('element_type_id', $preferredElementTypeId ?? ''));

        if (hiddenClientInput && hiddenClientInput.value) {
            document.querySelectorAll('.create-client-single-checkbox').forEach(cb => {
                cb.checked = String(cb.value) === String(hiddenClientInput.value);
            });

            filterCreateDiagnosticElementTypesByClient(hiddenClientInput.value, preferredElementTypeId);
        } else if (@json((bool) $singleClient)) {
            filterCreateDiagnosticElementTypesByClient(
                @json((string) ($singleClient->id ?? '')),
                preferredElementTypeId
            );
        }

        const editClientIdInput = document.getElementById('edit_diagnostic_client_id');
        if (editClientIdInput && editClientIdInput.value) {
            filterEditDiagnosticElementTypesByClient(editClientIdInput.value);
        }

        const createDiagnosticForm = document.getElementById('createDiagnosticForm');
        const editDiagnosticForm = document.getElementById('editDiagnosticForm');

        if (createDiagnosticForm) {
            createDiagnosticForm.addEventListener('submit', handleCreateDiagnosticSubmit);
        }

        if (editDiagnosticForm) {
            editDiagnosticForm.addEventListener('submit', handleEditDiagnosticSubmit);
        }
    });

    document.addEventListener('click', function (event) {
        const popover = document.getElementById('filterPopover');
        const modal = document.getElementById('editDiagnosticModal');

        if (!popover.classList.contains('hidden')) {
            if (!popover.contains(event.target) && !event.target.closest('button[onclick^="openFilterPopover"]')) {
                closeFilterPopover();
            }
        }

        if (modal && modal.classList.contains('flex') && event.target === modal) {
            closeEditDiagnosticModal();
        }

        const paginationLink = event.target.closest('[data-pagination-link]');
        if (paginationLink) {
            event.preventDefault();
            const href = paginationLink.getAttribute('href');
            if (!href || href === '#') return;
            const pageParam = new URL(href, window.location.href).searchParams.get('page');
            loadDiagnosticsList(pageParam ? parseInt(pageParam) : 1);
        }

        const clearFiltersBtn = event.target.closest('[data-clear-filters]');
        if (clearFiltersBtn) {
            event.preventDefault();
            Object.keys(activeFilters).forEach(key => { activeFilters[key] = []; });
            loadDiagnosticsList(1);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeFilterPopover();
            closeEditDiagnosticModal();
        }
    });

    window.addEventListener('popstate', function (event) {
        const params = new URLSearchParams(window.location.search);

        Object.keys(activeFilters).forEach(key => { activeFilters[key] = []; });

        params.forEach((value, key) => {
            const cleanKey = key.replace('[]', '');
            if (cleanKey in activeFilters) {
                if (!Array.isArray(activeFilters[cleanKey])) activeFilters[cleanKey] = [];
                activeFilters[cleanKey].push(value);
            }
        });

        const page = parseInt(params.get('page') || '1');
        loadDiagnosticsList(page, false);
    });

    function showDiagnosticToast(message, type = 'success') {
        const container = document.getElementById('diagnosticToastContainer');

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

    function clearDiagnosticAjaxErrors(containerId) {
        const box = document.getElementById(containerId);
        if (!box) return;

        box.classList.add('hidden');
        box.innerHTML = '';
    }

    function renderDiagnosticAjaxErrors(containerId, errors) {
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

    function setDiagnosticFormSubmittingState(form, isSubmitting, loadingText = 'Guardando...') {
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

    async function parseDiagnosticJsonResponse(response) {
        const contentType = response.headers.get('content-type') || '';

        if (!contentType.includes('application/json')) {
            throw new Error('El servidor no devolvió JSON. Revisa sesión, permisos o respuesta del controlador.');
        }

        return await response.json();
    }

    async function handleCreateDiagnosticSubmit(event) {
        event.preventDefault();

        const form = event.currentTarget;
        clearDiagnosticAjaxErrors('createDiagnosticAjaxErrors');
        setDiagnosticFormSubmittingState(form, true, 'Guardando...');

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

            const data = await parseDiagnosticJsonResponse(response);

            if (response.status === 422) {
                renderDiagnosticAjaxErrors('createDiagnosticAjaxErrors', data.errors || {});
                showDiagnosticToast(data.message || 'Corrige los errores del formulario.', 'error');
                return;
            }

            if (!response.ok || data.success === false) {
                throw new Error(data.message || 'No fue posible crear el diagnóstico.');
            }

            resetCreateDiagnosticForm();
            loadDiagnosticsList(1);

            showDiagnosticToast(data.message || 'Diagnóstico creado correctamente.', 'success');
        } catch (error) {
            showDiagnosticToast(error.message || 'Ocurrió un error al crear el diagnóstico.', 'error');
        } finally {
            setDiagnosticFormSubmittingState(form, false);
        }
    }

    async function handleEditDiagnosticSubmit(event) {
        event.preventDefault();

        const form = event.currentTarget;
        clearDiagnosticAjaxErrors('editDiagnosticAjaxErrors');
        setDiagnosticFormSubmittingState(form, true, 'Actualizando...');

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

            const data = await parseDiagnosticJsonResponse(response);

            if (response.status === 422) {
                renderDiagnosticAjaxErrors('editDiagnosticAjaxErrors', data.errors || {});
                showDiagnosticToast(data.message || 'Corrige los errores del formulario.', 'error');
                return;
            }

            if (!response.ok || data.success === false) {
                throw new Error(data.message || 'No fue posible actualizar el diagnóstico.');
            }

            closeEditDiagnosticModal();
            loadDiagnosticsList(currentPage);

            showDiagnosticToast(data.message || 'Diagnóstico actualizado correctamente.', 'success');
        } catch (error) {
            showDiagnosticToast(error.message || 'Ocurrió un error al actualizar el diagnóstico.', 'error');
        } finally {
            setDiagnosticFormSubmittingState(form, false);
        }
    }

    async function deleteDiagnostic(diagnosticId) {
        const confirmed = confirm('¿Seguro que deseas eliminar este diagnóstico?');

        if (!confirmed) return;

        const row = document.getElementById(`diagnostic-row-${diagnosticId}`);
        const form = document.getElementById(`delete-diagnostic-form-${diagnosticId}`);

        if (!form) {
            showDiagnosticToast('No se encontró el formulario de eliminación.', 'error');
            return;
        }

        const formData = new FormData(form);

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
                body: formData,
            });

            const data = await parseDiagnosticJsonResponse(response);

            if (!response.ok || data.success === false) {
                throw new Error(data.message || 'No fue posible eliminar el diagnóstico.');
            }

            loadDiagnosticsList(currentPage);

            showDiagnosticToast(data.message || 'Diagnóstico eliminado correctamente.', 'success');
        } catch (error) {
            if (row) {
                row.classList.remove('opacity-60', 'pointer-events-none');
            }

            showDiagnosticToast(error.message || 'Ocurrió un error al eliminar el diagnóstico.', 'error');
        }
    }

    async function toggleDiagnosticStatus(button) {
        const url = button.dataset.url;

        if (!url || button.disabled) return;

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

            const data = await parseDiagnosticJsonResponse(response);

            if (!response.ok || data.success === false) {
                throw new Error(data.message || 'No fue posible cambiar el estado.');
            }

            loadDiagnosticsList(currentPage, false);

            showDiagnosticToast(data.message || 'Estado actualizado correctamente.', 'success');
        } catch (error) {
            button.disabled = false;
            button.classList.remove('opacity-60', 'cursor-wait');
            showDiagnosticToast(error.message || 'Ocurrió un error al cambiar el estado.', 'error');
        }
    }

    function resetCreateDiagnosticForm() {
        const form = document.getElementById('createDiagnosticForm');

        if (!form) return;

        form.reset();
        clearDiagnosticAjaxErrors('createDiagnosticAjaxErrors');

        const hiddenClientInput = document.getElementById('create_client_id');
        const createElementTypeSelect = document.getElementById('create_element_type_id');

        if (!@json((bool) $singleClient)) {
            if (hiddenClientInput) {
                hiddenClientInput.value = '';
            }

            document.querySelectorAll('.create-client-single-checkbox').forEach(cb => {
                cb.checked = false;
            });

            if (createElementTypeSelect) {
                createElementTypeSelect.value = '';
                filterCreateDiagnosticElementTypesByClient('', '');
            }
        } else {
            const preferredElementTypeId = @json($preferredElementTypeId ?? '');
            filterCreateDiagnosticElementTypesByClient(@json((string) ($singleClient->id ?? '')), preferredElementTypeId);
        }
    }
</script>

@endsection
