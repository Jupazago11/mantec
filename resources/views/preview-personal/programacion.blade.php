@extends('preview-personal._layout')

@section('title', 'Programación')

@php
    // Datos de ejemplo tomados de las capturas reales del Excel "DIARIO DE CAMPO
    // TRABAJOS MAN & TEC" (Programación del 7/09/2026). Solo para maquetar la
    // pantalla — no hay persistencia real detrás de esta vista.
    $empresasCatalogo = [
        ['nombre' => 'ARGOS', 'defecto' => true],
        ['nombre' => 'CORONA', 'defecto' => false],
        ['nombre' => 'CALIDRA', 'defecto' => false],
    ];

    // Áreas de ejemplo (no hay captura real que las liste, son ilustrativas).
    $areasCatalogo = ['Trituración', 'Molienda', 'Empaque', 'Talleres', 'Confiabilidad', 'Administrativa'];

    // Fuente unica de empleados (confirmado 2026-09-11): la Programacion ya
    // no mantiene su propio catalogo de personas por separado — usa los
    // mismos registros que Empleados/Bitacora. 'empresas' se deriva del
    // campo 'accesos' compartido (completo=true -> vigente, completo=false
    // -> vencido, ausente -> no_posee via el fallback que ya usa el JS de
    // esta vista); 'certs' se aplana de lista a mapa label => estado.
    $empleadosCompartidos = include resource_path('views/preview-personal/_empleados-data.php');
    $estadoAcceso = function (array $accesos, string $empresa) {
        foreach ($accesos as $a) {
            if ($a['empresa'] === $empresa) {
                return $a['completo'] ? 'vigente' : 'vencido';
            }
        }
        return 'no_posee';
    };
    $empleados = collect($empleadosCompartidos)
        ->map(fn ($e) => [
            'nombre' => $e['nickname'],
            'categoria' => $e['categoria'],
            'abreviatura' => $e['abreviatura'],
            'empresas' => collect($empresasCatalogo)
                ->pluck('nombre')
                ->mapWithKeys(fn ($empresa) => [$empresa => $estadoAcceso($e['accesos'], $empresa)])
                ->filter(fn ($estado) => $estado !== 'no_posee')
                ->all(),
            'certs' => collect($e['certs'])->pluck('estado', 'label')->all(),
        ])
        ->values()
        ->all();

    // Dos dias reales de las capturas de "DIARIO DE CAMPO TRABAJOS MAN &
    // TEC" (7/09/2026 y 4/09/2026), para demostrar la navegacion por
    // calendario entre fechas con datos reales distintos. "Hoy" (segun el
    // reloj de la app) arranca sin actividades, para demostrar el estado
    // vacio hasta que se navegue con el calendario a un dia con datos.
    $fechaEjemplo = '2026-09-07';
    $fechaCuatro = '2026-09-04';

    $actividades = [
        ['fecha' => $fechaEjemplo, 'grupo' => 1, 'empresa' => 'ARGOS', 'equipo' => 'TP1 Trituradora', 'actividad' => 'Realizar actividades varias en la TP1 y estar disponibles para atender cualquier evento', 'personas' => [['nombre' => 'Pachón', 'tipo' => 'P'], ['nombre' => 'Yesid', 'tipo' => 'S']], 'horas' => 12, 'jornada' => 'Diurno', 'responsable' => 'Gr-J', 'transporte' => false],
        ['fecha' => $fechaEjemplo, 'grupo' => 1, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Conductor y otras actividades', 'personas' => [['nombre' => 'Monsalve', 'tipo' => 'P']], 'horas' => 12, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaEjemplo, 'grupo' => 1, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Supervisor', 'personas' => [['nombre' => 'Gerónimo', 'tipo' => 'P']], 'horas' => 12, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaEjemplo, 'grupo' => 1, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Transporte Taxi, Grupo 1. Argos', 'personas' => [], 'horas' => null, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => true],

        ['fecha' => $fechaEjemplo, 'grupo' => 2, 'empresa' => 'ARGOS', 'equipo' => 'TP1 Camino', 'actividad' => 'Realizar cambio camino de molienda', 'personas' => [['nombre' => 'Cristian M', 'tipo' => 'P'], ['nombre' => 'Anderson', 'tipo' => 'S']], 'horas' => 10, 'jornada' => 'Diurno', 'responsable' => 'Gr-J', 'transporte' => false],
        ['fecha' => $fechaEjemplo, 'grupo' => 2, 'empresa' => 'ARGOS', 'equipo' => 'TP2 Rotor', 'actividad' => 'Realizar montaje Rotor Secundario', 'personas' => [['nombre' => 'Eder', 'tipo' => 'P'], ['nombre' => 'J Manuel', 'tipo' => 'S'], ['nombre' => 'Brayan Q', 'tipo' => 'S'], ['nombre' => 'Alan', 'tipo' => 'S']], 'horas' => 10, 'jornada' => 'Diurno', 'responsable' => 'Gr-J', 'transporte' => false],
        ['fecha' => $fechaEjemplo, 'grupo' => 2, 'empresa' => 'ARGOS', 'equipo' => 'Lubricación', 'actividad' => 'Realizar Gamas de Lubricación', 'personas' => [['nombre' => 'Omar', 'tipo' => 'P'], ['nombre' => 'Brayan C', 'tipo' => 'S']], 'horas' => 10, 'jornada' => 'Diurno', 'responsable' => 'Gr-J', 'transporte' => false],
        ['fecha' => $fechaEjemplo, 'grupo' => 2, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Herramientero y otras actividades', 'personas' => [['nombre' => 'Evelio', 'tipo' => 'P']], 'horas' => 10, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaEjemplo, 'grupo' => 2, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'SST', 'personas' => [['nombre' => 'Nedy Johana', 'tipo' => 'P']], 'horas' => 10, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaEjemplo, 'grupo' => 2, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Coordinador', 'personas' => [['nombre' => 'Conrado', 'tipo' => 'P']], 'horas' => 10, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaEjemplo, 'grupo' => 2, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Transporte Aerovan 463, Grupo 2. Argos', 'personas' => [], 'horas' => null, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => true],

        ['fecha' => $fechaEjemplo, 'grupo' => 3, 'empresa' => 'CORONA', 'equipo' => 'Confiabilidad', 'actividad' => 'Preventivo Bandas', 'personas' => [['nombre' => 'Wilmar', 'tipo' => 'P'], ['nombre' => 'Espinosa 2', 'tipo' => 'S'], ['nombre' => 'Herrera', 'tipo' => 'S'], ['nombre' => 'Ana C', 'tipo' => 'S'], ['nombre' => 'Norman', 'tipo' => 'S']], 'horas' => 9.5, 'jornada' => 'Diurno', 'responsable' => 'F-C', 'transporte' => false],
        ['fecha' => $fechaEjemplo, 'grupo' => 3, 'empresa' => 'CORONA', 'equipo' => '', 'actividad' => 'SST', 'personas' => [['nombre' => 'Camila', 'tipo' => 'P']], 'horas' => 9.5, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaEjemplo, 'grupo' => 3, 'empresa' => 'CORONA', 'equipo' => '', 'actividad' => 'Supervisor', 'personas' => [['nombre' => 'Fernando', 'tipo' => 'P']], 'horas' => 9.5, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaEjemplo, 'grupo' => 3, 'empresa' => 'CORONA', 'equipo' => '', 'actividad' => 'Transporte Carro, Grupo 3. Corona', 'personas' => [], 'horas' => null, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => true],

        ['fecha' => $fechaEjemplo, 'grupo' => 4, 'empresa' => 'ARGOS', 'equipo' => 'TP2 Rotor', 'actividad' => 'Realizar montaje Rotor Secundario', 'personas' => [['nombre' => 'Danilo', 'tipo' => 'P'], ['nombre' => 'Brayan R', 'tipo' => 'S'], ['nombre' => 'J David', 'tipo' => 'S']], 'horas' => 12, 'jornada' => 'Nocturno', 'responsable' => 'Lm', 'transporte' => false],
        ['fecha' => $fechaEjemplo, 'grupo' => 4, 'empresa' => 'ARGOS', 'equipo' => 'TP1 Trituradora', 'actividad' => 'Realizar actividades varias en la TP1 y estar disponibles para atender cualquier evento', 'personas' => [['nombre' => 'Valbuena', 'tipo' => 'P']], 'horas' => 12, 'jornada' => 'Nocturno', 'responsable' => 'Lm', 'transporte' => false],
        ['fecha' => $fechaEjemplo, 'grupo' => 4, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Transporte Taxi, Grupo 4. Argos', 'personas' => [], 'horas' => null, 'jornada' => 'Nocturno', 'responsable' => '', 'transporte' => true],

        ['fecha' => $fechaEjemplo, 'grupo' => null, 'empresa' => 'ARGOS', 'equipo' => 'Taller San Luis', 'actividad' => 'Actividad Varias', 'personas' => [['nombre' => 'Espinosa 1', 'tipo' => 'S'], ['nombre' => 'Diego S', 'tipo' => 'S']], 'horas' => 10, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaEjemplo, 'grupo' => null, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Incapacidad', 'personas' => [['nombre' => 'Bonilla', 'tipo' => 'S']], 'horas' => null, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaEjemplo, 'grupo' => null, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Descanso', 'personas' => [['nombre' => 'Diego O', 'tipo' => 'S'], ['nombre' => 'Jeison', 'tipo' => 'S'], ['nombre' => 'Alex Sierra', 'tipo' => 'S'], ['nombre' => 'Brayam P', 'tipo' => 'S']], 'horas' => null, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],

        // 4/09/2026 — segundo dia real capturado (mismo Excel), para
        // demostrar navegacion a otra fecha con datos distintos.
        ['fecha' => $fechaCuatro, 'grupo' => 1, 'empresa' => 'ARGOS', 'equipo' => 'TP1 Trituradora', 'actividad' => 'Realizar actividades varias en la TP1 y estar disponibles para atender cualquier evento', 'personas' => [['nombre' => 'Herrera', 'tipo' => 'P'], ['nombre' => 'Alan', 'tipo' => 'S']], 'horas' => 10.5, 'jornada' => 'Diurno', 'responsable' => 'B', 'transporte' => false],
        ['fecha' => $fechaCuatro, 'grupo' => 1, 'empresa' => 'ARGOS', 'equipo' => 'TP2 Cinta', 'actividad' => 'Montar ángulos', 'personas' => [['nombre' => 'Pachón', 'tipo' => 'P'], ['nombre' => 'Yesid', 'tipo' => 'S'], ['nombre' => 'Brayan Q', 'tipo' => 'S']], 'horas' => 10.5, 'jornada' => 'Diurno', 'responsable' => 'B', 'transporte' => false],
        ['fecha' => $fechaCuatro, 'grupo' => 1, 'empresa' => 'ARGOS', 'equipo' => 'Banda U1U06', 'actividad' => 'Realizar empalme vulcanizado Avería', 'personas' => [['nombre' => 'Jeison', 'tipo' => 'P'], ['nombre' => 'Alais', 'tipo' => 'S'], ['nombre' => 'Alex Sierra', 'tipo' => 'S'], ['nombre' => 'Brayan C', 'tipo' => 'S'], ['nombre' => 'Alberto Zurbaran', 'tipo' => 'S']], 'horas' => 10.5, 'jornada' => 'Diurno', 'responsable' => 'B', 'transporte' => false],
        ['fecha' => $fechaCuatro, 'grupo' => 1, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Conductor y otras actividades', 'personas' => [['nombre' => 'Monsalve', 'tipo' => 'P']], 'horas' => 10.5, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaCuatro, 'grupo' => 1, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Supervisor', 'personas' => [['nombre' => 'Bonilla', 'tipo' => 'P']], 'horas' => 10.5, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaCuatro, 'grupo' => 1, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Coordinador', 'personas' => [['nombre' => 'Conrado', 'tipo' => 'P']], 'horas' => 10.5, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaCuatro, 'grupo' => 1, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Transporte Aerovans 463, Grupo 1. Argos', 'personas' => [], 'horas' => null, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => true],

        ['fecha' => $fechaCuatro, 'grupo' => 4, 'empresa' => 'ARGOS', 'equipo' => 'TP1 Trituradora', 'actividad' => 'Realizar actividades varias en la TP1 y estar disponibles para atender cualquier evento', 'personas' => [['nombre' => 'Omar', 'tipo' => 'P'], ['nombre' => 'Neider', 'tipo' => 'S']], 'horas' => 12, 'jornada' => 'Nocturno', 'responsable' => 'F', 'transporte' => false],
        ['fecha' => $fechaCuatro, 'grupo' => 4, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Supervisor', 'personas' => [['nombre' => 'Fernando', 'tipo' => 'P']], 'horas' => 12, 'jornada' => 'Nocturno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaCuatro, 'grupo' => 4, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Transporte Taxi, Grupo 4. Argos', 'personas' => [], 'horas' => null, 'jornada' => 'Nocturno', 'responsable' => '', 'transporte' => true],

        ['fecha' => $fechaCuatro, 'grupo' => 3, 'empresa' => 'CORONA', 'equipo' => 'Elevador', 'actividad' => 'Realizar Procedimientos', 'personas' => [['nombre' => 'Wilmar', 'tipo' => 'P']], 'horas' => 6, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaCuatro, 'grupo' => 3, 'empresa' => 'CORONA', 'equipo' => '', 'actividad' => 'SST', 'personas' => [['nombre' => 'Sara', 'tipo' => 'P']], 'horas' => 6, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaCuatro, 'grupo' => 3, 'empresa' => 'CORONA', 'equipo' => '', 'actividad' => 'Transporte, Grupo 3. Corona', 'personas' => [], 'horas' => null, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => true],

        ['fecha' => $fechaCuatro, 'grupo' => null, 'empresa' => 'ARGOS', 'equipo' => 'Trabajo en Casa', 'actividad' => 'Terminar Informes de Gamas', 'personas' => [['nombre' => 'Anderson', 'tipo' => 'S']], 'horas' => 7, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaCuatro, 'grupo' => null, 'empresa' => 'ARGOS', 'equipo' => 'Taller San Luis', 'actividad' => 'Actividad Varias', 'personas' => [['nombre' => 'Espinosa 2', 'tipo' => 'S'], ['nombre' => 'Diego S', 'tipo' => 'S'], ['nombre' => 'Espinosa 1', 'tipo' => 'S']], 'horas' => 8, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaCuatro, 'grupo' => null, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Licencia', 'personas' => [['nombre' => 'Alejandro', 'tipo' => 'S']], 'horas' => null, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaCuatro, 'grupo' => null, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Permiso cita odontológica', 'personas' => [['nombre' => 'Brayam P', 'tipo' => 'S']], 'horas' => null, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaCuatro, 'grupo' => null, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Descanso', 'personas' => [
            ['nombre' => 'Diego O', 'tipo' => 'S'], ['nombre' => 'J Manuel', 'tipo' => 'S'], ['nombre' => 'Luis Meza', 'tipo' => 'S'], ['nombre' => 'Wilmar Guzman', 'tipo' => 'S'],
            ['nombre' => 'Danilo', 'tipo' => 'S'], ['nombre' => 'J David', 'tipo' => 'S'], ['nombre' => 'Brayan R', 'tipo' => 'S'], ['nombre' => 'Valbuena', 'tipo' => 'S'],
            ['nombre' => 'Ana C', 'tipo' => 'S'], ['nombre' => 'Norman', 'tipo' => 'S'], ['nombre' => 'Camila', 'tipo' => 'S'], ['nombre' => 'Eder', 'tipo' => 'S'],
            ['nombre' => 'Cristian M', 'tipo' => 'S'], ['nombre' => 'Gerónimo', 'tipo' => 'S'], ['nombre' => 'Evelio', 'tipo' => 'S'], ['nombre' => 'Nedy Johana', 'tipo' => 'S'],
            ['nombre' => 'Wilmar Guzman', 'tipo' => 'S'],
        ], 'horas' => null, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
    ];
@endphp

@section('content')
<div
    x-data="programacion({
        actividades: @js($actividades),
        empresas: @js($empresasCatalogo),
        empleados: @js($empleados),
        areas: @js($areasCatalogo),
        hoy: @js(now()->toDateString()),
    })"
    x-init="organizar()"
>
    <div class="mb-4 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900 md:text-2xl">Programación de actividades</h1>
                <div class="mt-1 flex flex-wrap items-center gap-2">
                    <button
                        @click="abrirCalendario()"
                        class="inline-flex items-center gap-1.5 rounded-md bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200"
                    >
                        <i data-lucide="calendar-days" class="h-3.5 w-3.5"></i>
                        <span x-text="fechaSeleccionadaLabel()"></span>
                    </button>
                    <span class="text-xs text-slate-400" x-show="fechaSeleccionada === hoy" x-cloak>(hoy)</span>
                    <span
                        class="rounded-full px-2 py-0.5 text-[11px] font-semibold"
                        :class="esEditable() ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                        x-text="esEditable() ? 'Editable' : 'Solo lectura'"
                    ></span>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    @click="exportarComoImagen()"
                    :disabled="exportando || gruposVisibles().length === 0"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    title="Genera una imagen de la programación para pegarla en WhatsApp (o descargarla en móvil)"
                >
                    <i data-lucide="image-down" class="h-4 w-4" x-show="!exportando"></i>
                    <i data-lucide="loader-2" class="h-4 w-4 animate-spin" x-show="exportando" x-cloak></i>
                    <span x-text="exportando ? 'Generando...' : 'Copiar como imagen'"></span>
                </button>
                <button @click="organizar()" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <i data-lucide="arrow-down-up" class="h-4 w-4"></i> Organizar
                </button>
                <button
                    @click="abrirModal()"
                    :disabled="!esEditable()"
                    class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white"
                    :class="esEditable() ? 'bg-[#d55b20] hover:bg-[#b8481a]' : 'cursor-not-allowed bg-slate-300'"
                >
                    <i data-lucide="plus" class="h-4 w-4"></i> Nueva actividad
                </button>
            </div>
        </div>
    </div>

    <template x-if="gruposVisibles().length === 0">
        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-400">
            No hay actividades programadas para <span class="font-medium text-slate-500" x-text="fechaSeleccionadaLabel()"></span>.
            <br>Usa el calendario para navegar a un día con programación de ejemplo.
        </div>
    </template>

    {{-- areaExportable: lo que captura "Copiar como imagen" — solo el
         encabezado + la tabla, sin los botones de acción. --}}
    <div id="areaExportable" class="space-y-2" x-show="gruposVisibles().length > 0">
        <p class="text-sm font-semibold capitalize text-slate-700">Programación — <span x-text="fechaSeleccionadaLabel()"></span></p>
        <div class="compact-table-wrapper rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="table-scroll-container">
        <table class="preventive-table divide-y divide-slate-200 text-sm">
            <thead class="sticky-table-head bg-slate-50">
                <tr>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Empresa</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Equipo</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500" style="min-width:18rem">Actividad</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500" style="min-width:14rem">Personas</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Horas</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Jornada</th>
                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Resp.</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                <template x-for="item in filasConEncabezados()" :key="item.key">
                    <tr :class="item.tipo === 'grupo' ? 'bg-slate-800' : (item.transporte ? 'bg-slate-50 font-semibold hover:bg-slate-100' : 'hover:bg-slate-50')">
                        <template x-if="item.tipo === 'grupo'">
                            <td colspan="7" class="px-4 py-2">
                                <span class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-white">
                                    <i data-lucide="users" class="h-3.5 w-3.5"></i>
                                    <span x-text="item.grupo ? ('Grupo ' + item.grupo) : 'Sin grupo asignado'"></span>
                                </span>
                            </td>
                        </template>

                        <template x-if="item.tipo === 'fila'">
                            <td class="px-4 py-2.5 whitespace-nowrap" x-text="item.empresa"></td>
                        </template>
                        <template x-if="item.tipo === 'fila'">
                            <td class="px-4 py-2.5 whitespace-nowrap text-slate-600" x-text="item.equipo || '—'"></td>
                        </template>
                        <template x-if="item.tipo === 'fila'">
                            <td class="px-4 py-2.5" x-text="item.actividad"></td>
                        </template>
                        <template x-if="item.tipo === 'fila'">
                            <td class="px-4 py-2.5">
                                <template x-if="item.personas.length === 0">
                                    <span class="text-slate-400">—</span>
                                </template>
                                <span class="flex flex-wrap gap-1">
                                    <template x-for="p in item.personas" :key="p.nombre">
                                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-700">
                                            <span :class="p.tipo === 'P' ? 'bg-[#d55b20] text-white' : 'bg-slate-300 text-slate-700'" class="flex h-4 w-4 items-center justify-center rounded-full text-[10px] font-bold" x-text="p.tipo"></span>
                                            <span x-text="p.nombre"></span>
                                        </span>
                                    </template>
                                </span>
                            </td>
                        </template>
                        <template x-if="item.tipo === 'fila'">
                            <td class="px-4 py-2.5 whitespace-nowrap" x-text="item.horas ?? '—'"></td>
                        </template>
                        <template x-if="item.tipo === 'fila'">
                            <td class="px-4 py-2.5 whitespace-nowrap" x-text="item.jornada"></td>
                        </template>
                        <template x-if="item.tipo === 'fila'">
                            <td class="px-4 py-2.5 whitespace-nowrap text-slate-500" x-text="item.responsable || '—'"></td>
                        </template>
                    </tr>
                </template>
            </tbody>
        </table>
        </div>
        </div>
    </div>

    <p class="mt-4 text-xs text-slate-400">
        "P" = actividad primaria (solo una por persona/día) · "S" = secundaria (varias permitidas).
        Las filas en negrita son de transporte — se escriben como texto libre en Actividad, no son un tipo especial.
    </p>

    {{-- Toast de resultado de la exportacion --}}
    <div x-show="mensajeExport" x-cloak x-transition class="fixed bottom-4 right-4 z-50 max-w-xs rounded-xl bg-slate-900 px-4 py-3 text-sm text-white shadow-xl" x-text="mensajeExport"></div>

    {{-- Calendario para navegar entre fechas de la Programacion --}}
    <div x-show="calendarioAbierto" x-cloak class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 p-4" style="top:0;left:0;height:100vh;width:100vw;">
        <div @click.outside="calendarioAbierto = false" x-show="calendarioAbierto" x-transition class="w-full max-w-sm rounded-3xl bg-white p-6 shadow-xl">
            <div class="mb-1 flex items-center justify-between">
                <button type="button" @click="cambiarMes(-1)" class="flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50">
                    <i data-lucide="chevron-left" class="h-4 w-4"></i>
                </button>
                <h3 class="text-base font-bold capitalize text-slate-900" x-text="mesCalendarioLabel()"></h3>
                <button type="button" @click="cambiarMes(1)" class="flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50">
                    <i data-lucide="chevron-right" class="h-4 w-4"></i>
                </button>
            </div>
            <p class="mb-4 text-center text-xs text-slate-500">Selecciona un día para ver su programación.</p>

            <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                <template x-for="d in ['LUN','MAR','MIE','JUE','VIE','SAB','DOM']" :key="d">
                    <div x-text="d"></div>
                </template>
            </div>
            <div class="mt-1 grid grid-cols-7 gap-1">
                <template x-for="(celda, idx) in diasCalendario()" :key="idx">
                    <button
                        type="button"
                        :disabled="!celda"
                        @click="celda && seleccionarFecha(celda.fecha)"
                        class="flex h-11 items-center justify-center rounded-xl text-sm font-medium transition"
                        :class="!celda ? 'invisible' : (
                            celda.fecha === fechaSeleccionada
                                ? 'bg-slate-900 text-white'
                                : diasConDatos().has(celda.fecha)
                                    ? 'bg-[#d55b20] text-white hover:bg-[#b8481a]'
                                    : (celda.fecha === hoy ? 'text-slate-700 ring-2 ring-[#d55b20]' : 'bg-slate-50 text-slate-600 hover:bg-slate-100')
                        )"
                        x-text="celda ? celda.numero : ''"
                    ></button>
                </template>
            </div>

            <div class="mt-4 flex items-center justify-center gap-2 text-xs text-slate-500">
                <span class="h-3 w-3 rounded-full bg-[#d55b20]"></span> Días con programación registrada
            </div>
        </div>
    </div>

    {{-- Modal nueva actividad --}}
    <div x-show="modalAbierto" x-cloak class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 p-4" style="top:0;left:0;height:100vh;width:100vw;">
        <div @click.outside="modalAbierto = false" x-show="modalAbierto" x-transition class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-bold text-slate-900">Nueva actividad</h2>
                <button @click="modalAbierto = false" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100"><i data-lucide="x" class="h-5 w-5"></i></button>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Empresa</label>
                    <select x-model="form.empresa" @change="onEmpresaCambiada()" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <template x-for="e in empresas" :key="e.nombre">
                            <option :value="e.nombre" x-text="e.nombre + (e.defecto ? ' (por defecto)' : '')"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Área</label>
                    <select x-model="form.area" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option :value="null">Sin especificar</option>
                        <template x-for="a in areas" :key="a">
                            <option :value="a" x-text="a"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Grupo</label>
                    <input type="number" min="1" x-model.number="form.grupo" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Ej. 1 (opcional)">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Equipo (texto libre)</label>
                    <input type="text" x-model="form.equipo" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Ej. TP1 Trituradora">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Jornada</label>
                    <select x-model="form.jornada" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option>Diurno</option>
                        <option>Nocturno</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Responsable (supervisor)</label>
                    <select x-model="form.responsable" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option :value="null">Sin asignar</option>
                        <template x-for="emp in empleados.filter(e => e.categoria === 'Administrativos' && e.abreviatura)" :key="emp.nombre">
                            <option :value="emp.abreviatura" x-text="emp.nombre + ' (' + emp.abreviatura + ')'"></option>
                        </template>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-medium text-slate-600">Actividad (texto libre, versión programada)</label>
                    <input type="text" x-model="form.actividad" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Ej. Realizar cambio de cauchos">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Horas estimadas (opcional)</label>
                    <input type="number" step="0.5" x-model.number="form.horas" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Ej. 10">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Actividad primaria para</label>
                    <select x-model="form.primaria" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option :value="null">Ninguno (todas secundarias)</option>
                        <template x-for="emp in personasElegiblesParaPrimaria()" :key="emp.nombre">
                            <option :value="emp.nombre" x-text="emp.nombre"></option>
                        </template>
                    </select>
                    <p class="mt-1 text-[11px] text-slate-400">
                        Aparecen las personas ya seleccionadas abajo — todas cumplen la inducción de
                        <span x-text="form.empresa"></span> vigente, requisito para poder seleccionarlas.
                    </p>
                </div>
            </div>

            <div class="mt-5">
                <div class="mb-2 flex items-center justify-between">
                    <p class="text-xs font-medium text-slate-600">
                        Personas de la actividad —
                        <span x-text="form.personas.length"></span> seleccionada(s)
                    </p>
                    <p class="text-[11px] text-slate-400">
                        Solo se muestran las personas con la inducción de <span x-text="form.empresa"></span> vigente
                        — quien no cumple no aparece, ni siquiera como secundaria.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <template x-for="col in ['Campo', 'Administrativos']" :key="col">
                        <div>
                            <div class="mb-2 rounded-lg bg-slate-800 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white" x-text="col"></div>
                            <div class="max-h-56 space-y-1 overflow-y-auto rounded-lg border border-slate-200 p-2">
                                <template x-for="emp in empleados.filter(e => e.categoria === col && elegible(e))" :key="emp.nombre">
                                    <div class="flex items-center justify-between gap-2 rounded-lg px-2 py-1.5 hover:bg-slate-50">
                                        <label class="flex min-w-0 items-center gap-2 text-sm">
                                            <input type="checkbox" @change="togglePersona(emp)" :checked="personaSeleccionada(emp)">
                                            <span class="truncate" x-text="emp.nombre"></span>
                                            <span x-show="form.primaria === emp.nombre" class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-[#d55b20] text-[10px] font-bold text-white">P</span>
                                        </label>
                                        <span class="flex shrink-0 items-center gap-1">
                                            <template x-for="(estado, cert) in emp.certs" :key="cert">
                                                <span
                                                    class="flex h-5 w-5 items-center justify-center rounded-full"
                                                    :class="{
                                                        'bg-emerald-100': estado === 'vigente',
                                                        'bg-amber-100': estado === 'vencido',
                                                        'bg-slate-100': estado === 'no_posee',
                                                    }"
                                                    :title="cert + ': ' + (estado === 'vigente' ? 'vigente' : estado === 'vencido' ? 'vencido' : 'no registra este certificado')"
                                                >
                                                    <i
                                                        :data-lucide="estado === 'vigente' ? 'shield-check' : estado === 'vencido' ? 'shield-alert' : 'shield-off'"
                                                        class="h-3 w-3"
                                                        :class="estado === 'vigente' ? 'text-emerald-600' : estado === 'vencido' ? 'text-amber-600 animate-pulse' : 'text-slate-300'"
                                                    ></i>
                                                </span>
                                            </template>
                                        </span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button @click="modalAbierto = false" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancelar</button>
                <button @click="guardarActividad()" class="rounded-xl bg-[#d55b20] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#b8481a]">Guardar actividad</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- html2canvas-pro (no html2canvas a secas): este proyecto usa Tailwind 4,
     que emite colores oklch() por defecto, y el html2canvas original (2023)
     no sabe parsear esa funcion de color — falla al clonar el documento.
     html2canvas-pro es el fork mantenido que agrega ese soporte. --}}
<script src="https://cdn.jsdelivr.net/npm/html2canvas-pro@2.4.2/dist/html2canvas-pro.min.js"></script>
<script>
    const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    function pad2(n) { return String(n).padStart(2, '0'); }
    function fechaAString(year, month, day) { return `${year}-${pad2(month + 1)}-${pad2(day)}`; }
    function sumarDias(fechaStr, delta) {
        const d = new Date(fechaStr + 'T00:00:00');
        d.setDate(d.getDate() + delta);
        return fechaAString(d.getFullYear(), d.getMonth(), d.getDate());
    }

    function programacion({ actividades, empresas, empleados, areas, hoy }) {
        const emptyForm = () => {
            const porDefecto = empresas.find(e => e.defecto)?.nombre ?? empresas[0]?.nombre ?? null;
            return { empresa: porDefecto, area: null, grupo: null, equipo: '', jornada: 'Diurno', responsable: null, actividad: '', horas: null, personas: [], primaria: null };
        };
        const [hy, hm] = hoy.split('-').map(Number);

        return {
            actividades,
            empresas,
            empleados,
            areas,
            hoy,
            fechaSeleccionada: hoy,
            calendarioAbierto: false,
            mesCalendario: { year: hy, month: hm - 1 },
            modalAbierto: false,
            form: emptyForm(),
            emptyForm,
            abrirModal() {
                this.form = this.emptyForm();
                this.modalAbierto = true;
                // La lista de personas (con sus iconos de certificados) se
                // reevalua con el form nuevo — Lucide solo convierte los
                // <i data-lucide> que existen al momento de llamarlo, asi
                // que hay que volver a correrlo despues de que Alpine
                // termine de renderizar esta lista, o quedan como circulos
                // vacios.
                this.$nextTick(() => window.lucide?.createIcons());
            },
            exportando: false,
            mensajeExport: null,
            async exportarComoImagen() {
                if (this.exportando) return;
                if (!window.html2canvas) {
                    this.mostrarMensaje('No se pudo cargar la librería de exportación (revisa la conexión/consola).');
                    console.error('exportarComoImagen: window.html2canvas no está definido — el script del CDN no cargó.');
                    return;
                }
                this.exportando = true;
                const el = document.getElementById('areaExportable');
                // La tabla vive dentro de .table-scroll-container (overflow-x:
                // auto), que recorta y solo deja ver el ancho de pantalla — su
                // scrollWidth SI reporta el ancho real completo (a diferencia
                // del contenedor externo, que al clipear no "hereda" ese
                // ancho). onclone quita el recorte solo en el DOM clonado que
                // usa html2canvas para renderizar, sin tocar ni parpadear la
                // pagina real.
                const scrollBox = el.querySelector('.table-scroll-container');
                const fullWidth = scrollBox ? scrollBox.scrollWidth : el.scrollWidth;
                try {
                    const canvas = await window.html2canvas(el, {
                        backgroundColor: '#ffffff',
                        scale: 2,
                        windowWidth: fullWidth,
                        width: fullWidth,
                        onclone: (clonedDoc) => {
                            clonedDoc.getElementById('areaExportable')
                                ?.querySelector('.table-scroll-container')
                                ?.style.setProperty('overflow', 'visible');
                        },
                    });
                    canvas.toBlob(async (blob) => {
                        if (!blob) {
                            this.exportando = false;
                            this.mostrarMensaje('No se pudo generar la imagen (canvas vacío).');
                            console.error('exportarComoImagen: canvas.toBlob devolvió null.');
                            return;
                        }
                        await this.copiarOdescargar(blob);
                        this.exportando = false;
                    }, 'image/png');
                } catch (err) {
                    this.exportando = false;
                    this.mostrarMensaje('No se pudo generar la imagen: ' + (err?.message || err));
                    console.error('exportarComoImagen: html2canvas lanzó un error:', err);
                }
            },
            async copiarOdescargar(blob) {
                try {
                    if (!navigator.clipboard || !window.ClipboardItem) throw new Error('Este navegador no soporta escribir imágenes en el portapapeles');
                    await navigator.clipboard.write([new ClipboardItem({ 'image/png': blob })]);
                    this.mostrarMensaje('Imagen copiada — pégala directo en WhatsApp (Ctrl+V).');
                } catch (err) {
                    console.warn('copiarOdescargar: no se pudo copiar al portapapeles, se descarga en su lugar:', err);
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `programacion-${this.fechaSeleccionada}.png`;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    URL.revokeObjectURL(url);
                    this.mostrarMensaje('No se pudo copiar automáticamente: la imagen se descargó.');
                }
            },
            mostrarMensaje(texto) {
                this.mensajeExport = texto;
                setTimeout(() => { this.mensajeExport = null; }, 5000);
            },
            esEditable() {
                return this.fechaSeleccionada === this.hoy || this.fechaSeleccionada === sumarDias(this.hoy, -1);
            },
            fechaSeleccionadaLabel() {
                const [y, m, d] = this.fechaSeleccionada.split('-').map(Number);
                return `${d} de ${MESES[m - 1]} de ${y}`;
            },
            mesCalendarioLabel() {
                return `${MESES[this.mesCalendario.month]} ${this.mesCalendario.year}`;
            },
            cambiarMes(delta) {
                let { year, month } = this.mesCalendario;
                month += delta;
                if (month < 0) { month = 11; year--; }
                if (month > 11) { month = 0; year++; }
                this.mesCalendario = { year, month };
            },
            diasConDatos() {
                return new Set(this.actividades.map(a => a.fecha));
            },
            diasCalendario() {
                const { year, month } = this.mesCalendario;
                const primerDia = new Date(year, month, 1);
                const totalDias = new Date(year, month + 1, 0).getDate();
                const offset = (primerDia.getDay() + 6) % 7; // lunes = 0
                const celdas = [];
                for (let i = 0; i < offset; i++) celdas.push(null);
                for (let d = 1; d <= totalDias; d++) celdas.push({ numero: d, fecha: fechaAString(year, month, d) });
                return celdas;
            },
            abrirCalendario() {
                const [y, m] = this.fechaSeleccionada.split('-').map(Number);
                this.mesCalendario = { year: y, month: m - 1 };
                this.calendarioAbierto = true;
            },
            seleccionarFecha(fechaStr) {
                this.fechaSeleccionada = fechaStr;
                this.calendarioAbierto = false;
            },
            empresaEstado(emp) {
                return emp.empresas[this.form.empresa] ?? 'no_posee';
            },
            elegible(emp) {
                return this.empresaEstado(emp) === 'vigente';
            },
            personaSeleccionada(emp) {
                return this.form.personas.some(p => p.nombre === emp.nombre);
            },
            togglePersona(emp) {
                const idx = this.form.personas.findIndex(p => p.nombre === emp.nombre);
                if (idx >= 0) {
                    this.form.personas.splice(idx, 1);
                    if (this.form.primaria === emp.nombre) this.form.primaria = null;
                } else {
                    // Se agrega como secundaria por defecto; la elegibilidad de
                    // empresa solo importa si luego se marca como primaria
                    // (ver "Actividad primaria para" arriba).
                    this.form.personas.push({ nombre: emp.nombre, tipo: 'S' });
                }
            },
            personasElegiblesParaPrimaria() {
                return this.form.personas
                    .map(p => this.empleados.find(e => e.nombre === p.nombre))
                    .filter(emp => emp && this.elegible(emp));
            },
            onEmpresaCambiada() {
                // Al cambiar de empresa, quien ya estaba seleccionado pero no
                // tiene la induccion vigente de la nueva empresa deja de ser
                // elegible — se quita de la seleccion, no solo se oculta.
                this.form.personas = this.form.personas.filter(p => {
                    const emp = this.empleados.find(e => e.nombre === p.nombre);
                    return emp && this.elegible(emp);
                });
                if (this.form.primaria && !this.form.personas.some(p => p.nombre === this.form.primaria)) {
                    this.form.primaria = null;
                }
                this.$nextTick(() => window.lucide?.createIcons());
            },
            guardarActividad() {
                if (!this.form.actividad) { return; }
                const primariaValida = this.personasElegiblesParaPrimaria().some(e => e.nombre === this.form.primaria);
                const primaria = primariaValida ? this.form.primaria : null;
                const personas = this.form.personas.map(p => ({ nombre: p.nombre, tipo: p.nombre === primaria ? 'P' : 'S' }));
                this.actividades.push({ ...this.form, personas, fecha: this.fechaSeleccionada, transporte: false });
                this.modalAbierto = false;
                this.organizar();
            },
            actividadesDelDia() {
                return this.actividades.filter(a => a.fecha === this.fechaSeleccionada);
            },
            organizar() {
                this.actividades.sort((a, b) => (a.grupo ?? 9999) - (b.grupo ?? 9999));
            },
            gruposVisibles() {
                return [...new Set(this.actividadesDelDia().map(a => a.grupo))].sort((a, b) => (a ?? 9999) - (b ?? 9999));
            },
            filasDelGrupo(grupo) {
                return this.actividadesDelDia().filter(a => a.grupo === grupo);
            },
            filasConEncabezados() {
                const out = [];
                for (const grupo of this.gruposVisibles()) {
                    out.push({ tipo: 'grupo', grupo, key: 'g-' + grupo });
                    this.filasDelGrupo(grupo).forEach((row, idx) => {
                        out.push({ tipo: 'fila', key: 'f-' + grupo + '-' + idx, ...row });
                    });
                }
                return out;
            },
        };
    }
</script>
@endpush
