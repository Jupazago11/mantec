@extends('preview-personal._layout')

@section('title', 'Empleados')

@php
    // Archivo PHP plano (no Blade @include): un @include de Blade renderiza
    // en un scope aislado y no comparte $empleados hacia este archivo.
    $empleados = include resource_path('views/preview-personal/_empleados-data.php');
    $fmt = fn (?string $d) => $d ? \Illuminate\Support\Carbon::parse($d)->translatedFormat('d/m/Y') : null;
    $edad = fn (string $nacimiento) => \Illuminate\Support\Carbon::parse($nacimiento)->age;

    // Notificaciones (confirmado 2026-09-10, umbral ajustado a "menos de 1
    // mes" y con cumpleaños): contratos por finalizar, certificados vencidos
    // /proximos a vencer, y cumpleaños hoy o en los proximos 7 dias.
    // Calculadas sobre los mismos datos de ejemplo — solo para maquetar el
    // boton de campana.
    $hoy = now();
    $hoyInicioDia = $hoy->copy()->startOfDay();
    $umbralDias = 30; // "menos de 1 mes"
    $notificaciones = [];
    foreach ($empleados as $e) {
        if (!$e['activo']) {
            continue;
        }
        if ($e['contrato_fin']) {
            $fin = \Illuminate\Support\Carbon::parse($e['contrato_fin'])->startOfDay();
            if ($fin->greaterThanOrEqualTo($hoyInicioDia) && ($dias = $hoyInicioDia->diffInDays($fin)) < $umbralDias) {
                $notificaciones[] = ['tipo' => 'contrato', 'icono' => 'file-clock', 'texto' => "El contrato de {$e['nombre']} vence el {$fin->translatedFormat('d/m/Y')} (en {$dias} día" . ($dias === 1 ? '' : 's') . ')'];
            }
        }
        foreach ($e['certs'] as $cert) {
            if ($cert['estado'] === 'vencido') {
                $notificaciones[] = ['tipo' => 'vencido', 'icono' => 'shield-alert', 'texto' => "El certificado de {$cert['label']} de {$e['nickname']} está vencido"];
            } elseif (preg_match('/(\d{2})\/(\d{4})/', $cert['detalle'], $m)) {
                $fechaCert = \Illuminate\Support\Carbon::createFromDate((int) $m[2], (int) $m[1], 1)->startOfDay();
                if ($fechaCert->greaterThanOrEqualTo($hoyInicioDia) && ($dias = $hoyInicioDia->diffInDays($fechaCert)) < $umbralDias) {
                    $notificaciones[] = ['tipo' => 'proximo', 'icono' => 'clock-alert', 'texto' => "El certificado de {$cert['label']} de {$e['nickname']} vence pronto ({$fechaCert->translatedFormat('m/Y')} · en {$dias} día" . ($dias === 1 ? '' : 's') . ')'];
                }
            }
        }

        // Cumpleaños: hoy, o dentro de los proximos 7 dias.
        $nacimiento = \Illuminate\Support\Carbon::parse($e['nacimiento']);
        $proximoCumple = $nacimiento->copy()->year($hoy->year)->startOfDay();
        if ($proximoCumple->lt($hoyInicioDia)) {
            $proximoCumple->addYear();
        }
        $diasParaCumple = $hoyInicioDia->diffInDays($proximoCumple);
        if ($diasParaCumple === 0) {
            $notificaciones[] = ['tipo' => 'cumple', 'icono' => 'cake', 'texto' => "¡Hoy es el cumpleaños de {$e['nickname']}!"];
        } elseif ($diasParaCumple <= 7) {
            $notificaciones[] = ['tipo' => 'cumple', 'icono' => 'cake', 'texto' => "El cumpleaños de {$e['nickname']} es en {$diasParaCumple} día" . ($diasParaCumple === 1 ? '' : 's') . " ({$proximoCumple->translatedFormat('d/m')})"];
        }
    }
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
                    código en la columna "Responsable") son 3 campos distintos. Las columnas de certificados son
                    dinámicas: cada una que se crea en su catálogo aparece automáticamente aquí.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                {{-- Campana de notificaciones --}}
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="relative flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white hover:bg-slate-50">
                        <i data-lucide="bell" class="h-5 w-5 text-slate-600"></i>
                        @if (count($notificaciones) > 0)
                            <span class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white">{{ count($notificaciones) }}</span>
                        @endif
                    </button>
                    <div x-show="open" x-cloak @click.outside="open = false" x-transition class="absolute right-0 z-30 mt-2 w-80 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl">
                        <p class="px-2 py-1 text-xs font-semibold uppercase tracking-wide text-slate-400">Notificaciones</p>
                        @forelse ($notificaciones as $n)
                            <div class="flex items-start gap-2 rounded-xl px-2 py-2 hover:bg-slate-50">
                                <i data-lucide="{{ $n['icono'] }}" class="mt-0.5 h-4 w-4 shrink-0 {{ $n['tipo'] === 'vencido' ? 'text-red-500' : ($n['tipo'] === 'cumple' ? 'text-pink-500' : 'text-amber-500') }}"></i>
                                <span class="text-xs text-slate-700">{{ $n['texto'] }}</span>
                            </div>
                        @empty
                            <p class="px-2 py-3 text-center text-xs text-slate-400">Sin notificaciones</p>
                        @endforelse
                    </div>
                </div>

                <button @click="modalEmpresas = true" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <i data-lucide="building-2" class="h-4 w-4"></i> Empresas e inducciones
                </button>
                <button @click="modalCertificados = true" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <i data-lucide="shield-check" class="h-4 w-4"></i> Certificados
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

    <div class="compact-table-wrapper rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="table-scroll-container">
        <table class="preventive-table divide-y divide-slate-200 text-sm">
            <thead class="sticky-table-head bg-slate-50">
                <tr>
                    <th class="sticky-col px-4 py-2 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">Acciones</th>
                    <th class="px-4 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500" style="min-width:14rem">Nombre completo</th>
                    <th class="px-4 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Nickname</th>
                    <th class="px-4 py-2 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">Abrev.</th>
                    <th class="px-4 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Cédula</th>
                    <th class="px-4 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Categoría</th>
                    <th class="px-4 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Nacimiento</th>
                    <th class="px-4 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Ingreso</th>
                    <th class="px-4 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Contrato desde</th>
                    <th class="px-4 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Contrato hasta</th>
                    <th class="px-4 py-2 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">Usuario</th>
                    <th class="px-4 py-2 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">Bitácora</th>
                    <th class="px-4 py-2 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">Accesos</th>
                    <th class="px-4 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Certificados</th>
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
                        <td class="whitespace-nowrap px-4 py-2 text-slate-500">{{ $e['cedula'] }}</td>
                        <td class="whitespace-nowrap px-4 py-2">
                            <span class="inline-flex items-center rounded-xl px-3 py-1 text-[11px] font-semibold {{ $e['categoria'] === 'Campo' ? 'bg-emerald-100 text-emerald-800' : 'bg-indigo-100 text-indigo-800' }}">
                                {{ $e['categoria'] }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-2 text-slate-500">{{ $fmt($e['nacimiento']) }} <span class="text-slate-400">({{ $edad($e['nacimiento']) }} años)</span></td>
                        <td class="whitespace-nowrap px-4 py-2 text-slate-500">{{ $fmt($e['ingreso']) }}</td>
                        <td class="whitespace-nowrap px-4 py-2 text-slate-500">{{ $fmt($e['contrato_inicio']) }}</td>
                        <td class="whitespace-nowrap px-4 py-2 text-slate-500">{{ $e['contrato_fin'] ? $fmt($e['contrato_fin']) : 'Indefinido' }}</td>
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
                        <td class="px-4 py-2 text-center">
                            <span x-data="{ tip: false, estilo: '' }" class="relative inline-flex">
                                <button type="button" @mouseenter="tip = true; estilo = posicionarPopover($el, 256, 150)" @mouseleave="tip = false" class="flex h-7 w-7 items-center justify-center rounded-lg hover:bg-slate-100">
                                    <i data-lucide="id-card" class="h-4 w-4 text-slate-500"></i>
                                </button>
                                <template x-teleport="body">
                                <div x-show="tip" x-cloak x-transition :style="estilo" class="rounded-xl border border-slate-200 bg-white p-3 text-left shadow-xl">
                                    <p class="mb-2 text-xs font-semibold text-slate-700">Acceso por inducción</p>
                                    @forelse ($e['accesos'] as $acceso)
                                        <div class="mb-1.5 flex items-center justify-between gap-2 text-xs last:mb-0">
                                            <span class="text-slate-600">{{ $acceso['empresa'] }}</span>
                                            @if ($acceso['completo'])
                                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 font-semibold text-emerald-800" title="{{ $acceso['detalle'] }}">
                                                    <i data-lucide="check" class="h-3 w-3"></i> Acceso
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 font-semibold text-red-700" title="{{ $acceso['detalle'] }}">
                                                    <i data-lucide="x" class="h-3 w-3"></i> Sin acceso
                                                </span>
                                            @endif
                                        </div>
                                    @empty
                                        <p class="text-xs text-slate-400">Sin empresas asociadas.</p>
                                    @endforelse
                                </div>
                                </template>
                            </span>
                        </td>
                        <td class="px-4 py-2">
                            <div class="flex gap-1">
                                @foreach ($e['certs'] as $cert)
                                    @include('preview-personal._cert-icon', ['estado' => $cert['estado'], 'label' => $cert['label'], 'detalle' => $cert['detalle']])
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>

    <div class="flex flex-wrap gap-4 text-xs text-slate-500">
        <span class="flex items-center gap-1"><i data-lucide="shield-check" class="h-4 w-4 text-emerald-600"></i> Vigente</span>
        <span class="flex items-center gap-1"><i data-lucide="shield-alert" class="h-4 w-4 animate-pulse text-amber-500"></i> Vencido (solo si el certificado está marcado "requerido")</span>
        <span class="flex items-center gap-1"><i data-lucide="shield-off" class="h-4 w-4 text-slate-300"></i> No registra el certificado</span>
    </div>

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
                    <label class="mb-1 block text-xs font-medium text-slate-600">Cédula</label>
                    <input type="text" x-model="formEmpleado.cedula" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Ej. 10345678">
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
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Fecha de nacimiento</label>
                    <input type="date" x-model="formEmpleado.nacimiento" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Fecha de ingreso</label>
                    <input type="date" x-model="formEmpleado.ingreso" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Inicio de contrato actual</label>
                    <input type="date" x-model="formEmpleado.contrato_inicio" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Fin de contrato (vacío = indefinido)</label>
                    <input type="date" x-model="formEmpleado.contrato_fin" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
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

    {{-- Modal CRUD Empresas e inducciones --}}
    <div x-show="modalEmpresas" x-cloak class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 p-4" style="top:0;left:0;height:100vh;width:100vw;">
        <div @click.outside="modalEmpresas = false" x-show="modalEmpresas" x-transition class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
            <div class="mb-1 flex items-center justify-between">
                <h2 class="text-lg font-bold text-slate-900">Empresas e inducciones</h2>
                <button @click="modalEmpresas = false" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100"><i data-lucide="x" class="h-5 w-5"></i></button>
            </div>
            <p class="mb-4 text-xs text-slate-500">
                Cada empresa define sus propias inducciones obligatorias — esto alimenta la regla de
                elegibilidad de la Programación (sección 4.4 del documento).
            </p>

            <div class="space-y-4">
                <template x-for="(empresa, ei) in empresas" :key="empresa.nombre">
                    <div class="rounded-xl border border-slate-200 p-4">
                        <div class="mb-3 flex items-center justify-between gap-2">
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
                            </div>
                            <div class="flex shrink-0 items-center gap-3">
                                <button type="button" @click="marcarDefecto(ei)" x-show="!empresa.defecto" class="text-xs font-medium text-[#d55b20] hover:underline">Marcar por defecto</button>
                                <button type="button" @click="eliminarEmpresa(ei)" class="text-slate-400 hover:text-red-500"><i data-lucide="trash-2" class="h-4 w-4"></i></button>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <template x-for="(ind, ii) in empresa.inducciones" :key="ind.nombre">
                                <div class="flex items-center justify-between gap-2 rounded-lg bg-slate-50 px-3 py-1.5">
                                    <span class="flex min-w-0 items-center gap-2 text-sm text-slate-700">
                                        <i data-lucide="badge-check" class="h-3.5 w-3.5 shrink-0 text-slate-400"></i>
                                        <template x-if="!ind.editando">
                                            <span x-text="ind.nombre"></span>
                                        </template>
                                        <template x-if="ind.editando">
                                            <input
                                                type="text" x-model="ind.nombre"
                                                @keydown.enter="ind.editando = false" @blur="ind.editando = false"
                                                x-init="$nextTick(() => $el.focus())"
                                                class="rounded-lg border border-slate-300 px-2 py-1 text-xs"
                                            >
                                        </template>
                                        <button type="button" @click="ind.editando = !ind.editando" class="shrink-0 text-slate-400 hover:text-[#d55b20]" title="Editar nombre">
                                            <i data-lucide="pencil" class="h-3 w-3"></i>
                                        </button>
                                    </span>
                                    <div class="flex shrink-0 items-center gap-3">
                                        <label class="flex items-center gap-1 text-xs text-slate-500">
                                            <input type="checkbox" x-model="ind.obligatoria"> Obligatoria
                                        </label>
                                        <button type="button" @click="eliminarInduccion(ei, ii)" class="text-slate-400 hover:text-red-500"><i data-lucide="x" class="h-3.5 w-3.5"></i></button>
                                    </div>
                                </div>
                            </template>
                            <template x-if="empresa.inducciones.length === 0">
                                <p class="px-1 text-xs text-slate-400">Sin inducciones registradas.</p>
                            </template>
                        </div>

                        <div class="mt-2 flex gap-2">
                            <input type="text" x-model="nuevaInduccionPorEmpresa[ei]" @keydown.enter="agregarInduccion(ei)" placeholder="Nueva inducción..." class="flex-1 rounded-lg border border-slate-300 px-3 py-1.5 text-xs">
                            <button type="button" @click="agregarInduccion(ei)" class="rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-700">Agregar</button>
                        </div>
                    </div>
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

    {{-- Modal CRUD Certificados --}}
    <div x-show="modalCertificados" x-cloak class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 p-4" style="top:0;left:0;height:100vh;width:100vw;">
        <div @click.outside="modalCertificados = false" x-show="modalCertificados" x-transition class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
            <div class="mb-1 flex items-center justify-between">
                <h2 class="text-lg font-bold text-slate-900">Certificados generales</h2>
                <button @click="modalCertificados = false" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100"><i data-lucide="x" class="h-5 w-5"></i></button>
            </div>
            <p class="mb-4 text-xs text-slate-500">
                "Requerido" controla si un certificado vencido genera alerta — desmárcalo para certificados
                opcionales y evitar falsas alertas en empleados que no lo necesitan.
            </p>

            <div class="space-y-2">
                <template x-for="(cert, ci) in certificados" :key="cert.nombre">
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 p-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100">
                                <i :data-lucide="cert.icono" class="h-4 w-4 text-slate-600"></i>
                            </span>
                            <template x-if="!cert.editando">
                                <span class="truncate text-sm font-medium text-slate-800" x-text="cert.nombre"></span>
                            </template>
                            <template x-if="cert.editando">
                                <input
                                    type="text" x-model="cert.nombre"
                                    @keydown.enter="cert.editando = false" @blur="cert.editando = false"
                                    x-init="$nextTick(() => $el.focus())"
                                    class="w-full rounded-lg border border-slate-300 px-2 py-1 text-sm"
                                >
                            </template>
                            <button type="button" @click="cert.editando = !cert.editando" class="shrink-0 text-slate-400 hover:text-[#d55b20]" title="Editar nombre">
                                <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                            </button>
                        </div>
                        <div class="flex shrink-0 items-center gap-3">
                            <label class="flex items-center gap-1 text-xs text-slate-500">
                                <input type="checkbox" x-model="cert.activo"> Activo
                            </label>
                            <label class="flex items-center gap-1 text-xs text-slate-500">
                                <input type="checkbox" x-model="cert.requerido"> Requerido
                            </label>
                            <button type="button" @click="eliminarCertificado(ci)" class="text-slate-400 hover:text-red-500"><i data-lucide="trash-2" class="h-4 w-4"></i></button>
                        </div>
                    </div>
                </template>
            </div>

            <div class="mt-4 rounded-xl border border-dashed border-slate-300 p-4">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Nuevo certificado</p>
                <div class="flex flex-wrap items-end gap-3">
                    <div class="flex-1">
                        <label class="mb-1 block text-xs font-medium text-slate-600">Nombre</label>
                        <input type="text" x-model="nuevoCertificado.nombre" placeholder="Ej. Rescate en aguas" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div class="relative">
                        <label class="mb-1 block text-xs font-medium text-slate-600">Ícono</label>
                        <button type="button" @click="pickerAbierto = !pickerAbierto" class="flex h-10 w-16 items-center justify-center rounded-lg border border-slate-300 hover:bg-slate-50">
                            <i :data-lucide="nuevoCertificado.icono" class="h-5 w-5 text-slate-600"></i>
                        </button>
                        <div x-show="pickerAbierto" x-cloak @click.outside="pickerAbierto = false" x-transition class="absolute bottom-full right-0 z-10 mb-2 grid w-64 grid-cols-6 gap-1 rounded-xl border border-slate-200 bg-white p-3 shadow-xl">
                            <template x-for="icono in iconosDisponibles" :key="icono">
                                <button
                                    type="button"
                                    @click="seleccionarIcono(icono)"
                                    class="flex h-9 w-9 items-center justify-center rounded-lg hover:bg-slate-100"
                                    :class="nuevoCertificado.icono === icono ? 'bg-[#d55b20]/10 ring-1 ring-[#d55b20]' : ''"
                                >
                                    <i :data-lucide="icono" class="h-4 w-4 text-slate-600"></i>
                                </button>
                            </template>
                        </div>
                    </div>
                    <label class="flex items-center gap-1 pb-2.5 text-xs text-slate-600">
                        <input type="checkbox" x-model="nuevoCertificado.requerido"> Requerido
                    </label>
                    <button type="button" @click="agregarCertificado()" class="inline-flex items-center gap-2 rounded-xl bg-[#d55b20] px-4 py-2 text-sm font-semibold text-white hover:bg-[#b8481a]">
                        <i data-lucide="plus" class="h-4 w-4"></i> Agregar
                    </button>
                </div>
                <p class="mt-2 text-[11px] text-slate-400">
                    Íconos de la librería Lucide (ya usada en todo Mantec) — si prefieren otra fuente de íconos
                    o un API externo, avisar para evaluarlo.
                </p>
            </div>

            <div class="mt-5 flex justify-end">
                <button @click="modalCertificados = false" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function empleadosPage({ estadosActivos, empleadosData }) {
        const emptyFormEmpleado = () => ({
            nombre: '', nickname: '', abreviatura: null, cedula: '', categoria: 'Campo',
            nacimiento: null, ingreso: null, contrato_inicio: null, contrato_fin: null,
            usuario: false, bitacora: true, activo: true,
        });

        return {
            modalNuevoEmpleado: false,
            modalEmpresas: false,
            modalCertificados: false,
            pickerAbierto: false,

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
                    cedula: emp.cedula, categoria: emp.categoria, nacimiento: emp.nacimiento,
                    ingreso: emp.ingreso, contrato_inicio: emp.contrato_inicio, contrato_fin: emp.contrato_fin,
                    usuario: emp.usuario, bitacora: emp.bitacora, activo: this.estadosActivos[emp.nombre],
                };
                this.modalNuevoEmpleado = true;
            },

            empresas: [
                { nombre: 'ARGOS', defecto: true, editando: false, inducciones: [
                    { nombre: 'Inducción SISO Argos', obligatoria: true, editando: false },
                    { nombre: 'Inducción trabajo en alturas Argos', obligatoria: true, editando: false },
                ]},
                { nombre: 'CORONA', defecto: false, editando: false, inducciones: [
                    { nombre: 'Inducción seguridad Corona', obligatoria: true, editando: false },
                ]},
                { nombre: 'CALIDRA', defecto: false, editando: false, inducciones: [] },
            ],
            nuevaEmpresaNombre: '',
            nuevaInduccionPorEmpresa: {},

            agregarEmpresa() {
                const nombre = this.nuevaEmpresaNombre.trim();
                if (!nombre) return;
                this.empresas.push({ nombre: nombre.toUpperCase(), defecto: false, editando: false, inducciones: [] });
                this.nuevaEmpresaNombre = '';
                this.$nextTick(() => window.lucide?.createIcons());
            },
            marcarDefecto(idx) {
                this.empresas.forEach((e, i) => { e.defecto = i === idx; });
            },
            eliminarEmpresa(idx) {
                this.empresas.splice(idx, 1);
            },
            agregarInduccion(empresaIdx) {
                const nombre = (this.nuevaInduccionPorEmpresa[empresaIdx] || '').trim();
                if (!nombre) return;
                this.empresas[empresaIdx].inducciones.push({ nombre, obligatoria: true, editando: false });
                this.nuevaInduccionPorEmpresa[empresaIdx] = '';
            },
            eliminarInduccion(empresaIdx, indIdx) {
                this.empresas[empresaIdx].inducciones.splice(indIdx, 1);
            },

            certificados: [
                { nombre: 'Alturas', icono: 'mountain-snow', activo: true, requerido: true, editando: false },
                { nombre: 'Espacios confinados', icono: 'shield-alert', activo: true, requerido: true, editando: false },
                { nombre: 'Rescate en aguas', icono: 'life-buoy', activo: true, requerido: false, editando: false },
            ],
            nuevoCertificado: { nombre: '', icono: 'shield-check', activo: true, requerido: true },
            iconosDisponibles: [
                'shield-check', 'shield-alert', 'shield-off', 'hard-hat', 'mountain-snow', 'life-buoy',
                'flame', 'zap', 'wind', 'anchor', 'siren', 'wrench',
                'hammer', 'truck', 'stethoscope', 'droplets', 'construction', 'radiation',
                'eye', 'ear', 'hand', 'footprints', 'car', 'cross',
            ],
            seleccionarIcono(icono) {
                this.nuevoCertificado.icono = icono;
                this.pickerAbierto = false;
                this.$nextTick(() => window.lucide?.createIcons());
            },
            agregarCertificado() {
                const nombre = this.nuevoCertificado.nombre.trim();
                if (!nombre) return;
                this.certificados.push({ ...this.nuevoCertificado, nombre, editando: false });
                this.nuevoCertificado = { nombre: '', icono: 'shield-check', activo: true, requerido: true };
                this.$nextTick(() => window.lucide?.createIcons());
            },
            eliminarCertificado(idx) {
                this.certificados.splice(idx, 1);
            },
        };
    }
</script>
@endpush
