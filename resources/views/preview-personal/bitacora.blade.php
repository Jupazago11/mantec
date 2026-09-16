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

    // Fuente unica compartida con Programacion — ver _horas-mes-data.php.
    // 'totales' se calcula ahi mismo sumando 'valores', asi el total del
    // pie de esta tabla SIEMPRE coincide con la suma de lo que se ve en
    // las celdas de los dias 1-6 (unicos con datos de ejemplo por ahora —
    // el resto del mes aun no tiene captura, ver dias 7-30 mas abajo).
    $horasMesData = $esMesConDatos ? include resource_path('views/preview-personal/_horas-mes-data.php') : ['valores' => [], 'totales' => []];
    $valores = $horasMesData['valores'];
    $totalesReales = $horasMesData['totales'];

    // Cuota por defecto cuando el mes aun no tiene una guardada — se
    // sobreescribe en el navegador (localStorage, por año-mes) desde que el
    // usuario la edita, ver Alpine mas abajo. "Se ponen manualmente" porque
    // el mes real de horas laborables varia (festivos, dias del mes, etc.),
    // no es una constante fija para siempre.
    $cuotaDefault = 182;

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
<div
    class="mx-auto max-w-[1900px] space-y-4"
    x-data="bitacora({ year: {{ $year }}, month: {{ $month }}, cuotaDefault: {{ $cuotaDefault }}, claves: @js(collect($empleados)->pluck('clave')), totales: @js($totalesReales) })"
>

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
                <label class="inline-flex items-center gap-1.5 rounded-xl bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-700" title="Cuota de horas a laborar este mes — se define manualmente cada mes, no es un valor fijo">
                    Cuota mensual:
                    <input
                        type="number" min="0" step="0.5"
                        x-model.number="cuota" @change="guardarCuota()"
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
                                                nuevoComentario: '',
                                                get final() { return this.corregida !== null && this.corregida !== '' ? this.corregida : this.reportada; },
                                                get esTexto() { return this.final !== null && this.final !== '' && isNaN(Number(this.final)); },
                                                get alerta() { return !this.esTexto && (this.final === null || this.final === '' || Number(this.final) === 0); },
                                                abrirModal() { this.nuevaCorreccion = this.corregida ?? ''; this.nuevoComentario = this.comentario ?? ''; this.modal = true; },
                                                guardar() {
                                                    const v = this.nuevaCorreccion.trim();
                                                    this.corregida = v === '' ? null : v;
                                                    const c = this.nuevoComentario.trim();
                                                    this.comentario = c === '' ? null : c;
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
                                                    <textarea x-model="nuevoComentario" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Ej. jornada nocturna-1 ED-11EN"></textarea>

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
                        <template x-for="clave in claves" :key="clave">
                            <td class="px-2 py-1.5 text-center" x-text="cuota"></td>
                        </template>
                    </tr>
                    <tr class="bg-slate-50 text-xs font-semibold text-red-500">
                        <td class="sticky-col whitespace-nowrap px-3 py-1.5">Extras</td>
                        <template x-for="clave in claves" :key="clave">
                            <td class="px-2 py-1.5 text-center" x-text="extras(clave)"></td>
                        </template>
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

@push('scripts')
<script>
    function bitacora({ year, month, cuotaDefault, claves, totales }) {
        // La cuota se "pone manualmente" cada mes (confirmado 2026-09-15):
        // no es un numero fijo para siempre, varia mes a mes (festivos,
        // dias habiles, etc). Se guarda en localStorage por año-mes para
        // que sobreviva a un refresh sin necesitar backend — mismo patron
        // de persistencia ya usado para el colapso del sidebar
        // (preview_personal_sidebar_collapsed en _layout.blade.php).
        const storageKey = `preview_personal_cuota_${year}-${month}`;
        const guardada = localStorage.getItem(storageKey);
        return {
            claves,
            totales,
            cuota: guardada !== null ? Number(guardada) : cuotaDefault,
            guardarCuota() {
                if (this.cuota === '' || this.cuota === null || isNaN(this.cuota)) return;
                localStorage.setItem(storageKey, String(this.cuota));
            },
            extras(clave) {
                const total = this.totales[clave];
                return total === undefined ? '—' : total - this.cuota;
            },
        };
    }
</script>
@endpush
