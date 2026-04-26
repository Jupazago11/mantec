@extends('layouts.admin')
@section('title', 'Diagnósticos')
@section('header_title', 'Diagnósticos')

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

        <div class="grid gap-8 xl:grid-cols-[320px_minmax(0,1fr)]">
            <div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Nuevo diagnóstico</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Registra un diagnóstico para uno de tus clientes.
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
                                Nombre del diagnóstico
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

                        @foreach(($activeFilters['client_ids'] ?? []) as $value)
                            <input type="hidden" name="redirect_client_ids[]" value="{{ $value }}">
                        @endforeach
                        @foreach(($activeFilters['element_type_ids'] ?? []) as $value)
                            <input type="hidden" name="redirect_element_type_ids[]" value="{{ $value }}">
                        @endforeach
                        @foreach(($activeFilters['diagnostic_names'] ?? []) as $value)
                            <input type="hidden" name="redirect_diagnostic_names[]" value="{{ $value }}">
                        @endforeach
                        @foreach(($activeFilters['statuses'] ?? []) as $value)
                            <input type="hidden" name="redirect_statuses[]" value="{{ $value }}">
                        @endforeach
                        <input type="hidden" name="redirect_page" value="{{ request('page', 1) }}">

                        <div class="pt-2">
                            <button
                                type="submit"
                                class="w-full rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b63c28]"
                            >
                                Crear diagnóstico
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div>
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <div class="flex items-center justify-between gap-4">
                            <h3 class="text-lg font-semibold text-slate-900">
                                Listado de diagnósticos
                            </h3>

                            @if($hasAnyActiveFilter)
                                <a
                                    href="{{ route('admin.managed-diagnostics.index') }}"
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
                                            <span>Tipo de activo</span>
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

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        <div class="flex items-center gap-2">
                                            <span>Nombre</span>
                                            <button
                                                type="button"
                                                onclick="openFilterPopover(event, 'diagnostic_names')"
                                                class="rounded p-1 transition hover:bg-slate-200 {{ $hasFilter('diagnostic_names') ? 'text-[#d94d33]' : 'text-slate-400' }}"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                                </svg>
                                            </button>
                                        </div>
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

                            <tbody id="diagnosticsTableBody" class="divide-y divide-slate-200 bg-white">
                                @forelse($diagnostics as $diagnostic)
                                    @php
                                        $hasDependencies = (($diagnostic->components_count ?? 0) + ($diagnostic->report_details_count ?? 0)) > 0;
                                    @endphp

                                    <tr class="hover:bg-slate-50" id="diagnostic-row-{{ $diagnostic->id }}">
                                        @if($showClientColumn)
                                            <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700" id="diagnostic-client-{{ $diagnostic->id }}">
                                                {{ $diagnostic->client?->name ?? '—' }}
                                            </td>
                                        @endif

                                        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700" id="diagnostic-element-type-{{ $diagnostic->id }}">
                                            {{ $diagnostic->elementType?->name ?? '—' }}
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-3 text-sm font-medium text-slate-900">
                                            {{ $diagnostic->name }}
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-3 text-sm">
                                            <button
                                                type="button"
                                                data-status-toggle
                                                data-url="{{ route('admin.managed-diagnostics.toggle-status', $diagnostic) }}"
                                                data-enabled="{{ $diagnostic->status ? '1' : '0' }}"
                                                onclick="toggleDiagnosticStatus(this)"
                                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition {{ $diagnostic->status ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-red-100 text-red-700 hover:bg-red-200' }}"
                                                title="Clic para activar o inactivar"
                                            >
                                                <i data-lucide="{{ $diagnostic->status ? 'check-circle-2' : 'x-circle' }}" class="h-3.5 w-3.5"></i>
                                                <span>{{ $diagnostic->status ? 'Activo' : 'Inactivo' }}</span>
                                            </button>
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-3 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <button
                                                    type="button"
                                                    data-edit-diagnostic
                                                    data-id="{{ $diagnostic->id }}"
                                                    data-client_id="{{ $diagnostic->client_id }}"
                                                    data-element_type_id="{{ $diagnostic->element_type_id }}"
                                                    data-name="{{ $diagnostic->name }}"
                                                    data-status="{{ $diagnostic->status ? 1 : 0 }}"
                                                    data-action="{{ route('admin.managed-diagnostics.update', $diagnostic) }}"
                                                    onclick="openEditDiagnosticModal(this)"
                                                    class="text-slate-400 transition hover:text-[#d94d33]"
                                                    title="Editar diagnóstico"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M16.862 4.487l1.651-1.651a2.121 2.121 0 113 3l-1.651 1.651M4 20h4l10.586-10.586a2 2 0 00-2.828-2.828L5.172 17.172A2 2 0 004 18.586V20z" />
                                                    </svg>
                                                </button>

                                                @if(!$hasDependencies)
                                                    <button
                                                        type="button"
                                                        onclick="deleteDiagnostic({{ $diagnostic->id }})"
                                                        class="text-red-500 transition hover:text-red-700"
                                                        title="Eliminar diagnóstico"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M6 7h12M9 7V4h6v3M10 11v6M14 11v6M5 7l1 13a2 2 0 002 2h8a2 2 0 002-2l1-13" />
                                                        </svg>
                                                    </button>

                                                    <form
                                                        id="delete-diagnostic-form-{{ $diagnostic->id }}"
                                                        method="POST"
                                                        action="{{ route('admin.managed-diagnostics.destroy', $diagnostic) }}"
                                                        class="hidden"
                                                    >
                                                        @csrf
                                                        @method('DELETE')

                                                        @foreach(($activeFilters['client_ids'] ?? []) as $value)
                                                            <input type="hidden" name="redirect_client_ids[]" value="{{ $value }}">
                                                        @endforeach
                                                        @foreach(($activeFilters['element_type_ids'] ?? []) as $value)
                                                            <input type="hidden" name="redirect_element_type_ids[]" value="{{ $value }}">
                                                        @endforeach
                                                        @foreach(($activeFilters['diagnostic_names'] ?? []) as $value)
                                                            <input type="hidden" name="redirect_diagnostic_names[]" value="{{ $value }}">
                                                        @endforeach
                                                        @foreach(($activeFilters['statuses'] ?? []) as $value)
                                                            <input type="hidden" name="redirect_statuses[]" value="{{ $value }}">
                                                        @endforeach
                                                        <input type="hidden" name="redirect_page" value="{{ $diagnostics->currentPage() }}">
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $showClientColumn ? 5 : 4 }}" class="px-5 py-10 text-center text-sm text-slate-500">
                                            No hay diagnósticos registrados todavía.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($diagnostics->hasPages())
                        <div class="border-t border-slate-200 px-6 py-4">
                            {{ $diagnostics->links() }}
                        </div>
                    @endif
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
                    Editar diagnóstico
                </h3>
                <p class="mt-0.5 hidden text-xs text-slate-500 sm:block">
                    Actualiza el diagnóstico seleccionado.
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

            @foreach(($activeFilters['client_ids'] ?? []) as $value)
                <input type="hidden" name="redirect_client_ids[]" value="{{ $value }}">
            @endforeach
            @foreach(($activeFilters['element_type_ids'] ?? []) as $value)
                <input type="hidden" name="redirect_element_type_ids[]" value="{{ $value }}">
            @endforeach
            @foreach(($activeFilters['diagnostic_names'] ?? []) as $value)
                <input type="hidden" name="redirect_diagnostic_names[]" value="{{ $value }}">
            @endforeach
            @foreach(($activeFilters['statuses'] ?? []) as $value)
                <input type="hidden" name="redirect_statuses[]" value="{{ $value }}">
            @endforeach
            <input type="hidden" name="redirect_page" value="{{ $diagnostics->currentPage() }}">

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
    const filterOptions = {
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

        if (modal.classList.contains('flex') && event.target === modal) {
            closeEditDiagnosticModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeFilterPopover();
            closeEditDiagnosticModal();
        }
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

        insertDiagnosticRow(data.diagnostic);
        resetCreateDiagnosticForm();

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

        updateDiagnosticRow(data.diagnostic);
        closeEditDiagnosticModal();

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

        if (row) {
            row.style.transition = 'opacity 180ms ease, transform 180ms ease';
            row.style.opacity = '0';
            row.style.transform = 'scale(0.98)';

            setTimeout(() => row.remove(), 180);
        }

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

        const data = await parseDiagnosticJsonResponse(response);

        if (!response.ok || data.success === false) {
            throw new Error(data.message || 'No fue posible cambiar el estado.');
        }

        renderDiagnosticStatusButton(button, Boolean(data.status));
        showDiagnosticToast(data.message || 'Estado actualizado correctamente.', 'success');
    } catch (error) {
        button.innerHTML = originalHtml;
        button.className = originalClass;
        showDiagnosticToast(error.message || 'Ocurrió un error al cambiar el estado.', 'error');
    } finally {
        button.disabled = false;
        button.classList.remove('opacity-60', 'cursor-wait');

        if (window.lucide) {
            window.lucide.createIcons();
        }
    }
}

function renderDiagnosticStatusButton(button, enabled) {
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

function updateDiagnosticRow(diagnostic) {
    if (!diagnostic || !diagnostic.id) return;

    const row = document.getElementById(`diagnostic-row-${diagnostic.id}`);
    const clientEl = document.getElementById(`diagnostic-client-${diagnostic.id}`);
    const elementTypeEl = document.getElementById(`diagnostic-element-type-${diagnostic.id}`);
    const nameEl = document.getElementById(`diagnostic-name-${diagnostic.id}`);
    const editButton = row?.querySelector('[data-edit-diagnostic]');
    const statusButton = row?.querySelector('[data-status-toggle]');

    if (clientEl) clientEl.textContent = diagnostic.client_name ?? '—';
    if (elementTypeEl) elementTypeEl.textContent = diagnostic.element_type_name ?? '—';
    if (nameEl) nameEl.textContent = diagnostic.name ?? '—';

    if (editButton) {
        editButton.dataset.client_id = diagnostic.client_id ?? '';
        editButton.dataset.element_type_id = diagnostic.element_type_id ?? '';
        editButton.dataset.name = diagnostic.name ?? '';
        editButton.dataset.status = diagnostic.status ? '1' : '0';
        editButton.dataset.action = diagnostic.update_url ?? '';
    }

    if (statusButton) {
        statusButton.dataset.url = diagnostic.toggle_status_url ?? statusButton.dataset.url;
        renderDiagnosticStatusButton(statusButton, Boolean(diagnostic.status));
    }
}

function insertDiagnosticRow(diagnostic) {
    if (!diagnostic || !diagnostic.id) return;

    const tbody = document.getElementById('diagnosticsTableBody');

    if (!tbody) return;

    const emptyRow = tbody.querySelector('td[colspan]');
    if (emptyRow) {
        emptyRow.closest('tr')?.remove();
    }

    const hasClientColumn = @json($showClientColumn);
    const hasDependencies =
        (Number(diagnostic.components_count || 0) + Number(diagnostic.report_details_count || 0)) > 0;

    const row = document.createElement('tr');
    row.id = `diagnostic-row-${diagnostic.id}`;
    row.className = 'hover:bg-slate-50';

    row.innerHTML = `
        ${hasClientColumn ? `
            <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700" id="diagnostic-client-${diagnostic.id}">
                ${escapeHtml(diagnostic.client_name ?? '—')}
            </td>
        ` : ''}

        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700" id="diagnostic-element-type-${diagnostic.id}">
            ${escapeHtml(diagnostic.element_type_name ?? '—')}
        </td>

        <td class="whitespace-nowrap px-5 py-3 text-sm font-medium text-slate-900" id="diagnostic-name-${diagnostic.id}">
            ${escapeHtml(diagnostic.name ?? '—')}
        </td>

        <td class="whitespace-nowrap px-5 py-3 text-sm">
            <button
                type="button"
                data-status-toggle
                data-url="${escapeHtml(diagnostic.toggle_status_url ?? '')}"
                data-enabled="${diagnostic.status ? '1' : '0'}"
                onclick="toggleDiagnosticStatus(this)"
                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition ${diagnostic.status
                    ? 'bg-green-100 text-green-700 hover:bg-green-200'
                    : 'bg-red-100 text-red-700 hover:bg-red-200'}"
                title="Clic para activar o inactivar"
            >
                <i data-lucide="${diagnostic.status ? 'check-circle-2' : 'x-circle'}" class="h-3.5 w-3.5"></i>
                <span>${diagnostic.status ? 'Activo' : 'Inactivo'}</span>
            </button>
        </td>

        <td class="whitespace-nowrap px-5 py-3 text-right">
            <div class="flex items-center justify-end gap-2">
                <button
                    type="button"
                    data-edit-diagnostic
                    data-id="${escapeHtml(String(diagnostic.id))}"
                    data-client_id="${escapeHtml(String(diagnostic.client_id ?? ''))}"
                    data-element_type_id="${escapeHtml(String(diagnostic.element_type_id ?? ''))}"
                    data-name="${escapeHtml(diagnostic.name ?? '')}"
                    data-status="${diagnostic.status ? '1' : '0'}"
                    data-action="${escapeHtml(diagnostic.update_url ?? '')}"
                    onclick="openEditDiagnosticModal(this)"
                    class="text-slate-400 transition hover:text-[#d94d33]"
                    title="Editar diagnóstico"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.862 4.487l1.651-1.651a2.121 2.121 0 113 3l-1.651 1.651M4 20h4l10.586-10.586a2 2 0 00-2.828-2.828L5.172 17.172A2 2 0 004 18.586V20z" />
                    </svg>
                </button>

                ${!hasDependencies ? `
                    <button
                        type="button"
                        onclick="deleteDiagnostic(${diagnostic.id})"
                        class="text-red-500 transition hover:text-red-700"
                        title="Eliminar diagnóstico"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M6 7h12M9 7V4h6v3M10 11v6M14 11v6M5 7l1 13a2 2 0 002 2h8a2 2 0 002-2l1-13" />
                        </svg>
                    </button>

                    <form
                        id="delete-diagnostic-form-${diagnostic.id}"
                        method="POST"
                        action="${escapeHtml(diagnostic.destroy_url ?? '')}"
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
