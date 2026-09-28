@extends('layouts.personal')

@section('title', 'Bitácora mensual')

@php
    $mesAnterior = $fechaMes->copy()->subMonthNoOverflow();
    $mesSiguiente = $fechaMes->copy()->addMonthNoOverflow();
    $anioAnterior = $fechaMes->copy()->subYearNoOverflow();
    $anioSiguiente = $fechaMes->copy()->addYearNoOverflow();
@endphp

@section('content')
<style>
    {{-- Scroll vertical interno solo para Bitácora (pedido 2026-09-24):
         .table-scroll-container comparte clase con Programación/Diario de
         Campo/Empleados/Roles/reportes (layouts/personal.blade.php), asi
         que el ajuste vive aqui, scoped con una clase propia, en vez de
         tocar esa regla compartida — un mes completo son hasta 31 filas,
         las otras tablas no tienen ese problema de altura. Con la pagina
         completa haciendo scroll (comportamiento antes de este cambio),
         el encabezado con los nombres y el pie con los totales se
         perdian de vista al bajar. Ahora el contenedor tiene una altura
         acotada con overflow-y:auto, .sticky-table-head (ya existente)
         queda fijo arriba DENTRO de este contenedor en vez de filtrarse
         hacia el viewport, y el tfoot se fija abajo. --}}
    .bitacora-scroll-y {
        overflow-y: auto;
        max-height: 70vh;
    }
    .bitacora-scroll-y tfoot {
        position: sticky;
        bottom: 0;
        z-index: 10;
        box-shadow: inset 0 1px 0 rgb(203 213 225);
    }

    {{-- "Cargando..." + aparicion animada (pedido 2026-09-28): cada celda
         es su propio x-data (31 dias x N empleados) y Alpine carga con
         defer, asi que antes el navegador pintaba la tabla cruda (valores
         vacios, iconos y puntos que luego se ocultan, colores que cambian)
         mientras Alpine inicializaba. Ahora el loader es HTML puro (se ve
         desde el primer pintado, sin esperar a Alpine) y la tabla queda
         oculta hasta que bitacoraPage marca "listo". CSS propio en vez de
         clases Tailwind nuevas: el CSS de Tailwind se compila con Vite y
         una clase que no exista en el build no tendria efecto. min-height
         ~ alto real de la tabla (acotada a 70vh arriba) para que el pie de
         pagina no salte al cambiar de loader a tabla. --}}
    .bitacora-loader {
        display: flex;
        min-height: 70vh;
        align-items: center;
        justify-content: center;
        gap: 0.625rem;
        font-size: 0.875rem;
        color: rgb(100 116 139);
    }
    .bitacora-spinner {
        width: 1.25rem;
        height: 1.25rem;
        border-radius: 9999px;
        border: 2px solid rgb(226 232 240);
        border-top-color: #d55b20;
        animation: bitacora-spin 0.8s linear infinite;
    }
    @keyframes bitacora-spin {
        to { transform: rotate(360deg); }
    }
    {{-- Una animacion CSS se reinicia cuando el elemento pasa de
         display:none (x-show) a visible, asi que corre justo al revelar. --}}
    .bitacora-reveal {
        animation: bitacora-reveal 0.35s ease-out both;
    }
    @keyframes bitacora-reveal {
        from { opacity: 0; transform: translateY(6px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @media (prefers-reduced-motion: reduce) {
        .bitacora-spinner { animation-duration: 2s; }
        .bitacora-reveal { animation: none; }
    }
</style>
<div
    class="mx-auto max-w-[1900px] space-y-4"
    x-data="bitacoraPage({
        year: {{ $year }},
        month: {{ $month }},
        domingos: @js(collect($dias)->where('esDomingo', true)->pluck('numero')->values()),
        festivosManuales: @js(collect($dias)->where('festivoManual', true)->pluck('numero')->values()),
        toggleFestivoUrl: @js(route('personal.bitacora.holidays.toggle')),
        entriesUrl: @js(route('personal.bitacora.entries.store')),
        quotaUrl: @js(route('personal.bitacora.quota.store')),
        csrfToken: @js(csrf_token()),
        {{-- Pie de la tabla reactivo (2026-09-28): corregir una celda o la
             cuota ya no recarga la pagina, asi que Total/Horas a
             laborar/Extras salen de este estado. (object): claves = id de
             empleado, nunca un arreglo JS. --}}
        totales: @js((object) $totales),
        cuota: @js($cuotaHoras + 0),
    })"
>
    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900 md:text-2xl">Bitácora mensual</h1>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-1 py-1">
                    <a href="{{ route('personal.bitacora.index', ['year' => $anioAnterior->year, 'month' => $anioAnterior->month]) }}" title="Año anterior" class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100">
                        <i data-lucide="chevrons-left" class="h-4 w-4"></i>
                    </a>
                    <a href="{{ route('personal.bitacora.index', ['year' => $mesAnterior->year, 'month' => $mesAnterior->month]) }}" title="Mes anterior" class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100">
                        <i data-lucide="chevron-left" class="h-4 w-4"></i>
                    </a>
                    <button
                        type="button"
                        @click="modalMeses = true; verAnio = {{ $year }}"
                        title="Elegir mes"
                        class="flex min-w-[9rem] items-center justify-center gap-1.5 rounded-lg px-2 py-0.5 text-sm font-semibold capitalize text-slate-800 hover:bg-slate-100"
                    >
                        <i data-lucide="calendar-days" class="h-3.5 w-3.5 text-slate-400"></i>
                        {{ $fechaMes->translatedFormat('F Y') }}
                    </button>
                    <a href="{{ route('personal.bitacora.index', ['year' => $mesSiguiente->year, 'month' => $mesSiguiente->month]) }}" title="Mes siguiente" class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100">
                        <i data-lucide="chevron-right" class="h-4 w-4"></i>
                    </a>
                    <a href="{{ route('personal.bitacora.index', ['year' => $anioSiguiente->year, 'month' => $anioSiguiente->month]) }}" title="Año siguiente" class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100">
                        <i data-lucide="chevrons-right" class="h-4 w-4"></i>
                    </a>
                </div>

                {{-- Cuota por AJAX (2026-09-28): antes era un <form> que
                     recargaba la pagina en cada cambio. --}}
                <label class="inline-flex items-center gap-1.5 rounded-xl bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-700" title="Cuota de horas a laborar este mes — se define manualmente cada mes, no es un valor fijo">
                    Cuota mensual:
                    <input
                        type="number" min="0" step="0.5"
                        value="{{ $cuotaHoras + 0 }}"
                        @change="guardarCuota($event.target)"
                        :disabled="guardandoCuota"
                        class="w-14 rounded border border-slate-300 bg-white px-1 py-0.5 text-center text-xs font-semibold text-slate-800"
                    >
                    h
                </label>

                <span class="inline-flex items-center rounded-xl bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-700">
                    {{ count($empleados) }} empleados
                </span>
            </div>
        </div>
    </div>

    @if (count($empleados) === 0)
        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-14 text-center text-sm text-slate-400">
            Ningún empleado registra horas en <span class="font-medium text-slate-500 capitalize">{{ $fechaMes->translatedFormat('F Y') }}</span>.
            <br>Usa las flechas para navegar a otro mes, o crea actividades en Programación para este.
        </div>
    @else
    <div x-show="!listo" class="bitacora-loader rounded-2xl border border-slate-200 bg-white shadow-sm" role="status" aria-live="polite">
        <span class="bitacora-spinner" aria-hidden="true"></span>
        <span>Cargando bitácora…</span>
    </div>

    <div x-show="listo" x-cloak class="bitacora-reveal space-y-4">
    <div class="compact-table-wrapper rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="table-scroll-container bitacora-scroll-y">
            <table class="preventive-table divide-y divide-slate-200 text-sm">
                <thead class="sticky-table-head bg-slate-50">
                    <tr>
                        <th class="sticky-col whitespace-nowrap px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Día</th>
                        @foreach ($empleados as $emp)
                            <th class="px-2 py-2 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500" style="min-width:3.75rem">{{ $emp->nickname }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach ($dias as $dia)
                        <tr class="align-top hover:bg-slate-50">
                            <td
                                class="sticky-col whitespace-nowrap px-3 py-1.5 text-xs font-medium"
                                :class="esFestivo({{ $dia['numero'] }}) ? 'text-red-500' : 'text-slate-500'"
                            >
                                @if ($dia['esDomingo'])
                                    {{ $dia['numero'] }} {{ $dia['nombre'] }}
                                @else
                                    {{-- Festivo manual (pedido 2026-09-24): el sistema no tiene
                                         calendario de festivos colombianos, asi que se marca a mano
                                         con un clic. Desde 2026-09-28 es AJAX (toggleFestivo() en
                                         bitacoraPage), sin recargar: el rojo de esta celda y de las
                                         celdas de horas de la fila sale de esFestivo(), reactivo. --}}
                                    <button
                                        type="button"
                                        @click="toggleFestivo({{ $dia['numero'] }})"
                                        :disabled="festivoPendiente !== null"
                                        :title="esFestivoManual({{ $dia['numero'] }}) ? 'Quitar festivo' : 'Marcar como festivo'"
                                        class="underline decoration-dotted decoration-slate-300 underline-offset-2 hover:decoration-[#d55b20] disabled:cursor-wait disabled:opacity-60"
                                    >{{ $dia['numero'] }} {{ $dia['nombre'] }}</button>
                                    <span x-show="esFestivoManual({{ $dia['numero'] }})" x-cloak title="Festivo marcado manualmente" class="ml-0.5 text-red-500">●</span>
                                @endif
                            </td>

                            @foreach ($empleados as $emp)
                                @php
                                    $c = $celdas[$emp->id][$dia['numero']];
                                    $fechaDia = sprintf('%04d-%02d-%02d', $year, $month, $dia['numero']);
                                @endphp
                                <td class="p-0 text-center">
                                    <div
                                        x-data="{
                                            tip: false, modal: false, estilo: '', guardando: false,
                                            programada: @js($c['programada']),
                                            reportada: @js($c['reportada']),
                                            corregida: @js($c['corregida']),
                                            {{-- Valor del Diario de Campo (2026-09-28): base del numero
                                                 final, igual al que muestra el Diario (incluye su
                                                 correccion de Horas). --}}
                                            diario: @js($c['diario']),
                                            diarioCorregido: @js($c['diario_corregido']),
                                            comentarios: @js($c['comentarios']),
                                            nuevaCorreccion: @js($c['corregida'] ?? ''),
                                            {{-- A diferencia de nuevaCorreccion, nuevoComentario ya NO se
                                                 pre-llena con nada guardado: el comentario ahora es historial
                                                 (varios admin + 1 responsable, pedido 2026-09-22), este campo
                                                 siempre es 'agregar uno nuevo', no 'editar el existente'. --}}
                                            nuevoComentario: '',
                                            {{-- Prioridad corregida > reportada > programada (seccion 14.19)
                                                 — antes solo comparaba corregida/programada porque "reportada"
                                                 siempre llegaba en null desde el backend; ya no es el caso
                                                 desde que "Ver como supervisor" (seccion 14.18) alimenta datos
                                                 reales. --}}
                                            {{-- 2026-09-28: la base ya no es reportada/programada del dia
                                                 sino el valor del Diario (mismo criterio que
                                                 BitacoraHoursCalculator::valorFinal()). --}}
                                            get final() { return this.corregida !== null && this.corregida !== '' ? this.corregida : this.diario; },
                                            get tieneCorreccion() { return (this.corregida !== null && this.corregida !== '') || this.diarioCorregido; },
                                            get corregidaTexto() {
                                                if (this.corregida !== null && this.corregida !== '') return this.corregida;
                                                return this.diarioCorregido ? this.diario + ' (Diario)' : '—';
                                            },
                                            get esTexto() { return this.final !== null && this.final !== '' && isNaN(Number(this.final)); },
                                            get alerta() { return !this.esTexto && (this.final === null || this.final === '' || Number(this.final) === 0); },
                                            // nuevaCorreccion/nuevoComentario ya arrancan con el valor guardado
                                            // (arriba, en la inicializacion de x-data) — abrirModal() ya NO los
                                            // reinicia, para que cerrar el modal por error no borre lo que ya
                                            // se habia escrito y no guardado.
                                            abrirModal() { this.modal = true; },
                                        }"
                                        class="relative"
                                    >
                                        <button
                                            type="button"
                                            @mouseenter="tip = true; estilo = posicionarPopover($el, 224, 100)" @mouseleave="tip = false"
                                            @click="abrirModal()"
                                            class="flex h-9 w-full items-center justify-center gap-0.5 text-xs font-medium"
                                            :class="esFestivo({{ $dia['numero'] }})
                                                ? 'text-red-600 ' + (esTexto ? 'bg-slate-100' : (alerta ? 'bg-amber-50' : ''))
                                                : (esTexto ? 'bg-slate-100 text-slate-500' : (alerta ? 'bg-amber-50 text-amber-700' : 'text-slate-700'))"
                                        >
                                            <span x-text="final ?? '—'"></span>
                                            <i x-show="tieneCorreccion" data-lucide="pencil-line" class="h-3 w-3 text-[#d55b20]"></i>
                                            <span x-show="comentarios.length > 0" class="absolute right-0.5 top-0.5 h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                        </button>

                                        <template x-teleport="body">
                                        <div x-show="tip" x-cloak x-transition :style="estilo" class="rounded-lg bg-slate-900 p-3 text-left text-xs text-white shadow-xl">
                                            <div class="mb-1 grid grid-cols-3 gap-1 text-center">
                                                <div><div class="text-[10px] uppercase text-slate-400">Program.</div><div class="font-semibold" x-text="programada ?? '—'"></div></div>
                                                <div><div class="text-[10px] uppercase text-slate-400">Reportada</div><div class="font-semibold" x-text="reportada ?? '—'"></div></div>
                                                <div><div class="text-[10px] uppercase text-slate-400">Corregida</div><div class="font-semibold" x-text="corregidaTexto"></div></div>
                                            </div>
                                            <div x-show="comentarios.length > 0" class="mt-2 space-y-1 border-t border-white/10 pt-2 text-slate-200">
                                                <template x-for="c in comentarios" :key="c.autor + c.texto">
                                                    <div>
                                                        <span class="font-semibold" :class="c.es_responsable ? 'text-sky-400' : 'text-amber-400'" x-text="c.autor + (c.es_responsable ? ' (responsable):' : ':')"></span>
                                                        <span x-text="c.texto"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                        </template>

                                        <div x-show="modal" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 p-4" style="top:0;left:0;height:100vh;width:100vw;">
                                            <div @click.outside="modal = false" class="w-full max-w-sm rounded-2xl bg-white p-5 text-left shadow-xl">
                                                {{-- AJAX (2026-09-28): guardarCelda() en bitacoraPage
                                                     actualiza esta celda, su historial y el total del
                                                     empleado sin recargar; errores en el toast, el modal
                                                     queda abierto con lo escrito. --}}
                                                <form @submit.prevent="guardarCelda($data, {{ $emp->id }}, @js($fechaDia))">
                                                    <h3 class="mb-1 text-sm font-bold text-slate-900">{{ $emp->nickname }} — día {{ $dia['numero'] }} ({{ $dia['nombre'] }})</h3>
                                                    <p class="mb-3 text-xs text-slate-500">
                                                        Programada: <span x-text="programada ?? '—'"></span> ·
                                                        Reportada: <span x-text="reportada ?? '—'"></span> ·
                                                        Corregida: <span x-text="corregidaTexto"></span>
                                                    </p>

                                                    <label class="mb-1 block text-xs font-medium text-slate-600">Corregir hora</label>
                                                    <input type="text" maxlength="20" x-model="nuevaCorreccion" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                                    <p class="mt-1 text-[11px] text-slate-400">
                                                        Texto libre a propósito: admite números y códigos de letra (L = licencia, y otros que se definan).
                                                        No borra la hora programada — queda disponible en el tooltip.
                                                        Esta corrección es del día completo de la persona; para corregir las horas de una
                                                        actividad (y que se vean igual en el Diario de Campo), usa la columna "Horas" del Diario.
                                                    </p>

                                                    <div x-show="comentarios.length > 0" class="mb-3 mt-3 max-h-28 space-y-1.5 overflow-y-auto rounded-lg bg-slate-50 px-3 py-2 text-xs">
                                                        <template x-for="c in comentarios" :key="c.autor + c.texto">
                                                            <div>
                                                                <span class="font-semibold" :class="c.es_responsable ? 'text-sky-600' : 'text-amber-600'" x-text="c.autor + (c.es_responsable ? ' (responsable):' : ':')"></span>
                                                                <span class="text-slate-600" x-text="c.texto"></span>
                                                            </div>
                                                        </template>
                                                    </div>

                                                    <label class="mb-1 mt-3 block text-xs font-medium text-slate-600">Agregar comentario</label>
                                                    <textarea maxlength="500" x-model="nuevoComentario" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Ej. jornada nocturna-1 ED-11EN"></textarea>

                                                    <div class="mt-4 flex justify-end gap-2">
                                                        <button type="button" @click="modal = false" class="rounded-xl border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">Cancelar</button>
                                                        <button type="submit" :disabled="guardando" class="rounded-xl bg-[#d55b20] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#b8481a] disabled:cursor-not-allowed disabled:opacity-50" x-text="guardando ? 'Guardando…' : 'Guardar'">Guardar</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-slate-300 bg-slate-50 font-semibold">
                        <td class="sticky-col whitespace-nowrap px-3 py-1.5 text-xs">Total horas</td>
                        @foreach ($empleados as $emp)
                            <td class="px-2 py-1.5 text-center text-xs" x-text="totalTexto({{ $emp->id }})">{{ $totales[$emp->id] !== null ? round($totales[$emp->id], 2) : '—' }}</td>
                        @endforeach
                    </tr>
                    <tr class="bg-slate-50 text-xs text-slate-500">
                        <td class="sticky-col whitespace-nowrap px-3 py-1.5">Horas a laborar</td>
                        @foreach ($empleados as $emp)
                            <td class="px-2 py-1.5 text-center" x-text="redondear(cuota)">{{ $cuotaHoras + 0 }}</td>
                        @endforeach
                    </tr>
                    <tr class="bg-slate-50 text-xs font-semibold text-red-500">
                        <td class="sticky-col whitespace-nowrap px-3 py-1.5">Extras</td>
                        @foreach ($empleados as $emp)
                            <td class="px-2 py-1.5 text-center" x-text="extrasTexto({{ $emp->id }})">{{ $totales[$emp->id] !== null ? round($totales[$emp->id] - $cuotaHoras, 2) : '—' }}</td>
                        @endforeach
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="flex flex-wrap gap-4 text-xs text-slate-500">
        <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded bg-amber-50 ring-1 ring-amber-200"></span> Sin datos (alerta)</span>
        <span class="inline-flex items-center gap-1"><i data-lucide="pencil-line" class="h-3.5 w-3.5 text-[#d55b20]"></i> Corrección administrativa</span>
        <span class="inline-flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Tiene comentario</span>
        <span class="inline-flex items-center gap-1"><span class="rounded bg-slate-100 px-1.5 text-slate-500">L</span> Licencia</span>
        <span class="inline-flex items-center gap-1 text-red-500"><span class="h-1.5 w-1.5 rounded-full bg-red-500"></span> Domingo / festivo — clic en el día para marcar o quitar un festivo</span>
    </div>
    </div>
    @endif

    {{-- Selector de mes/año (pedido: la Bitácora es mensual, no diaria, asi
         que a diferencia del calendario de dias de Programacion/Diario de
         Campo, este modal navega por meses del año en vez de por dias del
         mes. Mismo shell visual (modal centrado, flechas +
         click.outside) que los otros dos, pero con una grilla de 12 meses
         en vez de una grilla de dias de la semana. verAnio es estado local
         del modal (no toca $year de la pagina hasta que se elige un mes,
         que navega via <a href> normal, igual que las flechas de arriba). --}}
    <div x-show="modalMeses" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 p-4" style="top:0;left:0;height:100vh;width:100vw;">
        <div @click.outside="modalMeses = false" x-show="modalMeses" x-transition class="w-full max-w-sm rounded-3xl bg-white p-6 shadow-xl">
            <div class="mb-1 flex items-center justify-between">
                <button type="button" @click="verAnio--" title="Año anterior" class="flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50">
                    <i data-lucide="chevron-left" class="h-4 w-4"></i>
                </button>
                <h3 class="text-base font-bold text-slate-900" x-text="verAnio"></h3>
                <button type="button" @click="verAnio++" title="Año siguiente" class="flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50">
                    <i data-lucide="chevron-right" class="h-4 w-4"></i>
                </button>
            </div>
            <p class="mb-4 text-center text-xs text-slate-500">Selecciona un mes para ver su bitácora.</p>

            <div class="grid grid-cols-4 gap-1.5">
                @foreach ([
                    1 => ['Ene', 'Enero'], 2 => ['Feb', 'Febrero'], 3 => ['Mar', 'Marzo'], 4 => ['Abr', 'Abril'],
                    5 => ['May', 'Mayo'], 6 => ['Jun', 'Junio'], 7 => ['Jul', 'Julio'], 8 => ['Ago', 'Agosto'],
                    9 => ['Sep', 'Septiembre'], 10 => ['Oct', 'Octubre'], 11 => ['Nov', 'Noviembre'], 12 => ['Dic', 'Diciembre'],
                ] as $numeroMes => [$abrevMes, $nombreMes])
                    <a
                        :href="'{{ route('personal.bitacora.index') }}?year=' + verAnio + '&month={{ $numeroMes }}'"
                        title="{{ $nombreMes }}"
                        :class="verAnio === {{ $year }} && {{ $numeroMes }} === {{ $month }}
                            ? 'bg-slate-900 text-white'
                            : (verAnio === {{ now()->year }} && {{ $numeroMes }} === {{ now()->month }}
                                ? 'text-slate-700 ring-2 ring-[#d55b20]'
                                : 'bg-slate-50 text-slate-600 hover:bg-slate-100')"
                        class="flex h-12 items-center justify-center rounded-xl text-xs font-semibold uppercase transition"
                    >{{ $abrevMes }}</a>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Estado de pagina de Bitacora. Los festivos (domingos + marcados a
    // mano) viven aqui, no en cada celda: las celdas de horas son x-data
    // anidados y llaman esFestivo(dia) de este scope padre, asi que al
    // marcar/quitar un festivo toda la fila cambia de color sin recargar.
    function bitacoraPage({ year, month, domingos, festivosManuales, toggleFestivoUrl, entriesUrl, quotaUrl, csrfToken, totales, cuota }) {
        const jsonHeaders = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        };
        // Primer mensaje de un 422 de Laravel ({errors: {campo: [..]}}) o el
        // "message" general.
        const mensajeError = (data, porDefecto) =>
            (data?.errors ? Object.values(data.errors).flat()[0] : null) || data?.message || porDefecto;

        return {
            modalMeses: false,
            verAnio: year,
            domingos,
            festivosManuales,
            // Pie de la tabla (2026-09-28): total del mes por empleado y
            // cuota, reactivos — se actualizan con cada guardado AJAX.
            totales,
            cuota,
            guardandoCuota: false,
            // Dia con peticion en curso (null = ninguna): bloquea los demas
            // clics para no disparar dos toggles seguidos sobre la BD.
            festivoPendiente: null,
            // false = se ve el loader "Cargando bitacora..." y la tabla sigue
            // oculta (ver .bitacora-loader/.bitacora-reveal arriba).
            listo: false,

            init() {
                // init() de este componente raiz corre ANTES de que Alpine
                // inicialice las celdas hijas (el arbol se recorre de arriba
                // hacia abajo en un solo paso sincronico); $nextTick se
                // resuelve despues de ese recorrido, cuando todas las celdas
                // ya tienen su valor, color e iconos finales.
                this.$nextTick(() => { this.listo = true; });
            },

            esFestivo(dia) {
                return this.domingos.includes(dia) || this.festivosManuales.includes(dia);
            },
            esFestivoManual(dia) {
                return this.festivosManuales.includes(dia);
            },
            async toggleFestivo(dia) {
                if (this.festivoPendiente !== null) return;
                this.festivoPendiente = dia;

                const fecha = `${year}-${String(month).padStart(2, '0')}-${String(dia).padStart(2, '0')}`;

                try {
                    const res = await fetch(toggleFestivoUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({ date: fecha }),
                    });
                    const data = await res.json().catch(() => null);

                    if (!res.ok || !data?.success) {
                        showCrudToast(data?.message || 'No se pudo actualizar el festivo.', 'error');
                        return;
                    }

                    // Se aplica el estado que devuelve el servidor, no el
                    // inverso del que se veia antes del clic.
                    const sinDia = this.festivosManuales.filter((d) => d !== dia);
                    this.festivosManuales = data.festivo ? [...sinDia, dia] : sinDia;
                    showCrudToast(data.message, 'success');
                } catch (e) {
                    console.error('toggleFestivo:', e);
                    showCrudToast('No se pudo actualizar el festivo (error de red).', 'error');
                } finally {
                    this.festivoPendiente = null;
                }
            },

            // --- pie de la tabla ---
            // Mismo formato que round($x, 2) de PHP: 18, 8.5, 0.25.
            redondear(n) {
                return String(Math.round(Number(n) * 100) / 100);
            },
            totalTexto(empleadoId) {
                const t = this.totales[empleadoId];
                return t === null || t === undefined ? '—' : this.redondear(t);
            },
            extrasTexto(empleadoId) {
                const t = this.totales[empleadoId];
                return t === null || t === undefined ? '—' : this.redondear(Number(t) - Number(this.cuota));
            },

            // Cuota mensual por AJAX (antes: <form> que recargaba la pagina).
            async guardarCuota(input) {
                const valor = input.value.trim();
                if (valor === '' || isNaN(Number(valor)) || Number(valor) < 0) {
                    showCrudToast('La cuota debe ser un número mayor o igual a 0.', 'error');
                    input.value = this.redondear(this.cuota);
                    return;
                }

                this.guardandoCuota = true;
                try {
                    const res = await fetch(quotaUrl, {
                        method: 'POST',
                        headers: jsonHeaders,
                        body: JSON.stringify({ year, month, quota_hours: Number(valor) }),
                    });
                    const data = await res.json().catch(() => null);

                    if (!res.ok || !data?.success) {
                        showCrudToast(mensajeError(data, 'No se pudo guardar la cuota.'), 'error');
                        input.value = this.redondear(this.cuota);
                        return;
                    }

                    this.cuota = data.cuota;
                    input.value = this.redondear(data.cuota);
                    showCrudToast(data.message, 'success');
                } catch (e) {
                    console.error('guardarCuota:', e);
                    showCrudToast('No se pudo guardar la cuota (error de red).', 'error');
                    input.value = this.redondear(this.cuota);
                } finally {
                    this.guardandoCuota = false;
                }
            },

            // Correccion + comentario de una celda por AJAX (antes: <form>
            // que recargaba la pagina). "celda" es el $data de la celda:
            // se le asigna lo que devuelve el servidor (valor corregido e
            // historial de comentarios) y el total del mes del empleado se
            // actualiza en el pie. Con error, el modal queda abierto con lo
            // escrito.
            async guardarCelda(celda, empleadoId, fecha) {
                if (celda.guardando) return;
                celda.guardando = true;

                try {
                    const res = await fetch(entriesUrl, {
                        method: 'POST',
                        headers: jsonHeaders,
                        body: JSON.stringify({
                            employee_id: empleadoId,
                            date: fecha,
                            corrected_value: celda.nuevaCorreccion,
                            comment: celda.nuevoComentario,
                        }),
                    });
                    const data = await res.json().catch(() => null);

                    if (!res.ok || !data?.success) {
                        showCrudToast(mensajeError(data, 'No se pudo guardar la corrección.'), 'error');
                        return;
                    }

                    celda.corregida = data.corregida;
                    celda.nuevaCorreccion = data.corregida ?? '';
                    celda.comentarios = data.comentarios;
                    celda.nuevoComentario = '';
                    celda.modal = false;
                    this.totales[empleadoId] = data.total_mes;
                    showCrudToast(data.message, 'success');
                    this.$nextTick(() => window.lucide?.createIcons());
                } catch (e) {
                    console.error('guardarCelda:', e);
                    showCrudToast('No se pudo guardar la corrección (error de red).', 'error');
                } finally {
                    celda.guardando = false;
                }
            },
        };
    }
</script>
@endpush
