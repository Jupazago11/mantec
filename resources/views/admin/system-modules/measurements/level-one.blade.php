@extends('layouts.measurements')

@section('title', 'Mediciones - Nivel 1')
@section('header_title', 'Mediciones')

@section('content')
    <div
        x-data="measurementLevelOneModule()"
        class="space-y-8"
    >
        @if($sections->isEmpty())
            <div class="rounded-2xl border border-slate-200 bg-white p-10 text-center shadow-sm">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                    <i data-lucide="folder-open" class="h-6 w-6"></i>
                </div>

                <p class="mt-4 text-base font-semibold text-slate-700">
                    No hay configuraciones activas para este módulo.
                </p>
                <p class="mt-2 text-sm text-slate-500">
                    Activa una combinación cliente + tipo de activo desde Config. módulos para visualizar la operación.
                </p>
            </div>
        @else
            <div class="space-y-8">
                @foreach($sections as $section)
                    @php
                        $areas = collect($section['areas'])->values();

                        $columns = collect([
                            collect(),
                            collect(),
                            collect(),
                        ]);

                        $columnSizes = [0, 0, 0];

                        foreach ($areas as $area) {
                            $elements = collect($area['elements'] ?? [])->values();
                            $areaRowsCount = max($elements->count(), 1);

                            $targetColumnIndex = array_search(min($columnSizes), $columnSizes, true);

                            $columns[$targetColumnIndex]->push([
                                'id' => $area['id'] ?? null,
                                'name' => $area['name'] ?? 'Sin área',
                                'count' => (int) ($area['count'] ?? $elements->count()),
                                'elements' => $elements,
                            ]);

                            $columnSizes[$targetColumnIndex] += $areaRowsCount;
                        }

                        $rowNumber = 1;
                    @endphp

                    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-5 py-4">
                            <div class="flex flex-wrap items-center gap-3">
                                <h3 class="text-lg font-bold text-slate-900">
                                    {{ $section['client_name'] }}
                                </h3>

                                <span class="inline-flex rounded-full bg-[#d94d33]/10 px-3 py-1 text-xs font-semibold text-[#d94d33]">
                                    {{ $section['element_type_name'] }}
                                </span>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-3 text-xs text-slate-500">
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 font-medium">
                                    Áreas: {{ $section['areas_count'] ?? 0 }}
                                </span>
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 font-medium">
                                    Activos: {{ $section['elements_count'] ?? 0 }}
                                </span>
                                <span class="inline-flex items-center rounded-full px-3 py-1 font-medium {{ $section['creation_enabled'] ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $section['creation_enabled'] ? 'Creación habilitada' : 'Solo consulta' }}
                                </span>
                            </div>
                        </div>

                        @if($areas->isEmpty())
                            <div class="px-5 py-8 text-center text-sm text-slate-500">
                                No hay activos activos relacionados a esta configuración.
                            </div>
                        @else
                            <div class="grid gap-4 p-4 xl:grid-cols-3">
                                @foreach($columns as $column)
                                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full border-collapse text-sm">
                                                <thead>
                                                    <tr class="bg-[#4f79bd] text-white">
                                                        <th class="w-12 border border-[#3f67a8] px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider">
                                                            N°
                                                        </th>

                                                        <th class="w-[125px] border border-[#3f67a8] px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider">
                                                            Área
                                                        </th>

                                                        <th class="w-[120px] border border-[#3f67a8] px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider">
                                                            Nombre del activo
                                                        </th>

                                                        <th class="min-w-[185px] border border-[#3f67a8] px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider">
                                                            Índice de medición de bandas
                                                        </th>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                    @foreach($column as $area)
                                                        @php
                                                            $elements = collect($area['elements'] ?? [])->values();
                                                            $rowspan = max($elements->count(), 1);
                                                        @endphp

                                                        @forelse($elements as $index => $element)
                                                            <tr class="transition hover:bg-slate-50">
                                                                <td class="border border-slate-200 bg-slate-100 px-2 py-2 text-center text-xs font-bold text-slate-700">
                                                                    {{ $rowNumber }}
                                                                </td>

                                                                @if($index === 0)
                                                                    <td
                                                                        rowspan="{{ $rowspan }}"
                                                                        class="border border-slate-200 bg-slate-100 px-1 py-1 text-center align-middle text-[11px] font-bold uppercase tracking-wide text-slate-800"
                                                                    >
                                                                        <button
                                                                            type="button"
                                                                            class="group inline-flex w-full items-center justify-center rounded-xl px-2 py-2 text-center transition hover:bg-[#d94d33]/10"
                                                                            @click="openAreaSummary({
                                                                                id: @js($area['id'] ?? null),
                                                                                name: @js($area['name'] ?? 'Sin área'),
                                                                                elementTypeId: @js($section['element_type_id']),
                                                                                clientName: @js($section['client_name'] ?? null),
                                                                                elementTypeName: @js($section['element_type_name'] ?? null)
                                                                            })"
                                                                            title="Ver resumen del área"
                                                                        >
                                                                            <span class="leading-tight transition group-hover:text-[#d94d33]">
                                                                                {{ $area['name'] }}
                                                                            </span>
                                                                        </button>
                                                                    </td>
                                                                @endif

                                                                <td class="border border-slate-200 bg-white px-2 py-2 text-center">
                                                                    <a
                                                                        href="{{ $element['url'] }}"
                                                                        class="inline-flex items-center justify-center rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-bold text-[#3566b8] transition hover:bg-[#d94d33]/10 hover:text-[#d94d33]"
                                                                    >
                                                                        {{ $element['name'] }}
                                                                    </a>
                                                                </td>

                                                                <td class="border border-slate-200 bg-slate-50 px-2 py-2 text-center">
                                                                    @if(!empty($element['band_measurement_index']))
                                                                        <span class="text-xs font-semibold text-slate-800">
                                                                            {{ $element['band_measurement_index'] }}
                                                                        </span>
                                                                    @else
                                                                        <span class="text-xs font-semibold text-slate-400">
                                                                            —
                                                                        </span>
                                                                    @endif
                                                                </td>
                                                            </tr>

                                                            @php $rowNumber++; @endphp
                                                        @empty
                                                            <tr class="transition hover:bg-slate-50">
                                                                <td class="border border-slate-200 bg-slate-100 px-2 py-2 text-center text-xs font-bold text-slate-700">
                                                                    {{ $rowNumber }}
                                                                </td>

                                                                <td
                                                                    rowspan="{{ $rowspan }}"
                                                                    class="border border-slate-200 bg-slate-100 px-2 py-2 text-center align-middle text-[11px] font-extrabold uppercase tracking-wide text-slate-800"
                                                                >
                                                                    <button
                                                                        type="button"
                                                                        class="group inline-flex w-full items-center justify-center rounded-xl px-2 py-2 text-center transition hover:bg-[#d94d33]/10"
                                                                        @click="openAreaSummary({
                                                                            id: @js($area['id'] ?? null),
                                                                            name: @js($area['name'] ?? 'Sin área'),
                                                                            elementTypeId: @js($section['element_type_id']),
                                                                            clientName: @js($section['client_name'] ?? null),
                                                                            elementTypeName: @js($section['element_type_name'] ?? null)
                                                                        })"
                                                                        title="Ver resumen del área"
                                                                    >
                                                                        <span class="leading-tight transition group-hover:text-[#d94d33]">
                                                                            {{ $area['name'] }}
                                                                        </span>
                                                                    </button>
                                                                </td>

                                                                <td class="border border-slate-200 bg-white px-2 py-2 text-center">
                                                                    <span class="text-xs text-slate-400">—</span>
                                                                </td>

                                                                <td class="border border-slate-200 bg-slate-50 px-2 py-2 text-center">
                                                                    <span class="text-xs text-slate-400">—</span>
                                                                </td>
                                                            </tr>

                                                            @php $rowNumber++; @endphp
                                                        @endforelse
                                                    @endforeach

                                                    @if($column->isEmpty())
                                                        <tr>
                                                            <td colspan="4" class="border border-slate-200 bg-slate-50 px-3 py-8 text-center text-xs font-medium text-slate-400">
                                                                Sin activos en esta columna.
                                                            </td>
                                                        </tr>
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
{{-- Modal resumen por área --}}
    <div
        x-cloak
        x-show="areaSummaryOpen"
        x-transition.opacity
        class="fixed inset-0 z-[9990] flex items-center justify-center bg-slate-900/60 px-4 py-6"
        @keydown.escape.window="closeAreaSummary()"
    >
        <div
            x-show="areaSummaryOpen"
            class="flex max-h-[92vh] w-full max-w-7xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl"
            @click.outside="closeAreaSummary()"
            @click.stop
        >
            <div class="border-b border-slate-200 bg-white px-6 py-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                            Resumen por área
                        </p>

                        <h3 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">
                            Resumen valores mínimos obtenidos
                        </h3>

                        <div class="mt-2 flex flex-wrap items-center gap-2 text-xs font-semibold">
                            <span class="inline-flex rounded-full bg-[#4f79bd]/10 px-3 py-1 text-[#315f9e]">
                                Área:
                                <span class="ml-1" x-text="selectedArea?.name || '—'"></span>
                            </span>

                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-slate-600">
                                Cliente:
                                <span class="ml-1" x-text="selectedArea?.client_name || selectedArea?.clientName || '—'"></span>
                            </span>

                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-slate-600">
                                Tipo:
                                <span class="ml-1" x-text="selectedArea?.element_type_name || selectedArea?.elementTypeName || '—'"></span>
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="closeAreaSummary()"
                            class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto bg-slate-50 px-6 py-5">
                <div
                    x-show="areaSummaryLoading"
                    x-cloak
                    class="rounded-2xl border border-slate-200 bg-white px-5 py-8 text-center text-sm font-medium text-slate-500"
                >
                    Cargando resumen del área...
                </div>

                <div
                    x-show="areaSummaryError"
                    x-cloak
                    class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700"
                    x-text="areaSummaryError"
                ></div>

                <template x-if="!areaSummaryLoading && !areaSummaryError && areaSummaryItems.length === 0">
                    <div class="rounded-2xl border border-slate-200 bg-white px-5 py-8 text-center text-sm font-medium text-slate-500">
                        No hay activos con información oficial disponible para esta área.
                    </div>
                </template>

                <template x-if="!areaSummaryLoading && !areaSummaryError && areaSummaryItems.length > 0">
                    <div class="grid gap-4 xl:grid-cols-2">
                        <template x-for="item in areaSummaryItems" :key="'area-summary-item-' + item.id">
                            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                                <table class="w-full border-collapse text-xs md:text-sm">
                                    <tbody>
                                        <tr class="bg-[#4f79bd] text-white">
                                            <th class="w-[150px] border border-[#3f67a8] px-3 py-2 text-center font-extrabold">
                                                <a
                                                    :href="item.url"
                                                    class="inline-flex items-center justify-center rounded-lg bg-white/15 px-3 py-1 text-white transition hover:bg-white/25"
                                                    x-text="item.name"
                                                ></a>
                                            </th>

                                            <th class="border border-[#3f67a8] px-3 py-2 text-center font-extrabold">
                                                Cubierta superior
                                            </th>

                                            <th class="border border-[#3f67a8] px-3 py-2 text-center font-extrabold">
                                                Cubierta inferior
                                            </th>

                                            <th class="w-[120px] border border-[#3f67a8] px-3 py-2 text-center font-extrabold">
                                                Dureza
                                            </th>
                                        </tr>

                                        <tr class="bg-white">
                                            <th class="border border-slate-200 bg-slate-50 px-3 py-2 text-center font-extrabold text-slate-800">
                                                Especificación
                                            </th>

                                            <td
                                                class="border border-slate-200 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900"
                                                x-text="formatValue(item.top_specification)"
                                            ></td>

                                            <td
                                                class="border border-slate-200 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900"
                                                x-text="formatValue(item.bottom_specification)"
                                            ></td>

                                            <td
                                                class="border border-slate-200 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900"
                                                x-text="formatValue(item.hardness_specification)"
                                            ></td>
                                        </tr>

                                        <tr class="bg-white">
                                            <th class="border border-slate-200 bg-slate-50 px-3 py-2 text-center font-extrabold text-slate-800">
                                                Medición
                                            </th>

                                            <td
                                                class="border border-slate-200 px-3 py-2 text-center font-semibold text-slate-900"
                                                x-text="formatValue(item.top_measurement)"
                                            ></td>

                                            <td
                                                class="border border-slate-200 px-3 py-2 text-center font-semibold text-slate-900"
                                                x-text="formatValue(item.bottom_measurement)"
                                            ></td>

                                            <td class="border border-slate-200 px-3 py-2 text-center font-semibold text-slate-400">
                                                —
                                            </td>
                                        </tr>

                                        <tr class="bg-slate-50/70">
                                            <th class="border border-slate-200 bg-slate-50 px-3 py-2 text-center font-extrabold text-slate-800">
                                                Porcentaje
                                            </th>

                                            <td
                                                class="border border-slate-200 px-3 py-2 text-center font-extrabold"
                                                :class="percentageClass(item.top_percentage)"
                                                x-text="formatPercentage(item.top_percentage)"
                                            ></td>

                                            <td
                                                class="border border-slate-200 px-3 py-2 text-center font-extrabold"
                                                :class="percentageClass(item.bottom_percentage)"
                                                x-text="formatPercentage(item.bottom_percentage)"
                                            ></td>

                                            <td class="border border-slate-200 px-3 py-2 text-center font-semibold text-slate-400">
                                                —
                                            </td>
                                        </tr>

                                        <tr class="bg-white">
                                            <td colspan="4" class="border border-slate-200 px-3 py-2">
                                                <div class="flex flex-wrap justify-end gap-2 text-[11px] font-semibold text-slate-500">
                                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1">
                                                        Informe estado:
                                                        <span class="ml-1 text-slate-700" x-text="item.band_state_report_date || 'Sin reporte'"></span>
                                                    </span>

                                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1">
                                                        Espesores:
                                                        <span class="ml-1 text-slate-700" x-text="item.thickness_report_date || 'Sin reporte'"></span>
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>  
    </div>

    </div>
    
<script>
    function measurementLevelOneModule() {
        return {
            areaSummaryOpen: false,
            areaSummaryLoading: false,
            areaSummaryError: null,
            selectedArea: null,
            areaSummaryItems: [],

            async openAreaSummary(area) {
                if (!area || !area.id || !area.elementTypeId) {
                    this.areaSummaryError = 'No fue posible identificar el área seleccionada.';
                    this.areaSummaryOpen = true;
                    return;
                }

                this.selectedArea = area;
                this.areaSummaryItems = [];
                this.areaSummaryError = null;
                this.areaSummaryOpen = true;
                this.areaSummaryLoading = true;

                const url = @js(route('admin.system-modules.measurements.level-one.area-summary', [
                    'area' => '__AREA__',
                ])) + '?element_type_id=' + encodeURIComponent(area.elementTypeId);

                try {
                    const response = await fetch(url.replace('__AREA__', area.id), {
                        headers: {
                            'Accept': 'application/json',
                        },
                    });

                    const data = await response.json();

                    if (!response.ok || data.success === false) {
                        this.areaSummaryError = data.message || 'No fue posible cargar el resumen del área.';
                        return;
                    }

                    this.selectedArea = {
                        ...this.selectedArea,
                        ...(data.area || {}),
                    };

                    this.areaSummaryItems = data.items || [];
                } catch (error) {
                    this.areaSummaryError = 'Ocurrió un error de red al cargar el resumen del área.';
                } finally {
                    this.areaSummaryLoading = false;

                    this.$nextTick(() => {
                        if (window.lucide) {
                            window.lucide.createIcons();
                        }
                    });
                }
            },

            closeAreaSummary() {
                this.areaSummaryOpen = false;
                this.areaSummaryLoading = false;
                this.areaSummaryError = null;
                this.areaSummaryItems = [];
                this.selectedArea = null;
            },

            formatValue(value) {
                if (value === null || value === undefined || value === '') {
                    return '—';
                }

                const number = Number(value);

                if (Number.isNaN(number)) {
                    return value;
                }

                return new Intl.NumberFormat('es-CO', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2,
                }).format(number);
            },

            formatPercentage(value) {
                if (value === null || value === undefined || value === '') {
                    return '—';
                }

                const number = Number(value);

                if (Number.isNaN(number)) {
                    return '—';
                }

                return `${new Intl.NumberFormat('es-CO', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0,
                }).format(number)}%`;
            },
            percentageClass(value) {
                if (value === null || value === undefined || value === '') {
                    return 'text-slate-400';
                }

                const number = Number(value);

                if (Number.isNaN(number)) {
                    return 'text-slate-400';
                }

                if (number <= 54.9) {
                    return 'text-red-700';
                }

                if (number >= 55 && number <= 89.9) {
                    return 'text-amber-600';
                }

                return 'text-emerald-700';
            },
            percentageBadgeClass(value) {
                if (value === null || value === undefined || value === '') {
                    return 'bg-slate-100 text-slate-400 ring-slate-200';
                }

                const number = Number(value);

                if (Number.isNaN(number)) {
                    return 'bg-slate-100 text-slate-400 ring-slate-200';
                }

                if (number <= 54.9) {
                    return 'bg-red-50 text-red-700 ring-red-200';
                }

                if (number >= 55 && number <= 89.9) {
                    return 'bg-amber-50 text-amber-700 ring-amber-200';
                }

                return 'bg-emerald-50 text-emerald-700 ring-emerald-200';
            },
        };
    }
</script>
@endsection