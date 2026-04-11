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
    @endphp

    <div class="space-y-8">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Gestión de activos</h2>
            <p class="mt-2 text-slate-600">
                Crea y administra activos para los clientes que tienes asignados.
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
<!-- FORMULARIO -->
            <div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Nuevo activo</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Registra un nuevo activo para uno de tus clientes.
                    </p>

                    <form method="POST" action="{{ route('admin.managed-elements.store') }}" class="mt-6 space-y-5">
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
                                value="{{ old('code') }}"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                placeholder="Opcional"
                            >
                            @error('code')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Código de almacén</label>
                            <input
                                type="text"
                                name="warehouse_code"
                                value="{{ old('warehouse_code') }}"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                placeholder="Opcional"
                            >
                            @error('warehouse_code')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Estado</label>
                            <select
                                name="status"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            >
                                <option value="1" @selected(old('status', '1') == '1')>Activo</option>
                                <option value="0" @selected(old('status') == '0')>Inactivo</option>
                            </select>
                            @error('status')
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
<!-- LISTADO -->
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


                            <tbody class="divide-y divide-slate-200 bg-white">
                                @forelse($elements as $element)
                                    @php
                                        $hasDependencies = (($element->components_count ?? 0) + ($element->report_details_count ?? 0)) > 0;
                                    @endphp

                                    <tr class="hover:bg-slate-50">
                                        @if($showClientColumn)
                                            <td class="px-4 py-3 text-sm text-slate-700">
                                                {{ $element->area?->client?->name ?? '—' }}
                                            </td>
                                        @endif

                                        <td class="px-4 py-3 text-sm text-slate-700">
                                            {{ $element->area?->name ?? '—' }}
                                        </td>

                                        <td class="px-4 py-3 text-sm text-slate-700">
                                            {{ $element->elementType?->name ?? '—' }}
                                        </td>

                                        <td class="px-4 py-3 text-sm font-medium text-slate-900">
                                            {{ $element->name }}
                                        </td>

                                        <td class="px-4 py-3 text-sm text-slate-700">
                                            {{ $element->warehouse_code ?: '—' }}
                                        </td>

                                        <td class="px-4 py-3 text-sm text-slate-700">
                                            {{ $element->components_count ?? 0 }}
                                        </td>

                                        <td class="px-4 py-3 text-sm text-slate-700">
                                            {{ $element->report_details_count ?? 0 }}
                                        </td>

                                        <td class="px-4 py-3 text-sm">
                                            @if($element->status)
                                                <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                                    Activo
                                                </span>
                                            @else
                                                <span class="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                                                    Inactivo
                                                </span>
                                            @endif
                                        </td>

                                        <td class="px-4 py-3 text-right">
                                            <div class="flex justify-end gap-2">
                                                <button
                                                    type="button"
                                                    onclick="openComponentsModal(
                                                        '{{ $element->id }}',
                                                        '{{ $element->area?->client_id ?? '' }}',
                                                        '{{ $element->element_type_id ?? '' }}',
                                                        @js($element->area?->name ?? '—'),
                                                        @js($element->elementType?->name ?? '—'),
                                                        @js($element->name),
                                                        '{{ route('admin.managed-elements.components.sync', $element) }}',
                                                        @js($element->components->pluck('id')->map(fn($id) => (string) $id)->toArray())
                                                    )"
                                                    class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-100"
                                                >
                                                    Componentes
                                                </button>


                                                <button
                                                    type="button"
                                                    onclick="openEditElementModal(
                                                        '{{ $element->id }}',
                                                        @js($element->name),
                                                        @js($element->code),
                                                        @js($element->warehouse_code),
                                                        '{{ $element->area_id }}',
                                                        '{{ $element->element_type_id }}',
                                                        '{{ $element->status ? 1 : 0 }}',
                                                        '{{ route('admin.managed-elements.update', $element) }}'
                                                    )"
                                                    class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                                                >
                                                    Editar
                                                </button>

                                                @if(!$hasDependencies)
                                                    <form
                                                        method="POST"
                                                        action="{{ route('admin.managed-elements.destroy', $element) }}"
                                                        onsubmit="return confirm('¿Seguro que deseas eliminar este activo?');"
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
                                                    <form
                                                        method="POST"
                                                        action="{{ route('admin.managed-elements.toggle-status', $element) }}"
                                                    >
                                                        @csrf
                                                        @method('PATCH')

                                                        <button
                                                            type="submit"
                                                            class="rounded-lg px-3 py-2 text-xs font-semibold text-white transition {{ $element->status ? 'bg-amber-500 hover:bg-amber-600' : 'bg-green-600 hover:bg-green-700' }}"
                                                        >
                                                            {{ $element->status ? 'Inactivar' : 'Activar' }}
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $showClientColumn ? 9 : 8 }}" class="px-4 py-10 text-center text-sm text-slate-500">
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
    <div id="editElementModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
        <div class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Editar activo</h3>
                <button type="button" class="text-slate-500 hover:text-slate-900" onclick="closeEditElementModal()">✕</button>
            </div>

            <form id="editElementForm" method="POST" class="space-y-5 p-6">
                @csrf
                @method('PUT')

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Nombre</label>
                    <input
                        type="text"
                        name="name"
                        id="edit_element_name"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                    >
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Código</label>
                    <input
                        type="text"
                        name="code"
                        id="edit_element_code"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                    >
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Código de almacén</label>
                    <input
                        type="text"
                        name="warehouse_code"
                        id="edit_element_warehouse_code"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                    >
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Área</label>
                    <select
                        name="area_id"
                        id="edit_element_area_id"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
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
                    <label class="mb-2 block text-sm font-medium text-slate-700">Tipo de activo</label>
                    <select
                        name="element_type_id"
                        id="edit_element_type_id"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                    >
                        @foreach($elementTypes as $elementType)
                            <option value="{{ $elementType->id }}" data-client-id="{{ $elementType->client_id }}">
                                {{ $elementType->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Estado</label>
                    <select
                        name="status"
                        id="edit_element_status"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                    >
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
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

                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        onclick="closeEditElementModal()"
                        class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                    >
                        Actualizar activo
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL COMPONENTES --}}
    <div id="componentsModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
        <div class="w-full max-w-3xl rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Asociar componentes</h3>
                <button type="button" class="text-slate-500 hover:text-slate-900" onclick="closeComponentsModal()">✕</button>
            </div>

            <form id="componentsForm" method="POST" class="space-y-5 p-6">
                @csrf

                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Área</div>
                        <div id="components_area_name" class="mt-1 text-sm text-slate-900"></div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tipo de activo</div>
                        <div id="components_type_name" class="mt-1 text-sm text-slate-900"></div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nombre</div>
                        <div id="components_element_name" class="mt-1 text-sm text-slate-900"></div>
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Componentes disponibles</label>
                    <div id="componentsChecklist" class="grid max-h-[420px] gap-3 overflow-y-auto rounded-xl border border-slate-200 p-4 md:grid-cols-2">
                        @forelse($components as $component)
                            <label
                                class="flex items-start gap-3 rounded-xl border border-slate-200 p-3 text-sm text-slate-700"
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

                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        onclick="closeComponentsModal()"
                        class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        class="rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                    >
                        Guardar componentes
                    </button>
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

        function openEditElementModal(id, name, code, warehouseCode, areaId, elementTypeId, status, actionUrl) {
            document.getElementById('editElementForm').action = actionUrl;
            document.getElementById('edit_element_name').value = name ?? '';
            document.getElementById('edit_element_code').value = code ?? '';
            document.getElementById('edit_element_warehouse_code').value = warehouseCode ?? '';
            document.getElementById('edit_element_area_id').value = areaId ?? '';
            filterEditFieldsByArea(areaId);
            document.getElementById('edit_element_type_id').value = elementTypeId ?? '';
            document.getElementById('edit_element_status').value = status ?? '1';

            const modal = document.getElementById('editElementModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeEditElementModal() {
            const modal = document.getElementById('editElementModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
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
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }


        function closeComponentsModal() {
            const modal = document.getElementById('componentsModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }

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
        });
    </script>
@endsection

