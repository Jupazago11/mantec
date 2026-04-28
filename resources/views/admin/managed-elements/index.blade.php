@extends('layouts.admin')
@section('title', 'Activos')
@section('header_title', 'Activos')

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

        $singleClientId = $clients->count() === 1 ? $clients->first()?->id : null;
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

        <div class="grid gap-8 xl:grid-cols-[320px_minmax(0,1fr)]">
            {{-- FORMULARIO --}}
            <div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Nuevo activo</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Registra un nuevo activo para uno de tus clientes.
                    </p>
                    <div id="createElementAjaxErrors" class="mt-4 hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>
                    <form
                        id="createElementForm"
                        method="POST"
                        action="{{ route('admin.managed-elements.store') }}"
                        class="mt-6 space-y-5"
                    >
                        @csrf

                        @if($showClientColumn)
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Cliente</label>
                                <div class="space-y-2 rounded-xl border border-slate-300 p-4">
                                    @foreach($clients as $client)
                                        <label class="flex items-center gap-3 text-sm text-slate-700">
                                            <input
                                                type="checkbox"
                                                name="client_id_checkbox"
                                                value="{{ $client->id }}"
                                                class="client-single-checkbox rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                                                {{ (string) old('client_id') === (string) $client->id ? 'checked' : '' }}
                                                onchange="handleSingleClientSelection(this)"
                                            >
                                            <span>{{ $client->name }}</span>
                                        </label>
                                    @endforeach
                                </div>

                                <input type="hidden" name="client_id" id="selected_client_id" value="{{ old('client_id') }}">

                                @error('client_id')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @else
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Cliente</label>
                                <input
                                    type="text"
                                    value="{{ $clients->first()?->name }}"
                                    disabled
                                    class="w-full rounded-xl border border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-700"
                                >
                                <input type="hidden" name="client_id" value="{{ $clients->first()?->id }}">
                            </div>
                        @endif

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Área</label>
                            <select
                                name="area_id"
                                id="create_area_id"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            >
                                <option value="">Seleccione un área</option>
                                @foreach($areas as $area)
                                    <option
                                        value="{{ $area->id }}"
                                        data-client-id="{{ $area->client_id }}"
                                        @selected(old('area_id') == $area->id)
                                    >
                                        @if($showClientColumn)
                                            {{ $area->client?->name }} - {{ $area->name }}
                                        @else
                                            {{ $area->name }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>

                            @error('area_id')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Tipo de activo</label>
                            <select
                                name="element_type_id"
                                id="create_element_type_id"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            >
                                <option value="">Seleccione un tipo</option>
                                @foreach($elementTypes as $elementType)
                                    <option
                                        value="{{ $elementType->id }}"
                                        data-client-id="{{ $elementType->client_id }}"
                                        @selected(old('element_type_id') == $elementType->id)
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
                            <label class="mb-2 block text-sm font-medium text-slate-700">Nombre</label>
                            <input
                                type="text"
                                name="name"
                                id="create_element_name"
                                value="{{ old('name') }}"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                placeholder="Ej. Banda 001"
                            >
                            @error('name')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Código</label>
                            <input
                                type="text"
                                name="code"
                                id="create_element_code"
                                value="{{ old('code') }}"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                placeholder="Opcional"
                            >
                            @error('code')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Ubicación técnica</label>
                            <input
                                type="text"
                                name="warehouse_code"
                                id="create_element_warehouse_code"
                                value="{{ old('warehouse_code') }}"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                placeholder="Opcional"
                            >
                            @error('warehouse_code')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Mantener filtros --}}
                        @foreach(($activeFilters['client_ids'] ?? []) as $value)
                            <input type="hidden" name="redirect_client_ids[]" value="{{ $value }}">
                        @endforeach
                        @foreach(($activeFilters['area_ids'] ?? []) as $value)
                            <input type="hidden" name="redirect_area_ids[]" value="{{ $value }}">
                        @endforeach
                        @foreach(($activeFilters['element_type_ids'] ?? []) as $value)
                            <input type="hidden" name="redirect_element_type_ids[]" value="{{ $value }}">
                        @endforeach
                        @foreach(($activeFilters['names'] ?? []) as $value)
                            <input type="hidden" name="redirect_names[]" value="{{ $value }}">
                        @endforeach
                        @foreach(($activeFilters['warehouse_codes'] ?? []) as $value)
                            <input type="hidden" name="redirect_warehouse_codes[]" value="{{ $value }}">
                        @endforeach
                        @foreach(($activeFilters['statuses'] ?? []) as $value)
                            <input type="hidden" name="redirect_statuses[]" value="{{ $value }}">
                        @endforeach
                        <input type="hidden" name="redirect_page" value="{{ request('page', 1) }}">

                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                        >
                            Guardar activo
                        </button>
                    </form>
                </div>
            </div>

            {{-- LISTADO --}}
            <div>
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <div class="flex items-center justify-between gap-4">
                            <h3 class="text-lg font-semibold text-slate-900">Listado de activos</h3>

                            @if($hasAnyActiveFilter)
                                <a
                                    href="{{ route('admin.managed-elements.index') }}"
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
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
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

                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        <div class="flex items-center gap-2">
                                            <span>Área</span>
                                            <button
                                                type="button"
                                                onclick="openFilterPopover(event, 'area_ids')"
                                                class="rounded p-1 transition hover:bg-slate-200 {{ $hasFilter('area_ids') ? 'text-[#d94d33]' : 'text-slate-400' }}"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Agrupación
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        <div class="flex items-center gap-2">
                                            <span>Tipo</span>
                                            <button
                                                type="button"
                                                onclick="openFilterPopover(event, 'element_type_ids')"
                                                class="rounded p-1 transition hover:bg-slate-200 {{ $hasFilter('element_type_ids') ? 'text-[#d94d33]' : 'text-slate-400' }}"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
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

                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        <div class="flex items-center gap-2">
                                            <span>Almacén</span>
                                            <button
                                                type="button"
                                                onclick="openFilterPopover(event, 'warehouse_codes')"
                                                class="rounded p-1 transition hover:bg-slate-200 {{ $hasFilter('warehouse_codes') ? 'text-[#d94d33]' : 'text-slate-400' }}"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Comp.
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Uso
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
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

                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>

                            <tbody id="elementsTableBody" class="divide-y divide-slate-200 bg-white">
                                @forelse($elements as $element)
                                    @php
                                        $hasDependencies = (($element->components_count ?? 0) + ($element->report_details_count ?? 0)) > 0;
                                    @endphp

                                    <tr class="hover:bg-slate-50" id="element-row-{{ $element->id }}">
                                        @if($showClientColumn)
                                            <td class="px-4 py-3 text-sm text-slate-700" id="element-client-{{ $element->id }}">
                                                {{ $element->area?->client?->name ?? '—' }}
                                            </td>
                                        @endif

                                        <td class="px-4 py-3 text-sm text-slate-700" id="element-area-{{ $element->id }}">
                                            {{ $element->area?->name ?? '—' }}
                                        </td>

                                        <td class="px-4 py-3 text-sm text-slate-700" id="element-group-{{ $element->id }}">
                                            {{ $element->group?->name ?? '—' }}
                                        </td>

                                        <td class="px-4 py-3 text-sm text-slate-700" id="element-type-{{ $element->id }}">
                                            {{ $element->elementType?->name ?? '—' }}
                                        </td>

                                        <td class="px-4 py-3 text-sm font-medium text-slate-900" id="element-name-{{ $element->id }}">
                                            {{ $element->name }}
                                        </td>

                                        <td class="px-4 py-3 text-sm text-slate-700" id="element-warehouse-code-{{ $element->id }}">
                                            {{ $element->warehouse_code ?: '—' }}
                                        </td>

                                        <td class="px-4 py-3 text-sm text-slate-700" id="element-components-count-{{ $element->id }}">
                                            {{ $element->components_count ?? 0 }}
                                        </td>

                                        <td class="px-4 py-3 text-sm text-slate-700" id="element-report-details-count-{{ $element->id }}">
                                            {{ $element->report_details_count ?? 0 }}
                                        </td>

                                        <td class="px-4 py-3 text-sm">
                                            <button
                                                type="button"
                                                data-status-toggle
                                                data-url="{{ route('admin.managed-elements.toggle-status', $element) }}"
                                                data-enabled="{{ $element->status ? '1' : '0' }}"
                                                onclick="toggleElementStatus(this)"
                                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition {{ $element->status ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-red-100 text-red-700 hover:bg-red-200' }}"
                                                title="Clic para activar o inactivar"
                                            >
                                                <i data-lucide="{{ $element->status ? 'check-circle-2' : 'x-circle' }}" class="h-3.5 w-3.5"></i>
                                                <span>{{ $element->status ? 'Activo' : 'Inactivo' }}</span>
                                            </button>
                                        </td>

                                        <td class="px-4 py-3 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <button
                                                    type="button"
                                                    class="text-blue-500 transition hover:text-blue-700"
                                                    title="Asociar componentes"
                                                    data-components-element
                                                    data-id="{{ $element->id }}"
                                                    data-client_id="{{ $element->area?->client_id ?? '' }}"
                                                    data-element_type_id="{{ $element->element_type_id ?? '' }}"
                                                    data-area_name="{{ $element->area?->name ?? '—' }}"
                                                    data-type_name="{{ $element->elementType?->name ?? '—' }}"
                                                    data-name="{{ $element->name }}"
                                                    data-action="{{ route('admin.managed-elements.components.sync', $element) }}"
                                                    data-component_ids='@json($element->components->pluck('id')->map(fn($id) => (string) $id)->toArray())'
                                                    onclick="openComponentsModalFromButton(this)"
                                                >
                                                    <i data-lucide="boxes" class="h-4 w-4"></i>
                                                </button>

                                                <button
                                                    type="button"
                                                    data-edit-element
                                                    data-id="{{ $element->id }}"
                                                    data-name="{{ $element->name }}"
                                                    data-code="{{ $element->code }}"
                                                    data-warehouse_code="{{ $element->warehouse_code }}"
                                                    data-area_id="{{ $element->area_id }}"
                                                    data-element_type_id="{{ $element->element_type_id }}"
                                                    data-status="{{ $element->status ? 1 : 0 }}"
                                                    data-action="{{ route('admin.managed-elements.update', $element) }}"
                                                    onclick="openEditElementModalFromButton(this)"
                                                    class="text-slate-400 transition hover:text-[#d94d33]"
                                                    title="Editar activo"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M16.862 4.487l1.651-1.651a2.121 2.121 0 113 3l-1.651 1.651M4 20h4l10.586-10.586a2 2 0 00-2.828-2.828L5.172 17.172A2 2 0 004 18.586V20z" />
                                                    </svg>
                                                </button>

                                                @if(!$hasDependencies)
                                                    <button
                                                        type="button"
                                                        onclick="deleteElement({{ $element->id }})"
                                                        class="text-red-500 transition hover:text-red-700"
                                                        title="Eliminar activo"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M6 7h12M9 7V4h6v3M10 11v6M14 11v6M5 7l1 13a2 2 0 002 2h8a2 2 0 002-2l1-13" />
                                                        </svg>
                                                    </button>

                                                    <form
                                                        id="delete-element-form-{{ $element->id }}"
                                                        method="POST"
                                                        action="{{ route('admin.managed-elements.destroy', $element) }}"
                                                        class="hidden"
                                                    >
                                                        @csrf
                                                        @method('DELETE')

                                                        @foreach(($activeFilters['client_ids'] ?? []) as $value)
                                                            <input type="hidden" name="redirect_client_ids[]" value="{{ $value }}">
                                                        @endforeach
                                                        @foreach(($activeFilters['area_ids'] ?? []) as $value)
                                                            <input type="hidden" name="redirect_area_ids[]" value="{{ $value }}">
                                                        @endforeach
                                                        @foreach(($activeFilters['element_type_ids'] ?? []) as $value)
                                                            <input type="hidden" name="redirect_element_type_ids[]" value="{{ $value }}">
                                                        @endforeach
                                                        @foreach(($activeFilters['names'] ?? []) as $value)
                                                            <input type="hidden" name="redirect_names[]" value="{{ $value }}">
                                                        @endforeach
                                                        @foreach(($activeFilters['warehouse_codes'] ?? []) as $value)
                                                            <input type="hidden" name="redirect_warehouse_codes[]" value="{{ $value }}">
                                                        @endforeach
                                                        @foreach(($activeFilters['statuses'] ?? []) as $value)
                                                            <input type="hidden" name="redirect_statuses[]" value="{{ $value }}">
                                                        @endforeach
                                                        <input type="hidden" name="redirect_page" value="{{ $elements->currentPage() }}">
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $showClientColumn ? 10 : 9 }}" class="px-4 py-10 text-center text-sm text-slate-500">
                                            No hay activos registrados todavía.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if(method_exists($elements, 'links'))
                        <div class="border-t border-slate-200 px-6 py-4">
                            {{ $elements->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

{{-- MODAL EDITAR --}}
<div
    id="editElementModal"
    class="fixed left-0 top-0 z-[9999] hidden h-[100dvh] w-[100vw] items-center justify-center overflow-y-auto bg-slate-950/60 px-3 py-4 backdrop-blur-sm sm:px-4 sm:py-6"
>
    <div
        id="editElementModalContent"
        class="flex w-full max-w-2xl scale-95 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white opacity-0 shadow-2xl transition duration-200 ease-out"
        style="max-height: calc(100dvh - 2rem);"
    >
        <div class="flex shrink-0 items-center justify-between border-b border-slate-200 bg-white px-4 py-3 sm:px-5">
            <div>
                <h3 class="text-base font-bold text-slate-900 sm:text-lg">
                    Editar activo
                </h3>
                <p class="mt-0.5 hidden text-xs text-slate-500 sm:block">
                    Actualiza los datos del activo seleccionado.
                </p>
            </div>

            <button
                type="button"
                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                onclick="closeEditElementModal()"
                title="Cerrar"
            >
                ✕
            </button>
        </div>

        <form id="editElementForm" method="POST" class="flex min-h-0 flex-1 flex-col">
            @csrf
            @method('PUT')

            <div class="min-h-0 flex-1 overflow-y-auto px-4 py-3 sm:px-5 sm:py-4">
                <div id="editElementAjaxErrors" class="mb-3 hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Nombre</label>
                        <input
                            type="text"
                            name="name"
                            id="edit_element_name"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                        >
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Código</label>
                        <input
                            type="text"
                            name="code"
                            id="edit_element_code"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                        >
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Ubicación técnica</label>
                        <input
                            type="text"
                            name="warehouse_code"
                            id="edit_element_warehouse_code"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                        >
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Área</label>
                        <select
                            name="area_id"
                            id="edit_element_area_id"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                        >
                            @foreach($areas as $area)
                                <option value="{{ $area->id }}" data-client-id="{{ $area->client_id }}">
                                    @if($showClientColumn)
                                        {{ $area->client?->name }} - {{ $area->name }}
                                    @else
                                        {{ $area->name }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Tipo de activo</label>
                        <select
                            name="element_type_id"
                            id="edit_element_type_id"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                        >
                            @foreach($elementTypes as $elementType)
                                <option value="{{ $elementType->id }}" data-client-id="{{ $elementType->client_id }}">
                                    {{ $elementType->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Estado</label>
                        <select
                            name="status"
                            id="edit_element_status"
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
            @foreach(($activeFilters['area_ids'] ?? []) as $value)
                <input type="hidden" name="redirect_area_ids[]" value="{{ $value }}">
            @endforeach
            @foreach(($activeFilters['element_type_ids'] ?? []) as $value)
                <input type="hidden" name="redirect_element_type_ids[]" value="{{ $value }}">
            @endforeach
            @foreach(($activeFilters['names'] ?? []) as $value)
                <input type="hidden" name="redirect_names[]" value="{{ $value }}">
            @endforeach
            @foreach(($activeFilters['warehouse_codes'] ?? []) as $value)
                <input type="hidden" name="redirect_warehouse_codes[]" value="{{ $value }}">
            @endforeach
            @foreach(($activeFilters['statuses'] ?? []) as $value)
                <input type="hidden" name="redirect_statuses[]" value="{{ $value }}">
            @endforeach
            <input type="hidden" name="redirect_page" value="{{ $elements->currentPage() }}">

            <div class="shrink-0 border-t border-slate-200 bg-slate-50 px-4 py-3 sm:px-5">
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
                    <button
                        type="button"
                        onclick="closeEditElementModal()"
                        class="inline-flex w-full justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 sm:w-auto"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="inline-flex w-full justify-center rounded-xl bg-[#d94d33] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#b83f29] sm:w-auto"
                    >
                        Actualizar activo
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- MODAL COMPONENTES --}}
<div
    id="componentsModal"
    class="fixed left-0 top-0 z-[9999] hidden h-[100dvh] w-[100vw] items-center justify-center overflow-y-auto bg-slate-950/60 px-3 py-4 backdrop-blur-sm sm:px-4 sm:py-6"
>
    <div
        id="componentsModalContent"
        class="flex w-full max-w-3xl scale-95 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white opacity-0 shadow-2xl transition duration-200 ease-out"
        style="max-height: calc(100dvh - 2rem);"
    >
        <div class="flex shrink-0 items-center justify-between border-b border-slate-200 bg-white px-4 py-3 sm:px-5">
            <div>
                <h3 class="text-base font-bold text-slate-900 sm:text-lg">
                    Asociar componentes
                </h3>
                <p class="mt-0.5 hidden text-xs text-slate-500 sm:block">
                    Selecciona los componentes disponibles para este activo.
                </p>
            </div>

            <button
                type="button"
                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                onclick="closeComponentsModal()"
                title="Cerrar"
            >
                ✕
            </button>
        </div>

        <form id="componentsForm" method="POST" class="flex min-h-0 flex-1 flex-col">
            @csrf

            <div class="min-h-0 flex-1 overflow-y-auto px-4 py-3 sm:px-5 sm:py-4">
                <div class="grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-3">
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Área</div>
                        <div id="components_area_name" class="mt-1 text-sm font-semibold text-slate-900"></div>
                    </div>
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Tipo de activo</div>
                        <div id="components_type_name" class="mt-1 text-sm font-semibold text-slate-900"></div>
                    </div>
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Nombre</div>
                        <div id="components_element_name" class="mt-1 text-sm font-semibold text-slate-900"></div>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="mb-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <label class="block text-sm font-semibold text-slate-700">
                            Componentes disponibles
                        </label>

                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                onclick="selectAllVisibleComponents()"
                                class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                            >
                                Seleccionar todo
                            </button>

                            <button
                                type="button"
                                onclick="unselectAllVisibleComponents()"
                                class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                            >
                                Deseleccionar todo
                            </button>
                        </div>
                    </div>

                    <div id="componentsChecklist" class="grid max-h-[44dvh] gap-3 overflow-y-auto rounded-2xl border border-slate-200 bg-white p-3 sm:p-4 md:grid-cols-2">
                        @forelse($components as $component)
                            <label
                                class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white p-3 text-sm text-slate-700 transition hover:border-[#d94d33]/40 hover:bg-orange-50/40"
                                data-component-item
                                data-client-id="{{ $component->client_id }}"
                                data-element-type-id="{{ $component->element_type_id }}"
                            >
                                <input
                                    type="checkbox"
                                    name="component_ids[]"
                                    value="{{ $component->id }}"
                                    data-component-checkbox
                                    class="mt-0.5 rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                                >
                                <span>{{ $component->name }}</span>
                            </label>
                        @empty
                            <div class="text-sm text-slate-500">No hay componentes disponibles.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            @foreach(($activeFilters['client_ids'] ?? []) as $value)
                <input type="hidden" name="redirect_client_ids[]" value="{{ $value }}">
            @endforeach
            @foreach(($activeFilters['area_ids'] ?? []) as $value)
                <input type="hidden" name="redirect_area_ids[]" value="{{ $value }}">
            @endforeach
            @foreach(($activeFilters['element_type_ids'] ?? []) as $value)
                <input type="hidden" name="redirect_element_type_ids[]" value="{{ $value }}">
            @endforeach
            @foreach(($activeFilters['names'] ?? []) as $value)
                <input type="hidden" name="redirect_names[]" value="{{ $value }}">
            @endforeach
            @foreach(($activeFilters['warehouse_codes'] ?? []) as $value)
                <input type="hidden" name="redirect_warehouse_codes[]" value="{{ $value }}">
            @endforeach
            @foreach(($activeFilters['statuses'] ?? []) as $value)
                <input type="hidden" name="redirect_statuses[]" value="{{ $value }}">
            @endforeach
            <input type="hidden" name="redirect_page" value="{{ $elements->currentPage() }}">

            <div class="shrink-0 border-t border-slate-200 bg-slate-50 px-4 py-3 sm:px-5">
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
                    <button
                        type="button"
                        onclick="closeComponentsModal()"
                        class="inline-flex w-full justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 sm:w-auto"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="inline-flex w-full justify-center rounded-xl bg-[#d94d33] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#b83f29] sm:w-auto"
                    >
                        Guardar componentes
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

    {{-- POPOVER FILTROS --}}
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
    <div id="elementToastContainer" class="fixed bottom-5 right-5 z-[99999] space-y-3"></div>

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
        area_ids: {
            type: 'checklist_object',
            title: 'Área',
            inputName: 'area_ids',
            options: @json($filterOptions['area_ids']),
        },
        element_type_ids: {
            type: 'checklist_object',
            title: 'Tipo',
            inputName: 'element_type_ids',
            options: @json($filterOptions['element_type_ids']),
        },
        names: {
            type: 'checklist',
            title: 'Nombre',
            inputName: 'names',
            options: @json($filterOptions['names']),
        },
        warehouse_codes: {
            type: 'checklist',
            title: 'Código almacén',
            inputName: 'warehouse_codes',
            options: @json($filterOptions['warehouse_codes']),
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

    function filterCreateAreasByClient(clientId) {
        const select = document.getElementById('create_area_id');
        if (!select) return;

        const currentValue = select.value;

        Array.from(select.options).forEach(option => {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            option.hidden = clientId ? String(option.dataset.clientId) !== String(clientId) : false;
        });

        if (currentValue) {
            const selectedOption = select.querySelector(`option[value="${currentValue}"]`);
            if (selectedOption && selectedOption.hidden) {
                select.value = '';
            }
        }
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

        filterCreateAreasByClient(clientId);
        filterCreateElementTypesByClient(clientId);
    }

    function filterEditFieldsByArea(areaId) {
        const areaSelect = document.getElementById('edit_element_area_id');
        const typeSelect = document.getElementById('edit_element_type_id');
        if (!areaSelect || !typeSelect) return;

        const selectedAreaOption = areaSelect.querySelector(`option[value="${areaId}"]`);
        const clientId = selectedAreaOption?.dataset.clientId ?? '';

        Array.from(typeSelect.options).forEach(option => {
            option.hidden = clientId ? String(option.dataset.clientId) !== String(clientId) : false;
        });

        if (typeSelect.value) {
            const selectedType = typeSelect.querySelector(`option[value="${typeSelect.value}"]`);
            if (selectedType && selectedType.hidden) {
                typeSelect.value = '';
            }
        }
    }

    function openEditElementModalFromButton(button) {
        clearElementAjaxErrors('editElementAjaxErrors');

        document.getElementById('editElementForm').action = button.dataset.action;
        document.getElementById('edit_element_name').value = button.dataset.name ?? '';
        document.getElementById('edit_element_code').value = button.dataset.code ?? '';
        document.getElementById('edit_element_warehouse_code').value = button.dataset.warehouse_code ?? '';
        document.getElementById('edit_element_area_id').value = button.dataset.area_id ?? '';

        filterEditFieldsByArea(button.dataset.area_id ?? '');

        document.getElementById('edit_element_type_id').value = button.dataset.element_type_id ?? '';
        document.getElementById('edit_element_status').value = button.dataset.status ?? '1';

        const modal = document.getElementById('editElementModal');
        const content = document.getElementById('editElementModalContent');

        modal.classList.remove('hidden');
        modal.classList.add('flex');

        document.documentElement.classList.add('overflow-hidden');
        document.body.classList.add('overflow-hidden');

        setTimeout(() => {
            content?.classList.remove('scale-95', 'opacity-0');
            content?.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeEditElementModal() {
        const modal = document.getElementById('editElementModal');
        const content = document.getElementById('editElementModalContent');

        clearElementAjaxErrors('editElementAjaxErrors');

        content?.classList.remove('scale-100', 'opacity-100');
        content?.classList.add('scale-95', 'opacity-0');

        setTimeout(() => {
            modal.classList.remove('flex');
            modal.classList.add('hidden');

            document.documentElement.classList.remove('overflow-hidden');
            document.body.classList.remove('overflow-hidden');
        }, 150);
    }

    function filterCreateElementTypesByClient(clientId) {
        const select = document.getElementById('create_element_type_id');
        if (!select) return;

        const currentValue = select.value;

        Array.from(select.options).forEach(option => {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            option.hidden = clientId
                ? String(option.dataset.clientId) !== String(clientId)
                : false;
        });

        if (currentValue) {
            const selectedOption = select.querySelector(`option[value="${currentValue}"]`);
            if (selectedOption && selectedOption.hidden) {
                select.value = '';
            }
        }
    }

    function filterComponentsChecklist(clientId, elementTypeId) {
        document.querySelectorAll('[data-component-item]').forEach(item => {
            const itemClientId = item.dataset.clientId ?? '';
            const itemElementTypeId = item.dataset.elementTypeId ?? '';
            const checkbox = item.querySelector('[data-component-checkbox]');

            const visible =
                String(itemClientId) === String(clientId) &&
                String(itemElementTypeId) === String(elementTypeId);

            item.classList.toggle('hidden', !visible);

            if (!visible && checkbox) {
                checkbox.checked = false;
            }
        });
    }

    function openComponentsModal(elementId, clientId, elementTypeId, areaName, typeName, elementName, actionUrl, selectedComponentIds) {
        document.getElementById('componentsForm').action = actionUrl;
        document.getElementById('components_area_name').textContent = areaName ?? '—';
        document.getElementById('components_type_name').textContent = typeName ?? '—';
        document.getElementById('components_element_name').textContent = elementName ?? '—';

        filterComponentsChecklist(clientId, elementTypeId);

        const selectedSet = new Set((selectedComponentIds ?? []).map(String));

        document.querySelectorAll('[data-component-checkbox]').forEach((checkbox) => {
            const item = checkbox.closest('[data-component-item]');
            const hidden = item?.classList.contains('hidden');

            if (hidden) {
                checkbox.checked = false;
                return;
            }

            checkbox.checked = selectedSet.has(String(checkbox.value));
        });

        const modal = document.getElementById('componentsModal');
        const content = document.getElementById('componentsModalContent');

        modal.classList.remove('hidden');
        modal.classList.add('flex');

        document.documentElement.classList.add('overflow-hidden');
        document.body.classList.add('overflow-hidden');

        setTimeout(() => {
            content?.classList.remove('scale-95', 'opacity-0');
            content?.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

function closeComponentsModal() {
    const modal = document.getElementById('componentsModal');
    const content = document.getElementById('componentsModalContent');

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
        const editAreaSelect = document.getElementById('edit_element_area_id');

        if (selectedClient && selectedClient.value) {
            document.querySelectorAll('.client-single-checkbox').forEach(cb => {
                cb.checked = parseInt(cb.value) === parseInt(selectedClient.value);
            });

            filterCreateAreasByClient(selectedClient.value);
            filterCreateElementTypesByClient(selectedClient.value);
        } else {
            filterCreateAreasByClient('');
            filterCreateElementTypesByClient('');
        }

        if (editAreaSelect) {
            editAreaSelect.addEventListener('change', function () {
                filterEditFieldsByArea(this.value);
            });
        }

        const createElementForm = document.getElementById('createElementForm');
        const editElementForm = document.getElementById('editElementForm');
        const componentsForm = document.getElementById('componentsForm');

        if (createElementForm) {
            createElementForm.addEventListener('submit', handleCreateElementSubmit);
        }

        if (editElementForm) {
            editElementForm.addEventListener('submit', handleEditElementSubmit);
        }

        if (componentsForm) {
            componentsForm.addEventListener('submit', handleComponentsElementSubmit);
        }
    });

    document.addEventListener('click', function (event) {
        const popover = document.getElementById('filterPopover');
        const editModal = document.getElementById('editElementModal');
        const componentsModal = document.getElementById('componentsModal');

        if (!popover.classList.contains('hidden')) {
            if (!popover.contains(event.target) && !event.target.closest('button[onclick^="openFilterPopover"]')) {
                closeFilterPopover();
            }
        }

        if (editModal.classList.contains('flex') && event.target === editModal) {
            closeEditElementModal();
        }

        if (componentsModal.classList.contains('flex') && event.target === componentsModal) {
            closeComponentsModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeFilterPopover();
            closeEditElementModal();
            closeComponentsModal();
        }
    });

    function openComponentsModalFromButton(button) {
    const selectedComponentIds = JSON.parse(button.dataset.component_ids || '[]');

    openComponentsModal(
        button.dataset.id,
        button.dataset.client_id,
        button.dataset.element_type_id,
        button.dataset.area_name,
        button.dataset.type_name,
        button.dataset.name,
        button.dataset.action,
        selectedComponentIds
    );
}

function selectAllVisibleComponents() {
    document
        .querySelectorAll('[data-component-item]:not(.hidden) [data-component-checkbox]')
        .forEach(checkbox => {
            checkbox.checked = true;
        });
}

function unselectAllVisibleComponents() {
    document
        .querySelectorAll('[data-component-item]:not(.hidden) [data-component-checkbox]')
        .forEach(checkbox => {
            checkbox.checked = false;
        });
}

function showElementToast(message, type = 'success') {
    const container = document.getElementById('elementToastContainer');

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

function clearElementAjaxErrors(containerId) {
    const box = document.getElementById(containerId);
    if (!box) return;

    box.classList.add('hidden');
    box.innerHTML = '';
}

function renderElementAjaxErrors(containerId, errors) {
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

function setElementFormSubmittingState(form, isSubmitting, loadingText = 'Guardando...') {
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

async function parseElementJsonResponse(response) {
    const contentType = response.headers.get('content-type') || '';

    if (!contentType.includes('application/json')) {
        throw new Error('El servidor no devolvió JSON. Revisa sesión, permisos o respuesta del controlador.');
    }

    return await response.json();
}
async function handleCreateElementSubmit(event) {
    event.preventDefault();

    const form = event.currentTarget;
    clearElementAjaxErrors('createElementAjaxErrors');
    setElementFormSubmittingState(form, true, 'Guardando...');

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

        const data = await parseElementJsonResponse(response);

        if (response.status === 422) {
            renderElementAjaxErrors('createElementAjaxErrors', data.errors || {});
            showElementToast(data.message || 'Corrige los errores del formulario.', 'error');
            return;
        }

        if (!response.ok || data.success === false) {
            throw new Error(data.message || 'No fue posible crear el activo.');
        }

        showElementToast(data.message || 'Activo creado correctamente.', 'success');

        // Por ahora recargamos para evitar inconsistencias con paginación/filtros.
        // Si quieres 100% sin recargar, falta agregar insertElementRow().
        window.location.reload();
    } catch (error) {
        showElementToast(error.message || 'Ocurrió un error al crear el activo.', 'error');
    } finally {
        setElementFormSubmittingState(form, false);
    }
}

async function handleEditElementSubmit(event) {
    event.preventDefault();

    const form = event.currentTarget;
    clearElementAjaxErrors('editElementAjaxErrors');
    setElementFormSubmittingState(form, true, 'Actualizando...');

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

        const data = await parseElementJsonResponse(response);

        if (response.status === 422) {
            renderElementAjaxErrors('editElementAjaxErrors', data.errors || {});
            showElementToast(data.message || 'Corrige los errores del formulario.', 'error');
            return;
        }

        if (!response.ok || data.success === false) {
            throw new Error(data.message || 'No fue posible actualizar el activo.');
        }

        updateElementRow(data.element);
        closeEditElementModal();

        showElementToast(data.message || 'Activo actualizado correctamente.', 'success');
    } catch (error) {
        showElementToast(error.message || 'Ocurrió un error al actualizar el activo.', 'error');
    } finally {
        setElementFormSubmittingState(form, false);
    }
}

async function handleComponentsElementSubmit(event) {
    event.preventDefault();

    const form = event.currentTarget;
    setElementFormSubmittingState(form, true, 'Guardando...');

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

        const data = await parseElementJsonResponse(response);

        if (!response.ok || data.success === false) {
            throw new Error(data.message || 'No fue posible actualizar los componentes.');
        }

        updateElementRow(data.element);
        closeComponentsModal();

        showElementToast(data.message || 'Componentes actualizados correctamente.', 'success');
    } catch (error) {
        showElementToast(error.message || 'Ocurrió un error al actualizar los componentes.', 'error');
    } finally {
        setElementFormSubmittingState(form, false);
    }
}

async function toggleElementStatus(button) {
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

        const data = await parseElementJsonResponse(response);

        if (!response.ok || data.success === false) {
            throw new Error(data.message || 'No fue posible cambiar el estado.');
        }

        renderElementStatusButton(button, Boolean(data.status));
        showElementToast(data.message || 'Estado actualizado correctamente.', 'success');
    } catch (error) {
        button.innerHTML = originalHtml;
        button.className = originalClass;
        showElementToast(error.message || 'Ocurrió un error al cambiar el estado.', 'error');
    } finally {
        button.disabled = false;
        button.classList.remove('opacity-60', 'cursor-wait');

        if (window.lucide) {
            window.lucide.createIcons();
        }
    }
}

function renderElementStatusButton(button, enabled) {
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

async function deleteElement(elementId) {
    const confirmed = confirm('¿Seguro que deseas eliminar este activo?');

    if (!confirmed) return;

    const row = document.getElementById(`element-row-${elementId}`);
    const form = document.getElementById(`delete-element-form-${elementId}`);

    if (!form) {
        showElementToast('No se encontró el formulario de eliminación.', 'error');
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

        const data = await parseElementJsonResponse(response);

        if (!response.ok || data.success === false) {
            throw new Error(data.message || 'No fue posible eliminar el activo.');
        }

        if (row) {
            row.style.transition = 'opacity 180ms ease, transform 180ms ease';
            row.style.opacity = '0';
            row.style.transform = 'scale(0.98)';
            setTimeout(() => row.remove(), 180);
        }

        showElementToast(data.message || 'Activo eliminado correctamente.', 'success');
    } catch (error) {
        if (row) {
            row.classList.remove('opacity-60', 'pointer-events-none');
        }

        showElementToast(error.message || 'Ocurrió un error al eliminar el activo.', 'error');
    }
}

function updateElementRow(element) {
    if (!element || !element.id) return;

    const row = document.getElementById(`element-row-${element.id}`);
    const clientEl = document.getElementById(`element-client-${element.id}`);
    const areaEl = document.getElementById(`element-area-${element.id}`);
    const groupEl = document.getElementById(`element-group-${element.id}`);
    const typeEl = document.getElementById(`element-type-${element.id}`);
    const nameEl = document.getElementById(`element-name-${element.id}`);
    const warehouseEl = document.getElementById(`element-warehouse-code-${element.id}`);
    const componentsCountEl = document.getElementById(`element-components-count-${element.id}`);
    const reportDetailsCountEl = document.getElementById(`element-report-details-count-${element.id}`);
    const editButton = row?.querySelector('[data-edit-element]');
    const componentsButton = row?.querySelector('[data-components-element]');
    const statusButton = row?.querySelector('[data-status-toggle]');

    if (clientEl) clientEl.textContent = element.client_name ?? '—';
    if (areaEl) areaEl.textContent = element.area_name ?? '—';
    if (groupEl) groupEl.textContent = element.group_name ?? '—';
    if (typeEl) typeEl.textContent = element.element_type_name ?? '—';
    if (nameEl) nameEl.textContent = element.name ?? '—';
    if (warehouseEl) warehouseEl.textContent = element.warehouse_code_label ?? '—';
    if (componentsCountEl) componentsCountEl.textContent = String(element.components_count ?? 0);
    if (reportDetailsCountEl) reportDetailsCountEl.textContent = String(element.report_details_count ?? 0);

    if (editButton) {
        editButton.dataset.name = element.name ?? '';
        editButton.dataset.code = element.code ?? '';
        editButton.dataset.warehouse_code = element.warehouse_code ?? '';
        editButton.dataset.area_id = element.area_id ?? '';
        editButton.dataset.element_type_id = element.element_type_id ?? '';
        editButton.dataset.status = element.status ? '1' : '0';
        editButton.dataset.action = element.update_url ?? '';
    }

    if (componentsButton) {
        componentsButton.dataset.client_id = element.client_id ?? '';
        componentsButton.dataset.element_type_id = element.element_type_id ?? '';
        componentsButton.dataset.area_name = element.area_name ?? '—';
        componentsButton.dataset.type_name = element.element_type_name ?? '—';
        componentsButton.dataset.name = element.name ?? '';
        componentsButton.dataset.action = element.sync_components_url ?? '';
        componentsButton.dataset.component_ids = JSON.stringify(element.component_ids || []);
    }

    if (statusButton) {
        statusButton.dataset.url = element.toggle_status_url ?? statusButton.dataset.url;
        renderElementStatusButton(statusButton, Boolean(element.status));
    }

    if (window.lucide) {
        window.lucide.createIcons();
    }
}
</script>

@endsection
