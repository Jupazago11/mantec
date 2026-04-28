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
<!-- FORMULARIO -->
            <div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Nueva área</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Registra una nueva área para uno de los clientes disponibles.
                    </p>
                    <div id="createAreaAjaxErrors" class="mt-4 hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>
                    <form
                        id="createAreaForm"
                        method="POST"
                        action="{{ route('admin.managed-areas.store') }}"
                        class="mt-6 space-y-5"
                    >
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
                                <select
                                    name="client_id"
                                    id="create_area_client_id"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                >
                                    <option value="">Seleccione un cliente</option>
                                    @foreach($clients as $client)
                                        <option 
                                            value="{{ $client->id }}" 
                                            @selected((string) $client->id === (string) old('client_id', $preferredClientId ?? ''))
                                        >
                                            {{ $client->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Nombre</label>
                            <input
                                type="text"
                                name="name"
                                value="{{ old('name') }}"
                                id="area_name"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                placeholder="Ej. Producción"
                            >
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Código</label>
                            <input
                                type="text"
                                name="code"
                                id="area_code"
                                value="{{ old('code') }}"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                placeholder="Opcional"
                            >
                        </div>

                        @foreach(($activeFilters['client_ids'] ?? []) as $value)
                            <input type="hidden" name="redirect_client_ids[]" value="{{ $value }}">
                        @endforeach

                        @foreach(($activeFilters['area_names'] ?? []) as $value)
                            <input type="hidden" name="redirect_area_names[]" value="{{ $value }}">
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
<!-- TABLA -->
            <div>
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-4">
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
                                            <span>Área</span>
                                            <button
                                                type="button"
                                                onclick="openFilterPopover(event, 'area_names')"
                                                class="rounded p-1 transition hover:bg-slate-200 {{ $hasFilter('area_names') ? 'text-[#d94d33]' : 'text-slate-400' }}"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Código
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Activos
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Estado
                                    </th>

                                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>

                            <tbody id="areasTableBody" class="divide-y divide-slate-200 bg-white">
                                @forelse($areas as $area)
                                    @php
                                        $hasDependencies = ($area->elements_count ?? 0) > 0;
                                    @endphp

                                    <tr class="hover:bg-slate-50" id="area-row-{{ $area->id }}">
                                        @if($showClientColumn)
                                            <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700" id="area-client-{{ $area->id }}">
                                                {{ $area->client?->name ?? '—' }}
                                            </td>
                                        @endif

                                        <td class="whitespace-nowrap px-5 py-3 text-sm font-medium text-slate-900" id="area-name-{{ $area->id }}">
                                            {{ $area->name }}
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700" id="area-code-{{ $area->id }}">
                                            {{ $area->code ?: '—' }}
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700" id="area-elements-count-{{ $area->id }}">
                                            {{ $area->elements_count ?? 0 }}
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-3 text-sm">
                                            <button
                                                type="button"
                                                data-status-toggle
                                                data-url="{{ route('admin.managed-areas.toggle-status', $area) }}"
                                                data-enabled="{{ $area->status ? '1' : '0' }}"
                                                onclick="toggleAreaStatus(this)"
                                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition {{ $area->status ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-red-100 text-red-700 hover:bg-red-200' }}"
                                                title="Clic para activar o inactivar"
                                            >
                                                <i data-lucide="{{ $area->status ? 'check-circle-2' : 'x-circle' }}" class="h-3.5 w-3.5"></i>
                                                <span>{{ $area->status ? 'Activo' : 'Inactivo' }}</span>
                                            </button>
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-3 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <button
                                                    type="button"
                                                    data-edit-area
                                                    data-id="{{ $area->id }}"
                                                    data-client_id="{{ $area->client_id }}"
                                                    data-name="{{ $area->name }}"
                                                    data-code="{{ $area->code }}"
                                                    data-action="{{ route('admin.managed-areas.update', $area) }}"
                                                    onclick="openEditAreaModal(this)"
                                                    class="text-slate-400 transition hover:text-[#d94d33]"
                                                    title="Editar área"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M16.862 4.487l1.651-1.651a2.121 2.121 0 113 3l-1.651 1.651M4 20h4l10.586-10.586a2 2 0 00-2.828-2.828L5.172 17.172A2 2 0 004 18.586V20z" />
                                                    </svg>
                                                </button>

                                                @if(!$hasDependencies)
                                                    <button
                                                        type="button"
                                                        onclick="deleteArea({{ $area->id }})"
                                                        class="text-red-500 transition hover:text-red-700"
                                                        title="Eliminar área"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M6 7h12M9 7V4h6v3M10 11v6M14 11v6M5 7l1 13a2 2 0 002 2h8a2 2 0 002-2l1-13" />
                                                        </svg>
                                                    </button>

                                                    <form
                                                        id="delete-area-form-{{ $area->id }}"
                                                        method="POST"
                                                        action="{{ route('admin.managed-areas.destroy', $area) }}"
                                                        class="hidden"
                                                    >
                                                        @csrf
                                                        @method('DELETE')

                                                        @foreach(($activeFilters['client_ids'] ?? []) as $value)
                                                            <input type="hidden" name="redirect_client_ids[]" value="{{ $value }}">
                                                        @endforeach

                                                        @foreach(($activeFilters['area_names'] ?? []) as $value)
                                                            <input type="hidden" name="redirect_area_names[]" value="{{ $value }}">
                                                        @endforeach

                                                        @foreach(($activeFilters['statuses'] ?? []) as $value)
                                                            <input type="hidden" name="redirect_statuses[]" value="{{ $value }}">
                                                        @endforeach

                                                        <input type="hidden" name="redirect_page" value="{{ $areas->currentPage() }}">
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
                        <div class="border-t border-slate-200 px-6 py-4">
                            {{ $areas->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
<div
    id="editAreaModal"
    class="fixed left-0 top-0 z-[9999] hidden h-[100dvh] w-[100vw] items-center justify-center overflow-y-auto bg-slate-950/60 px-3 py-4 backdrop-blur-sm sm:px-4 sm:py-6"
>
    <div
        id="editAreaModalContent"
        class="flex w-full max-w-xl scale-95 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white opacity-0 shadow-2xl transition duration-200 ease-out"
        style="max-height: calc(100dvh - 2rem);"
    >
        <div class="flex shrink-0 items-center justify-between border-b border-slate-200 bg-white px-4 py-3 sm:px-5">
            <div>
                <h3 class="text-base font-bold text-slate-900 sm:text-lg">
                    Editar área
                </h3>
                <p class="mt-0.5 hidden text-xs text-slate-500 sm:block">
                    Actualiza los datos del área seleccionada.
                </p>
            </div>

            <button
                type="button"
                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                onclick="closeEditAreaModal()"
                title="Cerrar"
            >
                ✕
            </button>
        </div>

        <form id="editAreaForm" method="POST" class="flex min-h-0 flex-1 flex-col">
            @csrf
            @method('PUT')

            <div class="min-h-0 flex-1 overflow-y-auto px-4 py-3 sm:px-5 sm:py-4">
                <div id="editAreaAjaxErrors" class="mb-3 hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

                <div class="space-y-4">
                    @if($singleClient)
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Cliente</label>
                            <div class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                {{ $singleClient->name }}
                            </div>
                            <input type="hidden" name="client_id" value="{{ $singleClient->id }}" id="edit_area_client_id_hidden">
                        </div>
                    @else
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Cliente</label>
                            <select
                                name="client_id"
                                id="edit_area_client_id"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            >
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}">
                                        {{ $client->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Nombre</label>
                        <input
                            type="text"
                            name="name"
                            id="edit_area_name"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                        >
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Código</label>
                        <input
                            type="text"
                            name="code"
                            id="edit_area_code"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                        >
                    </div>
                </div>
            </div>

            @foreach(($activeFilters['client_ids'] ?? []) as $value)
                <input type="hidden" name="redirect_client_ids[]" value="{{ $value }}">
            @endforeach

            @foreach(($activeFilters['area_names'] ?? []) as $value)
                <input type="hidden" name="redirect_area_names[]" value="{{ $value }}">
            @endforeach

            @foreach(($activeFilters['statuses'] ?? []) as $value)
                <input type="hidden" name="redirect_statuses[]" value="{{ $value }}">
            @endforeach

            <input type="hidden" name="redirect_page" value="{{ $areas->currentPage() }}">

            <div class="shrink-0 border-t border-slate-200 bg-slate-50 px-4 py-3 sm:px-5">
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
                    <button
                        type="button"
                        onclick="closeEditAreaModal()"
                        class="inline-flex w-full justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 sm:w-auto"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="inline-flex w-full justify-center rounded-xl bg-[#d94d33] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#b83f29] sm:w-auto"
                    >
                        Actualizar área
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
    <div id="areaToastContainer" class="fixed bottom-5 right-5 z-[99999] space-y-3"></div>

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
        area_names: {
            type: 'checklist',
            title: 'Área',
            inputName: 'area_names',
            options: @json($filterOptions['area_names']),
        },
        statuses: {
            type: 'checklist_object',
            title: 'Estado',
            inputName: 'statuses',
            options: [
                { value: '1', label: 'Activo' },
                { value: '0', label: 'Inactivo' }
            ],
        },
    };

    const activeFilters = @json($activeFilters);
    let currentPopoverKey = null;

function openEditAreaModal(btn) {
    clearAreaAjaxErrors('editAreaAjaxErrors');

    document.getElementById('editAreaForm').action = btn.dataset.action;
    document.getElementById('edit_area_name').value = btn.dataset.name ?? '';
    document.getElementById('edit_area_code').value = btn.dataset.code ?? '';

    const clientSelect = document.getElementById('edit_area_client_id');
    const clientHidden = document.getElementById('edit_area_client_id_hidden');

    if (clientSelect) {
        clientSelect.value = btn.dataset.client_id ?? '';
    }

    if (clientHidden) {
        clientHidden.value = btn.dataset.client_id ?? '';
    }

    const modal = document.getElementById('editAreaModal');
    const content = document.getElementById('editAreaModalContent');

    modal.classList.remove('hidden');
    modal.classList.add('flex');

    document.documentElement.classList.add('overflow-hidden');
    document.body.classList.add('overflow-hidden');

    setTimeout(() => {
        content?.classList.remove('scale-95', 'opacity-0');
        content?.classList.add('scale-100', 'opacity-100');
    }, 10);
}

function closeEditAreaModal() {
    const modal = document.getElementById('editAreaModal');
    const content = document.getElementById('editAreaModalContent');

    clearAreaAjaxErrors('editAreaAjaxErrors');

    content?.classList.remove('scale-100', 'opacity-100');
    content?.classList.add('scale-95', 'opacity-0');

    setTimeout(() => {
        modal.classList.remove('flex');
        modal.classList.add('hidden');

        document.documentElement.classList.remove('overflow-hidden');
        document.body.classList.remove('overflow-hidden');
    }, 150);
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
                value
                    .filter(item => item !== null && item !== '')
                    .forEach(item => addHidden(`${key}[]`, item));
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

    document.addEventListener('click', function (event) {
        const popover = document.getElementById('filterPopover');
        const modal = document.getElementById('editAreaModal');

        if (!popover.classList.contains('hidden')) {
            if (!popover.contains(event.target) && !event.target.closest('button[onclick^="openFilterPopover"]')) {
                closeFilterPopover();
            }
        }

        if (modal.classList.contains('flex') && event.target === modal) {
            closeEditAreaModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeFilterPopover();
            closeEditAreaModal();
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        const input = document.getElementById('area_name');

        if (!input) return;

        input.addEventListener('input', function () {
            const start = this.selectionStart;
            const end = this.selectionEnd;

            this.value = this.value.toUpperCase();

            this.setSelectionRange(start, end);
        });

        const createAreaForm = document.getElementById('createAreaForm');
        const editAreaForm = document.getElementById('editAreaForm');

        if (createAreaForm) {
            createAreaForm.addEventListener('submit', handleCreateAreaSubmit);
        }

        if (editAreaForm) {
            editAreaForm.addEventListener('submit', handleEditAreaSubmit);
        }
    });

    function showAreaToast(message, type = 'success') {
    const container = document.getElementById('areaToastContainer');

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

function clearAreaAjaxErrors(containerId) {
    const box = document.getElementById(containerId);
    if (!box) return;

    box.classList.add('hidden');
    box.innerHTML = '';
}

function renderAreaAjaxErrors(containerId, errors) {
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

function setAreaFormSubmittingState(form, isSubmitting, loadingText = 'Guardando...') {
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

async function parseAreaJsonResponse(response) {
    const contentType = response.headers.get('content-type') || '';

    if (!contentType.includes('application/json')) {
        throw new Error('El servidor no devolvió JSON. Revisa sesión, permisos o respuesta del controlador.');
    }

    return await response.json();
}

async function handleCreateAreaSubmit(event) {
    event.preventDefault();

    const form = event.currentTarget;
    clearAreaAjaxErrors('createAreaAjaxErrors');
    setAreaFormSubmittingState(form, true, 'Guardando...');

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

        const data = await parseAreaJsonResponse(response);

        if (response.status === 422) {
            renderAreaAjaxErrors('createAreaAjaxErrors', data.errors || {});
            showAreaToast(data.message || 'Corrige los errores del formulario.', 'error');
            return;
        }

        if (!response.ok || data.success === false) {
            throw new Error(data.message || 'No fue posible crear el área.');
        }

        showAreaToast(data.message || 'Área creada correctamente.', 'success');

        window.location.reload();
    } catch (error) {
        showAreaToast(error.message || 'Ocurrió un error al crear el área.', 'error');
    } finally {
        setAreaFormSubmittingState(form, false);
    }
}

async function handleEditAreaSubmit(event) {
    event.preventDefault();

    const form = event.currentTarget;
    clearAreaAjaxErrors('editAreaAjaxErrors');
    setAreaFormSubmittingState(form, true, 'Actualizando...');

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

        const data = await parseAreaJsonResponse(response);

        if (response.status === 422) {
            renderAreaAjaxErrors('editAreaAjaxErrors', data.errors || {});
            showAreaToast(data.message || 'Corrige los errores del formulario.', 'error');
            return;
        }

        if (!response.ok || data.success === false) {
            throw new Error(data.message || 'No fue posible actualizar el área.');
        }

        updateAreaRow(data.area);
        closeEditAreaModal();

        showAreaToast(data.message || 'Área actualizada correctamente.', 'success');
    } catch (error) {
        showAreaToast(error.message || 'Ocurrió un error al actualizar el área.', 'error');
    } finally {
        setAreaFormSubmittingState(form, false);
    }
}

async function toggleAreaStatus(button) {
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

        const data = await parseAreaJsonResponse(response);

        if (!response.ok || data.success === false) {
            throw new Error(data.message || 'No fue posible cambiar el estado.');
        }

        renderAreaStatusButton(button, Boolean(data.status));
        showAreaToast(data.message || 'Estado actualizado correctamente.', 'success');
    } catch (error) {
        button.innerHTML = originalHtml;
        button.className = originalClass;
        showAreaToast(error.message || 'Ocurrió un error al cambiar el estado.', 'error');
    } finally {
        button.disabled = false;
        button.classList.remove('opacity-60', 'cursor-wait');

        if (window.lucide) {
            window.lucide.createIcons();
        }
    }
}

function renderAreaStatusButton(button, enabled) {
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

async function deleteArea(areaId) {
    const confirmed = confirm('¿Seguro que deseas eliminar esta área?');

    if (!confirmed) return;

    const row = document.getElementById(`area-row-${areaId}`);
    const form = document.getElementById(`delete-area-form-${areaId}`);

    if (!form) {
        showAreaToast('No se encontró el formulario de eliminación.', 'error');
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

        const data = await parseAreaJsonResponse(response);

        if (!response.ok || data.success === false) {
            throw new Error(data.message || 'No fue posible eliminar el área.');
        }

        if (row) {
            row.style.transition = 'opacity 180ms ease, transform 180ms ease';
            row.style.opacity = '0';
            row.style.transform = 'scale(0.98)';
            setTimeout(() => row.remove(), 180);
        }

        showAreaToast(data.message || 'Área eliminada correctamente.', 'success');
    } catch (error) {
        if (row) {
            row.classList.remove('opacity-60', 'pointer-events-none');
        }

        showAreaToast(error.message || 'Ocurrió un error al eliminar el área.', 'error');
    }
}

function updateAreaRow(area) {
    if (!area || !area.id) return;

    const row = document.getElementById(`area-row-${area.id}`);
    const clientEl = document.getElementById(`area-client-${area.id}`);
    const nameEl = document.getElementById(`area-name-${area.id}`);
    const codeEl = document.getElementById(`area-code-${area.id}`);
    const elementsCountEl = document.getElementById(`area-elements-count-${area.id}`);
    const editButton = row?.querySelector('[data-edit-area]');
    const statusButton = row?.querySelector('[data-status-toggle]');

    if (clientEl) clientEl.textContent = area.client_name ?? '—';
    if (nameEl) nameEl.textContent = area.name ?? '—';
    if (codeEl) codeEl.textContent = area.code_label ?? '—';
    if (elementsCountEl) elementsCountEl.textContent = String(area.elements_count ?? 0);

    if (editButton) {
        editButton.dataset.client_id = area.client_id ?? '';
        editButton.dataset.name = area.name ?? '';
        editButton.dataset.code = area.code ?? '';
        editButton.dataset.action = area.update_url ?? '';
    }

    if (statusButton) {
        statusButton.dataset.url = area.toggle_status_url ?? statusButton.dataset.url;
        renderAreaStatusButton(statusButton, Boolean(area.status));
    }

    if (window.lucide) {
        window.lucide.createIcons();
    }
}
</script>

@endsection


