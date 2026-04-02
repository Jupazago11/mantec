@extends('layouts.admin')
@section('title', 'Áreas')
@section('header_title', 'Áreas')

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
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Gestión de áreas</h2>
            <p class="mt-2 text-slate-600">
                Crea y administra áreas para los clientes que tienes asignados.
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

        <div class="grid gap-8 xl:grid-cols-[320px_minmax(0,1fr)]">
            <div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Nueva área</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Registra una nueva área para uno de tus clientes.
                    </p>

                    <form method="POST" action="{{ route('admin.managed-areas.store') }}" class="mt-6 space-y-5">
                        @csrf

                        @if($singleClient)
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Cliente</label>
                                <div class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                    {{ $singleClient->name }}
                                </div>
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
                                                {{ old('client_id') == $client->id ? 'checked' : '' }}
                                                onchange="handleSingleClientSelection(this)"
                                            >
                                            {{ $client->name }}
                                        </label>
                                    @endforeach
                                </div>
                                <input type="hidden" name="client_id" id="selected_client_id" value="{{ old('client_id') }}">
                            </div>
                        @endif

                        <x-form.input
                            name="name"
                            label="Nombre"
                            placeholder="Ej. TRITURACION"
                        />

                        <x-form.input
                            name="code"
                            label="Código"
                            placeholder="Ej. TR-01"
                        />

                        @foreach(($activeFilters['client_ids'] ?? []) as $value)
                            <input type="hidden" name="redirect_client_ids[]" value="{{ $value }}">
                        @endforeach
                        @foreach(($activeFilters['names'] ?? []) as $value)
                            <input type="hidden" name="redirect_names[]" value="{{ $value }}">
                        @endforeach
                        @foreach(($activeFilters['codes'] ?? []) as $value)
                            <input type="hidden" name="redirect_codes[]" value="{{ $value }}">
                        @endforeach
                        @foreach(($activeFilters['statuses'] ?? []) as $value)
                            <input type="hidden" name="redirect_statuses[]" value="{{ $value }}">
                        @endforeach
                        <input type="hidden" name="redirect_page" value="{{ request('page', 1) }}">

                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                        >
                            Guardar área
                        </button>
                    </form>
                </div>
            </div>

            <div>
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <div class="flex items-center justify-between gap-4">
                            <h3 class="text-lg font-semibold text-slate-900">Listado de áreas</h3>

                            @if($hasAnyActiveFilter)
                                <a
                                    href="{{ route('admin.managed-areas.index') }}"
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
                                    @if($showClientColumn)
                                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                            <div class="flex items-center gap-2">
                                                <span>Cliente</span>
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
                                        <div class="flex items-center gap-2">
                                            <span>Código</span>
                                            <button
                                                type="button"
                                                onclick="openFilterPopover(event, 'codes')"
                                                class="rounded p-1 transition hover:bg-slate-200 {{ $hasFilter('codes') ? 'text-[#d94d33]' : 'text-slate-400' }}"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Activos
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
                                @forelse($areas as $area)
                                    @php
                                        $hasDependencies = $area->elements_count > 0;
                                    @endphp

                                    <tr class="hover:bg-slate-50">
                                        @if($showClientColumn)
                                            <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700">
                                                {{ $area->client?->name ?? '—' }}
                                            </td>
                                        @endif

                                        <td class="whitespace-nowrap px-5 py-3 text-sm font-medium text-slate-900">
                                            {{ $area->name }}
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-600">
                                            {{ $area->code ?: '—' }}
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700">
                                            {{ $area->elements_count }}
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-3 text-sm">
                                            @if($area->status)
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
                                                <button
                                                    type="button"
                                                    class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                                                    data-name="{{ $area->name }}"
                                                    data-code="{{ $area->code }}"
                                                    data-client_id="{{ $area->client_id }}"
                                                    data-action="{{ route('admin.managed-areas.update', $area) }}"
                                                    onclick="openEditAreaModal(this)"
                                                >
                                                    Editar
                                                </button>

                                                @if(!$hasDependencies)
                                                    <form
                                                        method="POST"
                                                        action="{{ route('admin.managed-areas.destroy', $area) }}"
                                                        onsubmit="return confirm('¿Seguro que deseas eliminar esta área?');"
                                                    >
                                                        @csrf
                                                        @method('DELETE')
                                                        @foreach(($activeFilters['client_ids'] ?? []) as $value)
                                                            <input type="hidden" name="redirect_client_ids[]" value="{{ $value }}">
                                                        @endforeach
                                                        @foreach(($activeFilters['names'] ?? []) as $value)
                                                            <input type="hidden" name="redirect_names[]" value="{{ $value }}">
                                                        @endforeach
                                                        @foreach(($activeFilters['codes'] ?? []) as $value)
                                                            <input type="hidden" name="redirect_codes[]" value="{{ $value }}">
                                                        @endforeach
                                                        @foreach(($activeFilters['statuses'] ?? []) as $value)
                                                            <input type="hidden" name="redirect_statuses[]" value="{{ $value }}">
                                                        @endforeach
                                                        <input type="hidden" name="redirect_page" value="{{ $areas->currentPage() }}">

                                                        <button
                                                            type="submit"
                                                            class="rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-red-700"
                                                        >
                                                            Eliminar
                                                        </button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('admin.managed-areas.toggle-status', $area) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        @foreach(($activeFilters['client_ids'] ?? []) as $value)
                                                            <input type="hidden" name="redirect_client_ids[]" value="{{ $value }}">
                                                        @endforeach
                                                        @foreach(($activeFilters['names'] ?? []) as $value)
                                                            <input type="hidden" name="redirect_names[]" value="{{ $value }}">
                                                        @endforeach
                                                        @foreach(($activeFilters['codes'] ?? []) as $value)
                                                            <input type="hidden" name="redirect_codes[]" value="{{ $value }}">
                                                        @endforeach
                                                        @foreach(($activeFilters['statuses'] ?? []) as $value)
                                                            <input type="hidden" name="redirect_statuses[]" value="{{ $value }}">
                                                        @endforeach
                                                        <input type="hidden" name="redirect_page" value="{{ $areas->currentPage() }}">

                                                        <button
                                                            type="submit"
                                                            class="rounded-lg px-3 py-2 text-xs font-semibold text-white transition {{ $area->status ? 'bg-amber-500 hover:bg-amber-600' : 'bg-green-600 hover:bg-green-700' }}"
                                                        >
                                                            {{ $area->status ? 'Inactivar' : 'Activar' }}
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $showClientColumn ? 6 : 5 }}" class="px-5 py-10 text-center text-sm text-slate-500">
                                            No hay áreas registradas todavía.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($areas->hasPages())
                        <div class="border-t border-slate-200 px-5 py-4">
                            {{ $areas->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div id="editAreaModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
        <div class="w-full max-w-3xl rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Editar área</h3>
                <button type="button" class="text-slate-500 hover:text-slate-900" onclick="closeEditAreaModal()">✕</button>
            </div>

            <form id="editAreaForm" method="POST" class="space-y-5 p-6">
                @csrf
                @method('PUT')

                @if($singleClient)
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Cliente</label>
                        <div class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            {{ $singleClient->name }}
                        </div>
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
                @endif

                <x-form.input
                    name="name"
                    label="Nombre"
                    id="edit_area_name"
                />

                <x-form.input
                    name="code"
                    label="Código"
                    id="edit_area_code"
                />

                @foreach(($activeFilters['client_ids'] ?? []) as $value)
                    <input type="hidden" name="redirect_client_ids[]" value="{{ $value }}">
                @endforeach
                @foreach(($activeFilters['names'] ?? []) as $value)
                    <input type="hidden" name="redirect_names[]" value="{{ $value }}">
                @endforeach
                @foreach(($activeFilters['codes'] ?? []) as $value)
                    <input type="hidden" name="redirect_codes[]" value="{{ $value }}">
                @endforeach
                @foreach(($activeFilters['statuses'] ?? []) as $value)
                    <input type="hidden" name="redirect_statuses[]" value="{{ $value }}">
                @endforeach
                <input type="hidden" name="redirect_page" value="{{ $areas->currentPage() }}">

                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        onclick="closeEditAreaModal()"
                        class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        class="rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                    >
                        Actualizar área
                    </button>
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
            codes: {
                type: 'checklist',
                title: 'Código',
                inputName: 'codes',
                options: @json($filterOptions['codes']),
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

        function handleSingleClientSelection(checkbox) {
            const all = document.querySelectorAll('.client-single-checkbox');
            all.forEach(item => {
                if (item !== checkbox) {
                    item.checked = false;
                }
            });

            document.getElementById('selected_client_id').value = checkbox.checked ? checkbox.value : '';
        }

        function handleSingleClientSelectionEdit(checkbox) {
            const all = document.querySelectorAll('.edit-client-single-checkbox');
            all.forEach(item => {
                if (item !== checkbox) {
                    item.checked = false;
                }
            });

            document.getElementById('edit_selected_client_id').value = checkbox.checked ? checkbox.value : '';
        }

        function openEditAreaModal(btn) {
            document.getElementById('editAreaForm').action = btn.dataset.action;
            document.getElementById('edit_area_name').value = btn.dataset.name ?? '';
            document.getElementById('edit_area_code').value = btn.dataset.code ?? '';

            const clientId = btn.dataset.client_id ?? '';
            const hiddenClientInput = document.getElementById('edit_selected_client_id');

            if (hiddenClientInput) {
                hiddenClientInput.value = clientId;
            }

            document.querySelectorAll('.edit-client-single-checkbox').forEach(cb => {
                cb.checked = parseInt(cb.value) === parseInt(clientId);
            });

            const modal = document.getElementById('editAreaModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeEditAreaModal() {
            const modal = document.getElementById('editAreaModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }

        document.addEventListener('DOMContentLoaded', function () {
            const selectedClient = document.getElementById('selected_client_id');

            if (selectedClient && selectedClient.value) {
                document.querySelectorAll('.client-single-checkbox').forEach(cb => {
                    cb.checked = parseInt(cb.value) === parseInt(selectedClient.value);
                });
            }
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
