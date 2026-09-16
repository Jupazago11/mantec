@extends('preview-personal._layout')

@section('title', 'Empleados')

@php
    // Archivo PHP plano (no Blade @include): un @include de Blade renderiza
    // en un scope aislado y no comparte $empleados hacia este archivo.
    //
    // Correccion 2026-09-16: cedula, fecha de nacimiento, fecha de ingreso
    // y fechas de contrato se quitan de la tabla y del formulario — no
    // hacen falta para el alcance ya contratado (Nivel 1, seccion 12.4).
    // El campo/dato sigue existiendo en _empleados-data.php por si se
    // retoma mas adelante, solo se dejo de mostrar/editar aqui.
    $empleados = include resource_path('views/preview-personal/_empleados-data.php');
@endphp

@section('content')
<div
    class="mx-auto max-w-[1900px] space-y-4"
    x-data="empleadosPage({
        estadosActivos: @js(collect($empleados)->pluck('activo', 'nombre')->toArray()),
        empleadosData: @js($empleados),
    })"
>
    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900 md:text-2xl">Empleados</h1>
                <p class="mt-1 text-xs text-slate-500">
                    Nombre completo, nickname (display corto) y abreviatura (solo Administrativos, usada como
                    código en la columna "Responsable") son 3 campos distintos.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button
                    @click="exportarComoImagen()"
                    :disabled="exportando"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    title="Genera una imagen de la tabla de empleados para pegarla en WhatsApp (o descargarla en móvil)"
                >
                    <i data-lucide="image-down" class="h-4 w-4" x-show="!exportando"></i>
                    <i data-lucide="loader-2" class="h-4 w-4 animate-spin" x-show="exportando" x-cloak></i>
                    <span x-text="exportando ? 'Generando...' : 'Copiar como imagen'"></span>
                </button>
                <button @click="modalEmpresas = true" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <i data-lucide="building-2" class="h-4 w-4"></i> Empresas
                </button>
                <button @click="nuevoEmpleado()" class="inline-flex items-center gap-2 rounded-xl bg-[#d55b20] px-4 py-2 text-sm font-semibold text-white hover:bg-[#b8481a]">
                    <i data-lucide="plus" class="h-4 w-4"></i> Nuevo empleado
                </button>
            </div>
        </div>

        <label class="mt-3 flex items-center gap-2 text-xs text-slate-500">
            <input type="checkbox" x-model="mostrarInactivos">
            Mostrar empleados inactivos
        </label>
    </div>

    {{-- areaExportable: lo que captura "Copiar como imagen" — mismo patron
         que Programacion (ver programacion.blade.php). --}}
    <div id="areaExportable" class="space-y-2">
        <p class="text-sm font-semibold text-slate-700">Empleados</p>
        <div class="compact-table-wrapper rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="table-scroll-container">
        <table class="preventive-table divide-y divide-slate-200 text-sm">
            <thead class="sticky-table-head bg-slate-50">
                <tr>
                    <th class="sticky-col px-4 py-2 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">Acciones</th>
                    <th class="px-4 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500" style="min-width:14rem">Nombre completo</th>
                    <th class="px-4 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Nickname</th>
                    <th class="px-4 py-2 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">Abrev.</th>
                    <th class="px-4 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Categoría</th>
                    <th class="px-4 py-2 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">Usuario</th>
                    <th class="px-4 py-2 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">Bitácora</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @foreach ($empleados as $e)
                    <tr class="hover:bg-slate-50" x-show="estadosActivos[{{ Illuminate\Support\Js::from($e['nombre']) }}] || mostrarInactivos">
                        <td class="sticky-col px-4 py-2">
                            <div class="flex items-center justify-center gap-1.5">
                                <button type="button" @click="editarEmpleado({{ Illuminate\Support\Js::from($e['nombre']) }})" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-[#d55b20]" title="Editar">
                                    <i data-lucide="pencil" class="h-4 w-4"></i>
                                </button>
                                <button
                                    type="button"
                                    @click="estadosActivos[{{ Illuminate\Support\Js::from($e['nombre']) }}] = !estadosActivos[{{ Illuminate\Support\Js::from($e['nombre']) }}]"
                                    class="rounded-full px-2 py-1 text-[10px] font-semibold"
                                    :class="estadosActivos[{{ Illuminate\Support\Js::from($e['nombre']) }}] ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-slate-200 text-slate-600 hover:bg-slate-300'"
                                    x-text="estadosActivos[{{ Illuminate\Support\Js::from($e['nombre']) }}] ? 'Activo' : 'Inactivo'"
                                ></button>
                            </div>
                        </td>
                        <td class="px-4 py-2 font-medium text-slate-800">{{ $e['nombre'] }}</td>
                        <td class="whitespace-nowrap px-4 py-2 text-slate-600">{{ $e['nickname'] }}</td>
                        <td class="whitespace-nowrap px-4 py-2 text-center">
                            @if ($e['abreviatura'])
                                <span class="inline-flex h-6 min-w-[1.5rem] items-center justify-center rounded-md bg-slate-100 px-1.5 text-xs font-bold text-slate-600">{{ $e['abreviatura'] }}</span>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-2">
                            <span class="inline-flex items-center rounded-xl px-3 py-1 text-[11px] font-semibold {{ $e['categoria'] === 'Campo' ? 'bg-emerald-100 text-emerald-800' : 'bg-indigo-100 text-indigo-800' }}">
                                {{ $e['categoria'] }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-center">
                            @if ($e['usuario'])
                                <i data-lucide="check-circle-2" class="mx-auto h-4 w-4 text-emerald-600"></i>
                            @else
                                <i data-lucide="minus-circle" class="mx-auto h-4 w-4 text-slate-300"></i>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-center">
                            @if ($e['bitacora'])
                                <i data-lucide="check-circle-2" class="mx-auto h-4 w-4 text-emerald-600"></i>
                            @else
                                <i data-lucide="minus-circle" class="mx-auto h-4 w-4 text-slate-300" title="No hace parte de la Bitácora"></i>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        </div>
    </div>

    {{-- Toast de resultado de la exportacion --}}
    <div x-show="mensajeExport" x-cloak x-transition class="fixed bottom-4 right-4 z-50 max-w-xs rounded-xl bg-slate-900 px-4 py-3 text-sm text-white shadow-xl" x-text="mensajeExport"></div>

    {{-- Modal nuevo/editar empleado --}}
    <div x-show="modalNuevoEmpleado" x-cloak class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 p-4" style="top:0;left:0;height:100vh;width:100vw;">
        <div @click.outside="modalNuevoEmpleado = false" x-show="modalNuevoEmpleado" x-transition class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-bold text-slate-900" x-text="modoEdicion ? 'Editar empleado' : 'Nuevo empleado'"></h2>
                <button @click="modalNuevoEmpleado = false" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100"><i data-lucide="x" class="h-5 w-5"></i></button>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-medium text-slate-600">Nombre completo</label>
                    <input type="text" x-model="formEmpleado.nombre" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Ej. Luis Fernando Montoya Zuluaga">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Nickname (display corto)</label>
                    <input type="text" x-model="formEmpleado.nickname" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Ej. Fernando">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Categoría</label>
                    <select x-model="formEmpleado.categoria" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option>Campo</option>
                        <option>Administrativos</option>
                    </select>
                </div>
                <div x-show="formEmpleado.categoria === 'Administrativos'" x-cloak>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Abreviatura (columna Responsable)</label>
                    <input type="text" x-model="formEmpleado.abreviatura" maxlength="4" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Ej. F">
                </div>
            </div>

            <div class="mt-5 space-y-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <label class="flex items-start gap-2 text-sm text-slate-700">
                    <input type="checkbox" x-model="formEmpleado.usuario" class="mt-0.5">
                    <span>
                        <span class="font-medium">Tiene usuario de acceso</span>
                        <span class="block text-xs text-slate-500">Habilita usuario y contraseña para este empleado en el módulo administrativo.</span>
                    </span>
                </label>
                <label class="flex items-start gap-2 text-sm text-slate-700">
                    <input type="checkbox" x-model="formEmpleado.bitacora" class="mt-0.5">
                    <span>
                        <span class="font-medium">Hace parte de la Bitácora</span>
                        <span class="block text-xs text-slate-500">Si se desmarca, este empleado nunca aparece como columna en la Bitácora mensual, aunque registre horas.</span>
                    </span>
                </label>
            </div>
            <p class="mt-2 text-[11px] text-slate-400" x-show="!modoEdicion">
                Todo empleado nuevo se crea activo. El estado se cambia después desde el botón "Activo/Inactivo" de la tabla.
            </p>

            <div class="mt-6 flex justify-end gap-2">
                <button @click="modalNuevoEmpleado = false" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancelar</button>
                <button @click="guardarEmpleado()" class="rounded-xl bg-[#d55b20] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#b8481a]">Guardar empleado</button>
            </div>
        </div>
    </div>

    {{-- Modal CRUD Empresas --}}
    <div x-show="modalEmpresas" x-cloak class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 p-4" style="top:0;left:0;height:100vh;width:100vw;">
        <div @click.outside="modalEmpresas = false" x-show="modalEmpresas" x-transition class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
            <div class="mb-1 flex items-center justify-between">
                <h2 class="text-lg font-bold text-slate-900">Empresas</h2>
                <button @click="modalEmpresas = false" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100"><i data-lucide="x" class="h-5 w-5"></i></button>
            </div>
            <p class="mb-4 text-xs text-slate-500">
                Catálogo de empresas cliente (sitios donde se presta mano de obra) que alimenta el selector de
                Empresa en la Programación. La marcada "Por defecto" se preselecciona ahí automáticamente.
            </p>

            <label class="mb-3 flex items-center gap-2 text-xs text-slate-500">
                <input type="checkbox" x-model="mostrarEmpresasArchivadas">
                Mostrar empresas archivadas
            </label>

            <div class="space-y-2">
                <template x-for="(empresa, ei) in empresas" :key="empresa.nombre">
                    <div class="flex items-center justify-between gap-2 rounded-xl border border-slate-200 p-3" x-show="!empresa.archivada || mostrarEmpresasArchivadas" :class="empresa.archivada ? 'opacity-50' : ''">
                        <div class="flex min-w-0 items-center gap-2">
                            <template x-if="!empresa.editando">
                                <span class="font-semibold text-slate-800" x-text="empresa.nombre"></span>
                            </template>
                            <template x-if="empresa.editando">
                                <input
                                    type="text" x-model="empresa.nombre"
                                    @keydown.enter="empresa.editando = false" @blur="empresa.editando = false"
                                    x-init="$nextTick(() => $el.focus())"
                                    class="rounded-lg border border-slate-300 px-2 py-1 text-sm font-semibold text-slate-800"
                                >
                            </template>
                            <button type="button" @click="empresa.editando = !empresa.editando" class="shrink-0 text-slate-400 hover:text-[#d55b20]" title="Editar nombre">
                                <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                            </button>
                            <span x-show="empresa.defecto" class="shrink-0 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-800">Por defecto</span>
                            <span x-show="empresa.archivada" class="shrink-0 rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-semibold text-slate-600">Archivada</span>
                        </div>
                        <div class="flex shrink-0 items-center gap-3">
                            <button type="button" @click="marcarDefecto(ei)" x-show="!empresa.defecto && !empresa.archivada" class="text-xs font-medium text-[#d55b20] hover:underline">Marcar por defecto</button>
                            <button type="button" @click="archivarEmpresa(ei)" class="text-xs font-medium text-slate-500 hover:text-slate-800" x-text="empresa.archivada ? 'Restablecer' : 'Archivar'"></button>
                        </div>
                    </div>
                </template>
                <template x-if="empresas.filter(e => !e.archivada || mostrarEmpresasArchivadas).length === 0">
                    <p class="px-1 text-xs text-slate-400">Sin empresas para mostrar.</p>
                </template>
            </div>

            <div class="mt-4 flex gap-2 border-t border-slate-200 pt-4">
                <input type="text" x-model="nuevaEmpresaNombre" @keydown.enter="agregarEmpresa()" placeholder="Nombre de la nueva empresa (ej. CEMEX)" class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <button type="button" @click="agregarEmpresa()" class="inline-flex items-center gap-2 rounded-xl bg-[#d55b20] px-4 py-2 text-sm font-semibold text-white hover:bg-[#b8481a]">
                    <i data-lucide="plus" class="h-4 w-4"></i> Nueva empresa
                </button>
            </div>

            <div class="mt-5 flex justify-end">
                <button @click="modalEmpresas = false" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function empleadosPage({ estadosActivos, empleadosData }) {
        const emptyFormEmpleado = () => ({
            nombre: '', nickname: '', abreviatura: null, categoria: 'Campo',
            usuario: false, bitacora: true, activo: true,
        });

        return {
            ...imageExporterMixin('empleados'),
            modalNuevoEmpleado: false,
            modalEmpresas: false,

            estadosActivos,
            mostrarInactivos: false,
            empleadosData,
            modoEdicion: false,
            formEmpleado: emptyFormEmpleado(),
            nuevoEmpleado() {
                this.modoEdicion = false;
                this.formEmpleado = emptyFormEmpleado();
                this.modalNuevoEmpleado = true;
            },
            guardarEmpleado() {
                // El estado activo/inactivo se cambia solo desde el boton
                // dedicado de la tabla, no desde este formulario.
                this.modalNuevoEmpleado = false;
            },
            editarEmpleado(nombre) {
                const emp = this.empleadosData.find(e => e.nombre === nombre);
                if (!emp) return;
                this.modoEdicion = true;
                this.formEmpleado = {
                    nombre: emp.nombre, nickname: emp.nickname, abreviatura: emp.abreviatura,
                    categoria: emp.categoria,
                    usuario: emp.usuario, bitacora: emp.bitacora, activo: this.estadosActivos[emp.nombre],
                };
                this.modalNuevoEmpleado = true;
            },

            // Correccion 2026-09-15: tras confirmar Mantec el Nivel 1
            // ($3.200.000 — Programacion, Diario de Campo, Bitacora), el
            // CRUD de empresas se simplifica a solo nombre/defecto/archivar
            // — sin el sub-CRUD de inducciones obligatorias por empresa
            // (eso era parte de la gestion documental completa que no se
            // contrato, ver seccion 12.4 del documento). "Eliminar" una
            // empresa ahora archiva (no borra), igual que activo/inactivo
            // en empleados — nunca se pierde el registro.
            empresas: [
                { nombre: 'ARGOS', defecto: true, editando: false, archivada: false },
                { nombre: 'CORONA', defecto: false, editando: false, archivada: false },
                { nombre: 'CALIDRA', defecto: false, editando: false, archivada: false },
            ],
            nuevaEmpresaNombre: '',
            mostrarEmpresasArchivadas: false,

            agregarEmpresa() {
                const nombre = this.nuevaEmpresaNombre.trim();
                if (!nombre) return;
                this.empresas.push({ nombre: nombre.toUpperCase(), defecto: false, editando: false, archivada: false });
                this.nuevaEmpresaNombre = '';
                this.$nextTick(() => window.lucide?.createIcons());
            },
            marcarDefecto(idx) {
                this.empresas.forEach((e, i) => { e.defecto = i === idx; });
            },
            archivarEmpresa(idx) {
                const empresa = this.empresas[idx];
                empresa.archivada = !empresa.archivada;
                // Una empresa archivada no puede seguir siendo la de
                // seleccion por defecto en Programacion.
                if (empresa.archivada && empresa.defecto) empresa.defecto = false;
            },
        };
    }
</script>
@endpush
