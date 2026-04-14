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

<div class="space-y-8">
    <div>
        <h2 class="text-3xl font-bold tracking-tight text-slate-900">Gestión de agrupaciones</h2>
        <p class="mt-2 text-slate-600">
            Crea y administra agrupaciones operativas por cliente.
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

    <div class="grid gap-8 xl:grid-cols-[340px_minmax(0,1fr)]">
        <div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900">Nueva agrupación</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Define agrupaciones operativas para organizar activos por cliente.
                </p>

                <form method="POST" action="{{ route('admin.managed-groups.store') }}" class="mt-6 space-y-5">
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
                            value="{{ old('name') }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            placeholder="Ej. Gamas mecánicas"
                        >
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Descripción</label>
                        <textarea
                            name="description"
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

                        @if($hasAnyActiveFilter)
                            <a
                                href="{{ route('admin.managed-groups.index') }}"
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
                                    Descripción
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
                            @forelse($groups as $group)
                                @php
                                    $editPayload = [
                                        'id' => $group->id,
                                        'client_id' => $group->client_id,
                                        'name' => $group->name,
                                        'description' => $group->description,
                                        'action' => route('admin.managed-groups.update', $group),
                                    ];

                                    $selectedElementIds = $group->elements
                                        ->pluck('id')
                                        ->map(fn ($id) => (string) $id)
                                        ->values()
                                        ->all();
                                @endphp

                                <tr class="hover:bg-slate-50">
                                    <td class="whitespace-nowrap px-5 py-3 text-sm font-medium text-slate-900">
                                        {{ $group->name }}
                                    </td>

                                    @if($showClientColumn)
                                        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700">
                                            {{ $group->client?->name ?? '—' }}
                                        </td>
                                    @endif

                                    <td class="px-5 py-3 text-sm text-slate-700">
                                        {{ $group->description ?: '—' }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700">
                                        {{ $group->elements_count ?? 0 }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-3 text-sm">
                                        @if($group->status)
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
                                                class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-100"
                                                onclick="openElementsModal(
                                                    '{{ $group->id }}',
                                                    '{{ $group->client_id }}',
                                                    @js($group->name),
                                                    @js($group->description ?: '—'),
                                                    '{{ route('admin.managed-groups.elements.sync', $group) }}',
                                                    @js($selectedElementIds)
                                                )"
                                            >
                                                Activos
                                            </button>

                                            <button
                                                type="button"
                                                class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                                                onclick='openEditGroupModal(@json($editPayload))'
                                            >
                                                Editar
                                            </button>

                                            @if(($group->elements_count ?? 0) === 0)
                                                <form
                                                    method="POST"
                                                    action="{{ route('admin.managed-groups.destroy', $group) }}"
                                                    onsubmit="return confirm('¿Seguro que deseas eliminar esta agrupación?');"
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
                                                    <input type="hidden" name="redirect_page" value="{{ $groups->currentPage() }}">

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
                                                    action="{{ route('admin.managed-groups.toggle-status', $group) }}"
                                                >
                                                    @csrf
                                                    @method('PATCH')

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

                                                    <button
                                                        type="submit"
                                                        class="rounded-lg px-3 py-2 text-xs font-semibold text-white transition {{ $group->status ? 'bg-amber-500 hover:bg-amber-600' : 'bg-green-600 hover:bg-green-700' }}"
                                                    >
                                                        {{ $group->status ? 'Inactivar' : 'Activar' }}
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $showClientColumn ? 6 : 5 }}" class="px-5 py-10 text-center text-sm text-slate-500">
                                        No hay agrupaciones registradas todavía.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($groups->hasPages())
                    <div class="border-t border-slate-200 px-5 py-4">
                        {{ $groups->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- MODAL EDITAR --}}
<div id="editGroupModal" class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-black/50 px-4 py-6">
    <div class="flex min-h-full w-full items-start justify-center">
        <div class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Editar agrupación</h3>
                <button type="button" class="text-slate-500 hover:text-slate-900" onclick="closeEditGroupModal()">✕</button>
            </div>

            <form id="editGroupForm" method="POST" class="space-y-5 p-6">
                @csrf
                @method('PUT')

                @if($singleClient)
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Cliente</label>
                        <div class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            {{ $singleClient->name }}
                        </div>
                        <input type="hidden" name="client_id" id="edit_client_id_hidden" value="{{ $singleClient->id }}">
                    </div>
                @else
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Cliente</label>
                        <select
                            name="client_id"
                            id="edit_client_id"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                        >
                            <option value="">Seleccione un cliente</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Nombre</label>
                    <input
                        type="text"
                        name="name"
                        id="edit_name"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                    >
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Descripción</label>
                    <textarea
                        name="description"
                        id="edit_description"
                        rows="4"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                    ></textarea>
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

                <div class="flex justify-end gap-3 border-t border-slate-200 pt-4">
                    <button
                        type="button"
                        onclick="closeEditGroupModal()"
                        class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        class="rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                    >
                        Actualizar agrupación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL ACTIVOS --}}
<div id="elementsModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
    <div class="w-full max-w-5xl rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <h3 class="text-lg font-semibold text-slate-900">Asociar activos</h3>
            <button type="button" class="text-slate-500 hover:text-slate-900" onclick="closeElementsModal()">✕</button>
        </div>

        <form id="elementsForm" method="POST" class="space-y-5 p-6">
            @csrf

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

                <div id="elementsChecklist" class="max-h-[500px] space-y-5 overflow-y-auto rounded-xl border border-slate-200 p-4">
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

            <div class="flex justify-end gap-3">
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

    function handleSingleClientSelection(currentCheckbox) {
        document.querySelectorAll('.group-single-checkbox').forEach(checkbox => {
            if (checkbox !== currentCheckbox) {
                checkbox.checked = false;
            }
        });

        document.getElementById('selected_client_id').value = currentCheckbox.checked ? currentCheckbox.value : '';
    }

    function openEditGroupModal(group) {
        document.getElementById('editGroupForm').action = group.action;
        document.getElementById('edit_name').value = group.name ?? '';
        document.getElementById('edit_description').value = group.description ?? '';

        const selectClient = document.getElementById('edit_client_id');
        const hiddenClient = document.getElementById('edit_client_id_hidden');

        if (selectClient) {
            selectClient.value = group.client_id ?? '';
        }

        if (hiddenClient) {
            hiddenClient.value = group.client_id ?? '';
        }

        const modal = document.getElementById('editGroupModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeEditGroupModal() {
        const modal = document.getElementById('editGroupModal');
        modal.classList.remove('flex');
        modal.classList.add('hidden');
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
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeElementsModal() {
        const modal = document.getElementById('elementsModal');
        modal.classList.remove('flex');
        modal.classList.add('hidden');
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

    document.addEventListener('DOMContentLoaded', function () {
        const selectedClientId = document.getElementById('selected_client_id');
        if (selectedClientId && selectedClientId.value) {
            const checkbox = document.querySelector(`.group-single-checkbox[value="${selectedClientId.value}"]`);
            if (checkbox) {
                checkbox.checked = true;
            }
        }
    });

    document.addEventListener('click', function (event) {
        const popover = document.getElementById('filterPopover');
        const editModal = document.getElementById('editGroupModal');
        const elementsModal = document.getElementById('elementsModal');

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
</script>
@endsection
