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

    $reopen = $errors->any();
    $oldId = old('employee_id');
@endphp

@section('content')
<div
    class="mx-auto max-w-[1900px] space-y-4"
    x-data="personalEmpleadosPage({
        storeUrl: @js(route('personal.empleados.store')),
        updateUrlTemplate: @js(route('personal.empleados.update', ['employee' => '__ID__'])),
        empresasStoreUrl: @js(route('personal.empresas.store')),
        empresasUpdateUrlTemplate: @js(route('personal.empresas.update', ['company' => '__ID__'])),
        empresasToggleArchivedUrlTemplate: @js(route('personal.empresas.toggle-archived', ['company' => '__ID__'])),
        empresasMarkDefaultUrlTemplate: @js(route('personal.empresas.mark-default', ['company' => '__ID__'])),
        csrfToken: @js(csrf_token()),
        empresasIniciales: @js($empresasJs),
        reopen: @js($reopen),
        oldValues: @js($reopen ? [
            'id' => $oldId,
            'nombre' => old('nombre', ''),
            'nickname' => old('nickname', ''),
            'abreviatura' => old('abreviatura', ''),
            'categoria' => old('categoria', 'Campo'),
            'has_login' => (bool) old('has_login'),
            'username' => old('username', ''),
            'personal_role_id' => old('personal_role_id', ''),
            'in_bitacora' => (bool) old('in_bitacora'),
        ] : null),
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

    {{-- areaExportable: lo que captura "Copiar como imagen". --}}
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
                @forelse ($empleados as $e)
                    <tr class="hover:bg-slate-50" x-show="mostrarInactivos || {{ $e->activo ? 'true' : 'false' }}">
                        <td class="sticky-col px-4 py-2">
                            <div class="flex items-center justify-center gap-1.5">
                                <button
                                    type="button"
                                    @click="editarEmpleado({{ Illuminate\Support\Js::from([
                                        'id' => $e->id, 'nombre' => $e->nombre, 'nickname' => $e->nickname,
                                        'abreviatura' => $e->abreviatura, 'categoria' => $e->categoria,
                                        'has_login' => $e->has_login, 'username' => $e->username,
                                        'personal_role_id' => $e->personal_role_id, 'in_bitacora' => $e->in_bitacora,
                                    ]) }})"
                                    class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-[#d55b20]" title="Editar"
                                >
                                    <i data-lucide="pencil" class="h-4 w-4"></i>
                                </button>
                                <form method="POST" action="{{ route('personal.empleados.toggle-status', $e) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button
                                        type="submit"
                                        class="rounded-full px-2 py-1 text-[10px] font-semibold {{ $e->activo ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-slate-200 text-slate-600 hover:bg-slate-300' }}"
                                    >
                                        {{ $e->activo ? 'Activo' : 'Inactivo' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                        <td class="px-4 py-2 font-medium text-slate-800">{{ $e->nombre }}</td>
                        <td class="whitespace-nowrap px-4 py-2 text-slate-600">{{ $e->nickname }}</td>
                        <td class="whitespace-nowrap px-4 py-2 text-center">
                            @if ($e->abreviatura)
                                <span class="inline-flex h-6 min-w-[1.5rem] items-center justify-center rounded-md bg-slate-100 px-1.5 text-xs font-bold text-slate-600">{{ $e->abreviatura }}</span>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-2">
                            <span class="inline-flex items-center rounded-xl px-3 py-1 text-[11px] font-semibold {{ $e->categoria === 'Campo' ? 'bg-emerald-100 text-emerald-800' : 'bg-indigo-100 text-indigo-800' }}">
                                {{ $e->categoria }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-center">
                            @if ($e->has_login)
                                <i data-lucide="check-circle-2" class="mx-auto h-4 w-4 text-emerald-600" title="{{ $e->username }} ({{ $e->personalRole?->name ?: 'sin rol' }})"></i>
                            @else
                                <i data-lucide="minus-circle" class="mx-auto h-4 w-4 text-slate-300"></i>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-center">
                            @if ($e->in_bitacora)
                                <i data-lucide="check-circle-2" class="mx-auto h-4 w-4 text-emerald-600"></i>
                            @else
                                <i data-lucide="minus-circle" class="mx-auto h-4 w-4 text-slate-300" title="No hace parte de la Bitácora"></i>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-400">
                            Todavía no hay empleados registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        </div>
    </div>

    {{-- Toast de resultado de la exportacion --}}
    <div x-show="mensajeExport" x-cloak x-transition class="fixed bottom-4 right-4 z-50 max-w-xs rounded-xl bg-slate-900 px-4 py-3 text-sm text-white shadow-xl" x-text="mensajeExport"></div>

    {{-- Modal nuevo/editar empleado — formulario real (POST/PUT), no solo
         estado local Alpine: guardarEmpleado() en el mockup no persistia
         nada, aqui si. --}}
    <div x-show="modalNuevoEmpleado" x-cloak class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 p-4" style="top:0;left:0;height:100vh;width:100vw;">
        <div @click.outside="modalNuevoEmpleado = false" x-show="modalNuevoEmpleado" x-transition class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
            <form :action="formAction" method="POST">
                @csrf
                <input type="hidden" name="_method" :value="formMethod">
                <input type="hidden" name="employee_id" :value="formEmpleado.id">

                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-slate-900" x-text="modoEdicion ? 'Editar empleado' : 'Nuevo empleado'"></h2>
                    <button type="button" @click="modalNuevoEmpleado = false" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100"><i data-lucide="x" class="h-5 w-5"></i></button>
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
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-medium text-slate-600">Nombre completo</label>
                        <input type="text" name="nombre" x-model="formEmpleado.nombre" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Ej. Luis Fernando Montoya Zuluaga">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Nickname (display corto)</label>
                        <input type="text" name="nickname" x-model="formEmpleado.nickname" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Ej. Fernando">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Categoría</label>
                        <select
                            name="categoria" x-model="formEmpleado.categoria"
                            @change="if (formEmpleado.categoria === 'Campo') { formEmpleado.has_login = false; formEmpleado.role = ''; }"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        >
                            <option>Campo</option>
                            <option>Administrativos</option>
                        </select>
                    </div>
                    <div x-show="formEmpleado.categoria === 'Administrativos'" x-cloak>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Abreviatura (columna Responsable)</label>
                        <input type="text" name="abreviatura" x-model="formEmpleado.abreviatura" maxlength="10" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Ej. F">
                    </div>
                </div>

                <div class="mt-5 space-y-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <label class="flex items-start gap-2 text-sm text-slate-700" :class="formEmpleado.categoria === 'Campo' ? 'opacity-50' : ''">
                        <input type="checkbox" name="has_login" value="1" x-model="formEmpleado.has_login" :disabled="formEmpleado.categoria === 'Campo'" class="mt-0.5">
                        <span class="font-medium">Tiene usuario de acceso</span>
                    </label>

                    <div x-show="formEmpleado.has_login" x-cloak class="grid gap-3 pl-6 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Usuario</label>
                            <input type="text" name="username" x-model="formEmpleado.username" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Ej. lfernando">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">
                                Contraseña <span class="text-slate-400" x-show="modoEdicion">(vacío = mantener la actual)</span>
                            </label>
                            <input type="password" name="password" x-model="formEmpleado.password" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-medium text-slate-600">Rol de acceso</label>
                            <select name="personal_role_id" x-model="formEmpleado.personal_role_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <option value="">Sin definir</option>
                                @foreach ($roles as $rol)
                                    <option value="{{ $rol->id }}">{{ $rol->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-[11px] text-slate-400">
                                Los roles y sus permisos se administran desde "Roles y permisos" (solo superadmin).
                            </p>
                        </div>
                    </div>

                    <label class="flex items-start gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="in_bitacora" value="1" x-model="formEmpleado.in_bitacora" class="mt-0.5">
                        <span class="font-medium">Hace parte de la Bitácora</span>
                    </label>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" @click="modalNuevoEmpleado = false" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancelar</button>
                    <button type="submit" class="rounded-xl bg-[#d55b20] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#b8481a]">Guardar empleado</button>
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
        storeUrl, updateUrlTemplate,
        empresasStoreUrl, empresasUpdateUrlTemplate, empresasToggleArchivedUrlTemplate, empresasMarkDefaultUrlTemplate,
        csrfToken, empresasIniciales, reopen, oldValues,
    }) {
        const emptyFormEmpleado = () => ({
            id: null, nombre: '', nickname: '', abreviatura: '', categoria: 'Campo',
            has_login: false, username: '', password: '', personal_role_id: '', in_bitacora: true,
        });

        const jsonHeaders = () => ({
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        });

        return {
            ...imageExporterMixin('empleados'),
            modalNuevoEmpleado: reopen,
            modalEmpresas: false,
            mostrarInactivos: false,
            modoEdicion: reopen && !!(oldValues && oldValues.id),
            formAction: (reopen && oldValues && oldValues.id) ? updateUrlTemplate.replace('__ID__', oldValues.id) : storeUrl,
            formMethod: (reopen && oldValues && oldValues.id) ? 'PUT' : 'POST',
            formEmpleado: reopen ? { ...emptyFormEmpleado(), ...oldValues } : emptyFormEmpleado(),

            nuevoEmpleado() {
                this.modoEdicion = false;
                this.formAction = storeUrl;
                this.formMethod = 'POST';
                this.formEmpleado = emptyFormEmpleado();
                this.modalNuevoEmpleado = true;
            },
            editarEmpleado(emp) {
                this.modoEdicion = true;
                this.formAction = updateUrlTemplate.replace('__ID__', emp.id);
                this.formMethod = 'PUT';
                this.formEmpleado = {
                    id: emp.id, nombre: emp.nombre, nickname: emp.nickname,
                    abreviatura: emp.abreviatura ?? '', categoria: emp.categoria,
                    has_login: !!emp.has_login, username: emp.username ?? '', password: '',
                    personal_role_id: emp.personal_role_id ?? '', in_bitacora: !!emp.in_bitacora,
                };
                this.modalNuevoEmpleado = true;
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
                    if (!data.success) { this.mostrarMensaje(data.message || 'No se pudo crear la empresa.'); return; }
                    this.empresas.push({ id: data.company.id, nombre: data.company.name, defecto: data.company.is_default, archivada: data.company.archived, editando: false });
                    this.nuevaEmpresaNombre = '';
                    this.$nextTick(() => window.lucide?.createIcons());
                } catch (e) {
                    console.error('agregarEmpresa:', e);
                    this.mostrarMensaje('No se pudo crear la empresa (error de red).');
                }
            },
            async guardarNombreEmpresa(empresa) {
                empresa.editando = false;
                const nombre = (empresa.nombre || '').trim();
                if (!nombre) return;
                try {
                    const res = await fetch(empresasUpdateUrlTemplate.replace('__ID__', empresa.id), { method: 'PUT', headers: jsonHeaders(), body: JSON.stringify({ name: nombre }) });
                    const data = await res.json();
                    if (!data.success) { this.mostrarMensaje(data.message || 'No se pudo renombrar la empresa.'); return; }
                    empresa.nombre = data.company.name;
                } catch (e) {
                    console.error('guardarNombreEmpresa:', e);
                    this.mostrarMensaje('No se pudo renombrar la empresa (error de red).');
                }
            },
            async marcarDefecto(empresa) {
                try {
                    const res = await fetch(empresasMarkDefaultUrlTemplate.replace('__ID__', empresa.id), { method: 'PATCH', headers: jsonHeaders() });
                    const data = await res.json();
                    if (!data.success) { this.mostrarMensaje(data.message || 'No se pudo marcar por defecto.'); return; }
                    this.empresas.forEach((e) => { e.defecto = e.id === empresa.id; });
                } catch (e) {
                    console.error('marcarDefecto:', e);
                    this.mostrarMensaje('No se pudo marcar por defecto (error de red).');
                }
            },
            async archivarEmpresa(empresa) {
                try {
                    const res = await fetch(empresasToggleArchivedUrlTemplate.replace('__ID__', empresa.id), { method: 'PATCH', headers: jsonHeaders() });
                    const data = await res.json();
                    if (!data.success) { this.mostrarMensaje(data.message || 'No se pudo archivar/restablecer la empresa.'); return; }
                    empresa.archivada = data.company.archived;
                    empresa.defecto = data.company.is_default;
                } catch (e) {
                    console.error('archivarEmpresa:', e);
                    this.mostrarMensaje('No se pudo archivar/restablecer la empresa (error de red).');
                }
            },
        };
    }
</script>
@endpush
