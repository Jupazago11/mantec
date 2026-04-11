@extends('layouts.admin')

@section('title', 'Dashboard')
@section('header_title', 'Dashboard')

@section('content')
    <div class="space-y-10">
        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-slate-900">Dashboard preventivo</h2>
                <p class="mt-2 text-slate-600">
                    Consulta reportes preventivos generales por cliente y reportes por tipo de activo, organizados por año.
                </p>
            </div>

            @if($isReadOnly)
                <div class="inline-flex items-center rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-800">
                    Modo observador: solo lectura
                </div>
            @endif
        </div>

        @if($generalReportModules->isEmpty() && $reportModules->isEmpty())
            <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900">No hay módulos disponibles</h3>
                <p class="mt-2 text-sm text-slate-500">
                    No se encontraron clientes o tipos de activo disponibles para este usuario.
                </p>
            </div>
        @endif

        @if($generalReportModules->isNotEmpty())
            <div class="space-y-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Reportes generales por cliente</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Cada tarjeta abre el consolidado general del cliente para el año indicado.
                    </p>
                </div>

                <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($generalReportModules as $module)
                        <a
                            href="{{ route('admin.preventive-reports.general', ['client' => $module['client_id'], 'year' => $module['year']]) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="group relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-xl"
                        >
                            <div class="absolute inset-0 bg-gradient-to-br from-slate-50 via-white to-orange-50"></div>

                            <div class="relative space-y-6">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                            Reporte preventivo general
                                        </p>

                                        <h3 class="mt-3 text-xl font-bold leading-tight text-slate-900">
                                            Planta {{ $module['client_name'] }}
                                        </h3>
                                    </div>

                                    <div class="rounded-2xl bg-[#d94d33]/10 px-4 py-2 text-sm font-bold text-[#d94d33]">
                                        {{ $module['year'] }}
                                    </div>
                                </div>

                                <div>
                                    @if($module['has_records'])
                                        <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                            Con registros
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                            Sin registros aún
                                        </span>
                                    @endif
                                </div>

                                <div class="flex items-center justify-between">
                                    <span class="inline-flex items-center rounded-xl bg-[#d94d33] px-4 py-2 text-sm font-semibold text-white transition group-hover:bg-[#b83f29]">
                                        Ver reporte general
                                    </span>

                                    <span class="text-sm font-semibold text-slate-400 transition group-hover:text-slate-600">
                                        →
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        @if($reportModules->isNotEmpty())
            <div class="space-y-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Reportes por tipo de activo</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Cada tarjeta abre el reporte preventivo filtrado por cliente, tipo de activo y año.
                    </p>
                </div>

                <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($reportModules as $module)
                        <a
                            href="{{ route('admin.preventive-reports.show', [
                                'client' => $module['client_id'],
                                'elementType' => $module['element_type_id'],
                                'year' => $module['year'],
                            ]) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="group relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-xl"
                        >
                            <div class="absolute inset-0 bg-gradient-to-br from-orange-50 via-white to-slate-50"></div>

                            <div class="relative space-y-6">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                            Reporte preventivo
                                        </p>

                                        <h3 class="mt-3 text-xl font-bold leading-tight text-slate-900">
                                            {{ $module['element_type_name'] }}
                                        </h3>

                                        <p class="mt-2 text-sm text-slate-600">
                                            Planta {{ $module['client_name'] }}
                                        </p>
                                    </div>

                                    <div class="rounded-2xl bg-[#d94d33]/10 px-4 py-2 text-sm font-bold text-[#d94d33]">
                                        {{ $module['year'] }}
                                    </div>
                                </div>

                                <div>
                                    @if($module['has_records'])
                                        <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                            Con registros
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                            Sin registros aún
                                        </span>
                                    @endif
                                </div>

                                <div class="flex items-center justify-between">
                                    <span class="inline-flex items-center rounded-xl bg-[#d94d33] px-4 py-2 text-sm font-semibold text-white transition group-hover:bg-[#b83f29]">
                                        Ver reportes
                                    </span>

                                    <span class="text-sm font-semibold text-slate-400 transition group-hover:text-slate-600">
                                        →
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection
