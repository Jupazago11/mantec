@extends('layouts.personal')

@section('title', 'Bitácora mensual')

@php
    $mesAnterior = $fechaMes->copy()->subMonthNoOverflow();
    $mesSiguiente = $fechaMes->copy()->addMonthNoOverflow();
    $anioAnterior = $fechaMes->copy()->subYearNoOverflow();
    $anioSiguiente = $fechaMes->copy()->addYearNoOverflow();
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
                    <a href="{{ route('personal.bitacora.index', ['year' => $anioAnterior->year, 'month' => $anioAnterior->month]) }}" title="Año anterior" class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100">
                        <i data-lucide="chevrons-left" class="h-4 w-4"></i>
                    </a>
                    <a href="{{ route('personal.bitacora.index', ['year' => $mesAnterior->year, 'month' => $mesAnterior->month]) }}" title="Mes anterior" class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100">
                        <i data-lucide="chevron-left" class="h-4 w-4"></i>
                    </a>
                    <span class="min-w-[9rem] px-2 text-center text-sm font-semibold capitalize text-slate-800">{{ $fechaMes->translatedFormat('F Y') }}</span>
                    <a href="{{ route('personal.bitacora.index', ['year' => $mesSiguiente->year, 'month' => $mesSiguiente->month]) }}" title="Mes siguiente" class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100">
                        <i data-lucide="chevron-right" class="h-4 w-4"></i>
                    </a>
                    <a href="{{ route('personal.bitacora.index', ['year' => $anioSiguiente->year, 'month' => $anioSiguiente->month]) }}" title="Año siguiente" class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100">
                        <i data-lucide="chevrons-right" class="h-4 w-4"></i>
                    </a>
                </div>

                <form method="POST" action="{{ route('personal.bitacora.quota.store') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-700" title="Cuota de horas a laborar este mes — se define manualmente cada mes, no es un valor fijo">
                    @csrf
                    <input type="hidden" name="year" value="{{ $year }}">
                    <input type="hidden" name="month" value="{{ $month }}">
                    Cuota mensual:
                    <input
                        type="number" name="quota_hours" min="0" step="0.5"
                        value="{{ old('quota_hours', $cuotaHoras + 0) }}"
                        onchange="this.form.requestSubmit()"
                        class="w-14 rounded border border-slate-300 bg-white px-1 py-0.5 text-center text-xs font-semibold text-slate-800"
                    >
                    h
                </form>

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
    <div class="compact-table-wrapper rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="table-scroll-container">
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
                            <td class="sticky-col whitespace-nowrap px-3 py-1.5 text-xs font-medium {{ $dia['festivo'] ? 'text-red-500' : 'text-slate-500' }}">
                                {{ $dia['numero'] }} {{ $dia['nombre'] }}
                            </td>

                            @foreach ($empleados as $emp)
                                @php
                                    $c = $celdas[$emp->id][$dia['numero']];
                                    $fechaDia = sprintf('%04d-%02d-%02d', $year, $month, $dia['numero']);
                                    $reabrirEsta = $errors->any() && (int) old('employee_id') === $emp->id && old('date') === $fechaDia;
                                @endphp
                                <td class="p-0 text-center">
                                    <div
                                        x-data="{
                                            tip: false, modal: {{ $reabrirEsta ? 'true' : 'false' }}, estilo: '',
                                            programada: @js($c['programada']),
                                            reportada: @js($c['reportada']),
                                            corregida: @js($c['corregida']),
                                            comentario: @js($c['comentario']),
                                            nuevaCorreccion: @js($reabrirEsta ? old('corrected_value') : ($c['corregida'] ?? '')),
                                            nuevoComentario: @js($reabrirEsta ? old('comment') : ($c['comentario'] ?? '')),
                                            get final() { return this.corregida !== null && this.corregida !== '' ? this.corregida : this.programada; },
                                            get esTexto() { return this.final !== null && this.final !== '' && isNaN(Number(this.final)); },
                                            get alerta() { return !this.esTexto && (this.final === null || this.final === '' || Number(this.final) === 0); },
                                            abrirModal() { this.nuevaCorreccion = this.corregida ?? ''; this.nuevoComentario = this.comentario ?? ''; this.modal = true; },
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
                                                <form method="POST" action="{{ route('personal.bitacora.entries.store') }}">
                                                    @csrf
                                                    <input type="hidden" name="employee_id" value="{{ $emp->id }}">
                                                    <input type="hidden" name="date" value="{{ $fechaDia }}">

                                                    <h3 class="mb-1 text-sm font-bold text-slate-900">{{ $emp->nickname }} — día {{ $dia['numero'] }} ({{ $dia['nombre'] }})</h3>
                                                    <p class="mb-3 text-xs text-slate-500">
                                                        Programada: <span x-text="programada ?? '—'"></span> ·
                                                        Reportada: <span x-text="reportada ?? '—'"></span> ·
                                                        Corregida: <span x-text="corregida ?? '—'"></span>
                                                    </p>

                                                    @if ($reabrirEsta)
                                                        <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                                                            <ul class="list-inside list-disc space-y-0.5">
                                                                @foreach ($errors->all() as $error)
                                                                    <li>{{ $error }}</li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endif

                                                    <label class="mb-1 block text-xs font-medium text-slate-600">Corregir hora</label>
                                                    <input type="text" name="corrected_value" x-model="nuevaCorreccion" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                                    <p class="mt-1 text-[11px] text-slate-400">
                                                        Texto libre a propósito: admite números y códigos de letra (L = licencia, y otros que se definan).
                                                        No borra la hora programada — queda disponible en el tooltip.
                                                    </p>

                                                    <label class="mb-1 mt-3 block text-xs font-medium text-slate-600">Comentario</label>
                                                    <textarea name="comment" x-model="nuevoComentario" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Ej. jornada nocturna-1 ED-11EN"></textarea>

                                                    <div class="mt-4 flex justify-end gap-2">
                                                        <button type="button" @click="modal = false" class="rounded-xl border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">Cancelar</button>
                                                        <button type="submit" class="rounded-xl bg-[#d55b20] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#b8481a]">Guardar</button>
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
                            <td class="px-2 py-1.5 text-center text-xs">{{ $totales[$emp->id] !== null ? round($totales[$emp->id], 2) : '—' }}</td>
                        @endforeach
                    </tr>
                    <tr class="bg-slate-50 text-xs text-slate-500">
                        <td class="sticky-col whitespace-nowrap px-3 py-1.5">Horas a laborar</td>
                        @foreach ($empleados as $emp)
                            <td class="px-2 py-1.5 text-center">{{ $cuotaHoras + 0 }}</td>
                        @endforeach
                    </tr>
                    <tr class="bg-slate-50 text-xs font-semibold text-red-500">
                        <td class="sticky-col whitespace-nowrap px-3 py-1.5">Extras</td>
                        @foreach ($empleados as $emp)
                            <td class="px-2 py-1.5 text-center">{{ $totales[$emp->id] !== null ? round($totales[$emp->id] - $cuotaHoras, 2) : '—' }}</td>
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
    </div>
    @endif
</div>
@endsection
