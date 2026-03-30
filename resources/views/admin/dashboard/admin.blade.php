@extends('layouts.admin')

@section('title', 'Dashboard Administrador')
@section('header_title', 'Dashboard Administrador')

@section('content')
    <div class="space-y-8">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Gestión operativa</h2>
            <p class="mt-2 text-slate-600">
                Seguimiento de clientes, elementos e inspecciones.
            </p>
        </div>

        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
            <x-admin.widgets.stat-card title="Clientes" value="8" description="Clientes gestionados" />
            <x-admin.widgets.stat-card title="Áreas" value="24" description="Áreas registradas" />
            <x-admin.widgets.stat-card title="Elementos" value="96" description="Elementos activos" />
            <x-admin.widgets.stat-card title="Inspecciones" value="180" description="Pendientes y completadas" />
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Actividad reciente</h3>
            <div class="mt-4 space-y-3 text-sm text-slate-600">
                <p>• Se registró una nueva inspección en Banda 001.</p>
                <p>• Se actualizó el catálogo de elementos.</p>
                <p>• Se generó un reporte semanal para cliente principal.</p>
            </div>
        </div>
    </div>
@endsection