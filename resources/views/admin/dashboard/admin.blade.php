@extends('layouts.admin')

@section('title', 'Dashboard de reportes')
@section('header_title', 'Dashboard de reportes')

@section('content')
    <div class="space-y-8">
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-slate-900">
                    Dashboard de reportes por agrupación
                </h2>
                <p class="mt-2 text-slate-600">
                    Selecciona un rango de fechas y accede a las agrupaciones disponibles según tu perfil.
                </p>
            </div>

            @if($isReadOnly)
                <span class="inline-flex items-center rounded-xl bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">
                    Modo solo lectura
                </span>
            @endif
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
                <div class="w-full lg:w-56">
                    <label for="date_from" class="mb-2 block text-sm font-medium text-slate-700">
                        Fecha inicial
                    </label>
                    <input
                        id="date_from"
                        type="date"
                        value="{{ $dateFrom }}"
                        class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-800 shadow-sm focus:border-[#d94d33] focus:outline-none focus:ring-2 focus:ring-[#d94d33]/20"
                    >
                </div>

                <div class="w-full lg:w-56">
                    <label for="date_to" class="mb-2 block text-sm font-medium text-slate-700">
                        Fecha final
                    </label>
                    <input
                        id="date_to"
                        type="date"
                        value="{{ $dateTo }}"
                        class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-800 shadow-sm focus:border-[#d94d33] focus:outline-none focus:ring-2 focus:ring-[#d94d33]/20"
                    >
                </div>

                <div class="text-xs text-slate-500 lg:pb-2">
                    Rango predeterminado: desde el 1 de enero del año actual hasta hoy.
                </div>
            </div>
        </div>

        @if($groupModules->isEmpty())
            <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900">No hay agrupaciones disponibles</h3>
                <p class="mt-2 text-sm text-slate-500">
                    No se encontraron agrupaciones visibles para este usuario.
                </p>
            </div>
        @else
            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                @foreach($groupModules as $module)
                    <a
                        href="{{ route('admin.preventive-reports.group', ['group' => $module['group_id']]) }}"
                        data-group-link
                        class="group relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-xl"
                    >
                        <div class="absolute inset-0 bg-gradient-to-br from-orange-50 via-white to-slate-50"></div>

                        <div class="relative space-y-6">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Agrupación
                                    </p>

                                    <h3 class="mt-3 text-xl font-bold leading-tight text-slate-900">
                                        {{ $module['group_name'] }}
                                    </h3>

                                    <p class="mt-2 text-sm text-slate-600">
                                        Planta {{ $module['client_name'] }}
                                    </p>
                                </div>
                            </div>

                            @if(!empty($module['group_description']))
                                <p class="text-sm leading-relaxed text-slate-500">
                                    {{ $module['group_description'] }}
                                </p>
                            @endif

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
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const dateFrom = document.getElementById('date_from');
            const dateTo = document.getElementById('date_to');

            document.querySelectorAll('[data-group-link]').forEach(link => {
                link.addEventListener('click', function (event) {
                    const from = dateFrom?.value;
                    const to = dateTo?.value;

                    if (!from || !to) {
                        event.preventDefault();
                        alert('Debes seleccionar la fecha inicial y la fecha final.');
                        return;
                    }

                    if (to < from) {
                        event.preventDefault();
                        alert('La fecha final no puede ser menor que la fecha inicial.');
                        return;
                    }

                    const url = new URL(this.href, window.location.origin);
                    url.searchParams.set('date_from', from);
                    url.searchParams.set('date_to', to);

                    this.href = url.toString();
                });
            });
        });
    </script>
@endsection