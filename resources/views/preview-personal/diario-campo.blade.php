@extends('preview-personal._layout')

@section('title', 'Diario de Campo')

@php
    // Datos de ejemplo tomados de las capturas reales de
    // "DIARIO DE CAMPO TRABAJOS MAN & TEC.xlsx" (1/09/2026). Sin persistencia real.
    $registros = [
        [
            'empresa' => 'ARGOS', 'equipo' => 'Pfister Puzolona', 'proceso' => 'Realizar cambio de cauchos',
            'programada' => 'Realizar cambio de cauchos',
            'ejecutada' => 'Se cortan cauchos y se fabrican ojos chinos en estos para su fácil calibración, estos cauchos quedan de 6" por orden de Diana Suan y Ricardo Castro, se hace el montaje de caucho en el perímetro de la canasta de la Pfister, se reorganizan los orificios de pisadores del caucho perimetral, no casaban de forma correcta, se le corta 5 mm a caucho perimetral interior.',
            'personas' => ['Eder', 'Cristian M', 'J Manuel'], 'horas' => 12, 'jornada' => 'Diurno', 'transporte' => false,
        ],
        [
            'empresa' => 'ARGOS', 'equipo' => 'TP1 Trituradora', 'proceso' => 'Realizar actividades varias en la TP1 y estar disponibles para atender cualquier evento',
            'programada' => 'Realizar actividades varias en la TP1 y estar disponibles para atender cualquier evento',
            'ejecutada' => 'En esta jornada se voltean martillos del rotor primario de TP1, se fabrica empaque de felpa para manjol de TP2.',
            'personas' => ['Herrera', 'Alex S'], 'horas' => 12, 'jornada' => 'Diurno', 'transporte' => false,
        ],
        [
            'empresa' => 'ARGOS', 'equipo' => 'Rioclaro', 'proceso' => '',
            'programada' => 'Conductor y otras actividades', 'ejecutada' => '',
            'personas' => ['Monsalve'], 'horas' => 12, 'jornada' => 'Diurno', 'transporte' => false,
        ],
        [
            'empresa' => 'ARGOS', 'equipo' => 'Rioclaro', 'proceso' => '',
            'programada' => 'Supervisor', 'ejecutada' => '',
            'personas' => ['Gerónimo'], 'horas' => 12, 'jornada' => 'Diurno', 'transporte' => false,
        ],
        [
            'empresa' => 'ARGOS', 'equipo' => '', 'proceso' => '',
            'programada' => 'Transporte Taxi', 'ejecutada' => '',
            'personas' => [], 'horas' => null, 'jornada' => 'Diurno', 'transporte' => true,
        ],
        [
            'empresa' => 'ARGOS', 'equipo' => 'Lubricación', 'proceso' => 'Realizar gamas de lubricación',
            'programada' => 'Realizar gamas de lubricación',
            'ejecutada' => 'Se realizó gamas de lubricación en la empacadora 3. Se realiza ruta de lubricación por la línea 1.',
            'personas' => ['Omar', 'Brayan C'], 'horas' => 10, 'jornada' => 'Diurno', 'transporte' => false,
        ],
        [
            'empresa' => 'ARGOS', 'equipo' => 'Banda A1L02', 'proceso' => 'Realizar alineación y pega en la banda y otras actividades',
            'programada' => 'Realizar alineación y pega en la banda y otras actividades',
            'ejecutada' => 'Se hace alineación de la banda A1L02, no se pudo realizar las pegas debido a que se prioriza la producción. En la banda A2J05 se le realiza inspección visual encontrando un corte pasante de 2 cm, enterado Oliver y este informa a Ramón Alzate.',
            'personas' => ['Alais', 'Brayam P', 'Diego S'], 'horas' => 10, 'jornada' => 'Diurno', 'transporte' => false,
        ],
        [
            'empresa' => 'CORONA', 'equipo' => 'Confiabilidad', 'proceso' => 'Preventivo y correctivo de bandas',
            'programada' => 'Preventivo y correctivo de bandas',
            'ejecutada' => 'Se realizan inspección en bandas de cemento, crudo y aditivos.',
            'personas' => ['Wilmar P', 'Valbuena', 'Norman', 'Espinosa 1', 'Ana C'], 'horas' => 9.5, 'jornada' => 'Diurno', 'transporte' => false,
        ],
        [
            'empresa' => 'CORONA', 'equipo' => '', 'proceso' => '',
            'programada' => 'Transporte Carro Alejandro', 'ejecutada' => '',
            'personas' => [], 'horas' => null, 'jornada' => 'Diurno', 'transporte' => true,
        ],
        [
            'empresa' => 'ARGOS', 'equipo' => 'TP1 Trituradora', 'proceso' => 'Realizar actividades varias en la TP1 y estar disponibles para atender cualquier evento',
            'programada' => 'Realizar actividades varias en la TP1 y estar disponibles para atender cualquier evento',
            'ejecutada' => 'Se terminan de preparar, cortar con arcair y montar, baldosas del esquinero lado izquierdo según flujo de la TP2. Fue necesario gestionar con los técnicos de turno de Argos, más soldadura de Arcair, y nos entregan 2 cajas, para culminar dicha actividad parte interna de la TP2. Además, se termina de preparar la felpa en manjol lado libre de la trituradora en mención, se deja ajustada la tapa del manjol para que el boxer pegante se adhiera mejor a la estructura metálica.',
            'personas' => ['Danilo', 'Néider'], 'horas' => 12, 'jornada' => 'Nocturno', 'transporte' => false,
        ],
        [
            'empresa' => 'ARGOS', 'equipo' => 'Taller San Luis', 'proceso' => '',
            'programada' => 'Actividad Varias',
            'ejecutada' => 'Se continúa con la fabricación del cajón para el soplador del silo 6.',
            'personas' => ['Pachon', 'Yesid', 'Espinosa 2'], 'horas' => 10, 'jornada' => 'Diurno', 'transporte' => false,
        ],
        [
            'empresa' => 'ARGOS', 'equipo' => 'Trabajo en casa', 'proceso' => '',
            'programada' => 'Actividad Varias',
            'ejecutada' => 'Se termina el informe de las gamas mecánicas de la empacadora 4 y se comienza el de las gamas mecánicas de la trituradora 2.',
            'personas' => ['Anderson'], 'horas' => 8, 'jornada' => 'Diurno', 'transporte' => false,
        ],
        [
            'empresa' => 'ARGOS', 'equipo' => '', 'proceso' => '',
            'programada' => 'Incapacidad', 'ejecutada' => '',
            'personas' => ['Jeison'], 'horas' => 7, 'jornada' => 'Diurno', 'transporte' => false,
        ],
        [
            'empresa' => 'ARGOS', 'equipo' => '', 'proceso' => '',
            'programada' => 'Descanso', 'ejecutada' => '',
            'personas' => ['Diego O', 'Brahian Q', 'Alan', 'Bonilla'], 'horas' => null, 'jornada' => 'Diurno', 'transporte' => false,
        ],
    ];
@endphp

@section('content')
<div class="mx-auto max-w-[1900px] space-y-4">
    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900 md:text-2xl">Diario de Campo</h1>
                <p class="mt-1 text-xs text-slate-500">
                    Mismo objeto que la Programación, enriquecido a medida que avanza su ciclo de vida.
                    "Horas" muestra siempre el valor final (corregida si existe, si no la reportada por el supervisor).
                </p>
            </div>
            <span class="inline-flex items-center rounded-xl bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-700">
                1/09/2026
            </span>
        </div>
    </div>

    <p class="mb-2 flex items-center gap-1 text-xs text-slate-400">
        <i data-lucide="move-horizontal" class="h-3.5 w-3.5"></i> Desliza horizontalmente para ver todas las columnas
    </p>

    <div class="compact-table-wrapper rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="scroll-container-visible">
        <table class="preventive-table divide-y divide-slate-200 text-sm">
            <thead class="sticky-table-head bg-slate-50">
                <tr>
                    <th class="whitespace-nowrap px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Empresa</th>
                    <th class="whitespace-nowrap px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Equipo</th>
                    <th class="px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500" style="min-width:20rem">Actividad</th>
                    <th class="px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500" style="min-width:11rem">Personas</th>
                    <th class="whitespace-nowrap px-3 py-2.5 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">N°</th>
                    <th class="whitespace-nowrap px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Horas</th>
                    <th class="whitespace-nowrap px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Jornada</th>
                    <th class="whitespace-nowrap px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Comentarios</th>
                    <th class="whitespace-nowrap bg-slate-100/70 px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400" style="width:5.5rem">ZCOM</th>
                    <th class="whitespace-nowrap bg-slate-100/70 px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400" style="width:5.5rem">Línea</th>
                    <th class="whitespace-nowrap bg-slate-100/70 px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400" style="width:6rem">OT SAP</th>
                    <th class="whitespace-nowrap bg-slate-100/70 px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400" style="width:7rem">Acta entrega</th>
                    <th class="whitespace-nowrap bg-slate-100/70 px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400" style="width:5.5rem">WE</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @foreach ($registros as $r)
                    <tr class="{{ $r['transporte'] ? 'bg-slate-50 font-semibold' : '' }} align-top hover:bg-slate-50">
                        <td class="whitespace-nowrap px-3 py-2.5">{{ $r['empresa'] }}</td>
                        <td class="whitespace-nowrap px-3 py-2.5 text-slate-600">{{ $r['equipo'] ?: '—' }}</td>
                        <td class="px-3 py-2.5">
                            <div class="font-medium text-slate-800">{{ $r['programada'] }}</div>
                            @if ($r['ejecutada'])
                                <div
                                    x-data="{ open: false }"
                                    class="mt-1 max-w-md text-xs leading-relaxed text-slate-500"
                                >
                                    <p :class="open ? '' : 'line-clamp-2'">{{ $r['ejecutada'] }}</p>
                                    <button @click="open = !open" class="mt-0.5 inline-flex items-center gap-0.5 font-medium text-[#d55b20] hover:underline" x-text="open ? '▲ ver menos' : '▼ ver detalle ejecutado'"></button>
                                </div>
                            @endif
                        </td>
                        <td class="px-3 py-2.5 text-slate-600">
                            {{ $r['personas'] ? implode(', ', $r['personas']) : '—' }}
                        </td>
                        <td class="px-3 py-2.5 text-center text-slate-500">{{ count($r['personas']) ?: '—' }}</td>
                        <td class="whitespace-nowrap px-3 py-2.5 font-medium">{{ $r['horas'] ?? '—' }}</td>
                        <td class="whitespace-nowrap px-3 py-2.5 text-slate-600">{{ $r['jornada'] }}</td>
                        <td class="px-3 py-2.5"><input type="text" class="w-full rounded border border-slate-200 px-1.5 py-1 text-xs" placeholder="—"></td>
                        <td class="bg-slate-100/40 px-2 py-2.5"><input type="text" class="w-full rounded border border-slate-200 bg-white px-1.5 py-1 text-xs" placeholder="—"></td>
                        <td class="bg-slate-100/40 px-2 py-2.5"><input type="text" class="w-full rounded border border-slate-200 bg-white px-1.5 py-1 text-xs" placeholder="—"></td>
                        <td class="bg-slate-100/40 px-2 py-2.5"><input type="text" class="w-full rounded border border-slate-200 bg-white px-1.5 py-1 text-xs" placeholder="—"></td>
                        <td class="bg-slate-100/40 px-2 py-2.5"><input type="text" class="w-full rounded border border-slate-200 bg-white px-1.5 py-1 text-xs" placeholder="—"></td>
                        <td class="bg-slate-100/40 px-2 py-2.5"><input type="text" class="w-full rounded border border-slate-200 bg-white px-1.5 py-1 text-xs" placeholder="—"></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>

    <p class="mt-4 text-xs text-slate-400">
        Las columnas grises (ZCOM / Línea / OT SAP / Acta entrega / WE) se muestran como texto libre a propósito —
        su significado exacto y si son fijos o configurables por empresa sigue pendiente de confirmar.
    </p>
</div>
@endsection
