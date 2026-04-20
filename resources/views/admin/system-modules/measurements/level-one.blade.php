@extends('layouts.measurements')

@section('title', 'Mediciones - Nivel 1')
@section('header_title', 'Nivel 1')

@section('content')
    <div class="space-y-8">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Nivel 1</h2>
            <p class="mt-2 text-slate-600">
                Consulta la estructura operativa habilitada para este módulo por cliente, tipo de activo, áreas y activos.
            </p>
        </div>

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
                        $areas = collect($section['areas']);
                        $splitIndex = (int) ceil($areas->count() / 2);
                        $leftAreas = $areas->slice(0, $splitIndex)->values();
                        $rightAreas = $areas->slice($splitIndex)->values();

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
                                    Áreas: {{ $section['areas_count'] }}
                                </span>
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 font-medium">
                                    Activos: {{ $section['elements_count'] }}
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
                            <div class="grid gap-0 xl:grid-cols-2">
                                {{-- Columna izquierda --}}
                                <div class="overflow-x-auto xl:border-r xl:border-slate-200">
                                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                                        <thead class="bg-slate-50">
                                            <tr>
                                                <th class="w-14 px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                                    N°
                                                </th>
                                                <th class="px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                                    Nombre del activo
                                                </th>
                                                <th class="w-[220px] px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                                    Índice de medición de bandas
                                                </th>
                                            </tr>
                                        </thead>

                                        <tbody class="divide-y divide-slate-200 bg-white">
                                            @foreach($leftAreas as $area)
                                                <tr class="bg-slate-100/80">
                                                    <td colspan="3" class="px-3 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-700">
                                                        {{ $area['name'] }}
                                                        <span class="ml-2 rounded-full bg-white px-2 py-0.5 text-[10px] font-semibold text-slate-500">
                                                            {{ $area['elements_count'] }} activo{{ $area['elements_count'] === 1 ? '' : 's' }}
                                                        </span>
                                                    </td>
                                                </tr>

                                                @foreach($area['elements'] as $element)
                                                    <tr class="hover:bg-slate-50">
                                                        <td class="px-3 py-2.5 text-xs font-semibold text-slate-500">
                                                            {{ $rowNumber }}
                                                        </td>

                                                        <td class="px-3 py-2.5 text-sm">
                                                            <a
                                                                href="{{ $element['url'] }}"
                                                                class="group inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 transition hover:border-[#d94d33]/40 hover:bg-[#d94d33]/5"
                                                            >
                                                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-slate-100 text-slate-500 transition group-hover:bg-[#d94d33]/10 group-hover:text-[#d94d33]">
                                                                    <i data-lucide="activity" class="h-3.5 w-3.5"></i>
                                                                </span>

                                                                <span class="font-medium text-slate-800 group-hover:text-slate-900">
                                                                    {{ $element['name'] }}
                                                                </span>

                                                                <span class="text-slate-400 transition group-hover:text-[#d94d33]">
                                                                    <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
                                                                </span>
                                                            </a>
                                                        </td>

                                                        <td class="px-3 py-2.5 text-sm text-slate-500">
                                                            {{-- vacío intencional por ahora --}}
                                                        </td>
                                                    </tr>

                                                    @php $rowNumber++; @endphp
                                                @endforeach
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                {{-- Columna derecha --}}
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                                        <thead class="bg-slate-50">
                                            <tr>
                                                <th class="w-14 px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                                    N°
                                                </th>
                                                <th class="px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                                    Nombre del activo
                                                </th>
                                                <th class="w-[220px] px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                                    Índice de medición de bandas
                                                </th>
                                            </tr>
                                        </thead>

                                        <tbody class="divide-y divide-slate-200 bg-white">
                                            @foreach($rightAreas as $area)
                                                <tr class="bg-slate-100/80">
                                                    <td colspan="3" class="px-3 py-2.5 text-[11px] font-bold uppercase tracking-wider text-slate-700">
                                                        {{ $area['name'] }}
                                                        <span class="ml-2 rounded-full bg-white px-2 py-0.5 text-[10px] font-semibold text-slate-500">
                                                            {{ $area['elements_count'] }} activo{{ $area['elements_count'] === 1 ? '' : 's' }}
                                                        </span>
                                                    </td>
                                                </tr>

                                                @foreach($area['elements'] as $element)
                                                    <tr class="hover:bg-slate-50">
                                                        <td class="px-3 py-2.5 text-xs font-semibold text-slate-500">
                                                            {{ $rowNumber }}
                                                        </td>

                                                        <td class="px-3 py-2.5 text-sm">
                                                            <a
                                                                href="{{ $element['url'] }}"
                                                                class="group inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 transition hover:border-[#d94d33]/40 hover:bg-[#d94d33]/5"
                                                            >
                                                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-slate-100 text-slate-500 transition group-hover:bg-[#d94d33]/10 group-hover:text-[#d94d33]">
                                                                    <i data-lucide="activity" class="h-3.5 w-3.5"></i>
                                                                </span>

                                                                <span class="font-medium text-slate-800 group-hover:text-slate-900">
                                                                    {{ $element['name'] }}
                                                                </span>

                                                                <span class="text-slate-400 transition group-hover:text-[#d94d33]">
                                                                    <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
                                                                </span>
                                                            </a>
                                                        </td>

                                                        <td class="px-3 py-2.5 text-sm text-slate-500">
                                                            {{-- vacío intencional por ahora --}}
                                                        </td>
                                                    </tr>

                                                    @php $rowNumber++; @endphp
                                                @endforeach
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection