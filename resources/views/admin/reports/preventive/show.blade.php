<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reporte preventivo por agrupación</title>
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
                width: 118px;
                min-width: 118px;
                max-width: 118px;
                white-space: normal;
                line-height: 1.1rem;
                overflow-wrap: anywhere;
                word-break: break-word;
            }

            .preventive-table .cell-inspector {
                width: 110px;
                min-width: 110px;
                max-width: 110px;
                white-space: normal;
                line-height: 1.1rem;
                overflow-wrap: anywhere;
                word-break: break-word;
            }

            .preventive-table.compact-mode .cell-inspector {
                width: 96px;
                min-width: 96px;
                max-width: 96px;
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
                width: 110px;
                min-width: 110px;
                max-width: 110px;
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

            .preventive-table thead th button {
                background: transparent;
                border: 0;
                padding: 0;
                font: inherit;
                text-transform: inherit;
                letter-spacing: inherit;
                cursor: pointer;
            }
    </style>
</head>
<body class="bg-slate-100 text-slate-900">
    @php
        $isReadOnly = $isReadOnly ?? false;
        $roleKey = $roleKey ?? auth()->user()?->role?->key;

        $dateFrom = $dateFrom ?? request('date_from', now()->startOfYear()->toDateString());
        $dateTo = $dateTo ?? request('date_to', now()->toDateString());

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

        $clearFiltersUrl = route('admin.preventive-reports.group', ['group' => $group->id]) .
            '?' . http_build_query([
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ]);

        $showWarehouseColumn = $showWarehouseColumn ?? false;

        $pageWindow = 2;
        $startPage = max(1, $reports->currentPage() - $pageWindow);
        $endPage = min($reports->lastPage(), $reports->currentPage() + $pageWindow);

        $totalGenerated = $totalGenerated ?? ($reportsTotal ?? $reports->total());
        $totalFiltered = $reports->total();
    @endphp

    <div class="min-h-screen p-3 md:p-4">
        <div class="report-shell space-y-3">
            <div id="reportTopbar" class="report-topbar">
                <div class="report-topbar-card rounded-2xl border border-slate-200 px-4 py-3 shadow-sm">
                    <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                        <div class="min-w-0">
                            <h1 class="text-xl font-bold tracking-tight text-slate-900 md:text-2xl">
                                Reporte preventivo {{ $group->name }} Planta {{ $group->client->name }}
                            </h1>

                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-xl bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-700">
                                    Desde {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }}
                                </span>

                                <span class="inline-flex items-center rounded-xl bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-700">
                                    Hasta {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
                                </span>

                                @if($isReadOnly)
                                    <span class="inline-flex items-center rounded-xl bg-amber-100 px-3 py-1 text-[11px] font-semibold text-amber-800">
                                        Modo solo lectura
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-col gap-2 xl:items-end">
                            <div class="flex flex-wrap gap-2 xl:justify-end">
                                <div class="compact-metric border border-slate-200 bg-slate-50 text-right">
                                    <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                                        Rango
                                    </div>
                                    <div class="mt-1 text-sm font-bold text-slate-900">
                                        {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }}
                                        —
                                        {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
                                    </div>
                                </div>

                                <div class="compact-metric border border-slate-200 bg-slate-50 text-right">
                                    <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                                        Total generado
                                    </div>
                                    <div class="mt-1 text-lg font-bold text-slate-900">
                                        {{ number_format($totalGenerated) }}
                                    </div>
                                </div>

                                <div class="compact-metric border border-slate-200 bg-slate-50 text-right">
                                    <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                                        Total filtrado
                                    </div>
                                    <div class="mt-1 text-lg font-bold text-slate-900">
                                        {{ number_format($totalFiltered) }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 xl:justify-end">
                                @if($reports->lastPage() > 1)
                                    <div class="custom-pagination">
                                        @if($reports->currentPage() > 1)
                                            <a
                                                class="page-btn"
                                                href="{{ $reports->appends(request()->query())->url(1) }}"
                                                title="Ir a la primera página"
                                            >
                                                «
                                            </a>

                                            <a
                                                class="page-btn"
                                                href="{{ $reports->appends(request()->query())->previousPageUrl() }}"
                                                title="Página anterior"
                                            >
                                                ‹
                                            </a>
                                        @endif

                                        @for($page = $startPage; $page <= $endPage; $page++)
                                            @if($page === $reports->currentPage())
                                                <span class="page-current">{{ $page }}</span>
                                            @else
                                                <a
                                                    class="page-btn"
                                                    href="{{ $reports->appends(request()->query())->url($page) }}"
                                                >
                                                    {{ $page }}
                                                </a>
                                            @endif
                                        @endfor

                                        @if($reports->currentPage() < $reports->lastPage())
                                            <a
                                                class="page-btn"
                                                href="{{ $reports->appends(request()->query())->nextPageUrl() }}"
                                                title="Página siguiente"
                                            >
                                                ›
                                            </a>

                                            <a
                                                class="page-btn"
                                                href="{{ $reports->appends(request()->query())->url($reports->lastPage()) }}"
                                                title="Ir a la última página"
                                            >
                                                »
                                            </a>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <form id="filtersForm" method="GET" class="hidden">
                <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                <input type="hidden" name="date_to" value="{{ $dateTo }}">
            </form>
                        <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm lg:flex-row lg:items-end lg:justify-between">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label for="date_from_visible" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                            Fecha inicial
                        </label>
                        <input
                            id="date_from_visible"
                            type="date"
                            value="{{ $dateFrom }}"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-[#d94d33] focus:outline-none focus:ring-2 focus:ring-[#d94d33]/20"
                        >
                    </div>

                    <div>
                        <label for="date_to_visible" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                            Fecha final
                        </label>
                        <input
                            id="date_to_visible"
                            type="date"
                            value="{{ $dateTo }}"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-[#d94d33] focus:outline-none focus:ring-2 focus:ring-[#d94d33]/20"
                        >
                    </div>

                    <div class="sm:col-span-2 lg:col-span-2">
                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                            Estado de filtros
                        </label>

                        <div class="flex h-[42px] items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm text-slate-600">
                            @if($hasAnyActiveFilter)
                                <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                                <span class="font-medium text-slate-700">Hay filtros activos en la tabla</span>
                            @else
                                <span class="inline-flex h-2.5 w-2.5 rounded-full bg-slate-300"></span>
                                <span class="font-medium text-slate-500">Sin filtros adicionales</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a
                        href="{{ route('admin.dashboard') }}"
                        class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 hover:text-slate-900"
                    >
                        Volver
                    </a>

                    <a
                        href="{{ $clearFiltersUrl }}"
                        class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 hover:text-slate-900"
                    >
                        Limpiar filtros
                    </a>

                    <button
                        type="button"
                        id="applyDateRangeBtn"
                        class="inline-flex items-center rounded-xl bg-[#d94d33] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#b63f28]"
                    >
                        Aplicar rango
                    </button>
                </div>
            </div>

            <div class="compact-table-wrapper rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div id="tableScrollContainer" class="table-scroll-container">
                    <table id="preventiveTable" class="preventive-table divide-y divide-slate-200">
                        <thead class="sticky-table-head bg-slate-50">
                            <tr>
                                <th class="cell-area text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                    <button
                                        type="button"
                                        onclick="openFilterPopover(event, 'area_names')"
                                        class="leading-tight transition hover:text-slate-700 {{ $hasFilter('area_names') ? 'text-slate-900' : 'text-slate-500' }}"
                                    >
                                        Área
                                    </button>
                                </th>

                                <th class="cell-element-name text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                    <button
                                        type="button"
                                        onclick="openFilterPopover(event, 'element_names')"
                                        class="leading-tight text-left transition hover:text-slate-700 {{ $hasFilter('element_names') ? 'text-slate-900' : 'text-slate-500' }}"
                                    >
                                        Nombre del<br>activo
                                    </button>
                                </th>

                                @if($showWarehouseColumn)
                                    <th class="cell-warehouse text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                        <button
                                            type="button"
                                            onclick="openFilterPopover(event, 'warehouse_codes')"
                                            class="leading-tight text-left transition hover:text-slate-700 {{ $hasFilter('warehouse_codes') ? 'text-slate-900' : 'text-slate-500' }}"
                                        >
                                            Código de<br>almacén
                                        </button>
                                    </th>
                                @endif

                                <th class="cell-diagnostic text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                    <button
                                        type="button"
                                        onclick="openFilterPopover(event, 'diagnostic_pairs')"
                                        class="leading-tight transition hover:text-slate-700 {{ $hasFilter('diagnostic_pairs') ? 'text-slate-900' : 'text-slate-500' }}"
                                    >
                                        Diagnóstico
                                    </button>
                                </th>

                                <th class="cell-recommendation text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                    <button
                                        type="button"
                                        onclick="openFilterPopover(event, 'recommendation_values')"
                                        class="leading-tight transition hover:text-slate-700 {{ $hasFilter('recommendation_values') ? 'text-slate-900' : 'text-slate-500' }}"
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
                                        class="leading-tight transition hover:text-slate-700 {{ $hasFilter('condition_codes') ? 'text-slate-900' : 'text-slate-500' }}"
                                    >
                                        Condición
                                    </button>
                                </th>

                                <th class="cell-short text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                    <button
                                        type="button"
                                        onclick="openFilterPopover(event, 'orden_values')"
                                        class="leading-tight transition hover:text-slate-700 {{ $hasFilter('orden_values') ? 'text-slate-900' : 'text-slate-500' }}"
                                    >
                                        Orden
                                    </button>
                                </th>

                                <th class="cell-short text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                    <button
                                        type="button"
                                        onclick="openFilterPopover(event, 'aviso_values')"
                                        class="leading-tight transition hover:text-slate-700 {{ $hasFilter('aviso_values') ? 'text-slate-900' : 'text-slate-500' }}"
                                    >
                                        Aviso
                                    </button>
                                </th>

                                <th class="cell-inspector text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                    <button
                                        type="button"
                                        onclick="openFilterPopover(event, 'inspector_names')"
                                        class="leading-tight transition hover:text-slate-700 {{ $hasFilter('inspector_names') ? 'text-slate-900' : 'text-slate-500' }}"
                                    >
                                        Inspector
                                    </button>
                                </th>

                                <th class="cell-responsable text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                    <button
                                        type="button"
                                        onclick="openFilterPopover(event, 'responsable_names')"
                                        class="leading-tight text-left transition hover:text-slate-700 {{ $hasFilter('responsable_names') ? 'text-slate-900' : 'text-slate-500' }}"
                                    >
                                        Responsable
                                    </button>
                                </th>

                                <th class="cell-date text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                    <button
                                        type="button"
                                        onclick="openFilterPopover(event, 'report_date_range')"
                                        class="leading-tight text-left transition hover:text-slate-700 {{ $hasFilter('report_date_range') ? 'text-slate-900' : 'text-slate-500' }}"
                                    >
                                        Fecha de<br>reporte
                                    </button>
                                </th>

                                <th class="cell-date text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                    <button
                                        type="button"
                                        onclick="openFilterPopover(event, 'execution_date_range')"
                                        class="leading-tight text-left transition hover:text-slate-700 {{ $hasFilter('execution_date_range') ? 'text-slate-900' : 'text-slate-500' }}"
                                    >
                                        Fecha de<br>ejecución
                                    </button>
                                </th>

                                <th class="cell-condition-name text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                    <button
                                        type="button"
                                        onclick="openFilterPopover(event, 'condition_names')"
                                        class="leading-tight text-left transition hover:text-slate-700 {{ $hasFilter('condition_names') ? 'text-slate-900' : 'text-slate-500' }}"
                                    >
                                        Condición del<br>activo
                                    </button>
                                </th>

                                <th class="cell-execution text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                    <button
                                        type="button"
                                        onclick="openFilterPopover(event, 'execution_statuses')"
                                        class="leading-tight text-left transition hover:text-slate-700 {{ $hasFilter('execution_statuses') ? 'text-slate-900' : 'text-slate-500' }}"
                                    >
                                        Ejecución<br>orden
                                    </button>
                                </th>

                                <th class="cell-week text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                    <button
                                        type="button"
                                        onclick="openFilterPopover(event, 'weeks')"
                                        class="leading-tight transition hover:text-slate-700 {{ $hasFilter('weeks') ? 'text-slate-900' : 'text-slate-500' }}"
                                    >
                                        Semana
                                    </button>
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-200 bg-white">
                                                        @forelse($reports as $report)
                                @php
                                    $reportId = $report->id;
                                    $executionBadgeClasses = $report->executed
                                        ? 'bg-emerald-100 text-emerald-800'
                                        : 'bg-amber-100 text-amber-800';

                                    $executionLabel = $report->executed ? 'REALIZADO' : 'PENDIENTE';

                                    $executionDateText = $report->execution_date
                                        ? \Carbon\Carbon::parse($report->execution_date)->format('Y-m-d')
                                        : '';

                                    $reportDateText = $report->report_date
                                        ? \Carbon\Carbon::parse($report->report_date)->format('Y-m-d')
                                        : '—';

                                    $reportTimeText = $report->created_at
                                        ? \Carbon\Carbon::parse($report->created_at)->format('H:i')
                                        : null;

                                    $conditionBg = $report->condition_color ?: '#e2e8f0';
                                    $conditionText = $report->condition_name ?: '—';

                                    $componentName = $report->component_name ?: '—';
                                    $diagnosticName = $report->diagnostic_name ?: '—';
                                    $recommendationText = filled($report->recommendation) ? $report->recommendation : '—';
                                    $responsableName = $report->responsable_name ?: '—';
                                    $inspectorName = $report->inspector_name ?: '—';
                                    $areaName = $report->area_name ?: '—';
                                    $elementName = $report->element_name ?: '—';
                                    $warehouseCode = $report->warehouse_code ?: '—';
                                    $conditionCode = $report->condition_code ?: '—';
                                    $ordenValue = $report->orden ?: '—';
                                    $avisoValue = $report->aviso ?: '—';
                                    $weekValue = $report->week ?: '—';
                                @endphp

                                <tr class="align-top hover:bg-slate-50">
                                    <td class="cell-area text-sm text-slate-700">
                                        {{ $areaName }}
                                    </td>

                                    <td class="cell-element-name text-sm font-medium text-slate-900">
                                        {{ $elementName }}
                                    </td>

                                    @if($showWarehouseColumn)
                                        <td class="cell-warehouse text-sm text-slate-700">
                                            {{ $warehouseCode }}
                                        </td>
                                    @endif

                                    <td class="cell-diagnostic text-sm text-slate-700">
                                        <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                            {{ $componentName }}
                                        </div>
                                        <div class="mt-1 whitespace-normal break-words">
                                            {{ $diagnosticName }}
                                        </div>
                                    </td>

                                    <td class="cell-recommendation text-sm text-slate-700">
                                        @if($recommendationText !== '—')
                                            <div lang="es">{{ $recommendationText }}</div>
                                        @else
                                            —
                                        @endif
                                    </td>

                                    <td class="cell-evidence whitespace-nowrap text-sm text-slate-700">
                                        @if($report->has_evidence ?? false)
                                            <a
                                                href="{{ route('admin.preventive-reports.evidence', ['reportDetail' => $reportId]) }}"
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
                                        {{ $conditionCode }}
                                    </td>

                                    <td class="cell-short whitespace-nowrap text-sm text-slate-700">
                                        {{ $ordenValue }}
                                    </td>

                                    <td class="cell-short whitespace-nowrap text-sm text-slate-700">
                                        {{ $avisoValue }}
                                    </td>

                                    <td class="cell-inspector text-sm text-slate-700">
                                        {{ $inspectorName }}
                                    </td>

                                    <td class="cell-responsable text-sm text-slate-700">
                                        {{ $responsableName }}
                                    </td>

                                    <td class="cell-date text-sm text-slate-700">
                                        <div>{{ $reportDateText }}</div>
                                        @if($reportTimeText)
                                            <div class="text-[11px] text-slate-500">{{ $reportTimeText }}</div>
                                        @endif
                                    </td>

                                    <td class="cell-date text-sm text-slate-700" id="execution-date-{{ $reportId }}">
                                        {{ $executionDateText }}
                                    </td>
                                                                        <td class="cell-condition-name text-sm text-slate-700">
                                        <span
                                            class="inline-flex items-center rounded-lg px-2 py-1 text-[11px] font-semibold text-slate-800"
                                            style="background-color: {{ $conditionBg }}"
                                        >
                                            {{ $conditionText }}
                                        </span>
                                    </td>

                                    <td class="cell-execution text-sm text-slate-700">
                                        @if(!$isReadOnly)
                                            <button
                                                type="button"
                                                onclick="toggleExecution({{ $reportId }})"
                                                class="inline-flex items-center justify-center rounded-xl px-3 py-1.5 text-[11px] font-semibold transition {{ $executionBadgeClasses }}"
                                                id="execution-badge-{{ $reportId }}"
                                            >
                                                {{ $executionLabel }}
                                            </button>
                                        @else
                                            <span
                                                class="inline-flex items-center justify-center rounded-xl px-3 py-1.5 text-[11px] font-semibold {{ $executionBadgeClasses }}"
                                            >
                                                {{ $executionLabel }}
                                            </span>
                                        @endif
                                    </td>

                                    <td class="cell-week whitespace-nowrap text-sm text-slate-700">
                                        {{ $weekValue }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="100%" class="px-4 py-10 text-center text-sm text-slate-500">
                                        No se encontraron registros para esta agrupación en el rango seleccionado.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div id="bottomScrollbar" class="bottom-scrollbar-fixed">
                    <div id="bottomScrollbarInner" class="bottom-scrollbar-inner"></div>
                </div>
            </div>
                        <div
                id="filterPopover"
                class="fixed z-[80] hidden w-[320px] max-w-[92vw] rounded-2xl border border-slate-200 bg-white p-4 shadow-2xl"
            ></div>
        </div>
    </div>

    <script>
        const ACTIVE_FILTERS = @json($activeFilters ?? []);
        const FILTER_OPTIONS = @json($filterOptions ?? []);
        const FILTER_LABELS = {
            area_names: 'Área',
            element_names: 'Nombre del activo',
            warehouse_codes: 'Código de almacén',
            diagnostic_pairs: 'Diagnóstico',
            recommendation_values: 'Recomendación',
            condition_codes: 'Condición',
            orden_values: 'Orden',
            aviso_values: 'Aviso',
            inspector_names: 'Inspector',
            responsable_names: 'Responsable',
            report_date_range: 'Fecha de reporte',
            execution_date_range: 'Fecha de ejecución',
            condition_names: 'Condición del activo',
            execution_statuses: 'Ejecución orden',
            weeks: 'Semana',
        };

        const dateFromVisible = document.getElementById('date_from_visible');
        const dateToVisible = document.getElementById('date_to_visible');
        const filtersForm = document.getElementById('filtersForm');
        const filterPopover = document.getElementById('filterPopover');
        const applyDateRangeBtn = document.getElementById('applyDateRangeBtn');

        function normalizeScalarArray(values) {
            if (!Array.isArray(values)) {
                return [];
            }

            return values
                .filter(value => value !== null && value !== undefined && value !== '')
                .map(value => String(value));
        }

        function getCurrentBaseUrl() {
            return "{{ route('admin.preventive-reports.group', ['group' => $group->id]) }}";
        }

        function applyDateRange() {
            const from = dateFromVisible?.value?.trim();
            const to = dateToVisible?.value?.trim();

            if (!from || !to) {
                alert('Debes seleccionar la fecha inicial y la fecha final.');
                return;
            }

            if (to < from) {
                alert('La fecha final no puede ser menor que la fecha inicial.');
                return;
            }

            const url = new URL(getCurrentBaseUrl(), window.location.origin);
            url.searchParams.set('date_from', from);
            url.searchParams.set('date_to', to);

            Object.entries(ACTIVE_FILTERS).forEach(([key, value]) => {
                if (Array.isArray(value)) {
                    value
                        .filter(item => item !== null && item !== undefined && item !== '')
                        .forEach(item => url.searchParams.append(`${key}[]`, item));
                    return;
                }

                if (value !== null && value !== undefined && value !== '') {
                    url.searchParams.set(key, value);
                }
            });

            window.location.href = url.toString();
        }

        if (applyDateRangeBtn) {
            applyDateRangeBtn.addEventListener('click', applyDateRange);
        }

        if (dateFromVisible) {
            dateFromVisible.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    applyDateRange();
                }
            });
        }

        if (dateToVisible) {
            dateToVisible.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    applyDateRange();
                }
            });
        }

        function closeFilterPopover() {
            if (!filterPopover) {
                return;
            }

            filterPopover.classList.add('hidden');
            filterPopover.innerHTML = '';
        }

        function rebuildFiltersForm(params) {
            if (!filtersForm) {
                return;
            }

            filtersForm.innerHTML = '';

            const dateFromInput = document.createElement('input');
            dateFromInput.type = 'hidden';
            dateFromInput.name = 'date_from';
            dateFromInput.value = dateFromVisible?.value || "{{ $dateFrom }}";
            filtersForm.appendChild(dateFromInput);

            const dateToInput = document.createElement('input');
            dateToInput.type = 'hidden';
            dateToInput.name = 'date_to';
            dateToInput.value = dateToVisible?.value || "{{ $dateTo }}";
            filtersForm.appendChild(dateToInput);

            Object.entries(params).forEach(([key, value]) => {
                if (Array.isArray(value)) {
                    value.forEach(item => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `${key}[]`;
                        input.value = item;
                        filtersForm.appendChild(input);
                    });
                    return;
                }

                if (value !== null && value !== undefined && value !== '') {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = value;
                    filtersForm.appendChild(input);
                }
            });
        }
                function submitFilters(nextFilters = {}) {
            const merged = {};

            Object.entries(ACTIVE_FILTERS).forEach(([key, value]) => {
                if (Array.isArray(value)) {
                    const normalized = normalizeScalarArray(value);
                    if (normalized.length > 0) {
                        merged[key] = normalized;
                    }
                    return;
                }

                if (value !== null && value !== undefined && value !== '') {
                    merged[key] = value;
                }
            });

            Object.entries(nextFilters).forEach(([key, value]) => {
                if (Array.isArray(value)) {
                    const normalized = normalizeScalarArray(value);
                    if (normalized.length > 0) {
                        merged[key] = normalized;
                    } else {
                        delete merged[key];
                    }
                    return;
                }

                if (value === null || value === undefined || value === '') {
                    delete merged[key];
                } else {
                    merged[key] = value;
                }
            });

            rebuildFiltersForm(merged);
            filtersForm.submit();
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function getPopoverPosition(event) {
            const triggerRect = event.currentTarget.getBoundingClientRect();
            const width = 320;
            const left = Math.min(
                window.scrollX + triggerRect.left,
                window.scrollX + window.innerWidth - width - 16
            );
            const top = window.scrollY + triggerRect.bottom + 10;

            return { left: Math.max(8, left), top };
        }

        function openFilterPopover(event, filterKey) {
            event.preventDefault();

            if (!filterPopover) {
                return;
            }

            const position = getPopoverPosition(event);
            filterPopover.style.left = `${position.left}px`;
            filterPopover.style.top = `${position.top}px`;
            filterPopover.classList.remove('hidden');

            const title = FILTER_LABELS[filterKey] || 'Filtro';
            const currentValue = ACTIVE_FILTERS[filterKey];
            const options = FILTER_OPTIONS[filterKey] || [];

            if (filterKey === 'report_date_range' || filterKey === 'execution_date_range') {
                const fromValue = currentValue?.from ?? '';
                const toValue = currentValue?.to ?? '';

                filterPopover.innerHTML = `
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold text-slate-900">${escapeHtml(title)}</h3>
                            <button
                                type="button"
                                onclick="closeFilterPopover()"
                                class="rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                                aria-label="Cerrar"
                            >
                                ✕
                            </button>
                        </div>

                        <div class="grid gap-3">
                            <div>
                                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                    Desde
                                </label>
                                <input
                                    id="popover-date-from"
                                    type="date"
                                    value="${escapeHtml(fromValue)}"
                                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-[#d94d33] focus:outline-none focus:ring-2 focus:ring-[#d94d33]/20"
                                >
                            </div>

                            <div>
                                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                    Hasta
                                </label>
                                <input
                                    id="popover-date-to"
                                    type="date"
                                    value="${escapeHtml(toValue)}"
                                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-[#d94d33] focus:outline-none focus:ring-2 focus:ring-[#d94d33]/20"
                                >
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-2 pt-1">
                            <button
                                type="button"
                                onclick="submitDateRangeFilter('${escapeHtml(filterKey)}')"
                                class="inline-flex items-center rounded-xl bg-[#d94d33] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#b63f28]"
                            >
                                Aplicar
                            </button>

                            <button
                                type="button"
                                onclick="clearSingleFilter('${escapeHtml(filterKey)}')"
                                class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                            >
                                Limpiar
                            </button>
                        </div>
                    </div>
                `;

                return;
            }

            const selectedValues = Array.isArray(currentValue)
                ? normalizeScalarArray(currentValue)
                : [];

            const optionItems = options.map(option => {
                const value = typeof option === 'object' ? option.value : option;
                const label = typeof option === 'object' ? option.label : option;
                const checked = selectedValues.includes(String(value)) ? 'checked' : '';

                return `
                    <label class="flex items-start gap-3 rounded-xl px-2 py-2 transition hover:bg-slate-50">
                        <input
                            type="checkbox"
                            value="${escapeHtml(value)}"
                            ${checked}
                            class="filter-option-checkbox mt-0.5 rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                        >
                        <span class="text-sm text-slate-700 leading-5">${escapeHtml(label)}</span>
                    </label>
                `;
            }).join('');

            filterPopover.innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-slate-900">${escapeHtml(title)}</h3>
                        <button
                            type="button"
                            onclick="closeFilterPopover()"
                            class="rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                            aria-label="Cerrar"
                        >
                            ✕
                        </button>
                    </div>

                    <div>
                        <input
                            id="filter-search-input"
                            type="text"
                            placeholder="Buscar..."
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-[#d94d33] focus:outline-none focus:ring-2 focus:ring-[#d94d33]/20"
                            oninput="filterPopoverOptions()"
                        >
                    </div>

                    <div
                        id="filter-options-container"
                        data-filter-key="${escapeHtml(filterKey)}"
                        class="max-h-72 space-y-1 overflow-y-auto pr-1"
                    >
                        ${optionItems || '<p class="px-2 py-2 text-sm text-slate-500">No hay opciones disponibles.</p>'}
                    </div>

                    <div class="flex items-center justify-between gap-2 pt-1">
                        <button
                            type="button"
                            onclick="applyMultiSelectFilter()"
                            class="inline-flex items-center rounded-xl bg-[#d94d33] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#b63f28]"
                        >
                            Aplicar
                        </button>

                        <button
                            type="button"
                            onclick="clearSingleFilter('${escapeHtml(filterKey)}')"
                            class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            Limpiar
                        </button>
                    </div>
                </div>
            `;
        }
                function filterPopoverOptions() {
            const searchInput = document.getElementById('filter-search-input');
            const container = document.getElementById('filter-options-container');

            if (!searchInput || !container) {
                return;
            }

            const term = searchInput.value.trim().toLowerCase();
            const labels = container.querySelectorAll('label');

            labels.forEach(label => {
                const text = label.textContent.trim().toLowerCase();
                label.classList.toggle('hidden', term !== '' && !text.includes(term));
            });
        }

        function applyMultiSelectFilter() {
            const container = document.getElementById('filter-options-container');

            if (!container) {
                return;
            }

            const filterKey = container.dataset.filterKey;
            const selectedValues = Array.from(
                container.querySelectorAll('.filter-option-checkbox:checked')
            ).map(input => input.value);

            submitFilters({
                [filterKey]: selectedValues,
                page: null,
            });

            closeFilterPopover();
        }

        function submitDateRangeFilter(filterKey) {
            const fromInput = document.getElementById('popover-date-from');
            const toInput = document.getElementById('popover-date-to');

            const from = fromInput?.value?.trim() || '';
            const to = toInput?.value?.trim() || '';

            if (from && to && to < from) {
                alert('La fecha final no puede ser menor que la fecha inicial.');
                return;
            }

            const payload = {};
            payload[filterKey] = (from || to) ? { from, to } : null;
            payload.page = null;

            submitFilters(payload);
            closeFilterPopover();
        }

        function clearSingleFilter(filterKey) {
            const payload = {};
            payload[filterKey] = null;
            payload.page = null;

            submitFilters(payload);
            closeFilterPopover();
        }

        document.addEventListener('click', function (event) {
            if (!filterPopover || filterPopover.classList.contains('hidden')) {
                return;
            }

            const clickedPopover = filterPopover.contains(event.target);
            const clickedTrigger = event.target.closest('th button');

            if (!clickedPopover && !clickedTrigger) {
                closeFilterPopover();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeFilterPopover();
            }
        });

        const tableScrollContainer = document.getElementById('tableScrollContainer');
        const bottomScrollbar = document.getElementById('bottomScrollbar');
        const bottomScrollbarInner = document.getElementById('bottomScrollbarInner');
        const preventiveTable = document.getElementById('preventiveTable');

        let syncingScroll = false;

        function syncBottomScrollbarWidth() {
            if (!tableScrollContainer || !bottomScrollbar || !bottomScrollbarInner || !preventiveTable) {
                return;
            }

            bottomScrollbarInner.style.width = `${preventiveTable.scrollWidth}px`;
        }

        function updateBottomScrollbarVisibility() {
            if (!tableScrollContainer || !bottomScrollbar || !preventiveTable) {
                return;
            }

            const hasHorizontalOverflow = preventiveTable.scrollWidth > tableScrollContainer.clientWidth;
            const tableRect = tableScrollContainer.getBoundingClientRect();
            const viewportHeight = window.innerHeight;

            const shouldShow =
                hasHorizontalOverflow &&
                tableRect.bottom > 0 &&
                tableRect.top < viewportHeight - 40;

            bottomScrollbar.classList.toggle('is-visible', shouldShow);

            if (shouldShow) {
                const shellRect = tableScrollContainer.getBoundingClientRect();
                bottomScrollbar.style.left = `${shellRect.left}px`;
                bottomScrollbar.style.width = `${shellRect.width}px`;
            }
        }

        if (tableScrollContainer && bottomScrollbar) {
            tableScrollContainer.addEventListener('scroll', () => {
                if (syncingScroll) return;
                syncingScroll = true;
                bottomScrollbar.scrollLeft = tableScrollContainer.scrollLeft;
                syncingScroll = false;
            });

            bottomScrollbar.addEventListener('scroll', () => {
                if (syncingScroll) return;
                syncingScroll = true;
                tableScrollContainer.scrollLeft = bottomScrollbar.scrollLeft;
                syncingScroll = false;
            });

            window.addEventListener('resize', () => {
                syncBottomScrollbarWidth();
                updateBottomScrollbarVisibility();
            });

            window.addEventListener('scroll', updateBottomScrollbarVisibility, { passive: true });

            requestAnimationFrame(() => {
                syncBottomScrollbarWidth();
                updateBottomScrollbarVisibility();
                bottomScrollbar.scrollLeft = tableScrollContainer.scrollLeft;
            });
        }
                function updateCompactMode() {
            if (!tableScrollContainer || !preventiveTable) {
                return;
            }

            const shouldCompact = preventiveTable.scrollWidth > tableScrollContainer.clientWidth + 140;
            preventiveTable.classList.toggle('compact-mode', shouldCompact);

            requestAnimationFrame(() => {
                syncBottomScrollbarWidth();
                updateBottomScrollbarVisibility();
            });
        }

        window.addEventListener('resize', updateCompactMode);

        requestAnimationFrame(() => {
            updateCompactMode();
        });

        async function toggleExecution(reportId) {
            if (@json($isReadOnly)) {
                return;
            }

            const badge = document.getElementById(`execution-badge-${reportId}`);
            const dateCell = document.getElementById(`execution-date-${reportId}`);

            if (!badge) {
                return;
            }

            const previousHtml = badge.innerHTML;
            const previousClassName = badge.className;
            const previousDate = dateCell ? dateCell.innerHTML : '';

            badge.disabled = true;
            badge.classList.add('opacity-70', 'pointer-events-none');
            badge.innerHTML = 'Actualizando...';

            try {
                const response = await fetch(
                    "{{ route('admin.preventive-reports.toggle-execution', ['reportDetail' => '__REPORT_ID__']) }}".replace('__REPORT_ID__', reportId),
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
                    throw new Error(data.message || 'No fue posible actualizar la ejecución.');
                }

                const executed = Boolean(data.executed);
                const executionDate = data.execution_date || '';

                badge.className = [
                    'inline-flex',
                    'items-center',
                    'justify-center',
                    'rounded-xl',
                    'px-3',
                    'py-1.5',
                    'text-[11px]',
                    'font-semibold',
                    'transition',
                    executed ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'
                ].join(' ');

                badge.innerHTML = executed ? 'REALIZADO' : 'PENDIENTE';

                if (dateCell) {
                    dateCell.textContent = executionDate;
                }
            } catch (error) {
                badge.className = previousClassName;
                badge.innerHTML = previousHtml;

                if (dateCell) {
                    dateCell.innerHTML = previousDate;
                }

                alert(error.message || 'Ocurrió un error al actualizar la ejecución.');
            } finally {
                badge.disabled = false;
                badge.classList.remove('opacity-70', 'pointer-events-none');
            }
        }
                document.addEventListener('DOMContentLoaded', () => {
            try {
                syncBottomScrollbarWidth();
                updateBottomScrollbarVisibility();
                updateCompactMode();
            } catch (error) {
                console.warn('Error inicializando layout de tabla:', error);
            }
        });

        let resizeObserver = null;

        if (typeof ResizeObserver !== 'undefined' && preventiveTable) {
            resizeObserver = new ResizeObserver(() => {
                syncBottomScrollbarWidth();
                updateBottomScrollbarVisibility();
                updateCompactMode();
            });

            resizeObserver.observe(preventiveTable);
        }

        window.addEventListener('beforeunload', () => {
            if (resizeObserver && preventiveTable) {
                resizeObserver.unobserve(preventiveTable);
            }
        });

        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                requestAnimationFrame(() => {
                    syncBottomScrollbarWidth();
                    updateBottomScrollbarVisibility();
                });
            }
        });
        </script>
    </body>
</html>