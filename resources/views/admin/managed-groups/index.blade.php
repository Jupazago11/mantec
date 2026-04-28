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
                                    Sincronización
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

                        <tbody id="groupsTableBody" class="divide-y divide-slate-200 bg-white">
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

                                <tr class="hover:bg-slate-50" id="group-row-{{ $group->id }}">
                                    <td class="whitespace-nowrap px-5 py-3 text-sm font-medium text-slate-900" id="group-name-{{ $group->id }}">
                                        {{ $group->name }}
                                    </td>

                                    @if($showClientColumn)
                                        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700" id="group-client-{{ $group->id }}">
                                            {{ $group->client?->name ?? '—' }}
                                        </td>
                                    @endif

                                    <td class="px-5 py-3 text-sm text-slate-700" id="group-description-{{ $group->id }}">
                                        {{ $group->description ?: '—' }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700" id="group-elements-count-{{ $group->id }}">
                                        {{ $group->elements_count ?? 0 }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-3 text-sm">
                                        <button
                                            type="button"
                                            data-sync-toggle
                                            data-url="{{ route('admin.managed-groups.toggle-sync', $group) }}"
                                            data-enabled="{{ $group->auto_sync ? '1' : '0' }}"
                                            onclick="toggleGroupSync(this)"
                                            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition {{ $group->auto_sync ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}"
                                            title="Clic para activar o desactivar la sincronización automática"
                                        >
                                            <i data-lucide="{{ $group->auto_sync ? 'refresh-cw-check' : 'refresh-cw-off' }}" class="h-3.5 w-3.5"></i>
                                            <span>{{ $group->auto_sync ? 'ON' : 'OFF' }}</span>
                                        </button>
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-3 text-sm">
                                        <button
                                            type="button"
                                            data-status-toggle
                                            data-url="{{ route('admin.managed-groups.toggle-status', $group) }}"
                                            data-enabled="{{ $group->status ? '1' : '0' }}"
                                            onclick="toggleGroupStatus(this)"
                                            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition {{ $group->status ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-red-100 text-red-700 hover:bg-red-200' }}"
                                            title="Clic para activar o inactivar"
                                        >
                                            <i data-lucide="{{ $group->status ? 'check-circle-2' : 'x-circle' }}" class="h-3.5 w-3.5"></i>
                                            <span>{{ $group->status ? 'Activo' : 'Inactivo' }}</span>
                                        </button>
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button
                                                type="button"
                                                class="text-blue-500 transition hover:text-blue-700"
                                                title="Asociar activos"
                                                onclick="openElementsModal(
                                                    '{{ $group->id }}',
                                                    '{{ $group->client_id }}',
                                                    @js($group->name),
                                                    @js($group->description ?: '—'),
                                                    '{{ route('admin.managed-groups.elements.sync', $group) }}',
                                                    @js($selectedElementIds)
                                                )"
                                            >
                                                <i data-lucide="boxes" class="h-4 w-4"></i>
                                            </button>

                                            <button
                                                type="button"
                                                data-edit-group
                                                data-id="{{ $group->id }}"
                                                data-client_id="{{ $group->client_id }}"
                                                data-name="{{ $group->name }}"
                                                data-description="{{ $group->description }}"
                                                data-action="{{ route('admin.managed-groups.update', $group) }}"
                                                class="text-slate-400 transition hover:text-[#d94d33]"
                                                onclick="openEditGroupModal(this)"
                                                title="Editar agrupación"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M16.862 4.487l1.651-1.651a2.121 2.121 0 113 3l-1.651 1.651M4 20h4l10.586-10.586a2 2 0 00-2.828-2.828L5.172 17.172A2 2 0 004 18.586V20z" />
                                                </svg>
                                            </button>

                                            @if(($group->elements_count ?? 0) === 0)
                                                <button
                                                    type="button"
                                                    onclick="deleteGroup({{ $group->id }})"
                                                    class="text-red-500 transition hover:text-red-700"
                                                    title="Eliminar agrupación"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M6 7h12M9 7V4h6v3M10 11v6M14 11v6M5 7l1 13a2 2 0 002 2h8a2 2 0 002-2l1-13" />
                                                    </svg>
                                                </button>

                                                <form
                                                    id="delete-group-form-{{ $group->id }}"
                                                    method="POST"
                                                    action="{{ route('admin.managed-groups.destroy', $group) }}"
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
                                                    <input type="hidden" name="redirect_page" value="{{ $groups->currentPage() }}">
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $showClientColumn ? 7 : 6 }}" class="px-5 py-10 text-center text-sm text-slate-500">
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

        insertGroupRow(data.group);
        resetCreateGroupForm();

        showGroupToast(data.message || 'Agrupación creada correctamente.', 'success');
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

        updateGroupRow(data.group);
        closeEditGroupModal();

        showGroupToast(data.message || 'Agrupación actualizada correctamente.', 'success');
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

        if (row) {
            row.style.transition = 'opacity 180ms ease, transform 180ms ease';
            row.style.opacity = '0';
            row.style.transform = 'scale(0.98)';
            setTimeout(() => row.remove(), 180);
        }

        showGroupToast(data.message || 'Agrupación eliminada correctamente.', 'success');
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

        renderGroupStatusButton(button, Boolean(data.status));
        showGroupToast(data.message || 'Estado actualizado correctamente.', 'success');
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
