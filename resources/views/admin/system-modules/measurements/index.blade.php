@extends('layouts.measurements')

@section('title', 'Mediciones')
@section('header_title', 'Inicio de Mediciones')

@section('content')
    <div class="space-y-8">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Inicio de Mediciones</h2>
            <p class="mt-2 text-slate-600">
                Esta sección centraliza el nuevo módulo operativo de mediciones y servirá como punto de entrada a sus vistas internas.
            </p>
        </div>

        <div class="grid gap-8 xl:grid-cols-[360px_minmax(0,1fr)]">
            <div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">¿Para qué sirve esta sección?</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Este módulo permitirá consultar y gestionar información técnica histórica por activo, comenzando por el flujo de mediciones para ciertos tipos de activo habilitados.
                    </p>

                    <div class="mt-6 space-y-4">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Objetivo</p>
                            <p class="mt-2 text-sm text-slate-700">
                                Organizar información técnica especializada por cliente, tipo de activo, área y activo.
                            </p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Estado actual</p>
                            <p class="mt-2 text-sm text-slate-700">
                                Base del módulo en construcción.
                            </p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Próximo paso</p>
                            <p class="mt-2 text-sm text-slate-700">
                                Explorar el Nivel 1 para navegar por cliente, tipo de activo, áreas y activos.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-5">
                        <h3 class="text-lg font-semibold text-slate-900">Resumen funcional</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Esta experiencia es independiente del panel administrativo general y tendrá su propia navegación interna.
                        </p>
                    </div>

                    <div class="grid gap-6 p-6 md:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-[#d94d33]/10 text-[#d94d33]">
                                    <i data-lucide="layout-panel-left" class="h-5 w-5"></i>
                                </span>
                                <h4 class="text-base font-semibold text-slate-900">Sidebar propio</h4>
                            </div>

                            <p class="mt-3 text-sm leading-6 text-slate-600">
                                El módulo tiene su propia navegación para mantener separada su operación del panel administrativo principal.
                            </p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-[#d94d33]/10 text-[#d94d33]">
                                    <i data-lucide="folders" class="h-5 w-5"></i>
                                </span>
                                <h4 class="text-base font-semibold text-slate-900">Nivel 1</h4>
                            </div>

                            <p class="mt-3 text-sm leading-6 text-slate-600">
                                Desde el Nivel 1 podrás navegar por configuraciones activas, áreas y activos para entrar al flujo operativo.
                            </p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 md:col-span-2">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-[#d94d33]/10 text-[#d94d33]">
                                    <i data-lucide="arrow-right-circle" class="h-5 w-5"></i>
                                </span>
                                <h4 class="text-base font-semibold text-slate-900">Acceso rápido</h4>
                            </div>

                            <div class="mt-4">
                                <a
                                    href="{{ route('admin.system-modules.measurements.level-one') }}"
                                    class="inline-flex items-center gap-2 rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                                >
                                    <i data-lucide="folders" class="h-4 w-4"></i>
                                    Ir al Nivel 1
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection