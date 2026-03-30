@extends('layouts.admin')

@section('title', 'Dashboard SuperAdmin')
@section('header_title', 'Dashboard SuperAdmin')

@section('content')
    <div class="space-y-8">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Vista global del sistema</h2>
            <p class="mt-2 text-slate-600">
                Resumen general de clientes, usuarios, elementos y operación.
            </p>
        </div>

        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
            <x-admin.widgets.stat-card title="Clientes" value="12" description="Total registrados" />
            <x-admin.widgets.stat-card title="Usuarios" value="28" description="Activos en el sistema" />
            <x-admin.widgets.stat-card title="Elementos" value="146" description="Inventario operativo" />
            <x-admin.widgets.stat-card title="Inspecciones" value="320" description="Registros acumulados" />
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Accesos rápidos</h3>
            <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <a href="#" class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50">Gestionar usuarios</a>
                <a href="#" class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50">Gestionar clientes</a>
                <a href="#" class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50">Ver reportes globales</a>
            </div>
        </div>
    </div>
@endsection