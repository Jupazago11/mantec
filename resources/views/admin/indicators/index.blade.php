@extends('layouts.admin')

@section('title', 'Indicadores')
@section('header_title', 'Indicadores')
@section('content')
    <div
        class="space-y-8"
        data-indicators-module
        data-route="{{ $dataRoute }}"
        data-semaphore-route="{{ $semaphoreDataRoute }}"
        data-semaphore-belt-change-update-route="{{ $semaphoreBeltChangeUpdateRoute }}"
        data-can-edit-semaphore="{{ $canEditSemaphore ? '1' : '0' }}"
        data-csrf-token="{{ csrf_token() }}"
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

                <button
                    type="button"
                    onclick="openSemaphoreTemplateConfig()"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                    title="Configurar plantillas de semáforo"
                >
                    <i data-lucide="settings-2" class="h-4 w-4"></i>
                    Configurar semáforo
                </button>

                <div class="inline-flex w-fit items-center rounded-full bg-[#d94d33]/10 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-[#d94d33]">
                    Reportes preventivos
                </div>
            </div>
        </div>

        {{-- FILTROS PRINCIPALES --}}
        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <div class="flex flex-wrap items-end gap-2">
                {{-- Cliente --}}
                <div class="flex min-w-[140px] flex-1 flex-col gap-1">
                    <span class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Cliente</span>
                    <select id="indicator_client_id" class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]">
                        <option value="">Todos</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" @selected(($defaultScope['client_id'] ?? null) == $client->id)>{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Agrupación --}}
                <div class="flex min-w-[180px] flex-[1.5] flex-col gap-1">
                    <span class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Agrupación</span>
                    <select id="indicator_group_id" class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]">
                        <option value="">Todas</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" data-client-id="{{ $group->client_id }}" @selected(($defaultScope['group_id'] ?? null) == $group->id)>
                                {{ $group->client?->name }} — {{ $group->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Tipo de activo --}}
                <div class="flex min-w-[140px] flex-1 flex-col gap-1">
                    <span class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Tipo de activo</span>
                    <select id="indicator_element_type_id" class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]">
                        <option value="">Todos</option>
                        @foreach($elementTypeOptions as $option)
                            <option value="{{ $option['element_type_id'] }}" data-client-id="{{ $option['client_id'] }}" data-group-id="{{ $option['group_id'] }}" data-has-semaphore="{{ !empty($option['has_semaphore']) ? '1' : '0' }}">
                                {{ $option['element_type_name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Año (oculto si solo hay uno) --}}
                <div id="indicator_year_wrapper" class="flex w-24 flex-none flex-col gap-1" @if(count($availableYears) <= 1) style="display:none" @endif>
                    <span id="indicator_year_label" class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Año</span>
                    <select id="indicator_year" class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]">
                        @foreach($availableYears as $yr)
                            <option value="{{ $yr }}" @selected($yr == $defaultYear)>{{ $yr }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Desde --}}
                <div class="flex w-28 flex-none flex-col gap-1">
                    <span class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Desde</span>
                    <select id="indicator_week_from" class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"></select>
                </div>

                {{-- Hasta --}}
                <div class="flex w-28 flex-none flex-col gap-1">
                    <span class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Hasta</span>
                    <select id="indicator_week_to" class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"></select>
                </div>

                {{-- Acciones --}}
                <div class="flex flex-none items-end gap-2 self-end">
                    <button type="button" id="indicator_apply_filters" class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-[#d94d33] px-4 text-sm font-semibold text-white transition hover:bg-[#b83f29]">
                        <i data-lucide="search" class="h-3.5 w-3.5"></i>
                        Consultar
                    </button>
                    <button type="button" id="indicator_reset_filters" title="Restablecer filtros" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700">
                        <i data-lucide="rotate-ccw" class="h-3.5 w-3.5"></i>
                    </button>
                </div>
            </div>
            <p id="element_type_hint" class="mt-1.5 hidden text-[10px] text-slate-400"></p>
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
            <div class="grid gap-4 md:grid-cols-3">
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
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-3xl border border-slate-200 bg-red-50 p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-red-600">Alta</p>
                    <p id="metric_high_findings" class="mt-3 text-3xl font-bold text-red-700">0</p>
                    <p class="mt-1 text-sm text-slate-500">Criticidad alta</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-yellow-50 p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-yellow-600">Media</p>
                    <p id="metric_medium_findings" class="mt-3 text-3xl font-bold text-yellow-600">0</p>
                    <p class="mt-1 text-sm text-slate-500">Criticidad media</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-blue-50 p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-blue-600">Baja</p>
                    <p id="metric_low_findings" class="mt-3 text-3xl font-bold text-blue-700">0</p>
                    <p class="mt-1 text-sm text-slate-500">Criticidad baja</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-green-50 p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-green-600">OK</p>
                    <p id="metric_ok_findings" class="mt-3 text-3xl font-bold text-green-700">0</p>
                    <p class="mt-1 text-sm text-slate-500">Sin novedad</p>
                </div>
            </div>


            <div class="grid gap-6 xl:grid-cols-2">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 id="condition_chart_title" class="text-lg font-semibold text-slate-900">
                                    Distribución por criticidad
                                </h3>
                                <span id="fallback_badge_condition_chart" class="hidden rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-600">Último reporte disponible</span>
                            </div>
                            <p id="condition_chart_description" class="mt-1 text-sm text-slate-500">
                                Cuando hay varios tipos de activo, se resume por criticidad para evitar mezclar condiciones distintas.
                            </p>
                        </div>
                    </div>

                    <div class="relative h-[320px]">
                        <canvas id="conditionChart"></canvas>
                        <p id="condition_chart_notice" class="pointer-events-none absolute inset-0 hidden items-center justify-center text-center text-sm text-slate-400" style="display:none">
                            Sin reportes en el rango de semanas seleccionado.
                        </p>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 id="weekly_chart_title" class="text-lg font-semibold text-slate-900">
                                    Reportes por semana
                                </h3>
                                <span id="fallback_badge_weekly_chart" class="hidden rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-600">Año completo</span>
                            </div>
                            <p id="weekly_chart_description" class="mt-1 text-sm text-slate-500">
                                Evolución semanal de reportes preventivos.
                            </p>
                        </div>
                        <button
                            type="button"
                            id="weekly_chart_toggle"
                            onclick="toggleWeeklyChartMode()"
                            class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl border border-slate-300 text-slate-500 transition hover:border-[#d94d33] hover:text-[#d94d33]"
                            title="Alternar entre Reportes por semana y Activos revisados por semana"
                        >
                            <i data-lucide="eye" class="h-4 w-4"></i>
                        </button>
                    </div>

                    <div class="relative h-[320px]">
                        <canvas id="weeklyChart"></canvas>
                        <p id="weekly_chart_notice" class="pointer-events-none absolute inset-0 hidden items-center justify-center text-center text-sm text-slate-400" style="display:none">
                            Sin reportes en el rango de semanas seleccionado.
                        </p>
                    </div>
                </div>
            </div>

            {{-- INDICADORES ANUALES --}}
            <div id="annual_indicators_section" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-5">
                    <div class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-slate-600">
                        <i data-lucide="calendar" class="h-3.5 w-3.5"></i>
                        Indicadores anuales — <span id="annual_year_label">{{ now()->year }}</span>
                    </div>
                    <p id="annual_subtitle" class="mt-2 text-sm text-slate-500">Estado más reciente por activo durante el año completo, independiente del rango de semanas seleccionado.</p>
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    {{-- Cambio de banda --}}
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="mb-4 flex items-center justify-between">
                            <h4 class="text-sm font-bold text-slate-800">Cambio de banda</h4>
                            <button type="button" id="belt_chart_eye" onclick="toggleAnnualChart('belt')"
                                class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-200 hover:text-slate-700"
                                title="Ver distribución por condición">
                                <i data-lucide="eye" class="h-4 w-4"></i>
                            </button>
                        </div>
                        <div id="belt_counters_wrap" style="overflow:hidden;transition:opacity .3s ease,max-height .35s ease;max-height:300px;opacity:1;">
                            <div class="grid grid-cols-3 gap-3">
                                <div id="belt_yes_card" class="relative cursor-default rounded-xl border border-orange-200 bg-orange-50 p-3 text-center">
                                    <p id="annual_belt_yes" class="text-2xl font-bold text-orange-700">0</p>
                                    <p class="mt-1 text-[11px] font-semibold uppercase tracking-wide text-orange-600">Sí requiere</p>
                                    <div id="belt_yes_tooltip" class="pointer-events-none fixed z-[9999] hidden w-max max-w-[220px] rounded-xl border border-orange-200 bg-white p-3 text-left shadow-lg">
                                        <p class="mb-1.5 text-[10px] font-bold uppercase tracking-wide text-orange-500">Activos</p>
                                        <ul id="belt_yes_tooltip_list" class="space-y-0.5 text-xs text-slate-700"></ul>
                                    </div>
                                </div>
                                <div class="rounded-xl border border-green-200 bg-green-50 p-3 text-center">
                                    <p id="annual_belt_no" class="text-2xl font-bold text-green-700">0</p>
                                    <p class="mt-1 text-[11px] font-semibold uppercase tracking-wide text-green-600">No requiere</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-white p-3 text-center">
                                    <p id="annual_belt_na" class="text-2xl font-bold text-slate-500">0</p>
                                    <p class="mt-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Sin dato</p>
                                </div>
                            </div>
                            <p class="mt-3 text-[11px] text-slate-400">«Sí» = último registro con cambio de banda requerido. Total activos: <span id="annual_belt_total" class="font-semibold text-slate-500">0</span></p>
                        </div>
                        <div id="belt_chart_container" style="overflow:hidden;transition:opacity .3s ease,max-height .35s ease;max-height:0;opacity:0;pointer-events:none;">
                            <div class="mt-1 rounded-xl bg-white p-3">
                                <div class="mb-1 flex items-center justify-between">
                                    <span class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Estado banda</span>
                                    <span id="belt_chart_badge" class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">0 registros</span>
                                </div>
                                <div id="belt_chart_wrap" style="height:160px">
                                    <canvas id="belt_annual_chart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Seguridad --}}
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="mb-4 flex items-center justify-between">
                            <h4 class="text-sm font-bold text-slate-800">Seguridad</h4>
                            <button type="button" id="security_chart_eye" onclick="toggleAnnualChart('security')"
                                class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-200 hover:text-slate-700"
                                title="Ver distribución por condición">
                                <i data-lucide="eye" class="h-4 w-4"></i>
                            </button>
                        </div>
                        <div id="security_counters_wrap" style="overflow:hidden;transition:opacity .3s ease,max-height .35s ease;max-height:300px;opacity:1;">
                            <div class="grid grid-cols-3 gap-3">
                                <div class="rounded-xl border border-green-200 bg-green-50 p-3 text-center">
                                    <p id="annual_security_ok" class="text-2xl font-bold text-green-700">0</p>
                                    <p class="mt-1 text-[11px] font-semibold uppercase tracking-wide text-green-600">OK</p>
                                </div>
                                <div class="rounded-xl border border-red-200 bg-red-50 p-3 text-center">
                                    <p id="annual_security_novedad" class="text-2xl font-bold text-red-700">0</p>
                                    <p class="mt-1 text-[11px] font-semibold uppercase tracking-wide text-red-600">Con novedad</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-white p-3 text-center">
                                    <p id="annual_security_na" class="text-2xl font-bold text-slate-500">0</p>
                                    <p class="mt-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Sin dato</p>
                                </div>
                            </div>
                            <p class="mt-3 text-[11px] text-slate-400">Guardas, cubiertas, plataforma y estructura. Total activos: <span id="annual_security_total" class="font-semibold text-slate-500">0</span></p>
                        </div>
                        <div id="security_chart_container" style="overflow:hidden;transition:opacity .3s ease,max-height .35s ease;max-height:0;opacity:0;pointer-events:none;">
                            <div class="mt-1 rounded-xl bg-white p-3">
                                <div class="mb-1 flex items-center justify-between">
                                    <span class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Seguridad</span>
                                    <span id="security_chart_badge" class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">0 registros</span>
                                </div>
                                <div id="security_chart_wrap" style="height:160px">
                                    <canvas id="security_annual_chart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-lg font-semibold text-slate-900">Activos con más condiciones</h3>
                            <span id="fallback_badge_elements" class="hidden rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-600">Último reporte disponible</span>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">Ranking de activos con más hallazgos que requieren atención.</p>
                    </div>
                    <div class="max-h-[420px] overflow-y-auto pr-2">
                        <div class="h-[320px]">
                            <canvas id="topElementsChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-lg font-semibold text-slate-900">Condiciones más frecuentes</h3>
                            <span id="fallback_badge_conditions" class="hidden rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-600">Último reporte disponible</span>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">Ordenadas de mayor a menor frecuencia, mostrando nombre y descripción cuando están disponibles.</p>
                    </div>
                    <div class="h-[320px]">
                        <canvas id="topConditionsChart"></canvas>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-lg font-semibold text-slate-900">Novedades por área</h3>
                            <span id="fallback_badge_area" class="hidden rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-600">Último reporte disponible</span>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">Todas las áreas ordenadas por proporción de hallazgos con atención frente a sus novedades.</p>
                    </div>
                    <div class="max-h-[420px] overflow-y-auto pr-2">
                        <div class="h-[320px]">
                            <canvas id="areaChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-lg font-semibold text-slate-900">Componentes con novedades</h3>
                                <span id="fallback_badge_components" class="hidden rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-600">Último reporte disponible</span>
                            </div>
                            <p id="component_chart_description" class="mt-1 text-sm text-slate-500">Componentes ordenados por proporción de hallazgos con atención sobre sus novedades. Pasa el mouse para ver detalle por condición.</p>
                        </div>
                        <div class="flex flex-shrink-0 gap-1.5">
                            <button
                                type="button"
                                id="component_mode_rate"
                                onclick="setComponentChartMode('rate')"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-[#d94d33] bg-[#d94d33] px-2.5 py-1.5 text-xs font-semibold text-white transition"
                            >
                                <i data-lucide="percent" class="h-3.5 w-3.5"></i>
                                Proporción
                            </button>
                            <button
                                type="button"
                                id="component_mode_count"
                                onclick="setComponentChartMode('count')"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50"
                            >
                                <i data-lucide="hash" class="h-3.5 w-3.5"></i>
                                Cantidad
                            </button>
                        </div>
                    </div>
                    <div class="max-h-[420px] overflow-y-auto pr-2">
                        <div class="h-[320px]">
                            <canvas id="componentChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- MODAL SEMÁFORO --}}
        <div
            id="semaphore_modal"
            class="fixed left-0 top-0 z-[99999] hidden h-[100dvh] w-[100vw] items-center justify-center bg-slate-950/65 p-4"
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

                <div id="semaphore_filter_popover" class="fixed z-[100000] hidden w-[320px] rounded-2xl border border-slate-200 bg-white shadow-2xl"></div>
                <div id="semaphore_cell_popover" class="fixed z-[100000] hidden w-[360px] rounded-2xl border border-slate-200 bg-white shadow-2xl"></div>

                <div class="min-h-0 flex-1 overflow-auto">
                    <div id="semaphore_table_container" class="min-w-full"></div>
                    <div id="semaphore_stats_container" class="hidden border-t border-slate-200 bg-slate-50 p-5"></div>
                </div>
            </div>
        </div>

        <div id="indicatorToastContainer" class="fixed bottom-5 right-5 z-[99999] space-y-3"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const indicatorDefaultWeekFrom = '{{ $defaultYearFrom }}-{{ str_pad($defaultWeekFrom, 2, "0", STR_PAD_LEFT) }}';
        const indicatorDefaultWeekTo   = '{{ $defaultYearTo }}-{{ str_pad($defaultWeekTo, 2, "0", STR_PAD_LEFT) }}';
        const indicatorDefaultYear     = {{ $defaultYear }};
        const indicatorAvailableYears  = @json($availableYears);

        const indicatorState = {
            conditionChart: null,
            weeklyChart: null,
            topElementsChart: null,
            topConditionsChart: null,
            areaChart: null,
            componentChart: null,
            semaphoreCharts: {},
            semaphoreData: null,
            semaphoreFilters: {},
            semaphoreFilterPopoverKey: null,
            mode: 'dashboard',
            weeklyChartMode: 'reports',
            weeklyChartData: null,
            componentsChartMode: 'rate',
            componentsData: null,
            beltChartData: [],
            securityChartData: [],
            belt_annual_chart: null,
            security_annual_chart: null,
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

            populateWeekSelects();

            document.getElementById('indicator_year')?.addEventListener('change', () => {
                populateWeekSelects();
            });

            applyButton?.addEventListener('click', () => {
                closeSemaphoreModal(false);
                indicatorState.mode = 'dashboard';
                loadIndicators();
            });

            document.getElementById('indicator_reset_filters')?.addEventListener('click', () => {
                resetIndicatorFilters();
            });
            document.getElementById('semaphore_year')?.addEventListener('change', () => {
                // Se consulta con el botón Consultar del modal.
            });

            document.getElementById('semaphore_week')?.addEventListener('change', () => {
                // Se consulta con el botón Consultar del modal.
            });

            filterGroupsByClient();
            filterElementTypes();
            loadIndicators(true, true);

            // Tooltip hover para "SÍ REQUIERE" de cambio de banda.
            const beltYesCard    = document.getElementById('belt_yes_card');
            const beltYesTooltip = document.getElementById('belt_yes_tooltip');
            if (beltYesCard && beltYesTooltip) {
                beltYesCard.addEventListener('mouseenter', () => {
                    const list = document.getElementById('belt_yes_tooltip_list');
                    if (!list || list.children.length === 0) return;

                    // Mostrar primero para poder medir dimensiones reales.
                    beltYesTooltip.classList.remove('hidden');
                    const cardRect = beltYesCard.getBoundingClientRect();
                    const tipRect  = beltYesTooltip.getBoundingClientRect();

                    // Posicionar encima del card, centrado horizontalmente.
                    let top  = cardRect.top - tipRect.height - 8;
                    let left = cardRect.left + cardRect.width / 2 - tipRect.width / 2;

                    // Evitar salirse de la pantalla.
                    if (top < 8) top = cardRect.bottom + 8;
                    left = Math.max(8, Math.min(left, window.innerWidth - tipRect.width - 8));

                    beltYesTooltip.style.top  = `${top}px`;
                    beltYesTooltip.style.left = `${left}px`;
                });
                beltYesCard.addEventListener('mouseleave', () => {
                    beltYesTooltip.classList.add('hidden');
                });
            }

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
            selectOnlyVisibleElementTypeOption(elementTypeSelect);
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

        function selectOnlyVisibleElementTypeOption(select) {
            const visibleOptions = Array.from(select.options)
                .filter(option => option.value && !option.hidden);

            const selectedVisible = visibleOptions.some(option => option.selected);

            if (visibleOptions.length === 1 && !selectedVisible) {
                select.value = visibleOptions[0].value;
            }
        }

        function updateElementTypeHint() {
            const elementTypeSelect = document.getElementById('indicator_element_type_id');
            const elementTypeId = elementTypeSelect?.value || '';
            const selectedOption = elementTypeSelect?.selectedOptions?.[0] || null;
            const hasSemaphore = selectedOption?.dataset?.hasSemaphore === '1';
            const hint = document.getElementById('element_type_hint');

            if (!hint) return;

            if (!elementTypeId) {
                hint.classList.add('hidden');
                hint.textContent = '';
                return;
            }

            hint.textContent = hasSemaphore
                ? 'Este tipo de activo tiene semáforo semanal habilitado.'
                : 'Este tipo de activo no tiene semáforo semanal habilitado.';
            hint.classList.remove('hidden');
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

        function openSemaphoreTemplateConfig() {
            const params = new URLSearchParams({
                client_id: document.getElementById('indicator_client_id')?.value || '',
                group_id: document.getElementById('indicator_group_id')?.value || '',
                element_type_id: document.getElementById('indicator_element_type_id')?.value || '',
            });

            window.location.href = `{{ route('admin.semaphore-templates.index') }}?${params.toString()}`;
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
            closeSemaphoreFilterPopover();
            closeSemaphoreCellPopover();

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
            const statsContainer = document.getElementById('semaphore_stats_container');

            if (empty) {
                empty.textContent = message || 'No hay datos para el semáforo seleccionado.';
                empty.classList.remove('hidden');
            }

            if (container) {
                container.innerHTML = '';
            }

            if (statsContainer) {
                statsContainer.innerHTML = '';
                statsContainer.classList.add('hidden');
            }

            destroySemaphoreCharts();
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
            indicatorState.semaphoreData = data;
            indicatorState.semaphoreFilters = {};
            closeSemaphoreFilterPopover();
            closeSemaphoreCellPopover();
            renderSemaphoreTable();
            return;

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

            renderSemaphoreStats(areas);
        }
        function renderSemaphoreFilterHeader(key, label, alignClass = 'text-center') {
            const active = isSemaphoreFilterActive(key);
            const activeClass = active ? 'text-[#d94d33]' : 'text-slate-500';

            return `
                <th class="px-3 py-3 ${alignClass} text-[11px] font-bold uppercase tracking-wide">
                    <button
                        type="button"
                        onclick="openSemaphoreFilterPopover(event, '${escapeHtml(key)}')"
                        class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 ${activeClass} transition hover:bg-white hover:text-[#d94d33]"
                    >
                        <span>${escapeHtml(label)}</span>
                        <i data-lucide="${active ? 'filter' : 'list-filter'}" class="h-3.5 w-3.5"></i>
                    </button>
                </th>
            `;
        }
        function isSemaphoreFilterActive(key) {
            const values = indicatorState.semaphoreFilters?.[key];

            return Array.isArray(values) && values.length > 0;
        }

        function applySemaphoreFilters(areas) {
            const filters = indicatorState.semaphoreFilters || {};

            return (areas || [])
                .map(area => {
                    const filteredRows = (area.rows || []).filter(row => {
                        return Object.entries(filters).every(([key, selectedValues]) => {
                            if (!Array.isArray(selectedValues) || selectedValues.length === 0) {
                                return true;
                            }

                            return selectedValues.includes(semaphoreFilterValue(row, area, key));
                        });
                    });

                    return {
                        ...area,
                        rows: filteredRows,
                        elements_count: filteredRows.length,
                    };
                })
                .filter(area => (area.rows || []).length > 0);
        }
        function semaphoreFilterOptions(key) {
            const data = indicatorState.semaphoreData || {};
            const values = [];

            (data.areas || []).forEach(area => {
                (area.rows || []).forEach(row => {
                    values.push(semaphoreFilterValue(row, area, key));
                });
            });

            return [...new Set(values)]
                .filter(value => value !== null && value !== undefined && value !== '')
                .sort((a, b) => String(a).localeCompare(String(b)));
        }

        function openSemaphoreFilterPopover(event, key) {
            event?.stopPropagation();

            const popover = document.getElementById('semaphore_filter_popover');
            const labels = semaphoreFilterColumns();

            if (!popover) {
                return;
            }

            indicatorState.semaphoreFilterPopoverKey = key;

            const selected = indicatorState.semaphoreFilters[key] || [];
            const options = semaphoreFilterOptions(key);

            popover.innerHTML = `
                <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                    <div>
                        <div class="text-sm font-bold text-slate-900">${escapeHtml(labels[key] || 'Filtro')}</div>
                        <div class="mt-0.5 text-xs text-slate-500">${selected.length || 'Sin'} filtros aplicados</div>
                    </div>
                    <button type="button" onclick="closeSemaphoreFilterPopover()" class="rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
                <div class="space-y-3 p-4">
                    <input
                        id="semaphore_filter_search"
                        type="search"
                        placeholder="Buscar valor..."
                        oninput="filterSemaphorePopoverOptions()"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                    >
                    <div id="semaphore_filter_options" class="max-h-64 space-y-1 overflow-auto pr-1">
                        ${options.map(option => `
                            <label class="semaphore-filter-option flex cursor-pointer items-start gap-2 rounded-xl px-2 py-2 text-sm text-slate-700 transition hover:bg-slate-50" data-filter-label="${escapeHtml(String(option).toLowerCase())}">
                                <input
                                    type="checkbox"
                                    class="semaphore-filter-check mt-0.5 rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                                    value="${escapeHtml(option)}"
                                    ${selected.includes(option) ? 'checked' : ''}
                                >
                                <span class="leading-snug">${escapeHtml(option)}</span>
                            </label>
                        `).join('') || '<div class="rounded-xl bg-slate-50 px-3 py-4 text-center text-sm text-slate-500">Sin opciones disponibles.</div>'}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 border-t border-slate-200 px-4 py-3">
                    <button type="button" onclick="clearSemaphoreFilter('${escapeHtml(key)}')" class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-500 transition hover:bg-slate-100">Limpiar</button>
                    <button type="button" onclick="applySemaphoreFilter()" class="rounded-xl bg-[#d94d33] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#b83f29]">Aplicar</button>
                </div>
            `;

            const rect = event.currentTarget.getBoundingClientRect();
            const top = Math.min(rect.bottom + 8, window.innerHeight - 420);
            const left = Math.min(Math.max(12, rect.left), window.innerWidth - 340);

            popover.style.top = `${Math.max(12, top)}px`;
            popover.style.left = `${left}px`;
            popover.classList.remove('hidden');

            if (window.lucide) {
                window.lucide.createIcons();
            }
        }

        function filterSemaphorePopoverOptions() {
            const term = String(document.getElementById('semaphore_filter_search')?.value || '').trim().toLowerCase();

            document.querySelectorAll('#semaphore_filter_options .semaphore-filter-option').forEach(option => {
                option.classList.toggle('hidden', !option.dataset.filterLabel.includes(term));
            });
        }

        function applySemaphoreFilter() {
            const key = indicatorState.semaphoreFilterPopoverKey;

            if (!key) {
                return;
            }

            const selected = Array.from(document.querySelectorAll('#semaphore_filter_options .semaphore-filter-check:checked'))
                .map(input => input.value);

            if (selected.length) {
                indicatorState.semaphoreFilters[key] = selected;
            } else {
                delete indicatorState.semaphoreFilters[key];
            }

            closeSemaphoreFilterPopover();
            renderSemaphoreTable();
        }

        function clearSemaphoreFilter(key) {
            delete indicatorState.semaphoreFilters[key];
            closeSemaphoreFilterPopover();
            renderSemaphoreTable();
        }

        function closeSemaphoreFilterPopover() {
            const popover = document.getElementById('semaphore_filter_popover');

            if (popover) {
                popover.classList.add('hidden');
                popover.innerHTML = '';
            }

            indicatorState.semaphoreFilterPopoverKey = null;
        }

        function openSemaphoreCellPopover(event, button) {
            event?.stopPropagation();

            const popover = document.getElementById('semaphore_cell_popover');
            const raw = button?.dataset?.cellPopover || '';

            if (!popover || !raw) {
                return;
            }

            let payload = null;

            try {
                payload = JSON.parse(raw);
            } catch (error) {
                return;
            }

            const title = payload?.title || 'Detalle';
            const cell = payload?.cell || {};
            const breakdown = Array.isArray(cell.breakdown) ? cell.breakdown : [];
            popover.innerHTML = `
                <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-4 py-3">
                    <div>
                        <div class="text-sm font-bold text-slate-900">${escapeHtml(title)}</div>
                        <div class="mt-1 text-xs text-slate-500">${escapeHtml(cell.detail || 'Detalle por componente')}</div>
                    </div>
                    <button type="button" onclick="closeSemaphoreCellPopover()" class="rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
                <div class="max-h-[360px] space-y-3 overflow-auto p-4">
                    ${breakdown.map(item => `
                        <div class="rounded-2xl border border-slate-200 p-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="text-sm font-semibold text-slate-800">${escapeHtml(item.component || 'Componente')}</div>
                                ${item.evaluated
                                    ? '<span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-700"><i data-lucide="check" class="h-3.5 w-3.5"></i></span>'
                                    : '<span class="rounded-full px-2 py-0.5 text-[10px] font-bold bg-red-50 text-red-600">No evaluado</span>'
                                }
                            </div>
                            <div class="mt-2 text-xs font-semibold text-slate-700">${escapeHtml(item.condition_name || 'Sin condición')}</div>
                            <div class="mt-1 text-xs leading-5 text-slate-500">${escapeHtml(item.condition_description || item.detail || 'Sin detalle.')}</div>
                        </div>
                    `).join('') || '<div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">Sin detalle disponible.</div>'}
                </div>
            `;

            const rect = button.getBoundingClientRect();
            const top = Math.min(rect.bottom + 8, window.innerHeight - 420);
            const left = Math.min(Math.max(12, rect.left - 120), window.innerWidth - 380);

            popover.style.top = `${Math.max(12, top)}px`;
            popover.style.left = `${left}px`;
            popover.classList.remove('hidden');

            if (window.lucide) {
                window.lucide.createIcons();
            }
        }

        function closeSemaphoreCellPopover() {
            const popover = document.getElementById('semaphore_cell_popover');

            if (popover) {
                popover.classList.add('hidden');
                popover.innerHTML = '';
            }
        }
        function renderSemaphoreStatsChart(canvasId, rows) {
            const canvas = document.getElementById(canvasId);

            if (!canvas || typeof Chart === 'undefined') {
                return;
            }

            indicatorState.semaphoreCharts[canvasId] = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: rows.map(row => row.label),
                    datasets: [
                        {
                            label: 'Activos',
                            data: rows.map(row => row.total),
                            backgroundColor: rows.map(row => row.color || semaphoreChartColor(row.label, row.level, row.color)),
                            borderRadius: 8,
                        },
                    ],
                },
                options: {
                    indexAxis: 'y',
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: { precision: 0 },
                        },
                    },
                    plugins: {
                        legend: { display: false },
                    },
                },
            });
        }

        function destroySemaphoreCharts() {
            Object.values(indicatorState.semaphoreCharts || {}).forEach(chart => {
                if (chart) {
                    chart.destroy();
                }
            });

            indicatorState.semaphoreCharts = {};
        }

        function semaphoreChartColor(label, level = null, fallback = null) {
            if (level === 'high') {
                return '#fca5a5';
            }

            if (level === 'medium') {
                return '#fde68a';
            }

            if (level === 'low') {
                return '#bfdbfe';
            }

            if (level === 'ok') {
                return '#bbf7d0';
            }

            if (level === 'warning') {
                return '#fdba74';
            }

            if (level === 'neutral') {
                return '#e2e8f0';
            }

            const normalized = String(label || '').toUpperCase();

            if (normalized === 'SI') {
                return '#fdba74';
            }

            if (normalized === 'NO') {
                return '#e2e8f0';
            }

            if (normalized.includes('ALTA')) {
                return '#f87171';
            }

            if (normalized.includes('MEDIA')) {
                return '#fbbf24';
            }

            if (normalized.includes('BAJA')) {
                return '#60a5fa';
            }

            if (normalized.includes('OK') || normalized.includes('NORMAL')) {
                return '#34d399';
            }

            const palette = [
                '#8b5cf6',
                '#14b8a6',
                '#f97316',
                '#ec4899',
                '#22c55e',
                '#0ea5e9',
            ];

            let hash = 0;
            for (let index = 0; index < normalized.length; index++) {
                hash = normalized.charCodeAt(index) + ((hash << 5) - hash);
            }

            return fallback || palette[Math.abs(hash) % palette.length];
        }
        function renderSemaphoreDetailCell(cell, title) {
            const value = cell || {};

            if (!Array.isArray(value.breakdown) || value.breakdown.length === 0) {
                return renderSemaphoreBadge(value);
            }

            const payload = escapeHtml(JSON.stringify({
                title,
                cell: value,
            }));
            const customStyle = semaphoreBadgeInlineStyle(value);

            return `
                <button
                    type="button"
                    data-cell-popover="${payload}"
                    onclick="openSemaphoreCellPopover(event, this)"
                    class="inline-flex max-w-[160px] items-center justify-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold leading-none transition hover:opacity-85 ${customStyle ? '' : semaphoreBadgeButtonClasses(value.level)}"
                    style="${customStyle}"
                    title="${escapeHtml(value.detail || title)}"
                >
                    <span class="truncate">${escapeHtml(value.label || 'N/A')}</span>
                    <i data-lucide="info" class="h-3.5 w-3.5"></i>
                </button>
            `;
        }
        async function updateSemaphoreBeltChange(button) {
            const module = document.querySelector('[data-indicators-module]');
            const canEdit = module?.dataset.canEditSemaphore === '1';
            const route = module?.dataset.semaphoreBeltChangeUpdateRoute;

            if (!canEdit) {
                showIndicatorToast('No tienes permisos para modificar cambio de banda.', 'error');
                return;
            }

            if (!route) {
                showIndicatorToast('No se encontró la ruta para actualizar cambio de banda.', 'error');
                return;
            }

            const payload = {
                client_id: document.getElementById('indicator_client_id')?.value || '',
                group_id: document.getElementById('indicator_group_id')?.value || '',
                element_type_id: document.getElementById('indicator_element_type_id')?.value || '',
                element_id: button?.dataset.elementId || '',
                year: document.getElementById('semaphore_year')?.value || '',
                week: document.getElementById('semaphore_week')?.value || '',
                is_belt_change: button?.dataset.currentValue !== '1',
            };

            const originalHtml = button.innerHTML;
            const originalClass = button.className;

            button.disabled = true;
            button.classList.add('opacity-60', 'cursor-wait');

            try {
                const response = await fetch(route, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': module?.dataset.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify(payload),
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    showIndicatorToast(data.message || 'No fue posible actualizar cambio de banda.', 'error');
                    return;
                }

                showIndicatorToast(data.message || 'Cambio de banda actualizado.', 'success');
                await loadSemaphore();
            } catch (error) {
                showIndicatorToast('Ocurrió un error de red al actualizar cambio de banda.', 'error');
            } finally {
                button.disabled = false;
                button.className = originalClass;
                button.innerHTML = originalHtml;
            }
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
            const customStyle = semaphoreBadgeInlineStyle(value);

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
                    class="inline-flex max-w-[130px] items-center justify-center truncate rounded-full px-2.5 py-1 text-[11px] font-bold leading-none ${customStyle ? '' : (classes[value.level] || classes.neutral)}"
                    style="${customStyle}"
                >
                    ${escapeHtml(label)}
                </span>
            `;
        }

        function semaphoreBadgeButtonClasses(level) {
            const classes = {
                ok: 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200',
                high: 'bg-red-100 text-red-700 hover:bg-red-200',
                medium: 'bg-amber-100 text-amber-700 hover:bg-amber-200',
                low: 'bg-blue-100 text-blue-700 hover:bg-blue-200',
                warning: 'bg-orange-100 text-orange-700 hover:bg-orange-200',
                neutral: 'bg-slate-100 text-slate-500 hover:bg-slate-200',
            };

            return classes[level] || classes.neutral;
        }

        function normalizeHexColor(value) {
            const color = String(value || '').trim();

            if (/^#[0-9A-Fa-f]{6}$/.test(color)) {
                return color;
            }

            return null;
        }

        function hexToRgba(hex, alpha = 1) {
            const normalized = normalizeHexColor(hex);

            if (!normalized) {
                return hex;
            }

            const red = parseInt(normalized.slice(1, 3), 16);
            const green = parseInt(normalized.slice(3, 5), 16);
            const blue = parseInt(normalized.slice(5, 7), 16);

            return `rgba(${red}, ${green}, ${blue}, ${alpha})`;
        }

        function hexToRgb(hex) {
            const normalized = normalizeHexColor(hex);

            if (!normalized) {
                return null;
            }

            return {
                red: parseInt(normalized.slice(1, 3), 16),
                green: parseInt(normalized.slice(3, 5), 16),
                blue: parseInt(normalized.slice(5, 7), 16),
            };
        }

        function relativeLuminanceChannel(channel) {
            const normalized = channel / 255;

            return normalized <= 0.03928
                ? normalized / 12.92
                : Math.pow((normalized + 0.055) / 1.055, 2.4);
        }

        function relativeLuminance(rgb) {
            if (!rgb) {
                return 1;
            }

            return (0.2126 * relativeLuminanceChannel(rgb.red))
                + (0.7152 * relativeLuminanceChannel(rgb.green))
                + (0.0722 * relativeLuminanceChannel(rgb.blue));
        }

        function semaphoreBadgeInlineStyle(cell) {
            const color = normalizeHexColor(cell?.color);
            const severity = Number(cell?.severity);

            if (!color || severity !== 0) {
                return '';
            }

            const luminance = relativeLuminance(hexToRgb(color));
            const backgroundColor = luminance < 0.28
                ? hexToRgba(color, 0.88)
                : hexToRgba(color, 0.18);
            const borderColor = luminance < 0.28
                ? hexToRgba(color, 0.96)
                : hexToRgba(color, 0.42);
            const textColor = luminance < 0.28 ? '#ffffff' : '#0f172a';

            return [
                `background-color: ${backgroundColor}`,
                `border: 1px solid ${borderColor}`,
                `color: ${textColor}`,
            ].join('; ');
        }

        async function loadIndicators(useLatest = false, strictSummary = false) {
            const module = document.querySelector('[data-indicators-module]');
            const route = module?.dataset.route;

            if (!route) {
                showIndicatorToast('No se encontró la ruta de indicadores.', 'error');
                return;
            }

            const year    = String(selectedYear());
            const weekFrom = (document.getElementById('indicator_week_from')?.value || '').split('-');
            const weekTo   = (document.getElementById('indicator_week_to')?.value || '').split('-');

            // weekFrom/weekTo values are "YEAR-WW"; year part should match selectedYear
            const params = new URLSearchParams({
                client_id:       document.getElementById('indicator_client_id')?.value || '',
                group_id:        document.getElementById('indicator_group_id')?.value || '',
                element_type_id: document.getElementById('indicator_element_type_id')?.value || '',
                year_from:       weekFrom[0] || year,
                week_from:       weekFrom[1] || '',
                year_to:         weekTo[0]   || year,
                week_to:         weekTo[1]   || '',
            });

            if (useLatest) {
                params.set('mode', 'latest');
            }

            if (strictSummary) {
                params.set('strict_summary', '1');
            }

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
            const chartMode = charts.mode || 'severity';

            setText('metric_total_elements', summary.total_elements ?? 0);
            setText('metric_inspected_elements', summary.inspected_elements ?? 0);
            setText('metric_not_inspected_elements', `${summary.not_inspected_elements ?? 0} sin inspección`);
            setText('metric_coverage', `${summary.coverage ?? 0}%`);
            setText('metric_high_findings', summary.high_findings ?? 0);
            setText('metric_medium_findings', summary.medium_findings ?? 0);
            setText('metric_low_findings', summary.low_findings ?? 0);
            setText('metric_ok_findings', summary.ok_findings ?? 0);

            const hasData = Number(summary.total_elements || 0) > 0 || Number(summary.inspected_elements || 0) > 0;

            document.getElementById('indicator_empty')?.classList.toggle('hidden', hasData);
            document.getElementById('indicator_content')?.classList.toggle('hidden', !hasData);

            if (!hasData) {
                destroyIndicatorCharts();
                return;
            }

            const isFallback = Boolean(charts.ranking_fallback);

            if (chartMode === 'condition') {
                setText('condition_chart_title', 'Distribución por condición');
                setText('condition_chart_description', 'Condiciones específicas del tipo de activo seleccionado.');
                renderConditionChart(charts.condition_distribution || []);
            } else {
                setText('condition_chart_title', 'Distribución por criticidad');
                setText('condition_chart_description', 'Resumen ejecutivo por criticidad. Evita mezclar condiciones propias de distintos tipos de activo.');
                renderConditionChart(charts.severity_distribution || []);
            }

            const weeklyData = isFallback && (charts.combined_weekly_data_ytd || []).length > 0
                ? charts.combined_weekly_data_ytd
                : charts.combined_weekly_data || [];
            indicatorState.weeklyChartData = weeklyData;
            renderCombinedWeeklyChart(indicatorState.weeklyChartData, indicatorState.weeklyChartMode);

            renderHorizontalChart('topElementsChart', 'topElementsChart', charts.top_elements || [], 'name', ['attention'], ['Atención']);
            renderHorizontalChart('topConditionsChart', 'topConditionsChart', charts.top_conditions || [], 'label', ['total'], ['Total'], true);
            renderHorizontalChart('areaChart', 'areaChart', charts.area_distribution || [], 'label', ['attention_rate'], ['Atención %']);
            indicatorState.componentsData = charts.top_components || [];
            renderComponentChart(indicatorState.componentsData, indicatorState.componentsChartMode);

            ['fallback_badge_condition_chart', 'fallback_badge_weekly_chart',
             'fallback_badge_elements', 'fallback_badge_conditions',
             'fallback_badge_area', 'fallback_badge_components'].forEach(id => {
                document.getElementById(id)?.classList.toggle('hidden', !isFallback);
            });

            renderAnnualIndicators(data.annual || {});

            if (window.lucide) {
                window.lucide.createIcons();
            }
        }

        function selectedElementTypeHasSemaphore() {
            const select = document.getElementById('indicator_element_type_id');
            const option = select?.selectedOptions?.[0] || null;

            return Boolean(select?.value) && option?.dataset?.hasSemaphore === '1';
        }

        function isoWeekAndYear(date) {
            const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
            const dayNum = d.getUTCDay() || 7;
            d.setUTCDate(d.getUTCDate() + 4 - dayNum);
            const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
            return {
                week: Math.ceil((((d - yearStart) / 86400000) + 1) / 7),
                year: d.getUTCFullYear(),
            };
        }

        function isoWeeksInYear(year) {
            // ISO week of Dec 28 always equals the total weeks in that year
            const dec28 = new Date(Date.UTC(year, 11, 28));
            const dayNum = dec28.getUTCDay() || 7;
            dec28.setUTCDate(dec28.getUTCDate() + 4 - dayNum);
            const yearStart = new Date(Date.UTC(dec28.getUTCFullYear(), 0, 1));
            return Math.ceil((((dec28 - yearStart) / 86400000) + 1) / 7);
        }

        function generateWeekOptionsForYear(year) {
            const current = isoWeekAndYear(new Date());
            const lastWeek = year === current.year ? current.week : isoWeeksInYear(year);
            const options = [];

            for (let week = lastWeek; week >= 1; week--) {
                const pad = String(week).padStart(2, '0');
                options.push({ value: `${year}-${pad}`, label: `S${pad}` });
            }

            return options;
        }

        function selectedYear() {
            const sel = document.getElementById('indicator_year');
            return sel ? parseInt(sel.value, 10) : indicatorDefaultYear;
        }

        function populateWeekSelects() {
            const fromSelect = document.getElementById('indicator_week_from');
            const toSelect   = document.getElementById('indicator_week_to');

            if (!fromSelect || !toSelect) {
                return;
            }

            const year    = selectedYear();
            const options = generateWeekOptionsForYear(year);
            const current = isoWeekAndYear(new Date());

            fromSelect.innerHTML = '';
            toSelect.innerHTML   = '';

            options.forEach(opt => {
                fromSelect.appendChild(Object.assign(document.createElement('option'), { value: opt.value, textContent: opt.label }));
                toSelect.appendChild(Object.assign(document.createElement('option'), { value: opt.value, textContent: opt.label }));
            });

            const lastOption  = options[options.length - 1];
            const firstOption = options[0];

            const defaultFrom = year === parseInt(indicatorDefaultWeekFrom.split('-')[0], 10)
                ? indicatorDefaultWeekFrom
                : lastOption?.value;

            const defaultTo = year === current.year
                ? `${current.year}-${String(current.week).padStart(2, '0')}`
                : firstOption?.value;

            fromSelect.value = defaultFrom || lastOption?.value;
            toSelect.value   = defaultTo   || firstOption?.value;
        }

        function resetIndicatorFilters() {
            const yearSel = document.getElementById('indicator_year');
            if (yearSel) yearSel.value = indicatorDefaultYear;

            document.getElementById('indicator_client_id').value       = '{{ ($defaultScope["client_id"] ?? "") }}';
            document.getElementById('indicator_group_id').value        = '';
            document.getElementById('indicator_element_type_id').value = '';

            filterGroupsByClient();
            filterElementTypes();
            populateWeekSelects();
            loadIndicators(true);
        }

        function renderAnnualIndicators(annual) {
            const belt     = annual.belt_change || {};
            const security = annual.security    || {};

            setText('annual_year_label', annual.year ?? '');

            const subtitle = document.getElementById('annual_subtitle');
            if (subtitle) {
                subtitle.textContent = annual.force_latest
                    ? 'Estado más reciente por activo durante el año completo, independiente del rango de semanas seleccionado.'
                    : 'Estado más reciente por activo en el período seleccionado.';
            }
            setText('annual_belt_yes',            belt.yes  ?? 0);
            setText('annual_belt_no',             belt.no   ?? 0);
            setText('annual_belt_na',             belt.na   ?? 0);
            setText('annual_belt_total',          belt.total ?? 0);
            setText('annual_security_ok',         security.ok      ?? 0);
            setText('annual_security_novedad',    security.novedad ?? 0);
            setText('annual_security_na',         security.na      ?? 0);
            setText('annual_security_total',      security.total   ?? 0);

            const list = document.getElementById('belt_yes_tooltip_list');
            const card = document.getElementById('belt_yes_card');
            const names = belt.yes_names || [];
            if (list) {
                list.innerHTML = names.map(n => `<li>${n}</li>`).join('');
            }
            if (card) {
                card.style.cursor = names.length > 0 ? 'pointer' : 'default';
            }

            // Guardar datos para gráficos del ojo y resetear estado.
            indicatorState.beltChartData     = belt.chart     || [];
            indicatorState.securityChartData = security.chart || [];

            ['belt', 'security'].forEach(type => {
                // Destruir chart si existe.
                if (indicatorState[`${type}_annual_chart`]) {
                    indicatorState[`${type}_annual_chart`].destroy();
                    indicatorState[`${type}_annual_chart`] = null;
                }
                // Resetear ojo.
                const eye = document.getElementById(`${type}_chart_eye`);
                if (eye) { eye.classList.remove('text-[#d94d33]', 'bg-orange-50'); }
                // Mostrar contadores (sin animación al recargar).
                const counters = document.getElementById(`${type}_counters_wrap`);
                if (counters) {
                    counters.style.transition    = 'none';
                    counters.style.opacity       = '1';
                    counters.style.maxHeight     = '300px';
                    counters.style.pointerEvents = 'auto';
                    requestAnimationFrame(() => { counters.style.transition = ''; });
                }
                // Ocultar chart (sin animación).
                const chartWrap = document.getElementById(`${type}_chart_container`);
                if (chartWrap) {
                    chartWrap.style.transition    = 'none';
                    chartWrap.style.opacity       = '0';
                    chartWrap.style.maxHeight     = '0';
                    chartWrap.style.pointerEvents = 'none';
                    requestAnimationFrame(() => { chartWrap.style.transition = ''; });
                }
            });

            const total = belt.total ?? 0;
            const secTotal = security.total ?? 0;
            const beltRegistros = indicatorState.beltChartData.reduce((s, r) => s + r.count, 0);
            const secRegistros  = indicatorState.securityChartData.reduce((s, r) => s + r.count, 0);
            setText('belt_chart_badge',     `${beltRegistros} registros`);
            setText('security_chart_badge', `${secRegistros} registros`);
        }

        function shiftIsoWeek(year, week, offset) {
            const base = new Date(Date.UTC(year, 0, 4));
            const day = base.getUTCDay() || 7;
            base.setUTCDate(base.getUTCDate() - day + 1 + ((week - 1 + offset) * 7));

            const thursday = new Date(base);
            thursday.setUTCDate(base.getUTCDate() + 3);
            const isoYear = thursday.getUTCFullYear();
            const firstThursday = new Date(Date.UTC(isoYear, 0, 4));
            const firstDay = firstThursday.getUTCDay() || 7;
            firstThursday.setUTCDate(firstThursday.getUTCDate() - firstDay + 4);
            const currentThursday = new Date(base);
            currentThursday.setUTCDate(base.getUTCDate() + 3);
            const diffWeeks = Math.round((currentThursday - firstThursday) / 604800000);

            return {
                year: isoYear,
                week: diffWeeks + 1,
            };
        }

        function setText(id, value) {
            const element = document.getElementById(id);

            if (element) {
                element.textContent = value;
            }
        }

        function distributeConditionRows(rows) {
            const groups = new Map();

            (rows || []).forEach(row => {
                const key = normalizeChartColorKey(row.color || semaphoreChartColor(row.label));

                if (!groups.has(key)) {
                    groups.set(key, []);
                }

                groups.get(key).push(row);
            });

            const orderedGroups = Array.from(groups.values())
                .map(group => group.slice().sort((a, b) => (b.total || 0) - (a.total || 0)))
                .sort((a, b) => (b[0]?.total || 0) - (a[0]?.total || 0));

            const distributed = [];
            let added = true;

            while (added) {
                added = false;

                orderedGroups.forEach(group => {
                    if (!group.length) {
                        return;
                    }

                    distributed.push(group.shift());
                    added = true;
                });
            }

            return distributed;
        }

        function normalizeChartColorKey(color) {
            const normalized = String(color || '').trim().toLowerCase();

            if (['#ff0000', '#ff0d0d', '#ff1a1a', '#f87171', '#ef4444', '#dc2626'].includes(normalized)) {
                return 'red';
            }

            if (['#ffff00', '#ffeb3b', '#fde68a', '#fbbf24', '#eab308'].includes(normalized)) {
                return 'yellow';
            }

            if (['#00ff00', '#22c55e', '#34d399', '#16a34a'].includes(normalized)) {
                return 'green';
            }

            if (['#00b0f0', '#60a5fa', '#3b82f6', '#0ea5e9'].includes(normalized)) {
                return 'blue';
            }

            if (['#ffffff', '#f8fafc', '#f1f5f9', '#e2e8f0'].includes(normalized)) {
                return 'neutral';
            }

            if (['#7030a0', '#8b5cf6', '#7c3aed'].includes(normalized)) {
                return 'purple';
            }

            if (['#ff9900', '#f97316', '#fdba74'].includes(normalized)) {
                return 'orange';
            }

            return normalized;
        }

        function setChartNotice(noticeId, show) {
            const el = document.getElementById(noticeId);
            if (!el) return;
            el.style.display = show ? 'flex' : 'none';
        }

        function renderConditionChart(rows) {
            const canvas = document.getElementById('conditionChart');

            if (!canvas) {
                return;
            }

            if (indicatorState.conditionChart) {
                indicatorState.conditionChart.destroy();
            }

            const chartRows = distributeConditionRows(rows);
            const isEmpty = chartRows.length === 0 || chartRows.every(r => !r.total);
            setChartNotice('condition_chart_notice', isEmpty);
            canvas.style.display = isEmpty ? 'none' : '';

            indicatorState.conditionChart = new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: chartRows.map(row => row.label),
                    datasets: [{
                        data: chartRows.map(row => row.total),
                        backgroundColor: chartRows.map(row => row.color || semaphoreChartColor(row.label)),
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

        function renderCombinedWeeklyChart(rows, mode) {
            const canvas = document.getElementById('weeklyChart');

            if (!canvas) {
                return;
            }

            if (indicatorState.weeklyChart) {
                indicatorState.weeklyChart.destroy();
            }

            const weeklyEmpty = !rows || rows.length === 0 || rows.every(r => !r.inspected);
            setChartNotice('weekly_chart_notice', weeklyEmpty);
            canvas.style.display = weeklyEmpty ? 'none' : '';

            // Recortar semanas vacías del inicio (ambos modos usan inspected).
            const firstIdx = rows.findIndex(r => r.inspected > 0);
            const trimmed  = firstIdx > 0 ? rows.slice(firstIdx) : rows;

            const isCoverage = mode === 'coverage';

            indicatorState.weeklyChart = new Chart(canvas, isCoverage ? {
                type: 'bar',
                data: {
                    labels: trimmed.map(row => row.label),
                    datasets: [
                        {
                            label: 'Inspeccionados',
                            data: trimmed.map(row => row.inspected),
                            backgroundColor: '#93c5fd',
                            borderColor: '#3b82f6',
                            borderWidth: 1,
                            borderRadius: 6,
                        },
                    ],
                },
                options: {
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 } },
                    },
                    plugins: {
                        legend: { display: false },
                    },
                },
            } : {
                type: 'line',
                data: {
                    labels: trimmed.map(row => row.label),
                    datasets: [{
                        label: 'Inspeccionados',
                        data: trimmed.map(row => row.inspected),
                        borderColor: '#d94d33',
                        backgroundColor: 'rgba(217,77,51,0.14)',
                        fill: true,
                        tension: 0.28,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#d94d33',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 } },
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: { displayColors: false },
                    },
                },
            });
        }

        function toggleWeeklyChartMode() {
            indicatorState.weeklyChartMode = indicatorState.weeklyChartMode === 'reports' ? 'coverage' : 'reports';
            const isReports = indicatorState.weeklyChartMode === 'reports';

            setText('weekly_chart_title', isReports ? 'Activos revisados por semana' : 'Activos revisados por semana');
            setText('weekly_chart_description', isReports
                ? 'Evolución semanal de activos inspeccionados durante el año completo.'
                : 'Activos inspeccionados por semana del rango seleccionado.');

            if (indicatorState.weeklyChartData) {
                renderCombinedWeeklyChart(indicatorState.weeklyChartData, indicatorState.weeklyChartMode);
            }
        }

        function renderComponentChart(rows, mode) {
            const isCount = mode === 'count';
            const valueKey = isCount ? 'attention' : 'attention_rate';
            const label = isCount ? 'Cantidad' : 'Atención %';

            const sorted = [...rows].sort((a, b) => (b[valueKey] ?? 0) - (a[valueKey] ?? 0));
            renderHorizontalChart('componentChart', 'componentChart', sorted, 'name', [valueKey], [label]);

            const rateBtn  = document.getElementById('component_mode_rate');
            const countBtn = document.getElementById('component_mode_count');

            if (rateBtn && countBtn) {
                const active   = 'border-[#d94d33] bg-[#d94d33] text-white';
                const inactive = 'border-slate-300 bg-white text-slate-600';

                rateBtn.className  = rateBtn.className.replace(isCount ? active : inactive, isCount ? inactive : active);
                countBtn.className = countBtn.className.replace(isCount ? inactive : active, isCount ? active : inactive);
            }
        }

        function setComponentChartMode(mode) {
            indicatorState.componentsChartMode = mode;
            renderComponentChart(indicatorState.componentsData || [], mode);
        }

        function toggleAnnualChart(type) {
            const counters  = document.getElementById(`${type}_counters_wrap`);
            const chartWrap = document.getElementById(`${type}_chart_container`);
            const eye       = document.getElementById(`${type}_chart_eye`);
            if (!counters || !chartWrap) return;

            const opening = parseFloat(chartWrap.style.opacity || '0') < 0.5;

            if (opening) {
                // Primero renderizar (canvas necesita estar visible para calcular dimensiones).
                const data = type === 'belt'
                    ? indicatorState.beltChartData
                    : indicatorState.securityChartData;
                const rowH    = Math.max(100, data.length * 38);
                const totalH  = rowH + 56; // chart + header badge
                const wrapEl  = document.getElementById(`${type}_chart_wrap`);
                if (wrapEl) wrapEl.style.height = `${rowH}px`;
                renderAnnualDistributionChart(type, data);

                // Ocultar contadores.
                counters.style.maxHeight = counters.scrollHeight + 'px'; // fijar antes de animar
                requestAnimationFrame(() => {
                    counters.style.opacity    = '0';
                    counters.style.maxHeight  = '0';
                    counters.style.pointerEvents = 'none';
                });

                // Mostrar gráfico.
                chartWrap.style.maxHeight    = `${totalH + 24}px`;
                chartWrap.style.opacity      = '1';
                chartWrap.style.pointerEvents = 'auto';
            } else {
                // Ocultar gráfico.
                chartWrap.style.opacity      = '0';
                chartWrap.style.maxHeight    = '0';
                chartWrap.style.pointerEvents = 'none';

                // Mostrar contadores.
                counters.style.maxHeight     = '300px';
                counters.style.opacity       = '1';
                counters.style.pointerEvents = 'auto';
            }

            if (eye) {
                eye.classList.toggle('text-[#d94d33]', opening);
                eye.classList.toggle('bg-orange-50',   opening);
            }
        }

        function renderAnnualDistributionChart(type, rows) {
            const canvasId = `${type}_annual_chart`;
            const wrapId   = `${type}_chart_wrap`;
            const canvas   = document.getElementById(canvasId);
            if (!canvas) return;

            if (indicatorState[`${type}_annual_chart`]) {
                indicatorState[`${type}_annual_chart`].destroy();
            }

            const wrap = document.getElementById(wrapId);
            if (wrap) wrap.style.height = `${Math.max(100, rows.length * 38)}px`;

            indicatorState[`${type}_annual_chart`] = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: rows.map(r => r.label),
                    datasets: [{
                        data: rows.map(r => r.count),
                        backgroundColor: rows.map(r => r.color || '#94a3b8'),
                        borderRadius: 4,
                    }],
                },
                options: {
                    indexAxis: 'y',
                    maintainAspectRatio: false,
                    scales: {
                        x: { beginAtZero: true, ticks: { precision: 0 } },
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => `Cantidad: ${ctx.raw}`,
                            },
                        },
                    },
                },
            });
        }

        function destroyIndicatorCharts() {
            [
                'conditionChart',
                'weeklyChart',
                'topElementsChart',
                'topConditionsChart',
                'areaChart',
                'componentChart',
                'belt_annual_chart',
                'security_annual_chart',
            ].forEach(key => {
                if (indicatorState[key]) {
                    indicatorState[key].destroy();
                    indicatorState[key] = null;
                }
            });

            ['condition_chart_notice', 'weekly_chart_notice'].forEach(id => setChartNotice(id, false));
        }

        function renderHorizontalChart(canvasId, stateKey, rows, labelKey, valueKeys, labels, useRowColors = false) {
            const canvas = document.getElementById(canvasId);

            if (!canvas) {
                return;
            }

            if (indicatorState[stateKey]) {
                indicatorState[stateKey].destroy();
            }

            const isVertical = canvasId === 'areaChart';

            const visibleRows = ['topElementsChart', 'componentChart'].includes(canvasId)
                ? rows
                : isVertical ? rows : rows.slice(0, 10);

            if (canvas.parentElement) {
                canvas.parentElement.style.height = ['topElementsChart', 'componentChart'].includes(canvasId)
                    ? `${Math.max(320, visibleRows.length * 42)}px`
                    : '320px';
            }

            const isRateLabel = labels[0] === 'Atención %';

            indicatorState[stateKey] = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: visibleRows.map(row => row[labelKey] || 'Sin dato'),
                    datasets: valueKeys.map((key, index) => ({
                        label: labels[index] || key,
                        data: visibleRows.map(row => row[key] || 0),
                        backgroundColor: useRowColors
                            ? visibleRows.map(row => row.color || semaphoreChartColor(row[labelKey]))
                            : undefined,
                        borderRadius: isVertical ? 4 : undefined,
                    })),
                },
                options: {
                    indexAxis: isVertical ? 'x' : 'y',
                    maintainAspectRatio: false,
                    scales: isVertical ? {
                        x: {
                            ticks: {
                                maxRotation: 35,
                                minRotation: 20,
                                font: { size: 11 },
                            },
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                callback: value => isRateLabel ? `${value}%` : value,
                            },
                        },
                    } : {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                callback: value => isRateLabel ? `${value}%` : value,
                            },
                        },
                    },
                    plugins: {
                        legend: {
                            display: valueKeys.length > 1,
                            position: 'bottom',
                        },
                        tooltip: {
                            callbacks: {
                                afterBody: context => {
                                    const row = visibleRows[context[0]?.dataIndex ?? -1];

                                    if (!row) {
                                        return [];
                                    }

                                    const extras = [];

                                    if (typeof row.description === 'string' && row.description.trim() !== '') {
                                        extras.push(`Descripción: ${row.description}`);
                                    }

                                    if (isRateLabel) {
                                        if (typeof row.total !== 'undefined') {
                                            extras.push(`Novedades: ${row.total}`);
                                        }

                                        if (typeof row.attention !== 'undefined') {
                                            extras.push(`Atención: ${row.attention}`);
                                        }
                                    }

                                    if (canvasId === 'areaChart' && typeof row.elements !== 'undefined') {
                                        extras.push(`Activos: ${row.elements}`);
                                    }

                                    if (canvasId === 'componentChart' && Array.isArray(row.conditions) && row.conditions.length > 0) {
                                        extras.push('');
                                        extras.push('Por condición:');
                                        row.conditions.forEach(c => extras.push(`  ${c.name}: ${c.count}`));
                                    }

                                    return extras;
                                },
                            },
                        },
                    },
                },
            });
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

        function semaphoreColumns() {
            const columns = indicatorState.semaphoreData?.columns;

            if (Array.isArray(columns) && columns.length > 0) {
                return columns;
            }

            return [
                { key: 'change_belt', label: 'Cambio banda', type: 'belt_change_manual', source_column_key: 'belt_status' },
                { key: 'belt_status', label: 'Estado banda', type: 'condition_aggregate', source_column_key: null },
                { key: 'safety_condition', label: 'Seguridad', type: 'condition_aggregate', source_column_key: null },
                { key: 'discharge', label: 'Descarga', type: 'condition_aggregate', source_column_key: null },
                { key: 'cleaner', label: 'Limpiador', type: 'condition_aggregate', source_column_key: null },
            ];
        }

        function semaphoreFilterColumns() {
            return semaphoreColumns().reduce((labels, column) => {
                labels[column.key] = column.label;
                return labels;
            }, {
                asset: 'Área / Activo',
            });
        }

        function renderSemaphoreTable() {
            const data = indicatorState.semaphoreData || {};
            const meta = data.meta || {};
            const areas = applySemaphoreFilters(data.areas || []);
            const columns = semaphoreColumns();
            const container = document.getElementById('semaphore_table_container');

            if (!container) {
                return;
            }

            closeSemaphoreCellPopover();

            setText(
                'semaphore_meta',
                `Semana ${meta.week || '—'} / ${meta.year || '—'} · ${areas.reduce((total, area) => total + (area.rows || []).length, 0)} de ${meta.elements_count || 0} activos visibles · ${meta.details_count || 0} registros preventivos · ${meta.template_name || 'Modelo legado'}`
            );

            if (!areas.length) {
                container.innerHTML = `
                    <div class="px-5 py-10 text-center text-sm font-semibold text-slate-500">
                        No hay activos que coincidan con los filtros aplicados.
                    </div>
                `;
                renderSemaphoreStats([]);
                return;
            }

            container.innerHTML = `
                <table class="w-full divide-y divide-slate-200 text-sm" style="min-width: ${Math.max(920, 260 + (columns.length * 150))}px;">
                    <thead class="sticky top-0 z-10 bg-slate-50 shadow-sm">
                        <tr>
                            ${renderSemaphoreFilterHeader('asset', 'Área / Activo', 'w-[220px] text-left')}
                            ${columns.map(column => renderSemaphoreFilterHeader(column.key, column.label, 'text-center')).join('')}
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

            renderSemaphoreStats(areas);
        }

        function semaphoreFilterValue(row, area, key) {
            if (key === 'asset') {
                const code = row.element_code || '';
                const name = row.element_name || '';
                const asset = code && name && code !== name ? `${code} - ${name}` : (code || name || 'Sin activo');

                return `${area?.name || 'Sin área'} / ${asset}`;
            }

            const cell = row.cells?.[key] || row[key] || {};

            return String(cell.label || 'N/A').trim() || 'N/A';
        }

        function renderSemaphoreStats(areas) {
            const container = document.getElementById('semaphore_stats_container');

            if (!container) {
                return;
            }

            destroySemaphoreCharts();

            const rows = (areas || []).flatMap(area => area.rows || []);
            const columns = semaphoreColumns();
            const stats = columns.map(column => ({
                key: column.key,
                title: column.label,
                rows: buildSemaphoreColumnStats(rows, column.key),
            }));

            if (!stats.length) {
                container.innerHTML = `
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-center text-sm font-semibold text-slate-500">
                        No hay valores estadísticos para graficar sin contar N/A.
                    </div>
                `;
                container.classList.remove('hidden');
                return;
            }

            container.innerHTML = `
                <div class="mb-4 flex flex-col gap-1">
                    <h4 class="text-base font-bold text-slate-900">Resumen estadístico del semáforo</h4>
                    <p class="text-sm text-slate-500">Frecuencia de valores por columna. Los N/A no se incluyen.</p>
                </div>
                <div class="grid gap-4 lg:grid-cols-2">
                    ${stats.map((column, index) => `
                        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <h5 class="text-sm font-bold text-slate-800">${escapeHtml(column.title)}</h5>
                                <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-bold text-indigo-700">
                                    ${column.rows.reduce((total, row) => total + row.total, 0)} registros
                                </span>
                            </div>
                            <div class="h-[220px]">
                                ${column.rows.length
                                    ? `<canvas id="semaphore_stats_chart_${index}"></canvas>`
                                    : `<div class="flex h-full items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 text-center text-sm font-semibold text-slate-500">Sin valores diferentes de N/A.</div>`
                                }
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
            container.classList.remove('hidden');

            stats.forEach((column, index) => {
                if (!column.rows.length) {
                    return;
                }

                renderSemaphoreStatsChart(`semaphore_stats_chart_${index}`, column.rows);
            });
        }

        function buildSemaphoreColumnStats(rows, key) {
            const map = new Map();

            rows.forEach(row => {
                const cell = row.cells?.[key] || row[key] || {};
                const label = String(cell.label || '').trim();
                const normalized = label.toUpperCase();

                if (!label || normalized === 'N/A') {
                    return;
                }

                const current = map.get(label) || {
                    label,
                    total: 0,
                    level: cell.level || null,
                    color: semaphoreChartColor(label, cell.level, cell.color),
                    order: Number.isFinite(Number(cell.order)) ? Number(cell.order) : 500,
                };

                current.total += 1;
                current.level = current.level || cell.level || null;
                current.color = current.color || semaphoreChartColor(label, cell.level, cell.color);
                current.order = Math.min(current.order, Number.isFinite(Number(cell.order)) ? Number(cell.order) : 500);

                map.set(label, current);
            });

            return Array.from(map.values())
                .sort((a, b) => a.order - b.order || b.total - a.total || a.label.localeCompare(b.label));
        }

        function renderSemaphoreArea(area) {
            const rows = area.rows || [];
            const colspan = semaphoreColumns().length + 1;

            return `
                <tr class="bg-slate-100">
                    <td colspan="${colspan}" class="px-4 py-3 text-sm font-bold uppercase tracking-wide text-slate-700">
                        ${escapeHtml(area.name || 'Sin área')} · ${area.elements_count || rows.length} activos
                    </td>
                </tr>
                ${rows.map(renderSemaphoreRow).join('')}
            `;
        }

        function renderSemaphoreRow(row) {
            const cells = row.cells || {};

            return `
                <tr class="hover:bg-slate-50">
                    <td class="sticky left-0 z-[1] bg-white px-3 py-2.5 shadow-[1px_0_0_#e2e8f0]">
                        <div class="text-sm font-semibold text-slate-900">${escapeHtml(row.element_code || row.element_name || 'Sin activo')}</div>
                        ${row.element_name && row.element_name !== row.element_code ? `<div class="mt-0.5 max-w-[190px] truncate text-xs text-slate-500">${escapeHtml(row.element_name)}</div>` : ''}
                    </td>
                    ${semaphoreColumns().map(column => `
                        <td class="px-3 py-2.5 text-center">${renderSemaphoreColumnCell(row, column, cells[column.key] || row[column.key] || {})}</td>
                    `).join('')}
                </tr>
            `;
        }

        function renderSemaphoreColumnCell(row, column, cell) {
            if (column?.type === 'belt_change_manual') {
                return renderSemaphoreBeltChangeControl(row, column, cell);
            }

            if (Array.isArray(cell?.breakdown) && cell.breakdown.length > 0) {
                return renderSemaphoreDetailCell(cell, column?.label || 'Detalle');
            }

            return renderSemaphoreBadge(cell);
        }

        function renderSemaphoreBeltChangeControl(row, column, cell) {
            const module = document.querySelector('[data-indicators-module]');
            const canEdit = module?.dataset.canEditSemaphore === '1';

            if (!canEdit || typeof cell.value !== 'boolean') {
                return renderSemaphoreBadge(cell);
            }

            const isChange = Boolean(cell.value || cell.label === 'SI');
            const title = `${cell.detail || (isChange ? 'Tiene cambio de banda registrado.' : 'Sin cambio de banda.')} Clic para cambiar.`;
            const classes = semaphoreBadgeButtonClasses(cell.level);
            const customStyle = semaphoreBadgeInlineStyle(cell);

            return `
                <button
                    type="button"
                    title="${escapeHtml(title)}"
                    class="inline-flex max-w-[130px] items-center justify-center truncate rounded-full px-2.5 py-1 text-[11px] font-bold leading-none transition disabled:cursor-wait disabled:opacity-60 ${customStyle ? '' : classes}"
                    style="${customStyle}"
                    data-belt-change-button
                    data-element-id="${escapeHtml(row.element_id)}"
                    data-column-key="${escapeHtml(column?.key || '')}"
                    data-current-value="${isChange ? '1' : '0'}"
                    onclick="updateSemaphoreBeltChange(this)"
                >
                    ${isChange ? 'SI' : 'NO'}
                </button>
            `;
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeSemaphoreFilterPopover();
                closeSemaphoreModal();
            }
        });

        document.addEventListener('click', function (event) {
            const modal = document.getElementById('semaphore_modal');
            const popover = document.getElementById('semaphore_filter_popover');
            const cellPopover = document.getElementById('semaphore_cell_popover');

            if (modal?.classList.contains('flex') && event.target === modal) {
                closeSemaphoreModal();
            }

            if (
                popover &&
                !popover.classList.contains('hidden') &&
                !popover.contains(event.target) &&
                !event.target.closest('button[onclick^="openSemaphoreFilterPopover"]')
            ) {
                closeSemaphoreFilterPopover();
            }

            if (
                cellPopover &&
                !cellPopover.classList.contains('hidden') &&
                !cellPopover.contains(event.target) &&
                !event.target.closest('button[onclick^="openSemaphoreCellPopover"]')
            ) {
                closeSemaphoreCellPopover();
            }
        });
    </script>
@endsection
