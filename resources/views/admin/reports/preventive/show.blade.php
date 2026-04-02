<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reportes preventivos</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-900">
    @php
        $hasFilter = function ($key) use ($activeFilters) {
            $value = $activeFilters[$key] ?? null;

            if (is_array($value)) {
                return count(array_filter($value, fn ($item) => $item !== null && $item !== '')) > 0;
            }

            return $value !== null && $value !== '';
        };
    @endphp

    <div class="min-h-screen p-4">
        <div class="mx-auto max-w-[1800px] space-y-4">

            @php
                $hasAnyActiveFilter =
                    collect($activeFilters)->contains(function ($value) {
                        if (is_array($value)) {
                            return count(array_filter($value, fn ($item) => $item !== null && $item !== '')) > 0;
                        }

                        return $value !== null && $value !== '';
                    });
            @endphp

            <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                        Reporte preventivo {{ $elementType->name }} Planta {{ $client->name }} {{ $currentYear }}
                    </h1>

                    @if($hasAnyActiveFilter)
                        <a
                            href="{{ route('admin.preventive-reports.show', [$client->id, $elementType->id]) }}"
                            class="inline-flex items-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                        >
                            Limpiar filtros
                        </a>
                    @endif
                </div>
            </div>

            <form id="filtersForm" method="GET" class="hidden"></form>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    <div class="flex items-center gap-2">
                                        <span>Nombre del activo</span>
                                        <button type="button"
                                            onclick="openFilterPopover(event, 'element_names')"
                                            class="rounded p-1 transition hover:bg-slate-200 {{ $hasFilter('element_names') ? 'text-[#d94d33]' : 'text-slate-400' }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </th>

                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    <div class="flex items-center gap-2">
                                        <span>ID almacén</span>
                                        <button type="button"
                                            onclick="openFilterPopover(event, 'warehouse_ids')"
                                            class="rounded p-1 transition hover:bg-slate-200 text-slate-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </th>

                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    <div class="flex items-center gap-2">
                                        <span>Diagnóstico</span>
                                        <button type="button"
                                            onclick="openFilterPopover(event, 'diagnostic_pairs')"
                                            class="rounded p-1 transition hover:bg-slate-200 {{ $hasFilter('diagnostic_pairs') ? 'text-[#d94d33]' : 'text-slate-400' }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </th>

                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    <div class="flex items-center gap-2">
                                        <span>Recomendación</span>
                                        <button type="button"
                                            onclick="openFilterPopover(event, 'recommendation_values')"
                                            class="rounded p-1 transition hover:bg-slate-200 {{ $hasFilter('recommendation_values') ? 'text-[#d94d33]' : 'text-slate-400' }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </th>

                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Evidencia
                                </th>

                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    <div class="flex items-center gap-2">
                                        <span>Condición</span>
                                        <button type="button"
                                            onclick="openFilterPopover(event, 'condition_codes')"
                                            class="rounded p-1 transition hover:bg-slate-200 {{ $hasFilter('condition_codes') ? 'text-[#d94d33]' : 'text-slate-400' }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </th>

                                <th class="w-[90px] px-2 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    <div class="flex items-center gap-2">
                                        <span>Orden</span>
                                        <button type="button"
                                            onclick="openFilterPopover(event, 'orden_values')"
                                            class="rounded p-1 transition hover:bg-slate-200 {{ $hasFilter('orden_values') ? 'text-[#d94d33]' : 'text-slate-400' }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </th>

                                <th class="w-[90px] px-2 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    <div class="flex items-center gap-2">
                                        <span>Aviso</span>
                                        <button type="button"
                                            onclick="openFilterPopover(event, 'aviso_values')"
                                            class="rounded p-1 transition hover:bg-slate-200 {{ $hasFilter('aviso_values') ? 'text-[#d94d33]' : 'text-slate-400' }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </th>

                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    <div class="flex items-center gap-2">
                                        <span>Responsable</span>
                                        <button type="button"
                                            onclick="openFilterPopover(event, 'responsable_names')"
                                            class="rounded p-1 transition hover:bg-slate-200 {{ $hasFilter('responsable_names') ? 'text-[#d94d33]' : 'text-slate-400' }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </th>

                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 leading-4">
                                    <div class="flex items-center gap-2">
                                        <span>Fecha de reporte</span>
                                        <button type="button"
                                            onclick="openFilterPopover(event, 'report_date_range')"
                                            class="rounded p-1 transition hover:bg-slate-200 {{ $hasFilter('report_date_from') || $hasFilter('report_date_to') ? 'text-[#d94d33]' : 'text-slate-400' }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </th>

                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 leading-4">
                                    <div class="flex items-center gap-2">
                                        <span>Fecha de ejecución</span>
                                        <button type="button"
                                            onclick="openFilterPopover(event, 'execution_date_range')"
                                            class="rounded p-1 transition hover:bg-slate-200 {{ $hasFilter('execution_date_from') || $hasFilter('execution_date_to') ? 'text-[#d94d33]' : 'text-slate-400' }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </th>

                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 leading-4">
                                    <div class="flex items-center gap-2">
                                        <span>Condición del activo</span>
                                        <button type="button"
                                            onclick="openFilterPopover(event, 'condition_names')"
                                            class="rounded p-1 transition hover:bg-slate-200 {{ $hasFilter('condition_names') ? 'text-[#d94d33]' : 'text-slate-400' }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </th>

                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 leading-4">
                                    <div class="flex items-center gap-2">
                                        <span>Ejecución orden</span>
                                        <button type="button"
                                            onclick="openFilterPopover(event, 'execution_statuses')"
                                            class="rounded p-1 transition hover:bg-slate-200 {{ $hasFilter('execution_statuses') ? 'text-[#d94d33]' : 'text-slate-400' }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </th>

                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    <div class="flex items-center gap-2">
                                        <span>Semana</span>
                                        <button type="button"
                                            onclick="openFilterPopover(event, 'weeks')"
                                            class="rounded p-1 transition hover:bg-slate-200 {{ $hasFilter('weeks') ? 'text-[#d94d33]' : 'text-slate-400' }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-200 bg-white">
                            @forelse($reports as $report)
                                @php
                                    $isDone = ($report->executionStatus?->name ?? null) === 'REALIZADO';
                                @endphp

                                <tr class="align-top hover:bg-slate-50">
                                    <td class="whitespace-nowrap px-3 py-3 text-sm font-medium text-slate-900">
                                        {{ $report->element?->name ?? '—' }}
                                    </td>

                                    <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-600">
                                        —
                                    </td>

                                    <td class="px-3 py-3 text-sm text-slate-700">
                                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            {{ $report->component?->name ?? '—' }}
                                        </div>
                                        <div class="mt-1">
                                            {{ $report->diagnostic?->name ?? '—' }}
                                        </div>
                                    </td>

                                    <td class="max-w-[380px] px-3 py-3 text-sm leading-5 text-slate-700">
                                        {!! (($report->recommendation ?? null) && trim((string) $report->recommendation) !== '')
                                            ? nl2br(e(ltrim((string) $report->recommendation)))
                                            : '—' !!}
                                    </td>

                                    <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-700">
                                        @if($report->files->count() > 0)
                                            <a
                                                href="{{ route('admin.preventive-reports.evidence', $report) }}"
                                                target="_blank"
                                                class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white p-2 text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                                                title="Ver evidencia"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M4 5.75A2.75 2.75 0 0 1 6.75 3h10.5A2.75 2.75 0 0 1 20 5.75v12.5A2.75 2.75 0 0 1 17.25 21H6.75A2.75 2.75 0 0 1 4 18.25V5.75Zm2.75-1.25c-.69 0-1.25.56-1.25 1.25v8.19l3.47-3.47a1.75 1.75 0 0 1 2.474 0l1.056 1.055 2.72-2.72a1.75 1.75 0 0 1 2.475 0l.803.803V5.75c0-.69-.56-1.25-1.25-1.25H6.75Zm11.75 6.932-1.863-1.863a.25.25 0 0 0-.354 0l-3.78 3.78-2.117-2.116a.25.25 0 0 0-.353 0L5.5 15.76v2.49c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.818ZM9.25 8A1.75 1.75 0 1 0 9.25 11.5 1.75 1.75 0 0 0 9.25 8Z"/>
                                                </svg>
                                            </a>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-700">
                                        {{ $report->condition?->code ?? '—' }}
                                    </td>

                                    <td class="whitespace-nowrap px-2 py-3 text-sm text-slate-700">
                                        {{ $report->orden ?: '—' }}
                                    </td>

                                    <td class="whitespace-nowrap px-2 py-3 text-sm text-slate-700">
                                        {{ $report->aviso ?: '—' }}
                                    </td>

                                    <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-700">
                                        {{ $report->user?->name ?? '—' }}
                                    </td>

                                    <td class="px-3 py-3 text-sm text-slate-700">
                                        @if($report->created_at)
                                            <div>{{ $report->created_at->format('Y-m-d') }}</div>
                                            <div class="text-xs text-slate-500">{{ $report->created_at->format('H:i') }}</div>
                                        @else
                                            —
                                        @endif
                                    </td>

                                    <td class="px-3 py-3 text-sm text-slate-700" id="execution-date-{{ $report->id }}">
                                        @if($isDone && $report->execution_date)
                                            {{ \Illuminate\Support\Carbon::parse($report->execution_date)->format('Y-m-d') }}
                                        @else
                                            —
                                        @endif
                                    </td>

                                    <td class="px-3 py-3 text-sm">
                                        @if($report->condition)
                                            <span
                                                class="inline-flex rounded-lg px-3 py-1 font-medium"
                                                style="background-color: {{ $report->condition->color ?? '#e2e8f0' }}; color: #0f172a;"
                                            >
                                                {{ $report->condition->name }}
                                            </span>
                                        @else
                                            <span class="text-slate-700">—</span>
                                        @endif
                                    </td>

                                    <td class="px-3 py-3 text-sm">
                                        <label
                                            id="execution-badge-{{ $report->id }}"
                                            class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold {{ $isDone ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-800' }}"
                                        >
                                            <input
                                                type="checkbox"
                                                class="execution-checkbox rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                                                data-id="{{ $report->id }}"
                                                {{ $isDone ? 'checked' : '' }}
                                            >
                                            <span>{{ $isDone ? 'REALIZADO' : 'PENDIENTE' }}</span>
                                        </label>
                                    </td>

                                    <td class="whitespace-nowrap px-3 py-3 text-sm font-semibold text-slate-900">
                                        {{ $report->week }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="14" class="px-3 py-10 text-center text-sm text-slate-500">
                                        No hay reportes para este tipo de activo en el año actual.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($reports->hasPages())
                    <div class="border-t border-slate-200 px-4 py-3">
                        {{ $reports->links() }}
                    </div>
                @endif
            </div>
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
            <button type="button"
                onclick="clearCurrentFilter()"
                class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                Limpiar
            </button>

            <button type="button"
                onclick="applyCurrentFilter()"
                class="rounded-lg bg-[#d94d33] px-3 py-2 text-xs font-semibold text-white hover:bg-[#b83f29]">
                Aplicar
            </button>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const filterOptions = {
            element_names: {
                type: 'checklist',
                title: 'Nombre del activo',
                inputName: 'element_names',
                options: @json($filterOptions['element_names']),
            },
            warehouse_ids: {
                type: 'empty',
                title: 'ID almacén',
                message: 'No hay valores disponibles todavía para esta columna.',
            },
            diagnostic_pairs: {
                type: 'checklist_object',
                title: 'Diagnóstico',
                inputName: 'diagnostic_pairs',
                options: @json($filterOptions['diagnostic_pairs']),
            },
            recommendation_values: {
                type: 'checklist',
                title: 'Recomendación',
                inputName: 'recommendation_values',
                options: @json($filterOptions['recommendation_values']),
            },
            condition_codes: {
                type: 'checklist',
                title: 'Condición',
                inputName: 'condition_codes',
                options: @json($filterOptions['condition_codes']),
            },
            orden_values: {
                type: 'checklist',
                title: 'Orden',
                inputName: 'orden_values',
                options: @json($filterOptions['orden_values']),
            },
            aviso_values: {
                type: 'checklist',
                title: 'Aviso',
                inputName: 'aviso_values',
                options: @json($filterOptions['aviso_values']),
            },
            responsable_names: {
                type: 'checklist',
                title: 'Responsable',
                inputName: 'responsable_names',
                options: @json($filterOptions['responsable_names']),
            },
            report_date_range: {
                type: 'date_range',
                title: 'Fecha de reporte',
                fromName: 'report_date_from',
                toName: 'report_date_to',
            },
            execution_date_range: {
                type: 'date_range',
                title: 'Fecha de ejecución',
                fromName: 'execution_date_from',
                toName: 'execution_date_to',
            },
            condition_names: {
                type: 'checklist',
                title: 'Condición del activo',
                inputName: 'condition_names',
                options: @json($filterOptions['condition_names']),
            },
            execution_statuses: {
                type: 'checklist',
                title: 'Ejecución orden',
                inputName: 'execution_statuses',
                options: @json($filterOptions['execution_statuses']),
            },
            weeks: {
                type: 'checklist',
                title: 'Semana',
                inputName: 'weeks',
                options: @json($filterOptions['weeks']),
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

            if (config.type === 'empty') {
                body.innerHTML = `<p class="text-sm text-slate-500">${config.message}</p>`;
            }

            if (config.type === 'checklist') {
                const values = Array.isArray(activeFilters[config.inputName]) ? activeFilters[config.inputName] : [];
                renderChecklist(body, config, values, false);
            }

            if (config.type === 'checklist_object') {
                const values = Array.isArray(activeFilters[config.inputName]) ? activeFilters[config.inputName] : [];
                renderChecklist(body, config, values, true);
            }

            if (config.type === 'date_range') {
                renderDateRange(body, config);
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

        function renderDateRange(body, config) {
            body.innerHTML = `
                <div class="space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Desde</label>
                        <input
                            type="date"
                            id="date_from_input"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                            value="${activeFilters[config.fromName] ?? ''}"
                        >
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Hasta</label>
                        <input
                            type="date"
                            id="date_to_input"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                            value="${activeFilters[config.toName] ?? ''}"
                        >
                    </div>
                </div>
            `;
        }

        function clearCurrentFilter() {
            if (!currentPopoverKey) return;

            const config = filterOptions[currentPopoverKey];

            if (config.type === 'checklist' || config.type === 'checklist_object') {
                activeFilters[config.inputName] = [];
            }

            if (config.type === 'date_range') {
                activeFilters[config.fromName] = '';
                activeFilters[config.toName] = '';
            }

            submitFilters();
        }

        function applyCurrentFilter() {
            if (!currentPopoverKey) return;

            const config = filterOptions[currentPopoverKey];

            if (config.type === 'checklist' || config.type === 'checklist_object') {
                const values = Array.from(document.querySelectorAll('#filterPopover .filter-check:checked'))
                    .map(cb => cb.value);

                activeFilters[config.inputName] = values;
            }

            if (config.type === 'date_range') {
                activeFilters[config.fromName] = document.getElementById('date_from_input')?.value ?? '';
                activeFilters[config.toName] = document.getElementById('date_to_input')?.value ?? '';
            }

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

        async function toggleExecution(checkbox) {
            const reportId = checkbox.dataset.id;
            const isChecked = checkbox.checked;

            const badge = document.getElementById(`execution-badge-${reportId}`);
            const dateCell = document.getElementById(`execution-date-${reportId}`);

            try {
                const response = await fetch(`/admin/preventive-reports/report-details/${reportId}/toggle-execution`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        is_checked: isChecked ? 1 : 0
                    })
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'No fue posible actualizar el estado.');
                }

                if (isChecked) {
                    badge.classList.remove('bg-amber-100', 'text-amber-800');
                    badge.classList.add('bg-green-100', 'text-green-700');
                    badge.querySelector('span').textContent = 'REALIZADO';
                    dateCell.textContent = data.execution_date ? data.execution_date.substring(0, 10) : '—';
                } else {
                    badge.classList.remove('bg-green-100', 'text-green-700');
                    badge.classList.add('bg-amber-100', 'text-amber-800');
                    badge.querySelector('span').textContent = 'PENDIENTE';
                    dateCell.textContent = '—';
                }
            } catch (error) {
                checkbox.checked = !isChecked;
                alert(error.message);
            }
        }

        document.querySelectorAll('.execution-checkbox').forEach(cb => {
            cb.addEventListener('change', function () {
                toggleExecution(this);
            });
        });

        document.addEventListener('click', function (event) {
            const popover = document.getElementById('filterPopover');

            if (popover.classList.contains('hidden')) return;

            if (!popover.contains(event.target) && !event.target.closest('button[onclick^="openFilterPopover"]')) {
                closeFilterPopover();
            }
        });
    </script>
</body>
</html>
