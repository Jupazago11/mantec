@extends('layouts.admin')
@section('title', 'Agrupaciones')
@section('header_title', 'Agrupaciones')

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

<div class="space-y-8" data-groups-index data-index-url="{{ route('admin.managed-groups.index') }}">
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
                <h3 class="text-lg font-semibold text-slate-900">Nueva agrupación</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Define agrupaciones operativas para organizar activos por cliente.
                </p>
                <div id="createGroupAjaxErrors" class="mt-4 hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>
                <form
                    id="createGroupForm"
                    method="POST"
                    action="{{ route('admin.managed-groups.store') }}"
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
                            <div class="max-h-56 space-y-2 overflow-y-auto rounded-xl border border-slate-300 p-4">
                                @foreach($clients as $client)
                                    <label class="flex items-center gap-3 text-sm text-slate-700">
                                        <input
                                            type="checkbox"
                                            name="client_id_checkbox"
                                            value="{{ $client->id }}"
                                            class="group-single-checkbox rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
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

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Nombre</label>
                        <input
                            type="text"
                            name="name"
                            id="create_group_name"
                            value="{{ old('name') }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            placeholder="Ej. Gamas mecánicas"
                        >
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Descripción</label>
                        <textarea
                            name="description"
                            id="create_group_description"
                            rows="4"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            placeholder="Opcional"
                        >{{ old('description') }}</textarea>
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
                        Guardar agrupación
                    </button>
                </form>
            </div>
        </div>

        <div>
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <div class="flex items-center justify-between gap-4">
                        <h3 class="text-lg font-semibold text-slate-900">Listado de agrupaciones</h3>

                        <a
                            href="{{ route('admin.managed-groups.index') }}"
                            data-clear-filters
                            class="inline-flex items-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 {{ $hasAnyActiveFilter ? '' : 'hidden' }}"
                        >
                            Limpiar filtros
                        </a>
                    </div>
                </div>

                <form id="filtersForm" method="GET" class="hidden"></form>
                @include('admin.managed-groups.partials.list', [
                    'groups' => $groups,
                    'showClientColumn' => $showClientColumn,
                    'activeFilters' => $activeFilters,
                    'hasFilter' => $hasFilter,
                ])
            </div>
        </div>
    </div>
</div>

{{-- MODAL EDITAR --}}
<div
    id="editGroupModal"
    class="fixed left-0 top-0 z-[9999] hidden h-[100dvh] w-[100vw] items-center justify-center overflow-y-auto bg-slate-950/60 px-3 py-4 backdrop-blur-sm sm:px-4 sm:py-6"
>
    <div
        id="editGroupModalContent"
        class="flex w-full max-w-2xl scale-95 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white opacity-0 shadow-2xl transition duration-200 ease-out"
        style="max-height: calc(100dvh - 2rem);"
    >
        <div class="flex shrink-0 items-center justify-between border-b border-slate-200 bg-white px-4 py-3 sm:px-5">
            <div>
                <h3 class="text-base font-bold text-slate-900 sm:text-lg">
                    Editar agrupación
                </h3>
                <p class="mt-0.5 hidden text-xs text-slate-500 sm:block">
                    Actualiza los datos de la agrupación seleccionada.
                </p>
            </div>

            <button
                type="button"
                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                onclick="closeEditGroupModal()"
                title="Cerrar"
            >
                ✕
            </button>
        </div>

        <form id="editGroupForm" method="POST" class="flex min-h-0 flex-1 flex-col">
            @csrf
            @method('PUT')

            <div class="min-h-0 flex-1 overflow-y-auto px-4 py-3 sm:px-5 sm:py-4">
                <div id="editGroupAjaxErrors" class="mb-3 hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

                <div class="space-y-4">
                    @if($singleClient)
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Cliente</label>
                            <div class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                {{ $singleClient->name }}
                            </div>
                            <input type="hidden" name="client_id" id="edit_client_id_hidden" value="{{ $singleClient->id }}">
                        </div>
                    @else
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Cliente</label>
                            <select
                                name="client_id"
                                id="edit_client_id"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            >
                                <option value="">Seleccione un cliente</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}">{{ $client->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Nombre</label>
                        <input
                            type="text"
                            name="name"
                            id="edit_name"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                        >
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Descripción</label>
                        <textarea
                            name="description"
                            id="edit_description"
                            rows="3"
                            class="w-full resize-none rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                        ></textarea>
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
            <input type="hidden" name="redirect_page" value="{{ $groups->currentPage() }}">

            <div class="shrink-0 border-t border-slate-200 bg-slate-50 px-4 py-3 sm:px-5">
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
                    <button
                        type="button"
                        onclick="closeEditGroupModal()"
                        class="inline-flex w-full justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 sm:w-auto"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="inline-flex w-full justify-center rounded-xl bg-[#d94d33] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#b83f29] sm:w-auto"
                    >
                        Actualizar agrupación
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- MODAL ACTIVOS --}}
<div
    id="elementsModal"
    class="fixed left-0 top-0 z-[9999] hidden h-[100dvh] w-[100vw] items-center justify-center overflow-y-auto bg-slate-950/60 px-3 py-4 backdrop-blur-sm sm:px-4 sm:py-6"
>
    <div
        id="elementsModalContent"
        class="flex w-full max-w-5xl scale-95 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white opacity-0 shadow-2xl transition duration-200 ease-out"
        style="max-height: calc(100dvh - 2rem);"
    >
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <h3 class="text-lg font-semibold text-slate-900">Asociar activos</h3>
            <button type="button" class="text-slate-500 hover:text-slate-900" onclick="closeElementsModal()">✕</button>
        </div>

        <form id="elementsForm" method="POST" class="flex min-h-0 flex-1 flex-col">
            @csrf
            <div class="min-h-0 flex-1 overflow-y-auto px-4 py-3 sm:px-5 sm:py-4">

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Agrupación</div>
                        <div id="elements_group_name" class="mt-1 text-sm text-slate-900"></div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Descripción</div>
                        <div id="elements_group_description" class="mt-1 text-sm text-slate-900"></div>
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Activos disponibles</label>

                    <div id="elementsEmptyState" class="hidden rounded-xl border border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                        No hay activos disponibles para este cliente.
                    </div>

                    <div id="elementsChecklist" class="max-h-[52dvh] space-y-5 overflow-y-auto rounded-xl border border-slate-200 p-4">
                        @php
                            $elementsGrouped = $availableElements
                                ->groupBy(fn ($element) => $element->area?->client_id)
                                ->map(function ($clientElements) {
                                    return $clientElements
                                        ->groupBy(fn ($element) => $element->elementType?->name ?? 'Sin tipo')
                                        ->sortKeys(SORT_NATURAL | SORT_FLAG_CASE);
                                });
                        @endphp

                        @foreach($elementsGrouped as $clientId => $typeGroups)
                            @foreach($typeGroups as $typeName => $typeElements)
                                <div
                                    class="rounded-2xl border border-slate-200"
                                    data-element-type-group
                                    data-client-id="{{ $clientId }}"
                                    data-type-name="{{ $typeName }}"
                                >
                                    <div class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50 px-4 py-4 md:flex-row md:items-center md:justify-between">
                                        <div>
                                            <div class="text-sm font-semibold text-slate-900">{{ $typeName }}</div>
                                            <div class="text-xs text-slate-500">{{ $typeElements->count() }} activos</div>
                                        </div>

                                        <div class="flex gap-2">
                                            <button
                                                type="button"
                                                class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                                                onclick="selectAllElementsByType(this)"
                                            >
                                                Seleccionar todos
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                                                onclick="clearAllElementsByType(this)"
                                            >
                                                Deseleccionar todos
                                            </button>
                                        </div>
                                    </div>

                                    <div class="grid gap-3 p-4 md:grid-cols-2">
                                        @foreach($typeElements->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE) as $element)
                                            <label
                                                class="flex items-start gap-3 rounded-xl border border-slate-200 p-3 text-sm text-slate-700"
                                                data-element-item
                                                data-client-id="{{ $element->area?->client_id }}"
                                                data-type-name="{{ $typeName }}"
                                            >
                                                <input
                                                    type="checkbox"
                                                    name="element_ids[]"
                                                    value="{{ $element->id }}"
                                                    data-element-checkbox
                                                    class="mt-0.5 rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                                                >
                                                <span>
                                                    <span class="block font-medium text-slate-900">{{ $element->name }}</span>
                                                    <span class="block text-xs text-slate-500">
                                                        Área: {{ $element->area?->name ?? '—' }}
                                                        @if($element->group?->name)
                                                            · Grupo actual: {{ $element->group->name }}
                                                        @endif
                                                    </span>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        @endforeach
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
                <input type="hidden" name="redirect_page" value="{{ $groups->currentPage() }}">

                <div class="shrink-0 border-t border-slate-200 bg-slate-50 px-4 py-3 sm:px-5">
                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
                        <button
                            type="button"
                            onclick="closeElementsModal()"
                            class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                        >
                            Guardar activos
                        </button>
                    </div>
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
{{-- MODAL CONFIGURACIÓN DE COLUMNAS --}}
<div
    id="reportConfigModal"
    class="fixed left-0 top-0 z-[9999] hidden h-[100dvh] w-[100vw] items-center justify-center overflow-y-auto bg-slate-950/60 px-3 py-4 backdrop-blur-sm sm:px-4 sm:py-6"
>
    <div
        id="reportConfigModalContent"
        class="flex w-full max-w-2xl scale-95 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white opacity-0 shadow-2xl transition duration-200 ease-out"
        style="max-height: calc(100dvh - 2rem);"
    >
        {{-- Header --}}
        <div class="flex shrink-0 items-center justify-between border-b border-slate-200 bg-white px-4 py-3 sm:px-5">
            <div>
                <h3 class="text-base font-bold text-slate-900 sm:text-lg">Configurar columnas</h3>
                <p id="reportConfigGroupName" class="mt-0.5 text-xs text-slate-500"></p>
            </div>
            <button
                type="button"
                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                onclick="closeReportConfigModal()"
            >✕</button>
        </div>

        {{-- Body --}}
        <div class="min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-5">

            {{-- Estado de carga --}}
            <div id="reportConfigLoading" class="flex items-center justify-center py-12 text-sm text-slate-400">
                Cargando configuración...
            </div>

            {{-- Contenido real --}}
            <div id="reportConfigBody" class="hidden space-y-4">

                {{-- Selector de rol --}}
                <div class="flex items-center gap-3">
                    <label class="shrink-0 text-sm font-semibold text-slate-700">Ver permisos de:</label>
                    <select
                        id="reportConfigRoleSelect"
                        class="rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                        onchange="renderReportConfigColumns()"
                    >
                        <option value="admin_cliente">Administrador cliente</option>
                        <option value="observador">Observador</option>
                        <option value="observador_cliente">Observador cliente</option>
                    </select>
                </div>

                {{-- Lista sortable --}}
                <div id="reportConfigColumnList" class="space-y-2"></div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="shrink-0 border-t border-slate-200 bg-slate-50 px-4 py-3 sm:px-5">
            <div id="reportConfigError" class="mb-3 hidden rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"></div>
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-between">
                <button
                    type="button"
                    id="reportConfigResetBtn"
                    onclick="resetReportConfig()"
                    class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 sm:w-auto"
                >
                    <i data-lucide="rotate-ccw" class="h-3.5 w-3.5"></i>
                    Restablecer predeterminados
                </button>
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:gap-3">
                    <button
                        type="button"
                        onclick="closeReportConfigModal()"
                        class="inline-flex w-full justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 sm:w-auto"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        id="reportConfigSaveBtn"
                        onclick="saveReportConfig()"
                        class="inline-flex w-full justify-center rounded-xl bg-[#d94d33] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#b83f29] sm:w-auto"
                    >
                        Guardar configuración
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="groupToastContainer" class="fixed bottom-5 right-5 z-[99999] space-y-3"></div>
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
        statuses: {
            type: 'checklist_object',
            title: 'Estado',
            inputName: 'statuses',
            options: @json($filterOptions['statuses']),
        },
    };

    const activeFilters = @json($activeFilters);
    let currentPopoverKey = null;
    let isGroupsListLoading = false;

    function handleSingleClientSelection(currentCheckbox) {
        document.querySelectorAll('.group-single-checkbox').forEach(checkbox => {
            if (checkbox !== currentCheckbox) {
                checkbox.checked = false;
            }
        });

        document.getElementById('selected_client_id').value = currentCheckbox.checked ? currentCheckbox.value : '';
    }

    function openEditGroupModal(button) {
        clearGroupAjaxErrors('editGroupAjaxErrors');

        document.getElementById('editGroupForm').action = button.dataset.action;
        document.getElementById('edit_name').value = button.dataset.name ?? '';
        document.getElementById('edit_description').value = button.dataset.description ?? '';

        const selectClient = document.getElementById('edit_client_id');
        const hiddenClient = document.getElementById('edit_client_id_hidden');

        if (selectClient) {
            selectClient.value = button.dataset.client_id ?? '';
        }

        if (hiddenClient) {
            hiddenClient.value = button.dataset.client_id ?? '';
        }

        const modal = document.getElementById('editGroupModal');
        const content = document.getElementById('editGroupModalContent');

        modal.classList.remove('hidden');
        modal.classList.add('flex');

        document.documentElement.classList.add('overflow-hidden');
        document.body.classList.add('overflow-hidden');

        setTimeout(() => {
            content?.classList.remove('scale-95', 'opacity-0');
            content?.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeEditGroupModal() {
        const modal = document.getElementById('editGroupModal');
        const content = document.getElementById('editGroupModalContent');

        clearGroupAjaxErrors('editGroupAjaxErrors');

        content?.classList.remove('scale-100', 'opacity-100');
        content?.classList.add('scale-95', 'opacity-0');

        setTimeout(() => {
            modal.classList.remove('flex');
            modal.classList.add('hidden');

            document.documentElement.classList.remove('overflow-hidden');
            document.body.classList.remove('overflow-hidden');
        }, 150);
    }

    function filterElementsChecklistByClient(clientId) {
        const groups = document.querySelectorAll('[data-element-type-group]');
        let visibleGroupCount = 0;

        groups.forEach(group => {
            const groupClientId = group.dataset.clientId ?? '';
            const visible = String(groupClientId) === String(clientId);

            group.classList.toggle('hidden', !visible);

            if (visible) {
                visibleGroupCount++;
            }

            group.querySelectorAll('[data-element-checkbox]').forEach(checkbox => {
                if (!visible) {
                    checkbox.checked = false;
                }
            });
        });

        document.getElementById('elementsEmptyState').classList.toggle('hidden', visibleGroupCount > 0);
    }

    function openElementsModal(groupId, clientId, groupName, groupDescription, actionUrl, selectedElementIds) {
        document.getElementById('elementsForm').action = actionUrl;
        document.getElementById('elements_group_name').textContent = groupName ?? '—';
        document.getElementById('elements_group_description').textContent = groupDescription ?? '—';

        filterElementsChecklistByClient(clientId);

        const selectedSet = new Set((selectedElementIds ?? []).map(String));

        document.querySelectorAll('[data-element-checkbox]').forEach((checkbox) => {
            const group = checkbox.closest('[data-element-type-group]');
            const hidden = group?.classList.contains('hidden');

            if (hidden) {
                checkbox.checked = false;
                return;
            }

            checkbox.checked = selectedSet.has(String(checkbox.value));
        });

        const modal = document.getElementById('elementsModal');
        const content = document.getElementById('elementsModalContent');

        modal.classList.remove('hidden');
        modal.classList.add('flex');

        document.documentElement.classList.add('overflow-hidden');
        document.body.classList.add('overflow-hidden');

        setTimeout(() => {
            content?.classList.remove('scale-95', 'opacity-0');
            content?.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeElementsModal() {
        const modal = document.getElementById('elementsModal');
        const content = document.getElementById('elementsModalContent');

        content?.classList.remove('scale-100', 'opacity-100');
        content?.classList.add('scale-95', 'opacity-0');

        setTimeout(() => {
            modal.classList.remove('flex');
            modal.classList.add('hidden');

            document.documentElement.classList.remove('overflow-hidden');
            document.body.classList.remove('overflow-hidden');
        }, 150);
    }

    function selectAllElementsByType(button) {
        const wrapper = button.closest('[data-element-type-group]');
        if (!wrapper || wrapper.classList.contains('hidden')) return;

        wrapper.querySelectorAll('[data-element-checkbox]').forEach(checkbox => {
            checkbox.checked = true;
        });
    }

    function clearAllElementsByType(button) {
        const wrapper = button.closest('[data-element-type-group]');
        if (!wrapper || wrapper.classList.contains('hidden')) return;

        wrapper.querySelectorAll('[data-element-checkbox]').forEach(checkbox => {
            checkbox.checked = false;
        });
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

    function updateGroupFilterOptions(options = {}) {
        if (filterOptions.client_ids && Array.isArray(options.client_ids)) {
            filterOptions.client_ids.options = options.client_ids;
        }

        if (filterOptions.names && Array.isArray(options.names)) {
            filterOptions.names.options = options.names;
        }

        if (filterOptions.statuses && Array.isArray(options.statuses)) {
            filterOptions.statuses.options = options.statuses;
        }
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
        loadGroupsList(1);
    }

    function buildGroupsQueryParams(page = 1) {
        const params = new URLSearchParams();

        Object.entries(activeFilters).forEach(([key, value]) => {
            if (Array.isArray(value)) {
                value
                    .filter(item => item !== null && item !== '')
                    .forEach(item => params.append(`${key}[]`, item));
            } else if (value !== null && value !== '') {
                params.set(key, value);
            }
        });

        params.set('page', String(page));

        return params;
    }

    async function loadGroupsList(page = 1, updateHistory = true) {
        const module = document.querySelector('[data-groups-index]');
        const url = module?.dataset.indexUrl;
        const container = document.getElementById('groupsListContainer');

        if (!url || !container || isGroupsListLoading) {
            return;
        }

        const params = buildGroupsQueryParams(page);
        isGroupsListLoading = true;
        container.classList.add('opacity-60', 'pointer-events-none');

        try {
            const response = await fetch(`${url}?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await parseGroupJsonResponse(response);

            if (!response.ok || data.success === false) {
                throw new Error(data.message || 'No fue posible cargar el listado de agrupaciones.');
            }

            container.outerHTML = data.list_html;
            updateGroupFilterOptions(data.filter_options || {});

            if (updateHistory) {
                window.history.pushState({ page }, '', `${url}?${params.toString()}`);
            }

            syncGroupRedirectInputs(page);

            const clearFiltersLink = document.querySelector('[data-clear-filters]');
            if (clearFiltersLink) {
                clearFiltersLink.classList.toggle('hidden', !data.has_any_active_filter);
            }

            if (window.lucide) {
                window.lucide.createIcons();
            }
        } catch (error) {
            showGroupToast(error.message || 'Ocurrió un error al cargar las agrupaciones.', 'error');
        } finally {
            const refreshedContainer = document.getElementById('groupsListContainer');
            refreshedContainer?.classList.remove('opacity-60', 'pointer-events-none');
            isGroupsListLoading = false;
        }
    }

    function currentGroupsPage() {
        const params = new URLSearchParams(window.location.search);
        return Number(params.get('page') || '1');
    }

    function syncGroupActiveFiltersFromLocation() {
        const params = new URLSearchParams(window.location.search);

        Object.keys(activeFilters).forEach(key => {
            activeFilters[key] = params.getAll(`${key}[]`);
        });
    }

    function syncGroupRedirectInputs(page = currentGroupsPage()) {
        document.querySelectorAll('input[name="redirect_page"]').forEach(input => {
            input.value = String(page);
        });
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
        const selectedClientId = document.getElementById('selected_client_id');
        if (selectedClientId && selectedClientId.value) {
            const checkbox = document.querySelector(`.group-single-checkbox[value="${selectedClientId.value}"]`);
            if (checkbox) {
                checkbox.checked = true;
            }
        }

        const createGroupForm = document.getElementById('createGroupForm');
        const editGroupForm = document.getElementById('editGroupForm');
        const elementsForm = document.getElementById('elementsForm');

        if (createGroupForm) {
            createGroupForm.addEventListener('submit', handleCreateGroupSubmit);
        }

        if (editGroupForm) {
            editGroupForm.addEventListener('submit', handleEditGroupSubmit);
        }

        if (elementsForm) {
            elementsForm.addEventListener('submit', handleElementsGroupSubmit);
        }

        syncGroupRedirectInputs();
    });

    document.addEventListener('click', function (event) {
        const popover = document.getElementById('filterPopover');
        const editModal = document.getElementById('editGroupModal');
        const elementsModal = document.getElementById('elementsModal');
        const paginationLink = event.target.closest('[data-pagination-link]');
        const clearFiltersLink = event.target.closest('[data-clear-filters]');

        if (paginationLink) {
            event.preventDefault();

            const href = paginationLink.getAttribute('href');
            if (!href || href === '#') {
                return;
            }

            const page = Number(new URL(href).searchParams.get('page') || '1');
            loadGroupsList(page);
            return;
        }

        if (clearFiltersLink) {
            event.preventDefault();
            Object.keys(activeFilters).forEach(key => {
                activeFilters[key] = [];
            });
            loadGroupsList(1);
            return;
        }

        if (!popover.classList.contains('hidden')) {
            if (!popover.contains(event.target) && !event.target.closest('button[onclick^="openFilterPopover"]')) {
                closeFilterPopover();
            }
        }

        if (editModal.classList.contains('flex') && event.target === editModal) {
            closeEditGroupModal();
        }

        if (elementsModal.classList.contains('flex') && event.target === elementsModal) {
            closeElementsModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeFilterPopover();
            closeEditGroupModal();
            closeElementsModal();
        }
    });

    window.addEventListener('popstate', function () {
        syncGroupActiveFiltersFromLocation();
        loadGroupsList(currentGroupsPage(), false);
    });

    function showGroupToast(message, type = 'success') {
        const container = document.getElementById('groupToastContainer');

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

    async function parseGroupJsonResponse(response) {
        const contentType = response.headers.get('content-type') || '';

        if (!contentType.includes('application/json')) {
            throw new Error('El servidor no devolvió JSON. Revisa sesión, permisos o respuesta del controlador.');
        }

        return await response.json();
    }

    async function toggleGroupSync(button) {
        const url = button.dataset.url;

        if (!url || button.disabled) {
            return;
        }

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

            const data = await parseGroupJsonResponse(response);

            if (!response.ok || data.success === false) {
                throw new Error(data.message || 'No fue posible cambiar la sincronización.');
            }

            renderGroupSyncButton(button, Boolean(data.auto_sync));
            showGroupToast(data.message || 'Sincronización actualizada correctamente.', 'success');
        } catch (error) {
            button.innerHTML = originalHtml;
            button.className = originalClass;
            showGroupToast(error.message || 'Ocurrió un error al cambiar la sincronización.', 'error');
        } finally {
            button.disabled = false;
            button.classList.remove('opacity-60', 'cursor-wait');

            if (window.lucide) {
                window.lucide.createIcons();
            }
        }
    }

    function renderGroupSyncButton(button, enabled) {
        button.dataset.enabled = enabled ? '1' : '0';

        button.classList.remove(
            'bg-emerald-100',
            'text-emerald-700',
            'hover:bg-emerald-200',
            'bg-slate-100',
            'text-slate-500',
            'hover:bg-slate-200'
        );

        if (enabled) {
            button.classList.add('bg-emerald-100', 'text-emerald-700', 'hover:bg-emerald-200');
        } else {
            button.classList.add('bg-slate-100', 'text-slate-500', 'hover:bg-slate-200');
        }

        button.innerHTML = `
            <i data-lucide="${enabled ? 'refresh-cw-check' : 'refresh-cw-off'}" class="h-3.5 w-3.5"></i>
            <span>${enabled ? 'ON' : 'OFF'}</span>
        `;

        if (window.lucide) {
            window.lucide.createIcons();
        }
    }

    function clearGroupAjaxErrors(containerId) {
    const box = document.getElementById(containerId);
    if (!box) return;

    box.classList.add('hidden');
    box.innerHTML = '';
}

function renderGroupAjaxErrors(containerId, errors) {
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

function setGroupFormSubmittingState(form, isSubmitting, loadingText = 'Guardando...') {
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

async function handleCreateGroupSubmit(event) {
    event.preventDefault();

    const form = event.currentTarget;
    clearGroupAjaxErrors('createGroupAjaxErrors');
    setGroupFormSubmittingState(form, true, 'Guardando...');

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

        const data = await parseGroupJsonResponse(response);

        if (response.status === 422) {
            renderGroupAjaxErrors('createGroupAjaxErrors', data.errors || {});
            showGroupToast(data.message || 'Corrige los errores del formulario.', 'error');
            return;
        }

        if (!response.ok || data.success === false) {
            throw new Error(data.message || 'No fue posible crear la agrupación.');
        }

        resetCreateGroupForm();
        showGroupToast(data.message || 'Agrupación creada correctamente.', 'success');
        loadGroupsList(currentGroupsPage(), false);
    } catch (error) {
        showGroupToast(error.message || 'Ocurrió un error al crear la agrupación.', 'error');
    } finally {
        setGroupFormSubmittingState(form, false);
    }
}

async function handleEditGroupSubmit(event) {
    event.preventDefault();

    const form = event.currentTarget;
    clearGroupAjaxErrors('editGroupAjaxErrors');
    setGroupFormSubmittingState(form, true, 'Actualizando...');

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

        const data = await parseGroupJsonResponse(response);

        if (response.status === 422) {
            renderGroupAjaxErrors('editGroupAjaxErrors', data.errors || {});
            showGroupToast(data.message || 'Corrige los errores del formulario.', 'error');
            return;
        }

        if (!response.ok || data.success === false) {
            throw new Error(data.message || 'No fue posible actualizar la agrupación.');
        }

        closeEditGroupModal();
        showGroupToast(data.message || 'Agrupación actualizada correctamente.', 'success');
        loadGroupsList(currentGroupsPage(), false);
    } catch (error) {
        showGroupToast(error.message || 'Ocurrió un error al actualizar la agrupación.', 'error');
    } finally {
        setGroupFormSubmittingState(form, false);
    }
}

async function handleElementsGroupSubmit(event) {
    event.preventDefault();

    const form = event.currentTarget;
    setGroupFormSubmittingState(form, true, 'Guardando...');

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

        const data = await parseGroupJsonResponse(response);

        if (!response.ok || data.success === false) {
            throw new Error(data.message || 'No fue posible actualizar los activos.');
        }

        updateGroupRow(data.group);
        closeElementsModal();

        showGroupToast(data.message || 'Activos actualizados correctamente.', 'success');
    } catch (error) {
        showGroupToast(error.message || 'Ocurrió un error al actualizar los activos.', 'error');
    } finally {
        setGroupFormSubmittingState(form, false);
    }
}

async function deleteGroup(groupId) {
    const confirmed = confirm('¿Seguro que deseas eliminar esta agrupación?');

    if (!confirmed) return;

    const row = document.getElementById(`group-row-${groupId}`);
    const form = document.getElementById(`delete-group-form-${groupId}`);

    if (!form) {
        showGroupToast('No se encontró el formulario de eliminación.', 'error');
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

        const data = await parseGroupJsonResponse(response);

        if (!response.ok || data.success === false) {
            throw new Error(data.message || 'No fue posible eliminar la agrupación.');
        }

        showGroupToast(data.message || 'Agrupación eliminada correctamente.', 'success');
        loadGroupsList(currentGroupsPage(), false);
    } catch (error) {
        if (row) {
            row.classList.remove('opacity-60', 'pointer-events-none');
        }

        showGroupToast(error.message || 'Ocurrió un error al eliminar la agrupación.', 'error');
    }
}

async function toggleGroupStatus(button) {
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

        const data = await parseGroupJsonResponse(response);

        if (!response.ok || data.success === false) {
            throw new Error(data.message || 'No fue posible cambiar el estado.');
        }

        showGroupToast(data.message || 'Estado actualizado correctamente.', 'success');
        loadGroupsList(currentGroupsPage(), false);
    } catch (error) {
        button.innerHTML = originalHtml;
        button.className = originalClass;
        showGroupToast(error.message || 'Ocurrió un error al cambiar el estado.', 'error');
    } finally {
        button.disabled = false;
        button.classList.remove('opacity-60', 'cursor-wait');

        if (window.lucide) {
            window.lucide.createIcons();
        }
    }
}

function renderGroupStatusButton(button, enabled) {
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
}

function resetCreateGroupForm() {
    const form = document.getElementById('createGroupForm');
    if (!form) return;

    form.reset();
    clearGroupAjaxErrors('createGroupAjaxErrors');

    const selectedClient = document.getElementById('selected_client_id');

    if (selectedClient && !@json((bool) $singleClient)) {
        selectedClient.value = '';
    }

    if (!@json((bool) $singleClient)) {
        document.querySelectorAll('.group-single-checkbox').forEach(cb => cb.checked = false);
    }
}

// =====================================================
// MODAL: CONFIGURACIÓN DE COLUMNAS POR AGRUPACIÓN
// =====================================================

let reportConfigColumns = [];
let reportConfigSaveUrl = '';
let reportConfigResetUrl = '';
let reportConfigSortable = null;

function openReportConfigModal(groupId, groupName, loadUrl, saveUrl, resetUrl) {
    reportConfigSaveUrl  = saveUrl;
    reportConfigResetUrl = resetUrl;

    document.getElementById('reportConfigGroupName').textContent = groupName;
    document.getElementById('reportConfigLoading').classList.remove('hidden');
    document.getElementById('reportConfigBody').classList.add('hidden');
    document.getElementById('reportConfigError').classList.add('hidden');

    const modal   = document.getElementById('reportConfigModal');
    const content = document.getElementById('reportConfigModalContent');

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.documentElement.classList.add('overflow-hidden');
    document.body.classList.add('overflow-hidden');

    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);

    fetch(loadUrl, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(r => r.json())
        .then(data => {
            if (!data.success) throw new Error(data.message || 'Error al cargar la configuración.');
            reportConfigColumns = data.columns;
            document.getElementById('reportConfigLoading').classList.add('hidden');
            document.getElementById('reportConfigBody').classList.remove('hidden');
            renderReportConfigColumns();
            if (window.lucide) window.lucide.createIcons();
        })
        .catch(err => {
            document.getElementById('reportConfigLoading').textContent = err.message || 'No se pudo cargar la configuración.';
        });
}

function closeReportConfigModal() {
    const modal   = document.getElementById('reportConfigModal');
    const content = document.getElementById('reportConfigModalContent');

    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');

    setTimeout(() => {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
        document.documentElement.classList.remove('overflow-hidden');
        document.body.classList.remove('overflow-hidden');
        if (reportConfigSortable) { reportConfigSortable.destroy(); reportConfigSortable = null; }
    }, 150);
}

function renderReportConfigColumns() {
    const roleKey = document.getElementById('reportConfigRoleSelect').value;
    const list    = document.getElementById('reportConfigColumnList');

    const ALWAYS_VISIBLE_KEYS = ['area', 'element_name', 'week'];

    list.innerHTML = reportConfigColumns.map((col, idx) => {
        const isAlwaysVisible = ALWAYS_VISIBLE_KEYS.includes(col.column_key);
        const isEditable = ['recommendation', 'recommendation_2', 'orden', 'aviso', 'execution_date'].includes(col.column_key);
        const canEdit    = col[`can_edit_${roleKey}`] ?? false;

        const togglesHtml = isAlwaysVisible
            ? `<div class="flex shrink-0 items-center gap-4">
                <span class="flex items-center gap-1.5 text-xs text-slate-300 w-16" title="Esta columna siempre es visible">
                    <input type="checkbox" class="h-4 w-4 rounded border-slate-200 cursor-not-allowed" checked disabled>
                    <span>Visible</span>
                </span>
                <span class="w-16 invisible"></span>
               </div>`
            : `<div class="flex shrink-0 items-center gap-4">
                <label class="flex cursor-pointer items-center gap-1.5 text-xs text-slate-500 w-16" title="Mostrar columna">
                    <input
                        type="checkbox"
                        class="rc-visible h-4 w-4 rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                        ${col.visible ? 'checked' : ''}
                        onchange="updateReportConfigColumn('${escapeHtml(col.column_key)}', 'visible', this.checked)"
                    >
                    <span>Visible</span>
                </label>
                <label class="flex cursor-pointer items-center gap-1.5 text-xs text-slate-500 w-16 ${isEditable ? '' : 'invisible'}"
                       title="Permitir edición a ${roleKey.replace('_', ' ')}">
                    <input
                        type="checkbox"
                        class="rc-editable h-4 w-4 rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                        ${canEdit ? 'checked' : ''}
                        ${isEditable ? `onchange="updateReportConfigColumn('${escapeHtml(col.column_key)}', 'can_edit_${roleKey}', this.checked)"` : ''}
                    >
                    <span>Editable</span>
                </label>
               </div>`;

        return `
        <div class="report-config-row flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2.5 shadow-sm"
             data-key="${escapeHtml(col.column_key)}"
             data-idx="${idx}">
            <span class="drag-handle cursor-grab touch-none text-slate-300 hover:text-slate-500 active:cursor-grabbing">
                <i data-lucide="grip-vertical" class="h-5 w-5 pointer-events-none"></i>
            </span>
            <span class="flex-1 text-sm font-medium text-slate-800">${escapeHtml(col.label)}</span>
            ${togglesHtml}
        </div>`;
    }).join('');

    if (window.lucide) window.lucide.createIcons();

    if (reportConfigSortable) reportConfigSortable.destroy();

    if (window.Sortable) {
        reportConfigSortable = new Sortable(list, {
            animation: 150,
            handle: '.drag-handle',
            ghostClass: 'opacity-40',
            onEnd() {
                const newOrder = [];
                list.querySelectorAll('.report-config-row').forEach(row => {
                    const key = row.dataset.key;
                    const col = reportConfigColumns.find(c => c.column_key === key);
                    if (col) newOrder.push(col);
                });
                reportConfigColumns = newOrder;
            },
        });
    }
}

function updateReportConfigColumn(key, field, value) {
    const col = reportConfigColumns.find(c => c.column_key === key);
    if (col) col[field] = value;
}

async function saveReportConfig() {
    const btn = document.getElementById('reportConfigSaveBtn');
    const errBox = document.getElementById('reportConfigError');
    errBox.classList.add('hidden');

    btn.disabled = true;

    try {
        const response = await fetch(reportConfigSaveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({ columns: reportConfigColumns }),
        });

        const data = await response.json();

        if (!response.ok || data.success === false) {
            throw new Error(data.message || 'No fue posible guardar la configuración.');
        }

        showGroupToast(data.message || 'Configuración guardada correctamente.', 'success');
    } catch (err) {
        errBox.textContent = err.message || 'Error al guardar.';
        errBox.classList.remove('hidden');
    } finally {
        btn.disabled = false;
    }
}

async function resetReportConfig() {
    if (!confirm('¿Restablecer la configuración predeterminada para esta agrupación?')) return;

    const btn = document.getElementById('reportConfigResetBtn');
    btn.disabled = true;

    try {
        const response = await fetch(reportConfigResetUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: new URLSearchParams({ _method: 'DELETE' }),
        });

        const data = await response.json();

        if (!response.ok || data.success === false) {
            throw new Error(data.message || 'No fue posible restablecer la configuración.');
        }

        reportConfigColumns = data.columns;
        renderReportConfigColumns();
        showGroupToast(data.message || 'Configuración restablecida.', 'success');
    } catch (err) {
        showGroupToast(err.message || 'Error al restablecer.', 'error');
    } finally {
        btn.disabled = false;
    }
}

// Cerrar modal al hacer clic fuera
document.addEventListener('click', function(event) {
    const configModal = document.getElementById('reportConfigModal');
    if (configModal?.classList.contains('flex') && event.target === configModal) {
        closeReportConfigModal();
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeReportConfigModal();
    }
});

function updateGroupRow(group) {
    if (!group || !group.id) return;

    const row = document.getElementById(`group-row-${group.id}`);
    const nameEl = document.getElementById(`group-name-${group.id}`);
    const clientEl = document.getElementById(`group-client-${group.id}`);
    const descriptionEl = document.getElementById(`group-description-${group.id}`);
    const elementsCountEl = document.getElementById(`group-elements-count-${group.id}`);
    const editButton = row?.querySelector('[data-edit-group]');
    const syncButton = row?.querySelector('[data-sync-toggle]');
    const statusButton = row?.querySelector('[data-status-toggle]');

    if (nameEl) nameEl.textContent = group.name ?? '—';
    if (clientEl) clientEl.textContent = group.client_name ?? '—';
    if (descriptionEl) descriptionEl.textContent = group.description_label ?? '—';
    if (elementsCountEl) elementsCountEl.textContent = String(group.elements_count ?? 0);

    if (editButton) {
        editButton.dataset.client_id = group.client_id ?? '';
        editButton.dataset.name = group.name ?? '';
        editButton.dataset.description = group.description ?? '';
        editButton.dataset.action = group.update_url ?? '';
    }

    if (syncButton) {
        syncButton.dataset.url = group.toggle_sync_url ?? syncButton.dataset.url;
        renderGroupSyncButton(syncButton, Boolean(group.auto_sync));
    }

    if (statusButton) {
        statusButton.dataset.url = group.toggle_status_url ?? statusButton.dataset.url;
        renderGroupStatusButton(statusButton, Boolean(group.status));
    }

    if (window.lucide) {
        window.lucide.createIcons();
    }
}
</script>
@endsection
