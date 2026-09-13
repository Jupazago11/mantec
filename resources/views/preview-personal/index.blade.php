@extends('preview-personal._layout')

@section('title', 'Inicio')

@section('content')
<div class="mx-auto max-w-3xl">
    <h1 class="text-2xl font-bold text-slate-900">Gestión de Personal y Programación de Actividades</h1>
    <p class="mt-2 text-sm text-slate-600">
        Prototipo visual para revisar con Mantec el aspecto de las pantallas principales del módulo nuevo.
        Los datos que se ven aquí son de ejemplo, tomados de los Excel reales compartidos
        (<code>Bitacora Septiembre 2026.xlsx</code>, <code>DIARIO DE CAMPO TRABAJOS MAN &amp; TEC.xlsx</code>),
        para que la comparación con el formato actual sea directa.
    </p>

    <div class="mt-8 grid gap-4 sm:grid-cols-2">
        @php
            $cards = [
                ['route' => 'preview-personal.login', 'title' => 'Login administrativo', 'desc' => 'Acceso independiente del login actual del sistema.', 'icon' => 'log-in'],
                ['route' => 'preview-personal.programacion', 'title' => 'Crear Programación', 'desc' => 'El supervisor arma las actividades del día por grupo.', 'icon' => 'calendar-days'],
                ['route' => 'preview-personal.diario-campo', 'title' => 'Diario de Campo', 'desc' => 'Detalle técnico enriquecido de cada actividad.', 'icon' => 'clipboard-list'],
                ['route' => 'preview-personal.bitacora', 'title' => 'Bitácora mensual', 'desc' => 'Consolidado de horas con trazabilidad de 3 valores.', 'icon' => 'table'],
                ['route' => 'preview-personal.empleados', 'title' => 'Empleados', 'desc' => 'CRUD con certificados/inducciones dinámicos.', 'icon' => 'users'],
            ];
        @endphp

        @foreach ($cards as $card)
            <a href="{{ route($card['route']) }}" class="flex items-start gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-[#d55b20]/40 hover:shadow-md">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#d55b20]/10 text-[#d55b20]">
                    <i data-lucide="{{ $card['icon'] }}" class="h-5 w-5"></i>
                </span>
                <span>
                    <span class="block font-semibold text-slate-900">{{ $card['title'] }}</span>
                    <span class="block text-sm text-slate-500">{{ $card['desc'] }}</span>
                </span>
            </a>
        @endforeach
    </div>
</div>
@endsection
