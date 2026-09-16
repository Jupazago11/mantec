@extends('layouts.personal')

@section('title', 'Roles y permisos')

@php
    // Etiquetas cortas para la tabla/modal — la clave real (columna de
    // personal_roles) es la que viaja en el formulario.
    $etiquetas = [
        'ver_empleados' => 'Empleados',
        'ver_programacion' => 'Programación',
        'editar_programacion_sin_limite' => 'Editar sin límite de hoy/ayer',
        'ver_diario_campo' => 'Diario de Campo',
        'cerrar_diario_campo' => 'Cerrar Diario de Campo',
        'ver_bitacora' => 'Bitácora',
    ];

    $reopen = $errors->any();
    $oldId = old('role_id');
@endphp

@section('content')
<div
    class="mx-auto max-w-[1900px] space-y-4"
    x-data="personalRolesPage({
        storeUrl: @js(route('personal.roles.store')),
        updateUrlTemplate: @js(route('personal.roles.update', ['personalRole' => '__ID__'])),
        permisos: @js($permisos),
        reopen: @js($reopen),
        oldValues: @js($reopen ? [
            'id' => $oldId,
            'name' => old('name', ''),
            ...collect($permisos)->mapWithKeys(fn ($p) => [$p => (bool) old($p)])->all(),
        ] : null),
    })"
>
    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900 md:text-2xl">Roles y permisos</h1>
                <p class="mt-1 text-xs text-slate-500">
                    Qué módulos ve y puede usar cada rol — exclusivo de superadmin. Crea subroles nuevos,
                    renómbralos, o ajusta sus permisos sin tocar código.
                </p>
            </div>
            <button @click="nuevoRol()" class="inline-flex items-center gap-2 rounded-xl bg-[#d55b20] px-4 py-2 text-sm font-semibold text-white hover:bg-[#b8481a]">
                <i data-lucide="plus" class="h-4 w-4"></i> Nuevo rol
            </button>
        </div>
    </div>

    @if (session('error'))
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="compact-table-wrapper rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="table-scroll-container">
    <table class="preventive-table divide-y divide-slate-200 text-sm">
        <thead class="sticky-table-head bg-slate-50">
            <tr>
                <th class="sticky-col px-4 py-2 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">Acciones</th>
                <th class="px-4 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Rol</th>
                <th class="px-4 py-2 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">Empleados</th>
                @foreach ($etiquetas as $label)
                    <th class="px-3 py-2 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500" style="min-width:7rem">{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 bg-white">
            @forelse ($roles as $rol)
                <tr class="hover:bg-slate-50">
                    <td class="sticky-col px-4 py-2">
                        <div class="flex items-center justify-center gap-1.5">
                            <button
                                type="button"
                                @click="editarRol({{ Illuminate\Support\Js::from([
                                    'id' => $rol->id,
                                    'name' => $rol->name,
                                    ...collect($permisos)->mapWithKeys(fn ($p) => [$p => (bool) $rol->{$p}])->all(),
                                ]) }})"
                                class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-[#d55b20]" title="Editar"
                            >
                                <i data-lucide="pencil" class="h-4 w-4"></i>
                            </button>
                            <form method="POST" action="{{ route('personal.roles.destroy', $rol) }}" onsubmit="return confirm('¿Eliminar el rol {{ $rol->name }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-600" title="Eliminar">
                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                    <td class="px-4 py-2 font-medium text-slate-800">{{ $rol->name }}</td>
                    <td class="px-4 py-2 text-center text-slate-500">{{ $rol->employees_count }}</td>
                    @foreach (array_keys($etiquetas) as $permiso)
                        <td class="px-3 py-2 text-center">
                            @if ($rol->{$permiso})
                                <i data-lucide="check" class="mx-auto h-4 w-4 text-emerald-600"></i>
                            @else
                                <i data-lucide="minus" class="mx-auto h-4 w-4 text-slate-300"></i>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 3 + count($etiquetas) }}" class="px-4 py-10 text-center text-sm text-slate-400">
                        Todavía no hay roles.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    </div>
    </div>

    {{-- Modal nuevo/editar rol. --}}
    <div x-show="modalAbierto" x-cloak class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 p-4" style="top:0;left:0;height:100vh;width:100vw;">
        <div @click.outside="modalAbierto = false" x-show="modalAbierto" x-transition class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
            <form :action="formAction" method="POST">
                @csrf
                <input type="hidden" name="_method" :value="formMethod">
                <input type="hidden" name="role_id" :value="rolId">

                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-slate-900" x-text="modoEdicion ? 'Editar rol' : 'Nuevo rol'"></h2>
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

                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Nombre del rol</label>
                    <input type="text" name="name" x-model="formRol.name" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Ej. SISO">
                </div>

                <p class="mb-2 mt-5 text-xs font-medium text-slate-600">Módulos que ve y puede usar</p>
                <div class="space-y-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    @foreach ($etiquetas as $permiso => $label)
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="{{ $permiso }}" value="1" x-model="formRol.{{ $permiso }}">
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" @click="modalAbierto = false" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancelar</button>
                    <button type="submit" class="rounded-xl bg-[#d55b20] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#b8481a]">Guardar rol</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function personalRolesPage({ storeUrl, updateUrlTemplate, permisos, reopen, oldValues }) {
        const emptyFormRol = () => {
            const form = { name: '' };
            permisos.forEach((p) => { form[p] = false; });
            return form;
        };

        return {
            modalAbierto: reopen,
            modoEdicion: reopen && !!(oldValues && oldValues.id),
            rolId: (reopen && oldValues && oldValues.id) ? oldValues.id : null,
            formAction: (reopen && oldValues && oldValues.id) ? updateUrlTemplate.replace('__ID__', oldValues.id) : storeUrl,
            formMethod: (reopen && oldValues && oldValues.id) ? 'PUT' : 'POST',
            formRol: reopen ? { ...emptyFormRol(), ...oldValues } : emptyFormRol(),

            nuevoRol() {
                this.modoEdicion = false;
                this.rolId = null;
                this.formAction = storeUrl;
                this.formMethod = 'POST';
                this.formRol = emptyFormRol();
                this.modalAbierto = true;
            },
            editarRol(rol) {
                this.modoEdicion = true;
                this.rolId = rol.id;
                this.formAction = updateUrlTemplate.replace('__ID__', rol.id);
                this.formMethod = 'PUT';
                this.formRol = { ...emptyFormRol(), ...rol };
                this.modalAbierto = true;
            },
        };
    }
</script>
@endpush
