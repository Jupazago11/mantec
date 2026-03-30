@extends('layouts.admin')

@section('title', 'Dashboard Inspector')
@section('header_title', 'Dashboard Inspector')

@section('content')
    <div class="space-y-8">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Panel de trabajo</h2>
            <p class="mt-2 text-slate-600">
                Inspecciones asignadas, pendientes y últimas actividades.
            </p>
        </div>

        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
            <x-admin.widgets.stat-card title="Pendientes" value="7" description="Inspecciones por realizar" />
            <x-admin.widgets.stat-card title="Realizadas" value="18" description="Completadas este periodo" />
            <x-admin.widgets.stat-card title="Elementos" value="12" description="Asignados para revisión" />
            <x-admin.widgets.stat-card title="Alertas" value="3" description="Casos prioritarios" />
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Próximas acciones</h3>
            <div class="mt-4 space-y-3 text-sm text-slate-600">
                <p>• Revisar Banda 001 en área de transporte.</p>
                <p>• Registrar hallazgos del componente Rodillo.</p>
                <p>• Cargar evidencias del turno actual.</p>
            </div>
        </div>
    </div>
@endsection