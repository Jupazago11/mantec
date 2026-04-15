<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reporte preventivo por tipo de activo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
            html {
                scroll-behavior: smooth;
            }

            body {
                overflow-x: hidden;
            }

            .report-shell {
                max-width: 1900px;
                margin: 0 auto;
            }

            .report-topbar {
                position: sticky;
                top: 0;
                z-index: 50;
                backdrop-filter: blur(8px);
            }

            .report-topbar-card {
                background: rgba(255, 255, 255, 0.96);
            }

            .compact-metric {
                padding: 0.5rem 0.75rem;
                border-radius: 0.95rem;
            }

            .compact-table-wrapper {
                position: relative;
            }

            .table-scroll-container {
                overflow-x: auto;
                overflow-y: visible;
                scrollbar-width: none;
            }

            .table-scroll-container::-webkit-scrollbar {
                height: 0;
            }

            .preventive-table {
                width: max-content;
                min-width: 100%;
                table-layout: auto;
            }

            .preventive-table th,
            .preventive-table td {
                vertical-align: top;
            }

            .preventive-table th {
                white-space: normal;
                line-height: 1.05rem;
                vertical-align: middle;
            }

            .sticky-table-head th {
                position: static;
                z-index: auto;
                background: rgb(248 250 252);
                box-shadow: inset 0 -1px 0 rgb(226 232 240);
            }

            .preventive-table .cell-area {
                width: 92px;
                min-width: 92px;
                max-width: 92px;
                white-space: normal;
                line-height: 1.1rem;
                overflow-wrap: break-word;
            }

            .preventive-table .cell-element-name {
                width: 95px;
                min-width: 95px;
                max-width: 95px;
                white-space: normal;
                line-height: 1.1rem;
                overflow-wrap: break-word;
            }

            .preventive-table .cell-warehouse {
                width: 92px;
                min-width: 92px;
                max-width: 92px;
                white-space: normal;
                line-height: 1.1rem;
                overflow-wrap: break-word;
            }

            .preventive-table .cell-diagnostic {
                width: 122px;
                min-width: 122px;
                max-width: 122px;
            }

            .preventive-table .cell-recommendation {
                width: 150px;
                min-width: 150px;
                max-width: 150px;
                white-space: normal;
                line-height: 1.15rem;
                overflow-wrap: anywhere;
                word-break: normal;
                hyphens: auto;
                -webkit-hyphens: auto;
                -ms-hyphens: auto;
            }

            .preventive-table .cell-responsable {
                width: 102px;
                min-width: 102px;
                max-width: 102px;
                white-space: normal;
                line-height: 1.1rem;
                overflow-wrap: break-word;
            }

            .preventive-table .cell-date {
                width: 82px;
                min-width: 82px;
                max-width: 82px;
                white-space: normal;
                line-height: 1.05rem;
            }

            .preventive-table .cell-short {
                width: 68px;
                min-width: 68px;
                max-width: 68px;
            }

            .preventive-table .cell-week {
                width: 48px;
                min-width: 48px;
                max-width: 48px;
            }

            .preventive-table .cell-condition-name {
                width: 96px;
                min-width: 96px;
                max-width: 96px;
            }

            .preventive-table .cell-execution {
                width: 94px;
                min-width: 94px;
                max-width: 94px;
            }

            .preventive-table .cell-evidence {
                width: 68px;
                min-width: 68px;
                max-width: 68px;
            }

            .preventive-table th,
            .preventive-table td {
                padding-top: 0.5rem;
                padding-bottom: 0.5rem;
                padding-left: 0.5rem;
                padding-right: 0.5rem;
                font-size: 0.84rem;
            }

            .preventive-table.compact-mode th,
            .preventive-table.compact-mode td {
                padding-top: 0.36rem !important;
                padding-bottom: 0.36rem !important;
                padding-left: 0.36rem !important;
                padding-right: 0.36rem !important;
                font-size: 0.76rem !important;
            }

            .preventive-table.compact-mode .cell-area {
                width: 82px;
                min-width: 82px;
                max-width: 82px;
            }

            .preventive-table.compact-mode .cell-element-name {
                width: 88px;
                min-width: 88px;
                max-width: 88px;
            }

            .preventive-table.compact-mode .cell-warehouse {
                width: 84px;
                min-width: 84px;
                max-width: 84px;
            }

            .preventive-table.compact-mode .cell-diagnostic {
                width: 112px;
                min-width: 112px;
                max-width: 112px;
            }

            .preventive-table.compact-mode .cell-recommendation {
                width: 132px;
                min-width: 132px;
                max-width: 132px;
                font-size: 0.78rem !important;
                line-height: 1.05rem !important;
            }

            .preventive-table.compact-mode .cell-responsable {
                width: 106px;
                min-width: 106px;
                max-width: 106px;
            }

            .preventive-table.compact-mode .cell-date {
                width: 74px;
                min-width: 74px;
                max-width: 74px;
            }

            .preventive-table.compact-mode .cell-short {
                width: 58px;
                min-width: 58px;
                max-width: 58px;
            }

            .preventive-table.compact-mode .cell-week {
                width: 42px;
                min-width: 42px;
                max-width: 42px;
            }

            .preventive-table.compact-mode .cell-condition-name {
                width: 96px;
                min-width: 96px;
                max-width: 96px;
            }

            .preventive-table.compact-mode .cell-execution {
                width: 98px;
                min-width: 98px;
                max-width: 98px;
            }

            .preventive-table.compact-mode .cell-evidence {
                width: 56px;
                min-width: 56px;
                max-width: 56px;
            }

            .custom-pagination {
                display: flex;
                align-items: center;
                gap: 0.3rem;
                flex-wrap: wrap;
            }

            .custom-pagination .page-btn,
            .custom-pagination .page-current {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 2rem;
                height: 2rem;
                padding: 0 0.65rem;
                border-radius: 0.7rem;
                border: 1px solid rgb(226 232 240);
                font-size: 0.82rem;
                font-weight: 600;
                background: white;
                color: rgb(71 85 105);
                transition: 0.18s ease;
            }

            .custom-pagination .page-btn:hover {
                background: rgb(248 250 252);
                border-color: rgb(203 213 225);
                color: rgb(30 41 59);
            }

            .custom-pagination .page-current {
                background: rgb(241 245 249);
                color: rgb(15 23 42);
                border-color: rgb(203 213 225);
            }

            .bottom-scrollbar-fixed {
                position: fixed;
                bottom: 0;
                z-index: 60;
                display: none;
                height: 18px;
                overflow-x: auto;
                overflow-y: hidden;
                background: rgba(248, 250, 252, 0.98);
                border: 1px solid rgb(203 213 225);
                border-bottom: 0;
                border-top-left-radius: 0.75rem;
                border-top-right-radius: 0.75rem;
                box-shadow: 0 -2px 10px rgba(15, 23, 42, 0.06);
            }

            .bottom-scrollbar-fixed.is-visible {
                display: block;
            }

            .bottom-scrollbar-inner {
                height: 1px;
            }

            .bottom-scrollbar-fixed::-webkit-scrollbar,
            .table-scroll-container::-webkit-scrollbar {
                height: 12px;
            }

            .bottom-scrollbar-fixed::-webkit-scrollbar-thumb,
            .table-scroll-container::-webkit-scrollbar-thumb {
                background: rgb(148 163 184);
                border-radius: 9999px;
            }

            .bottom-scrollbar-fixed::-webkit-scrollbar-track,
            .table-scroll-container::-webkit-scrollbar-track {
                background: rgb(241 245 249);
            }

            @media (max-width: 1280px) {
                .preventive-table th,
                .preventive-table td {
                    font-size: 0.82rem;
                }
            }

            .preventive-table thead th {
                vertical-align: middle;
            }

            .preventive-table th > div {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 6px;
                min-height: 32px;
            }
    </style>
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

        $hasAnyActiveFilter =
            collect($activeFilters)->contains(function ($value) {
                if (is_array($value)) {
                    return count(array_filter($value, fn ($item) => $item !== null && $item !== '')) > 0;
                }

                return $value !== null && $value !== '';
            });

        $clearFiltersUrl = route('admin.preventive-reports.show', [$client->id, $elementType->id]) . '?year=' . $currentYear;

        $showWarehouseColumn = $showWarehouseColumn ?? false;

        $pageWindow = 2;
        $startPage = max(1, $reports->currentPage() - $pageWindow);
        $endPage = min($reports->lastPage(), $reports->currentPage() + $pageWindow);
    @endphp

    <div class="min-h-screen p-3 md:p-4">
        <div class="report-shell space-y-3">
            <div id="reportTopbar" class="report-topbar">
                <div class="report-topbar-card rounded-2xl border border-slate-200 px-4 py-3 shadow-sm">
                    <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                        <div class="min-w-0">
                            <h1 class="text-xl font-bold tracking-tight text-slate-900 md:text-2xl">
                                Reporte preventivo {{ $elementType->name }} Planta {{ $client->name }}
                            </h1>

                            @if($isReadOnly ?? false)
                                <div class="mt-2">
                                    <span class="inline-flex items-center rounded-xl bg-amber-100 px-3 py-1 text-[11px] font-semibold text-amber-800">
                                        Modo solo lectura
                                    </span>
                                </div>
                            @endif
                        </div>

                        <div class="flex flex-col gap-2 xl:items-end">
                            <div class="flex flex-wrap gap-2 xl:justify-end">
                                <div class="compact-metric border border-slate-200 bg-slate-50 text-right">
                                    <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                                        Año
                                    </div>
                                    <div class="mt-1 text-lg font-bold text-slate-900">
                                        {{ $currentYear }}
                                    </div>
                                </div>

                                <div class="compact-metric border border-slate-200 bg-slate-50 text-right">
                                    <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                                        Total generado
                                    </div>
                                    <div class="mt-1 text-lg font-bold text-slate-900">
                                        {{ $totalReportsGenerated ?? 0 }}
                                    </div>
                                </div>

                                <div class="compact-metric border border-slate-200 bg-slate-50 text-right">
                                    <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                                        Total filtrado
                                    </div>
                                    <div class="mt-1 text-lg font-bold text-slate-900">
                                        {{ $totalReportsFiltered ?? 0 }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 xl:justify-end">
                            @if($reports->hasPages())
                                <div class="custom-pagination">
                                    @if($reports->currentPage() > 1)
                                        <a
                                            class="page-btn"
                                            href="{{ $reports->appends(['year' => $currentYear])->url(1) }}"
                                            title="Ir a la primera página"
                                        >
                                            «
                                        </a>

                                        <a
                                            class="page-btn"
                                            href="{{ $reports->appends(['year' => $currentYear])->previousPageUrl() }}"
                                            title="Página anterior"
                                        >
                                            ‹
                                        </a>
                                    @endif

                                    @for($page = $startPage; $page <= $endPage; $page++)
                                        @if($page === $reports->currentPage())
                                            <span class="page-current">{{ $page }}</span>
                                        @else
                                            <a class="page-btn" href="{{ $reports->appends(['year' => $currentYear])->url($page) }}">{{ $page }}</a>
                                        @endif
                                    @endfor

                                    @if($reports->currentPage() < $reports->lastPage())
                                        <a
                                            class="page-btn"
                                            href="{{ $reports->appends(['year' => $currentYear])->nextPageUrl() }}"
                                            title="Página siguiente"
                                        >
                                            ›
                                        </a>

                                        <a
                                            class="page-btn"
                                            href="{{ $reports->appends(['year' => $currentYear])->url($reports->lastPage()) }}"
                                            title="Ir a la última página"
                                        >
                                            »
                                        </a>
                                    @endif
                                </div>
                            @endif

                                @if($hasAnyActiveFilter)
                                    <a
                                        href="{{ $clearFiltersUrl }}"
                                        class="inline-flex items-center rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                                    >
                                        Limpiar filtros
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <form id="filtersForm" method="GET" class="hidden">
                <input type="hidden" name="year" value="{{ $currentYear }}">
            </form>

            <div class="compact-table-wrapper rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div id="tableScrollContainer" class="table-scroll-container">
                    <table id="preventiveTable" class="preventive-table divide-y divide-slate-200">
                        <thead class="sticky-table-head bg-slate-50">
                            <tr>
                                <th class="cell-area text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                    <button
                                        type="button"
                                        onclick="openFilterPopover(event, 'area_names')"
                                        class="leading-tight transition hover:text-slate-700 {{ $hasFilter('area_names') ? 'text-[#d94d33]' : 'text-slate-500' }}"
                                    >
                                        Área
                                    </button>
                                </th>

                                <th class="cell-element-name text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                    <button
                                        type="button"
                                        onclick="openFilterPopover(event, 'element_names')"
                                        class="leading-tight text-left transition hover:text-slate-700 {{ $hasFilter('element_names') ? 'text-[#d94d33]' : 'text-slate-500' }}"
                                    >
                                        Nombre del<br>activo
                                    </button>
                                </th>

                                @if($showWarehouseColumn)
                                    <th class="cell-warehouse text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                        <button
                                            type="button"
                                            onclick="openFilterPopover(event, 'warehouse_codes')"
                                            class="leading-tight text-left transition hover:text-slate-700 {{ $hasFilter('warehouse_codes') ? 'text-[#d94d33]' : 'text-slate-500' }}"
                                        >
                                            Ubicación<br>técnica
                                        </button>
                                    </th>
                                @endif

                                <th class="cell-diagnostic text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                    <button
                                        type="button"
                                        onclick="openFilterPopover(event, 'diagnostic_pairs')"
                                        class="leading-tight transition hover:text-slate-700 {{ $hasFilter('diagnostic_pairs') ? 'text-[#d94d33]' : 'text-slate-500' }}"
                                    >
                                        Diagnóstico
                                    </button>
                                </th>

                                <th class="cell-recommendation text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                    <button
                                        type="button"
                                        onclick="openFilterPopover(event, 'recommendation_values')"
                                        class="leading-tight transition hover:text-slate-700 {{ $hasFilter('recommendation_values') ? 'text-[#d94d33]' : 'text-slate-500' }}"
                                    >
                                        Recomendación
                                    </button>
                                </th>

                                <th class="cell-evidence text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                    Evidencia
                                </th>

                                <th class="cell-short text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                    <button
                                        type="button"
                                        onclick="openFilterPopover(event, 'condition_codes')"
                                        class="leading-tight transition hover:text-slate-700 {{ $hasFilter('condition_codes') ? 'text-[#d94d33]' : 'text-slate-500' }}"
                                    >
                                        Condición
                                    </button>
                                </th>

                                <th class="cell-short text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                    <button
                                        type="button"
                                        onclick="openFilterPopover(event, 'orden_values')"
                                        class="leading-tight transition hover:text-slate-700 {{ $hasFilter('orden_values') ? 'text-[#d94d33]' : 'text-slate-500' }}"
                                    >
                                        Orden
                                    </button>
                                </th>

                                <th class="cell-short text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                    <button
                                        type="button"
                                        onclick="openFilterPopover(event, 'aviso_values')"
                                        class="leading-tight transition hover:text-slate-700 {{ $hasFilter('aviso_values') ? 'text-[#d94d33]' : 'text-slate-500' }}"
                                    >
                                        Aviso
                                    </button>
                                </th>

                                <th class="cell-short text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                    <button
                                        type="button"
                                        onclick="openFilterPopover(event, 'inspector_names')"
                                        class="leading-tight transition hover:text-slate-700 {{ $hasFilter('inspector_names') ? 'text-[#d94d33]' : 'text-slate-500' }}"
                                    >
                                        Inspector
                                    </button>
                                </th>

                                <th class="cell-responsable text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                    <button
                                        type="button"
                                        onclick="openFilterPopover(event, 'responsable_names')"
                                        class="leading-tight text-left transition hover:text-slate-700 {{ $hasFilter('responsable_names') ? 'text-[#d94d33]' : 'text-slate-500' }}"
                                    >
                                        Responsable
                                    </button>
                                </th>

                                <th class="cell-date text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                    <button
                                        type="button"
                                        onclick="openFilterPopover(event, 'report_date_range')"
                                        class="leading-tight text-left transition hover:text-slate-700 {{ $hasFilter('report_date_from') || $hasFilter('report_date_to') ? 'text-[#d94d33]' : 'text-slate-500' }}"
                                    >
                                        Fecha de<br>reporte
                                    </button>
                                </th>

                                <th class="cell-date text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                    <button
                                        type="button"
                                        onclick="openFilterPopover(event, 'execution_date_range')"
                                        class="leading-tight text-left transition hover:text-slate-700 {{ $hasFilter('execution_date_from') || $hasFilter('execution_date_to') ? 'text-[#d94d33]' : 'text-slate-500' }}"
                                    >
                                        Fecha de<br>ejecución
                                    </button>
                                </th>

                                <th class="cell-condition-name text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                    <button
                                        type="button"
                                        onclick="openFilterPopover(event, 'condition_names')"
                                        class="leading-tight text-left transition hover:text-slate-700 {{ $hasFilter('condition_names') ? 'text-[#d94d33]' : 'text-slate-500' }}"
                                    >
                                        Condición del<br>activo
                                    </button>
                                </th>

                                <th class="cell-execution text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                    <button
                                        type="button"
                                        onclick="openFilterPopover(event, 'execution_statuses')"
                                        class="leading-tight text-left transition hover:text-slate-700 {{ $hasFilter('execution_statuses') ? 'text-[#d94d33]' : 'text-slate-500' }}"
                                    >
                                        Ejecución<br>orden
                                    </button>
                                </th>

                                <th class="cell-week text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                    <button
                                        type="button"
                                        onclick="openFilterPopover(event, 'weeks')"
                                        class="leading-tight transition hover:text-slate-700 {{ $hasFilter('weeks') ? 'text-[#d94d33]' : 'text-slate-500' }}"
                                    >
                                        Semana
                                    </button>
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-200 bg-white">
                            @forelse($reports as $report)
                                @php
                                    $isDone = ($report->executionStatus?->name ?? null) === 'REALIZADO';
                                @endphp

                                <tr class="align-top hover:bg-slate-50">
                                    <td class="cell-area text-sm text-slate-700">
                                        {{ $report->element?->area?->name ?? '—' }}
                                    </td>

                                    <td class="cell-element-name text-sm font-medium text-slate-900">
                                        {{ $report->element?->name ?? '—' }}
                                    </td>

                                    @if($showWarehouseColumn)
                                        <td class="cell-warehouse text-sm text-slate-700">
                                            {{ $report->element?->warehouse_code ?? '—' }}
                                        </td>
                                    @endif

                                    <td class="cell-diagnostic text-sm text-slate-700">
                                        <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                            {{ $report->component?->name ?? '—' }}
                                        </div>
                                        <div class="mt-1 whitespace-normal break-words">
                                            {{ $report->diagnostic?->name ?? '—' }}
                                        </div>
                                    </td>

                                    <td class="cell-recommendation text-sm text-slate-700">
                                        @if(($report->recommendation ?? null) && trim((string) $report->recommendation) !== '')
                                            <div lang="es">{!! nl2br(e(ltrim((string) $report->recommendation))) !!}</div>
                                        @else
                                            —
                                        @endif
                                    </td>

                                    <td class="cell-evidence whitespace-nowrap text-sm text-slate-700">
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

                                    <td class="cell-short whitespace-nowrap text-sm text-slate-700">
                                        {{ $report->condition?->code ?? '—' }}
                                    </td>

                                    <td class="cell-short whitespace-nowrap text-sm text-slate-700">
                                        {{ $report->orden ?: '—' }}
                                    </td>

                                    <td class="cell-short whitespace-nowrap text-sm text-slate-700">
                                        {{ $report->aviso ?: '—' }}
                                    </td>

                                    <td class="cell-short whitespace-nowrap text-sm text-slate-700">
                                        {{ $report->user?->name ?? '—' }}
                                    </td>

                                    <td class="cell-responsable text-sm text-slate-700">
                                        {{ $report->responsable_names ?? '—' }}
                                    </td>

                                    <td class="cell-date text-sm text-slate-700">
                                        @if($report->created_at)
                                            <div>{{ $report->created_at->format('Y-m-d') }}</div>
                                            <div class="text-[11px] text-slate-500">{{ $report->created_at->format('H:i') }}</div>
                                        @else
                                            —
                                        @endif
                                    </td>

                                    <td class="cell-date text-sm text-slate-700" id="execution-date-{{ $report->id }}">
                                        @if($isDone && $report->execution_date)
                                            {{ \Illuminate\Support\Carbon::parse($report->execution_date)->format('Y-m-d') }}
                                        @else
                                            —
                                        @endif
                                    </td>

                                    <td class="cell-condition-name text-sm">
                                        @if($report->condition)
                                            <span
                                                class="inline-flex rounded-lg px-2.5 py-1 font-medium"
                                                style="background-color: {{ $report->condition->color ?? '#e2e8f0' }}; color: #0f172a;"
                                            >
                                                {{ $report->condition->name }}
                                            </span>
                                        @else
                                            <span class="text-slate-700">—</span>
                                        @endif
                                    </td>

                                    <td class="cell-execution text-sm">
                                        @if($isReadOnly ?? false)
                                            <span
                                                id="execution-badge-{{ $report->id }}"
                                                class="inline-flex items-center gap-2 rounded-xl px-2.5 py-1.5 text-xs font-semibold {{ $isDone ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-800' }}"
                                            >
                                                <span>{{ $isDone ? 'REALIZADO' : 'PENDIENTE' }}</span>
                                            </span>
                                        @else
                                            <label
                                                id="execution-badge-{{ $report->id }}"
                                                class="inline-flex items-center gap-2 rounded-xl px-2.5 py-1.5 text-xs font-semibold {{ $isDone ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-800' }}"
                                            >
                                                <input
                                                    type="checkbox"
                                                    class="execution-checkbox rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                                                    data-id="{{ $report->id }}"
                                                    {{ $isDone ? 'checked' : '' }}
                                                >
                                                <span>{{ $isDone ? 'REALIZADO' : 'PENDIENTE' }}</span>
                                            </label>
                                        @endif
                                    </td>

                                    <td class="cell-week whitespace-nowrap text-sm font-semibold text-slate-900">
                                        {{ $report->week }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $showWarehouseColumn ? 16 : 15 }}" class="px-3 py-10 text-center text-sm text-slate-500">
                                        No hay reportes para este tipo de activo en el año seleccionado.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="bottomHorizontalScrollbar" class="bottom-scrollbar-fixed">
        <div id="bottomHorizontalScrollbarInner" class="bottom-scrollbar-inner"></div>
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
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const isReadOnly = @json($isReadOnly ?? false);

        const filterOptions = {
            area_names: {
                type: 'checklist',
                title: 'Área',
                inputName: 'area_names',
                options: @json($filterOptions['area_names'] ?? []),
            },
            element_names: {
                type: 'checklist',
                title: 'Nombre del activo',
                inputName: 'element_names',
                options: @json($filterOptions['element_names'] ?? []),
            },
            warehouse_codes: {
                type: 'checklist',
                title: 'Ubicación técnica',
                inputName: 'warehouse_codes',
                options: @json($filterOptions['warehouse_codes'] ?? []),
            },
            diagnostic_pairs: {
                type: 'checklist_object',
                title: 'Diagnóstico',
                inputName: 'diagnostic_pairs',
                options: @json($filterOptions['diagnostic_pairs'] ?? []),
            },
            recommendation_values: {
                type: 'checklist',
                title: 'Recomendación',
                inputName: 'recommendation_values',
                options: @json($filterOptions['recommendation_values'] ?? []),
            },
            condition_codes: {
                type: 'checklist',
                title: 'Condición',
                inputName: 'condition_codes',
                options: @json($filterOptions['condition_codes'] ?? []),
            },
            orden_values: {
                type: 'checklist',
                title: 'Orden',
                inputName: 'orden_values',
                options: @json($filterOptions['orden_values'] ?? []),
            },
            aviso_values: {
                type: 'checklist',
                title: 'Aviso',
                inputName: 'aviso_values',
                options: @json($filterOptions['aviso_values'] ?? []),
            },
            inspector_names: {
                type: 'checklist',
                title: 'Inspector',
                inputName: 'inspector_names',
                options: @json($filterOptions['inspector_names'] ?? []),
            },
            responsable_names: {
                type: 'checklist',
                title: 'Responsable',
                inputName: 'responsable_names',
                options: @json($filterOptions['responsable_names'] ?? []),
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
                options: @json($filterOptions['condition_names'] ?? []),
            },
            execution_statuses: {
                type: 'checklist',
                title: 'Ejecución orden',
                inputName: 'execution_statuses',
                options: @json($filterOptions['execution_statuses'] ?? []),
            },
            weeks: {
                type: 'checklist',
                title: 'Semana',
                inputName: 'weeks',
                options: @json($filterOptions['weeks'] ?? []),
            },
        };

        const activeFilters = @json($activeFilters);
        let currentPopoverKey = null;


        function buildFiltersForm() {
            const form = document.getElementById('filtersForm');
            form.innerHTML = '';

            const yearInput = document.createElement('input');
            yearInput.type = 'hidden';
            yearInput.name = 'year';
            yearInput.value = @json($currentYear);
            form.appendChild(yearInput);

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
            if (!config) return;

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
                let items = config.options ?? [];

                if (objectMode) {
                    items = items.filter(item => String(item.label ?? '').toLowerCase().includes(term));
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
            if (isReadOnly) {
                checkbox.checked = !checkbox.checked;
                return;
            }

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

        function initExecutionCheckboxes() {
            document.querySelectorAll('.execution-checkbox').forEach(cb => {
                cb.addEventListener('change', function () {
                    toggleExecution(this);
                });

                if (isReadOnly) {
                    cb.disabled = true;
                    cb.classList.add('cursor-not-allowed', 'opacity-60');
                }
            });
        }

        function syncBottomScrollbar() {
            const bar = document.getElementById('bottomHorizontalScrollbar');
            const barInner = document.getElementById('bottomHorizontalScrollbarInner');
            const container = document.getElementById('tableScrollContainer');
            const table = document.getElementById('preventiveTable');

            if (!bar || !barInner || !container || !table) return;

            const containerRect = container.getBoundingClientRect();
            const hasOverflow = table.scrollWidth > container.clientWidth + 4;

            if (!hasOverflow) {
                bar.classList.remove('is-visible');
                return;
            }

            bar.classList.add('is-visible');
            bar.style.left = `${containerRect.left}px`;
            bar.style.width = `${containerRect.width}px`;
            barInner.style.width = `${table.scrollWidth}px`;

            if (!bar.dataset.bound) {
                let syncingFromBar = false;
                let syncingFromTable = false;

                bar.addEventListener('scroll', () => {
                    if (syncingFromTable) return;
                    syncingFromBar = true;
                    container.scrollLeft = bar.scrollLeft;
                    syncingFromBar = false;
                });

                container.addEventListener('scroll', () => {
                    if (syncingFromBar) return;
                    syncingFromTable = true;
                    bar.scrollLeft = container.scrollLeft;
                    syncingFromTable = false;
                });

                bar.dataset.bound = '1';
            }

            bar.scrollLeft = container.scrollLeft;
        }

        function applyCompactModeIfNeeded() {
            const table = document.getElementById('preventiveTable');
            const container = document.getElementById('tableScrollContainer');
            if (!table || !container) return;

            table.classList.remove('compact-mode');

            requestAnimationFrame(() => {
                const hasOverflow = table.scrollWidth > container.clientWidth + 4;
                table.classList.toggle('compact-mode', hasOverflow);

                requestAnimationFrame(() => {
                    syncBottomScrollbar();
                });
            });
        }

        initExecutionCheckboxes();

        document.addEventListener('click', function (event) {
            const popover = document.getElementById('filterPopover');

            if (popover.classList.contains('hidden')) return;

            if (!popover.contains(event.target) && !event.target.closest('button[onclick^="openFilterPopover"]')) {
                closeFilterPopover();
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            applyCompactModeIfNeeded();
        });

        window.addEventListener('load', function () {
            applyCompactModeIfNeeded();
        });

        window.addEventListener('resize', function () {
            applyCompactModeIfNeeded();
        });

        window.addEventListener('scroll', function () {
            syncBottomScrollbar();
        }, { passive: true });
    </script>
</body>
</html>