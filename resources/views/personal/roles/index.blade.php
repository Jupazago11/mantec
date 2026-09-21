@extends('layouts.personal')

@section('title', 'Roles y permisos')

@php
    // Etiquetas cortas para la tabla/modal de subroles — la clave real
    // (columna de personal_roles) es la que viaja en el formulario.
    $etiquetas = [
        'ver_empleados' => 'Empleados',
        'ver_programacion' => 'Programación',
        'editar_programacion_sin_limite' => 'Editar sin límite de hoy/ayer',
        'ver_diario_campo' => 'Diario de Campo',
        'cerrar_diario_campo' => 'Cerrar Diario de Campo',
        'ver_bitacora' => 'Bitácora',
    ];

    // Jerarquia Rol -> Subrol (2026-09-17): "Rol" (PersonalCategory) es el
    // nivel superior — hoy Campo/Administrativos, en el futuro cualquier
    // otro. Confirmado con el usuario: cualquier Rol puede tener subroles
    // con permisos, no hay restriccion de sistema.
    //
    // Pantalla convertida a AJAX completo (2026-09-17): igual que Empleados,
    // se siembra el estado inicial como JSON y toda mutacion (crear/editar
    // Rol o Subrol, archivar/reactivar) va por fetch() + showCrudToast(),
    // sin recargar la pagina.
    $categoriasJs = $categorias->map(fn ($cat) => [
        'id' => $cat->id,
        'name' => $cat->name,
        'activo' => (bool) $cat->activo,
        'responsable_actividad' => (bool) $cat->responsable_actividad,
        'employees_count' => $cat->employees()->count(),
        'roles' => $cat->roles->map(fn ($r) => array_merge([
            'id' => $r->id,
            'name' => $r->name,
            'personal_category_id' => $r->personal_category_id,
            'activo' => (bool) $r->activo,
            'employees_count' => $r->employees_count,
            'disponible_en_programacion' => (bool) $r->disponible_en_programacion,
            'responsable_actividad' => (bool) $r->responsable_actividad,
        ], collect($permisos)->mapWithKeys(fn ($p) => [$p => (bool) $r->{$p}])->all()))->values(),
    ])->values();
@endphp

@section('content')
{{-- Acotado a esta pantalla: a diferencia de Programacion/Diario de
     Campo/Bitacora (tablas de datos que si necesitan scroll horizontal
     real), esta es una tabla de configuracion con pocas filas y muchas
     columnas booleanas cortas — con 12 columnas se desbordaba en pantallas
     "algo pequeñas" sin ninguna pista de que habia que scrollear
     (table-scroll-container oculta la barra de scroll a proposito para
     esas otras pantallas). table-layout:fixed + colgroup fuerza que las
     columnas angostas (Acciones/Estado/Empleados) queden compactas y el
     resto del ancho se reparta entre las demas, dejando que el encabezado
     se envuelva en 2-3 lineas en vez de desbordar. No se toca el CSS
     compartido de layouts/personal.blade.php para no afectar esas otras
     pantallas. --}}
<style>
    .roles-permission-table {
        table-layout: fixed;
        width: 100%;
        min-width: 0;
    }
    {{-- Encabezados de una sola palabra ("Acciones", "Empleados",
         "Responsable", "Programación") no tienen espacio donde envolver
         con solo white-space:normal (heredado del CSS compartido) — sin
         esto se desbordaban igual que antes aunque las frases con varias
         palabras si envolvian bien. overflow-wrap:anywhere permite partir
         la palabra si hace falta, como ultimo recurso. --}}
    .roles-permission-table th {
        overflow-wrap: anywhere;
    }
</style>
<div
    class="mx-auto max-w-[1900px] space-y-4"
    x-data="personalRolesPage({
        storeUrl: @js(route('personal.roles.store')),
        updateUrlTemplate: @js(route('personal.roles.update', ['personalRole' => '__ID__'])),
        toggleActiveUrlTemplate: @js(route('personal.roles.toggle-active', ['personalRole' => '__ID__'])),
        categoriaStoreUrl: @js(route('personal.categorias.store')),
        categoriaUpdateUrlTemplate: @js(route('personal.categorias.update', ['personalCategory' => '__ID__'])),
        categoriaToggleActiveUrlTemplate: @js(route('personal.categorias.toggle-active', ['personalCategory' => '__ID__'])),
        permisos: @js($permisos),
        categoriasIniciales: @js($categoriasJs),
        csrfToken: @js(csrf_token()),
    })"
>
    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900 md:text-2xl">Roles y permisos</h1>
            </div>
            <button type="button" @click.stop="nuevaCategoria()" class="inline-flex items-center gap-2 rounded-xl bg-[#d55b20] px-4 py-2 text-sm font-semibold text-white hover:bg-[#b8481a]">
                <i data-lucide="plus" class="h-4 w-4"></i> Nuevo rol
            </button>
        </div>

        <label class="mt-3 flex items-center gap-2 text-xs text-slate-500">
            <input type="checkbox" x-model="mostrarArchivados">
            Mostrar roles y subroles archivados
        </label>
    </div>

    <template x-if="categorias.length === 0">
        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-400">
            Todavía no hay roles. Crea el primero con "Nuevo rol".
        </div>
    </template>

    <template x-for="cat in categorias" :key="cat.id">
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm" x-show="mostrarArchivados || cat.activo">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
                <div class="flex items-center gap-2">
                    <h2 class="text-base font-bold text-slate-900" x-text="cat.name"></h2>
                    <button
                        type="button"
                        @click="toggleCategoria(cat)"
                        :disabled="cat.toggling"
                        class="rounded-full px-2.5 py-1 text-[11px] font-semibold disabled:cursor-not-allowed disabled:opacity-50"
                        :class="cat.activo ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-slate-200 text-slate-600 hover:bg-slate-300'"
                        :title="cat.activo ? 'Clic para archivar' : 'Clic para reactivar'"
                        x-text="cat.activo ? 'Activo' : 'Archivado'"
                    ></button>
                    <span
                        x-show="cat.responsable_actividad"
                        class="rounded-full bg-blue-100 px-2.5 py-1 text-[11px] font-semibold text-blue-800"
                        title="Los empleados de este Rol pueden elegirse como Responsable al crear una actividad en Programación"
                    >Responsable de actividad</span>
                    <span class="text-xs text-slate-400" x-text="cat.employees_count + ' empleado(s)'"></span>
                </div>
                <div class="flex items-center gap-1.5">
                    <button
                        type="button"
                        @click.stop="nuevoRol(cat.id)"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        <i data-lucide="plus" class="h-3.5 w-3.5"></i> Nuevo subrol
                    </button>
                    <button
                        type="button"
                        @click.stop="editarCategoria(cat)"
                        class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-[#d55b20]" title="Editar nombre"
                    >
                        <i data-lucide="pencil" class="h-4 w-4"></i>
                    </button>
                </div>
            </div>

            <div class="table-scroll-container">
            <table class="preventive-table roles-permission-table divide-y divide-slate-200 text-sm">
                <colgroup>
                    <col style="width:80px">
                    <col style="width:100px">
                    <col style="width:100px">
                    <col style="width:90px">
                    @foreach ($etiquetas as $label)
                        <col>
                    @endforeach
                    <col>
                    <col>
                </colgroup>
                <thead class="sticky-table-head bg-slate-50">
                    <tr>
                        {{-- "Acciones" primero y fijo (sticky-col, mismo patron que la
                             tabla de Programacion) — con la columna nueva de Responsable
                             la tabla ya no cabe en el ancho visible y el scroll horizontal
                             no tiene barra visible (table-scroll-container la oculta a
                             proposito para otras pantallas), asi que el lapiz de editar
                             quedaba fuera de vista sin ninguna pista de que habia que
                             scrollear. Ahora ademas table-layout:fixed + colgroup dejan
                             que el encabezado se envuelva en 2-3 lineas en pantallas mas
                             pequeñas en vez de desbordar. --}}
                        <th class="sticky-col px-2 py-2 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">Acciones</th>
                        <th class="px-2 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Subrol</th>
                        <th class="px-2 py-2 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">Estado</th>
                        <th class="px-2 py-2 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">Empleados</th>
                        {{-- Sin min-width inline: con table-layout:fixed el ancho real
                             lo determina el <colgroup> de arriba, y el texto se envuelve
                             (white-space:normal ya viene del CSS compartido) en vez de
                             forzar que la columna crezca. --}}
                        @foreach ($etiquetas as $label)
                            <th class="px-2 py-2 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ $label }}</th>
                        @endforeach
                        <th class="px-2 py-2 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">Disponible en Programación</th>
                        <th class="px-2 py-2 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">Responsable de actividad</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    <template x-if="cat.roles.length === 0">
                        <tr>
                            <td colspan="{{ 6 + count($etiquetas) }}" class="px-4 py-6 text-center text-sm text-slate-400">
                                Este rol todavía no tiene subroles.
                            </td>
                        </tr>
                    </template>
                    <template x-for="rol in cat.roles" :key="rol.id">
                        <tr class="hover:bg-slate-50" x-show="mostrarArchivados || rol.activo">
                            <td class="sticky-col px-2 py-2">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button
                                        type="button"
                                        @click.stop="editarRol(rol)"
                                        class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-[#d55b20]" title="Editar"
                                    >
                                        <i data-lucide="pencil" class="h-4 w-4"></i>
                                    </button>
                                </div>
                            </td>
                            <td class="px-2 py-2 font-medium text-slate-800" x-text="rol.name"></td>
                            <td class="px-2 py-2 text-center">
                                <button
                                    type="button"
                                    @click="toggleRol(rol)"
                                    :disabled="rol.toggling"
                                    class="rounded-full px-2.5 py-1 text-[11px] font-semibold disabled:cursor-not-allowed disabled:opacity-50"
                                    :class="rol.activo ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-slate-200 text-slate-600 hover:bg-slate-300'"
                                    :title="rol.activo ? 'Clic para archivar' : 'Clic para reactivar'"
                                    x-text="rol.activo ? 'Activo' : 'Archivado'"
                                ></button>
                            </td>
                            <td class="px-2 py-2 text-center text-slate-500" x-text="rol.employees_count"></td>
                            @foreach (array_keys($etiquetas) as $permiso)
                                <td class="px-2 py-2 text-center">
                                    <i x-show="rol.{{ $permiso }}" data-lucide="check" class="mx-auto h-4 w-4 text-emerald-600"></i>
                                    <i x-show="!rol.{{ $permiso }}" data-lucide="minus" class="mx-auto h-4 w-4 text-slate-300"></i>
                                </td>
                            @endforeach
                            <td class="px-2 py-2 text-center">
                                <i x-show="rol.disponible_en_programacion" data-lucide="check" class="mx-auto h-4 w-4 text-emerald-600"></i>
                                <i x-show="!rol.disponible_en_programacion" data-lucide="minus" class="mx-auto h-4 w-4 text-slate-300"></i>
                            </td>
                            {{-- Muestra el efecto REAL (Rol Y Subrol), no solo el flag
                                 crudo del subrol — un subrol marcado con el Rol apagado
                                 no otorga nada, y mostrarlo en verde seria enganoso. --}}
                            <td class="px-2 py-2 text-center">
                                <i x-show="cat.responsable_actividad && rol.responsable_actividad" data-lucide="check" class="mx-auto h-4 w-4 text-emerald-600"></i>
                                <i x-show="!(cat.responsable_actividad && rol.responsable_actividad)" data-lucide="minus" class="mx-auto h-4 w-4 text-slate-300"></i>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
            </div>
        </div>
    </template>

    {{-- Modal nuevo/editar Rol (PersonalCategory). Teletransportado a
         <body> (mismo mecanismo que los combobox de Programacion) — el
         fondo oscuro no estaba cubriendo el sidebar por como queda anidado
         este modal dentro de <main>, igual que el bug ya documentado del
         wizard de cambio de banda en ANALISIS_SISTEMA_LARAVEL.md. --}}
    <template x-teleport="body">
    <div x-show="modalCategoriaAbierto" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 p-4" style="top:0;left:0;height:100vh;width:100vw;">
        <div @click.outside="modalCategoriaAbierto = false" x-show="modalCategoriaAbierto" x-transition class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
            <form @submit.prevent="guardarCategoria()">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-slate-900" x-text="modoEdicionCategoria ? 'Editar rol' : 'Nuevo rol'"></h2>
                    <button type="button" @click="modalCategoriaAbierto = false" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100"><i data-lucide="x" class="h-5 w-5"></i></button>
                </div>

                <template x-if="formErrorsCategoria.length">
                    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <ul class="list-inside list-disc space-y-0.5">
                            <template x-for="error in formErrorsCategoria" :key="error">
                                <li x-text="error"></li>
                            </template>
                        </ul>
                    </div>
                </template>

                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Nombre del rol <span class="text-red-500">*</span></label>
                    <input type="text" x-model="formCategoria.name" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Ej. Campo">
                </div>

                <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" x-model="formCategoria.responsable_actividad">
                        <span>Responsable de actividad</span>
                    </label>
                    <p class="mt-1 text-[11px] text-slate-400">
                        Todo empleado de este Rol (en cualquiera de sus subroles) podrá elegirse como "Responsable
                        (supervisor)" al crear una actividad en Programación.
                    </p>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" @click="modalCategoriaAbierto = false" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancelar</button>
                    <button type="submit" :disabled="guardandoCategoria" class="rounded-xl bg-[#d55b20] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#b8481a] disabled:cursor-not-allowed disabled:opacity-60" x-text="guardandoCategoria ? 'Guardando…' : 'Guardar rol'"></button>
                </div>
            </form>
        </div>
    </div>
    </template>

    {{-- Modal nuevo/editar Subrol (PersonalRole). Tambien teletransportado,
         mismo motivo que el modal de Rol de arriba. --}}
    <template x-teleport="body">
    <div x-show="modalAbierto" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 p-4" style="top:0;left:0;height:100vh;width:100vw;">
        <div @click.outside="modalAbierto = false" x-show="modalAbierto" x-transition class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
            <form @submit.prevent="guardarRol()">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-slate-900" x-text="modoEdicion ? 'Editar subrol' : 'Nuevo subrol'"></h2>
                    <button type="button" @click="modalAbierto = false" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100"><i data-lucide="x" class="h-5 w-5"></i></button>
                </div>

                <template x-if="formErrorsRol.length">
                    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <ul class="list-inside list-disc space-y-0.5">
                            <template x-for="error in formErrorsRol" :key="error">
                                <li x-text="error"></li>
                            </template>
                        </ul>
                    </div>
                </template>

                {{-- El Rol no se elige aqui: ya quedo definido por el boton
                     "+ Nuevo subrol" de esa seccion (o, al editar, por el
                     subrol que se abrio) — mostrar un select editable aqui
                     era confuso/redundante (se podia cambiar por error). --}}
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Nombre del subrol <span class="text-red-500">*</span></label>
                    <input type="text" x-model="formRol.name" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Ej. SISO">
                    <p class="mt-1 text-[11px] text-slate-400">
                        Rol: <span class="font-semibold text-slate-600" x-text="nombreCategoriaActual()"></span>
                    </p>
                </div>

                <p class="mb-2 mt-5 text-xs font-medium text-slate-600">Módulos que ve y puede usar</p>
                <div class="space-y-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    @foreach ($etiquetas as $permiso => $label)
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" x-model="formRol.{{ $permiso }}">
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>

                <p class="mb-2 mt-5 text-xs font-medium text-slate-600">Otra configuración</p>
                <div class="space-y-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div>
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" x-model="formRol.disponible_en_programacion">
                            <span>Aparece como persona seleccionable en Programación</span>
                        </label>
                        <p class="mt-1 text-[11px] text-slate-400">
                            No es un permiso de acceso — solo controla si los empleados con este subrol se pueden agregar
                            como "Personas de la actividad" al crear una actividad.
                        </p>
                    </div>
                    <div class="border-t border-slate-200 pt-3">
                        <label class="flex items-center gap-2 text-sm" :class="categoriaActualResponsable() ? 'text-slate-700' : 'text-slate-400'">
                            <input type="checkbox" x-model="formRol.responsable_actividad" :disabled="!categoriaActualResponsable()">
                            <span>Responsable de actividad</span>
                        </label>
                        <template x-if="categoriaActualResponsable()">
                            <p class="mt-1 text-[11px] text-slate-400">
                                Tampoco es un permiso de acceso — controla si los empleados con este subrol pueden
                                elegirse como "Responsable (supervisor)" al crear una actividad.
                            </p>
                        </template>
                        <template x-if="!categoriaActualResponsable()">
                            <p class="mt-1 text-[11px] text-amber-600">
                                El Rol "<span x-text="nombreCategoriaActual()"></span>" no tiene activo "Responsable de
                                actividad" — actívalo primero ahí (botón de lápiz junto al nombre del Rol) para poder
                                marcar subroles específicos aquí.
                            </p>
                        </template>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" @click="modalAbierto = false" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancelar</button>
                    <button type="submit" :disabled="guardandoRol" class="rounded-xl bg-[#d55b20] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#b8481a] disabled:cursor-not-allowed disabled:opacity-60" x-text="guardandoRol ? 'Guardando…' : 'Guardar subrol'"></button>
                </div>
            </form>
        </div>
    </div>
    </template>
</div>
@endsection

@push('scripts')
<script>
    function personalRolesPage({
        storeUrl, updateUrlTemplate, toggleActiveUrlTemplate,
        categoriaStoreUrl, categoriaUpdateUrlTemplate, categoriaToggleActiveUrlTemplate,
        permisos, categoriasIniciales, csrfToken,
    }) {
        const emptyFormRol = (categoryId = null) => {
            const form = { name: '', personal_category_id: categoryId, disponible_en_programacion: true, responsable_actividad: false };
            permisos.forEach((p) => { form[p] = false; });
            return form;
        };
        const emptyFormCategoria = () => ({ name: '', responsable_actividad: false });

        const jsonHeaders = () => ({
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        });

        return {
            mostrarArchivados: false,
            // "toggling" se inicializa aqui (no en el JSON del backend):
            // dejarlo undefined hasta el primer clic provoca que
            // :disabled="cat.toggling" quede pegado en "disabled" en la
            // carga en frio (bug real, confirmado con Alpine 3.17.3 — el
            // binding no re-evalua hasta que otro efecto de ese mismo scope
            // "despierta" la reactividad). Inicializar el key explicito
            // desde el primer render lo evita.
            categorias: categoriasIniciales.map((cat) => ({
                ...cat,
                toggling: false,
                roles: cat.roles.map((rol) => ({ ...rol, toggling: false })),
            })),
            guardandoCategoria: false,
            guardandoRol: false,
            formErrorsCategoria: [],
            formErrorsRol: [],

            nombreCategoriaActual() {
                return this.categorias.find((c) => c.id === this.formRol.personal_category_id)?.name ?? '';
            },
            categoriaActualResponsable() {
                return this.categorias.find((c) => c.id === this.formRol.personal_category_id)?.responsable_actividad ?? false;
            },

            // --- Subrol (PersonalRole) ---
            modalAbierto: false,
            modoEdicion: false,
            rolId: null,
            formAction: storeUrl,
            formMethod: 'POST',
            formRol: emptyFormRol(categoriasIniciales[0]?.id ?? null),

            // Mismo criterio que Empleados/Programacion: cerrar el modal
            // nunca borra el borrador, solo lo oculta.
            // Cada Rol tiene su propio boton "+ Nuevo subrol" — hay que
            // resetear el formulario no solo cuando se viene de editar,
            // sino tambien cuando el categoryId del boton presionado es
            // distinto al que ya tenia el borrador (si no, clic en el boton
            // de un Rol distinto seguia mostrando el Rol del intento
            // anterior en vez del que realmente se presiono).
            nuevoRol(categoryId = null) {
                const targetCategoryId = categoryId ?? (this.categorias[0]?.id ?? null);

                if (this.modoEdicion || this.formRol.personal_category_id !== targetCategoryId) {
                    this.modoEdicion = false;
                    this.rolId = null;
                    this.formAction = storeUrl;
                    this.formMethod = 'POST';
                    this.formRol = emptyFormRol(targetCategoryId);
                }
                this.formErrorsRol = [];
                this.modalAbierto = true;
            },
            editarRol(rol) {
                if (!(this.modoEdicion && this.rolId === rol.id)) {
                    this.modoEdicion = true;
                    this.rolId = rol.id;
                    this.formAction = updateUrlTemplate.replace('__ID__', rol.id);
                    this.formMethod = 'PUT';
                    this.formRol = { ...emptyFormRol(), ...rol };
                }
                this.formErrorsRol = [];
                this.modalAbierto = true;
            },
            async guardarRol() {
                this.guardandoRol = true;
                this.formErrorsRol = [];

                try {
                    const payload = {
                        name: this.formRol.name,
                        personal_category_id: this.formRol.personal_category_id,
                        disponible_en_programacion: this.formRol.disponible_en_programacion,
                        responsable_actividad: this.formRol.responsable_actividad,
                    };
                    permisos.forEach((p) => { payload[p] = this.formRol[p]; });

                    const res = await fetch(this.formAction, { method: this.formMethod, headers: jsonHeaders(), body: JSON.stringify(payload) });
                    const data = await res.json().catch(() => null);

                    if (res.status === 422 && data?.errors) {
                        this.formErrorsRol = Object.values(data.errors).flat();
                        showCrudToast(this.formErrorsRol, 'error');
                        return;
                    }
                    if (!res.ok || !data?.success) {
                        showCrudToast(data?.message || 'No se pudo guardar el subrol.', 'error');
                        return;
                    }

                    const cat = this.categorias.find((c) => c.id === data.role.personal_category_id);
                    if (cat) {
                        const idx = cat.roles.findIndex((r) => r.id === data.role.id);
                        // toggling:false explicito por la misma razon que en
                        // la carga inicial — data.role viene del backend sin
                        // ese key transitorio de UI.
                        if (idx !== -1) {
                            cat.roles[idx] = { ...data.role, toggling: false };
                        } else {
                            cat.roles.push({ ...data.role, toggling: false });
                        }
                    }

                    this.modoEdicion = false;
                    this.formRol = emptyFormRol();
                    this.modalAbierto = false;
                    showCrudToast(data.message || 'Subrol guardado correctamente.', 'success');
                    this.$nextTick(() => window.lucide?.createIcons());
                } catch (e) {
                    console.error('guardarRol:', e);
                    showCrudToast('No se pudo guardar el subrol (error de red).', 'error');
                } finally {
                    this.guardandoRol = false;
                }
            },
            async toggleRol(rol) {
                if (rol.toggling) return;
                rol.toggling = true;

                try {
                    const url = toggleActiveUrlTemplate.replace('__ID__', rol.id);
                    const res = await fetch(url, { method: 'PATCH', headers: jsonHeaders() });
                    const data = await res.json().catch(() => null);

                    if (!res.ok || !data?.success) {
                        showCrudToast(data?.message || 'No se pudo cambiar el estado del subrol.', 'error');
                        return;
                    }

                    Object.assign(rol, data.role);
                    showCrudToast(data.message || 'Estado actualizado correctamente.', 'success');
                } catch (e) {
                    console.error('toggleRol:', e);
                    showCrudToast('No se pudo cambiar el estado del subrol (error de red).', 'error');
                } finally {
                    rol.toggling = false;
                }
            },

            // --- Rol (PersonalCategory) ---
            modalCategoriaAbierto: false,
            modoEdicionCategoria: false,
            categoriaId: null,
            formCategoriaAction: categoriaStoreUrl,
            formCategoriaMethod: 'POST',
            formCategoria: emptyFormCategoria(),

            nuevaCategoria() {
                if (this.modoEdicionCategoria) {
                    this.modoEdicionCategoria = false;
                    this.categoriaId = null;
                    this.formCategoriaAction = categoriaStoreUrl;
                    this.formCategoriaMethod = 'POST';
                    this.formCategoria = emptyFormCategoria();
                }
                this.formErrorsCategoria = [];
                this.modalCategoriaAbierto = true;
            },
            editarCategoria(cat) {
                if (!(this.modoEdicionCategoria && this.categoriaId === cat.id)) {
                    this.modoEdicionCategoria = true;
                    this.categoriaId = cat.id;
                    this.formCategoriaAction = categoriaUpdateUrlTemplate.replace('__ID__', cat.id);
                    this.formCategoriaMethod = 'PUT';
                    this.formCategoria = { name: cat.name, responsable_actividad: cat.responsable_actividad };
                }
                this.formErrorsCategoria = [];
                this.modalCategoriaAbierto = true;
            },
            async guardarCategoria() {
                this.guardandoCategoria = true;
                this.formErrorsCategoria = [];

                try {
                    const res = await fetch(this.formCategoriaAction, { method: this.formCategoriaMethod, headers: jsonHeaders(), body: JSON.stringify({
                        name: this.formCategoria.name,
                        responsable_actividad: this.formCategoria.responsable_actividad,
                    }) });
                    const data = await res.json().catch(() => null);

                    if (res.status === 422 && data?.errors) {
                        this.formErrorsCategoria = Object.values(data.errors).flat();
                        showCrudToast(this.formErrorsCategoria, 'error');
                        return;
                    }
                    if (!res.ok || !data?.success) {
                        showCrudToast(data?.message || 'No se pudo guardar el rol.', 'error');
                        return;
                    }

                    const idx = this.categorias.findIndex((c) => c.id === data.category.id);
                    if (idx !== -1) {
                        Object.assign(this.categorias[idx], data.category);
                    } else {
                        this.categorias.push({ ...data.category, toggling: false, roles: [] });
                    }

                    this.modoEdicionCategoria = false;
                    this.formCategoria = emptyFormCategoria();
                    this.modalCategoriaAbierto = false;
                    showCrudToast(data.message || 'Rol guardado correctamente.', 'success');
                } catch (e) {
                    console.error('guardarCategoria:', e);
                    showCrudToast('No se pudo guardar el rol (error de red).', 'error');
                } finally {
                    this.guardandoCategoria = false;
                }
            },
            async toggleCategoria(cat) {
                if (cat.toggling) return;
                cat.toggling = true;

                try {
                    const url = categoriaToggleActiveUrlTemplate.replace('__ID__', cat.id);
                    const res = await fetch(url, { method: 'PATCH', headers: jsonHeaders() });
                    const data = await res.json().catch(() => null);

                    if (!res.ok || !data?.success) {
                        showCrudToast(data?.message || 'No se pudo cambiar el estado del rol.', 'error');
                        return;
                    }

                    Object.assign(cat, data.category);
                    showCrudToast(data.message || 'Estado actualizado correctamente.', 'success');
                } catch (e) {
                    console.error('toggleCategoria:', e);
                    showCrudToast('No se pudo cambiar el estado del rol (error de red).', 'error');
                } finally {
                    cat.toggling = false;
                }
            },
        };
    }
</script>
@endpush
