@extends('layouts.personal')

@section('title', 'Diario de Campo')

@php
    $fechaLabel = \Carbon\Carbon::parse($fecha)->translatedFormat('d \d\e F \d\e Y');
@endphp

@section('content')
<style>
    .inline-edit-trigger {
        cursor: pointer;
        border-radius: 0.65rem;
        padding: 0.2rem 0.35rem;
        transition: background-color 0.18s ease;
        display: inline-block;
        min-width: 36px;
        /* Sin esto, un inline-block sin ancho maximo crece para caber el
           texto SIN partir linea (ej. executed_description/comments
           largos) — eso arrastra toda la columna, y como las columnas de
           una tabla comparten ancho entre filas, arrastra la tabla
           entera (medido: 5360px con un solo comentario largo). */
        max-width: 20rem;
        white-space: normal;
        overflow-wrap: break-word;
        word-break: break-word;
    }

    .inline-edit-trigger:hover {
        background: rgb(241 245 249);
    }

    .inline-edit-box {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }

    .inline-edit-input,
    .inline-edit-textarea {
        width: 100%;
        min-width: 70px;
        border: 1px solid rgb(203 213 225);
        border-radius: 0.65rem;
        padding: 0.35rem 0.55rem;
        font-size: 0.82rem;
        line-height: 1.2rem;
        color: rgb(51 65 85);
        background: white;
    }

    .inline-edit-textarea {
        min-height: 4.5rem;
        resize: vertical;
        font-family: inherit;
    }

    .inline-edit-input:focus,
    .inline-edit-textarea:focus {
        outline: none;
        border-color: #d94d33;
        box-shadow: 0 0 0 3px rgba(217, 77, 51, 0.15);
    }

    .inline-edit-actions {
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    .inline-edit-btn {
        border: 0;
        border-radius: 0.55rem;
        width: 26px;
        height: 26px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.82rem;
        font-weight: 700;
        transition: 0.18s ease;
        cursor: pointer;
    }

    .inline-edit-btn-cancel {
        background: rgb(248 250 252);
        color: rgb(71 85 105);
        border: 1px solid rgb(226 232 240);
    }

    .inline-edit-btn-cancel:hover {
        background: rgb(241 245 249);
    }

    .inline-edit-btn-save {
        background: rgb(22 163 74);
        color: white;
    }

    .inline-edit-btn-save:hover {
        background: rgb(21 128 61);
    }

    .inline-edit-loading {
        opacity: 0.6;
        pointer-events: none;
    }

    .inline-toast {
        position: fixed;
        right: 18px;
        bottom: 24px;
        z-index: 100;
        min-width: 220px;
        max-width: 340px;
        border-radius: 0.95rem;
        padding: 0.8rem 1rem;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
        font-size: 0.9rem;
        font-weight: 600;
        transform: translateY(10px);
        opacity: 0;
        pointer-events: none;
        transition: 0.22s ease;
    }

    .inline-toast.show {
        transform: translateY(0);
        opacity: 1;
    }

    .inline-toast-success {
        background: rgb(240 253 244);
        color: rgb(22 101 52);
        border: 1px solid rgb(187 247 208);
    }

    .inline-toast-error {
        background: rgb(254 242 242);
        color: rgb(153 27 27);
        border: 1px solid rgb(254 202 202);
    }

    /* Scrollbar lateral SIEMPRE visible (pedido 2026-09-19) — el patron
       por defecto de layouts.personal (.table-scroll-container) la
       oculta a proposito (scrollbar-width:none + ::-webkit-scrollbar
       height:0), apoyandose solo en el texto "Desliza horizontalmente".
       Este patron ya existia, pensado para esta pantalla, en el mockup
       preview-personal/_layout.blade.php ("Para tablas grandes
       (Bitacora, Diario de Campo)") — se copia aqui tal cual en vez de
       reinventarlo. layouts/personal.blade.php ya soporta esta clase en
       el exportador de imagen (busca .table-scroll-container O
       .scroll-container-visible), asi que "Copiar como imagen" sigue
       funcionando sin cambios adicionales. */
    .scroll-container-visible {
        overflow: auto;
        max-height: 65vh;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f1f5f9;
    }
    .scroll-container-visible::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }
    .scroll-container-visible::-webkit-scrollbar-track {
        background: #f1f5f9;
    }
    .scroll-container-visible::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 9999px;
    }
    .scroll-container-visible::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>
<div
    class="mx-auto max-w-[1900px] space-y-4"
    x-data="diarioCampoPage({
        fecha: @js($fecha),
        {{-- Ver nota en personal/programacion/index.blade.php: fecha real
             de "hoy" calculada en el servidor (zona America/Bogota), no
             con new Date() del navegador (toISOString() siempre da UTC). --}}
        hoyReal: @js(today()->toDateString()),
        diasConDatosUrlBase: @js(route('personal.programacion.dias-con-datos')),
    })"
>
    <div id="diarioCampoToast" class="inline-toast" role="status" aria-live="polite"></div>

    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900 md:text-2xl">Diario de Campo</h1>
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
        <div id="areaExportable" class="space-y-2">
            <p class="text-sm font-semibold text-slate-700">Diario de Campo — {{ $fechaLabel }}</p>
            <div class="compact-table-wrapper rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="scroll-container-visible">
            <table class="preventive-table divide-y divide-slate-200 text-sm">
                <thead class="sticky-table-head bg-slate-50">
                    <tr>
                        <th class="whitespace-nowrap px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Empresa</th>
                        <th class="whitespace-nowrap px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Equipo</th>
                        <th class="whitespace-nowrap px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Proceso</th>
                        <th class="px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500" style="min-width:14rem">Actividad programada</th>
                        <th class="px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500" style="min-width:14rem">Actividad ejecutada</th>
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
                        @php $grupos = $a->diaryHourGroups(); @endphp
                        @foreach ($grupos as $grupo)
                            <tr class="align-top hover:bg-slate-50">
                                <td class="whitespace-nowrap px-3 py-2.5">{{ $a->company->name }}</td>
                                <td class="whitespace-nowrap px-3 py-2.5 text-slate-600">{{ $a->team ?: '—' }}</td>
                                <td class="px-3 py-2.5 text-slate-600">
                                    @if ($puedeEditar)
                                        <div class="inline-editable" data-activity-id="{{ $a->id }}" data-field="process" data-value="{{ $a->process ?? '' }}">
                                            <span class="inline-edit-trigger">{{ $a->process ?: '—' }}</span>
                                        </div>
                                    @else
                                        {{ $a->process ?: '—' }}
                                    @endif
                                </td>
                                <td class="px-3 py-2.5">
                                    <div class="font-medium text-slate-800">{{ $a->description }}</div>
                                </td>
                                <td class="px-3 py-2.5 text-slate-600" style="min-width:14rem">
                                    {{-- Seccion 14.23: el comentario del supervisor (campo
                                         "comments", el mismo que llena en "Ver como
                                         supervisor") ES la actividad ejecutada — no
                                         "executed_description" (ese campo ya no tiene
                                         columna visible aqui). Misma fuente que la
                                         columna "Comentarios" de mas adelante, a
                                         proposito (confirmado con el usuario). --}}
                                    @if ($puedeEditar)
                                        <div class="inline-editable" data-multiline="1" data-activity-id="{{ $a->id }}" data-field="comments" data-value="{{ $a->comments ?? '' }}">
                                            <span class="inline-edit-trigger">{{ $a->comments ?: '—' }}</span>
                                        </div>
                                    @else
                                        {{ $a->comments ?: '—' }}
                                    @endif
                                </td>
                                <td class="px-3 py-2.5 text-slate-600">{{ $grupo['personas']->pluck('nickname')->implode(', ') ?: '—' }}</td>
                                <td class="px-3 py-2.5 text-center text-slate-500">{{ $grupo['personas']->count() ?: '—' }}</td>
                                <td class="whitespace-nowrap px-3 py-2.5 font-medium">
                                    @php $horasDisplay = $grupo['horas'] !== null ? $grupo['horas'] + 0 : null; @endphp
                                    @if ($puedeEditar)
                                        <div class="inline-editable" data-activity-id="{{ $a->id }}" data-field="corrected_hours" data-value="{{ $horasDisplay ?? '' }}">
                                            <span class="inline-edit-trigger">{{ $horasDisplay ?? '—' }}</span>
                                        </div>
                                    @else
                                        {{ $horasDisplay ?? '—' }}
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-3 py-2.5 text-slate-600">{{ $a->shift }}</td>
                                <td class="px-3 py-2.5 text-slate-600">
                                    @if ($puedeEditar)
                                        <div class="inline-editable" data-multiline="1" data-activity-id="{{ $a->id }}" data-field="comments" data-value="{{ $a->comments ?? '' }}">
                                            <span class="inline-edit-trigger">{{ $a->comments ?: '—' }}</span>
                                        </div>
                                    @else
                                        {{ $a->comments ?: '—' }}
                                    @endif
                                </td>
                                <td class="bg-slate-100/40 px-2 py-2.5 text-slate-600">
                                    @if ($puedeEditar)
                                        <div class="inline-editable" data-activity-id="{{ $a->id }}" data-field="zcom" data-value="{{ $a->zcom ?? '' }}">
                                            <span class="inline-edit-trigger">{{ $a->zcom ?: '—' }}</span>
                                        </div>
                                    @else
                                        {{ $a->zcom ?: '—' }}
                                    @endif
                                </td>
                                <td class="bg-slate-100/40 px-2 py-2.5 text-slate-600">
                                    @if ($puedeEditar)
                                        <div class="inline-editable" data-activity-id="{{ $a->id }}" data-field="line_code" data-value="{{ $a->line_code ?? '' }}">
                                            <span class="inline-edit-trigger">{{ $a->line_code ?: '—' }}</span>
                                        </div>
                                    @else
                                        {{ $a->line_code ?: '—' }}
                                    @endif
                                </td>
                                <td class="bg-slate-100/40 px-2 py-2.5 text-slate-600">
                                    @if ($puedeEditar)
                                        <div class="inline-editable" data-activity-id="{{ $a->id }}" data-field="ot_sap" data-value="{{ $a->ot_sap ?? '' }}">
                                            <span class="inline-edit-trigger">{{ $a->ot_sap ?: '—' }}</span>
                                        </div>
                                    @else
                                        {{ $a->ot_sap ?: '—' }}
                                    @endif
                                </td>
                                <td class="bg-slate-100/40 px-2 py-2.5 text-slate-600">
                                    @if ($puedeEditar)
                                        <div class="inline-editable" data-activity-id="{{ $a->id }}" data-field="acta_entrega" data-value="{{ $a->acta_entrega ?? '' }}">
                                            <span class="inline-edit-trigger">{{ $a->acta_entrega ?: '—' }}</span>
                                        </div>
                                    @else
                                        {{ $a->acta_entrega ?: '—' }}
                                    @endif
                                </td>
                                <td class="bg-slate-100/40 px-2 py-2.5 text-slate-600">
                                    @if ($puedeEditar)
                                        <div class="inline-editable" data-activity-id="{{ $a->id }}" data-field="we_code" data-value="{{ $a->we_code ?? '' }}">
                                            <span class="inline-edit-trigger">{{ $a->we_code ?: '—' }}</span>
                                        </div>
                                    @else
                                        {{ $a->we_code ?: '—' }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
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
    function diarioCampoPage({ fecha, hoyReal, diasConDatosUrlBase }) {
        const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        const [fy, fm] = fecha.split('-').map(Number);
        const pad2 = (n) => String(n).padStart(2, '0');
        const fechaAString = (year, month, day) => `${year}-${pad2(month + 1)}-${pad2(day)}`;

        return {
            ...imageExporterMixin('diario-de-campo'),
            fecha,
            hoyStr: hoyReal,

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

    // Edicion en linea celda por celda (seccion 14.22) — mismo patron que
    // AdminPreventiveReportController::inlineUpdate() /
    // resources/views/admin/reports/preventive/show.blade.php, adaptado a
    // Activity. A diferencia de ese patron (que solo actualiza el DOM de
    // la celda editada), aqui CUALQUIER guardado exitoso recarga la
    // pagina: varios campos (Proceso, Comentarios, ZCOM...) se repiten
    // identicos en todas las filas generadas de una misma actividad
    // (cuando tiene mas de un grupo de horas), y "Horas" en particular
    // escribe en corrected_hours, que puede cambiar cuantas filas genera
    // esa actividad (Activity::diaryHourGroups()) — mantener todo eso
    // sincronizado sin recargar seria fragil, un reload es simple y
    // siempre correcto.
    const DIARIO_CSRF_TOKEN = @js(csrf_token());

    function diarioEscapeInlineValue(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function diarioShowToast(message, type) {
        const el = document.getElementById('diarioCampoToast');
        if (!el) return;
        el.className = 'inline-toast ' + (type === 'error' ? 'inline-toast-error' : 'inline-toast-success');
        el.textContent = message;
        el.classList.add('show');
        clearTimeout(window.__diarioToastTimeout);
        window.__diarioToastTimeout = setTimeout(() => el.classList.remove('show'), 4200);
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

        const field_el = container.querySelector(multiline ? '.inline-edit-textarea' : '.inline-edit-input');
        const cancelBtn = container.querySelector('.inline-edit-btn-cancel');
        const saveBtn = container.querySelector('.inline-edit-btn-save');

        field_el?.focus();
        field_el?.select?.();

        field_el?.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                diarioRestoreInlineCell(container, originalValue);
            }
            if (event.key === 'Enter' && !multiline) {
                event.preventDefault();
                diarioSaveInlineCell(container, activityId, field, field_el.value);
            }
        });

        cancelBtn?.addEventListener('click', () => diarioRestoreInlineCell(container, originalValue));
        saveBtn?.addEventListener('click', () => diarioSaveInlineCell(container, activityId, field, field_el?.value ?? ''));
    }

    function diarioRestoreInlineCell(container, value) {
        container.dataset.editing = '0';
        container.dataset.value = value ?? '';
        const displayValue = value && String(value).trim() !== '' ? value : '—';
        container.innerHTML = `<span class="inline-edit-trigger">${diarioEscapeInlineValue(displayValue)}</span>`;
    }

    async function diarioSaveInlineCell(container, activityId, field, value) {
        container.classList.add('inline-edit-loading');

        try {
            const url = "{{ route('personal.diario-campo.inline-update', ['activity' => '__ACTIVITY_ID__']) }}".replace('__ACTIVITY_ID__', activityId);
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

            // Reload en vez de actualizar el DOM in-place — ver comentario
            // arriba de DIARIO_CSRF_TOKEN.
            window.location.reload();
        } catch (error) {
            container.classList.remove('inline-edit-loading');
            diarioShowToast(error.message || 'Ocurrió un error al guardar.', 'error');
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
