@extends('layouts.admin')

@section('title', 'Indicadores')
@section('header_title', 'Indicadores')

@section('content')
    <div
        class="space-y-8"
        data-indicators-module
        data-route="{{ $dataRoute }}"
    >
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-slate-900">
                    Indicadores
                </h2>

                <p class="mt-2 max-w-3xl text-slate-600">
                    Indicadores preventivos por cliente, agrupación y rango de fechas.
                </p>
            </div>

            <div class="inline-flex w-fit items-center rounded-full bg-[#d94d33]/10 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-[#d94d33]">
                Reportes preventivos
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="grid gap-4 xl:grid-cols-[minmax(220px,1fr)_minmax(260px,1.2fr)_180px_180px_auto]">
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">
                        Cliente
                    </label>

                    <select
                        id="indicator_client_id"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
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
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">
                        Agrupación
                    </label>

                    <select
                        id="indicator_group_id"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
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
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">
                        Desde
                    </label>

                    <input
                        id="indicator_date_from"
                        type="date"
                        value="{{ $defaultDateFrom }}"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                    >
                </div>

                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">
                        Hasta
                    </label>

                    <input
                        id="indicator_date_to"
                        type="date"
                        value="{{ $defaultDateTo }}"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                    >
                </div>

                <div class="flex items-end">
                    <button
                        type="button"
                        id="indicator_apply_filters"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-[#d94d33] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                    >
                        Consultar
                    </button>
                </div>
            </div>
        </div>

        <div id="indicator_loading" class="hidden rounded-2xl border border-slate-200 bg-white px-5 py-4 text-sm font-semibold text-slate-600 shadow-sm">
            Cargando indicadores...
        </div>

        <div id="indicator_empty" class="hidden rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-slate-100 text-slate-500">
                <i data-lucide="search-x" class="h-8 w-8"></i>
            </div>

            <h3 class="mt-5 text-xl font-semibold text-slate-900">
                Sin datos para el filtro seleccionado
            </h3>

            <p class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                No se encontraron reportes preventivos para el cliente, agrupación y rango seleccionado.
            </p>
        </div>

        <div id="indicator_content" class="space-y-8">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Activos</p>
                    <p id="metric_total_elements" class="mt-3 text-3xl font-bold text-slate-900">0</p>
                    <p class="mt-1 text-sm text-slate-500">Activos en agrupación</p>
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

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Componentes evaluados</p>
                    <p id="metric_evaluated_components" class="mt-3 text-3xl font-bold text-slate-900">0</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Diagnósticos registrados</p>
                    <p id="metric_diagnostics" class="mt-3 text-3xl font-bold text-slate-900">0</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Pendientes de ejecución</p>
                    <p id="metric_pending_execution" class="mt-3 text-3xl font-bold text-slate-900">0</p>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">
                                Distribución por condición
                            </h3>
                            <p class="mt-1 text-sm text-slate-500">
                                Registros preventivos agrupados por condición.
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

            <div class="grid gap-6 xl:grid-cols-3">
                <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h3 class="text-lg font-semibold text-slate-900">Top activos</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Activo</th>
                                    <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Total</th>
                                    <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Críticos</th>
                                </tr>
                            </thead>
                            <tbody id="table_top_elements" class="divide-y divide-slate-200 bg-white"></tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h3 class="text-lg font-semibold text-slate-900">Top componentes</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Componente</th>
                                    <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Total</th>
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
                                    <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Total</th>
                                </tr>
                            </thead>
                            <tbody id="table_top_diagnostics" class="divide-y divide-slate-200 bg-white"></tbody>
                        </table>
                    </div>
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
        };

        document.addEventListener('DOMContentLoaded', () => {
            const clientSelect = document.getElementById('indicator_client_id');
            const groupSelect = document.getElementById('indicator_group_id');
            const applyButton = document.getElementById('indicator_apply_filters');

            clientSelect?.addEventListener('change', () => {
                filterGroupsByClient();
                loadIndicators();
            });

            groupSelect?.addEventListener('change', loadIndicators);
            document.getElementById('indicator_date_from')?.addEventListener('change', loadIndicators);
            document.getElementById('indicator_date_to')?.addEventListener('change', loadIndicators);
            applyButton?.addEventListener('click', loadIndicators);

            filterGroupsByClient();
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
            document.getElementById('indicator_loading')?.classList.toggle('hidden', !isLoading);
        }

        function renderIndicators(data) {
            const summary = data.summary || {};
            const charts = data.charts || {};
            const tables = data.tables || {};

            setText('metric_total_elements', summary.total_elements ?? 0);
            setText('metric_inspected_elements', summary.inspected_elements ?? 0);
            setText('metric_not_inspected_elements', `${summary.not_inspected_elements ?? 0} sin inspección`);
            setText('metric_coverage', `${summary.coverage ?? 0}%`);
            setText('metric_preventive_reports', summary.preventive_reports ?? 0);
            setText('metric_evaluated_components', summary.evaluated_components ?? 0);
            setText('metric_diagnostics', summary.diagnostics ?? 0);
            setText('metric_pending_execution', summary.pending_execution ?? 0);

            const hasData = Number(summary.preventive_reports || 0) > 0 || Number(summary.evaluated_components || 0) > 0;

            document.getElementById('indicator_empty')?.classList.toggle('hidden', hasData);
            document.getElementById('indicator_content')?.classList.toggle('hidden', false);

            renderConditionChart(charts.condition_distribution || []);
            renderWeeklyChart(charts.reports_by_week || []);

            renderTopElements(tables.top_elements || []);
            renderSimpleTable('table_top_components', tables.top_components || [], 'component');
            renderSimpleTable('table_top_diagnostics', tables.top_diagnostics || [], 'diagnostic');

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

        function renderTopElements(rows) {
            const tbody = document.getElementById('table_top_elements');

            if (!tbody) {
                return;
            }

            if (!rows.length) {
                tbody.innerHTML = emptyRow(3);
                return;
            }

            tbody.innerHTML = rows.map(row => `
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3 text-sm font-semibold text-slate-800">${escapeHtml(row.name)}</td>
                    <td class="px-5 py-3 text-right text-sm text-slate-700">${row.total}</td>
                    <td class="px-5 py-3 text-right text-sm text-slate-700">${row.critical}</td>
                </tr>
            `).join('');
        }

        function renderSimpleTable(id, rows) {
            const tbody = document.getElementById(id);

            if (!tbody) {
                return;
            }

            if (!rows.length) {
                tbody.innerHTML = emptyRow(2);
                return;
            }

            tbody.innerHTML = rows.map(row => `
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3 text-sm font-semibold text-slate-800">${escapeHtml(row.name)}</td>
                    <td class="px-5 py-3 text-right text-sm text-slate-700">${row.total}</td>
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
    </script>
@endsection