    @extends('layouts.admin')

    @section('title', 'Dashboard administrador')
    @section('header_title', 'Dashboard administrador')

    @section('content')
        <div class="space-y-10">
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-slate-900">Vista de administrador</h2>
                <p class="mt-2 text-slate-600">
                    Accede a los reportes preventivos por tipo de activo y también a reportes generales por cliente.
                </p>
            </div>

            @if($generalReportModules->isNotEmpty())
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-slate-900">Reportes generales por cliente</h3>

                    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                        @foreach($generalReportModules as $module)
                            <a
                                href="{{ route('admin.preventive-reports.general', $module['client_id']) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="group relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-xl"
                            >
                                <div class="absolute inset-0 bg-gradient-to-br from-slate-50 via-white to-orange-50 opacity-100"></div>

                                <div class="relative">
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

                                    <div class="mt-8 flex items-center justify-between">
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
                    <h3 class="text-lg font-semibold text-slate-900">Reportes por tipo de activo</h3>

                    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                        @foreach($reportModules as $module)
                            <a
                                href="{{ route('admin.preventive-reports.show', [$module['client_id'], $module['element_type_id']]) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="group relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-xl"
                            >
                                <div class="absolute inset-0 bg-gradient-to-br from-orange-50 via-white to-slate-50 opacity-100"></div>

                                <div class="relative">
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

                                    <div class="mt-8 flex items-center justify-between">
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