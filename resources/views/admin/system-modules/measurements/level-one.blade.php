@extends('layouts.measurements')

@section('title', 'Mediciones - Nivel 1')
@section('header_title', 'Mediciones')

@section('content')
    <div
        x-data="{}"
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
                                                            Descripción banda
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
                                                                            @click="$dispatch('open-area-summary', {
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
                                                                        @click="$dispatch('open-area-summary', {
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
    </div>

    @include('admin.system-modules.measurements.partials.area-summary-modal')
@endsection