@extends('layouts.admin')

@section('title', 'Dashboard administrador')
@section('header_title', 'Dashboard administrador')

@section('content')
    <div class="space-y-8">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Vista de administrador</h2>
            <p class="mt-2 text-slate-600">
                Resumen general de gestión para los clientes asignados.
            </p>
        </div>

        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Clientes asignados</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">
                    {{ auth()->user()->clients->count() }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Áreas activas</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">—</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Activos registrados</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">—</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Inspectores</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">—</p>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Accesos rápidos</h3>
            <p class="mt-1 text-sm text-slate-500">
                Usa estas opciones para administrar la operación de tus clientes.
            </p>

            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <a href="#"
                   class="rounded-2xl border border-slate-200 p-5 transition hover:bg-slate-50">
                    <p class="text-base font-semibold text-slate-900">Usuarios</p>
                    <p class="mt-1 text-sm text-slate-500">Gestiona administradores cliente e inspectores.</p>
                </a>

                <a href="#"
                   class="rounded-2xl border border-slate-200 p-5 transition hover:bg-slate-50">
                    <p class="text-base font-semibold text-slate-900">Diagnósticos</p>
                    <p class="mt-1 text-sm text-slate-500">Administra catálogos técnicos por cliente.</p>
                </a>

                <a href="#"
                   class="rounded-2xl border border-slate-200 p-5 transition hover:bg-slate-50">
                    <p class="text-base font-semibold text-slate-900">Condiciones</p>
                    <p class="mt-1 text-sm text-slate-500">Configura condiciones disponibles por cliente.</p>
                </a>
            </div>
        </div>
    </div>
@endsection