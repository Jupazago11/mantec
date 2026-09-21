@extends('layouts.personal')

@section('title', 'Empleados')

@php
    // empresasJs: traduce Company (columnas en ingles, mismo criterio que
    // `clients`) a las mismas claves en español que ya usaba el mockup
    // portado (preview-personal/empleados.blade.php), para reusar su
    // plantilla Alpine casi sin cambios.
    $empresasJs = $empresas->map(fn ($c) => [
        'id' => $c->id,
        'nombre' => $c->name,
        'defecto' => $c->is_default,
        'archivada' => $c->archived,
        'editando' => false,
    ])->values();

    // eligibleResponsableRoleIds: mismo criterio que ActivityController
    // (PersonalRole::eligibleAsResponsableIds()) — solo empleados con un
    // subrol elegible como Responsable muestran el boton "Ver como
    // supervisor" (seccion 14.18: "eso es solo para los roles que son
    // responsables, no para todos").
    $eligibleResponsableRoleIds = \App\Models\PersonalRole::eligibleAsResponsableIds()->all();

    // empleadosJs: misma idea que empresasJs — la tabla pasa de un
    // @forelse estatico a un array reactivo Alpine para que crear/editar/
    // activar-inactivar no requiera recargar la pagina (mismo patron que
    // Empresas en este archivo).
    $empleadosJs = $empleados->map(fn ($e) => [
        'id' => $e->id,
        'nombre' => $e->nombre,
        'nickname' => $e->nickname,
        'personal_category_id' => $e->personal_category_id,
        'categoria' => $e->personalCategory->name,
        'has_login' => (bool) $e->has_login,
        'username' => $e->username,
        'personal_role_id' => $e->personal_role_id,
        'personal_role_name' => $e->personalRole?->name,
        'in_bitacora' => (bool) $e->in_bitacora,
        'activo' => (bool) $e->activo,
        'es_responsable' => in_array($e->personal_role_id, $eligibleResponsableRoleIds, true),
    ])->values();

    // rolesJs: subroles disponibles para el select "Subrol de acceso",
    // filtrados en JS por Rol (categoria) elegida — ver rolesFiltrados().
    $rolesJs = $roles->map(fn ($r) => [
        'id' => $r->id,
        'name' => $r->name,
        'personal_category_id' => $r->personal_category_id,
    ])->values();
@endphp

@section('content')
<div
    class="mx-auto max-w-[1900px] space-y-4"
    x-data="personalEmpleadosPage({
        storeUrl: @js(route('personal.empleados.store')),
        updateUrlTemplate: @js(route('personal.empleados.update', ['employee' => '__ID__'])),
        toggleStatusUrlTemplate: @js(route('personal.empleados.toggle-status', ['employee' => '__ID__'])),
        empresasStoreUrl: @js(route('personal.empresas.store')),
        empresasUpdateUrlTemplate: @js(route('personal.empresas.update', ['company' => '__ID__'])),
        empresasToggleArchivedUrlTemplate: @js(route('personal.empresas.toggle-archived', ['company' => '__ID__'])),
        empresasMarkDefaultUrlTemplate: @js(route('personal.empresas.mark-default', ['company' => '__ID__'])),
        verComoUrlTemplate: @js(route('personal.ver-como.index', ['employee' => '__ID__'])),
        csrfToken: @js(csrf_token()),
        empresasIniciales: @js($empresasJs),
        empleadosIniciales: @js($empleadosJs),
        roles: @js($rolesJs),
        categorias: @js($categorias->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values()),
    })"
>
    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900 md:text-2xl">Empleados</h1>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button
                    @click="exportarTablaEmpleados()"
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

        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
            <label class="flex items-center gap-2 text-xs text-slate-500">
                <input type="checkbox" x-model="mostrarInactivos" @change="paginaActual = 1">
                Mostrar empleados inactivos
            </label>
            <div class="flex items-center gap-3 text-xs text-slate-500">
                <span x-text="rangoPaginaTexto()"></span>
                <button type="button" @click="limpiarColumnFilters()" x-show="hayFiltrosActivos()" class="font-medium text-[#d55b20] hover:underline">Limpiar filtros</button>
            </div>
        </div>
    </div>

    {{-- areaExportable: lo que captura "Copiar como imagen" — durante la
         exportacion, mostrandoTodasParaExport hace que la tabla muestre
         TODAS las filas filtradas (no solo la pagina actual), igual que
         antes de que existiera paginacion. --}}
    <div
        id="areaExportable"
        class="space-y-2"
        x-init="calcularFilasPorPagina($el); window.addEventListener('resize', () => calcularFilasPorPagina($el))"
        x-effect="const tp = totalPaginas(); if (paginaActual > tp) paginaActual = tp; if (paginaActual < 1) paginaActual = 1;"
    >
        <p class="text-sm font-semibold text-slate-700">Empleados</p>
        <div class="compact-table-wrapper rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="table-scroll-container">
        <table class="preventive-table divide-y divide-slate-200 text-sm">
            <thead class="sticky-table-head bg-slate-50">
                <tr>
                    @include('personal.empleados._th-filter', ['field' => 'nombre', 'label' => 'Nombre completo', 'minWidth' => '14rem'])
                    @include('personal.empleados._th-filter', ['field' => 'nickname', 'label' => 'Nickname'])
                    @include('personal.empleados._th-filter', ['field' => 'categoria', 'label' => 'Categoría'])
                    @include('personal.empleados._th-filter', ['field' => 'rol', 'label' => 'Rol'])
                    @include('personal.empleados._th-filter', ['field' => 'usuario', 'label' => 'Usuario'])
                    @include('personal.empleados._th-filter', ['field' => 'bitacora', 'label' => 'Bitácora', 'align' => 'center'])
                    <th class="px-4 py-2 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                <template x-if="empleadosFiltrados().length === 0">
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-400">
                            Ningún empleado coincide con los filtros aplicados.
                        </td>
                    </tr>
                </template>
                <template x-for="emp in empleadosPaginaActual()" :key="emp.id">
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-2 font-medium text-slate-800" x-text="emp.nombre"></td>
                        <td class="whitespace-nowrap px-4 py-2 text-slate-600" x-text="emp.nickname"></td>
                        <td class="whitespace-nowrap px-4 py-2">
                            <span
                                class="inline-flex items-center rounded-xl px-3 py-1 text-[11px] font-semibold"
                                :class="emp.categoria === 'Campo' ? 'bg-emerald-100 text-emerald-800' : 'bg-indigo-100 text-indigo-800'"
                                x-text="emp.categoria"
                            ></span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-2 text-slate-600" x-text="emp.personal_role_name || '—'"></td>
                        <td class="whitespace-nowrap px-4 py-2 text-slate-600" x-text="emp.has_login ? emp.username : '—'"></td>
                        <td class="px-4 py-2 text-center">
                            <i x-show="emp.in_bitacora" data-lucide="check-circle-2" class="mx-auto h-4 w-4 text-emerald-600"></i>
                            <i x-show="!emp.in_bitacora" data-lucide="minus-circle" class="mx-auto h-4 w-4 text-slate-300" title="No hace parte de la Bitácora"></i>
                        </td>
                        <td class="px-4 py-2">
                            <div class="flex items-center justify-center gap-1.5">
                                <button
                                    type="button"
                                    @click="editarEmpleado(emp)"
                                    class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-[#d55b20]" title="Editar"
                                >
                                    <i data-lucide="pencil" class="h-4 w-4"></i>
                                </button>
                                <button
                                    type="button"
                                    @click="toggleEmpleado(emp)"
                                    :disabled="emp.toggling"
                                    class="rounded-full px-2 py-1 text-[10px] font-semibold disabled:cursor-not-allowed disabled:opacity-50"
                                    :class="emp.activo ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-slate-200 text-slate-600 hover:bg-slate-300'"
                                    x-text="emp.activo ? 'Activo' : 'Inactivo'"
                                ></button>
                                @if (\App\Support\PersonalGuard::isSuperadmin())
                                    {{-- "Ver como supervisor" (pedido 2026-09-19, seccion
                                         14.18): prototipo de la pantalla que eventualmente
                                         sera la app Android real (seccion 7) — no cambia la
                                         sesion de superadmin (se abre en pestaña nueva),
                                         solo consulta/opera sobre los datos del empleado
                                         elegido. Server-side gated en
                                         SupervisorViewController, no solo oculto aqui. Solo
                                         para roles elegibles como Responsable (es_responsable,
                                         pedido 2026-09-19: "no para todos"), mismo criterio
                                         que el selector de Responsable en Programacion. --}}
                                    <a
                                        x-show="emp.es_responsable"
                                        :href="verComoUrlTemplate.replace('__ID__', emp.id)"
                                        target="_blank"
                                        rel="noopener"
                                        class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-[#d55b20]"
                                        title="Ver como supervisor (pestaña nueva)"
                                    >
                                        <i data-lucide="external-link" class="h-4 w-4"></i>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
        </div>
        </div>
    </div>

    {{-- Paginacion — fuera de #areaExportable a proposito: exportar como
         imagen debe seguir mostrando TODAS las filas filtradas (ver
         mostrandoTodasParaExport), no solo la pagina visible. filasPorPagina
         se recalcula segun el alto disponible de pantalla (calcularFilasPorPagina),
         asi que un monitor grande cabe mas filas que un laptop. --}}
    <div class="mt-2 flex items-center justify-between gap-3" x-show="totalPaginas() > 1">
        <button
            type="button"
            @click="paginaActual--"
            :disabled="paginaActual <= 1"
            class="inline-flex items-center gap-1 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
        >
            <i data-lucide="chevron-left" class="h-3.5 w-3.5"></i> Anterior
        </button>
        <span class="text-xs text-slate-500">Página <span class="font-semibold text-slate-700" x-text="paginaActual"></span> de <span class="font-semibold text-slate-700" x-text="totalPaginas()"></span></span>
        <button
            type="button"
            @click="paginaActual++"
            :disabled="paginaActual >= totalPaginas()"
            class="inline-flex items-center gap-1 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
        >
            Siguiente <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
        </button>
    </div>

    {{-- Toast de resultado de la exportacion --}}
    <div x-show="mensajeExport" x-cloak x-transition class="fixed bottom-4 right-4 z-50 max-w-xs rounded-xl bg-slate-900 px-4 py-3 text-sm text-white shadow-xl" x-text="mensajeExport"></div>

    {{-- Modal nuevo/editar empleado — formulario real (POST/PUT), no solo
         estado local Alpine: guardarEmpleado() en el mockup no persistia
         nada, aqui si. --}}
    <div x-show="modalNuevoEmpleado" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 p-4" style="top:0;left:0;height:100vh;width:100vw;">
        <div @click.outside="modalNuevoEmpleado = false" x-show="modalNuevoEmpleado" x-transition class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
            <form @submit.prevent="guardarEmpleado()">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-slate-900" x-text="modoEdicion ? 'Editar empleado' : 'Nuevo empleado'"></h2>
                    <button type="button" @click="modalNuevoEmpleado = false" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100"><i data-lucide="x" class="h-5 w-5"></i></button>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-medium text-slate-600">Nombre completo <span class="text-red-500">*</span></label>
                        <input type="text" name="nombre" x-model="formEmpleado.nombre" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Ej. Luis Fernando Montoya Zuluaga">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Nickname (display corto) <span class="text-red-500">*</span></label>
                        <input type="text" name="nickname" x-model="formEmpleado.nickname" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Ej. Fernando">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Rol <span class="text-red-500">*</span></label>
                        <input type="hidden" name="personal_category_id" :value="formEmpleado.personal_category_id">
                        <div class="inline-flex flex-wrap gap-0.5 rounded-lg border border-slate-300 p-0.5">
                            <template x-for="cat in categorias" :key="cat.id">
                                <button
                                    type="button"
                                    @click="seleccionarCategoria(cat.id)"
                                    class="rounded-md px-3 py-1.5 text-sm font-medium transition"
                                    :class="formEmpleado.personal_category_id === cat.id ? 'bg-[#d55b20] text-white' : 'text-slate-600 hover:bg-slate-50'"
                                    x-text="cat.name"
                                ></button>
                            </template>
                        </div>
                    </div>
                    {{-- El subrol ya no depende de "Tiene usuario de acceso":
                         se resuelve apenas se elige el Rol de arriba, tenga o
                         no login (ej. un supervisor de Campo sin usuario
                         igual necesita su subrol para Programación). --}}
                    <div
                        class="sm:col-span-2"
                        x-effect="
                            const opciones = rolesFiltrados();
                            if (opciones.length === 1) { formEmpleado.personal_role_id = opciones[0].id; }
                            else if (!opciones.some((r) => r.id === formEmpleado.personal_role_id)) { formEmpleado.personal_role_id = ''; }
                        "
                    >
                        <label class="mb-1 block text-xs font-medium text-slate-600">Subrol</label>
                        <input type="hidden" name="personal_role_id" :value="formEmpleado.personal_role_id">

                        <template x-if="rolesFiltrados().length === 0">
                            <p class="text-xs text-slate-400">Este Rol no tiene subroles configurados.</p>
                        </template>

                        <template x-if="rolesFiltrados().length === 1">
                            <p class="text-xs text-slate-600">
                                Asignado automáticamente: <span class="font-semibold" x-text="rolesFiltrados()[0].name"></span>
                            </p>
                        </template>

                        <template x-if="rolesFiltrados().length > 1">
                            <div>
                                <div class="inline-flex flex-wrap gap-0.5 rounded-lg border border-slate-300 p-0.5">
                                    <template x-for="rol in rolesFiltrados()" :key="rol.id">
                                        <button
                                            type="button"
                                            @click="formEmpleado.personal_role_id = rol.id"
                                            class="rounded-md px-3 py-1.5 text-sm font-medium transition"
                                            :class="formEmpleado.personal_role_id === rol.id ? 'bg-[#d55b20] text-white' : 'text-slate-600 hover:bg-slate-50'"
                                            x-text="rol.name"
                                        ></button>
                                    </template>
                                </div>
                                <p class="mt-1 text-[11px] text-slate-400" x-show="!formEmpleado.personal_role_id">
                                    Este Rol tiene varios subroles — elige uno.
                                </p>
                            </div>
                        </template>

                        <p class="mt-1 text-[11px] text-slate-400">
                            Los subroles y sus permisos se administran desde "Roles y permisos" (solo superadmin).
                        </p>
                    </div>
                </div>

                <div class="mt-5 space-y-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <label class="flex items-start gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="has_login" value="1" x-model="formEmpleado.has_login" class="mt-0.5">
                        <span class="font-medium">Tiene usuario de acceso</span>
                    </label>

                    <div x-show="formEmpleado.has_login" x-cloak class="grid gap-3 pl-6 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Usuario <span class="text-red-500">*</span></label>
                            <input type="text" name="username" x-model="formEmpleado.username" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Ej. lfernando">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">
                                Contraseña <span class="text-red-500" x-show="!modoEdicion">*</span>
                                <span class="text-slate-400" x-show="modoEdicion">(vacío = mantener la actual)</span>
                            </label>
                            <input type="password" name="password" x-model="formEmpleado.password" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        </div>
                    </div>

                    <label class="flex items-start gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="in_bitacora" value="1" x-model="formEmpleado.in_bitacora" class="mt-0.5">
                        <span class="font-medium">Hace parte de la Bitácora</span>
                    </label>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" @click="modalNuevoEmpleado = false" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancelar</button>
                    <button type="submit" :disabled="guardando" class="rounded-xl bg-[#d55b20] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#b8481a] disabled:cursor-not-allowed disabled:opacity-60" x-text="guardando ? 'Guardando...' : 'Guardar empleado'"></button>
                </div>
            </form>
        </div>
    </div>

    @include('personal.empleados._modal-empresas')
</div>
@endsection

@push('scripts')
<script>
    function personalEmpleadosPage({
        storeUrl, updateUrlTemplate, toggleStatusUrlTemplate,
        empresasStoreUrl, empresasUpdateUrlTemplate, empresasToggleArchivedUrlTemplate, empresasMarkDefaultUrlTemplate,
        verComoUrlTemplate, csrfToken, empresasIniciales, empleadosIniciales, roles, categorias,
    }) {
        // categoryId/roleId por defecto vienen del ULTIMO empleado creado
        // (no editado), no siempre de categorias[0] — al crear decenas de
        // empleados seguidos del mismo Rol/Subrol, repetir el mismo clic
        // cada vez es tiempo perdido. Ver ultimaCategoriaCreada/ultimoRolCreado.
        const emptyFormEmpleado = (categoryId = null, roleId = null) => ({
            id: null, nombre: '', nickname: '', personal_category_id: categoryId ?? (categorias[0]?.id ?? null),
            has_login: false, username: '', password: '', personal_role_id: roleId ?? '', in_bitacora: true,
        });

        const jsonHeaders = () => ({
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        });

        return {
            ...imageExporterMixin('empleados'),
            // La tabla ahora esta paginada (ver empleadosPaginaActual), pero
            // "Copiar como imagen" debe seguir capturando TODAS las filas
            // filtradas como hacia antes — exportarTablaEmpleados() envuelve
            // el metodo del mixin para forzar esto durante la captura.
            mostrandoTodasParaExport: false,
            async exportarTablaEmpleados() {
                this.mostrandoTodasParaExport = true;
                await this.$nextTick();
                await this.exportarComoImagen();
                this.mostrandoTodasParaExport = false;
            },
            verComoUrlTemplate,
            modalNuevoEmpleado: false,
            modalEmpresas: false,
            mostrarInactivos: false,
            modoEdicion: false,
            guardando: false,
            formErrors: [],
            formAction: storeUrl,
            formMethod: 'POST',
            formEmpleado: emptyFormEmpleado(),
            ultimaCategoriaCreada: null,
            ultimoRolCreado: null,

            // Empleados — mismo patron que ya usa Empresas en este archivo:
            // fetch() + array reactivo, sin recargar la pagina.
            // toggling:false explicito desde el inicio — dejarlo undefined
            // hasta el primer clic deja :disabled="emp.toggling" pegado en
            // "disabled" en la carga en frio (bug confirmado con Alpine
            // 3.17.3 en el mismo patron de roles/index.blade.php).
            empleados: empleadosIniciales.map((emp) => ({ ...emp, toggling: false })),
            roles,
            categorias,
            rolesFiltrados() {
                return this.roles.filter((r) => r.personal_category_id == this.formEmpleado.personal_category_id);
            },

            // Filtros de encabezado (mismo patron visual que
            // /admin/preventive-reports, pero 100% en cliente: los
            // empleados ya estan cargados en memoria). columnFilters[campo]
            // es la lista de valores permitidos para esa columna; vacio =
            // sin filtro en esa columna.
            columnFilters: { nombre: [], nickname: [], categoria: [], rol: [], usuario: [], bitacora: [] },
            columnValue(field, emp) {
                switch (field) {
                    case 'nombre': return emp.nombre;
                    case 'nickname': return emp.nickname;
                    case 'categoria': return emp.categoria;
                    case 'rol': return emp.personal_role_name || 'Sin subrol';
                    case 'usuario': return emp.has_login ? emp.username : 'Sin usuario';
                    case 'bitacora': return emp.in_bitacora ? 'Sí' : 'No';
                    default: return '';
                }
            },
            distinctValuesFor(field) {
                const base = this.empleados.filter((emp) => this.mostrarInactivos || emp.activo);
                const valores = base.map((emp) => this.columnValue(field, emp));
                return [...new Set(valores)].sort((a, b) => String(a).localeCompare(String(b), 'es'));
            },
            empleadosFiltrados() {
                return this.empleados.filter((emp) => {
                    if (!this.mostrarInactivos && !emp.activo) return false;
                    return Object.entries(this.columnFilters).every(([field, seleccionados]) => (
                        seleccionados.length === 0 || seleccionados.includes(this.columnValue(field, emp))
                    ));
                });
            },
            hayFiltrosActivos() {
                return Object.values(this.columnFilters).some((seleccionados) => seleccionados.length > 0);
            },
            limpiarColumnFilters() {
                Object.keys(this.columnFilters).forEach((field) => { this.columnFilters[field] = []; });
                this.paginaActual = 1;
            },

            // Paginacion — la cantidad de filas se calcula segun el alto
            // disponible de pantalla (un laptop cabe menos, un monitor
            // grande mas), no un numero fijo. A proposito NUNCA se resetea
            // paginaActual ni columnFilters al crear/editar/archivar un
            // empleado — solo cambian por accion explicita del usuario
            // (paginar, filtrar, "Limpiar filtros"), para que crear varios
            // empleados seguidos no te regrese a la pagina 1 cada vez.
            filasPorPagina: 10,
            paginaActual: 1,
            calcularFilasPorPagina(el) {
                const alturaFila = 41; // ~padding py-2 + texto text-sm + borde
                const reservado = 90; // barra de paginacion + margenes
                const disponible = window.innerHeight - el.getBoundingClientRect().top - reservado;
                this.filasPorPagina = Math.max(5, Math.floor(disponible / alturaFila));
            },
            totalPaginas() {
                return Math.max(1, Math.ceil(this.empleadosFiltrados().length / this.filasPorPagina));
            },
            empleadosPaginaActual() {
                const filtrados = this.empleadosFiltrados();
                if (this.mostrandoTodasParaExport) return filtrados;
                const inicio = (this.paginaActual - 1) * this.filasPorPagina;
                return filtrados.slice(inicio, inicio + this.filasPorPagina);
            },
            rangoPaginaTexto() {
                const total = this.empleadosFiltrados().length;
                if (total === 0) return 'Mostrando 0 de ' + this.empleados.length;
                const inicio = (this.paginaActual - 1) * this.filasPorPagina + 1;
                const fin = Math.min(total, this.paginaActual * this.filasPorPagina);
                return `Mostrando ${inicio}–${fin} de ${total}`;
            },

            seleccionarCategoria(categoryId) {
                this.formEmpleado.personal_category_id = categoryId;
            },

            // Cerrar el modal (X, click afuera, Cancelar) nunca borra
            // formEmpleado — solo oculta. Reabrir con "Nuevo empleado" o el
            // lapiz de la MISMA fila que ya se estaba editando conserva lo
            // escrito (como minimizar); solo se reinicia si viene de un modo
            // distinto (de editar a nuevo, o de editar otra persona).
            nuevoEmpleado() {
                if (this.modoEdicion) {
                    this.modoEdicion = false;
                    this.formAction = storeUrl;
                    this.formMethod = 'POST';
                    this.formEmpleado = emptyFormEmpleado(this.ultimaCategoriaCreada, this.ultimoRolCreado);
                }
                this.formErrors = [];
                this.modalNuevoEmpleado = true;
            },
            editarEmpleado(emp) {
                if (!(this.modoEdicion && this.formEmpleado.id === emp.id)) {
                    this.modoEdicion = true;
                    this.formAction = updateUrlTemplate.replace('__ID__', emp.id);
                    this.formMethod = 'PUT';
                    this.formEmpleado = {
                        id: emp.id, nombre: emp.nombre, nickname: emp.nickname,
                        personal_category_id: emp.personal_category_id,
                        has_login: !!emp.has_login, username: emp.username ?? '', password: '',
                        personal_role_id: emp.personal_role_id ?? '', in_bitacora: !!emp.in_bitacora,
                    };
                }
                this.formErrors = [];
                this.modalNuevoEmpleado = true;
            },
            async guardarEmpleado() {
                this.guardando = true;
                this.formErrors = [];

                try {
                    const payload = {
                        nombre: this.formEmpleado.nombre,
                        nickname: this.formEmpleado.nickname,
                        personal_category_id: this.formEmpleado.personal_category_id,
                        has_login: this.formEmpleado.has_login,
                        username: this.formEmpleado.username,
                        password: this.formEmpleado.password,
                        personal_role_id: this.formEmpleado.personal_role_id || null,
                        in_bitacora: this.formEmpleado.in_bitacora,
                    };

                    const res = await fetch(this.formAction, {
                        method: this.formMethod,
                        headers: jsonHeaders(),
                        body: JSON.stringify(payload),
                    });
                    const data = await res.json().catch(() => null);

                    if (res.status === 422 && data?.errors) {
                        this.formErrors = Object.values(data.errors).flat();
                        showCrudToast(this.formErrors, 'error');
                        return;
                    }

                    if (!res.ok || !data?.success) {
                        showCrudToast(data?.message || 'No se pudo guardar el empleado.', 'error');
                        return;
                    }

                    const idx = this.empleados.findIndex((e) => e.id === data.employee.id);
                    if (idx !== -1) {
                        this.empleados[idx] = { ...data.employee, toggling: false };
                    } else {
                        this.empleados.push({ ...data.employee, toggling: false });
                        // Se crean decenas de empleados seguidos, casi siempre
                        // del mismo Rol/Subrol — recordar el ultimo CREADO
                        // (no editado) para preseleccionarlo en el siguiente
                        // "Nuevo empleado" y ahorrar esos clics repetidos.
                        this.ultimaCategoriaCreada = data.employee.personal_category_id;
                        this.ultimoRolCreado = data.employee.personal_role_id;
                    }

                    // Ya se guardo de verdad — a diferencia de un cierre
                    // manual, aqui si se limpia el borrador para que la
                    // proxima vez que abras "Nuevo empleado" no muestre este
                    // registro (ya guardado) como si siguiera pendiente.
                    this.modoEdicion = false;
                    this.formEmpleado = emptyFormEmpleado(this.ultimaCategoriaCreada, this.ultimoRolCreado);
                    this.modalNuevoEmpleado = false;
                    showCrudToast(data.message || 'Empleado guardado correctamente.', 'success');
                    this.$nextTick(() => window.lucide?.createIcons());
                } catch (e) {
                    console.error('guardarEmpleado:', e);
                    showCrudToast('No se pudo guardar el empleado (error de red).', 'error');
                } finally {
                    this.guardando = false;
                }
            },
            async toggleEmpleado(emp) {
                if (emp.toggling) return;
                emp.toggling = true;

                try {
                    const url = toggleStatusUrlTemplate.replace('__ID__', emp.id);
                    const res = await fetch(url, { method: 'PATCH', headers: jsonHeaders() });
                    const data = await res.json().catch(() => null);

                    if (!res.ok || !data?.success) {
                        showCrudToast(data?.message || 'No se pudo cambiar el estado del empleado.', 'error');
                        return;
                    }

                    Object.assign(emp, data.employee);
                    showCrudToast(data.message || 'Estado actualizado correctamente.', 'success');
                } catch (e) {
                    console.error('toggleEmpleado:', e);
                    showCrudToast('No se pudo cambiar el estado del empleado (error de red).', 'error');
                } finally {
                    emp.toggling = false;
                }
            },

            // Empresas — mismo CRUD visual del mockup (preview-personal),
            // ahora contra los endpoints JSON reales de CompanyController.
            // "Eliminar" archiva, nunca borra (confirmado 2026-09-16).
            empresas: empresasIniciales,
            nuevaEmpresaNombre: '',
            mostrarEmpresasArchivadas: false,
            async agregarEmpresa() {
                const nombre = this.nuevaEmpresaNombre.trim();
                if (!nombre) return;
                try {
                    const res = await fetch(empresasStoreUrl, { method: 'POST', headers: jsonHeaders(), body: JSON.stringify({ name: nombre }) });
                    const data = await res.json();
                    if (!data.success) { showCrudToast(data.message || 'No se pudo crear la empresa.', 'error'); return; }
                    this.empresas.push({ id: data.company.id, nombre: data.company.name, defecto: data.company.is_default, archivada: data.company.archived, editando: false });
                    this.nuevaEmpresaNombre = '';
                    showCrudToast(data.message || 'Empresa creada correctamente.', 'success');
                    this.$nextTick(() => window.lucide?.createIcons());
                } catch (e) {
                    console.error('agregarEmpresa:', e);
                    showCrudToast('No se pudo crear la empresa (error de red).', 'error');
                }
            },
            async guardarNombreEmpresa(empresa) {
                empresa.editando = false;
                const nombre = (empresa.nombre || '').trim();
                if (!nombre) return;
                try {
                    const res = await fetch(empresasUpdateUrlTemplate.replace('__ID__', empresa.id), { method: 'PUT', headers: jsonHeaders(), body: JSON.stringify({ name: nombre }) });
                    const data = await res.json();
                    if (!data.success) { showCrudToast(data.message || 'No se pudo renombrar la empresa.', 'error'); return; }
                    empresa.nombre = data.company.name;
                    showCrudToast(data.message || 'Empresa renombrada correctamente.', 'success');
                } catch (e) {
                    console.error('guardarNombreEmpresa:', e);
                    showCrudToast('No se pudo renombrar la empresa (error de red).', 'error');
                }
            },
            async marcarDefecto(empresa) {
                try {
                    const res = await fetch(empresasMarkDefaultUrlTemplate.replace('__ID__', empresa.id), { method: 'PATCH', headers: jsonHeaders() });
                    const data = await res.json();
                    if (!data.success) { showCrudToast(data.message || 'No se pudo marcar por defecto.', 'error'); return; }
                    this.empresas.forEach((e) => { e.defecto = e.id === empresa.id; });
                    showCrudToast(data.message || 'Empresa marcada por defecto correctamente.', 'success');
                } catch (e) {
                    console.error('marcarDefecto:', e);
                    showCrudToast('No se pudo marcar por defecto (error de red).', 'error');
                }
            },
            async archivarEmpresa(empresa) {
                try {
                    const res = await fetch(empresasToggleArchivedUrlTemplate.replace('__ID__', empresa.id), { method: 'PATCH', headers: jsonHeaders() });
                    const data = await res.json();
                    if (!data.success) { showCrudToast(data.message || 'No se pudo archivar/restablecer la empresa.', 'error'); return; }
                    empresa.archivada = data.company.archived;
                    empresa.defecto = data.company.is_default;
                    showCrudToast(data.message || 'Empresa actualizada correctamente.', 'success');
                } catch (e) {
                    console.error('archivarEmpresa:', e);
                    showCrudToast('No se pudo archivar/restablecer la empresa (error de red).', 'error');
                }
            },
        };
    }
</script>
@endpush
