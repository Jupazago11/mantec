@extends('layouts.admin')

@section('title', 'Condiciones')
@section('header_title', 'Condiciones')

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
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Gestión de condiciones</h2>
            <p class="mt-2 text-slate-600">
                Crea y administra condiciones para los clientes que tienes asignados.
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
<div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Nueva condición</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Registra una nueva condición para uno de tus clientes.
                    </p>

                    <form method="POST" action="{{ route('admin.managed-conditions.store') }}" class="mt-6 space-y-5">
                        @csrf

                        @if($showClientColumn)
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
                        @else
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Cliente</label>
                                <div class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                    {{ $singleClient->name }}
                                </div>
                                <input type="hidden" name="client_id" value="{{ $singleClient->id }}">
                            </div>
                        @endif

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Código</label>
                            <input
                                type="text"
                                name="code"
                                value="{{ old('code') }}"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                placeholder="Ej. PF1"
                            >
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Nombre</label>
                            <input
                                type="text"
                                name="name"
                                value="{{ old('name') }}"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                placeholder="Ej. Probabilidad de falla alta"
                            >
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Descripción</label>
                            <textarea
                                name="description"
                                rows="4"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                placeholder="Descripción opcional de la condición"
                            >{{ old('description') }}</textarea>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Severidad</label>
                            <input
                                type="number"
                                name="severity"
                                value="{{ old('severity') }}"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                placeholder="Ej. 1"
                                min="0"
                            >
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Color</label>
                            <div class="flex items-center gap-3">
                                <input
                                    type="color"
                                    name="color"
                                    value="{{ old('color', '#ff0000') }}"
                                    class="h-12 w-20 cursor-pointer rounded-lg border border-slate-300 bg-white p-1"
                                    id="create_color_input"
                                >
                                <input
                                    type="text"
                                    value="{{ old('color', '#ff0000') }}"
                                    readonly
                                    class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700"
                                    id="create_color_preview"
                                >
                            </div>
                        </div>

                        @foreach(($activeFilters['client_ids'] ?? []) as $value)
                            <input type="hidden" name="redirect_client_ids[]" value="{{ $value }}">
                        @endforeach
                        @foreach(($activeFilters['codes'] ?? []) as $value)
                            <input type="hidden" name="redirect_codes[]" value="{{ $value }}">
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
                            Guardar condición
                        </button>
                    </form>
                </div>
            </div>
<div>
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <div class="flex items-center justify-between gap-4">
                            <h3 class="text-lg font-semibold text-slate-900">Listado de condiciones</h3>

                            @if($hasAnyActiveFilter)
                                <a
                                    href="{{ route('admin.managed-conditions.index') }}"
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
                                        Descripción
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Severidad
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Uso
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
                                @forelse($conditions as $condition)
                                    @php
                                        $hasDependencies = ($condition->report_details_count ?? 0) > 0;
                                    @endphp

                                    <tr class="hover:bg-slate-50">
                                        @if($showClientColumn)
                                            <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700">
                                                {{ $condition->client?->name ?? '—' }}
                                            </td>
                                        @endif

                                        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700">
                                            {{ $condition->code }}
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-3 text-sm font-medium text-slate-900">
                                            <div class="flex items-center gap-2">
                                                <span class="inline-block h-5 w-5 rounded-full border border-slate-300"
                                                      style="background-color: {{ $condition->color ?? '#ffffff' }};"></span>
                                                <span>{{ $condition->name }}</span>
                                            </div>
                                        </td>

                                        <td class="px-5 py-3 text-sm text-slate-600">
                                            {{ $condition->description ?: '—' }}
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700">
                                            {{ $condition->severity }}
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700">
                                            {{ $condition->report_details_count }}
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-3 text-sm">
                                            @if($condition->status)
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
                                                    data-code="{{ $condition->code }}"
                                                    data-name="{{ $condition->name }}"
                                                    data-description="{{ $condition->description }}"
                                                    data-severity="{{ $condition->severity }}"
                                                    data-color="{{ $condition->color }}"
                                                    data-client_id="{{ $condition->client_id }}"
                                                    data-action="{{ route('admin.managed-conditions.update', $condition) }}"
                                                    onclick="openEditConditionModal(this)"
                                                >
                                                    Editar
                                                </button>

                                                @if(!$hasDependencies)
                                                    <form
                                                        method="POST"
                                                        action="{{ route('admin.managed-conditions.destroy', $condition) }}"
                                                        onsubmit="return confirm('¿Seguro que deseas eliminar esta condición?');"
                                                    >
                                                        @csrf
                                                        @method('DELETE')

                                                        @foreach(($activeFilters['client_ids'] ?? []) as $value)
                                                            <input type="hidden" name="redirect_client_ids[]" value="{{ $value }}">
                                                        @endforeach
                                                        @foreach(($activeFilters['codes'] ?? []) as $value)
                                                            <input type="hidden" name="redirect_codes[]" value="{{ $value }}">
                                                        @endforeach
                                                        @foreach(($activeFilters['names'] ?? []) as $value)
                                                            <input type="hidden" name="redirect_names[]" value="{{ $value }}">
                                                        @endforeach
                                                        @foreach(($activeFilters['statuses'] ?? []) as $value)
                                                            <input type="hidden" name="redirect_statuses[]" value="{{ $value }}">
                                                        @endforeach
                                                        <input type="hidden" name="redirect_page" value="{{ $conditions->currentPage() }}">

                                                        <button
                                                            type="submit"
                                                            class="rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-red-700"
                                                        >
                                                            Eliminar
                                                        </button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('admin.managed-conditions.toggle-status', $condition) }}">
                                                        @csrf
                                                        @method('PATCH')

                                                        @foreach(($activeFilters['client_ids'] ?? []) as $value)
                                                            <input type="hidden" name="redirect_client_ids[]" value="{{ $value }}">
                                                        @endforeach
                                                        @foreach(($activeFilters['codes'] ?? []) as $value)
                                                            <input type="hidden" name="redirect_codes[]" value="{{ $value }}">
                                                        @endforeach
                                                        @foreach(($activeFilters['names'] ?? []) as $value)
                                                            <input type="hidden" name="redirect_names[]" value="{{ $value }}">
                                                        @endforeach
                                                        @foreach(($activeFilters['statuses'] ?? []) as $value)
                                                            <input type="hidden" name="redirect_statuses[]" value="{{ $value }}">
                                                        @endforeach
                                                        <input type="hidden" name="redirect_page" value="{{ $conditions->currentPage() }}">

                                                        <button
                                                            type="submit"
                                                            class="rounded-lg px-3 py-2 text-xs font-semibold text-white transition {{ $condition->status ? 'bg-amber-500 hover:bg-amber-600' : 'bg-green-600 hover:bg-green-700' }}"
                                                        >
                                                            {{ $condition->status ? 'Inactivar' : 'Activar' }}
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $showClientColumn ? 8 : 7 }}" class="px-5 py-10 text-center text-sm text-slate-500">
                                            No hay condiciones registradas todavía.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($conditions->hasPages())
                        <div class="border-t border-slate-200 px-6 py-4">
                            {{ $conditions->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
<div id="editConditionModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
        <div class="w-full max-w-3xl rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Editar condición</h3>
                <button type="button" class="text-slate-500 hover:text-slate-900" onclick="closeEditConditionModal()">✕</button>
            </div>

            <form id="editConditionForm" method="POST" class="space-y-5 p-6">
                @csrf
                @method('PUT')

                @if($showClientColumn)
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
                @else
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Cliente</label>
                        <div class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            {{ $singleClient->name }}
                        </div>
                        <input type="hidden" name="client_id" value="{{ $singleClient->id }}" id="edit_selected_client_id">
                    </div>
                @endif

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Código</label>
                    <input
                        type="text"
                        name="code"
                        id="edit_condition_code"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                    >
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Nombre</label>
                    <input
                        type="text"
                        name="name"
                        id="edit_condition_name"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                    >
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Descripción</label>
                    <textarea
                        name="description"
                        id="edit_condition_description"
                        rows="4"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                    ></textarea>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Severidad</label>
                    <input
                        type="number"
                        name="severity"
                        id="edit_condition_severity"
                        min="0"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                    >
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Color</label>
                    <div class="flex items-center gap-3">
                        <input
                            type="color"
                            name="color"
                            id="edit_condition_color"
                            class="h-12 w-20 cursor-pointer rounded-lg border border-slate-300 bg-white p-1"
                        >
                        <input
                            type="text"
                            readonly
                            class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700"
                            id="edit_color_preview"
                        >
                    </div>
                </div>

                @foreach(($activeFilters['client_ids'] ?? []) as $value)
                    <input type="hidden" name="redirect_client_ids[]" value="{{ $value }}">
                @endforeach
                @foreach(($activeFilters['codes'] ?? []) as $value)
                    <input type="hidden" name="redirect_codes[]" value="{{ $value }}">
                @endforeach
                @foreach(($activeFilters['names'] ?? []) as $value)
                    <input type="hidden" name="redirect_names[]" value="{{ $value }}">
                @endforeach
                @foreach(($activeFilters['statuses'] ?? []) as $value)
                    <input type="hidden" name="redirect_statuses[]" value="{{ $value }}">
                @endforeach
                <input type="hidden" name="redirect_page" value="{{ $conditions->currentPage() }}">

                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        onclick="closeEditConditionModal()"
                        class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                    >
                        Actualizar condición
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
        codes: {
            type: 'checklist',
            title: 'Código',
            inputName: 'codes',
            options: @json($filterOptions['codes']),
        },
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

    function syncColorPreview(inputId, previewId) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);

        if (input && preview) {
            preview.value = input.value;
        }
    }

    function openEditConditionModal(btn) {
        document.getElementById('editConditionForm').action = btn.dataset.action;
        document.getElementById('edit_condition_code').value = btn.dataset.code ?? '';
        document.getElementById('edit_condition_name').value = btn.dataset.name ?? '';
        document.getElementById('edit_condition_description').value = btn.dataset.description ?? '';
        document.getElementById('edit_condition_severity').value = btn.dataset.severity ?? '';
        document.getElementById('edit_condition_color').value = btn.dataset.color ?? '#ff0000';
        document.getElementById('edit_color_preview').value = btn.dataset.color ?? '#ff0000';

        const clientId = btn.dataset.client_id ?? '';
        const hiddenClientInput = document.getElementById('edit_selected_client_id');
        if (hiddenClientInput) {
            hiddenClientInput.value = clientId;
        }

        document.querySelectorAll('.edit-client-single-checkbox').forEach(cb => {
            cb.checked = parseInt(cb.value) === parseInt(clientId);
        });

        const modal = document.getElementById('editConditionModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeEditConditionModal() {
        const modal = document.getElementById('editConditionModal');
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

        const createColorInput = document.getElementById('create_color_input');
        const editColorInput = document.getElementById('edit_condition_color');

        if (createColorInput) {
            createColorInput.addEventListener('input', function () {
                syncColorPreview('create_color_input', 'create_color_preview');
            });
            syncColorPreview('create_color_input', 'create_color_preview');
        }

        if (editColorInput) {
            editColorInput.addEventListener('input', function () {
                syncColorPreview('edit_condition_color', 'edit_color_preview');
            });
        }
    });

    document.addEventListener('click', function (event) {
        const popover = document.getElementById('filterPopover');
        const modal = document.getElementById('editConditionModal');

        if (!popover.classList.contains('hidden')) {
            if (!popover.contains(event.target) && !event.target.closest('button[onclick^="openFilterPopover"]')) {
                closeFilterPopover();
            }
        }

        if (modal.classList.contains('flex') && event.target === modal) {
            closeEditConditionModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeFilterPopover();
            closeEditConditionModal();
        }
    });
</script>
@endsection
