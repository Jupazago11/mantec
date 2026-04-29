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
                width: 82px;
                min-width: 82px;
                max-width: 82px;
                white-space: normal;
                overflow-wrap: break-word;
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
                width: 92px;
                min-width: 92px;
                max-width: 92px;
                white-space: normal;
                overflow-wrap: break-word;
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
                width: 76px;
                min-width: 76px;
                max-width: 76px;
                white-space: normal;
                overflow-wrap: break-word;
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
                width: 86px;
                min-width: 86px;
                max-width: 86px;
                white-space: normal;
                overflow-wrap: break-word;
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
                justify-content: flex-start;
                gap: 4px;
                min-height: 32px;
                max-width: 100%;
            }

            .preventive-table thead th button {
                display: block;
                max-width: 100%;
                background: transparent;
                border: 0;
                padding: 0;
                font: inherit;
                text-align: left;
                text-transform: inherit;
                letter-spacing: inherit;
                line-height: 1.05rem;
                white-space: normal;
                overflow-wrap: break-word;
                cursor: pointer;
            }

            .inline-edit-trigger {
                cursor: pointer;
                border-radius: 0.65rem;
                padding: 0.2rem 0.35rem;
                transition: background-color 0.18s ease;
                display: inline-block;
                min-width: 36px;
            }

            .inline-edit-trigger:hover {
                background: rgb(241 245 249);
            }

            .inline-edit-box {
                display: flex;
                flex-direction: column;
                gap: 0.35rem;
            }

            .inline-edit-input {
                width: 100%;
                min-width: 70px;
                border: 1px solid rgb(203 213 225);
                border-radius: 0.65rem;
                padding: 0.35rem 0.55rem;
                font-size: 0.82rem;
                line-height: 1.2rem;
                color: rgb(51 65 85);
                background: white;
            }

            .inline-edit-input:focus {
                outline: none;
                border-color: #d94d33;
                box-shadow: 0 0 0 3px rgba(217, 77, 51, 0.15);
            }

            .inline-edit-actions {
                display: flex;
                align-items: center;
                gap: 0.35rem;
            }

            .inline-edit-btn {
                border: 0;
                border-radius: 0.55rem;
                width: 26px;
                height: 26px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 0.82rem;
                font-weight: 700;
                transition: 0.18s ease;
                cursor: pointer;
            }

            .inline-edit-btn-cancel {
                background: rgb(248 250 252);
                color: rgb(71 85 105);
                border: 1px solid rgb(226 232 240);
            }

            .inline-edit-btn-cancel:hover {
                background: rgb(241 245 249);
            }

            .inline-edit-btn-save {
                background: rgb(22 163 74);
                color: white;
            }

            .inline-edit-btn-save:hover {
                background: rgb(21 128 61);
            }

            .inline-edit-loading {
                opacity: 0.6;
                pointer-events: none;
            }

            .inline-toast {
                position: fixed;
                right: 18px;
                bottom: 24px;
                z-index: 100;
                min-width: 220px;
                max-width: 340px;
                border-radius: 0.95rem;
                padding: 0.8rem 1rem;
                box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
                font-size: 0.9rem;
                font-weight: 600;
                transform: translateY(10px);
                opacity: 0;
                pointer-events: none;
                transition: 0.22s ease;
            }

            .inline-toast.show {
                transform: translateY(0);
                opacity: 1;
            }

            .inline-toast-success {
                background: rgb(220 252 231);
                color: rgb(22 101 52);
                border: 1px solid rgb(187 247 208);
            }

            .inline-toast-error {
                background: rgb(254 226 226);
                color: rgb(153 27 27);
                border: 1px solid rgb(254 202 202);
            }


            .inline-date-trigger {
                cursor: pointer;
                border-radius: 0.65rem;
                padding: 0.2rem 0.35rem;
                transition: background-color 0.18s ease;
                display: inline-block;
                min-width: 36px;
            }

            .inline-date-trigger:hover {
                background: rgb(241 245 249);
            }

            .inline-date-box {
                display: flex;
                flex-direction: column;
                gap: 0.35rem;
            }

            .inline-date-input {
                width: 100%;
                min-width: 118px;
                border: 1px solid rgb(203 213 225);
                border-radius: 0.65rem;
                padding: 0.35rem 0.55rem;
                font-size: 0.82rem;
                line-height: 1.2rem;
                color: rgb(51 65 85);
                background: white;
            }

            .inline-date-input:focus {
                outline: none;
                border-color: #d94d33;
                box-shadow: 0 0 0 3px rgba(217, 77, 51, 0.15);
            }

            .inline-date-actions {
                display: flex;
                align-items: center;
                gap: 0.35rem;
            }

            .inline-date-loading {
                opacity: 0.6;
                pointer-events: none;
            }
    </style>
</head>
<body class="bg-slate-100 text-slate-900">
    @php
        $isReadOnly = $isReadOnly ?? false;
        $roleKey = $roleKey ?? auth()->user()?->role?->key;

        $dateFrom = $dateFrom ?? request('date_from', now()->startOfYear()->toDateString());
        $dateTo = $dateTo ?? request('date_to', now()->toDateString());
        $canInlineEditOrderAviso = $canInlineEditOrderAviso ?? false;
        $canInlineEditExecutionDate = $canInlineEditExecutionDate ?? false;

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

                                    <td class="cell-short text-sm text-slate-700">
                                        @if($canInlineEditOrderAviso)
                                            <div
                                                class="inline-editable"
                                                data-report-id="{{ $reportId }}"
                                                data-field="orden"
                                                data-value="{{ $report->orden ?? '' }}"
                                            >
                                                <span class="inline-edit-trigger">
                                                    {{ $ordenValue }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="whitespace-nowrap">{{ $ordenValue }}</span>
                                        @endif
                                    </td>

                                    <td class="cell-short text-sm text-slate-700">
                                        @if($canInlineEditOrderAviso)
                                            <div
                                                class="inline-editable"
                                                data-report-id="{{ $reportId }}"
                                                data-field="aviso"
                                                data-value="{{ $report->aviso ?? '' }}"
                                            >
                                                <span class="inline-edit-trigger">
                                                    {{ $avisoValue }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="whitespace-nowrap">{{ $avisoValue }}</span>
                                        @endif
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
                                        <div id="execution-date-content-{{ $reportId }}">
                                            @if($report->executed && $canInlineEditExecutionDate)
                                                <div
                                                    class="inline-date-editable"
                                                    data-report-id="{{ $reportId }}"
                                                    data-value="{{ $report->execution_date ? \Carbon\Carbon::parse($report->execution_date)->format('Y-m-d') : now()->toDateString() }}"
                                                >
                                                    <span class="inline-date-trigger">
                                                        {{ $executionDateText }}
                                                    </span>
                                                </div>
                                            @else
                                                {{ $executionDateText }}
                                            @endif
                                        </div>
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
                                        <div class="flex items-start gap-2">
                                            
                                            {{-- NUMERO DE SEMANA --}}
                                            <span class="mt-[2px]">{{ $weekValue }}</span>

                                            {{-- ICONOS --}}
                                            <div class="flex flex-col items-center gap-1">
                                                
                                                {{-- EDITAR --}}
                                                @if($canEditReports)
                                                    <button
                                                        type="button"
                                                        onclick="openEditReportModal({{ $reportId }})"
                                                        class="text-slate-400 hover:text-[#d94d33] transition"
                                                        title="Editar reporte"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M16.862 4.487l1.651-1.651a2.121 2.121 0 113 3l-1.651 1.651M4 20h4l10.586-10.586a2 2 0 00-2.828-2.828L5.172 17.172A2 2 0 004 18.586V20z" />
                                                        </svg>
                                                    </button>
                                                @endif

                                                {{-- ELIMINAR --}}
                                                @if(in_array($roleKey, ['superadmin', 'admin_global'], true))
                                                    <button
                                                        type="button"
                                                        onclick="toggleReportStatus({{ $reportId }})"
                                                        class="text-red-500 hover:text-red-700 transition"
                                                        title="Ocultar reporte"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M6 7h12M9 7V4h6v3M10 11v6M14 11v6M5 7l1 13a2 2 0 002 2h8a2 2 0 002-2l1-13" />
                                                        </svg>
                                                    </button>
                                                @endif

                                            </div>
                                        </div>
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
    <div id="inlineToast" class="inline-toast"></div>
    <div id="editReportModal" class="fixed inset-0 z-[200] hidden items-center justify-center bg-slate-900/50 px-4 py-6 backdrop-blur-[2px]">
    <div class="max-h-[92vh] w-full max-w-4xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl">
        
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <h2 class="text-lg font-semibold text-slate-900">
                Editar reporte
            </h2>

            <button onclick="closeEditReportModal()" class="text-slate-400 hover:text-slate-700">
                ✕
            </button>
        </div>

        <div class="px-6 py-5">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Área</label>
                    <select
                        id="edit-area"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm transition focus:border-[#d94d33] focus:outline-none focus:ring-2 focus:ring-[#d94d33]/20"
                    ></select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Activo</label>
                    <select
                        id="edit-element"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm transition focus:border-[#d94d33] focus:outline-none focus:ring-2 focus:ring-[#d94d33]/20"
                    ></select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Componente</label>
                    <select
                        id="edit-component"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm transition focus:border-[#d94d33] focus:outline-none focus:ring-2 focus:ring-[#d94d33]/20"
                    ></select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Diagnóstico</label>
                    <select
                        id="edit-diagnostic"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm transition focus:border-[#d94d33] focus:outline-none focus:ring-2 focus:ring-[#d94d33]/20"
                    ></select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Condición</label>
                    <select
                        id="edit-condition"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm transition focus:border-[#d94d33] focus:outline-none focus:ring-2 focus:ring-[#d94d33]/20"
                    ></select>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Fecha del reporte</label>

                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input
                                type="radio"
                                name="edit-date-mode"
                                value="keep"
                                checked
                                class="h-4 w-4 border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                            >
                            <span>Conservar fecha original</span>
                        </label>

                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input
                                type="radio"
                                name="edit-date-mode"
                                value="new"
                                class="h-4 w-4 border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                            >
                            <span>Registrar nueva fecha</span>
                        </label>
                    </div>

                    <div class="mt-3">
                        <input
                            type="date"
                            id="edit-new-date"
                            class="hidden w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm transition focus:border-[#d94d33] focus:outline-none focus:ring-2 focus:ring-[#d94d33]/20"
                        >
                    </div>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Recomendación</label>
                    <textarea
                        id="edit-recommendation"
                        rows="4"
                        class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition focus:border-[#d94d33] focus:outline-none focus:ring-2 focus:ring-[#d94d33]/20"
                        placeholder="Escribe la recomendación actualizada..."
                    ></textarea>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2 px-5 py-4">
            <button
                onclick="closeEditReportModal()"
                class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
            >
                Cancelar
            </button>

            <button
                id="saveEditReportBtn"
                class="rounded-xl bg-[#d94d33] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
            >
                Guardar cambios
            </button>
        </div>
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
            if (!preventiveTable) {
                return;
            }

            const shouldCompact = window.innerWidth <= 1366;

            preventiveTable.classList.toggle('compact-mode', shouldCompact);

            requestAnimationFrame(() => {
                syncBottomScrollbarWidth();
                updateBottomScrollbarVisibility();
            });
        }

        window.addEventListener('resize', () => {
            requestAnimationFrame(updateCompactMode);
        });

        requestAnimationFrame(updateCompactMode);

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

                renderExecutionDateCell(reportId, executed, executionDate);

                if (executed) {
                    const editableDateContainer = document.querySelector(
                        `#execution-date-content-${reportId} .inline-date-editable`
                    );

                    if (editableDateContainer) {
                        mountInlineDateEditor(editableDateContainer);
                    }
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

        if (typeof ResizeObserver !== 'undefined' && tableScrollContainer) {
            resizeObserver = new ResizeObserver(() => {
                requestAnimationFrame(() => {
                    syncBottomScrollbarWidth();
                    updateBottomScrollbarVisibility();
                });
            });

            resizeObserver.observe(tableScrollContainer);
        }

        window.addEventListener('beforeunload', () => {
            if (resizeObserver && tableScrollContainer) {
                resizeObserver.unobserve(tableScrollContainer);
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
        const inlineToast = document.getElementById('inlineToast');

        function showInlineToast(message, type = 'success') {
            if (!inlineToast) return;

            inlineToast.className = 'inline-toast';
            inlineToast.classList.add(type === 'success' ? 'inline-toast-success' : 'inline-toast-error');
            inlineToast.textContent = message;
            inlineToast.classList.add('show');

            clearTimeout(window.__inlineToastTimeout);
            window.__inlineToastTimeout = setTimeout(() => {
                inlineToast.classList.remove('show');
            }, 4200);
        }

        function escapeInlineValue(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function mountInlineEditor(container) {
            if (!container || container.dataset.editing === '1') {
                return;
            }

            const reportId = container.dataset.reportId;
            const field = container.dataset.field;
            const originalValue = container.dataset.value ?? '';

            container.dataset.editing = '1';

            container.innerHTML = `
                <div class="inline-edit-box">
                    <input
                        type="text"
                        class="inline-edit-input"
                        value="${escapeInlineValue(originalValue)}"
                        maxlength="255"
                    >
                    <div class="inline-edit-actions">
                        <button type="button" class="inline-edit-btn inline-edit-btn-cancel" title="Cancelar">✕</button>
                        <button type="button" class="inline-edit-btn inline-edit-btn-save" title="Guardar">✓</button>
                    </div>
                </div>
            `;

            const input = container.querySelector('.inline-edit-input');
            const cancelBtn = container.querySelector('.inline-edit-btn-cancel');
            const saveBtn = container.querySelector('.inline-edit-btn-save');

            if (input) {
                input.focus();
                input.select();

                input.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        event.preventDefault();
                        restoreInlineCell(container, originalValue);
                    }

                    if (event.key === 'Enter') {
                        event.preventDefault();
                        saveInlineCell(container, reportId, field, input.value);
                    }
                });
            }

            cancelBtn?.addEventListener('click', () => {
                restoreInlineCell(container, originalValue);
            });

            saveBtn?.addEventListener('click', () => {
                saveInlineCell(container, reportId, field, input?.value ?? '');
            });
        }

        function restoreInlineCell(container, value) {
            container.dataset.editing = '0';
            container.dataset.value = value ?? '';

            const displayValue = value && String(value).trim() !== '' ? value : '—';

            container.innerHTML = `
                <span class="inline-edit-trigger">${escapeInlineValue(displayValue)}</span>
            `;
        }

        async function saveInlineCell(container, reportId, field, value) {
            container.classList.add('inline-edit-loading');

            try {
                const response = await fetch(
                    "{{ route('admin.preventive-reports.inline-update', ['reportDetail' => '__REPORT_ID__']) }}".replace('__REPORT_ID__', reportId),
                    {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            field,
                            value,
                        }),
                    }
                );

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'No fue posible guardar el campo.');
                }

                restoreInlineCell(container, data.value ?? '');
                showInlineToast(data.message || 'Guardado exitoso.', 'success');
            } catch (error) {
                container.classList.remove('inline-edit-loading');
                showInlineToast(error.message || 'Ocurrió un error al guardar.', 'error');
                return;
            }

            container.classList.remove('inline-edit-loading');
        }

        document.addEventListener('click', function (event) {
            const editableContainer = event.target.closest('.inline-editable');

            if (editableContainer && editableContainer.dataset.editing !== '1') {
                mountInlineEditor(editableContainer);
            }
        });


        function mountInlineDateEditor(container) {
            if (!container || container.dataset.editing === '1') {
                return;
            }

            const reportId = container.dataset.reportId;
            const originalValue = container.dataset.value || new Date().toISOString().slice(0, 10);

            container.dataset.editing = '1';

            container.innerHTML = `
                <div class="inline-date-box">
                    <input
                        type="date"
                        class="inline-date-input"
                        value="${escapeInlineValue(originalValue)}"
                    >
                    <div class="inline-date-actions">
                        <button type="button" class="inline-edit-btn inline-edit-btn-cancel" title="Cancelar">✕</button>
                        <button type="button" class="inline-edit-btn inline-edit-btn-save" title="Guardar">✓</button>
                    </div>
                </div>
            `;

            const input = container.querySelector('.inline-date-input');
            const cancelBtn = container.querySelector('.inline-edit-btn-cancel');
            const saveBtn = container.querySelector('.inline-edit-btn-save');

            input?.focus();

            input?.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    event.preventDefault();
                    restoreInlineDateCell(container, originalValue);
                }

                if (event.key === 'Enter') {
                    event.preventDefault();
                    saveInlineDateCell(container, reportId, input.value);
                }
            });

            cancelBtn?.addEventListener('click', () => {
                restoreInlineDateCell(container, originalValue);
            });

            saveBtn?.addEventListener('click', () => {
                saveInlineDateCell(container, reportId, input?.value ?? '');
            });
        }

        function restoreInlineDateCell(container, value) {
            container.dataset.editing = '0';
            container.dataset.value = value ?? '';

            const displayValue = value && String(value).trim() !== '' ? value : '';

            container.innerHTML = `
                <span class="inline-date-trigger">${escapeInlineValue(displayValue)}</span>
            `;
        }

        async function saveInlineDateCell(container, reportId, executionDate) {
            if (!executionDate) {
                showInlineToast('Debes seleccionar una fecha.', 'error');
                return;
            }

            container.classList.add('inline-date-loading');

            try {
                const response = await fetch(
                    "{{ route('admin.preventive-reports.execution-date.update', ['reportDetail' => '__REPORT_ID__']) }}".replace('__REPORT_ID__', reportId),
                    {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            execution_date: executionDate,
                        }),
                    }
                );

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'No fue posible actualizar la fecha de ejecución.');
                }

                restoreInlineDateCell(container, data.execution_date || '');
                showInlineToast(data.message || 'Fecha actualizada correctamente.', 'success');
            } catch (error) {
                container.classList.remove('inline-date-loading');
                showInlineToast(error.message || 'Ocurrió un error al guardar la fecha.', 'error');
                return;
            }

            container.classList.remove('inline-date-loading');
        }

        document.addEventListener('click', function (event) {
            const editableDateContainer = event.target.closest('.inline-date-editable');

            if (editableDateContainer && editableDateContainer.dataset.editing !== '1') {
                mountInlineDateEditor(editableDateContainer);
            }
        });
                function renderExecutionDateCell(reportId, executed, executionDate) {
            const container = document.getElementById(`execution-date-content-${reportId}`);
            if (!container) return;

            if (!executed) {
                container.innerHTML = '';
                return;
            }

            const dateValue = executionDate && executionDate.trim() !== ''
                ? executionDate
                : new Date().toISOString().slice(0, 10);

            const canEdit = @json($canInlineEditExecutionDate);

            if (!canEdit) {
                container.textContent = dateValue;
                return;
            }

            container.innerHTML = `
                <div
                    class="inline-date-editable"
                    data-report-id="${reportId}"
                    data-value="${escapeInlineValue(dateValue)}"
                >
                    <span class="inline-date-trigger">${escapeInlineValue(dateValue)}</span>
                </div>
            `;
        }
        let CURRENT_REPORT_ID = null;

        async function openEditReportModal(reportId) {
            CURRENT_REPORT_ID = reportId;

            resetEditReportModalFields();

            document.getElementById('editReportModal').classList.remove('hidden');
            document.getElementById('editReportModal').classList.add('flex');

            try {
                const response = await fetch(
                    "{{ route('admin.preventive-reports.edit-data', ['reportDetail' => '__REPORT_ID__']) }}".replace('__REPORT_ID__', reportId),
                    {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    }
                );

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'No fue posible cargar el reporte.');
                }

                fillSelect(editArea, data.areas || [], data.report.area_id, 'Seleccione un área');

                await reloadElementsByArea(data.report.area_id, data.report.element_id);
                await reloadComponentsByElement(data.report.element_id, data.report.component_id);
                await reloadDiagnosticsByComponent(data.report.component_id, data.report.diagnostic_id);
                await reloadConditionsByComponent(data.report.component_id, data.report.condition_id);

                if (editRecommendation) {
                    editRecommendation.value = data.report.recommendation || '';
                }

                if (editNewDate) {
                    editNewDate.value = data.report.report_date || new Date().toISOString().slice(0, 10);
                }
            } catch (error) {
                showInlineToast(error.message || 'No fue posible cargar los datos del reporte.', 'error');
                closeEditReportModal();
            }
        }

        function closeEditReportModal() {
            CURRENT_REPORT_ID = null;
            resetEditReportModalFields();

            document.getElementById('editReportModal').classList.add('hidden');
            document.getElementById('editReportModal').classList.remove('flex');
        }

        document.addEventListener('change', function (e) {
            if (e.target.name === 'edit-date-mode') {
                const input = document.getElementById('edit-new-date');

                if (e.target.value === 'new') {
                    input.classList.remove('hidden');
                } else {
                    input.classList.add('hidden');
                }
            }
        });

        const editArea = document.getElementById('edit-area');
        const editElement = document.getElementById('edit-element');
        const editComponent = document.getElementById('edit-component');
        const editDiagnostic = document.getElementById('edit-diagnostic');
        const editRecommendation = document.getElementById('edit-recommendation');
        const editCondition = document.getElementById('edit-condition');
        const editNewDate = document.getElementById('edit-new-date');

        function fillSelect(select, items, selectedValue = null, placeholder = 'Seleccione') {
            if (!select) return;

            const normalizedSelected = selectedValue !== null && selectedValue !== undefined
                ? String(selectedValue)
                : '';

            select.innerHTML = '';

            const firstOption = document.createElement('option');
            firstOption.value = '';
            firstOption.textContent = placeholder;
            select.appendChild(firstOption);

            items.forEach(item => {
                const option = document.createElement('option');
                option.value = String(item.id);
                option.textContent = item.code ? `${item.code} - ${item.name}` : item.name;
                option.selected = String(item.id) === normalizedSelected;
                select.appendChild(option);
            });
        }

        function resetEditReportModalFields() {
            fillSelect(editArea, [], null, '');
            fillSelect(editElement, [], null, '');
            fillSelect(editComponent, [], null, '');
            fillSelect(editDiagnostic, [], null, '');
            fillSelect(editCondition, [], null, '');

            if (editRecommendation) {
                editRecommendation.value = '';
            }

            if (editNewDate) {
                editNewDate.value = '';
                editNewDate.classList.add('hidden');
            }

            const keepRadio = document.querySelector('input[name="edit-date-mode"][value="keep"]');
            if (keepRadio) {
                keepRadio.checked = true;
            }
        }

        async function fetchJsonOrFail(url, defaultMessage) {
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            let data = null;

            try {
                data = await response.json();
            } catch (_) {
                data = null;
            }

            if (!response.ok) {
                throw new Error(data?.message || defaultMessage);
            }

            return data;
        }

async function reloadElementsByArea(areaId, selectedElementId = null) {
    fillSelect(editElement, [], null, 'Cargando activos...');
    fillSelect(editComponent, [], null, 'Seleccione un componente');
    fillSelect(editDiagnostic, [], null, 'Seleccione un diagnóstico');
    fillSelect(editCondition, [], null, 'Seleccione una condición');

    if (!areaId) {
        fillSelect(editElement, [], null, 'Seleccione un activo');
        return;
    }

    const url = new URL(
        "{{ route('admin.preventive-report-data.elements-by-area', ['area' => '__AREA__']) }}".replace('__AREA__', areaId),
        window.location.origin
    );

    url.searchParams.set('group_id', "{{ $group->id }}");

    const data = await fetchJsonOrFail(
        url.toString(),
        'No fue posible cargar los activos.'
    );

    fillSelect(editElement, data || [], selectedElementId, 'Seleccione un activo');
}

        async function reloadComponentsByElement(elementId, selectedComponentId = null) {
            fillSelect(editComponent, [], null, 'Cargando componentes...');
            fillSelect(editDiagnostic, [], null, 'Seleccione un diagnóstico');
            fillSelect(editCondition, [], null, 'Seleccione una condición');

            if (!elementId) {
                fillSelect(editComponent, [], null, 'Seleccione un componente');
                return;
            }

            const data = await fetchJsonOrFail(
                "{{ route('admin.preventive-report-data.components-by-element', ['element' => '__ELEMENT__']) }}".replace('__ELEMENT__', elementId),
                'No fue posible cargar los componentes.'
            );

            fillSelect(editComponent, data || [], selectedComponentId, 'Seleccione un componente');
        }

        async function reloadDiagnosticsByComponent(componentId, selectedDiagnosticId = null) {
            fillSelect(editDiagnostic, [], null, 'Cargando diagnósticos...');

            if (!componentId) {
                fillSelect(editDiagnostic, [], null, 'Seleccione un diagnóstico');
                return;
            }

            const data = await fetchJsonOrFail(
                "{{ route('admin.preventive-report-data.diagnostics-by-component', ['component' => '__COMPONENT__']) }}".replace('__COMPONENT__', componentId),
                'No fue posible cargar los diagnósticos.'
            );

            fillSelect(editDiagnostic, data || [], selectedDiagnosticId, 'Seleccione un diagnóstico');
        }

        if (editArea) {
            editArea.addEventListener('change', async function () {
                try {
                    await reloadElementsByArea(this.value, null);
                } catch (error) {
                    showInlineToast(error.message || 'Error cargando activos.', 'error');
                }
            });
        }

        if (editElement) {
            editElement.addEventListener('change', async function () {
                try {
                    await reloadComponentsByElement(this.value, null);
                } catch (error) {
                    showInlineToast(error.message || 'Error cargando componentes.', 'error');
                }
            });
        }

        if (editComponent) {
            editComponent.addEventListener('change', async function () {
                try {
                    await reloadDiagnosticsByComponent(this.value, null);
                    await reloadConditionsByComponent(this.value, null);
                } catch (error) {
                    showInlineToast(error.message || 'Error cargando diagnósticos o condiciones.', 'error');
                }
            });
        }

        document.getElementById('saveEditReportBtn').addEventListener('click', async function () {

            if (!CURRENT_REPORT_ID) return;

            const dateMode = document.querySelector('input[name="edit-date-mode"]:checked')?.value;

            const payload = {
                area_id: editArea.value,
                element_id: editElement.value,
                component_id: editComponent.value,
                diagnostic_id: editDiagnostic.value,
                condition_id: editCondition.value,
                recommendation: editRecommendation.value,
                date_mode: dateMode,
                new_date: editNewDate.value,
            };

            try {
                const response = await fetch(
                    "{{ route('admin.preventive-reports.admin-update', ['reportDetail' => '__ID__']) }}".replace('__ID__', CURRENT_REPORT_ID),
                    {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                        body: JSON.stringify(payload),
                    }
                );

                const data = await response.json();

                if (!response.ok) throw new Error(data.message);

                showInlineToast('Reporte actualizado correctamente');
                closeEditReportModal();

                location.reload(); // luego optimizamos esto

            } catch (err) {
                showInlineToast(err.message, 'error');
            }
        });

        async function reloadConditionsByComponent(componentId, selectedConditionId = null) {
            fillSelect(editCondition, [], null, 'Cargando condiciones...');

            if (!componentId) {
                fillSelect(editCondition, [], null, 'Seleccione una condición');
                return;
            }

            const data = await fetchJsonOrFail(
                "{{ route('admin.preventive-report-data.conditions-by-component', ['component' => '__COMPONENT__']) }}".replace('__COMPONENT__', componentId),
                'No fue posible cargar las condiciones.'
            );

            fillSelect(editCondition, data || [], selectedConditionId, 'Seleccione una condición');
        }

        async function toggleReportStatus(reportId) {
    const confirmed = confirm('¿Seguro que deseas ELIMINAR este reporte?');
    if (!confirmed) return;

    try {
        const response = await fetch(
            "{{ route('admin.preventive-reports.toggle-status', ['reportDetail' => '__ID__']) }}".replace('__ID__', reportId),
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
            throw new Error(data.message || 'No fue posible modificar el reporte.');
        }

        showInlineToast(data.message || 'Operación realizada correctamente.', 'success');
        location.reload();
    } catch (error) {
        showInlineToast(error.message || 'Ocurrió un error al modificar el reporte.', 'error');
    }
}
        </script>
    </body>
</html>