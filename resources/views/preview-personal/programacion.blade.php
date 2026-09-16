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
    // mismos registros que Empleados/Bitacora.
    //
    // Correccion 2026-09-15: tras confirmar Mantec el Nivel 1 (seccion
    // 12.4 del documento), ya no hay bloqueo de seleccion por inducciones
    // de empresa ni certificados generales — ambos eran parte de la
    // gestion documental completa que no se contrato. Todos los empleados
    // activos aparecen en el buscador de personas (ver seccion 6.1/13.5).
    // 'horas_mes' viene del mismo archivo compartido que usa la Bitacora
    // (_horas-mes-data.php) para mostrar cuantas horas lleva acumuladas
    // cada persona este mes al seleccionarla.
    $empleadosCompartidos = include resource_path('views/preview-personal/_empleados-data.php');
    $horasMes = include resource_path('views/preview-personal/_horas-mes-data.php');
    $empleados = collect($empleadosCompartidos)
        ->filter(fn ($e) => $e['activo'])
        ->map(fn ($e) => [
            'nombre' => $e['nickname'],
            'categoria' => $e['categoria'],
            'abreviatura' => $e['abreviatura'],
            'horasMes' => $horasMes[$e['codigo_bitacora'] ?? $e['nickname']] ?? null,
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

    // Correccion 2026-09-14: primaria/secundaria es una propiedad de la
    // ACTIVIDAD completa (aplica a todas las personas asignadas en ella),
    // no una marca individual por persona dentro de la misma fila — ver
    // seccion 6 del documento. Las filas de "novedad" (transporte, descanso,
    // incapacidad, licencia, permiso, "actividad varias" sin equipo) van
    // como secundarias; el trabajo tecnico real va como primaria.
    $actividades = [
        ['fecha' => $fechaEjemplo, 'grupo' => 1, 'empresa' => 'ARGOS', 'equipo' => 'TP1 Trituradora', 'actividad' => 'Realizar actividades varias en la TP1 y estar disponibles para atender cualquier evento', 'tipo' => 'P', 'personas' => ['Pachón', 'Yesid'], 'horas' => 12, 'jornada' => 'Diurno', 'responsable' => 'Gr-J', 'transporte' => false],
        ['fecha' => $fechaEjemplo, 'grupo' => 1, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Conductor y otras actividades', 'tipo' => 'P', 'personas' => ['Monsalve'], 'horas' => 12, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaEjemplo, 'grupo' => 1, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Supervisor', 'tipo' => 'P', 'personas' => ['Gerónimo'], 'horas' => 12, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaEjemplo, 'grupo' => 1, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Transporte Taxi, Grupo 1. Argos', 'tipo' => 'S', 'personas' => [], 'horas' => null, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => true],

        ['fecha' => $fechaEjemplo, 'grupo' => 2, 'empresa' => 'ARGOS', 'equipo' => 'TP1 Camino', 'actividad' => 'Realizar cambio camino de molienda', 'tipo' => 'P', 'personas' => ['Cristian M', 'Anderson'], 'horas' => 10, 'jornada' => 'Diurno', 'responsable' => 'Gr-J', 'transporte' => false],
        ['fecha' => $fechaEjemplo, 'grupo' => 2, 'empresa' => 'ARGOS', 'equipo' => 'TP2 Rotor', 'actividad' => 'Realizar montaje Rotor Secundario', 'tipo' => 'P', 'personas' => ['Eder', 'J Manuel', 'Brayan Q', 'Alan'], 'horas' => 10, 'jornada' => 'Diurno', 'responsable' => 'Gr-J', 'transporte' => false],
        ['fecha' => $fechaEjemplo, 'grupo' => 2, 'empresa' => 'ARGOS', 'equipo' => 'Lubricación', 'actividad' => 'Realizar Gamas de Lubricación', 'tipo' => 'P', 'personas' => ['Omar', 'Brayan C'], 'horas' => 10, 'jornada' => 'Diurno', 'responsable' => 'Gr-J', 'transporte' => false],
        ['fecha' => $fechaEjemplo, 'grupo' => 2, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Herramientero y otras actividades', 'tipo' => 'P', 'personas' => ['Evelio'], 'horas' => 10, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaEjemplo, 'grupo' => 2, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'SST', 'tipo' => 'P', 'personas' => ['Nedy Johana'], 'horas' => 10, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaEjemplo, 'grupo' => 2, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Coordinador', 'tipo' => 'P', 'personas' => ['Conrado'], 'horas' => 10, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaEjemplo, 'grupo' => 2, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Transporte Aerovan 463, Grupo 2. Argos', 'tipo' => 'S', 'personas' => [], 'horas' => null, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => true],

        ['fecha' => $fechaEjemplo, 'grupo' => 3, 'empresa' => 'CORONA', 'equipo' => 'Confiabilidad', 'actividad' => 'Preventivo Bandas', 'tipo' => 'P', 'personas' => ['Wilmar', 'Espinosa 2', 'Herrera', 'Ana C', 'Norman'], 'horas' => 9.5, 'jornada' => 'Diurno', 'responsable' => 'F-C', 'transporte' => false],
        ['fecha' => $fechaEjemplo, 'grupo' => 3, 'empresa' => 'CORONA', 'equipo' => '', 'actividad' => 'SST', 'tipo' => 'P', 'personas' => ['Camila'], 'horas' => 9.5, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaEjemplo, 'grupo' => 3, 'empresa' => 'CORONA', 'equipo' => '', 'actividad' => 'Supervisor', 'tipo' => 'P', 'personas' => ['Fernando'], 'horas' => 9.5, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaEjemplo, 'grupo' => 3, 'empresa' => 'CORONA', 'equipo' => '', 'actividad' => 'Transporte Carro, Grupo 3. Corona', 'tipo' => 'S', 'personas' => [], 'horas' => null, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => true],

        ['fecha' => $fechaEjemplo, 'grupo' => 4, 'empresa' => 'ARGOS', 'equipo' => 'TP2 Rotor', 'actividad' => 'Realizar montaje Rotor Secundario', 'tipo' => 'P', 'personas' => ['Danilo', 'Brayan R', 'J David'], 'horas' => 12, 'jornada' => 'Nocturno', 'responsable' => 'Lm', 'transporte' => false],
        ['fecha' => $fechaEjemplo, 'grupo' => 4, 'empresa' => 'ARGOS', 'equipo' => 'TP1 Trituradora', 'actividad' => 'Realizar actividades varias en la TP1 y estar disponibles para atender cualquier evento', 'tipo' => 'P', 'personas' => ['Valbuena'], 'horas' => 12, 'jornada' => 'Nocturno', 'responsable' => 'Lm', 'transporte' => false],
        ['fecha' => $fechaEjemplo, 'grupo' => 4, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Transporte Taxi, Grupo 4. Argos', 'tipo' => 'S', 'personas' => [], 'horas' => null, 'jornada' => 'Nocturno', 'responsable' => '', 'transporte' => true],

        ['fecha' => $fechaEjemplo, 'grupo' => null, 'empresa' => 'ARGOS', 'equipo' => 'Taller San Luis', 'actividad' => 'Actividad Varias', 'tipo' => 'S', 'personas' => ['Espinosa 1', 'Diego S'], 'horas' => 10, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaEjemplo, 'grupo' => null, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Incapacidad', 'tipo' => 'S', 'personas' => ['Bonilla'], 'horas' => null, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaEjemplo, 'grupo' => null, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Descanso', 'tipo' => 'S', 'personas' => ['Diego O', 'Jeison', 'Alex Sierra', 'Brayam P'], 'horas' => null, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],

        // 4/09/2026 — segundo dia real capturado (mismo Excel), para
        // demostrar navegacion a otra fecha con datos distintos.
        ['fecha' => $fechaCuatro, 'grupo' => 1, 'empresa' => 'ARGOS', 'equipo' => 'TP1 Trituradora', 'actividad' => 'Realizar actividades varias en la TP1 y estar disponibles para atender cualquier evento', 'tipo' => 'P', 'personas' => ['Herrera', 'Alan'], 'horas' => 10.5, 'jornada' => 'Diurno', 'responsable' => 'B', 'transporte' => false],
        ['fecha' => $fechaCuatro, 'grupo' => 1, 'empresa' => 'ARGOS', 'equipo' => 'TP2 Cinta', 'actividad' => 'Montar ángulos', 'tipo' => 'P', 'personas' => ['Pachón', 'Yesid', 'Brayan Q'], 'horas' => 10.5, 'jornada' => 'Diurno', 'responsable' => 'B', 'transporte' => false],
        ['fecha' => $fechaCuatro, 'grupo' => 1, 'empresa' => 'ARGOS', 'equipo' => 'Banda U1U06', 'actividad' => 'Realizar empalme vulcanizado Avería', 'tipo' => 'P', 'personas' => ['Jeison', 'Alais', 'Alex Sierra', 'Brayan C', 'Alberto Zurbaran'], 'horas' => 10.5, 'jornada' => 'Diurno', 'responsable' => 'B', 'transporte' => false],
        ['fecha' => $fechaCuatro, 'grupo' => 1, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Conductor y otras actividades', 'tipo' => 'P', 'personas' => ['Monsalve'], 'horas' => 10.5, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaCuatro, 'grupo' => 1, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Supervisor', 'tipo' => 'P', 'personas' => ['Bonilla'], 'horas' => 10.5, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaCuatro, 'grupo' => 1, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Coordinador', 'tipo' => 'P', 'personas' => ['Conrado'], 'horas' => 10.5, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaCuatro, 'grupo' => 1, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Transporte Aerovans 463, Grupo 1. Argos', 'tipo' => 'S', 'personas' => [], 'horas' => null, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => true],

        ['fecha' => $fechaCuatro, 'grupo' => 4, 'empresa' => 'ARGOS', 'equipo' => 'TP1 Trituradora', 'actividad' => 'Realizar actividades varias en la TP1 y estar disponibles para atender cualquier evento', 'tipo' => 'P', 'personas' => ['Omar', 'Neider'], 'horas' => 12, 'jornada' => 'Nocturno', 'responsable' => 'F', 'transporte' => false],
        ['fecha' => $fechaCuatro, 'grupo' => 4, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Supervisor', 'tipo' => 'P', 'personas' => ['Fernando'], 'horas' => 12, 'jornada' => 'Nocturno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaCuatro, 'grupo' => 4, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Transporte Taxi, Grupo 4. Argos', 'tipo' => 'S', 'personas' => [], 'horas' => null, 'jornada' => 'Nocturno', 'responsable' => '', 'transporte' => true],

        ['fecha' => $fechaCuatro, 'grupo' => 3, 'empresa' => 'CORONA', 'equipo' => 'Elevador', 'actividad' => 'Realizar Procedimientos', 'tipo' => 'P', 'personas' => ['Wilmar'], 'horas' => 6, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaCuatro, 'grupo' => 3, 'empresa' => 'CORONA', 'equipo' => '', 'actividad' => 'SST', 'tipo' => 'P', 'personas' => ['Sara'], 'horas' => 6, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaCuatro, 'grupo' => 3, 'empresa' => 'CORONA', 'equipo' => '', 'actividad' => 'Transporte, Grupo 3. Corona', 'tipo' => 'S', 'personas' => [], 'horas' => null, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => true],

        ['fecha' => $fechaCuatro, 'grupo' => null, 'empresa' => 'ARGOS', 'equipo' => 'Trabajo en Casa', 'actividad' => 'Terminar Informes de Gamas', 'tipo' => 'S', 'personas' => ['Anderson'], 'horas' => 7, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaCuatro, 'grupo' => null, 'empresa' => 'ARGOS', 'equipo' => 'Taller San Luis', 'actividad' => 'Actividad Varias', 'tipo' => 'S', 'personas' => ['Espinosa 2', 'Diego S', 'Espinosa 1'], 'horas' => 8, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaCuatro, 'grupo' => null, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Licencia', 'tipo' => 'S', 'personas' => ['Alejandro'], 'horas' => null, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaCuatro, 'grupo' => null, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Permiso cita odontológica', 'tipo' => 'S', 'personas' => ['Brayam P'], 'horas' => null, 'jornada' => 'Diurno', 'responsable' => '', 'transporte' => false],
        ['fecha' => $fechaCuatro, 'grupo' => null, 'empresa' => 'ARGOS', 'equipo' => '', 'actividad' => 'Descanso', 'tipo' => 'S', 'personas' => [
            'Diego O', 'J Manuel', 'Luis Meza', 'Wilmar Guzman',
            'Danilo', 'J David', 'Brayan R', 'Valbuena',
            'Ana C', 'Norman', 'Camila', 'Eder',
            'Cristian M', 'Gerónimo', 'Evelio', 'Nedy Johana',
            'Wilmar Guzman',
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
                    @click="exportarComoImagen(fechaSeleccionada)"
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
                    <th class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Empresa</th>
                    <th class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Equipo</th>
                    <th class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500" style="min-width:13rem">Actividad</th>
                    <th class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500" style="min-width:9rem">Personas</th>
                    <th class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Horas</th>
                    <th class="px-3 py-2 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500" title="Jornada">Jorn.</th>
                    <th class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Resp.</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                <template x-for="item in filasConEncabezados()" :key="item.key">
                    <tr :class="item.rowType === 'grupo' ? 'bg-slate-800' : (item.transporte ? 'bg-slate-50 font-semibold hover:bg-slate-100' : 'hover:bg-slate-50')">
                        <template x-if="item.rowType === 'grupo'">
                            <td colspan="7" class="px-3 py-1.5">
                                <span class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-white">
                                    <i data-lucide="users" class="h-3.5 w-3.5"></i>
                                    <span x-text="item.grupo ? ('Grupo ' + item.grupo) : 'Sin grupo asignado'"></span>
                                </span>
                            </td>
                        </template>

                        <template x-if="item.rowType === 'fila'">
                            <td class="px-3 py-2 whitespace-nowrap" x-text="item.empresa"></td>
                        </template>
                        <template x-if="item.rowType === 'fila'">
                            <td class="px-3 py-2 whitespace-nowrap text-slate-600" x-text="item.equipo || '—'"></td>
                        </template>
                        <template x-if="item.rowType === 'fila'">
                            <td class="px-3 py-2">
                                <span
                                    :class="item.tipo === 'P' ? 'bg-[#d55b20] text-white' : 'bg-slate-300 text-slate-700'"
                                    class="mr-1.5 inline-flex h-4 w-4 items-center justify-center rounded-full align-[-2px] text-[10px] font-bold"
                                    :title="item.tipo === 'P' ? 'Actividad primaria' : 'Actividad secundaria'"
                                    x-text="item.tipo"
                                ></span>
                                <span x-text="item.actividad"></span>
                            </td>
                        </template>
                        <template x-if="item.rowType === 'fila'">
                            <td class="px-3 py-2 text-slate-600" x-text="item.personas.length ? item.personas.join(', ') : '—'"></td>
                        </template>
                        <template x-if="item.rowType === 'fila'">
                            <td class="px-3 py-2 whitespace-nowrap" x-text="item.horas ?? '—'"></td>
                        </template>
                        <template x-if="item.rowType === 'fila'">
                            <td class="px-3 py-2 whitespace-nowrap text-center text-slate-600" :title="item.jornada" x-text="item.jornada === 'Nocturno' ? 'N' : 'D'"></td>
                        </template>
                        <template x-if="item.rowType === 'fila'">
                            <td class="px-3 py-2 whitespace-nowrap text-slate-500" x-text="item.responsable || '—'"></td>
                        </template>
                    </tr>
                </template>
            </tbody>
        </table>
        </div>
        </div>
    </div>

    <p class="mt-4 text-xs text-slate-400">
        "P" / "S" junto a la Actividad = la actividad completa es primaria o secundaria (aplica a todas las personas
        de esa fila) · una persona solo puede estar en una actividad primaria por día, pero en varias secundarias.
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
                    <select x-model="form.empresa" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
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
                    <label class="mb-1 block text-xs font-medium text-slate-600">Tipo de actividad</label>
                    <select x-model="form.tipo" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="P">Primaria</option>
                        <option value="S">Secundaria</option>
                    </select>
                    <p class="mt-1 text-[11px] text-slate-400">
                        Aplica a todas las personas de esta actividad. Cada persona solo puede tener
                        una actividad primaria por día (no validado en este mockup).
                    </p>
                </div>
            </div>

            <div class="mt-5">
                <p class="mb-2 text-xs font-medium text-slate-600">
                    Personas de la actividad —
                    <span x-text="form.personas.length"></span> seleccionada(s)
                </p>

                {{-- Chips de personas ya seleccionadas --}}
                <div class="mb-2 flex flex-wrap gap-1.5" x-show="form.personas.length > 0">
                    <template x-for="nombre in form.personas" :key="nombre">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 py-1 pl-3 pr-1.5 text-xs text-slate-700">
                            <span x-text="nombre"></span>
                            <span class="text-slate-400" x-text="horasMesTexto(nombre)"></span>
                            <button type="button" @click="quitarPersona(nombre)" class="flex h-4 w-4 items-center justify-center rounded-full text-slate-400 hover:bg-slate-200 hover:text-red-500">
                                <i data-lucide="x" class="h-3 w-3"></i>
                            </button>
                        </span>
                    </template>
                </div>

                {{-- Combobox: buscar y agregar personas, agrupadas por
                     categoria. Reemplaza la grilla de checkboxes anterior —
                     con todos los empleados visibles (sin filtro de
                     elegibilidad, ver 6.1/13.5) una lista fija ya no era
                     practica. Muestra horas acumuladas del mes junto a cada
                     nombre (mismo dato de _horas-mes-data.php que usa la
                     Bitacora). --}}
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <div class="relative">
                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input
                            type="text"
                            x-model="busquedaPersona"
                            @focus="open = true"
                            placeholder="Buscar persona por nombre..."
                            class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm"
                        >
                    </div>
                    <div x-show="open" x-cloak x-transition class="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-xl">
                        <template x-for="col in ['Campo', 'Administrativos']" :key="col">
                            <template x-if="personasFiltradas(col).length > 0">
                                <div>
                                    <div class="sticky top-0 bg-slate-50 px-3 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400" x-text="col"></div>
                                    <template x-for="emp in personasFiltradas(col)" :key="emp.nombre">
                                        <button
                                            type="button"
                                            @click="togglePersona(emp); busquedaPersona = ''"
                                            class="flex w-full items-center justify-between gap-2 px-3 py-1.5 text-left text-sm hover:bg-slate-50"
                                        >
                                            <span class="flex min-w-0 items-center gap-2">
                                                <input type="checkbox" tabindex="-1" :checked="personaSeleccionada(emp)" class="pointer-events-none">
                                                <span class="truncate" x-text="emp.nombre"></span>
                                            </span>
                                            <span class="shrink-0 text-xs text-slate-400" x-text="horasMesTexto(emp.nombre)"></span>
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </template>
                        <p class="px-3 py-3 text-center text-xs text-slate-400" x-show="personasFiltradas('Campo').length === 0 && personasFiltradas('Administrativos').length === 0">
                            Sin resultados.
                        </p>
                    </div>
                </div>
                <p class="mt-1 text-[11px] text-slate-400">
                    "Horas este mes" es lo acumulado en la Bitácora hasta ahora — referencia para no
                    sobrecargar a quien ya lleva muchas horas, no bloquea la selección.
                </p>
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
{{-- html2canvas-pro y el mixin exportarComoImagen()/copiarOdescargar() ya se
     cargan una sola vez en _layout.blade.php (compartidos con Diario de
     Campo y Empleados) — ver ese archivo para el detalle de por que hace
     falta inlinear el CSS en el clon. --}}
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
            return { empresa: porDefecto, area: null, grupo: null, equipo: '', jornada: 'Diurno', responsable: null, actividad: '', horas: null, personas: [], tipo: 'P' };
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
            ...imageExporterMixin('programacion'),
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
            busquedaPersona: '',
            personaSeleccionada(emp) {
                return this.form.personas.includes(emp.nombre);
            },
            togglePersona(emp) {
                const idx = this.form.personas.indexOf(emp.nombre);
                if (idx >= 0) {
                    this.form.personas.splice(idx, 1);
                } else {
                    this.form.personas.push(emp.nombre);
                }
            },
            quitarPersona(nombre) {
                const idx = this.form.personas.indexOf(nombre);
                if (idx >= 0) this.form.personas.splice(idx, 1);
            },
            personasFiltradas(categoria) {
                const q = this.busquedaPersona.trim().toLowerCase();
                return this.empleados.filter(e => e.categoria === categoria && (!q || e.nombre.toLowerCase().includes(q)));
            },
            horasMesTexto(nombre) {
                const emp = this.empleados.find(e => e.nombre === nombre);
                return emp && emp.horasMes !== null ? `· ${emp.horasMes}h este mes` : '';
            },
            guardarActividad() {
                if (!this.form.actividad) { return; }
                this.actividades.push({ ...this.form, fecha: this.fechaSeleccionada, transporte: false });
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
                    out.push({ rowType: 'grupo', grupo, key: 'g-' + grupo });
                    this.filasDelGrupo(grupo).forEach((row, idx) => {
                        out.push({ rowType: 'fila', key: 'f-' + grupo + '-' + idx, ...row });
                    });
                }
                return out;
            },
        };
    }
</script>
@endpush
