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
                Crea y administra condiciones por cliente y tipo de activo.
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
                        Registra una nueva condición para uno de tus clientes y un tipo de activo específico.
                    </p>
                    <div id="createConditionAjaxErrors" class="hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>
                    <form id="createConditionForm" method="POST" action="{{ route('admin.managed-conditions.store') }}" class="mt-6 space-y-5">
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
                                                @checked((string) old('client_id', $preferredClientId ?? '') === (string) $client->id)
                                                onchange="handleSingleClientSelection(this)"
                                            >
                                            {{ $client->name }}
                                        </label>
                                    @endforeach
                                </div>
                                <input type="hidden" name="client_id" id="selected_client_id" value="{{ old('client_id', $preferredClientId ?? '') }}">
                                @error('client_id')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @else
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Cliente</label>
                                <div class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                    {{ $singleClient->name }}
                                </div>
                                <input type="hidden" name="client_id" value="{{ $singleClient->id }}" id="selected_client_id">
                            </div>
                        @endif

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Tipo de activo</label>
                            <select
                                name="element_type_id"
                                id="create_element_type_id"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
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
                            <label class="mb-2 block text-sm font-medium text-slate-700">Código</label>
                            <input
                                type="text"
                                name="code"
                                value="{{ old('code') }}"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                placeholder="Ej. PF1"
                            >
                            @error('code')
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
                                placeholder="Ej. Probabilidad de falla alta"
                            >
                            @error('name')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Descripción</label>
                            <textarea
                                name="description"
                                rows="4"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                placeholder="Descripción opcional de la condición"
                            >{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <div class="flex items-center gap-2">
                                <label class="block text-sm font-medium text-slate-700">
                                    Criticidad
                                </label>

                                <div class="relative group">
                                    <!-- Icono -->
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="h-4 w-4 text-slate-400 cursor-pointer"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z" />
                                    </svg>

                                    <!-- Tooltip -->
                                    <div class="pointer-events-none absolute left-0 top-6 z-10 w-64 rounded-xl border border-slate-200 bg-white p-3 text-xs text-slate-600 shadow-lg opacity-0 transition group-hover:opacity-100">
                                        <p><strong>0:</strong> Condición informativa (no afecta indicadores).</p>
                                        <p class="mt-1"><strong>1 en adelante:</strong> Se utiliza para cálculo de indicadores y niveles de criticidad.</p>
                                    </div>
                                </div>
                            </div>
                            <input
                                type="number"
                                name="severity"
                                value="{{ old('severity') }}"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                placeholder="Ej. 0"
                                min="0"
                            >
                            @error('severity')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
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
                            @error('color')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        @foreach(($activeFilters['client_ids'] ?? []) as $value)
                            <input type="hidden" name="redirect_client_ids[]" value="{{ $value }}">
                        @endforeach
                        @foreach(($activeFilters['element_type_ids'] ?? []) as $value)
                            <input type="hidden" name="redirect_element_type_ids[]" value="{{ $value }}">
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
                                        Criticidad
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

                            <tbody id="conditionsTableBody" class="divide-y divide-slate-200 bg-white">
                                @forelse($conditions as $condition)
                                    @php
                                        $hasDependencies = ($condition->report_details_count ?? 0) > 0;
                                    @endphp

                                    <tr class="hover:bg-slate-50" id="condition-row-{{ $condition->id }}">
                                        @if($showClientColumn)
                                            <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700">
                                                {{ $condition->client?->name ?? '—' }}
                                            </td>
                                        @endif

                                        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700" id="condition-element-type-{{ $condition->id }}">
                                            {{ $condition->elementType?->name ?? '—' }}
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700" id="condition-code-{{ $condition->id }}">
                                            {{ $condition->code }}
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-3 text-sm font-medium text-slate-900">
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="inline-block h-5 w-5 rounded-full border border-slate-300"
                                                    id="condition-color-dot-{{ $condition->id }}"
                                                    style="background-color: {{ $condition->color ?? '#ffffff' }};"
                                                ></span>
                                                <span id="condition-name-{{ $condition->id }}">{{ $condition->name }}</span>
                                            </div>
                                        </td>

                                        <td class="px-5 py-3 text-sm text-slate-600" id="condition-description-{{ $condition->id }}">
                                            {{ $condition->description ?: '—' }}
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700" id="condition-severity-{{ $condition->id }}">
                                            {{ $condition->severity }}
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700">
                                            {{ $condition->report_details_count }}
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-3 text-sm">
                                            <button
                                                type="button"
                                                onclick="toggleConditionStatus({{ $condition->id }})"
                                                class="inline-flex rounded-full px-3 py-1 text-xs font-semibold transition {{ $condition->status ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-red-100 text-red-700 hover:bg-red-200' }}"
                                                id="condition-status-badge-{{ $condition->id }}"
                                                title="Cambiar estado"
                                            >
                                                {{ $condition->status ? 'Activo' : 'Inactivo' }}
                                            </button>
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-3 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <button
                                                    type="button"
                                                    class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-100"
                                                    onclick="openComponentConditionModal(
                                                        '{{ $condition->id }}',
                                                        @js($condition->client?->name ?? '—'),
                                                        @js($condition->elementType?->name ?? '—'),
                                                        @js($condition->name)
                                                    )"
                                                >
                                                    Componentes
                                                </button>
                                                <button
                                                    type="button"
                                                    id="condition-edit-button-{{ $condition->id }}"
                                                    class="text-slate-400 transition hover:text-[#d94d33]"
                                                    data-code="{{ $condition->code }}"
                                                    data-name="{{ $condition->name }}"
                                                    data-description="{{ $condition->description }}"
                                                    data-severity="{{ $condition->severity }}"
                                                    data-color="{{ $condition->color }}"
                                                    data-client_id="{{ $condition->client_id }}"
                                                    data-element_type_id="{{ $condition->element_type_id }}"
                                                    data-action="{{ route('admin.managed-conditions.update', $condition) }}"
                                                    onclick="openEditConditionModal(this)"
                                                    title="Editar condición"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M16.862 4.487l1.651-1.651a2.121 2.121 0 113 3l-1.651 1.651M4 20h4l10.586-10.586a2 2 0 00-2.828-2.828L5.172 17.172A2 2 0 004 18.586V20z" />
                                                    </svg>
                                                </button>

                                                @if(!$hasDependencies)
                                                    <button
                                                        type="button"
                                                        onclick="deleteCondition({{ $condition->id }})"
                                                        class="text-red-500 transition hover:text-red-700"
                                                        title="Eliminar condición"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M6 7h12M9 7V4h6v3M10 11v6M14 11v6M5 7l1 13a2 2 0 002 2h8a2 2 0 002-2l1-13" />
                                                        </svg>
                                                    </button>

                                                    <form
                                                        id="delete-condition-form-{{ $condition->id }}"
                                                        method="POST"
                                                        action="{{ route('admin.managed-conditions.destroy', $condition) }}"
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
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $showClientColumn ? 9 : 8 }}" class="px-5 py-10 text-center text-sm text-slate-500">
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

<div id="editConditionModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 px-4 py-6 backdrop-blur-sm">
    <div id="editConditionModalContent" class="max-h-[92vh] w-full max-w-4xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl scale-95 opacity-0 transition duration-200 ease-out">
        
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">

            <button
                type="button"
                class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                onclick="closeEditConditionModal()"
            >
                ✕
            </button>
        </div>

        <form id="editConditionForm" method="POST">
            <div id="editConditionAjaxErrors" class="hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>
            @csrf
            @method('PUT')

            <div class="px-6 py-5">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    @if($showClientColumn)
                        <div class="md:col-span-2">
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Cliente</label>
                            <div class="max-h-44 space-y-2 overflow-y-auto rounded-2xl border border-slate-300 bg-white p-4">
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
                        <div class="md:col-span-2">
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Cliente</label>
                            <div class="w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                {{ $singleClient->name }}
                            </div>
                            <input type="hidden" name="client_id" value="{{ $singleClient->id }}" id="edit_selected_client_id">
                        </div>
                    @endif

                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Tipo de activo</label>
                        <select
                            name="element_type_id"
                            id="edit_element_type_id"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm outline-none transition focus:border-[#d94d33] focus:ring-2 focus:ring-[#d94d33]/20"
                        >
                            @foreach($elementTypes as $elementType)
                                <option
                                    value="{{ $elementType->id }}"
                                    data-client-id="{{ $elementType->client_id }}"
                                >
                                    {{ $elementType->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Código</label>
                        <input
                            type="text"
                            name="code"
                            id="edit_condition_code"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm outline-none transition focus:border-[#d94d33] focus:ring-2 focus:ring-[#d94d33]/20"
                        >
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Nombre</label>
                        <input
                            type="text"
                            name="name"
                            id="edit_condition_name"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm outline-none transition focus:border-[#d94d33] focus:ring-2 focus:ring-[#d94d33]/20"
                        >
                    </div>

                    <div>
                        <div class="mb-1.5 flex items-center gap-2">
                            <label class="block text-sm font-semibold text-slate-700">Criticidad</label>

                            <div class="relative group">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-4 w-4 cursor-pointer text-slate-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z" />
                                </svg>

                                <div class="pointer-events-none absolute left-0 top-6 z-10 w-64 rounded-xl border border-slate-200 bg-white p-3 text-xs text-slate-600 shadow-lg opacity-0 transition group-hover:opacity-100">
                                    <p><strong>0:</strong> Condición informativa.</p>
                                    <p class="mt-1"><strong>1 en adelante:</strong> Participa en indicadores y criticidad.</p>
                                </div>
                            </div>
                        </div>

                        <input
                            type="number"
                            name="severity"
                            id="edit_condition_severity"
                            min="0"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm outline-none transition focus:border-[#d94d33] focus:ring-2 focus:ring-[#d94d33]/20"
                        >
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Color</label>
                        <div class="flex items-center gap-3">
                            <input
                                type="color"
                                name="color"
                                id="edit_condition_color"
                                class="h-11 w-16 cursor-pointer rounded-xl border border-slate-300 bg-white p-1"
                            >
                            <input
                                type="text"
                                readonly
                                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm text-slate-700"
                                id="edit_color_preview"
                            >
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Descripción</label>
                        <textarea
                            name="description"
                            id="edit_condition_description"
                            rows="4"
                            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm outline-none transition focus:border-[#d94d33] focus:ring-2 focus:ring-[#d94d33]/20"
                        ></textarea>
                    </div>
                </div>
            </div>

            @foreach(($activeFilters['client_ids'] ?? []) as $value)
                <input type="hidden" name="redirect_client_ids[]" value="{{ $value }}">
            @endforeach
            @foreach(($activeFilters['element_type_ids'] ?? []) as $value)
                <input type="hidden" name="redirect_element_type_ids[]" value="{{ $value }}">
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

            <div class="flex items-center justify-end gap-3 bg-slate-50 px-6 py-4">
                <button
                    type="button"
                    onclick="closeEditConditionModal()"
                    class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                >
                    Cancelar
                </button>

                <button
                    type="submit"
                    class="rounded-xl bg-[#d94d33] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
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
    <div id="componentConditionModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
        <div class="w-full max-w-3xl rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Asociar componentes</h3>
                <button type="button" class="text-slate-500 hover:text-slate-900" onclick="closeComponentConditionModal()">✕</button>
            </div>

            <form id="componentConditionForm" method="POST" class="space-y-5 p-6">
                @csrf

                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Cliente</div>
                        <div id="cc_client_name" class="mt-1 text-sm text-slate-900"></div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tipo de activo</div>
                        <div id="cc_element_type_name" class="mt-1 text-sm text-slate-900"></div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Condición</div>
                        <div id="cc_condition_name" class="mt-1 text-sm text-slate-900"></div>
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Componentes disponibles</label>
                    <div
                        id="cc_components_container"
                        class="grid max-h-[420px] gap-3 overflow-y-auto rounded-xl border border-slate-200 p-4 md:grid-cols-2"
                    ></div>
                </div>

                @foreach(($activeFilters['client_ids'] ?? []) as $value)
                    <input type="hidden" name="redirect_client_ids[]" value="{{ $value }}">
                @endforeach
                @foreach(($activeFilters['element_type_ids'] ?? []) as $value)
                    <input type="hidden" name="redirect_element_type_ids[]" value="{{ $value }}">
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
                        onclick="closeComponentConditionModal()"
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

    filterElementTypesByClient('create_element_type_id', clientId);
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

    filterElementTypesByClient('edit_element_type_id', clientId);
}

function filterElementTypesByClient(selectId, clientId) {
    const select = document.getElementById(selectId);
    if (!select) return;

    Array.from(select.options).forEach((option, index) => {
        if (index === 0 && selectId === 'create_element_type_id') {
            option.hidden = false;
            return;
        }

        const optionClientId = option.dataset.clientId ?? '';
        option.hidden = clientId !== '' && optionClientId !== String(clientId);
    });

    const selectedOption = select.options[select.selectedIndex];
    if (selectedOption && selectedOption.hidden) {
        select.value = '';
    }
}

function syncColorPreview(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);

    if (input && preview) {
        preview.value = input.value;
    }
}

function openEditConditionModal(btn) {
    clearAjaxErrors('editConditionAjaxErrors');

    document.getElementById('editConditionForm').action = btn.dataset.action;
    document.getElementById('edit_condition_code').value = btn.dataset.code ?? '';
    document.getElementById('edit_condition_name').value = btn.dataset.name ?? '';
    document.getElementById('edit_condition_description').value = btn.dataset.description ?? '';
    document.getElementById('edit_condition_severity').value = btn.dataset.severity ?? '';
    document.getElementById('edit_condition_color').value = btn.dataset.color ?? '#ff0000';
    document.getElementById('edit_color_preview').value = btn.dataset.color ?? '#ff0000';
    document.getElementById('edit_element_type_id').value = btn.dataset.element_type_id ?? '';

    const clientId = btn.dataset.client_id ?? '';
    const hiddenClientInput = document.getElementById('edit_selected_client_id');
    if (hiddenClientInput) {
        hiddenClientInput.value = clientId;
    }

    document.querySelectorAll('.edit-client-single-checkbox').forEach(cb => {
        cb.checked = parseInt(cb.value) === parseInt(clientId);
    });

    filterElementTypesByClient('edit_element_type_id', clientId);

    const modal = document.getElementById('editConditionModal');
    const content = document.getElementById('editConditionModalContent');

    modal.classList.remove('hidden');
    modal.classList.add('flex');

    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
}

function closeEditConditionModal() {
    const modal = document.getElementById('editConditionModal');
    const content = document.getElementById('editConditionModalContent');
    clearAjaxErrors('editConditionAjaxErrors');

    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');

    setTimeout(() => {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }, 150);
}

document.addEventListener('DOMContentLoaded', function () {
    const selectedClient = document.getElementById('selected_client_id');

    if (selectedClient && selectedClient.value) {
        document.querySelectorAll('.client-single-checkbox').forEach(cb => {
            cb.checked = parseInt(cb.value) === parseInt(selectedClient.value);
        });
    }

    filterElementTypesByClient('create_element_type_id', selectedClient?.value ?? '');

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
    const createConditionForm = document.getElementById('createConditionForm');
    const editConditionForm = document.getElementById('editConditionForm');

    if (createConditionForm) {
        createConditionForm.addEventListener('submit', handleCreateConditionSubmit);
    }

    if (editConditionForm) {
        editConditionForm.addEventListener('submit', handleEditConditionSubmit);
    }
});

document.addEventListener('click', function (event) {
    const popover = document.getElementById('filterPopover');
    const editModal = document.getElementById('editConditionModal');
    const componentModal = document.getElementById('componentConditionModal');

    if (!popover.classList.contains('hidden')) {
        if (!popover.contains(event.target) && !event.target.closest('button[onclick^="openFilterPopover"]')) {
            closeFilterPopover();
        }
    }

    if (editModal.classList.contains('flex') && event.target === editModal) {
        closeEditConditionModal();
    }

    if (componentModal.classList.contains('flex') && event.target === componentModal) {
        closeComponentConditionModal();
    }
});

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        closeFilterPopover();
        closeEditConditionModal();
        closeComponentConditionModal();
    }
});

async function openComponentConditionModal(conditionId, clientName, elementTypeName, conditionName) {
    const modal = document.getElementById('componentConditionModal');
    const form = document.getElementById('componentConditionForm');
    const container = document.getElementById('cc_components_container');

    document.getElementById('cc_client_name').textContent = clientName ?? '—';
    document.getElementById('cc_element_type_name').textContent = elementTypeName ?? '—';
    document.getElementById('cc_condition_name').textContent = conditionName ?? '—';

    form.action = `/admin/managed-conditions/${conditionId}/components`;

    container.innerHTML = `
        <div class="text-sm text-slate-500 md:col-span-2">Cargando componentes...</div>
    `;

    modal.classList.remove('hidden');
    modal.classList.add('flex');

    try {
        const response = await fetch(`/admin/managed-conditions/${conditionId}/components`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        });

        const data = await response.json();

        container.innerHTML = '';

        if (!data.components || data.components.length === 0) {
            container.innerHTML = `
                <div class="text-sm text-slate-500 md:col-span-2">No hay componentes disponibles.</div>
            `;
            return;
        }

        data.components.forEach(component => {
            const checked = data.assigned_ids.includes(component.id);

            const item = document.createElement('label');
            item.className = 'flex items-start gap-3 rounded-xl border border-slate-200 p-3 text-sm text-slate-700';

            item.innerHTML = `
                <input
                    type="checkbox"
                    name="component_ids[]"
                    value="${component.id}"
                    ${checked ? 'checked' : ''}
                    class="mt-0.5 rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                >
                <span>${escapeHtml(String(component.name))}</span>
            `;

            container.appendChild(item);
        });

    } catch (error) {
        console.error(error);
        container.innerHTML = `
            <div class="text-sm text-red-500 md:col-span-2">Error cargando componentes.</div>
        `;
    }
}

function closeComponentConditionModal() {
    const modal = document.getElementById('componentConditionModal');
    const container = document.getElementById('cc_components_container');

    if (container) {
        container.innerHTML = '';
    }

    modal.classList.remove('flex');
    modal.classList.add('hidden');
}

function showCrudToast(message, type = 'success') {
    const toastId = 'crudInlineToast';
    let toast = document.getElementById(toastId);

    if (!toast) {
        toast = document.createElement('div');
        toast.id = toastId;
        document.body.appendChild(toast);
    }

    toast.className =
        'fixed bottom-6 right-6 z-[80] rounded-2xl px-4 py-3 text-sm font-semibold shadow-xl transition ' +
        (type === 'success'
            ? 'border border-green-200 bg-green-100 text-green-700'
            : 'border border-red-200 bg-red-100 text-red-700');

    toast.textContent = message;
    toast.classList.remove('hidden');

    clearTimeout(window.__crudToastTimeout);
    window.__crudToastTimeout = setTimeout(() => {
        toast.classList.add('hidden');
    }, 2200);
}

async function toggleConditionStatus(conditionId) {
    const badge = document.getElementById(`condition-status-badge-${conditionId}`);
    if (!badge) return;

    const originalText = badge.textContent;
    const originalClass = badge.className;

    badge.textContent = 'Cambiando...';
    badge.classList.add('opacity-70', 'pointer-events-none');

    try {
        const response = await fetch(
            "{{ route('admin.managed-conditions.toggle-status-ajax', ['condition' => '__ID__']) }}".replace('__ID__', conditionId),
            {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({})
            }
        );

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'No fue posible cambiar el estado.');
        }

        badge.textContent = data.label;
        badge.className = 'inline-flex rounded-full px-3 py-1 text-xs font-semibold transition ' +
            (data.status
                ? 'bg-green-100 text-green-700 hover:bg-green-200'
                : 'bg-red-100 text-red-700 hover:bg-red-200');

        badge.setAttribute('onclick', `toggleConditionStatus(${conditionId})`);

        showCrudToast(data.message || 'Estado actualizado correctamente.', 'success');
    } catch (error) {
        badge.textContent = originalText;
        badge.className = originalClass;
        showCrudToast(error.message || 'Ocurrió un error al cambiar el estado.', 'error');
    } finally {
        badge.classList.remove('opacity-70', 'pointer-events-none');
    }
}

async function deleteCondition(conditionId) {
    const confirmed = confirm('¿Seguro que deseas eliminar esta condición?');
    if (!confirmed) return;

    const row = document.getElementById(`condition-row-${conditionId}`);
    const form = document.getElementById(`delete-condition-form-${conditionId}`);

    if (!form) {
        showCrudToast('No se encontró el formulario de eliminación.', 'error');
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

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'No fue posible eliminar la condición.');
        }

        if (row) {
            row.style.transition = 'opacity 180ms ease, transform 180ms ease';
            row.style.opacity = '0';
            row.style.transform = 'scale(0.98)';

            setTimeout(() => {
                row.remove();
            }, 180);
        }

        showCrudToast(data.message || 'Condición eliminada correctamente.', 'success');
    } catch (error) {
        if (row) {
            row.classList.remove('opacity-60', 'pointer-events-none');
        }

        showCrudToast(error.message || 'Ocurrió un error al eliminar la condición.', 'error');
    }
}

function renderAjaxErrors(containerId, errors) {
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

function clearAjaxErrors(containerId) {
    const box = document.getElementById(containerId);
    if (!box) return;

    box.classList.add('hidden');
    box.innerHTML = '';
}

function setFormSubmittingState(form, isSubmitting, loadingText = 'Guardando...') {
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


async function handleCreateConditionSubmit(event) {
    event.preventDefault();

    const form = event.currentTarget;
    clearAjaxErrors('createConditionAjaxErrors');
    setFormSubmittingState(form, true, 'Guardando...');

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

        const data = await response.json();

        if (response.status === 422) {
            renderAjaxErrors('createConditionAjaxErrors', data.errors || {});
            showCrudToast('Corrige los errores del formulario.', 'error');
            return;
        }

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'No fue posible crear la condición.');
        }

        insertConditionRow(data.condition);
        resetCreateConditionForm();

        showCrudToast(data.message || 'Condición creada correctamente.', 'success');
    } catch (error) {
        showCrudToast(error.message || 'Ocurrió un error al crear la condición.', 'error');
    } finally {
        setFormSubmittingState(form, false);
    }
}


async function handleEditConditionSubmit(event) {
    event.preventDefault();

    const form = event.currentTarget;
    clearAjaxErrors('editConditionAjaxErrors');
    setFormSubmittingState(form, true, 'Actualizando...');

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

        const data = await response.json();

        if (response.status === 422) {
            renderAjaxErrors('editConditionAjaxErrors', data.errors || {});
            showCrudToast('Corrige los errores del formulario.', 'error');
            return;
        }

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'No fue posible actualizar la condición.');
        }

        showCrudToast(data.message || 'Condición actualizada correctamente.', 'success');
        closeEditConditionModal();
        updateConditionRow(data.condition);
    } catch (error) {
        showCrudToast(error.message || 'Ocurrió un error al actualizar la condición.', 'error');
    } finally {
        setFormSubmittingState(form, false);
    }
}
function updateConditionRow(condition) {
    if (!condition || !condition.id) return;

    const codeEl = document.getElementById(`condition-code-${condition.id}`);
    const nameEl = document.getElementById(`condition-name-${condition.id}`);
    const descEl = document.getElementById(`condition-description-${condition.id}`);
    const severityEl = document.getElementById(`condition-severity-${condition.id}`);
    const colorDotEl = document.getElementById(`condition-color-dot-${condition.id}`);
    const elementTypeEl = document.getElementById(`condition-element-type-${condition.id}`);
    const editButton = document.getElementById(`condition-edit-button-${condition.id}`);

    if (codeEl) {
        codeEl.textContent = condition.code ?? '—';
    }

    if (nameEl) {
        nameEl.textContent = condition.name ?? '—';
    }

    if (descEl) {
        descEl.textContent =
            condition.description && String(condition.description).trim() !== ''
                ? condition.description
                : '—';
    }

    if (severityEl) {
        severityEl.textContent = condition.severity ?? '0';
    }

    if (colorDotEl) {
        colorDotEl.style.backgroundColor = condition.color || '#ffffff';
    }

    if (elementTypeEl) {
        elementTypeEl.textContent = condition.element_type_name ?? '—';
    }

    if (editButton) {
        editButton.dataset.code = condition.code ?? '';
        editButton.dataset.name = condition.name ?? '';
        editButton.dataset.description = condition.description ?? '';
        editButton.dataset.severity = condition.severity ?? '';
        editButton.dataset.color = condition.color ?? '#ff0000';
        editButton.dataset.client_id = condition.client_id ?? '';
        editButton.dataset.element_type_id = condition.element_type_id ?? '';
    }
}

function resetCreateConditionForm() {
    const form = document.getElementById('createConditionForm');
    if (!form) return;

    form.reset();
    clearAjaxErrors('createConditionAjaxErrors');

    const selectedClientInput = document.getElementById('selected_client_id');
    if (selectedClientInput) {
        selectedClientInput.value = '';
    }

    document.querySelectorAll('.client-single-checkbox').forEach(cb => {
        cb.checked = false;
    });

    const createElementType = document.getElementById('create_element_type_id');
    if (createElementType) {
        createElementType.value = '';
        filterElementTypesByClient('create_element_type_id', '');
    }

    const colorInput = document.getElementById('create_color_input');
    const colorPreview = document.getElementById('create_color_preview');
    if (colorInput) {
        colorInput.value = '#ff0000';
    }
    if (colorPreview) {
        colorPreview.value = '#ff0000';
    }
}

function insertConditionRow(condition) {
    if (!condition || !condition.id) return;

    const tbody = document.getElementById('conditionsTableBody');
    if (!tbody) return;

    const emptyRow = tbody.querySelector('td[colspan]');
    if (emptyRow) {
        emptyRow.closest('tr')?.remove();
    }

    const hasClientColumn = @json($showClientColumn);
    const canDelete = !(condition.report_details_count > 0);

    const row = document.createElement('tr');
    row.id = `condition-row-${condition.id}`;
    row.className = 'hover:bg-slate-50';

    row.innerHTML = `
        ${hasClientColumn ? `
            <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700">
                ${escapeHtml(condition.client_name ?? '—')}
            </td>
        ` : ''}

        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700" id="condition-element-type-${condition.id}">
            ${escapeHtml(condition.element_type_name ?? '—')}
        </td>

        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700" id="condition-code-${condition.id}">
            ${escapeHtml(condition.code ?? '—')}
        </td>

        <td class="whitespace-nowrap px-5 py-3 text-sm font-medium text-slate-900">
            <div class="flex items-center gap-2">
                <span
                    class="inline-block h-5 w-5 rounded-full border border-slate-300"
                    id="condition-color-dot-${condition.id}"
                    style="background-color: ${escapeHtml(condition.color || '#ffffff')};"
                ></span>
                <span id="condition-name-${condition.id}">${escapeHtml(condition.name ?? '—')}</span>
            </div>
        </td>

        <td class="px-5 py-3 text-sm text-slate-600" id="condition-description-${condition.id}">
            ${condition.description && String(condition.description).trim() !== ''
                ? escapeHtml(condition.description)
                : '—'}
        </td>

        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700" id="condition-severity-${condition.id}">
            ${escapeHtml(String(condition.severity ?? 0))}
        </td>

        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700">
            ${escapeHtml(String(condition.report_details_count ?? 0))}
        </td>

        <td class="whitespace-nowrap px-5 py-3 text-sm">
            <button
                type="button"
                onclick="toggleConditionStatus(${condition.id})"
                class="inline-flex rounded-full px-3 py-1 text-xs font-semibold transition ${condition.status
                    ? 'bg-green-100 text-green-700 hover:bg-green-200'
                    : 'bg-red-100 text-red-700 hover:bg-red-200'}"
                id="condition-status-badge-${condition.id}"
                title="Cambiar estado"
            >
                ${condition.status ? 'Activo' : 'Inactivo'}
            </button>
        </td>

        <td class="whitespace-nowrap px-5 py-3 text-right">
            <div class="flex items-center justify-end gap-2">
                <button
                    type="button"
                    class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-100"
                    onclick="openComponentConditionModal(
                        '${condition.id}',
                        ${JSON.stringify(condition.client_name ?? '—')},
                        ${JSON.stringify(condition.element_type_name ?? '—')},
                        ${JSON.stringify(condition.name ?? '—')}
                    )"
                >
                    Componentes
                </button>

                <button
                    type="button"
                    id="condition-edit-button-${condition.id}"
                    class="text-slate-400 transition hover:text-[#d94d33]"
                    data-code="${escapeHtml(condition.code ?? '')}"
                    data-name="${escapeHtml(condition.name ?? '')}"
                    data-description="${escapeHtml(condition.description ?? '')}"
                    data-severity="${escapeHtml(String(condition.severity ?? ''))}"
                    data-color="${escapeHtml(condition.color ?? '#ff0000')}"
                    data-client_id="${escapeHtml(String(condition.client_id ?? ''))}"
                    data-element_type_id="${escapeHtml(String(condition.element_type_id ?? ''))}"
                    data-action="${escapeHtml(condition.update_url ?? '')}"
                    onclick="openEditConditionModal(this)"
                    title="Editar condición"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.862 4.487l1.651-1.651a2.121 2.121 0 113 3l-1.651 1.651M4 20h4l10.586-10.586a2 2 0 00-2.828-2.828L5.172 17.172A2 2 0 004 18.586V20z" />
                    </svg>
                </button>

                ${canDelete ? `
                    <button
                        type="button"
                        onclick="deleteCondition(${condition.id})"
                        class="text-red-500 transition hover:text-red-700"
                        title="Eliminar condición"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M6 7h12M9 7V4h6v3M10 11v6M14 11v6M5 7l1 13a2 2 0 002 2h8a2 2 0 002-2l1-13" />
                        </svg>
                    </button>

                    <form
                        id="delete-condition-form-${condition.id}"
                        method="POST"
                        action="${escapeHtml(condition.destroy_url ?? '')}"
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
}
</script>
@endsection
