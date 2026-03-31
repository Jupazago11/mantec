@extends('layouts.admin')

@section('title', 'Dashboard administrador')
@section('header_title', 'Dashboard administrador')

@section('content')
    <div class="space-y-8">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Vista de administrador</h2>
            <p class="mt-2 text-slate-600">
                Accede a los reportes preventivos por tipo de activo y cliente.
            </p>
        </div>

        @if($reportModules->isEmpty())
            <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
                <p class="text-sm text-slate-500">
                    No hay tipos de activo configurados para tus clientes asignados.
                </p>
            </div>
        @else
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
        @endif
    </div>
@endsection