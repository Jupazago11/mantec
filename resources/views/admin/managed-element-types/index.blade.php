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
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Nuevo tipo de activo</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Registra un tipo de activo para uno de tus clientes.
                    </p>
                    <div id="createElementTypeAjaxErrors" class="mt-4 hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>
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
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <div class="flex items-center justify-between gap-4">
                            <h3 class="text-lg font-semibold text-slate-900">Listado de tipos de activos</h3>

                            @if($hasAnyActiveFilter)
                                <a
                                    href="{{ route('admin.managed-element-types.index') }}"
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
                                        Semáforo
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
                            <tbody id="elementTypesTableBody" class="divide-y divide-slate-200 bg-white">
                                @forelse($elementTypes as $elementType)
                                    @php
                                        $hasDependencies = (($elementType->components_count ?? 0) + ($elementType->elements_count ?? 0)) > 0;
                                    @endphp

                                    <tr class="hover:bg-slate-50" id="element-type-row-{{ $elementType->id }}">
                                        @if($showClientColumn)
                                        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700" id="element-type-client-{{ $elementType->id }}">
                                            {{ $elementType->client?->name ?? '—' }}
                                        </td>
                                        @endif

                                        <td class="whitespace-nowrap px-5 py-3 text-sm font-medium text-slate-900" id="element-type-name-{{ $elementType->id }}">
                                            {{ $elementType->name }}
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-3 text-sm">
                                            <button
                                                type="button"
                                                data-semaphore-toggle
                                                data-url="{{ route('admin.managed-element-types.toggle-semaphore', $elementType) }}"
                                                data-enabled="{{ $elementType->has_semaphore ? '1' : '0' }}"
                                                onclick="toggleElementTypeSemaphore(this)"
                                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition {{ $elementType->has_semaphore ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}"
                                                title="Clic para activar o desactivar semáforo semanal"
                                            >
                                                <i data-lucide="calendar-days" class="h-3.5 w-3.5"></i>
                                                <span>{{ $elementType->has_semaphore ? 'Sí' : 'No' }}</span>
                                            </button>
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-3 text-sm">
                                            <button
                                                type="button"
                                                data-status-toggle
                                                data-url="{{ route('admin.managed-element-types.toggle-status', $elementType) }}"
                                                data-enabled="{{ $elementType->status ? '1' : '0' }}"
                                                onclick="toggleElementTypeStatus(this)"
                                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition {{ $elementType->status ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-red-100 text-red-700 hover:bg-red-200' }}"
                                                title="Clic para activar o inactivar"
                                            >
                                                <i data-lucide="{{ $elementType->status ? 'check-circle-2' : 'x-circle' }}" class="h-3.5 w-3.5"></i>
                                                <span>{{ $elementType->status ? 'Activo' : 'Inactivo' }}</span>
                                            </button>
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-3 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <button
                                                    type="button"
                                                    data-edit-element-type
                                                    data-id="{{ $elementType->id }}"
                                                    data-client-id="{{ $elementType->client_id }}"
                                                    data-name="{{ $elementType->name }}"
                                                    data-has-semaphore="{{ $elementType->has_semaphore ? '1' : '0' }}"
                                                    data-status="{{ $elementType->status ? '1' : '0' }}"
                                                    data-action="{{ route('admin.managed-element-types.update', $elementType) }}"
                                                    onclick="openEditElementTypeModalFromButton(this)"
                                                    class="text-slate-400 transition hover:text-[#d94d33]"
                                                    title="Editar tipo de activo"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M16.862 4.487l1.651-1.651a2.121 2.121 0 113 3l-1.651 1.651M4 20h4l10.586-10.586a2 2 0 00-2.828-2.828L5.172 17.172A2 2 0 004 18.586V20z" />
                                                    </svg>
                                                </button>

                                                @if(!$hasDependencies)
                                                    <button
                                                        type="button"
                                                        onclick="deleteElementType({{ $elementType->id }})"
                                                        class="text-red-500 transition hover:text-red-700"
                                                        title="Eliminar tipo de activo"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M6 7h12M9 7V4h6v3M10 11v6M14 11v6M5 7l1 13a2 2 0 002 2h8a2 2 0 002-2l1-13" />
                                                        </svg>
                                                    </button>

                                                    <form
                                                        id="delete-element-type-form-{{ $elementType->id }}"
                                                        method="POST"
                                                        action="{{ route('admin.managed-element-types.destroy', $elementType) }}"
                                                        class="hidden"
                                                    >
                                                        @csrf
                                                        @method('DELETE')

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
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $showClientColumn ? 5 : 4 }}" class="px-5 py-10 text-center text-sm text-slate-500">
                                            No hay tipos de activos registrados todavía.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($elementTypes->hasPages())
                        <div class="border-t border-slate-200 px-6 py-4">
                            {{ $elementTypes->links() }}
                        </div>
                    @endif
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
                    <div id="editElementTypeAjaxErrors" class="mb-3 hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

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

    async function toggleElementTypeSemaphore(button) {
        const url = button.dataset.url;

        if (!url || button.disabled) {
            return;
        }

        const originalHtml = button.innerHTML;
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
                button.innerHTML = originalHtml;
                return;
            }

            renderSemaphoreButton(button, Boolean(data.has_semaphore));
            showElementTypeToast(data.message || 'Estado del semáforo actualizado.', 'success');
        } catch (error) {
            button.innerHTML = originalHtml;
            showElementTypeToast('Ocurrió un error de red al actualizar el semáforo.', 'error');
        } finally {
            button.disabled = false;
            button.classList.remove('opacity-60', 'cursor-wait');

            if (window.lucide) {
                window.lucide.createIcons();
            }
        }
    }

    function renderSemaphoreButton(button, enabled) {
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
            <i data-lucide="calendar-days" class="h-3.5 w-3.5"></i>
            <span>${enabled ? 'Sí' : 'No'}</span>
        `;

        const row = button.closest('tr');
        const editButton = row?.querySelector('[data-edit-element-type]');

        if (editButton) {
            editButton.dataset.hasSemaphore = enabled ? '1' : '0';
        }
    }

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

    async function toggleElementTypeStatus(button) {
        const url = button.dataset.url;

        if (!url || button.disabled) {
            return;
        }

        const originalHtml = button.innerHTML;

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
                button.innerHTML = originalHtml;
                showElementTypeToast(data.message || 'No fue posible cambiar el estado.', 'error');
                return;
            }

            renderElementTypeStatusButton(button, Boolean(data.status));
            showElementTypeToast(data.message || 'Estado actualizado correctamente.', 'success');
        } catch (error) {
            button.innerHTML = originalHtml;
            showElementTypeToast('Ocurrió un error de red al actualizar el estado.', 'error');
        } finally {
            button.disabled = false;
            button.classList.remove('opacity-60', 'cursor-wait');

            if (window.lucide) {
                window.lucide.createIcons();
            }
        }
    }

    function renderElementTypeStatusButton(button, enabled) {
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

        const row = button.closest('tr');
        const editButton = row?.querySelector('[data-edit-element-type]');

        if (editButton) {
            editButton.dataset.status = enabled ? '1' : '0';
        }
    }

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

    document.addEventListener('DOMContentLoaded', function () {
        const selectedClient = document.getElementById('selected_client_id');

        if (selectedClient && selectedClient.value) {
            document.querySelectorAll('.client-single-checkbox').forEach(cb => {
                cb.checked = parseInt(cb.value) === parseInt(selectedClient.value);
            });
        }

        const input = document.getElementById('element_type_name');

        if (!input) return;

        input.addEventListener('input', function () {
            let value = this.value;

            if (value.length === 0) return;

            this.value = value.charAt(0).toUpperCase() + value.slice(1);
        });

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
        const popover = document.getElementById('filterPopover');
        const modal = document.getElementById('editElementTypeModal');

        if (!popover.classList.contains('hidden')) {
            if (!popover.contains(event.target) && !event.target.closest('button[onclick^="openFilterPopover"]')) {
                closeFilterPopover();
            }
        }

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
                renderElementTypeAjaxErrors('createElementTypeAjaxErrors', data.errors || {});
                showElementTypeToast(data.message || 'Corrige los errores del formulario.', 'error');
                return;
            }

            if (!response.ok || data.success === false) {
                throw new Error(data.message || 'No fue posible crear el tipo de activo.');
            }

            insertElementTypeRow(data.element_type);
            resetCreateElementTypeForm();

            showElementTypeToast(data.message || 'Tipo de activo creado correctamente.', 'success');
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

            updateElementTypeRow(data.element_type);
            closeEditElementTypeModal();

            showElementTypeToast(data.message || 'Tipo de activo actualizado correctamente.', 'success');
        } catch (error) {
            showElementTypeToast(error.message || 'Ocurrió un error al actualizar el tipo de activo.', 'error');
        } finally {
            setElementTypeFormSubmittingState(form, false);
        }
    }

    async function deleteElementType(elementTypeId) {
        const confirmed = confirm('¿Seguro que deseas eliminar este tipo de activo?');

        if (!confirmed) return;

        const row = document.getElementById(`element-type-row-${elementTypeId}`);
        const form = document.getElementById(`delete-element-type-form-${elementTypeId}`);

        if (!form) {
            showElementTypeToast('No se encontró el formulario de eliminación.', 'error');
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

            const data = await parseElementTypeJsonResponse(response);

            if (!response.ok || data.success === false) {
                throw new Error(data.message || 'No fue posible eliminar el tipo de activo.');
            }

            if (row) {
                row.style.transition = 'opacity 180ms ease, transform 180ms ease';
                row.style.opacity = '0';
                row.style.transform = 'scale(0.98)';

                setTimeout(() => row.remove(), 180);
            }

            showElementTypeToast(data.message || 'Tipo de activo eliminado correctamente.', 'success');
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

        form.reset();
        clearElementTypeAjaxErrors('createElementTypeAjaxErrors');

        const selectedClientInput = document.getElementById('selected_client_id');

        if (selectedClientInput) {
            selectedClientInput.value = '';
        }

        document.querySelectorAll('.client-single-checkbox').forEach(cb => {
            cb.checked = false;
        });
    }

    function updateElementTypeRow(elementType) {
        if (!elementType || !elementType.id) return;

        const row = document.getElementById(`element-type-row-${elementType.id}`);
        const nameEl = document.getElementById(`element-type-name-${elementType.id}`);
        const clientEl = document.getElementById(`element-type-client-${elementType.id}`);
        const editButton = row?.querySelector('[data-edit-element-type]');
        const semaphoreButton = row?.querySelector('[data-semaphore-toggle]');
        const statusButton = row?.querySelector('[data-status-toggle]');

        if (nameEl) {
            nameEl.textContent = elementType.name ?? '—';
        }

        if (clientEl) {
            clientEl.textContent = elementType.client_name ?? '—';
        }

        if (editButton) {
            editButton.dataset.clientId = elementType.client_id ?? '';
            editButton.dataset.name = elementType.name ?? '';
            editButton.dataset.hasSemaphore = elementType.has_semaphore ? '1' : '0';
            editButton.dataset.status = elementType.status ? '1' : '0';
            editButton.dataset.action = elementType.update_url ?? '';
        }

        if (semaphoreButton) {
            semaphoreButton.dataset.url = elementType.toggle_semaphore_url ?? semaphoreButton.dataset.url;
            renderSemaphoreButton(semaphoreButton, Boolean(elementType.has_semaphore));
        }

        if (statusButton) {
            statusButton.dataset.url = elementType.toggle_status_url ?? statusButton.dataset.url;
            renderElementTypeStatusButton(statusButton, Boolean(elementType.status));
        }

        if (window.lucide) {
            window.lucide.createIcons();
        }
    }

    function insertElementTypeRow(elementType) {
        if (!elementType || !elementType.id) return;

        const tbody = document.getElementById('elementTypesTableBody');

        if (!tbody) return;

        const emptyRow = tbody.querySelector('td[colspan]');
        if (emptyRow) {
            emptyRow.closest('tr')?.remove();
        }

        const hasClientColumn = @json($showClientColumn);
        const canDelete = !((Number(elementType.components_count || 0) + Number(elementType.elements_count || 0)) > 0);

        const row = document.createElement('tr');
        row.id = `element-type-row-${elementType.id}`;
        row.className = 'hover:bg-slate-50';

        row.innerHTML = `
            ${hasClientColumn ? `
                <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700" id="element-type-client-${elementType.id}">
                    ${escapeHtml(elementType.client_name ?? '—')}
                </td>
            ` : ''}

            <td class="whitespace-nowrap px-5 py-3 text-sm font-medium text-slate-900" id="element-type-name-${elementType.id}">
                ${escapeHtml(elementType.name ?? '—')}
            </td>

            <td class="whitespace-nowrap px-5 py-3 text-sm">
                <button
                    type="button"
                    data-semaphore-toggle
                    data-url="${escapeHtml(elementType.toggle_semaphore_url ?? '')}"
                    data-enabled="${elementType.has_semaphore ? '1' : '0'}"
                    onclick="toggleElementTypeSemaphore(this)"
                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition ${elementType.has_semaphore
                        ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200'
                        : 'bg-slate-100 text-slate-500 hover:bg-slate-200'}"
                    title="Clic para activar o desactivar semáforo semanal"
                >
                    <i data-lucide="calendar-days" class="h-3.5 w-3.5"></i>
                    <span>${elementType.has_semaphore ? 'Sí' : 'No'}</span>
                </button>
            </td>

            <td class="whitespace-nowrap px-5 py-3 text-sm">
                <button
                    type="button"
                    data-status-toggle
                    data-url="${escapeHtml(elementType.toggle_status_url ?? '')}"
                    data-enabled="${elementType.status ? '1' : '0'}"
                    onclick="toggleElementTypeStatus(this)"
                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition ${elementType.status
                        ? 'bg-green-100 text-green-700 hover:bg-green-200'
                        : 'bg-red-100 text-red-700 hover:bg-red-200'}"
                    title="Clic para activar o inactivar"
                >
                    <i data-lucide="${elementType.status ? 'check-circle-2' : 'x-circle'}" class="h-3.5 w-3.5"></i>
                    <span>${elementType.status ? 'Activo' : 'Inactivo'}</span>
                </button>
            </td>

            <td class="whitespace-nowrap px-5 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                    <button
                        type="button"
                        data-edit-element-type
                        data-id="${escapeHtml(String(elementType.id))}"
                        data-client-id="${escapeHtml(String(elementType.client_id ?? ''))}"
                        data-name="${escapeHtml(elementType.name ?? '')}"
                        data-has-semaphore="${elementType.has_semaphore ? '1' : '0'}"
                        data-status="${elementType.status ? '1' : '0'}"
                        data-action="${escapeHtml(elementType.update_url ?? '')}"
                        onclick="openEditElementTypeModalFromButton(this)"
                        class="text-slate-400 transition hover:text-[#d94d33]"
                        title="Editar tipo de activo"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16.862 4.487l1.651-1.651a2.121 2.121 0 113 3l-1.651 1.651M4 20h4l10.586-10.586a2 2 0 00-2.828-2.828L5.172 17.172A2 2 0 004 18.586V20z" />
                        </svg>
                    </button>

                    ${canDelete ? `
                        <button
                            type="button"
                            onclick="deleteElementType(${elementType.id})"
                            class="text-red-500 transition hover:text-red-700"
                            title="Eliminar tipo de activo"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6 7h12M9 7V4h6v3M10 11v6M14 11v6M5 7l1 13a2 2 0 002 2h8a2 2 0 002-2l1-13" />
                            </svg>
                        </button>

                        <form
                            id="delete-element-type-form-${elementType.id}"
                            method="POST"
                            action="${escapeHtml(elementType.destroy_url ?? '')}"
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
</script>
@endsection
