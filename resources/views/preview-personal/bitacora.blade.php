@extends('preview-personal._layout')

@section('title', 'Bitácora mensual')

@php
    // Navegacion por mes/anio via query string (?year=&month=), sin JS: es un
    // Route::view() asi que request() funciona igual que en un controlador.
    $year = (int) request('year', now()->year);
    $month = (int) request('month', now()->month);
    $fechaMes = \Carbon\Carbon::createFromDate($year, $month, 1);
    $mesAnterior = $fechaMes->copy()->subMonthNoOverflow();
    $mesSiguiente = $fechaMes->copy()->addMonthNoOverflow();
    $anioAnterior = $fechaMes->copy()->subYearNoOverflow();
    $anioSiguiente = $fechaMes->copy()->addYearNoOverflow();

    // Solo tenemos datos reales de ejemplo para septiembre 2026 (de las
    // capturas de "Bitacora Septiembre 2026.xlsx"). Cualquier otro mes
    // muestra el estado vacio a proposito, para demostrar la regla de que
    // solo aparecen empleados con horas registradas ese mes.
    $esMesConDatos = ($year === 2026 && $month === 9);

    // Fuente unica de empleados (confirmado 2026-09-11): la Bitacora ya no
    // mantiene su propia lista de nombres — se apoya en los mismos registros
    // que se ven en Empleados (misma logica que usa empleados.blade.php).
    // 'codigo_bitacora' reconcilia el nickname canonico (el que se ve en
    // Empleados) con la clave/abreviatura que traen los datos historicos del
    // Excel de Bitacora cuando difieren (ej. nickname "Fernando" vs clave
    // historica "Luis Fdo M"); si no hay override, es igual al nickname.
    $empleadosCatalogo = include resource_path('views/preview-personal/_empleados-data.php');

    $dias = [
        ['numero' => 1, 'nombre' => 'martes', 'festivo' => false],
        ['numero' => 2, 'nombre' => 'miércoles', 'festivo' => false],
        ['numero' => 3, 'nombre' => 'jueves', 'festivo' => false],
        ['numero' => 4, 'nombre' => 'viernes', 'festivo' => false],
        ['numero' => 5, 'nombre' => 'sábado', 'festivo' => false],
        ['numero' => 6, 'nombre' => 'domingo', 'festivo' => true],
    ];
    for ($n = 7; $n <= 30; $n++) {
        $dias[] = ['numero' => $n, 'nombre' => \Carbon\Carbon::parse("2026-09-{$n}")->translatedFormat('l'), 'festivo' => \Carbon\Carbon::parse("2026-09-{$n}")->isSunday()];
    }

    $valores = $esMesConDatos ? [
        1 => ['Norman' => 9.5, 'Alais' => 10, 'Espinosa 1' => 9.5, 'Jeison' => 7, 'Wilmar' => 9.5, 'Sara' => 9.5, 'Conrado' => 10, 'Espinosa 2' => 10, 'Monsalve' => 12, 'Alex Sierra' => 12, 'Geferson' => 12, 'Alejandro' => 'L', 'Herrera' => 12, 'Luis Fdo M' => 9.5, 'Yesid' => 10, 'Geronimo' => 12, 'J Manuel' => 12, 'Camila' => 9.5, 'Pachon' => 10, 'Cristian M' => 12, 'Evelio' => 10, 'Brayan R' => 8, 'Brahian Q' => 10, 'Omar' => 10, 'Nedy Johana' => 12, 'Danilo' => 10, 'Brayam P' => 9.5, 'Valbuena' => 9.5, 'Ana C' => 9.5, 'Diego O' => 10, 'Brayan C' => 10, 'Diego S' => 12, 'Wilmar Guzman' => 12, 'Jose Alberto' => 12, 'Neider' => 12, 'Luis M' => 12, 'Diana Suan' => 8],
        2 => ['Bonilla' => 12, 'Norman' => 9.5, 'Alais' => 13.5, 'Espinosa 1' => 9.5, 'Jeison' => 7, 'Wilmar' => 9.5, 'Sara' => 9.5, 'Conrado' => 10, 'Espinosa 2' => 9.5, 'Monsalve' => 12, 'Alex Sierra' => 13.5, 'Geferson' => 12, 'Alejandro' => 'L', 'Herrera' => 12, 'Luis Fdo M' => 9.5, 'Alan' => 12, 'Yesid' => 10, 'Geronimo' => 10, 'J Manuel' => 12, 'Camila' => 9.5, 'Pachon' => 10, 'Cristian M' => 12, 'Evelio' => 10, 'Brayan R' => 12, 'Anderson' => 13.5, 'Brahian Q' => 10, 'Omar' => 10, 'Nedy Johana' => 12, 'Danilo' => 13.5, 'Brayam P' => 9.5, 'Valbuena' => 9.5, 'Ana C' => 9.5, 'Eder' => 12, 'Diego O' => 10, 'Brayan C' => 13.5, 'Diego S' => 12, 'Wilmar Guzman' => 12, 'Jose Alberto' => 12, 'Neider' => 12, 'Luis M' => 12],
        3 => ['Bonilla' => 12, 'Norman' => 9.5, 'Alais' => 10, 'Espinosa 1' => 10, 'Jeison' => [null, 7, 'jornada nocturna-1 ED-11EN'], 'Wilmar' => 9.5, 'Sara' => 9.5, 'Conrado' => 10, 'Espinosa 2' => 9.5, 'Monsalve' => 12, 'Alex Sierra' => 10, 'Alejandro' => 'L', 'Herrera' => 12, 'Luis Fdo M' => 9.5, 'Alan' => 12, 'Yesid' => 10, 'Geronimo' => 10, 'J Manuel' => 10, 'Camila' => 9.5, 'Pachon' => 10, 'Cristian M' => 10, 'Brayan R' => 10, 'Anderson' => 10, 'Brahian Q' => 12, 'Omar' => 10, 'Nedy Johana' => 10, 'Danilo' => 10, 'Brayam P' => 13.5, 'Valbuena' => 9.5, 'Ana C' => 9.5, 'Eder' => 12, 'Diego O' => 10, 'Brayan C' => 10, 'Diego S' => 12, 'Jose Alberto' => 12, 'Luis M' => 12],
        4 => ['Bonilla' => 10.5, 'Norman' => 13.5, 'Alais' => 22.5, 'Espinosa 1' => 8, 'Jeison' => 10.5, 'Wilmar' => 6, 'Sara' => 6, 'Conrado' => 16, 'Espinosa 2' => 8, 'Monsalve' => 11, 'Alex Sierra' => 16, 'Geferson' => 9, 'Alejandro' => 'L', 'Herrera' => 10.5, 'Luis Fdo M' => 12, 'Alan' => 10.5, 'Yesid' => 10.5, 'Pachon' => 10.5, 'Evelio' => 13.5, 'Anderson' => 7, 'Brahian Q' => 10.5, 'Omar' => 12, 'Danilo' => 13.5, 'Ana C' => 12.5, 'Eder' => 13.5, 'Wilmar Guzman' => 10.5, 'Jose Alberto' => 17, 'Neider' => 12],
        5 => ['Bonilla' => 12, 'Alais' => 4, 'Jeison' => 10.5, 'Sara' => 7, 'Alex Sierra' => 12.5, 'Geferson' => 8.5, 'Yesid' => 12, 'Pachon' => 12, 'Brayan R' => 13.5, 'Brahian Q' => 7, 'Omar' => 10.5, 'Valbuena' => 12],
        6 => ['Luis Fdo M' => 12, 'Yesid' => 12, 'Pachon' => 12],
    ] : [];

    $totalesReales = $esMesConDatos ? [
        'Bonilla' => 46.5, 'Norman' => 42, 'Alais' => 56, 'Espinosa 1' => 37, 'Jeison' => 35.5, 'Wilmar' => 34.5, 'Sara' => 34.5, 'Conrado' => 53,
        'Espinosa 2' => 37, 'Monsalve' => 59.5, 'Alex Sierra' => 51.5, 'Geferson' => 53.5, 'Alejandro' => 0, 'Herrera' => 46.5, 'Luis Fdo M' => 52.5, 'Alan' => 34.5,
        'Yesid' => 64.5, 'Geronimo' => 32, 'J Manuel' => 34, 'Camila' => 28.5, 'Pachon' => 64.5, 'Cristian M' => 34, 'Evelio' => 30, 'Brayan R' => 49.5,
        'Anderson' => 38.5, 'Brahian Q' => 39, 'Omar' => 42, 'Nedy Johana' => 30, 'Danilo' => 48, 'Brayam P' => 47, 'Valbuena' => 52.5, 'Ana C' => 28.5,
        'Eder' => 34, 'Diego O' => 0, 'Brayan C' => 40.5, 'Diego S' => 50.5, 'Wilmar Guzman' => 72, 'Jose Alberto' => 52, 'Neider' => 72, 'Luis M' => 72,
        'Diana Suan' => 20,
    ] : [];

    $cuota = 182;

    // Regla confirmada 2026-09-10: solo aparece si (a) tiene la bandera
    // "hace parte de la Bitacora" activa Y (b) registra al menos una hora
    // este mes especifico. Ambas condiciones, no solo una. La columna se
    // etiqueta con el nickname real de Empleados; la busqueda de horas usa
    // 'codigo_bitacora' para no perder el historico ya capturado del Excel.
    $empleados = collect($empleadosCatalogo)
        ->map(fn ($e) => ['clave' => $e['codigo_bitacora'] ?? $e['nickname'], 'label' => $e['nickname'], 'bitacora' => $e['bitacora']])
        ->filter(fn ($e) => $e['bitacora'] && isset($totalesReales[$e['clave']]))
        ->values()
        ->all();

    $celda = function (int $dia, string $clave) use ($valores) {
        $v = $valores[$dia][$clave] ?? null;
        if (is_array($v)) {
            return ['programada' => null, 'reportada' => null, 'corregida' => $v[1], 'comentario' => $v[2]];
        }
        return ['programada' => $v, 'reportada' => $v, 'corregida' => null, 'comentario' => null];
    };
@endphp

@section('content')
<div class="mx-auto max-w-[1900px] space-y-4">

    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900 md:text-2xl">Bitácora mensual</h1>
                <p class="mt-1 text-xs text-slate-500">
                    Solo aparecen empleados marcados como "hace parte de la Bitácora" que además
                    registren al menos una hora ese mes — el resto no sale como columna.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-1 py-1">
                    <a href="{{ route('preview-personal.bitacora', ['year' => $anioAnterior->year, 'month' => $anioAnterior->month]) }}" title="Año anterior" class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100">
                        <i data-lucide="chevrons-left" class="h-4 w-4"></i>
                    </a>
                    <a href="{{ route('preview-personal.bitacora', ['year' => $mesAnterior->year, 'month' => $mesAnterior->month]) }}" title="Mes anterior" class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100">
                        <i data-lucide="chevron-left" class="h-4 w-4"></i>
                    </a>
                    <span class="min-w-[9rem] px-2 text-center text-sm font-semibold capitalize text-slate-800">{{ $fechaMes->translatedFormat('F Y') }}</span>
                    <a href="{{ route('preview-personal.bitacora', ['year' => $mesSiguiente->year, 'month' => $mesSiguiente->month]) }}" title="Mes siguiente" class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100">
                        <i data-lucide="chevron-right" class="h-4 w-4"></i>
                    </a>
                    <a href="{{ route('preview-personal.bitacora', ['year' => $anioSiguiente->year, 'month' => $anioSiguiente->month]) }}" title="Año siguiente" class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100">
                        <i data-lucide="chevrons-right" class="h-4 w-4"></i>
                    </a>
                </div>
                <span class="inline-flex items-center rounded-xl bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-700">
                    Cuota mensual: {{ $cuota }} h
                </span>
                <span class="inline-flex items-center rounded-xl bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-700">
                    {{ count($empleados) }} empleados
                </span>
            </div>
        </div>
    </div>

    @if (count($empleados) === 0)
        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-14 text-center text-sm text-slate-400">
            Ningún empleado registra horas en <span class="font-medium text-slate-500 capitalize">{{ $fechaMes->translatedFormat('F Y') }}</span>.
            <br>Usa las flechas para navegar a septiembre de 2026 y ver el ejemplo con datos reales.
        </div>
    @else
    <div class="compact-table-wrapper rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="scroll-container-visible">
            <table class="preventive-table divide-y divide-slate-200 text-sm">
                <thead class="sticky-table-head bg-slate-50">
                    <tr>
                        <th class="sticky-col whitespace-nowrap px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Día</th>
                        @foreach ($empleados as $emp)
                            <th class="px-2 py-2 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500" style="min-width:3.75rem">{{ $emp['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach ($dias as $dia)
                        <tr class="align-top hover:bg-slate-50">
                            <td class="sticky-col whitespace-nowrap px-3 py-1.5 text-xs font-medium {{ $dia['festivo'] ? 'text-red-500' : 'text-slate-500' }}">
                                {{ $dia['numero'] }} {{ $dia['nombre'] }}
                            </td>

                            @foreach ($empleados as $emp)
                                @php $c = $celda($dia['numero'], $emp['clave']); @endphp

                                @if ($dia['numero'] > 6)
                                    {{-- Dias 7-30: sin datos capturados aun, celda simple sin interactividad --}}
                                    <td class="px-2 py-1.5 text-center text-slate-300">·</td>
                                @else
                                    <td class="p-0 text-center">
                                        <div
                                            x-data="{
                                                tip: false, modal: false, estilo: '',
                                                programada: @js($c['programada']),
                                                reportada: @js($c['reportada']),
                                                corregida: @js($c['corregida']),
                                                comentario: @js($c['comentario']),
                                                nuevaCorreccion: '',
                                                get final() { return this.corregida !== null && this.corregida !== '' ? this.corregida : this.reportada; },
                                                get esTexto() { return this.final !== null && this.final !== '' && isNaN(Number(this.final)); },
                                                get alerta() { return !this.esTexto && (this.final === null || this.final === '' || Number(this.final) === 0); },
                                                abrirModal() { this.nuevaCorreccion = this.corregida ?? ''; this.modal = true; },
                                                guardar() {
                                                    const v = this.nuevaCorreccion.trim();
                                                    this.corregida = v === '' ? null : v;
                                                    this.modal = false;
                                                },
                                            }"
                                            class="relative"
                                        >
                                            <button
                                                type="button"
                                                @mouseenter="tip = true; estilo = posicionarPopover($el, 224, 100)" @mouseleave="tip = false"
                                                @click="abrirModal()"
                                                class="flex h-9 w-full items-center justify-center gap-0.5 text-xs font-medium"
                                                :class="esTexto ? 'bg-slate-100 text-slate-500' : (alerta ? 'bg-amber-50 text-amber-700' : 'text-slate-700')"
                                            >
                                                <span x-text="final ?? '—'"></span>
                                                <i x-show="corregida !== null && corregida !== ''" data-lucide="pencil-line" class="h-3 w-3 text-[#d55b20]"></i>
                                                <span x-show="comentario" class="absolute right-0.5 top-0.5 h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                            </button>

                                            <template x-teleport="body">
                                            <div x-show="tip" x-cloak x-transition :style="estilo" class="rounded-lg bg-slate-900 p-3 text-left text-xs text-white shadow-xl">
                                                <div class="mb-1 grid grid-cols-3 gap-1 text-center">
                                                    <div><div class="text-[10px] uppercase text-slate-400">Program.</div><div class="font-semibold" x-text="programada ?? '—'"></div></div>
                                                    <div><div class="text-[10px] uppercase text-slate-400">Reportada</div><div class="font-semibold" x-text="reportada ?? '—'"></div></div>
                                                    <div><div class="text-[10px] uppercase text-slate-400">Corregida</div><div class="font-semibold" x-text="corregida ?? '—'"></div></div>
                                                </div>
                                                <div x-show="comentario" class="mt-2 border-t border-white/10 pt-2 text-slate-200"><span class="font-semibold text-amber-400">Comentario:</span> <span x-text="comentario"></span></div>
                                            </div>
                                            </template>

                                            <div x-show="modal" x-cloak class="fixed inset-0 z-30 flex items-center justify-center bg-black/40 p-4" style="top:0;left:0;height:100vh;width:100vw;">
                                                <div @click.outside="modal = false" class="w-full max-w-sm rounded-2xl bg-white p-5 text-left shadow-xl">
                                                    <h3 class="mb-1 text-sm font-bold text-slate-900">{{ $emp['label'] }} — día {{ $dia['numero'] }} ({{ $dia['nombre'] }})</h3>
                                                    <p class="mb-3 text-xs text-slate-500">
                                                        Programada: <span x-text="programada ?? '—'"></span> ·
                                                        Reportada: <span x-text="reportada ?? '—'"></span> ·
                                                        Corregida: <span x-text="corregida ?? '—'"></span>
                                                    </p>

                                                    <label class="mb-1 block text-xs font-medium text-slate-600">Corregir hora</label>
                                                    <input type="text" x-model="nuevaCorreccion" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                                    <p class="mt-1 text-[11px] text-slate-400">
                                                        Texto libre a propósito: admite números y códigos de letra (L = licencia, y otros que se definan).
                                                        No borra la hora reportada por el supervisor — queda disponible en el tooltip.
                                                    </p>

                                                    <label class="mb-1 mt-3 block text-xs font-medium text-slate-600">Comentario</label>
                                                    <textarea x-model="comentario" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Ej. jornada nocturna-1 ED-11EN"></textarea>

                                                    <div class="mt-4 flex justify-end gap-2">
                                                        <button @click="modal = false" class="rounded-xl border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">Cancelar</button>
                                                        <button @click="guardar()" class="rounded-xl bg-[#d55b20] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#b8481a]">Guardar</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-slate-300 bg-slate-50 font-semibold">
                        <td class="sticky-col whitespace-nowrap px-3 py-1.5 text-xs">Total horas</td>
                        @foreach ($empleados as $emp)
                            <td class="px-2 py-1.5 text-center text-xs">{{ $totalesReales[$emp['clave']] ?? '—' }}</td>
                        @endforeach
                    </tr>
                    <tr class="bg-slate-50 text-xs text-slate-500">
                        <td class="sticky-col whitespace-nowrap px-3 py-1.5">Horas a laborar</td>
                        @foreach ($empleados as $emp)
                            <td class="px-2 py-1.5 text-center">{{ $cuota }}</td>
                        @endforeach
                    </tr>
                    <tr class="bg-slate-50 text-xs font-semibold text-red-500">
                        <td class="sticky-col whitespace-nowrap px-3 py-1.5">Extras</td>
                        @foreach ($empleados as $emp)
                            <td class="px-2 py-1.5 text-center">{{ isset($totalesReales[$emp['clave']]) ? $totalesReales[$emp['clave']] - $cuota : '—' }}</td>
                        @endforeach
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="flex flex-wrap gap-4 text-xs text-slate-500">
        <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded bg-amber-50 ring-1 ring-amber-200"></span> Sin reportar (alerta)</span>
        <span class="inline-flex items-center gap-1"><i data-lucide="pencil-line" class="h-3.5 w-3.5 text-[#d55b20]"></i> Corrección administrativa</span>
        <span class="inline-flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Tiene comentario</span>
        <span class="inline-flex items-center gap-1"><span class="rounded bg-slate-100 px-1.5 text-slate-500">L</span> Licencia</span>
    </div>
    @endif
</div>
@endsection
