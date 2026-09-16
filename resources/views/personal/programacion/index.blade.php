@extends('layouts.personal')

@section('title', 'Programación')

@php
    $grupos = $actividades->groupBy('group_number')->sortKeysUsing(
        fn ($a, $b) => ($a ?? 999999) <=> ($b ?? 999999)
    );
    $reopen = $errors->any();
    $oldActivityId = old('activity_id');
    $fechaLabel = \Carbon\Carbon::parse($fecha)->translatedFormat('d \d\e F \d\e Y');
@endphp

@section('content')
<div
    x-data="programacionPage({
        empleados: @js($empleados),
        diasConDatosUrlBase: @js(route('personal.programacion.dias-con-datos')),
        fecha: @js($fecha),
        storeUrl: @js(route('personal.programacion.store')),
        updateUrlTemplate: @js(route('personal.programacion.update', ['activity' => '__ID__'])),
        defaultCompanyId: @js($empresas->firstWhere('is_default', true)?->id),
        reopen: @js($reopen),
        oldPersonas: @js($reopen ? collect(old('personas', []))->map(fn ($v) => (int) $v)->values() : []),
        oldValues: @js($reopen ? [
            'id' => $oldActivityId,
            'company_id' => old('company_id'),
            'area' => old('area'),
            'group_number' => old('group_number'),
            'team' => old('team'),
            'shift' => old('shift', 'Diurno'),
            'responsible_employee_id' => old('responsible_employee_id'),
            'description' => old('description'),
            'estimated_hours' => old('estimated_hours'),
            'activity_type' => old('activity_type', 'P'),
        ] : null),
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
                    :disabled="exportando || {{ $actividades->isEmpty() ? 'true' : 'false' }}"
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

    @if ($actividades->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-400">
            No hay actividades programadas para <span class="font-medium text-slate-500">{{ $fechaLabel }}</span>.
            <br>Usa el calendario para navegar a otro día, o crea la primera actividad de hoy.
        </div>
    @else
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
                        <th class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500" style="min-width:13rem">Actividad</th>
                        <th class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500" style="min-width:9rem">Personas</th>
                        <th class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Horas</th>
                        <th class="px-3 py-2 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500" title="Jornada">Jorn.</th>
                        <th class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Resp.</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach ($grupos as $grupoNum => $filas)
                        <tr class="bg-slate-800">
                            <td colspan="8" class="px-3 py-1.5">
                                <span class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-white">
                                    <i data-lucide="users" class="h-3.5 w-3.5"></i>
                                    {{ $grupoNum ? "Grupo {$grupoNum}" : 'Sin grupo asignado' }}
                                </span>
                            </td>
                        </tr>
                        @foreach ($filas as $a)
                            <tr class="hover:bg-slate-50">
                                <td class="sticky-col px-3 py-2">
                                    @if ($modificables[$a->id] ?? false)
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button
                                                type="button"
                                                @click="editarActividad({{ Illuminate\Support\Js::from([
                                                    'id' => $a->id,
                                                    'company_id' => $a->company_id,
                                                    'area' => $a->area,
                                                    'group_number' => $a->group_number,
                                                    'team' => $a->team,
                                                    'shift' => $a->shift,
                                                    'responsible_employee_id' => $a->responsible_employee_id,
                                                    'description' => $a->description,
                                                    'estimated_hours' => $a->estimated_hours !== null ? $a->estimated_hours + 0 : null,
                                                    'activity_type' => $a->activity_type,
                                                    'personas' => $a->personas->pluck('id'),
                                                ]) }})"
                                                class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-[#d55b20]" title="Editar"
                                            >
                                                <i data-lucide="pencil" class="h-4 w-4"></i>
                                            </button>
                                            <form method="POST" action="{{ route('personal.programacion.destroy', $a) }}" onsubmit="return confirm('¿Eliminar esta actividad? Esta acción no se puede deshacer.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-600" title="Eliminar">
                                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="block text-center text-slate-300" title="{{ $a->isClosed() ? 'Ya fue cerrada en Diario de Campo' : 'Fuera de la ventana de edición' }}">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">{{ $a->company->name }}</td>
                                <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $a->team ?: '—' }}</td>
                                <td class="px-3 py-2">
                                    <span
                                        class="mr-1.5 inline-flex h-4 w-4 items-center justify-center rounded-full align-[-2px] text-[10px] font-bold {{ $a->activity_type === 'P' ? 'bg-[#d55b20] text-white' : 'bg-slate-300 text-slate-700' }}"
                                        title="{{ $a->activity_type === 'P' ? 'Actividad primaria' : 'Actividad secundaria' }}"
                                    >{{ $a->activity_type }}</span>
                                    {{ $a->description }}
                                </td>
                                <td class="px-3 py-2 text-slate-600">{{ $a->personas->pluck('nickname')->implode(', ') ?: '—' }}</td>
                                {{-- +0 quita los ceros de mas del cast decimal:2 (ej. "10.00" -> 10, "9.50" -> 9.5), igual estilo que el mockup. --}}
                                <td class="px-3 py-2 whitespace-nowrap">{{ $a->estimated_hours !== null ? $a->estimated_hours + 0 : '—' }}</td>
                                <td class="px-3 py-2 whitespace-nowrap text-center text-slate-600" title="{{ $a->shift }}">{{ $a->shift === 'Nocturno' ? 'N' : 'D' }}</td>
                                <td class="px-3 py-2 whitespace-nowrap text-slate-500">{{ $a->responsible->abreviatura ?? '—' }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
            </div>
            </div>
        </div>
    @endif

    <p class="mt-4 text-xs text-slate-400">
        "P" / "S" junto a la Actividad = la actividad completa es primaria o secundaria (aplica a todas las personas
        de esa fila) · una persona solo puede estar en una actividad primaria por día, pero en varias secundarias.
    </p>

    {{-- Toast de resultado de la exportacion --}}
    <div x-show="mensajeExport" x-cloak x-transition class="fixed bottom-4 right-4 z-50 max-w-xs rounded-xl bg-slate-900 px-4 py-3 text-sm text-white shadow-xl" x-text="mensajeExport"></div>

    {{-- Calendario para navegar entre fechas. Los dias con datos se piden
         por AJAX a /personal/programacion/dias-con-datos (no se puede
         cargar todo el historial en memoria como hacia el mockup). --}}
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
    <div x-show="modalAbierto" x-cloak class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 p-4" style="top:0;left:0;height:100vh;width:100vw;">
        <div @click.outside="modalAbierto = false" x-show="modalAbierto" x-transition class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
            <form :action="formAction" method="POST">
                @csrf
                <input type="hidden" name="_method" :value="formMethod">
                <input type="hidden" name="activity_id" :value="activityId">
                <input type="hidden" name="date" value="{{ $fecha }}">

                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-slate-900" x-text="modoEdicion ? 'Editar actividad' : 'Nueva actividad'"></h2>
                    <button type="button" @click="modalAbierto = false" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100"><i data-lucide="x" class="h-5 w-5"></i></button>
                </div>

                @if ($errors->any())
                    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <ul class="list-inside list-disc space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Empresa</label>
                        <select name="company_id" x-model="formActividad.company_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            @foreach ($empresas as $e)
                                <option value="{{ $e->id }}">{{ $e->name }}{{ $e->is_default ? ' (por defecto)' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Área</label>
                        <select name="area" x-model="formActividad.area" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="">Sin especificar</option>
                            @foreach ($areas as $a)
                                <option value="{{ $a }}">{{ $a }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Grupo</label>
                        <input type="number" name="group_number" x-model="formActividad.group_number" min="1" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Ej. 1 (opcional)">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Equipo (texto libre)</label>
                        <input type="text" name="team" x-model="formActividad.team" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Ej. TP1 Trituradora">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Jornada</label>
                        <select name="shift" x-model="formActividad.shift" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option>Diurno</option>
                            <option>Nocturno</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Responsable (supervisor)</label>
                        <select name="responsible_employee_id" x-model="formActividad.responsible_employee_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="">Sin asignar</option>
                            @foreach ($empleados->filter(fn ($e) => $e['categoria'] === 'Administrativos' && $e['abreviatura']) as $emp)
                                <option value="{{ $emp['id'] }}">{{ $emp['nombre'] }} ({{ $emp['abreviatura'] }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-medium text-slate-600">Actividad (texto libre, versión programada)</label>
                        <input type="text" name="description" x-model="formActividad.description" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Ej. Realizar cambio de cauchos">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Horas estimadas (opcional)</label>
                        <input type="number" name="estimated_hours" x-model="formActividad.estimated_hours" step="0.5" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Ej. 10">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Tipo de actividad</label>
                        <select name="activity_type" x-model="formActividad.activity_type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="P">Primaria</option>
                            <option value="S">Secundaria</option>
                        </select>
                        <p class="mt-1 text-[11px] text-slate-400">
                            Aplica a todas las personas de esta actividad. Cada persona solo puede tener
                            una actividad primaria por día — el sistema lo valida al guardar.
                        </p>
                    </div>
                </div>

                <div class="mt-5">
                    <p class="mb-2 text-xs font-medium text-slate-600">
                        Personas de la actividad — <span x-text="personas.length"></span> seleccionada(s)
                    </p>

                    <template x-for="id in personas" :key="id">
                        <input type="hidden" name="personas[]" :value="id">
                    </template>

                    <div class="mb-2 flex flex-wrap gap-1.5" x-show="personas.length > 0">
                        <template x-for="id in personas" :key="id">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 py-1 pl-3 pr-1.5 text-xs text-slate-700">
                                <span x-text="nombrePersona(id)"></span>
                                <span class="text-slate-400" x-text="horasMesTexto(id)"></span>
                                <button type="button" @click="quitarPersona(id)" class="flex h-4 w-4 items-center justify-center rounded-full text-slate-400 hover:bg-slate-200 hover:text-red-500">
                                    <i data-lucide="x" class="h-3 w-3"></i>
                                </button>
                            </span>
                        </template>
                    </div>

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
                                                <span class="shrink-0 text-xs text-slate-400" x-text="horasMesTexto(emp.id)"></span>
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
                        "Horas este mes" es un dato de ejemplo (la Bitácora real todavía no existe) — referencia,
                        no bloquea la selección.
                    </p>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" @click="modalAbierto = false" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancelar</button>
                    <button type="submit" class="rounded-xl bg-[#d55b20] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#b8481a]">Guardar actividad</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function programacionPage({ empleados, diasConDatosUrlBase, fecha, storeUrl, updateUrlTemplate, defaultCompanyId, reopen, oldPersonas, oldValues }) {
        const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        const [fy, fm] = fecha.split('-').map(Number);
        const pad2 = (n) => String(n).padStart(2, '0');
        const fechaAString = (year, month, day) => `${year}-${pad2(month + 1)}-${pad2(day)}`;

        const emptyFormActividad = () => ({
            company_id: defaultCompanyId ?? '', area: '', group_number: '', team: '', shift: 'Diurno',
            responsible_employee_id: '', description: '', estimated_hours: '', activity_type: 'P',
        });
        const reopenEditando = reopen && !!(oldValues && oldValues.id);

        return {
            ...imageExporterMixin('programacion'),
            empleados,
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

            modalAbierto: reopen,
            modoEdicion: reopenEditando,
            activityId: reopenEditando ? oldValues.id : null,
            formAction: reopenEditando ? updateUrlTemplate.replace('__ID__', oldValues.id) : storeUrl,
            formMethod: reopenEditando ? 'PUT' : 'POST',
            formActividad: reopen ? { ...emptyFormActividad(), ...oldValues } : emptyFormActividad(),
            busquedaPersona: '',
            personas: reopen ? oldPersonas : [],
            abrirModal() {
                this.modoEdicion = false;
                this.activityId = null;
                this.formAction = storeUrl;
                this.formMethod = 'POST';
                this.formActividad = emptyFormActividad();
                this.personas = [];
                this.busquedaPersona = '';
                this.modalAbierto = true;
                this.$nextTick(() => window.lucide?.createIcons());
            },
            editarActividad(a) {
                this.modoEdicion = true;
                this.activityId = a.id;
                this.formAction = updateUrlTemplate.replace('__ID__', a.id);
                this.formMethod = 'PUT';
                this.formActividad = {
                    company_id: a.company_id ?? '', area: a.area ?? '', group_number: a.group_number ?? '',
                    team: a.team ?? '', shift: a.shift, responsible_employee_id: a.responsible_employee_id ?? '',
                    description: a.description, estimated_hours: a.estimated_hours ?? '', activity_type: a.activity_type,
                };
                this.personas = a.personas ?? [];
                this.busquedaPersona = '';
                this.modalAbierto = true;
                this.$nextTick(() => window.lucide?.createIcons());
            },
            personaSeleccionada(emp) {
                return this.personas.includes(emp.id);
            },
            togglePersona(emp) {
                const idx = this.personas.indexOf(emp.id);
                if (idx >= 0) this.personas.splice(idx, 1); else this.personas.push(emp.id);
            },
            quitarPersona(id) {
                const idx = this.personas.indexOf(id);
                if (idx >= 0) this.personas.splice(idx, 1);
            },
            personasFiltradas(categoria) {
                const q = this.busquedaPersona.trim().toLowerCase();
                return this.empleados.filter((e) => e.categoria === categoria && (!q || e.nombre.toLowerCase().includes(q)));
            },
            nombrePersona(id) {
                return this.empleados.find((e) => e.id === id)?.nombre ?? '';
            },
            horasMesTexto(id) {
                const emp = this.empleados.find((e) => e.id === id);
                return emp && emp.horasMes !== null && emp.horasMes !== undefined ? `· ${emp.horasMes}h este mes` : '';
            },
        };
    }
</script>
@endpush
