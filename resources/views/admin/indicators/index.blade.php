@extends('layouts.admin')

@section('title', 'Indicadores')
@section('header_title', 'Indicadores')
@section('content')
    <div
        class="space-y-8"
        data-indicators-module
        data-route="{{ $dataRoute }}"
        data-semaphore-route="{{ $semaphoreDataRoute }}"
    >
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex flex-wrap items-center gap-3">
                <button
                    type="button"
                    id="indicator_show_dashboard"
                    onclick="showIndicatorDashboard()"
                    class="inline-flex items-center gap-2 rounded-xl bg-[#d94d33] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                >
                    <i data-lucide="bar-chart-3" class="h-4 w-4"></i>
                    Indicadores
                </button>

                <button
                    type="button"
                    id="indicator_show_semaphore"
                    onclick="showIndicatorSemaphore()"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                >
                    <i data-lucide="traffic-cone" class="h-4 w-4"></i>
                    Semáforo
                </button>

                <div class="inline-flex w-fit items-center rounded-full bg-[#d94d33]/10 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-[#d94d33]">
                    Reportes preventivos
                </div>
            </div>
        </div>

        {{-- FILTROS PRINCIPALES --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="grid gap-3 xl:grid-cols-[minmax(180px,1fr)_minmax(220px,1.2fr)_minmax(200px,1fr)_145px_145px_auto] lg:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-500">
                        Cliente
                    </label>

                    <select
                        id="indicator_client_id"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                    >
                        <option value="">Todos los clientes</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">
                                {{ $client->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-500">
                        Agrupación
                    </label>

                    <select
                        id="indicator_group_id"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                    >
                        <option value="">Todas las agrupaciones</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" data-client-id="{{ $group->client_id }}">
                                {{ $group->client?->name }} — {{ $group->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-500">
                        Tipo de activo
                    </label>

                    <select
                        id="indicator_element_type_id"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                    >
                        <option value="">Todos los tipos de activo</option>
                        @foreach($elementTypeOptions as $option)
                        <option
                            value="{{ $option['element_type_id'] }}"
                            data-client-id="{{ $option['client_id'] }}"
                            data-group-id="{{ $option['group_id'] }}"
                            data-has-semaphore="{{ !empty($option['has_semaphore']) ? '1' : '0' }}"
                        >
                            {{ $option['element_type_name'] }}
                        </option>
                        @endforeach
                    </select>

                    <p id="element_type_hint" class="mt-1 text-[11px] leading-4 text-slate-500">
                        Si seleccionas todos los tipos, las condiciones se resumen por criticidad.
                    </p>
                </div>

                <div>
                    <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-500">
                        Desde
                    </label>

                    <input
                        id="indicator_date_from"
                        type="date"
                        value="{{ $defaultDateFrom }}"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                    >
                </div>

                <div>
                    <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-500">
                        Hasta
                    </label>

                    <input
                        id="indicator_date_to"
                        type="date"
                        value="{{ $defaultDateTo }}"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                    >
                </div>

                <div>
                    <label class="mb-1.5 block select-none text-[11px] font-bold uppercase tracking-wide text-transparent">
                        Acción
                    </label>

                    <button
                        type="button"
                        id="indicator_apply_filters"
                        class="inline-flex h-[42px] w-full items-center justify-center gap-2 rounded-xl bg-[#d94d33] px-4 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                    >
                        <i data-lucide="search" class="h-4 w-4"></i>
                        Consultar
                    </button>
                </div>
            </div>
        </div>

        {{-- LOADING INDICADORES --}}
        <div id="indicator_loading" class="pointer-events-none fixed bottom-5 left-1/2 z-[9998] hidden -translate-x-1/2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 shadow-lg transition duration-200">
            Cargando indicadores...
        </div>

        {{-- EMPTY INDICADORES --}}
        <div id="indicator_empty" class="hidden rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-slate-100 text-slate-500">
                <i data-lucide="search-x" class="h-8 w-8"></i>
            </div>

            <h3 class="mt-5 text-xl font-semibold text-slate-900">
                Sin datos para el filtro seleccionado
            </h3>

            <p class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                No se encontraron reportes preventivos para el cliente, agrupación, tipo de activo y rango seleccionado.
            </p>
        </div>

        {{-- CONTENIDO INDICADORES --}}
        <div id="indicator_content" class="space-y-8">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Activos</p>
                    <p id="metric_total_elements" class="mt-3 text-3xl font-bold text-slate-900">0</p>
                    <p class="mt-1 text-sm text-slate-500">Activos filtrados</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Inspeccionados</p>
                    <p id="metric_inspected_elements" class="mt-3 text-3xl font-bold text-slate-900">0</p>
                    <p id="metric_not_inspected_elements" class="mt-1 text-sm text-slate-500">0 sin inspección</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Cobertura</p>
                    <p id="metric_coverage" class="mt-3 text-3xl font-bold text-slate-900">0%</p>
                    <p class="mt-1 text-sm text-slate-500">Activos con reporte preventivo</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Reportes preventivos</p>
                    <p id="metric_preventive_reports" class="mt-3 text-3xl font-bold text-slate-900">0</p>
                    <p class="mt-1 text-sm text-slate-500">Reportes únicos del rango</p>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-4">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Componentes evaluados</p>
                    <p id="metric_evaluated_components" class="mt-3 text-3xl font-bold text-slate-900">0</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Diagnósticos registrados</p>
                    <p id="metric_diagnostics" class="mt-3 text-3xl font-bold text-slate-900">0</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Novedades con atención</p>
                    <p id="metric_attention_findings" class="mt-3 text-3xl font-bold text-slate-900">0</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Pendientes de ejecución</p>
                    <p id="metric_pending_execution" class="mt-3 text-3xl font-bold text-slate-900">0</p>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div>
                            <h3 id="condition_chart_title" class="text-lg font-semibold text-slate-900">
                                Distribución por criticidad
                            </h3>
                            <p id="condition_chart_description" class="mt-1 text-sm text-slate-500">
                                Cuando hay varios tipos de activo, se resume por criticidad para evitar mezclar condiciones distintas.
                            </p>
                        </div>
                    </div>

                    <div class="h-[320px]">
                        <canvas id="conditionChart"></canvas>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-slate-900">
                            Reportes por semana
                        </h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Evolución semanal de reportes preventivos.
                        </p>
                    </div>

                    <div class="h-[320px]">
                        <canvas id="weeklyChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="text-lg font-semibold text-slate-900">Resumen por tipo de activo</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Permite comparar tipos de activo sin mezclar condiciones propias de cada plantilla.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Tipo de activo</th>
                                <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Activos</th>
                                <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Inspeccionados</th>
                                <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Cobertura</th>
                                <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Novedades</th>
                                <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Atención</th>
                            </tr>
                        </thead>

                        <tbody id="table_summary_by_element_type" class="divide-y divide-slate-200 bg-white"></tbody>
                    </table>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h3 class="text-lg font-semibold text-slate-900">Activos con más novedades preventivas</h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Activo</th>
                                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Tipo</th>
                                    <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Novedades</th>
                                    <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Atención</th>
                                </tr>
                            </thead>

                            <tbody id="table_top_elements" class="divide-y divide-slate-200 bg-white"></tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h3 class="text-lg font-semibold text-slate-900">Top condiciones</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Se separan por tipo de activo para evitar ambigüedad entre condiciones repetidas.
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Tipo</th>
                                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Código</th>
                                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Condición</th>
                                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Criticidad</th>
                                    <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Total</th>
                                </tr>
                            </thead>

                            <tbody id="table_top_conditions" class="divide-y divide-slate-200 bg-white"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h3 class="text-lg font-semibold text-slate-900">Top componentes</h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Componente</th>
                                    <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Novedades</th>
                                    <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Atención</th>
                                </tr>
                            </thead>

                            <tbody id="table_top_components" class="divide-y divide-slate-200 bg-white"></tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h3 class="text-lg font-semibold text-slate-900">Top diagnósticos</h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Diagnóstico</th>
                                    <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Novedades</th>
                                    <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Atención</th>
                                </tr>
                            </thead>

                            <tbody id="table_top_diagnostics" class="divide-y divide-slate-200 bg-white"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- MODAL SEMÁFORO --}}
        <div
            id="semaphore_modal"
            class="fixed inset-0 z-[9998] hidden items-center justify-center bg-slate-900/60 px-4 py-4"
        >
            <div class="relative flex h-[90vh] w-full max-w-[1180px] flex-col overflow-hidden rounded-3xl bg-white shadow-2xl">
                <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full bg-orange-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-orange-700">
                            <i data-lucide="traffic-cone" class="h-3.5 w-3.5"></i>
                            Semáforo semanal
                        </div>

                        <h3 class="mt-2 text-xl font-bold text-slate-900">
                            Semáforo por área y activo
                        </h3>

                        <p id="semaphore_meta" class="mt-1 text-sm text-slate-500">
                            Selecciona año, semana y consulta.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-end gap-3">
                        <div class="w-32">
                            <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-500">
                                Año
                            </label>
                            <input
                                id="semaphore_year"
                                type="number"
                                min="2020"
                                max="2100"
                                value="{{ now()->isoWeekYear() }}"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            >
                        </div>

                        <div class="w-32">
                            <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-500">
                                Semana
                            </label>
                            <input
                                id="semaphore_week"
                                type="number"
                                min="1"
                                max="53"
                                value="{{ now()->isoWeek() }}"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            >
                        </div>

                        <button
                            type="button"
                            onclick="loadSemaphore()"
                            class="inline-flex h-[42px] items-center justify-center gap-2 rounded-xl bg-[#d94d33] px-4 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                        >
                            <i data-lucide="search" class="h-4 w-4"></i>
                            Consultar
                        </button>

                        <button
                            type="button"
                            onclick="closeSemaphoreModal()"
                            class="inline-flex h-[42px] items-center justify-center rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 bg-slate-50 px-5 py-3 text-xs font-semibold">
                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-emerald-700">OK</span>
                    <span class="rounded-full bg-red-100 px-3 py-1 text-red-700">Alta</span>
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-amber-700">Media</span>
                    <span class="rounded-full bg-blue-100 px-3 py-1 text-blue-700">Baja</span>
                    <span class="rounded-full bg-orange-100 px-3 py-1 text-orange-700">Cambio</span>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-500">N/A</span>
                </div>

                <div id="semaphore_loading" class="pointer-events-none absolute right-6 top-[132px] z-20 hidden rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 shadow-lg">
                    Cargando semáforo...
                </div>

                <div id="semaphore_empty" class="hidden px-5 py-10 text-center text-sm text-slate-500">
                    No hay datos para el semáforo seleccionado.
                </div>

                <div class="min-h-0 flex-1 overflow-auto">
                    <div id="semaphore_table_container" class="min-w-full"></div>
                </div>
            </div>
        </div>

        <div id="indicatorToastContainer" class="fixed bottom-5 right-5 z-[99999] space-y-3"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const indicatorState = {
            conditionChart: null,
            weeklyChart: null,
            mode: 'dashboard',
        };

        document.addEventListener('DOMContentLoaded', () => {
            const clientSelect = document.getElementById('indicator_client_id');
            const groupSelect = document.getElementById('indicator_group_id');
            const elementTypeSelect = document.getElementById('indicator_element_type_id');
            const applyButton = document.getElementById('indicator_apply_filters');

            clientSelect?.addEventListener('change', () => {
                filterGroupsByClient();
                filterElementTypes();
                updateElementTypeHint();
            });

            groupSelect?.addEventListener('change', () => {
                filterElementTypes();
                updateElementTypeHint();
            });

            elementTypeSelect?.addEventListener('change', () => {
                updateElementTypeHint();
            });

            document.getElementById('indicator_date_from')?.addEventListener('change', () => {
                // No consultar automáticamente para evitar parpadeo visual.
            });

            document.getElementById('indicator_date_to')?.addEventListener('change', () => {
                // No consultar automáticamente para evitar parpadeo visual.
            });

            applyButton?.addEventListener('click', () => {
                closeSemaphoreModal(false);
                indicatorState.mode = 'dashboard';
                loadIndicators();
});
            document.getElementById('semaphore_year')?.addEventListener('change', () => {
                // Se consulta con el botón Consultar del modal.
            });

            document.getElementById('semaphore_week')?.addEventListener('change', () => {
                // Se consulta con el botón Consultar del modal.
            });

            filterGroupsByClient();
            filterElementTypes();
            loadIndicators();

            if (window.lucide) {
                window.lucide.createIcons();
            }
        });

        function filterGroupsByClient() {
            const clientId = document.getElementById('indicator_client_id')?.value || '';
            const groupSelect = document.getElementById('indicator_group_id');

            if (!groupSelect) {
                return;
            }

            Array.from(groupSelect.options).forEach(option => {
                if (!option.value) {
                    option.hidden = false;
                    return;
                }

                option.hidden = clientId !== '' && option.dataset.clientId !== clientId;

                if (option.hidden && option.selected) {
                    groupSelect.value = '';
                }
            });
        }

        function filterElementTypes() {
            const clientId = document.getElementById('indicator_client_id')?.value || '';
            const groupId = document.getElementById('indicator_group_id')?.value || '';
            const elementTypeSelect = document.getElementById('indicator_element_type_id');

            if (!elementTypeSelect) {
                return;
            }

            const visibleValues = new Set();

            Array.from(elementTypeSelect.options).forEach(option => {
                if (!option.value) {
                    option.hidden = false;
                    return;
                }

                const matchesClient = clientId === '' || option.dataset.clientId === clientId;
                const matchesGroup = groupId === '' || option.dataset.groupId === groupId;

                option.hidden = !(matchesClient && matchesGroup);

                if (!option.hidden) {
                    visibleValues.add(option.value);
                }

                if (option.hidden && option.selected) {
                    elementTypeSelect.value = '';
                }
            });

            deduplicateVisibleElementTypeOptions(elementTypeSelect);
            updateElementTypeHint();
        }

        function deduplicateVisibleElementTypeOptions(select) {
            const seen = new Set();

            Array.from(select.options).forEach(option => {
                if (!option.value || option.hidden) {
                    return;
                }

                if (seen.has(option.value)) {
                    option.hidden = true;
                    return;
                }

                seen.add(option.value);
            });
        }

        function updateElementTypeHint() {
            const elementTypeSelect = document.getElementById('indicator_element_type_id');
            const elementTypeId = elementTypeSelect?.value || '';
            const selectedOption = elementTypeSelect?.selectedOptions?.[0] || null;
            const hasSemaphore = selectedOption?.dataset?.hasSemaphore === '1';
            const hint = document.getElementById('element_type_hint');

            if (!hint) {
                return;
            }

            if (!elementTypeId) {
                hint.textContent = 'Si seleccionas todos los tipos, las condiciones se resumen por criticidad.';
                return;
            }

            hint.textContent = hasSemaphore
                ? 'Este tipo de activo tiene semáforo semanal habilitado.'
                : 'Este tipo de activo no tiene semáforo semanal habilitado.';
        }


        function showIndicatorDashboard() {
            indicatorState.mode = 'dashboard';
            closeSemaphoreModal(false);

            const dashboardButton = document.getElementById('indicator_show_dashboard');
            const semaphoreButton = document.getElementById('indicator_show_semaphore');

            dashboardButton?.classList.remove('border', 'border-slate-300', 'bg-white', 'text-slate-700', 'hover:bg-slate-100');
            dashboardButton?.classList.add('bg-[#d94d33]', 'text-white', 'hover:bg-[#b83f29]');

            semaphoreButton?.classList.remove('bg-[#d94d33]', 'text-white', 'hover:bg-[#b83f29]');
            semaphoreButton?.classList.add('border', 'border-slate-300', 'bg-white', 'text-slate-700', 'hover:bg-slate-100');
        }

        function showIndicatorSemaphore() {
            indicatorState.mode = 'semaphore';

            const elementTypeSelect = document.getElementById('indicator_element_type_id');
            const selectedOption = elementTypeSelect?.selectedOptions?.[0] || null;
            const elementTypeId = elementTypeSelect?.value || '';
            const hasSemaphore = selectedOption?.dataset?.hasSemaphore === '1';

            if (!elementTypeId) {
                indicatorState.mode = 'dashboard';
                showIndicatorToast('Selecciona un tipo de activo para consultar el semáforo.', 'error');
                return;
            }

            if (!hasSemaphore) {
                indicatorState.mode = 'dashboard';
                showIndicatorToast('Este tipo de activo no se puede mostrar en semáforo porque no está habilitado.', 'error');
                return;
            }

            const dashboardButton = document.getElementById('indicator_show_dashboard');
            const semaphoreButton = document.getElementById('indicator_show_semaphore');

            semaphoreButton?.classList.remove('border', 'border-slate-300', 'bg-white', 'text-slate-700', 'hover:bg-slate-100');
            semaphoreButton?.classList.add('bg-[#d94d33]', 'text-white', 'hover:bg-[#b83f29]');

            dashboardButton?.classList.remove('bg-[#d94d33]', 'text-white', 'hover:bg-[#b83f29]');
            dashboardButton?.classList.add('border', 'border-slate-300', 'bg-white', 'text-slate-700', 'hover:bg-slate-100');

            openSemaphoreModal();
            loadSemaphore();
        }

        function openSemaphoreModal() {
            const modal = document.getElementById('semaphore_modal');

            if (!modal) {
                return;
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');

            if (window.lucide) {
                window.lucide.createIcons();
            }
        }

        function closeSemaphoreModal(resetMode = true) {
            const modal = document.getElementById('semaphore_modal');

            if (!modal) {
                return;
            }

            modal.classList.remove('flex');
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');

            if (resetMode) {
                indicatorState.mode = 'dashboard';
            }
        }

        async function loadSemaphore() {
            const module = document.querySelector('[data-indicators-module]');
            const route = module?.dataset.semaphoreRoute;

            if (!route) {
                showIndicatorToast('No se encontró la ruta del semáforo.', 'error');
                return;
            }

            const elementTypeSelect = document.getElementById('indicator_element_type_id');
            const selectedOption = elementTypeSelect?.selectedOptions?.[0] || null;
            const elementTypeId = elementTypeSelect?.value || '';
            const hasSemaphore = selectedOption?.dataset?.hasSemaphore === '1';

            if (!elementTypeId) {
                closeSemaphoreModal(false);
                indicatorState.mode = 'dashboard';
                showIndicatorToast('Selecciona un tipo de activo para consultar el semáforo.', 'error');
                return;
            }

            if (!hasSemaphore) {
                closeSemaphoreModal(false);
                indicatorState.mode = 'dashboard';
                showIndicatorToast('Este tipo de activo no se puede mostrar en semáforo porque no está habilitado.', 'error');
                return;
            }

            openSemaphoreModal();

            const params = new URLSearchParams({
                client_id: document.getElementById('indicator_client_id')?.value || '',
                group_id: document.getElementById('indicator_group_id')?.value || '',
                element_type_id: elementTypeId,
                year: document.getElementById('semaphore_year')?.value || '',
                week: document.getElementById('semaphore_week')?.value || '',
            });

            setSemaphoreLoading(true);

            try {
                const response = await fetch(`${route}?${params.toString()}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const data = await response.json();

                if (!response.ok || data.success === false) {
                    renderSemaphoreEmpty(data.message || 'No fue posible cargar el semáforo.');
                    showIndicatorToast(data.message || 'No fue posible cargar el semáforo.', 'error');
                    return;
                }

                renderSemaphore(data);
            } catch (error) {
                renderSemaphoreEmpty('Ocurrió un error de red al cargar el semáforo.');
                showIndicatorToast('Ocurrió un error de red al cargar el semáforo.', 'error');
            } finally {
                setSemaphoreLoading(false);
            }
        }

        function renderSemaphoreEmpty(message) {
            const empty = document.getElementById('semaphore_empty');
            const container = document.getElementById('semaphore_table_container');

            if (empty) {
                empty.textContent = message || 'No hay datos para el semáforo seleccionado.';
                empty.classList.remove('hidden');
            }

            if (container) {
                container.innerHTML = '';
            }

            setText('semaphore_meta', 'Sin datos para mostrar.');
        }

        function renderSemaphore(data) {
            const areas = data.areas || [];
            const meta = data.meta || {};
            const empty = document.getElementById('semaphore_empty');
            const container = document.getElementById('semaphore_table_container');

            setText(
                'semaphore_meta',
                `Semana ${meta.week || '—'} / ${meta.year || '—'} · ${meta.elements_count || 0} activos · ${meta.details_count || 0} registros preventivos`
            );

            if (!container) {
                return;
            }

            if (!areas.length) {
                renderSemaphoreEmpty('No hay activos para el filtro seleccionado.');
                return;
            }

            empty?.classList.add('hidden');

            container.innerHTML = `
                <table class="min-w-[920px] w-full divide-y divide-slate-200 text-sm">
                    <thead class="sticky top-0 z-10 bg-slate-50 shadow-sm">
                        <tr>
                            <th class="w-[220px] px-3 py-3 text-left text-[11px] font-bold uppercase tracking-wide text-slate-500">Área / Activo</th>
                            <th class="px-3 py-3 text-center text-[11px] font-bold uppercase tracking-wide text-slate-500">Cambio banda</th>
                            <th class="px-3 py-3 text-center text-[11px] font-bold uppercase tracking-wide text-slate-500">Estado banda</th>
                            <th class="px-3 py-3 text-center text-[11px] font-bold uppercase tracking-wide text-slate-500">Seguridad</th>
                            <th class="px-3 py-3 text-center text-[11px] font-bold uppercase tracking-wide text-slate-500">Descarga</th>
                            <th class="px-3 py-3 text-center text-[11px] font-bold uppercase tracking-wide text-slate-500">Limpiador</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        ${areas.map(renderSemaphoreArea).join('')}
                    </tbody>
                </table>
            `;

            if (window.lucide) {
                window.lucide.createIcons();
            }
        }

        function renderSemaphoreArea(area) {
            const rows = area.rows || [];

            return `
                <tr class="bg-slate-100">
                    <td colspan="6" class="px-4 py-3 text-sm font-bold uppercase tracking-wide text-slate-700">
                        ${escapeHtml(area.name || 'Sin área')} · ${area.elements_count || rows.length} activos
                    </td>
                </tr>
                ${rows.map(renderSemaphoreRow).join('')}
            `;
        }

        function renderSemaphoreRow(row) {
            return `
                <tr class="hover:bg-slate-50">
                    <td class="sticky left-0 z-[1] bg-white px-3 py-2.5 shadow-[1px_0_0_#e2e8f0]">
                        <div class="text-sm font-semibold text-slate-900">${escapeHtml(row.element_code || row.element_name || 'Sin activo')}</div>
                        ${row.element_name && row.element_name !== row.element_code ? `<div class="mt-0.5 max-w-[190px] truncate text-xs text-slate-500">${escapeHtml(row.element_name)}</div>` : ''}
                    </td>
                    <td class="px-3 py-2.5 text-center">${renderSemaphoreBadge(row.change_belt)}</td>
                    <td class="px-3 py-2.5 text-center">${renderSemaphoreBadge(row.belt_status)}</td>
                    <td class="px-3 py-2.5 text-center">${renderSemaphoreBadge(row.safety_condition)}</td>
                    <td class="px-3 py-2.5 text-center">${renderSemaphoreBadge(row.discharge)}</td>
                    <td class="px-3 py-2.5 text-center">${renderSemaphoreBadge(row.cleaner)}</td>
                </tr>
            `;
        }



        function setSemaphoreLoading(isLoading) {
            const loading = document.getElementById('semaphore_loading');
            const container = document.getElementById('semaphore_table_container');

            loading?.classList.toggle('hidden', !isLoading);

            if (container) {
                container.classList.toggle('opacity-60', isLoading);
                container.classList.toggle('pointer-events-none', isLoading);
                container.classList.add('transition', 'duration-150');
            }
        }


        function renderSemaphoreBadge(cell) {
            const value = cell || {};
            const label = value.label || 'N/A';
            const title = value.detail || label;

            const classes = {
                ok: 'bg-emerald-100 text-emerald-700',
                high: 'bg-red-100 text-red-700',
                medium: 'bg-amber-100 text-amber-700',
                low: 'bg-blue-100 text-blue-700',
                warning: 'bg-orange-100 text-orange-700',
                neutral: 'bg-slate-100 text-slate-500',
            };

            return `
                <span
                    title="${escapeHtml(title)}"
                    class="inline-flex max-w-[130px] items-center justify-center truncate rounded-full px-2.5 py-1 text-[11px] font-bold leading-none ${classes[value.level] || classes.neutral}"
                >
                    ${escapeHtml(label)}
                </span>
            `;
        }

        async function loadIndicators() {
            const module = document.querySelector('[data-indicators-module]');
            const route = module?.dataset.route;

            if (!route) {
                showIndicatorToast('No se encontró la ruta de indicadores.', 'error');
                return;
            }

            const params = new URLSearchParams({
                client_id: document.getElementById('indicator_client_id')?.value || '',
                group_id: document.getElementById('indicator_group_id')?.value || '',
                element_type_id: document.getElementById('indicator_element_type_id')?.value || '',
                date_from: document.getElementById('indicator_date_from')?.value || '',
                date_to: document.getElementById('indicator_date_to')?.value || '',
            });

            setIndicatorLoading(true);

            try {
                const response = await fetch(`${route}?${params.toString()}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const data = await response.json();

                if (!response.ok || data.success === false) {
                    showIndicatorToast(data.message || 'No fue posible cargar los indicadores.', 'error');
                    return;
                }

                renderIndicators(data);
            } catch (error) {
                showIndicatorToast('Ocurrió un error de red al cargar los indicadores.', 'error');
            } finally {
                setIndicatorLoading(false);
            }
        }

        function setIndicatorLoading(isLoading) {
            const loading = document.getElementById('indicator_loading');
            const content = document.getElementById('indicator_content');
            const applyButton = document.getElementById('indicator_apply_filters');

            loading?.classList.toggle('hidden', !isLoading);
            loading?.classList.toggle('opacity-100', isLoading);
            loading?.classList.toggle('opacity-0', !isLoading);

            if (content && !content.classList.contains('hidden')) {
                content.classList.toggle('opacity-60', isLoading);
                content.classList.toggle('pointer-events-none', isLoading);
                content.classList.add('transition', 'duration-150');
            }

            if (applyButton) {
                applyButton.disabled = isLoading;
                applyButton.classList.toggle('opacity-70', isLoading);
                applyButton.classList.toggle('cursor-wait', isLoading);
            }
        }

        function renderIndicators(data) {
            const summary = data.summary || {};
            const charts = data.charts || {};
            const tables = data.tables || {};
            const chartMode = charts.mode || 'severity';

            setText('metric_total_elements', summary.total_elements ?? 0);
            setText('metric_inspected_elements', summary.inspected_elements ?? 0);
            setText('metric_not_inspected_elements', `${summary.not_inspected_elements ?? 0} sin inspección`);
            setText('metric_coverage', `${summary.coverage ?? 0}%`);
            setText('metric_preventive_reports', summary.preventive_reports ?? 0);
            setText('metric_evaluated_components', summary.evaluated_components ?? 0);
            setText('metric_diagnostics', summary.diagnostics ?? 0);
            setText('metric_attention_findings', summary.attention_findings ?? 0);
            setText('metric_pending_execution', summary.pending_execution ?? 0);

            const hasData = Number(summary.preventive_reports || 0) > 0 || Number(summary.evaluated_components || 0) > 0;

            document.getElementById('indicator_empty')?.classList.toggle('hidden', hasData);
            document.getElementById('indicator_content')?.classList.toggle('hidden', !hasData);

            if (!hasData) {
                if (indicatorState.conditionChart) {
                    indicatorState.conditionChart.destroy();
                    indicatorState.conditionChart = null;
                }

                if (indicatorState.weeklyChart) {
                    indicatorState.weeklyChart.destroy();
                    indicatorState.weeklyChart = null;
                }

                return;
            }

            if (chartMode === 'condition') {
                setText('condition_chart_title', 'Distribución por condición');
                setText('condition_chart_description', 'Condiciones específicas del tipo de activo seleccionado.');
                renderConditionChart(charts.condition_distribution || []);
            } else {
                setText('condition_chart_title', 'Distribución por criticidad');
                setText('condition_chart_description', 'Resumen ejecutivo por criticidad. Evita mezclar condiciones propias de distintos tipos de activo.');
                renderConditionChart(charts.severity_distribution || []);
            }

            renderWeeklyChart(charts.reports_by_week || []);

            renderSummaryByElementType(tables.summary_by_element_type || []);
            renderTopElements(tables.top_elements || []);
            renderTopConditions(tables.top_conditions || []);
            renderAttentionTable('table_top_components', tables.top_components || [], 3);
            renderAttentionTable('table_top_diagnostics', tables.top_diagnostics || [], 3);

            if (window.lucide) {
                window.lucide.createIcons();
            }
        }

        function setText(id, value) {
            const element = document.getElementById(id);

            if (element) {
                element.textContent = value;
            }
        }

        function renderConditionChart(rows) {
            const canvas = document.getElementById('conditionChart');

            if (!canvas) {
                return;
            }

            if (indicatorState.conditionChart) {
                indicatorState.conditionChart.destroy();
            }

            indicatorState.conditionChart = new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: rows.map(row => row.label),
                    datasets: [{
                        data: rows.map(row => row.total),
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                    },
                },
            });
        }

        function renderWeeklyChart(rows) {
            const canvas = document.getElementById('weeklyChart');

            if (!canvas) {
                return;
            }

            if (indicatorState.weeklyChart) {
                indicatorState.weeklyChart.destroy();
            }

            indicatorState.weeklyChart = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: rows.map(row => row.label),
                    datasets: [{
                        label: 'Reportes',
                        data: rows.map(row => row.total),
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                            },
                        },
                    },
                    plugins: {
                        legend: {
                            display: false,
                        },
                    },
                },
            });
        }

        function renderSummaryByElementType(rows) {
            const tbody = document.getElementById('table_summary_by_element_type');

            if (!tbody) {
                return;
            }

            if (!rows.length) {
                tbody.innerHTML = emptyRow(6);
                return;
            }

            tbody.innerHTML = rows.map(row => `
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3 text-sm font-semibold text-slate-800">${escapeHtml(row.name)}</td>
                    <td class="px-5 py-3 text-right text-sm text-slate-700">${row.elements}</td>
                    <td class="px-5 py-3 text-right text-sm text-slate-700">${row.inspected}</td>
                    <td class="px-5 py-3 text-right text-sm text-slate-700">${row.coverage}%</td>
                    <td class="px-5 py-3 text-right text-sm text-slate-700">${row.findings}</td>
                    <td class="px-5 py-3 text-right text-sm font-semibold text-slate-900">${row.attention}</td>
                </tr>
            `).join('');
        }

        function renderTopElements(rows) {
            const tbody = document.getElementById('table_top_elements');

            if (!tbody) {
                return;
            }

            if (!rows.length) {
                tbody.innerHTML = emptyRow(4);
                return;
            }

            tbody.innerHTML = rows.map(row => `
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3 text-sm font-semibold text-slate-800">${escapeHtml(row.name)}</td>
                    <td class="px-5 py-3 text-sm text-slate-700">${escapeHtml(row.type)}</td>
                    <td class="px-5 py-3 text-right text-sm text-slate-700">${row.total}</td>
                    <td class="px-5 py-3 text-right text-sm font-semibold text-slate-900">${row.attention}</td>
                </tr>
            `).join('');
        }

        function renderTopConditions(rows) {
            const tbody = document.getElementById('table_top_conditions');

            if (!tbody) {
                return;
            }

            if (!rows.length) {
                tbody.innerHTML = emptyRow(5);
                return;
            }

            tbody.innerHTML = rows.map(row => `
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3 text-sm text-slate-700">${escapeHtml(row.type)}</td>
                    <td class="px-5 py-3 text-sm font-semibold text-slate-800">${escapeHtml(row.code)}</td>
                    <td class="px-5 py-3 text-sm font-semibold text-slate-800">${escapeHtml(row.name)}</td>
                    <td class="px-5 py-3 text-sm text-slate-700">${escapeHtml(row.severity_label)}</td>
                    <td class="px-5 py-3 text-right text-sm text-slate-700">${row.total}</td>
                </tr>
            `).join('');
        }

        function renderAttentionTable(id, rows, colspan) {
            const tbody = document.getElementById(id);

            if (!tbody) {
                return;
            }

            if (!rows.length) {
                tbody.innerHTML = emptyRow(colspan);
                return;
            }

            tbody.innerHTML = rows.map(row => `
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3 text-sm font-semibold text-slate-800">${escapeHtml(row.name)}</td>
                    <td class="px-5 py-3 text-right text-sm text-slate-700">${row.total}</td>
                    <td class="px-5 py-3 text-right text-sm font-semibold text-slate-900">${row.attention}</td>
                </tr>
            `).join('');
        }

        function emptyRow(colspan) {
            return `
                <tr>
                    <td colspan="${colspan}" class="px-5 py-8 text-center text-sm text-slate-500">
                        Sin datos para mostrar.
                    </td>
                </tr>
            `;
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function showIndicatorToast(message, type = 'success') {
            const container = document.getElementById('indicatorToastContainer');

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
                toast.classList.add('opacity-0', 'translate-y-2');
                toast.classList.add('transition', 'duration-300');

                setTimeout(() => toast.remove(), 350);
            }, 4000);
        }
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeSemaphoreModal();
            }
        });

        document.addEventListener('click', function (event) {
            const modal = document.getElementById('semaphore_modal');

            if (modal?.classList.contains('flex') && event.target === modal) {
                closeSemaphoreModal();
            }
        });
    </script>
@endsection