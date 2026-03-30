@extends('layouts.admin')

@section('title', 'Dashboard Cliente')
@section('header_title', 'Dashboard Cliente')

@section('content')
    <div class="space-y-8">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Resumen de mi cliente</h2>
            <p class="mt-2 text-slate-600">
                Consulta de áreas, elementos y reportes del cliente asignado.
            </p>
        </div>

        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
            <x-admin.widgets.stat-card title="Áreas" value="6" description="Áreas operativas" />
            <x-admin.widgets.stat-card title="Elementos" value="32" description="Elementos registrados" />
            <x-admin.widgets.stat-card title="Reportes" value="14" description="Reportes disponibles" />
            <x-admin.widgets.stat-card title="Hallazgos" value="9" description="Pendientes de seguimiento" />
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Últimos movimientos</h3>
            <div class="mt-4 space-y-3 text-sm text-slate-600">
                <p>• Se cargó un nuevo reporte semanal.</p>
                <p>• Se actualizó la condición de un componente crítico.</p>
                <p>• Se registró nueva evidencia fotográfica.</p>
            </div>
        </div>
    </div>
@endsection