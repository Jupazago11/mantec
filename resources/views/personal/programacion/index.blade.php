@extends('layouts.personal')

@section('title', 'Programación')

@php
    $fechaLabel = \Carbon\Carbon::parse($fecha)->translatedFormat('d \d\e F \d\e Y');
@endphp

@section('content')
<div
    x-data="programacionPage({
        empleados: @js($empleados),
        categorias: @js($categorias),
        supervisores: @js($supervisores),
        equipos: @js($equipos),
        procesos: @js($procesos),
        gruposResponsables: @js($gruposResponsables),
        diasConDatosUrlBase: @js(route('personal.programacion.dias-con-datos')),
        fecha: @js($fecha),
        {{-- Fecha real de "hoy" calculada en el servidor (zona America/Bogota,
             config/app.php) — no se usa new Date() en el navegador porque
             toISOString() siempre convierte a UTC sin importar la zona del
             navegador: entre las 19:00 y medianoche hora Colombia marcaria
             "hoy" un dia adelantado para cualquier usuario. --}}
        hoyReal: @js(today()->toDateString()),
        storeUrl: @js(route('personal.programacion.store')),
        updateUrlTemplate: @js(route('personal.programacion.update', ['activity' => '__ID__'])),
        destroyUrlTemplate: @js(route('personal.programacion.destroy', ['activity' => '__ID__'])),
        csrfToken: @js(csrf_token()),
        {{-- Sin empresa por defecto marcada, el <select> nativo igual
             muestra la primera opcion del listado (comportamiento del
             navegador) — antes eso no importaba porque el <form> nativo
             enviaba lo que se viera en pantalla; en AJAX se envia
             formActividad.company_id tal cual, asi que tiene que arrancar
             en el mismo valor que el <select> muestra por defecto (bug
             corregido 2026-09-19, ver seccion 14.14). --}}
        defaultCompanyId: @js($empresas->firstWhere('is_default', true)?->id ?? $empresas->first()?->id),
        actividadesIniciales: @js($actividadesJs),
        horasAcumuladasPorId: @js($horasAcumuladasPorId),
    })"
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
                        <span>{{ $fechaLabel }}</span>
                    </button>
                    @if ($fecha === today()->toDateString())
                        <span class="text-xs text-slate-400">(hoy)</span>
                    @endif
                    <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $isEditable ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                        {{ $isEditable ? 'Editable' : 'Solo lectura' }}
                    </span>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    @click="exportarComoImagen(@js($fecha))"
                    :disabled="exportando || actividades.length === 0"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    title="Genera una imagen de la programación para pegarla en WhatsApp (o descargarla en móvil)"
                >
                    <i data-lucide="image-down" class="h-4 w-4" x-show="!exportando"></i>
                    <i data-lucide="loader-2" class="h-4 w-4 animate-spin" x-show="exportando" x-cloak></i>
                    <span x-text="exportando ? 'Generando...' : 'Copiar como imagen'"></span>
                </button>
                <button
                    @click="abrirModal()"
                    @disabled(! $isEditable)
                    class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white {{ $isEditable ? 'bg-[#d55b20] hover:bg-[#b8481a]' : 'cursor-not-allowed bg-slate-300' }}"
                >
                    <i data-lucide="plus" class="h-4 w-4"></i> Nueva actividad
                </button>
            </div>
        </div>
    </div>

    <template x-if="actividades.length === 0">
        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-400">
            No hay actividades programadas para <span class="font-medium text-slate-500">{{ $fechaLabel }}</span>.
            <br>Usa el calendario para navegar a otro día, o crea la primera actividad de hoy.
        </div>
    </template>

    {{-- Tabla reactiva Alpine (pedido 2026-09-19, mismo patron que
         Empleados/Roles): ya no es un @foreach de Blade — actividades vive
         en JS (guardarActividad()/eliminarActividad() la mutan en memoria)
         para que crear/editar/eliminar no recargue la pagina. La agrupacion
         por group_number (con "Sin grupo asignado" al final) se replica en
         gruposOrdenados(), mismo criterio que antes tenia el controller. --}}
    <template x-if="actividades.length > 0">
        {{-- areaExportable: lo que captura "Copiar como imagen". --}}
        <div id="areaExportable" class="space-y-2">
            <p class="text-sm font-semibold capitalize text-slate-700">Programación — {{ $fechaLabel }}</p>
            <div class="compact-table-wrapper rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="table-scroll-container">
            <table class="preventive-table divide-y divide-slate-200 text-sm">
                <thead class="sticky-table-head bg-slate-50">
                    <tr>
                        <th class="sticky-col px-3 py-2 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">Acciones</th>
                        <th class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Empresa</th>
                        <th class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Equipo</th>
                        <th class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Proceso</th>
                        <th class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500" style="min-width:13rem">Actividad</th>
                        <th class="px-3 py-2 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">Personas</th>
                        <th class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Horas</th>
                        <th class="px-3 py-2 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500" title="Jornada">Jorn.</th>
                        <th class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500" style="min-width:9rem">Nombre de las personas</th>
                        <th class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Resp.</th>
                    </tr>
                </thead>
                <template x-for="grupo in gruposOrdenados()" :key="grupo.grupoNum ?? 'sin-grupo'">
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <tr class="bg-slate-800">
                            <td colspan="10" class="px-3 py-1.5">
                                <span class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-white">
                                    <i data-lucide="users" class="h-3.5 w-3.5"></i>
                                    <span x-text="grupo.grupoNum ? `Grupo ${grupo.grupoNum}` : 'Sin grupo asignado'"></span>
                                </span>
                            </td>
                        </tr>
                        <template x-for="a in grupo.filas" :key="a.id">
                            <tr class="hover:bg-slate-50">
                                <td class="sticky-col px-3 py-2">
                                    <template x-if="a.modificable">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button
                                                type="button"
                                                @click="editarActividad(a)"
                                                class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-[#d55b20]" title="Editar"
                                            >
                                                <i data-lucide="pencil" class="h-4 w-4"></i>
                                            </button>
                                            <button
                                                type="button"
                                                @click="eliminarActividad(a)"
                                                :disabled="a.eliminando"
                                                class="rounded-lg p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-600 disabled:cursor-not-allowed disabled:opacity-50" title="Eliminar"
                                            >
                                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                                            </button>
                                        </div>
                                    </template>
                                    <template x-if="!a.modificable">
                                        <span class="block text-center text-slate-300" :title="a.closed ? 'Ya fue cerrada en Diario de Campo' : 'Fuera de la ventana de edición'">—</span>
                                    </template>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap" x-text="a.company_name"></td>
                                <td class="px-3 py-2 whitespace-nowrap text-slate-600" x-text="a.team || '—'"></td>
                                <td class="px-3 py-2 whitespace-nowrap text-slate-600" x-text="a.process || '—'"></td>
                                <td class="px-3 py-2">
                                    <span
                                        class="mr-1.5 inline-flex h-4 w-4 items-center justify-center rounded-full align-[-2px] text-[10px] font-bold"
                                        :class="a.activity_type === 'P' ? 'bg-[#d55b20] text-white' : 'bg-slate-300 text-slate-700'"
                                        :title="a.activity_type === 'P' ? 'Actividad primaria' : 'Actividad secundaria'"
                                        x-text="a.activity_type"
                                    ></span>
                                    <span x-text="a.description"></span>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-center text-slate-600" x-text="a.personas.length || '—'"></td>
                                <td class="px-3 py-2 whitespace-nowrap" x-text="a.estimated_hours ?? '—'"></td>
                                <td class="px-3 py-2 whitespace-nowrap text-center text-slate-600" :title="a.shift" x-text="a.shift === 'Nocturno' ? 'N' : 'D'"></td>
                                <td class="px-3 py-2 text-slate-600">
                                    <span x-show="a.personas.length === 0">—</span>
                                    {{-- Hover con nombre completo + horas acumuladas del mes
                                         (pedido 2026-09-19, seccion 14.17) — mismo patron de
                                         popover teleportado que los combobox del modal
                                         (posicionarPopover()), solo que disparado por
                                         mouseenter/mouseleave en vez de focus/click. --}}
                                    <template x-for="(p, idx) in a.personas" :key="p.id">
                                        <span
                                            class="relative inline-block"
                                            x-data="{ open: false, estilo: '' }"
                                            @mouseleave="open = false"
                                        >
                                            {{-- Separador ", " fusionado en el mismo span que el
                                                 nombre (antes era un <span> aparte) — dos <span>
                                                 vecinos sin espacio de por medio en el HTML fuente
                                                 se perdia visualmente tanto en el navegador como en
                                                 la imagen exportada (html2canvas). Un solo nodo de
                                                 texto "Nombre, " no deja margen para que se pierda. --}}
                                            <span
                                                @mouseenter="open = true; estilo = posicionarPopover($el, 220, 64)"
                                                class="cursor-default border-b border-dotted border-slate-300"
                                                {{-- espacio duro (U+00A0), no uno normal: html2canvas
                                                     (usado en "Copiar como imagen") recorta un espacio
                                                     normal al final del texto de un <span>, dejando el
                                                     separador pegado en la imagen exportada aunque en la
                                                     pagina viva se vea bien. --}}
                                                x-text="p.nickname + (idx < a.personas.length - 1 ? ', ' : '')"
                                            ></span>
                                            <template x-teleport="body">
                                                <div x-show="open" x-cloak x-transition :style="estilo" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs shadow-xl">
                                                    <p class="font-semibold text-slate-800" x-text="p.nombre"></p>
                                                    <p class="mt-0.5 text-slate-500" x-text="horasAcumuladasCardTexto(p.id)"></p>
                                                </div>
                                            </template>
                                        </span>
                                    </template>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-slate-500">
                                    <span x-show="!a.responsible_employee_id">—</span>
                                    <span
                                        x-show="a.responsible_employee_id"
                                        class="relative inline-block"
                                        x-data="{ open: false, estilo: '' }"
                                        @mouseleave="open = false"
                                    >
                                        <span
                                            @mouseenter="open = true; estilo = posicionarPopover($el, 220, 64)"
                                            class="cursor-default border-b border-dotted border-slate-300"
                                            x-text="a.responsible_nickname"
                                        ></span>
                                        <template x-teleport="body">
                                            <div x-show="open" x-cloak x-transition :style="estilo" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs shadow-xl">
                                                <p class="font-semibold text-slate-800" x-text="a.responsible_nombre"></p>
                                                <p class="mt-0.5 text-slate-500" x-text="horasAcumuladasCardTexto(a.responsible_employee_id)"></p>
                                            </div>
                                        </template>
                                    </span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </template>
            </table>
            </div>
            </div>
        </div>
    </template>

    <p class="mt-4 text-xs text-slate-400">
        "P" / "S" junto a la Actividad = la actividad completa es primaria o secundaria (aplica a todas las personas
        de esa fila) · una persona solo puede estar en una actividad primaria por día, pero en varias secundarias.
    </p>

    {{-- Toast de resultado de la exportacion --}}
    <div x-show="mensajeExport" x-cloak x-transition class="fixed bottom-4 right-4 z-50 max-w-xs rounded-xl bg-slate-900 px-4 py-3 text-sm text-white shadow-xl" x-text="mensajeExport"></div>

    {{-- Calendario para navegar entre fechas. Los dias con datos se piden
         por AJAX a /personal/programacion/dias-con-datos (no se puede
         cargar todo el historial en memoria como hacia el mockup). --}}
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
            <p class="mb-4 text-center text-xs text-slate-500">Selecciona un día para ver su programación.</p>

            <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                <template x-for="d in ['LUN','MAR','MIE','JUE','VIE','SAB','DOM']" :key="d">
                    <div x-text="d"></div>
                </template>
            </div>
            <div class="mt-1 grid grid-cols-7 gap-1">
                <template x-for="(celda, idx) in diasCalendario()" :key="idx">
                    <a
                        :href="celda ? '{{ route('personal.programacion.index') }}?date=' + celda.fecha : null"
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
                <span class="h-3 w-3 rounded-full bg-[#d55b20]"></span> Días con programación registrada
            </div>
        </div>
    </div>

    {{-- Modal nueva/editar actividad — un solo formulario dual, mismo
         patron que Empleados (formAction/formMethod dinamicos). Los campos
         simples usan x-model (antes eran old()/selected de Blade, pero eso
         solo alcanza para el flujo de creacion) — el buscador de personas
         ya era Alpine. --}}
    <div x-show="modalAbierto" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 p-4" style="top:0;left:0;height:100vh;width:100vw;">
        <div @click.outside="modalAbierto = false" x-show="modalAbierto" x-transition class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
            <form @submit.prevent="guardarActividad()">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-slate-900" x-text="modoEdicion ? 'Editar actividad' : 'Nueva actividad'"></h2>
                    <button type="button" @click="modalAbierto = false" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100"><i data-lucide="x" class="h-5 w-5"></i></button>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Empresa <span class="text-red-500">*</span></label>
                        <select name="company_id" x-model="formActividad.company_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            @foreach ($empresas as $e)
                                <option value="{{ $e->id }}">{{ $e->name }}{{ $e->is_default ? ' (por defecto)' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Equipo</label>
                        {{-- Combobox de texto libre (pedido 2026-09-18): el valor que
                             se guarda es exactamente lo escrito/seleccionado, sin
                             catalogo con FK — las sugerencias solo evitan variantes
                             tipo "Cemento"/"CEMENTO" para que Equipo/Proceso queden
                             consistentes al filtrar despues. Mismo patron de popover
                             teleportado que Responsable/Personas de este mismo modal. --}}
                        <div class="relative" x-data="{ open: false, estilo: '' }">
                            <div x-ref="teamTrigger">
                                <input
                                    type="text"
                                    name="team"
                                    x-model="formActividad.team"
                                    @focus="open = true; estilo = posicionarPopover($el, $el.offsetWidth, 208)"
                                    @input="open = true"
                                    autocomplete="off"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                                    placeholder="Ej. TP1 Trituradora"
                                >
                            </div>
                            <template x-teleport="body">
                                <div
                                    x-show="open" x-cloak x-transition
                                    @click.outside="if (!$refs.teamTrigger.contains($event.target)) open = false"
                                    @click.stop
                                    :style="estilo"
                                    class="max-h-52 overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-xl"
                                >
                                    <template x-for="op in equiposFiltrados()" :key="op">
                                        <button type="button" @click="formActividad.team = op; open = false" class="block w-full truncate px-3 py-1.5 text-left text-sm hover:bg-slate-50" x-text="op"></button>
                                    </template>
                                    <p class="px-3 py-3 text-center text-xs text-slate-400" x-show="equiposFiltrados().length === 0">
                                        Sin coincidencias — se guardará el texto escrito.
                                    </p>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Proceso</label>
                        <div class="relative" x-data="{ open: false, estilo: '' }">
                            <div x-ref="processTrigger">
                                <input
                                    type="text"
                                    name="process"
                                    x-model="formActividad.process"
                                    @focus="open = true; estilo = posicionarPopover($el, $el.offsetWidth, 208)"
                                    @input="open = true"
                                    autocomplete="off"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                                    placeholder="Ej. Cambiar banda"
                                >
                            </div>
                            <template x-teleport="body">
                                <div
                                    x-show="open" x-cloak x-transition
                                    @click.outside="if (!$refs.processTrigger.contains($event.target)) open = false"
                                    @click.stop
                                    :style="estilo"
                                    class="max-h-52 overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-xl"
                                >
                                    <template x-for="op in procesosFiltrados()" :key="op">
                                        <button type="button" @click="formActividad.process = op; open = false" class="block w-full truncate px-3 py-1.5 text-left text-sm hover:bg-slate-50" x-text="op"></button>
                                    </template>
                                    <p class="px-3 py-3 text-center text-xs text-slate-400" x-show="procesosFiltrados().length === 0">
                                        Sin coincidencias — se guardará el texto escrito.
                                    </p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Fila compacta: campos cortos (numero/toggle), uno al lado del
                     otro en vez de ocupar media fila cada uno. --}}
                <div class="mt-4 flex flex-wrap items-start gap-4">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Grupo</label>
                        <input
                            type="number"
                            name="group_number"
                            x-model.number="formActividad.group_number"
                            @change="autocompletarResponsablePorGrupo()"
                            min="1"
                            class="w-20 rounded-lg border border-slate-300 px-3 py-2 text-sm"
                            placeholder="Ej. 1"
                        >
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Jornada <span class="text-red-500">*</span></label>
                        <div class="inline-flex rounded-lg border border-slate-300 p-0.5">
                            <button
                                type="button"
                                @click="formActividad.shift = 'Diurno'"
                                class="rounded-md px-3 py-1.5 text-sm font-medium transition"
                                :class="formActividad.shift === 'Diurno' ? 'bg-[#d55b20] text-white' : 'text-slate-600 hover:bg-slate-50'"
                            >Diurno</button>
                            <button
                                type="button"
                                @click="formActividad.shift = 'Nocturno'"
                                class="rounded-md px-3 py-1.5 text-sm font-medium transition"
                                :class="formActividad.shift === 'Nocturno' ? 'bg-[#d55b20] text-white' : 'text-slate-600 hover:bg-slate-50'"
                            >Nocturno</button>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Horas estimadas</label>
                        <input type="number" name="estimated_hours" x-model="formActividad.estimated_hours" step="0.5" min="0" class="w-20 rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Ej. 10">
                    </div>
                    <div>
                        <label class="mb-1 flex items-center gap-1 text-xs font-medium text-slate-600">
                            Tipo de actividad <span class="text-red-500">*</span>
                            {{-- Mismo patron que /admin/managed-conditions (campo Criticidad):
                                 CSS puro con group-hover, sin Alpine/JS. --}}
                            <span class="relative inline-block group">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 cursor-pointer text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z" />
                                </svg>
                                <span class="pointer-events-none absolute left-0 top-6 z-10 w-64 rounded-xl border border-slate-200 bg-white p-3 text-left text-xs font-normal normal-case text-slate-600 shadow-lg opacity-0 transition group-hover:opacity-100">
                                    Aplica a todas las personas de esta actividad. Cada persona solo puede tener
                                    una actividad primaria por día — el sistema lo valida al guardar.
                                </span>
                            </span>
                        </label>
                        <div class="inline-flex rounded-lg border border-slate-300 p-0.5">
                            <button
                                type="button"
                                @click="formActividad.activity_type = 'P'"
                                class="rounded-md px-3 py-1.5 text-sm font-medium transition"
                                :class="formActividad.activity_type === 'P' ? 'bg-[#d55b20] text-white' : 'text-slate-600 hover:bg-slate-50'"
                            >Primaria</button>
                            <button
                                type="button"
                                @click="formActividad.activity_type = 'S'"
                                class="rounded-md px-3 py-1.5 text-sm font-medium transition"
                                :class="formActividad.activity_type === 'S' ? 'bg-[#d55b20] text-white' : 'text-slate-600 hover:bg-slate-50'"
                            >Secundaria</button>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="mb-2 block text-xs font-medium text-slate-600">Responsable (supervisor)</label>

                        <div x-show="formActividad.responsible_employee_id" class="mb-2">
                            <span class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                                <span class="border-b-2 border-[#d55b20] pb-0.5" x-text="nombreSupervisor(formActividad.responsible_employee_id)"></span>
                                <button type="button" @click="formActividad.responsible_employee_id = ''; responsableAutocompletado = false" class="flex h-5 w-5 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-red-500">
                                    <i data-lucide="x" class="h-3.5 w-3.5"></i>
                                </button>
                            </span>
                        </div>

                        <div class="relative" x-data="{ open: false, estilo: '' }">
                            <div class="relative" x-ref="respTrigger">
                                <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z" />
                                </svg>
                                <input
                                    type="text"
                                    x-model="busquedaResponsable"
                                    @focus="open = true; estilo = posicionarPopover($el, $el.offsetWidth, 220)"
                                    placeholder="Buscar supervisor por nombre..."
                                    class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm"
                                >
                            </div>
                            {{-- Teleport a <body> + posicionamiento fijo (posicionarPopover, ya
                                 usado por Bitácora) — el modal tiene overflow-y-auto para su
                                 propio scroll, y eso recortaba el dropdown si se quedaba
                                 dentro. --}}
                            <template x-teleport="body">
                                <div
                                    x-show="open" x-cloak x-transition
                                    @click.outside="if (!$refs.respTrigger.contains($event.target)) { open = false; busquedaResponsable = ''; }"
                                    @click.stop
                                    :style="estilo"
                                    class="max-h-64 overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-xl"
                                >
                                    <template x-for="sup in supervisoresFiltrados()" :key="sup.id">
                                        <button
                                            type="button"
                                            @click="formActividad.responsible_employee_id = sup.id; responsableAutocompletado = false; busquedaResponsable = ''; open = false"
                                            class="flex w-full items-center justify-between gap-2 px-3 py-1.5 text-left text-sm hover:bg-slate-50"
                                        >
                                            <span class="flex items-center gap-2">
                                                <input type="checkbox" tabindex="-1" :checked="formActividad.responsible_employee_id === sup.id" class="pointer-events-none">
                                                <span class="truncate" x-text="sup.nombre"></span>
                                            </span>
                                        </button>
                                    </template>
                                    <p class="px-3 py-3 text-center text-xs text-slate-400" x-show="supervisoresFiltrados().length === 0">
                                        Sin resultados.
                                    </p>
                                </div>
                            </template>
                        </div>
                </div>

                <div class="mt-4">
                    <label class="mb-1 block text-xs font-medium text-slate-600">Actividad (texto libre, versión programada) <span class="text-red-500">*</span></label>
                    <input type="text" name="description" x-model="formActividad.description" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Ej. Realizar cambio de cauchos">
                </div>

                <div class="mt-5">
                    <p class="mb-2 flex items-center gap-1 text-xs font-medium text-slate-600">
                        Personas de la actividad — <span x-text="personas.length"></span> seleccionada(s)
                        <span class="relative inline-block group">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 cursor-pointer text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z" />
                            </svg>
                            <span class="pointer-events-none absolute left-0 top-6 z-10 w-64 rounded-xl border border-slate-200 bg-white p-3 text-left text-xs font-normal text-slate-600 shadow-lg opacity-0 transition group-hover:opacity-100">
                                Horas acumuladas en Bitácora este mes (antes de esta fecha), más las de esta
                                actividad si ya escribiste "Horas estimadas" — referencia para decidir a quién
                                programar, no bloquea la selección.
                            </span>
                        </span>
                    </p>

                    <div class="mb-2 flex flex-wrap gap-x-5 gap-y-2" x-show="personas.length > 0">
                        {{-- Sin fondo (pedido 2026-09-19: el naranja no
                             dejaba leer bien el nombre) — solo texto con un
                             subrayado naranja sutil (border-b) y su boton
                             "x", un poco mas grande que el resto del modal
                             porque son "los trabajadores", el dato mas
                             importante de la fila. Si el guardado fallo por
                             "primaria unica por persona/dia"
                             (personasConflicto, poblado en guardarActividad()
                             a partir del error 422 del backend), el texto y
                             el subrayado se pintan de rojo para identificar
                             a simple vista quien genero el choque. --}}
                        <template x-for="id in personas" :key="id">
                            <span
                                class="inline-flex items-center gap-2 text-sm font-medium"
                                :class="personasConflicto.includes(id) ? 'text-red-700' : 'text-slate-700'"
                            >
                                <span
                                    class="border-b-2 pb-0.5"
                                    :class="personasConflicto.includes(id) ? 'border-red-500' : 'border-[#d55b20]'"
                                    x-text="nombrePersona(id)"
                                ></span>
                                <span class="text-xs text-slate-400" x-text="horasResumenTexto(id)"></span>
                                <button
                                    type="button"
                                    @click="quitarPersona(id)"
                                    class="flex h-5 w-5 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-red-500"
                                >
                                    <i data-lucide="x" class="h-3.5 w-3.5"></i>
                                </button>
                            </span>
                        </template>
                    </div>

                    <div class="relative" x-data="{ open: false, estilo: '' }">
                        <div class="relative" x-ref="personasTrigger">
                            <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z" />
                            </svg>
                            <input
                                type="text"
                                x-model="busquedaPersona"
                                @focus="open = true; estilo = posicionarPopover($el, $el.offsetWidth, 280)"
                                placeholder="Buscar persona por nombre..."
                                class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm"
                            >
                        </div>
                        {{-- Teleport a <body>, mismo motivo que el combobox de Responsable
                             de arriba: el modal tiene overflow-y-auto y recortaba esta lista. --}}
                        <template x-teleport="body">
                        <div
                            x-show="open" x-cloak x-transition
                            @click.outside="if (!$refs.personasTrigger.contains($event.target)) { open = false; }"
                            @click.stop
                            :style="estilo"
                            class="max-h-64 overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-xl"
                        >
                            <template x-for="col in categorias" :key="col">
                                <template x-if="personasFiltradas(col).length > 0">
                                    <div>
                                        <div class="sticky top-0 bg-slate-50 px-3 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400" x-text="col"></div>
                                        <template x-for="emp in personasFiltradas(col)" :key="emp.id">
                                            <button
                                                type="button"
                                                @click="togglePersona(emp); busquedaPersona = ''"
                                                class="flex w-full items-center justify-between gap-2 px-3 py-1.5 text-left text-sm hover:bg-slate-50"
                                            >
                                                <span class="flex min-w-0 items-center gap-2">
                                                    <input type="checkbox" tabindex="-1" :checked="personaSeleccionada(emp)" class="pointer-events-none">
                                                    <span class="truncate" x-text="emp.nombre"></span>
                                                </span>
                                                <span class="shrink-0 text-xs text-slate-400" x-text="horasResumenTexto(emp.id)"></span>
                                            </button>
                                        </template>
                                    </div>
                                </template>
                            </template>
                            <p class="px-3 py-3 text-center text-xs text-slate-400" x-show="personasFiltradas('Campo').length === 0 && personasFiltradas('Administrativos').length === 0">
                                Sin resultados.
                            </p>
                        </div>
                        </template>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" @click="modalAbierto = false" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancelar</button>
                    <button type="submit" :disabled="guardando" class="rounded-xl bg-[#d55b20] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#b8481a] disabled:cursor-not-allowed disabled:opacity-60" x-text="guardando ? 'Guardando...' : 'Guardar actividad'"></button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function programacionPage({ empleados, categorias, supervisores, equipos, procesos, gruposResponsables, diasConDatosUrlBase, fecha, hoyReal, storeUrl, updateUrlTemplate, destroyUrlTemplate, csrfToken, defaultCompanyId, actividadesIniciales, horasAcumuladasPorId }) {
        const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        const [fy, fm] = fecha.split('-').map(Number);
        const pad2 = (n) => String(n).padStart(2, '0');
        const fechaAString = (year, month, day) => `${year}-${pad2(month + 1)}-${pad2(day)}`;

        const emptyFormActividad = () => ({
            company_id: defaultCompanyId ?? '', group_number: '', team: '', process: '', shift: 'Diurno',
            responsible_employee_id: '', description: '', estimated_hours: '', activity_type: 'P',
        });

        const jsonHeaders = () => ({
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        });

        return {
            ...imageExporterMixin('programacion'),
            empleados,
            categorias,
            supervisores,
            equipos,
            procesos,
            gruposResponsables,
            // Autocompletar Responsable por Grupo (pedido 2026-09-18): solo
            // rellena si el campo Responsable todavia esta vacio, o si el
            // valor actual lo puso esta misma funcion la vez anterior
            // (responsableAutocompletado) — nunca pisa una eleccion manual
            // del usuario. gruposResponsables ya viene acotado al dia que
            // se esta viendo (armado en el backend sobre las actividades de
            // esa fecha), asi que nunca sugiere el responsable de otro dia.
            //
            // Fix 2026-09-22 (bug real reportado por el usuario): la guarda
            // original (`if (responsible_employee_id) return`) impedia
            // volver a evaluar el autocompletado despues del primer relleno
            // automatico — al cambiar de Grupo 1 (con responsable sugerido)
            // a Grupo 2 (sin ninguna actividad ese dia), el campo se quedaba
            // pegado con el responsable del grupo anterior en vez de
            // vaciarse. Ahora, mientras el valor actual siga siendo uno que
            // nosotros mismos autocompletamos, se reevalua en cada cambio de
            // grupo: se actualiza al sugerido del grupo nuevo, o se vacia si
            // ese grupo no tiene ninguno (o si el campo Grupo quedo vacio).
            responsableAutocompletado: false,
            autocompletarResponsablePorGrupo() {
                if (this.formActividad.responsible_employee_id && !this.responsableAutocompletado) return;

                const grupo = this.formActividad.group_number;
                const sugerido = grupo ? (this.gruposResponsables[grupo] ?? null) : null;

                if (sugerido) {
                    this.formActividad.responsible_employee_id = sugerido;
                    this.responsableAutocompletado = true;
                } else {
                    this.formActividad.responsible_employee_id = '';
                    this.responsableAutocompletado = false;
                }
            },
            equiposFiltrados() {
                const q = (this.formActividad.team || '').trim().toLowerCase();
                return this.equipos.filter((e) => !q || e.toLowerCase().includes(q));
            },
            procesosFiltrados() {
                const q = (this.formActividad.process || '').trim().toLowerCase();
                return this.procesos.filter((p) => !q || p.toLowerCase().includes(q));
            },
            busquedaResponsable: '',
            supervisoresFiltrados() {
                const q = this.busquedaResponsable.trim().toLowerCase();
                return this.supervisores.filter((s) => !q || s.nombre.toLowerCase().includes(q));
            },
            nombreSupervisor(id) {
                return this.supervisores.find((s) => s.id === id)?.nombre ?? '';
            },
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

            // Actividades — mismo patron que Empleados/Roles: fetch() +
            // array reactivo, sin recargar la pagina (pedido 2026-09-19). El
            // resultado se muestra con showCrudToast() (esquina inferior
            // derecha, compartido con el resto de /personal) en vez del
            // banner verde de session('success').
            actividades: actividadesIniciales.map((a) => ({ ...a, eliminando: false })),
            horasAcumuladasPorId,
            // Texto de la tarjeta de hover (seccion 14.17) — mismo mapa
            // id => horas que ya usa horasResumenTexto() en el selector del
            // modal, pero sin combinar con "estimated_hours" (aqui es solo
            // informativo sobre filas ya guardadas, no un formulario en
            // progreso).
            horasAcumuladasCardTexto(id) {
                const horas = this.horasAcumuladasPorId[id];
                return horas !== undefined && horas !== null
                    ? `${horas}h acumuladas este mes`
                    : 'Sin horas acumuladas este mes';
            },
            // Agrupa por group_number igual que antes hacia el controller
            // (groupBy + sortKeysUsing), con "Sin grupo asignado" siempre al
            // final. Dentro de cada grupo se ordena por id (orden de
            // creacion), igual que la query original.
            gruposOrdenados() {
                const grupos = new Map();
                for (const a of this.actividades) {
                    // Number(...) normaliza el tipo de la key antes de
                    // agrupar (bug 2026-09-19): un group_number "1" (string,
                    // ej. si llegara asi de alguna respuesta) y 1 (number)
                    // deben caer en el mismo grupo — un Map trata claves de
                    // distinto tipo como distintas aunque representen el
                    // mismo valor.
                    const key = a.group_number === null || a.group_number === undefined ? null : Number(a.group_number);
                    if (!grupos.has(key)) grupos.set(key, []);
                    grupos.get(key).push(a);
                }
                const keys = [...grupos.keys()].sort((x, y) => {
                    if (x === null) return 1;
                    if (y === null) return -1;
                    return x - y;
                });
                return keys.map((grupoNum) => ({
                    grupoNum,
                    filas: grupos.get(grupoNum).slice().sort((x, y) => x.id - y.id),
                }));
            },

            modalAbierto: false,
            modoEdicion: false,
            guardando: false,
            formErrors: [],
            activityId: null,
            formAction: storeUrl,
            formMethod: 'POST',
            formActividad: emptyFormActividad(),
            busquedaPersona: '',
            personas: [],
            // ids de personas que causaron el error "Ya tiene otra actividad
            // primaria ese día" (poblado en guardarActividad(), ver
            // extraerPersonasConflicto) — pinta su chip de rojo en vez del
            // naranja normal, para identificar a simple vista quien choco.
            personasConflicto: [],
            // Cerrar (X, click afuera, Cancelar) nunca borra formActividad —
            // solo oculta. Reabrir conserva el borrador (como minimizar);
            // solo se reinicia si se viene de un modo distinto (de editar a
            // nuevo, o de editar otra actividad).
            abrirModal() {
                if (this.modoEdicion) {
                    this.modoEdicion = false;
                    this.activityId = null;
                    this.formAction = storeUrl;
                    this.formMethod = 'POST';
                    this.formActividad = emptyFormActividad();
                    this.personas = [];
                    this.responsableAutocompletado = false;
                }
                this.formErrors = [];
                this.personasConflicto = [];
                this.busquedaPersona = '';
                this.busquedaResponsable = '';
                this.modalAbierto = true;
                this.$nextTick(() => window.lucide?.createIcons());
            },
            editarActividad(a) {
                if (!(this.modoEdicion && this.activityId === a.id)) {
                    this.modoEdicion = true;
                    this.activityId = a.id;
                    this.formAction = updateUrlTemplate.replace('__ID__', a.id);
                    this.formMethod = 'PUT';
                    this.formActividad = {
                        company_id: a.company_id ?? '', group_number: a.group_number ?? '',
                        team: a.team ?? '', process: a.process ?? '', shift: a.shift, responsible_employee_id: a.responsible_employee_id ?? '',
                        description: a.description, estimated_hours: a.estimated_hours ?? '', activity_type: a.activity_type,
                    };
                    this.personas = a.personas.map((p) => p.id);
                    // El responsable de una actividad ya guardada es un valor
                    // deliberado (elegido a mano o ya persistido), nunca un
                    // autocompletado fresco — no se debe tocar solo porque el
                    // usuario edite el campo Grupo mientras esta en este modal.
                    this.responsableAutocompletado = false;
                }
                this.formErrors = [];
                this.personasConflicto = [];
                this.busquedaPersona = '';
                this.busquedaResponsable = '';
                this.modalAbierto = true;
                this.$nextTick(() => window.lucide?.createIcons());
            },
            async guardarActividad() {
                this.guardando = true;
                this.formErrors = [];
                this.personasConflicto = [];

                try {
                    const payload = {
                        date: this.fecha,
                        company_id: this.formActividad.company_id,
                        group_number: this.formActividad.group_number || null,
                        team: this.formActividad.team || null,
                        process: this.formActividad.process || null,
                        shift: this.formActividad.shift,
                        responsible_employee_id: this.formActividad.responsible_employee_id || null,
                        description: this.formActividad.description,
                        estimated_hours: this.formActividad.estimated_hours === '' ? null : this.formActividad.estimated_hours,
                        activity_type: this.formActividad.activity_type,
                        personas: this.personas,
                    };

                    const res = await fetch(this.formAction, {
                        method: this.formMethod,
                        headers: jsonHeaders(),
                        body: JSON.stringify(payload),
                    });
                    const data = await res.json().catch(() => null);

                    if (res.status === 422 && data?.errors) {
                        this.formErrors = Object.values(data.errors).flat();
                        this.personasConflicto = this.extraerPersonasConflicto(data.errors.personas);
                        showCrudToast(this.formErrors, 'error');
                        return;
                    }

                    if (!res.ok || !data?.success) {
                        showCrudToast(data?.message || 'No se pudo guardar la actividad.', 'error');
                        return;
                    }

                    const nueva = { ...data.activity, eliminando: false };
                    const idx = this.actividades.findIndex((a) => a.id === nueva.id);
                    if (idx !== -1) {
                        this.actividades[idx] = nueva;
                    } else {
                        this.actividades.push(nueva);
                    }

                    // Mantener sugerencias/autocompletar al dia sin recargar
                    // la pagina (antes lo resolvia el full reload).
                    this.agregarSugerencia('equipos', nueva.team);
                    this.agregarSugerencia('procesos', nueva.process);
                    if (nueva.group_number && nueva.responsible_employee_id && !this.gruposResponsables[nueva.group_number]) {
                        this.gruposResponsables[nueva.group_number] = nueva.responsible_employee_id;
                    }

                    this.modoEdicion = false;
                    this.activityId = null;
                    this.formAction = storeUrl;
                    this.formMethod = 'POST';
                    this.formActividad = emptyFormActividad();
                    this.personas = [];
                    this.personasConflicto = [];
                    this.modalAbierto = false;
                    showCrudToast(data.message || 'Actividad guardada correctamente.', 'success');
                    this.$nextTick(() => window.lucide?.createIcons());
                } catch (e) {
                    console.error('guardarActividad:', e);
                    showCrudToast('No se pudo guardar la actividad (error de red).', 'error');
                } finally {
                    this.guardando = false;
                }
            },
            async eliminarActividad(a) {
                if (a.eliminando) return;
                if (!confirm('¿Eliminar esta actividad? Esta acción no se puede deshacer.')) return;
                a.eliminando = true;

                try {
                    const res = await fetch(destroyUrlTemplate.replace('__ID__', a.id), {
                        method: 'DELETE',
                        headers: jsonHeaders(),
                    });
                    const data = await res.json().catch(() => null);

                    if (!res.ok || !data?.success) {
                        showCrudToast(data?.message || 'No se pudo eliminar la actividad.', 'error');
                        return;
                    }

                    this.actividades = this.actividades.filter((x) => x.id !== a.id);
                    showCrudToast(data.message || 'Actividad eliminada correctamente.', 'success');
                } catch (e) {
                    console.error('eliminarActividad:', e);
                    showCrudToast('No se pudo eliminar la actividad (error de red).', 'error');
                } finally {
                    a.eliminando = false;
                }
            },
            // Combobox Equipo/Proceso (ver equiposFiltrados/procesosFiltrados
            // arriba): agrega el valor recien guardado a la lista de
            // sugerencias si no existe todavia (dedup case-insensitive,
            // mismo criterio que distinctFreeTextValues() en el backend).
            agregarSugerencia(lista, valor) {
                if (!valor) return;
                const v = String(valor).trim();
                if (!v) return;
                const existe = this[lista].some((x) => x.toLowerCase() === v.toLowerCase());
                if (!existe) this[lista].push(v);
            },
            // El backend no devuelve ids en el error de "primaria unica por
            // persona/dia" (ActivityController::validated(), regla
            // 'personas'), solo un texto con los nicknames en conflicto:
            // "Ya tiene otra actividad primaria ese día: fulano, mengano."
            // Se parsea ese texto y se matchea contra nombrePersona(id) —
            // que en este modulo devuelve el nickname, mismo dato que usa
            // el backend — para saber que chips pintar de rojo.
            extraerPersonasConflicto(erroresPersonas) {
                if (!erroresPersonas) return [];
                const mensaje = erroresPersonas.map(String).find((e) => e.includes('otra actividad primaria'));
                if (!mensaje) return [];
                const listaNombres = mensaje.split(':').slice(1).join(':').replace(/\.\s*$/, '');
                const nombres = listaNombres.split(',').map((n) => n.trim().toLowerCase()).filter(Boolean);
                if (nombres.length === 0) return [];
                return this.personas.filter((id) => nombres.includes((this.nombrePersona(id) || '').toLowerCase()));
            },
            personaSeleccionada(emp) {
                return this.personas.includes(emp.id);
            },
            togglePersona(emp) {
                const idx = this.personas.indexOf(emp.id);
                if (idx >= 0) this.personas.splice(idx, 1); else this.personas.push(emp.id);
                // El chip nuevo trae su propio <i data-lucide="x"> (icono de
                // quitar) — a diferencia del chip de Responsable (elemento
                // estatico, ya convertido a SVG desde que abrirModal()/
                // editarActividad() corrieron createIcons() al abrir el
                // modal), este chip lo crea x-for recien ahora y nadie lo
                // habia convertido todavia. Sin este refresh, el icono se
                // queda como <i> vacio (sin la "X" visible) para siempre.
                this.$nextTick(() => window.lucide?.createIcons());
            },
            quitarPersona(id) {
                const idx = this.personas.indexOf(id);
                if (idx >= 0) this.personas.splice(idx, 1);
            },
            // Pedido 2026-09-21: dentro de cada categoria (el agrupamiento
            // por rol/categoria ya estaba y se mantiene igual, es el
            // criterio principal) se ordena por horas acumuladas del mes,
            // de mayor a menor — mismo dato que ya se mostraba a la derecha
            // de cada fila (horasResumenTexto()), ahora tambien define el
            // orden. Sin horas acumuladas (null) se trata como 0 y queda al
            // final de su categoria.
            personasFiltradas(categoria) {
                const q = this.busquedaPersona.trim().toLowerCase();
                return this.empleados
                    .filter((e) => e.categoria === categoria && (!q || e.nombre.toLowerCase().includes(q)))
                    .sort((a, b) => (b.horasAcumuladas ?? 0) - (a.horasAcumuladas ?? 0));
            },
            nombrePersona(id) {
                return this.empleados.find((e) => e.id === id)?.nombre ?? '';
            },
            // "acumuladas + hoy" (pedido 2026-09-19, seccion 14.16): horasAcumuladas
            // viene del backend (BitacoraHoursCalculator, suma de dias
            // anteriores a la fecha vista/programada — nunca incluye el
            // dia de hoy). "+ hoy" es reactivo: lee formActividad.estimated_hours
            // en vivo, asi que al escribir horas en el formulario, el
            // buscador y los chips ya seleccionados se actualizan solos sin
            // recargar nada.
            //
            // Fix 2026-09-22 (bug real reportado por el usuario): "+Xh hoy"
            // se mostraba para TODAS las personas de la lista, incluso las
            // que todavia no estaban marcadas — daba la impresion de que ya
            // se les habian sumado las horas de esta actividad sin haberlas
            // asignado. Ahora "+Xh hoy" solo aparece si la persona ya esta
            // seleccionada (this.personas.includes(id)) — para las demas
            // solo se muestra su acumulado real, sin la suma hipotetica.
            horasResumenTexto(id) {
                const emp = this.empleados.find((e) => e.id === id);
                if (!emp) return '';

                const partes = [];
                if (emp.horasAcumuladas !== null && emp.horasAcumuladas !== undefined) {
                    partes.push(`${emp.horasAcumuladas}h`);
                }

                if (this.personas.includes(id)) {
                    const hoy = this.formActividad.estimated_hours;
                    const hoyNum = hoy === '' || hoy === null || hoy === undefined ? null : Number(hoy);
                    if (hoyNum !== null && !Number.isNaN(hoyNum) && hoyNum > 0) {
                        partes.push(`+${hoyNum}h hoy`);
                    }
                }

                return partes.length ? `· ${partes.join(' ')}` : '';
            },
        };
    }
</script>
@endpush
