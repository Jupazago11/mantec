@extends('layouts.personal')

@section('title', 'Diario de Campo')
@section('main_class', 'dc-main-compacto')

@use('App\Services\FieldDiary\FieldDiaryFullView')
@use('Carbon\Carbon')

{{-- Diario de Campo — una sola vista (pedido 2026-09-28) con dos modos:
     "Por dia" (por defecto hoy, sin columna Fecha, flechas + calendario) y
     "Todas las fechas" (?vista=todas). Misma tabla, filtros tipo Excel por
     columna, paginacion de 100 y edicion en linea en ambos modos. Datos:
     FieldDiaryController::index() + FieldDiaryFullView. --}}

@php
    $fechaDesde = $filtrosActivos['fecha']['from'];
    $fechaHasta = $filtrosActivos['fecha']['to'];

    $filtroActivo = fn (string $clave) => $clave === 'fecha'
        ? ($fechaDesde !== null || $fechaHasta !== null)
        : $filtrosActivos[$clave] !== [];

    // Chips de "Filtros activos": etiqueta + resumen de lo seleccionado.
    $chips = [];
    foreach (array_keys($columnas) as $clave) {
        if (! $filtroActivo($clave)) {
            continue;
        }
        if ($clave === 'fecha') {
            $resumen = match (true) {
                $fechaDesde !== null && $fechaHasta !== null => Carbon::parse($fechaDesde)->format('d/m/Y').' – '.Carbon::parse($fechaHasta)->format('d/m/Y'),
                $fechaDesde !== null => 'desde '.Carbon::parse($fechaDesde)->format('d/m/Y'),
                default => 'hasta '.Carbon::parse($fechaHasta)->format('d/m/Y'),
            };
        } else {
            $valores = array_map(fn ($v) => $v === FieldDiaryFullView::EMPTY_VALUE ? 'Sin valor' : $v, $filtrosActivos[$clave]);
            $resumen = implode(', ', array_map(fn ($v) => \Illuminate\Support\Str::limit($v, 28), array_slice($valores, 0, 3)))
                .(count($valores) > 3 ? ' +'.(count($valores) - 3) : '');
        }
        $chips[] = ['clave' => $clave, 'etiqueta' => $columnas[$clave], 'resumen' => $resumen];
    }

    $paginaInicio = max(1, $filas->currentPage() - 2);
    $paginaFin = min($filas->lastPage(), $filas->currentPage() + 2);

    $celdasLargas = ['actividad', 'ejecutada', 'comentarios', 'personas'];
    $columnasGrises = ['zcom', 'linea', 'ot_sap', 'acta', 'we'];

    // Columnas editables con clic (pedido 2026-09-28) => [campo en
    // activities, multilinea]. "Actividad ejecutada" y "Comentarios" son el
    // mismo campo (comments, seccion 14.23). Personas/N° vienen de
    // Programacion y no se editan aqui; Empresa/Jornada/Fecha tampoco.
    // "Horas" escribe corrected_hours (correccion administrativa, un valor
    // para toda la actividad), igual que la vista diaria anterior.
    $editables = [
        'equipo' => ['team', false],
        'proceso' => ['process', false],
        'actividad' => ['description', true],
        'ejecutada' => ['comments', true],
        'horas' => ['corrected_hours', false],
        'comentarios' => ['comments', true],
        'zcom' => ['zcom', false],
        'linea' => ['line_code', false],
        'ot_sap' => ['ot_sap', false],
        'acta' => ['acta_entrega', false],
        'we' => ['we_code', false],
    ];

    $diaLabel = $dia ? Carbon::parse($dia)->translatedFormat('d \d\e F \d\e Y') : null;
@endphp

@section('content')
<style>
    {{-- Scrollbar siempre visible (seccion 14.23). --}}
    .scroll-container-visible {
        overflow: auto;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f1f5f9;
    }
    .scroll-container-visible::-webkit-scrollbar { width: 10px; height: 10px; }
    .scroll-container-visible::-webkit-scrollbar-track { background: #f1f5f9; }
    .scroll-container-visible::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
    .scroll-container-visible::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

    {{-- Pantalla completa sin desbordar: menos margen que el resto del
         modulo (main_class), la pagina llena el alto disponible y es la
         TABLA la que hace scroll (vertical y horizontal), con el
         encabezado de columnas fijo. En pantallas muy bajas la tarjeta no
         baja de 16rem y el <main> hace scroll. --}}
    .dc-main-compacto { padding: 0.75rem; display: flex; flex-direction: column; }
    .dc-pagina { flex: 1 1 auto; min-height: 0; display: flex; flex-direction: column; gap: 0.5rem; width: 100%; }
    .dc-tabla-card { flex: 1 1 auto; min-height: 16rem; display: flex; flex-direction: column; overflow: hidden; }
    .dc-tabla-card .scroll-container-visible { flex: 1 1 auto; min-height: 0; }

    {{-- Paginacion: mismos estilos que reportes preventivos, un poco mas
         compactos para caber en la franja superior. --}}
    .custom-pagination { display: flex; align-items: center; gap: 0.3rem; flex-wrap: wrap; }
    .custom-pagination .page-btn,
    .custom-pagination .page-current {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 1.75rem; height: 1.75rem; padding: 0 0.5rem; border-radius: 0.7rem;
        border: 1px solid rgb(226 232 240); font-size: 0.78rem; font-weight: 600;
        background: white; color: rgb(71 85 105); transition: 0.18s ease;
    }
    .custom-pagination .page-btn:hover { background: rgb(248 250 252); border-color: rgb(203 213 225); color: rgb(30 41 59); }
    .custom-pagination .page-current { background: rgb(241 245 249); color: rgb(15 23 42); border-color: rgb(203 213 225); }

    {{-- Selector de modo "Por dia" / "Todas las fechas". --}}
    .dc-modo { display: inline-flex; border: 1px solid rgb(226 232 240); border-radius: 0.75rem; padding: 0.125rem; background: rgb(248 250 252); }
    .dc-modo button { border-radius: 0.6rem; padding: 0.25rem 0.75rem; font-size: 0.75rem; font-weight: 600; color: rgb(100 116 139); }
    .dc-modo button:hover { color: rgb(30 41 59); }
    .dc-modo button.is-active { background: white; color: #d55b20; box-shadow: 0 1px 2px rgb(15 23 42 / 0.08); }
    .dc-dia-btn { display: inline-flex; align-items: center; justify-content: center; width: 1.75rem; height: 1.75rem; border-radius: 0.6rem; color: rgb(100 116 139); }
    .dc-dia-btn:hover { background: rgb(241 245 249); color: rgb(30 41 59); }

    {{-- Encabezado de columna = boton de filtro (como Excel). Activo en
         naranja de marca con icono de embudo. --}}
    .dc-th-btn {
        display: inline-flex; align-items: center; gap: 0.25rem;
        text-transform: uppercase; letter-spacing: 0.05em;
        font-size: 11px; font-weight: 600; color: rgb(100 116 139);
        white-space: nowrap; cursor: pointer; transition: color 0.15s ease;
    }
    .dc-th-btn:hover { color: rgb(30 41 59); }
    .dc-th-btn.is-active { color: #d55b20; }

    .dc-celda-larga { max-width: 20rem; white-space: normal; overflow-wrap: anywhere; }
    .dc-nuevo-dia > td { border-top: 2px solid rgb(203 213 225); }
    .dc-chip-x { display: inline-flex; align-items: center; justify-content: center; width: 1.1rem; height: 1.1rem; border-radius: 9999px; color: rgb(148 163 184); }
    .dc-chip-x:hover { background: rgb(254 226 226); color: rgb(220 38 38); }
    .dc-sin-valor { font-style: italic; color: rgb(148 163 184); }
    .dc-metrica-principal { font-size: 1rem; line-height: 1.25rem; font-weight: 800; }

    {{-- Popover de filtro: nunca mas alto que el espacio libre (max-height
         lo calcula posicionarFiltro() en JS); solo la lista se encoge. --}}
    .dc-popover { display: flex; flex-direction: column; overflow: hidden; }
    .dc-popover-cuerpo { display: flex; flex-direction: column; gap: 0.5rem; flex: 1 1 auto; min-height: 0; }
    .dc-popover-lista { flex: 1 1 auto; min-height: 2.5rem; max-height: 16rem; overflow-y: auto; padding-right: 0.25rem; }

    {{-- Edicion en linea (seccion 14.22, mismo patron que reportes
         preventivos). max-width + overflow-wrap: sin esto un texto largo
         arrastra el ancho de toda la columna. --}}
    .inline-edit-trigger {
        cursor: pointer; border-radius: 0.65rem; padding: 0.2rem 0.35rem;
        transition: background-color 0.18s ease; display: inline-block; min-width: 36px;
        max-width: 20rem; white-space: normal; overflow-wrap: break-word; word-break: break-word;
    }
    .inline-edit-trigger:hover { background: rgb(241 245 249); }
    .inline-edit-box { display: flex; flex-direction: column; gap: 0.35rem; min-width: 12rem; }
    .inline-edit-input,
    .inline-edit-textarea {
        width: 100%; min-width: 70px; border: 1px solid rgb(203 213 225); border-radius: 0.65rem;
        padding: 0.35rem 0.55rem; font-size: 0.82rem; line-height: 1.2rem; color: rgb(51 65 85); background: white;
    }
    .inline-edit-textarea { min-height: 4.5rem; resize: vertical; font-family: inherit; }
    .inline-edit-input:focus,
    .inline-edit-textarea:focus { outline: none; border-color: #d94d33; box-shadow: 0 0 0 3px rgba(217, 77, 51, 0.15); }
    .inline-edit-actions { display: flex; align-items: center; gap: 0.35rem; }
    .inline-edit-btn {
        border: 0; border-radius: 0.55rem; width: 26px; height: 26px; display: inline-flex;
        align-items: center; justify-content: center; font-size: 0.82rem; font-weight: 700; transition: 0.18s ease; cursor: pointer;
    }
    .inline-edit-btn-cancel { background: rgb(248 250 252); color: rgb(71 85 105); border: 1px solid rgb(226 232 240); }
    .inline-edit-btn-cancel:hover { background: rgb(241 245 249); }
    .inline-edit-btn-save { background: rgb(22 163 74); color: white; }
    .inline-edit-btn-save:hover { background: rgb(21 128 61); }
    .inline-edit-loading { opacity: 0.6; pointer-events: none; }
    {{-- Destello verde en las celdas actualizadas sin recargar. --}}
    .dc-guardado .inline-edit-trigger { animation: dc-guardado 1.2s ease-out; }
    @keyframes dc-guardado { from { background: rgb(220 252 231); } to { background: transparent; } }
</style>

<div
    class="dc-pagina"
    x-data="diarioCampoPage({
        baseUrl: @js(route('personal.diario-campo.index')),
        modoTodas: @js($modoTodas),
        dia: @js($dia),
        {{-- Fecha real de "hoy" calculada en el servidor (America/Bogota),
             no con new Date() del navegador (seccion 14.25). --}}
        hoyReal: @js(today()->toDateString()),
        diasConDatosUrlBase: @js(route('personal.programacion.dias-con-datos')),
        filtros: @js($filtrosActivos),
        opciones: @js($opciones),
        etiquetas: @js($columnas),
    })"
    @keydown.escape.window="cerrarFiltro(); calendarioAbierto = false"
>
    {{-- Una sola franja: titulo + modo + dia + filtros activos + totales +
         paginacion. --}}
    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 shadow-sm">
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-lg font-bold tracking-tight text-slate-900">Diario de Campo</h1>

            <div class="dc-modo" role="group" aria-label="Modo de vista">
                <button type="button" @click="irAPorDia()" class="{{ $modoTodas ? '' : 'is-active' }}">Por día</button>
                <button type="button" @click="irATodas()" class="{{ $modoTodas ? 'is-active' : '' }}">Todas las fechas</button>
            </div>

            @unless ($modoTodas)
                <div class="inline-flex items-center gap-1">
                    <button type="button" @click="moverDia(-1)" class="dc-dia-btn" title="Día anterior" aria-label="Día anterior">
                        <i data-lucide="chevron-left" class="h-4 w-4"></i>
                    </button>
                    <button
                        type="button"
                        @click="abrirCalendario()"
                        class="inline-flex items-center gap-1.5 rounded-md bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200"
                        title="Elegir día"
                    >
                        <i data-lucide="calendar-days" class="h-3.5 w-3.5"></i>
                        <span>{{ $diaLabel }}</span>
                    </button>
                    <button type="button" @click="moverDia(1)" class="dc-dia-btn" title="Día siguiente" aria-label="Día siguiente">
                        <i data-lucide="chevron-right" class="h-4 w-4"></i>
                    </button>
                </div>
            @endunless

            <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
                @forelse ($chips as $chip)
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 py-1 pl-3 pr-1.5 text-xs text-slate-700">
                        <button type="button" @click.stop="abrirFiltro(@js($chip['clave']), $el)" class="text-left" title="Editar este filtro">
                            <span class="font-semibold">{{ $chip['etiqueta'] }}:</span> {{ $chip['resumen'] }}
                        </button>
                        <button type="button" @click="quitarFiltro(@js($chip['clave']))" class="dc-chip-x" title="Quitar este filtro" aria-label="Quitar filtro {{ $chip['etiqueta'] }}">
                            <i data-lucide="x" class="h-3 w-3"></i>
                        </button>
                    </span>
                @empty
                    <span class="text-xs text-slate-400">Sin filtros</span>
                @endforelse
                @if (count($chips) > 0)
                    <button
                        type="button"
                        @click="limpiarTodo()"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                    >
                        <i data-lucide="filter-x" class="h-3.5 w-3.5"></i>
                        <span>Limpiar filtros</span>
                    </button>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-2">
                {{-- Filtrado primero y resaltado, Total despues. --}}
                <span class="inline-flex items-center gap-1.5 rounded-xl bg-[#d55b20]/10 px-3 py-1 text-[#d55b20]" title="Filas que cumplen los filtros actuales">
                    <span class="text-[11px] font-semibold">Filtrado:</span>
                    <span class="dc-metrica-principal">{{ number_format($totalFiltrado) }}</span>
                </span>
                <span class="inline-flex items-center rounded-xl bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-500" title="{{ $modoTodas ? 'Todas las filas del Diario de Campo, sin filtros' : 'Todas las filas de este día, sin filtros' }}">
                    Total: {{ number_format($totalGenerado) }}
                </span>
                @include('personal.diario-campo._paginacion')
            </div>
        </div>
    </div>

    <div class="compact-table-wrapper dc-tabla-card rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="scroll-container-visible" id="diarioTablaScroll">
            <table class="preventive-table divide-y divide-slate-200 text-sm">
                <thead class="sticky-table-head bg-slate-50">
                    <tr>
                        @foreach ($columnas as $clave => $etiqueta)
                            <th class="px-3 py-2.5 text-left {{ in_array($clave, $columnasGrises, true) ? 'bg-slate-100/70' : '' }}">
                                <button
                                    type="button"
                                    @click.stop="abrirFiltro(@js($clave), $el)"
                                    class="dc-th-btn {{ $filtroActivo($clave) ? 'is-active' : '' }}"
                                    title="Filtrar por {{ $etiqueta }}"
                                >
                                    <span>{{ $etiqueta }}</span>
                                    <i data-lucide="{{ $filtroActivo($clave) ? 'filter' : 'chevron-down' }}" class="h-3 w-3"></i>
                                </button>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @php $fechaAnterior = null; @endphp
                    @forelse ($filas as $fila)
                        <tr class="align-top hover:bg-slate-50 {{ $fechaAnterior !== null && $fechaAnterior !== $fila['fecha'] ? 'dc-nuevo-dia' : '' }}">
                            @foreach (array_keys($columnas) as $clave)
                                @php
                                    $valor = match ($clave) {
                                        'fecha' => null,
                                        'personas' => implode(', ', $fila['personas']),
                                        'n_personas' => $fila['n_personas'] > 0 ? $fila['n_personas'] : '',
                                        'horas' => FieldDiaryFullView::formatHours($fila['horas']),
                                        default => $fila[$clave],
                                    };
                                    $vacio = $valor === null || trim((string) $valor) === '';
                                    $editable = $puedeEditar && isset($editables[$clave]);
                                @endphp
                                @if ($clave === 'fecha')
                                    <td class="whitespace-nowrap px-3 py-2.5 font-medium text-slate-700">
                                        <a
                                            href="{{ route('personal.diario-campo.index', ['date' => $fila['fecha']]) }}"
                                            @click.prevent="irADia(@js($fila['fecha']))"
                                            class="hover:text-[#d55b20] hover:underline"
                                            title="Ver solo este día"
                                        >{{ Carbon::parse($fila['fecha'])->format('d/m/Y') }}</a>
                                    </td>
                                @else
                                    <td class="px-3 py-2.5 {{ $clave === 'actividad' ? 'font-medium text-slate-800' : 'text-slate-600' }} {{ $clave === 'n_personas' ? 'text-center' : '' }} {{ in_array($clave, $columnasGrises, true) ? 'bg-slate-100/40' : '' }} {{ in_array($clave, ['empresa', 'jornada'], true) ? 'whitespace-nowrap' : '' }}">
                                        @if ($editable)
                                            <div class="inline-editable" data-activity-id="{{ $fila['activity_id'] }}" data-field="{{ $editables[$clave][0] }}" data-value="{{ $vacio ? '' : $valor }}" @if ($editables[$clave][1]) data-multiline="1" @endif>
                                                <span class="inline-edit-trigger">{{ $vacio ? '—' : $valor }}</span>
                                            </div>
                                        @elseif (in_array($clave, $celdasLargas, true))
                                            <div class="dc-celda-larga">{{ $vacio ? '—' : $valor }}</div>
                                        @else
                                            {{ $vacio ? '—' : $valor }}
                                        @endif
                                    </td>
                                @endif
                            @endforeach
                        </tr>
                        @php $fechaAnterior = $fila['fecha']; @endphp
                    @empty
                        <tr>
                            <td colspan="{{ count($columnas) }}" class="px-6 py-14 text-center text-sm text-slate-400">
                                @if (count($chips) > 0)
                                    Ningún registro coincide con los filtros actuales.
                                @elseif ($modoTodas)
                                    Todavía no hay actividades programadas.
                                @else
                                    No hay actividades programadas para el {{ $diaLabel }}.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Popover de filtro de columna (uno solo, se reutiliza para todas),
         teleportado a <body> y posicionado con posicionarFiltro(). --}}
    <template x-teleport="body">
        <div
            x-show="filtro.abierto"
            x-cloak
            x-transition.opacity
            :style="filtro.estilo"
            @click.outside="cerrarFiltro()"
            class="dc-popover rounded-2xl border border-slate-200 bg-white p-4 text-left shadow-xl"
        >
            <div class="mb-3 flex items-center justify-between gap-3">
                <h3 class="text-sm font-semibold text-slate-900" x-text="'Filtrar: ' + (etiquetas[filtro.clave] || '')"></h3>
                <button type="button" @click="cerrarFiltro()" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Cerrar">✕</button>
            </div>

            <template x-if="filtro.clave === 'fecha'">
                <div class="grid gap-3">
                    <div>
                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Desde</label>
                        <input type="date" x-model="filtro.desde" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700">
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Hasta</label>
                        <input type="date" x-model="filtro.hasta" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700">
                    </div>
                </div>
            </template>

            <template x-if="filtro.clave && filtro.clave !== 'fecha'">
                <div class="dc-popover-cuerpo">
                    <input
                        type="text"
                        x-ref="busquedaFiltro"
                        x-model="filtro.busqueda"
                        placeholder="Buscar..."
                        class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700"
                    >
                    <div class="flex items-center justify-between gap-2 text-xs">
                        <span class="text-slate-400" x-text="filtro.seleccion.length + ' seleccionado(s)'"></span>
                        <div class="flex items-center gap-3">
                            <button type="button" @click="marcarVisibles(true)" class="font-semibold text-slate-500 hover:text-slate-700">Seleccionar todo</button>
                            <button type="button" @click="marcarVisibles(false)" class="font-semibold text-slate-500 hover:text-slate-700">Deseleccionar</button>
                        </div>
                    </div>
                    <div class="dc-popover-lista space-y-0.5">
                        <template x-for="o in opcionesVisibles().slice(0, maxOpciones)" :key="o.value">
                            <label class="flex items-start gap-2.5 rounded-lg px-2 py-1.5 hover:bg-slate-50">
                                <input type="checkbox" :checked="estaMarcada(o.value)" @change="alternar(o.value)" class="mt-0.5 rounded border-slate-300">
                                <span class="text-sm leading-5 text-slate-700" :class="o.value === '{{ FieldDiaryFullView::EMPTY_VALUE }}' ? 'dc-sin-valor' : ''" x-text="o.label"></span>
                            </label>
                        </template>
                        <p x-show="opcionesVisibles().length === 0" class="px-2 py-2 text-sm text-slate-400">Sin coincidencias.</p>
                        <p x-show="opcionesVisibles().length > maxOpciones" class="px-2 py-2 text-xs text-slate-400" x-text="'Mostrando ' + maxOpciones + ' de ' + opcionesVisibles().length + ' — escribe en la búsqueda para acotar.'"></p>
                    </div>
                </div>
            </template>

            <div class="mt-3 flex items-center justify-between gap-2">
                <button type="button" @click="aplicarFiltro()" class="inline-flex items-center rounded-xl bg-[#d55b20] px-4 py-2 text-sm font-semibold text-white hover:bg-[#b8481a]">Aplicar</button>
                <button type="button" @click="quitarFiltro(filtro.clave)" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Quitar filtro</button>
            </div>
        </div>
    </template>

    {{-- Calendario del modo "Por dia" — mismo patron y mismo endpoint
         dias-con-datos que Programacion (es la misma tabla activities). --}}
    <div x-show="calendarioAbierto" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 p-4" style="top:0;left:0;height:100vh;width:100vw;">
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
            <p class="mb-4 text-center text-xs text-slate-500">Selecciona un día para ver su Diario de Campo.</p>

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
                        @click="celda && irADia(celda.fecha)"
                        :class="!celda ? 'invisible' : (
                            celda.fecha === dia
                                ? 'bg-slate-900 text-white'
                                : diasConDatosSet.has(celda.fecha)
                                    ? 'bg-[#d55b20] text-white hover:bg-[#b8481a]'
                                    : (celda.fecha === hoyStr ? 'text-slate-700 ring-2 ring-[#d55b20]' : 'bg-slate-50 text-slate-600 hover:bg-slate-100')
                        )"
                        class="flex h-11 items-center justify-center rounded-xl text-sm font-medium transition"
                        x-text="celda ? celda.numero : ''"
                    ></button>
                </template>
            </div>

            <div class="mt-4 flex items-center justify-center gap-2 text-xs text-slate-500">
                <span class="h-3 w-3 rounded-full bg-[#d55b20]"></span> Días con actividades registradas
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Componente de pagina del Diario de Campo. El filtrado es en servidor
    // (FieldDiaryFullView): cada cambio (filtro, modo, dia) arma la URL con
    // TODO el estado vigente y navega — asi los filtros se combinan, se
    // mantienen al cambiar de dia o de modo, y la URL se puede recargar o
    // compartir. Cambiar algo vuelve a la pagina 1 (no se copia "page").
    function diarioCampoPage({ baseUrl, modoTodas, dia, hoyReal, diasConDatosUrlBase, filtros, opciones, etiquetas }) {
        const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        const pad2 = (n) => String(n).padStart(2, '0');
        const fechaAString = (year, month0, day) => `${year}-${pad2(month0 + 1)}-${pad2(day)}`;
        const [fy, fm] = (dia || hoyReal).split('-').map(Number);

        // Posicion del popover de filtro: se abre hacia donde haya MAS
        // espacio (abajo si caben ~320px) y su max-height es ese espacio,
        // para no salirse nunca de la pantalla (.dc-popover-lista es lo
        // unico que se encoge). Alineado al borde izquierdo del
        // encabezado, sin salirse por la derecha.
        function posicionarFiltro(el) {
            const r = el.getBoundingClientRect();
            const ancho = 320;
            const margen = 8;
            const left = Math.max(margen, Math.min(r.left, window.innerWidth - ancho - margen));
            const libreAbajo = window.innerHeight - r.bottom - 6 - margen;
            const libreArriba = r.top - 6 - margen;
            const base = `position:fixed; left:${left}px; width:${ancho}px; z-index:9999;`;

            if (libreAbajo >= 320 || libreAbajo >= libreArriba) {
                return `${base} top:${r.bottom + 6}px; max-height:${Math.floor(libreAbajo)}px;`;
            }
            return `${base} bottom:${window.innerHeight - r.top + 6}px; max-height:${Math.floor(libreArriba)}px;`;
        }

        return {
            modoTodas,
            dia,
            hoyStr: hoyReal,
            filtros,
            opciones,
            etiquetas,
            // Tope de opciones renderizadas por popover (texto libre como
            // Comentarios puede tener miles de valores distintos).
            maxOpciones: 300,
            filtro: { abierto: false, clave: null, estilo: '', busqueda: '', seleccion: [], desde: '', hasta: '' },

            calendarioAbierto: false,
            mesCalendario: { year: fy, month: fm - 1 },
            diasConDatosSet: new Set(),

            init() {
                diarioRestaurarScroll();
            },

            // --- navegacion (modo, dia, filtros) ---
            navegar(f, { todas = this.modoTodas, fecha = this.dia } = {}) {
                const url = new URL(baseUrl, window.location.origin);
                if (todas) {
                    url.searchParams.set('vista', 'todas');
                    if (f.fecha?.from) url.searchParams.set('fecha_desde', f.fecha.from);
                    if (f.fecha?.to) url.searchParams.set('fecha_hasta', f.fecha.to);
                } else {
                    url.searchParams.set('date', fecha || hoyReal);
                }
                Object.entries(f).forEach(([clave, valores]) => {
                    if (clave === 'fecha' || !Array.isArray(valores)) return;
                    valores.forEach((v) => url.searchParams.append(clave + '[]', v));
                });
                window.location.href = url.toString();
            },
            irATodas() {
                if (!this.modoTodas) this.navegar(this.filtros, { todas: true });
            },
            irAPorDia() {
                if (this.modoTodas) this.navegar(this.filtros, { todas: false, fecha: hoyReal });
            },
            irADia(fecha) {
                this.navegar(this.filtros, { todas: false, fecha });
            },
            moverDia(delta) {
                const [y, m, d] = this.dia.split('-').map(Number);
                const f = new Date(y, m - 1, d + delta);
                this.irADia(fechaAString(f.getFullYear(), f.getMonth(), f.getDate()));
            },

            // --- filtros por columna ---
            abrirFiltro(clave, el) {
                this.filtro = {
                    abierto: true,
                    clave,
                    estilo: posicionarFiltro(el),
                    busqueda: '',
                    seleccion: clave === 'fecha' ? [] : [...(this.filtros[clave] || [])],
                    desde: clave === 'fecha' ? (this.filtros.fecha.from || '') : '',
                    hasta: clave === 'fecha' ? (this.filtros.fecha.to || '') : '',
                };
                this.$nextTick(() => this.$refs.busquedaFiltro?.focus());
            },
            cerrarFiltro() {
                this.filtro.abierto = false;
            },
            opcionesVisibles() {
                const lista = this.opciones[this.filtro.clave] || [];
                const termino = this.filtro.busqueda.trim().toLowerCase();
                return termino === '' ? lista : lista.filter((o) => o.label.toLowerCase().includes(termino));
            },
            estaMarcada(valor) {
                return this.filtro.seleccion.includes(valor);
            },
            alternar(valor) {
                const i = this.filtro.seleccion.indexOf(valor);
                if (i === -1) this.filtro.seleccion.push(valor);
                else this.filtro.seleccion.splice(i, 1);
            },
            // Como "Seleccionar todo" de Excel: solo sobre lo visible.
            marcarVisibles(marcar) {
                const visibles = this.opcionesVisibles().map((o) => o.value);
                if (marcar) {
                    this.filtro.seleccion = [...new Set([...this.filtro.seleccion, ...visibles])];
                } else {
                    this.filtro.seleccion = this.filtro.seleccion.filter((v) => !visibles.includes(v));
                }
            },
            aplicarFiltro() {
                const siguientes = JSON.parse(JSON.stringify(this.filtros));
                if (this.filtro.clave === 'fecha') {
                    if (this.filtro.desde && this.filtro.hasta && this.filtro.hasta < this.filtro.desde) {
                        showCrudToast('La fecha final no puede ser menor que la inicial.', 'error');
                        return;
                    }
                    siguientes.fecha = { from: this.filtro.desde || null, to: this.filtro.hasta || null };
                } else {
                    siguientes[this.filtro.clave] = [...this.filtro.seleccion];
                }
                this.navegar(siguientes);
            },
            quitarFiltro(clave) {
                const siguientes = JSON.parse(JSON.stringify(this.filtros));
                if (clave === 'fecha') siguientes.fecha = { from: null, to: null };
                else siguientes[clave] = [];
                this.navegar(siguientes);
            },
            // Quita todos los filtros de columna; conserva el modo y el dia.
            limpiarTodo() {
                this.navegar({ fecha: { from: null, to: null } });
            },

            // --- calendario (modo Por dia) ---
            abrirCalendario() {
                this.mesCalendario = { year: fy, month: fm - 1 };
                this.cargarDiasConDatos();
                this.calendarioAbierto = true;
            },
            cambiarMes(delta) {
                let { year, month } = this.mesCalendario;
                month += delta;
                if (month < 0) { month = 11; year--; }
                if (month > 11) { month = 0; year++; }
                this.mesCalendario = { year, month };
                this.cargarDiasConDatos();
            },
            async cargarDiasConDatos() {
                try {
                    const url = `${diasConDatosUrlBase}?year=${this.mesCalendario.year}&month=${this.mesCalendario.month + 1}`;
                    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();
                    this.diasConDatosSet = new Set(data.dias || []);
                } catch (e) {
                    console.error('cargarDiasConDatos:', e);
                }
            },
            diasCalendario() {
                const { year, month } = this.mesCalendario;
                const offset = (new Date(year, month, 1).getDay() + 6) % 7;
                const totalDias = new Date(year, month + 1, 0).getDate();
                const celdas = [];
                for (let i = 0; i < offset; i++) celdas.push(null);
                for (let d = 1; d <= totalDias; d++) celdas.push({ numero: d, fecha: fechaAString(year, month, d) });
                return celdas;
            },
            mesCalendarioLabel() {
                return `${MESES[this.mesCalendario.month]} ${this.mesCalendario.year}`;
            },
        };
    }

    // Edicion en linea celda por celda (seccion 14.22, mismo patron que
    // AdminPreventiveReportController::inlineUpdate()). Desde 2026-09-28 un
    // guardado exitoso actualiza la celda EN SU LUGAR, sin recargar — y en
    // todas las filas de la misma actividad (cuando tiene varios grupos de
    // horas, varios campos se repiten; "Actividad ejecutada" y
    // "Comentarios" son el mismo campo). Excepcion: "Horas" (corrected_hours)
    // cambia cuantas filas genera la actividad (diaryHourGroups), ahi se
    // recarga conservando la posicion del scroll de la tabla.
    const DIARIO_CSRF_TOKEN = @js(csrf_token());
    const DIARIO_SCROLL_KEY = 'diario_campo_scroll';

    // El mensaje de exito viaja junto con la posicion del scroll: el toast
    // se muestra despues de recargar (antes de recargar no alcanzaria a
    // verse).
    function diarioGuardarScrollYRecargar(mensaje) {
        const box = document.getElementById('diarioTablaScroll');
        try {
            sessionStorage.setItem(DIARIO_SCROLL_KEY, JSON.stringify({
                url: window.location.href,
                top: box ? box.scrollTop : 0,
                left: box ? box.scrollLeft : 0,
                mensaje,
            }));
        } catch (e) {}
        window.location.reload();
    }

    function diarioRestaurarScroll() {
        let guardado = null;
        try {
            guardado = JSON.parse(sessionStorage.getItem(DIARIO_SCROLL_KEY) || 'null');
            sessionStorage.removeItem(DIARIO_SCROLL_KEY);
        } catch (e) {}
        const box = document.getElementById('diarioTablaScroll');
        if (guardado && box && guardado.url === window.location.href) {
            box.scrollTop = guardado.top;
            box.scrollLeft = guardado.left;
            if (guardado.mensaje) showCrudToast(guardado.mensaje, 'success');
        }
    }

    function diarioEscapeInlineValue(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function diarioMountInlineEditor(container) {
        if (!container || container.dataset.editing === '1') return;

        const activityId = container.dataset.activityId;
        const field = container.dataset.field;
        const originalValue = container.dataset.value ?? '';
        const multiline = container.dataset.multiline === '1';

        container.dataset.editing = '1';

        container.innerHTML = `
            <div class="inline-edit-box">
                ${multiline
                    ? `<textarea class="inline-edit-textarea" maxlength="2000">${diarioEscapeInlineValue(originalValue)}</textarea>`
                    : `<input type="text" class="inline-edit-input" value="${diarioEscapeInlineValue(originalValue)}" maxlength="255">`}
                <div class="inline-edit-actions">
                    <button type="button" class="inline-edit-btn inline-edit-btn-cancel" title="Cancelar">✕</button>
                    <button type="button" class="inline-edit-btn inline-edit-btn-save" title="Guardar">✓</button>
                </div>
            </div>
        `;

        const campo = container.querySelector(multiline ? '.inline-edit-textarea' : '.inline-edit-input');
        const cancelBtn = container.querySelector('.inline-edit-btn-cancel');
        const saveBtn = container.querySelector('.inline-edit-btn-save');

        campo?.focus();
        campo?.select?.();

        campo?.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                event.stopPropagation();
                diarioRestoreInlineCell(container, originalValue);
            }
            if (event.key === 'Enter' && !multiline) {
                event.preventDefault();
                diarioSaveInlineCell(container, activityId, field, campo.value);
            }
        });

        cancelBtn?.addEventListener('click', () => diarioRestoreInlineCell(container, originalValue));
        saveBtn?.addEventListener('click', () => diarioSaveInlineCell(container, activityId, field, campo?.value ?? ''));
    }

    function diarioRestoreInlineCell(container, value) {
        container.dataset.editing = '0';
        container.dataset.value = value ?? '';
        const displayValue = value !== null && value !== undefined && String(value).trim() !== '' ? value : '—';
        container.innerHTML = `<span class="inline-edit-trigger">${diarioEscapeInlineValue(displayValue)}</span>`;
    }

    async function diarioSaveInlineCell(container, activityId, field, value) {
        container.classList.add('inline-edit-loading');

        try {
            const url = @js(route('personal.diario-campo.inline-update', ['activity' => '__ACTIVITY_ID__'])).replace('__ACTIVITY_ID__', activityId);
            const response = await fetch(url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': DIARIO_CSRF_TOKEN,
                },
                body: JSON.stringify({ field, value }),
            });
            const data = await response.json().catch(() => null);

            if (!response.ok || !data?.success) {
                throw new Error(data?.errors?.value?.[0] || data?.message || 'No fue posible guardar el campo.');
            }

            const mensaje = data.message || 'Campo actualizado correctamente.';

            if (field === 'corrected_hours') {
                diarioGuardarScrollYRecargar(mensaje);
                return;
            }

            // Todas las celdas de esa actividad y ese campo (varias filas por
            // grupo de horas; Actividad ejecutada + Comentarios).
            const valor = data.value ?? '';
            document
                .querySelectorAll(`.inline-editable[data-activity-id="${activityId}"][data-field="${field}"]`)
                .forEach((celda) => {
                    celda.classList.remove('inline-edit-loading', 'dc-guardado');
                    diarioRestoreInlineCell(celda, valor);
                    void celda.offsetWidth; // reinicia la animacion si ya estaba
                    celda.classList.add('dc-guardado');
                });
            showCrudToast(mensaje, 'success');
        } catch (error) {
            container.classList.remove('inline-edit-loading');
            showCrudToast(error.message || 'Ocurrió un error al guardar.', 'error');
        }
    }

    document.addEventListener('click', function (event) {
        const editableContainer = event.target.closest('.inline-editable');
        if (editableContainer && editableContainer.dataset.editing !== '1') {
            diarioMountInlineEditor(editableContainer);
        }
    });
</script>
@endpush
