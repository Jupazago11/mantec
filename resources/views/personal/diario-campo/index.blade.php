@extends('layouts.personal')

@section('title', 'Diario de Campo')

@php
    $fechaLabel = \Carbon\Carbon::parse($fecha)->translatedFormat('d \d\e F \d\e Y');
    $erroresActivityId = $errors->any() ? old('_activity_id') : null;
@endphp

@section('content')
<div
    class="mx-auto max-w-[1900px] space-y-4"
    x-data="diarioCampoPage({
        fecha: @js($fecha),
        diasConDatosUrlBase: @js(route('personal.programacion.dias-con-datos')),
    })"
>
    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900 md:text-2xl">Diario de Campo</h1>
                <p class="mt-1 text-xs text-slate-500">
                    Mismo objeto que la Programación, enriquecido a medida que avanza su ciclo de vida.
                    "Horas" muestra siempre el valor final (corregida si existe, si no la programada).
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button
                    @click="abrirCalendario()"
                    class="inline-flex items-center gap-1.5 rounded-md bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200"
                >
                    <i data-lucide="calendar-days" class="h-3.5 w-3.5"></i>
                    <span>{{ $fechaLabel }}</span>
                </button>
                <button
                    @click="exportarComoImagen(@js($fecha))"
                    :disabled="exportando || {{ $actividades->isEmpty() ? 'true' : 'false' }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    title="Genera una imagen del Diario de Campo para pegarla en WhatsApp (o descargarla en móvil)"
                >
                    <i data-lucide="image-down" class="h-4 w-4" x-show="!exportando"></i>
                    <i data-lucide="loader-2" class="h-4 w-4 animate-spin" x-show="exportando" x-cloak></i>
                    <span x-text="exportando ? 'Generando...' : 'Copiar como imagen'"></span>
                </button>
            </div>
        </div>
    </div>

    @if ($actividades->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-400">
            No hay actividades programadas para <span class="font-medium text-slate-500">{{ $fechaLabel }}</span>.
        </div>
    @else
        <p class="mb-2 flex items-center gap-1 text-xs text-slate-400">
            <i data-lucide="move-horizontal" class="h-3.5 w-3.5"></i> Desliza horizontalmente para ver todas las columnas
        </p>

        <div id="areaExportable" class="space-y-2">
            <p class="text-sm font-semibold text-slate-700">Diario de Campo — {{ $fechaLabel }}</p>
            <div class="compact-table-wrapper rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="table-scroll-container">
            <table class="preventive-table divide-y divide-slate-200 text-sm">
                <thead class="sticky-table-head bg-slate-50">
                    <tr>
                        <th class="whitespace-nowrap px-3 py-2.5 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">Estado</th>
                        <th class="whitespace-nowrap px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Empresa</th>
                        <th class="whitespace-nowrap px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Equipo</th>
                        <th class="whitespace-nowrap px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Proceso</th>
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
                    @foreach ($actividades as $a)
                        @php $reabrirEsta = $erroresActivityId == $a->id; @endphp
                        <tr class="align-top hover:bg-slate-50" x-data="{ modalAbierto: {{ $reabrirEsta ? 'true' : 'false' }} }">
                            <td class="whitespace-nowrap px-3 py-2.5 text-center">
                                <span class="mb-1 inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $a->isClosed() ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $a->isClosed() ? 'Cerrado' : 'Pendiente' }}
                                </span>
                                @if ($puedeEditar)
                                    <button type="button" @click="modalAbierto = true" class="mt-1 block w-full text-[11px] font-medium text-[#d55b20] hover:underline">
                                        {{ $a->isClosed() ? 'Editar cierre' : 'Cerrar' }}
                                    </button>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-3 py-2.5">{{ $a->company->name }}</td>
                            <td class="whitespace-nowrap px-3 py-2.5 text-slate-600">{{ $a->team ?: '—' }}</td>
                            <td class="whitespace-nowrap px-3 py-2.5 text-slate-600">{{ $a->process ?: '—' }}</td>
                            <td class="px-3 py-2.5">
                                <div class="font-medium text-slate-800">{{ $a->description }}</div>
                                @if ($a->executed_description)
                                    <div x-data="{ open: false }" class="mt-1 max-w-md text-xs leading-relaxed text-slate-500">
                                        <p :class="open ? '' : 'line-clamp-2'">{{ $a->executed_description }}</p>
                                        <button type="button" @click="open = !open" class="mt-0.5 inline-flex items-center gap-0.5 font-medium text-[#d55b20] hover:underline" x-text="open ? '▲ ver menos' : '▼ ver detalle ejecutado'"></button>
                                    </div>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-slate-600">{{ $a->personas->pluck('nickname')->implode(', ') ?: '—' }}</td>
                            <td class="px-3 py-2.5 text-center text-slate-500">{{ $a->personas->count() ?: '—' }}</td>
                            <td class="whitespace-nowrap px-3 py-2.5 font-medium">{{ $a->finalHours() !== null ? $a->finalHours() + 0 : '—' }}</td>
                            <td class="whitespace-nowrap px-3 py-2.5 text-slate-600">{{ $a->shift }}</td>
                            <td class="px-3 py-2.5 text-slate-600">{{ $a->comments ?: '—' }}</td>
                            <td class="bg-slate-100/40 px-2 py-2.5 text-slate-600">{{ $a->zcom ?: '—' }}</td>
                            <td class="bg-slate-100/40 px-2 py-2.5 text-slate-600">{{ $a->line_code ?: '—' }}</td>
                            <td class="bg-slate-100/40 px-2 py-2.5 text-slate-600">{{ $a->ot_sap ?: '—' }}</td>
                            <td class="bg-slate-100/40 px-2 py-2.5 text-slate-600">{{ $a->acta_entrega ?: '—' }}</td>
                            <td class="bg-slate-100/40 px-2 py-2.5 text-slate-600">{{ $a->we_code ?: '—' }}</td>

                            @if ($puedeEditar)
                                {{-- Modal "Cerrar actividad" — un solo PATCH con todos los
                                     campos de cierre, en vez de inputs sueltos por celda
                                     con autoguardado (mas simple de validar). --}}
                                <template x-teleport="body">
                                <div x-show="modalAbierto" x-cloak class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 p-4" style="top:0;left:0;height:100vh;width:100vw;">
                                    <div @click.outside="modalAbierto = false" x-show="modalAbierto" x-transition class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
                                        <form method="POST" action="{{ route('personal.diario-campo.close', $a) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="_activity_id" value="{{ $a->id }}">

                                            <div class="mb-1 flex items-center justify-between">
                                                <h3 class="text-base font-bold text-slate-900">{{ $a->company->name }} — {{ $a->description }}</h3>
                                                <button type="button" @click="modalAbierto = false" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100"><i data-lucide="x" class="h-5 w-5"></i></button>
                                            </div>
                                            <p class="mb-4 text-xs text-slate-500">{{ $a->personas->pluck('nickname')->implode(', ') ?: 'Sin personas asignadas' }}</p>

                                            @if ($reabrirEsta)
                                                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                                    <ul class="list-inside list-disc space-y-0.5">
                                                        @foreach ($errors->all() as $error)
                                                            <li>{{ $error }}</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif

                                            <div class="grid gap-3 sm:grid-cols-2">
                                                <div>
                                                    <label class="mb-1 block text-xs font-medium text-slate-600">Proceso</label>
                                                    <input type="text" name="process" value="{{ $reabrirEsta ? old('process') : $a->process }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Ej. Cambiar banda">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-xs font-medium text-slate-600">Horas corregida</label>
                                                    <input type="number" step="0.5" name="corrected_hours" value="{{ $reabrirEsta ? old('corrected_hours') : $a->corrected_hours }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Ej. 10">
                                                </div>
                                            </div>

                                            <div class="mt-3">
                                                <label class="mb-1 block text-xs font-medium text-slate-600">Actividad — versión ejecutada</label>
                                                <textarea name="executed_description" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Descripción técnica detallada de lo realmente realizado">{{ $reabrirEsta ? old('executed_description') : $a->executed_description }}</textarea>
                                            </div>

                                            <div class="mt-3">
                                                <label class="mb-1 block text-xs font-medium text-slate-600">Comentarios</label>
                                                <textarea name="comments" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ $reabrirEsta ? old('comments') : $a->comments }}</textarea>
                                            </div>

                                            <p class="mb-1 mt-4 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                                Códigos de integración (texto libre — significado exacto pendiente de confirmar)
                                            </p>
                                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                                                <div>
                                                    <label class="mb-1 block text-xs font-medium text-slate-600">ZCOM</label>
                                                    <input type="text" name="zcom" value="{{ $reabrirEsta ? old('zcom') : $a->zcom }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-xs font-medium text-slate-600">Línea</label>
                                                    <input type="text" name="line_code" value="{{ $reabrirEsta ? old('line_code') : $a->line_code }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-xs font-medium text-slate-600">OT SAP</label>
                                                    <input type="text" name="ot_sap" value="{{ $reabrirEsta ? old('ot_sap') : $a->ot_sap }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-xs font-medium text-slate-600">Acta entrega</label>
                                                    <input type="text" name="acta_entrega" value="{{ $reabrirEsta ? old('acta_entrega') : $a->acta_entrega }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-xs font-medium text-slate-600">WE</label>
                                                    <input type="text" name="we_code" value="{{ $reabrirEsta ? old('we_code') : $a->we_code }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                                </div>
                                            </div>

                                            <div class="mt-6 flex justify-end gap-2">
                                                <button type="button" @click="modalAbierto = false" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancelar</button>
                                                <button type="submit" class="rounded-xl bg-[#d55b20] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#b8481a]">Guardar cierre</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                </template>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
            </div>
        </div>

        <p class="mt-4 text-xs text-slate-400">
            Las columnas grises (ZCOM / Línea / OT SAP / Acta entrega / WE) son texto libre a propósito —
            su significado exacto y si son fijos o configurables por empresa sigue pendiente de confirmar.
        </p>
    @endif

    {{-- Toast de resultado de la exportacion --}}
    <div x-show="mensajeExport" x-cloak x-transition class="fixed bottom-4 right-4 z-50 max-w-xs rounded-xl bg-slate-900 px-4 py-3 text-sm text-white shadow-xl" x-text="mensajeExport"></div>

    {{-- Calendario — mismo patron y mismo endpoint dias-con-datos que
         Programacion (es la misma tabla activities). --}}
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
            <p class="mb-4 text-center text-xs text-slate-500">Selecciona un día para ver su Diario de Campo.</p>

            <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                <template x-for="d in ['LUN','MAR','MIE','JUE','VIE','SAB','DOM']" :key="d">
                    <div x-text="d"></div>
                </template>
            </div>
            <div class="mt-1 grid grid-cols-7 gap-1">
                <template x-for="(celda, idx) in diasCalendario()" :key="idx">
                    <a
                        :href="celda ? '{{ route('personal.diario-campo.index') }}?date=' + celda.fecha : null"
                        :class="!celda ? 'invisible' : (
                            celda.fecha === fecha
                                ? 'bg-slate-900 text-white'
                                : diasConDatosSet.has(celda.fecha)
                                    ? 'bg-[#d55b20] text-white hover:bg-[#b8481a]'
                                    : (celda.fecha === hoyStr ? 'text-slate-700 ring-2 ring-[#d55b20]' : 'bg-slate-50 text-slate-600 hover:bg-slate-100')
                        )"
                        class="flex h-11 items-center justify-center rounded-xl text-sm font-medium transition"
                        x-text="celda ? celda.numero : ''"
                    ></a>
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
    function diarioCampoPage({ fecha, diasConDatosUrlBase }) {
        const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        const [fy, fm] = fecha.split('-').map(Number);
        const pad2 = (n) => String(n).padStart(2, '0');
        const fechaAString = (year, month, day) => `${year}-${pad2(month + 1)}-${pad2(day)}`;

        return {
            ...imageExporterMixin('diario-de-campo'),
            fecha,
            hoyStr: new Date().toISOString().slice(0, 10),

            calendarioAbierto: false,
            mesCalendario: { year: fy, month: fm - 1 },
            diasConDatosSet: new Set(),
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
                const primerDia = new Date(year, month, 1);
                const totalDias = new Date(year, month + 1, 0).getDate();
                const offset = (primerDia.getDay() + 6) % 7;
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
</script>
@endpush
