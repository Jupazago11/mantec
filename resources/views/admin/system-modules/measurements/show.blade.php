@extends('layouts.measurements')

@section('title', 'Mediciones - ' . $element->name)
@section('header_title', 'Vista 2')

@section('header_context')
    <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
        <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 font-medium">
            Cliente: {{ $client->name }}
        </span>
        <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 font-medium">
            Tipo: {{ $elementType->name }}
        </span>
        <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 font-medium">
            Área: {{ $area->name }}
        </span>
        <span class="inline-flex items-center rounded-full bg-[#d94d33]/10 px-3 py-1 font-semibold text-[#d94d33]">
            Activo: {{ $element->name }}
        </span>
    </div>
@endsection

@section('content')
    <div
        x-data="measurementThicknessModule({
            elementId: @js($element->id),

            initialDraft: @js($thicknessDraftData),
            initialLatestReport: @js($latestThicknessReportData ?? null),
            initialHistoricalReports: @js($thicknessHistoricalReportsData ?? []),

            initialBandStateDraft: @js($bandStateDraftData ?? null),
            initialLatestBandStateReport: @js($latestBandStateReportData ?? null),
            initialBandStateHistoricalReports: @js($bandStateHistoricalReportsData ?? []),

            routes: {
                create: @js(route('admin.system-modules.measurements.thickness-draft.create', $element->id)),
                update: @js(route('admin.system-modules.measurements.thickness-draft.update', $element->id)),
                addCover: @js(route('admin.system-modules.measurements.thickness-draft.add-cover', $element->id)),
                removeCoverTemplate: @js(route('admin.system-modules.measurements.thickness-draft.remove-cover', [
                    'element' => $element->id,
                    'coverNumber' => '__COVER__',
                ])),
                publish: @js(route('admin.system-modules.measurements.thickness-draft.publish', $element->id)),
                historyIndex: @js(route('admin.system-modules.measurements.reports.index', $element->id)),
                historyShowTemplate: @js(route('admin.system-modules.measurements.reports.show', [
                    'element' => $element->id,
                    'report' => '__REPORT__',
                ])),

                bandStateCreate: @js(route('admin.system-modules.measurements.band-state-draft.create', $element->id)),
                bandStateUpdate: @js(route('admin.system-modules.measurements.band-state-draft.update', $element->id)),
                bandStatePublish: @js(route('admin.system-modules.measurements.band-state-draft.publish', $element->id)),
                bandStateHistoryIndex: @js(route('admin.system-modules.measurements.band-state-reports.index', $element->id)),
                bandStateHistoryShowTemplate: @js(route('admin.system-modules.measurements.band-state-reports.show', [
                    'element' => $element->id,
                    'report' => '__REPORT__',
                ])),
            },
            today: @js(now()->format('Y-m-d')),
        })"
        class="space-y-8"
    >

        <div class="grid gap-8 xl:grid-cols-2">
            {{-- Superior izquierda --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-4">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Informe de estado de banda</h3>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                @click="openBandStateDraftModal()"
                                class="rounded-xl bg-[#d94d33] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                                x-text="hasBandStateDraft() ? 'Continuar borrador' : 'Crear borrador'"
                            ></button>

                            <button
                                type="button"
                                @click="openBandStateHistoryModal()"
                                :disabled="loading"
                                class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 disabled:pointer-events-none disabled:opacity-70"
                            >
                                Ver histórico
                            </button>
                        </div>
                    </div>
                </div>

                <div class="p-6 space-y-5">
                    <div
                        x-show="!latestBandStateReport"
                        x-cloak
                        class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center"
                    >
                        <p class="text-sm font-semibold text-slate-700">
                            Aún no existe un reporte oficial de informe de estado de banda.
                        </p>
                        <p class="mt-2 text-sm text-slate-500">
                            Usa el borrador para registrar la información y publicarla cuando esté lista.
                        </p>
                    </div>

                    <div
                        x-show="latestBandStateReport"
                        x-cloak
                        class="overflow-hidden rounded-2xl border border-slate-200"
                    >
                        <table class="w-full border-collapse text-sm">
                            <tbody>
                                <tr class="bg-[#4f79bd] text-white">
                                    <th colspan="4" class="border border-slate-300 px-3 py-2 text-center text-sm font-bold uppercase">
                                        Informe de estado de bandas
                                    </th>
                                </tr>

                                <tr class="bg-white">
                                    <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">TAG DE LA BANDA</th>
                                    <td class="border border-slate-300 px-3 py-2 text-center font-semibold text-slate-800">{{ $element->name }}</td>
                                    <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">DESCRIPCIÓN</th>
                                    <td class="border border-slate-300 bg-yellow-200 px-3 py-2 text-center font-semibold text-slate-900" x-text="latestBandStateReport?.description || '—'"></td>
                                </tr>

                                <tr class="bg-white">
                                    <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">ANCHO</th>
                                    <td class="border border-slate-300 bg-yellow-200 px-3 py-2 text-center font-semibold text-slate-900" x-text="displayValue(latestBandStateReport?.width)"></td>
                                    <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">ÁREA</th>
                                    <td class="border border-slate-300 px-3 py-2 text-center font-semibold text-slate-800">{{ $area->name }}</td>
                                </tr>

                                <tr class="bg-white">
                                    <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">CUBIERTA SUPERIOR</th>
                                    <td class="border border-slate-300 bg-yellow-200 px-3 py-2 text-center font-semibold text-slate-900" x-text="displayValue(latestBandStateReport?.top_cover)"></td>
                                    <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">CUBIERTA INFERIOR</th>
                                    <td class="border border-slate-300 bg-yellow-200 px-3 py-2 text-center font-semibold text-slate-900" x-text="displayValue(latestBandStateReport?.bottom_cover)"></td>
                                </tr>

                                <tr class="bg-white">
                                    <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">DUREZA</th>
                                    <td colspan="3" class="border border-slate-300 px-3 py-2 text-center font-semibold text-slate-500">
                                        —
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="grid gap-4 border-t border-slate-200 bg-slate-50 p-4 md:grid-cols-3">
                            <div class="rounded-xl border border-slate-200 bg-white p-3">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Fecha de reporte</p>
                                <p class="mt-2 text-sm font-semibold text-slate-800" x-text="latestBandStateReport?.report_date ? formatDate(latestBandStateReport.report_date) : '—'"></p>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white p-3">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Fecha de publicación</p>
                                <p class="mt-2 text-sm font-semibold text-slate-800" x-text="latestBandStateReport?.published_at ? formatDateTime(latestBandStateReport.published_at) : '—'"></p>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white p-3">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Usuario</p>
                                <p class="mt-2 text-sm font-semibold text-slate-800" x-text="latestBandStateReport?.published_by || '—'"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            {{-- Superior derecha --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h3 class="text-lg font-semibold text-slate-900">Submódulo superior derecho</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Espacio reservado para la segunda sección del activo.
                    </p>
                </div>

                <div class="p-6">
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center">
                        <p class="text-sm font-medium text-slate-600">
                            Pendiente de implementación
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Inferior ancho completo --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Medición de espesores y dureza</h3>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            @click="openDraftModal()"
                            class="rounded-xl bg-[#d94d33] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                            x-text="hasDraft() ? 'Continuar borrador' : 'Crear borrador'"
                        ></button>

                        <button
                            type="button"
                            @click="openHistoryModal()"
                            :disabled="loading"
                            class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 disabled:pointer-events-none disabled:opacity-70"
                        >
                            Ver histórico
                        </button>
                    </div>
                </div>
            </div>

            <div class="p-6 space-y-6">


                <div class="rounded-2xl border border-slate-200 bg-white">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <h4 class="text-base font-semibold text-slate-900">Último reporte oficial publicado</h4>
                    </div>

                    <div class="p-6">
                        <div
                            x-show="!latestReport"
                            x-cloak
                            class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center"
                        >
                            <p class="text-base font-semibold text-slate-700">
                                Aún no existe ningún reporte oficial publicado.
                            </p>
                            <p class="mt-2 text-sm text-slate-500">
                                Crea o continúa un borrador y publícalo cuando todas las cubiertas estén completas.
                            </p>
                        </div>

                        <div x-show="latestReport" x-cloak class="space-y-4">
                            <div class="grid gap-4 md:grid-cols-3">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Fecha de reporte</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-800" x-text="latestReport?.report_date ? formatDate(latestReport.report_date) : '—'"></p>
                                </div>

                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Fecha de publicación</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-800" x-text="latestReport?.published_at ? formatDateTime(latestReport.published_at) : '—'"></p>
                                </div>

                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Usuario</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-800" x-text="latestReport?.published_by || '—'"></p>
                                </div>
                            </div>

                            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                                <div class="overflow-x-auto">
                                    <div class="grid min-w-[1100px] grid-cols-[minmax(0,1.75fr)_minmax(320px,0.85fr)]">
                                        {{-- Tabla principal --}}
                                        <div class="border-r border-slate-200">
                                            <table class="w-full border-collapse text-sm">
                                                <thead>
                                                    <tr class="bg-slate-100 text-slate-700">
                                                        <th class="border-b border-slate-200 px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Mediciones</th>
                                                        <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">Izquierdo</th>
                                                        <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">Centro</th>
                                                        <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">Derecho</th>
                                                        <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">Max</th>
                                                        <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">Min</th>
                                                        <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">% Suficiencia</th>
                                                    </tr>
                                                </thead>

                                                <template x-for="line in (latestReport?.lines || [])" :key="'latest-main-' + line.id">
                                                    <tbody>
                                                        <tr class="bg-white hover:bg-slate-50 transition">
                                                            <td class="border-b border-slate-200 px-4 py-3 font-semibold text-slate-800">
                                                                CUBIERTA SUPERIOR <span x-text="line.cover_number"></span>
                                                            </td>
                                                            <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700" x-text="displayValue(line.top_left)"></td>
                                                            <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700" x-text="displayValue(line.top_center)"></td>
                                                            <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700" x-text="displayValue(line.top_right)"></td>
                                                            <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700" x-text="maxValue([line.top_left, line.top_center, line.top_right]) || '—'"></td>
                                                            <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700" x-text="minValue([line.top_left, line.top_center, line.top_right]) || '—'"></td>
                                                            <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-400">—</td>
                                                        </tr>

                                                        <tr class="bg-slate-50/60 hover:bg-slate-100/70 transition">
                                                            <td class="border-b border-slate-200 px-4 py-3 font-semibold text-slate-800">
                                                                CUBIERTA INFERIOR <span x-text="line.cover_number"></span>
                                                            </td>
                                                            <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700" x-text="displayValue(line.bottom_left)"></td>
                                                            <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700" x-text="displayValue(line.bottom_center)"></td>
                                                            <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700" x-text="displayValue(line.bottom_right)"></td>
                                                            <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700" x-text="maxValue([line.bottom_left, line.bottom_center, line.bottom_right]) || '—'"></td>
                                                            <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700" x-text="minValue([line.bottom_left, line.bottom_center, line.bottom_right]) || '—'"></td>
                                                            <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-400">—</td>
                                                        </tr>
                                                    </tbody>
                                                </template>
                                            </table>
                                        </div>

                                        {{-- Tabla dureza --}}
                                        <div>
                                            <table class="w-full border-collapse text-sm">
                                                <thead>
                                                    <tr class="bg-slate-100 text-slate-700">
                                                        <th class="border-b border-slate-200 px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Mediciones</th>
                                                        <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">Izquierdo</th>
                                                        <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">Centro</th>
                                                        <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">Derecho</th>
                                                    </tr>
                                                </thead>

                                                <template x-for="line in (latestReport?.lines || [])" :key="'latest-hardness-' + line.id">
                                                    <tbody>
                                                        <tr class="bg-white hover:bg-slate-50 transition">
                                                            <td class="border-b border-slate-200 px-4 py-3 font-semibold text-slate-800">
                                                                DUREZA <span x-text="line.cover_number"></span>
                                                            </td>
                                                            <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700" x-text="displayValue(line.hardness_left)"></td>
                                                            <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700" x-text="displayValue(line.hardness_center)"></td>
                                                            <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700" x-text="displayValue(line.hardness_right)"></td>
                                                        </tr>

                                                        <tr class="bg-slate-50/60">
                                                            <td class="border-b border-slate-200 px-4 py-3 text-transparent select-none">.</td>
                                                            <td class="border-b border-slate-200 px-4 py-3 text-transparent select-none">.</td>
                                                            <td class="border-b border-slate-200 px-4 py-3 text-transparent select-none">.</td>
                                                            <td class="border-b border-slate-200 px-4 py-3 text-transparent select-none">.</td>
                                                        </tr>
                                                    </tbody>
                                                </template>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    {{-- Modal borrador - Informe de estado de banda --}}
    <div
        x-cloak
        x-show="bandStateDraftModalOpen"
        x-transition.opacity
        class="fixed inset-0 z-[96] flex items-center justify-center bg-slate-900/50 px-4 py-6"
    >
    <div
        x-show="bandStateDraftModalOpen"
        x-transition
        class="flex w-full max-w-4xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl"
        @click.outside="closeBandStateDraftModal()"
    >
        <div class="border-b border-slate-200 px-6 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h3 class="text-xl font-semibold text-slate-900">Borrador - Informe de estado de banda</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Edita los 4 campos del informe del activo <span class="font-semibold text-slate-700">{{ $element->name }}</span>.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        @click="saveBandStateDraft()"
                        :disabled="loading"
                        class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 disabled:pointer-events-none disabled:opacity-70"
                    >
                        Guardar borrador
                    </button>

                    <button
                        type="button"
                        @click="openBandStatePublishConfirm()"
                        :disabled="loading || !hasBandStateDraft()"
                        class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:pointer-events-none disabled:opacity-70"
                    >
                        Publicar reporte
                    </button>

                    <button
                        type="button"
                        @click="closeBandStateDraftModal()"
                        class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </div>

        <div class="p-6">
            <div
                x-show="bandStateErrors.length > 0"
                class="mb-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
            >
                <div class="font-semibold">Hay errores en el informe de estado de banda.</div>
                <ul class="mt-2 list-disc pl-5">
                    <template x-for="error in bandStateErrors" :key="error">
                        <li x-text="error"></li>
                    </template>
                </ul>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200">
                <table class="w-full border-collapse text-sm">
                    <tbody>
                        <tr class="bg-[#4f79bd] text-white">
                            <th colspan="4" class="border border-slate-300 px-3 py-2 text-center text-sm font-bold uppercase">
                                Informe de estado de bandas
                            </th>
                        </tr>

                        <tr class="bg-white">
                            <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">TAG DE LA BANDA</th>
                            <td class="border border-slate-300 px-3 py-2 text-center font-semibold text-slate-800">{{ $element->name }}</td>
                            <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">DESCRIPCIÓN</th>
                            <td class="border border-slate-300 bg-yellow-200 px-3 py-2">
                                <input
                                    type="text"
                                    x-model="bandStateDraft.description"
                                    class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                >
                            </td>
                        </tr>

                        <tr class="bg-white">
                            <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">ANCHO</th>
                            <td class="border border-slate-300 bg-yellow-200 px-3 py-2">
                                <input
                                    type="number"
                                    step="0.01"
                                    x-model="bandStateDraft.width"
                                    class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                >
                            </td>
                            <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">ÁREA</th>
                            <td class="border border-slate-300 px-3 py-2 text-center font-semibold text-slate-800">{{ $area->name }}</td>
                        </tr>

                        <tr class="bg-white">
                            <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">CUBIERTA SUPERIOR</th>
                            <td class="border border-slate-300 bg-yellow-200 px-3 py-2">
                                <input
                                    type="number"
                                    step="0.01"
                                    x-model="bandStateDraft.top_cover"
                                    class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                >
                            </td>
                            <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">CUBIERTA INFERIOR</th>
                            <td class="border border-slate-300 bg-yellow-200 px-3 py-2">
                                <input
                                    type="number"
                                    step="0.01"
                                    x-model="bandStateDraft.bottom_cover"
                                    class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                >
                            </td>
                        </tr>

                        <tr class="bg-white">
                            <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">DUREZA</th>
                            <td colspan="3" class="border border-slate-300 px-3 py-2 text-center font-semibold text-slate-500">
                                —
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Modal histórico - Informe de estado de banda --}}
<div
    x-cloak
    x-show="bandStateHistoryModalOpen"
    x-transition.opacity
    class="fixed inset-0 z-[94] flex items-center justify-center bg-slate-900/50 px-4 py-6"
    @keydown.escape.window="closeBandStateHistoryModal()"
>
    <div
        x-show="bandStateHistoryModalOpen"
        x-transition
        class="flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl"
        @click.outside="closeBandStateHistoryModal()"
    >
        <div class="border-b border-slate-200 px-6 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h3 class="text-xl font-semibold text-slate-900">Histórico - Informe de estado de banda</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Consulta los reportes oficiales publicados del informe de estado de banda del activo
                        <span class="font-semibold text-slate-700">{{ $element->name }}</span>.
                    </p>
                </div>

                <button
                    type="button"
                    @click="closeBandStateHistoryModal()"
                    class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                >
                    Cerrar
                </button>
            </div>
        </div>

        <div class="flex-1 overflow-hidden">
            <div class="grid h-full xl:grid-cols-[250px_minmax(0,1fr)]">
                <div class="border-b border-slate-200 xl:border-b-0 xl:border-r xl:border-slate-200">
                    <div class="max-h-[78vh] overflow-y-auto p-3">
                        <div
                            x-show="bandStateHistoricalReports.length === 0"
                            x-cloak
                            class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center"
                        >
                            <p class="text-sm font-semibold text-slate-700">
                                No hay reportes históricos aún.
                            </p>
                        </div>

                        <div class="space-y-3">
                            <template x-for="report in bandStateHistoricalReports" :key="'band-state-history-summary-' + report.id">
                                <button
                                    type="button"
                                    @click="selectBandStateHistoricalReport(report.id)"
                                    class="block w-full rounded-xl border px-3 py-3 text-left transition"
                                    :class="selectedBandStateHistoryReport && selectedBandStateHistoryReport.id === report.id
                                        ? 'border-[#d94d33] bg-[#d94d33]/5'
                                        : 'border-slate-200 bg-white hover:bg-slate-50'"
                                >
                                    <p class="text-sm font-semibold text-slate-900">
                                        Reporte <span x-text="formatDate(report.report_date)"></span>
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        Publicado: <span x-text="report.published_at ? formatDateTime(report.published_at) : '—'"></span>
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        Usuario: <span x-text="report.published_by || '—'"></span>
                                    </p>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="max-h-[78vh] overflow-y-auto p-4">
                    <div
                        x-show="bandStateHistoryLoading"
                        x-cloak
                        class="rounded-2xl border border-slate-200 bg-slate-50 p-6 text-center text-sm text-slate-600"
                    >
                        Cargando reporte...
                    </div>

                    <div
                        x-show="!bandStateHistoryLoading && !selectedBandStateHistoryReport"
                        x-cloak
                        class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center"
                    >
                        <p class="text-base font-semibold text-slate-700">
                            Selecciona un reporte del listado.
                        </p>
                    </div>

                    <div
                        x-show="!bandStateHistoryLoading && selectedBandStateHistoryReport"
                        x-cloak
                        class="space-y-4"
                    >
                        <div class="grid gap-4 md:grid-cols-3">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Fecha de reporte</p>
                                <p class="mt-2 text-sm font-semibold text-slate-800" x-text="selectedBandStateHistoryReport?.report_date ? formatDate(selectedBandStateHistoryReport.report_date) : '—'"></p>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Fecha de publicación</p>
                                <p class="mt-2 text-sm font-semibold text-slate-800" x-text="selectedBandStateHistoryReport?.published_at ? formatDateTime(selectedBandStateHistoryReport.published_at) : '—'"></p>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Usuario</p>
                                <p class="mt-2 text-sm font-semibold text-slate-800" x-text="selectedBandStateHistoryReport?.published_by || '—'"></p>
                            </div>
                        </div>

                        <div class="overflow-hidden rounded-2xl border border-slate-200">
                            <table class="w-full border-collapse text-sm">
                                <tbody>
                                    <tr class="bg-[#4f79bd] text-white">
                                        <th colspan="4" class="border border-slate-300 px-3 py-2 text-center text-sm font-bold uppercase">
                                            Informe de estado de bandas
                                        </th>
                                    </tr>

                                    <tr class="bg-white">
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">TAG DE LA BANDA</th>
                                        <td class="border border-slate-300 px-3 py-2 text-center font-semibold text-slate-800">{{ $element->name }}</td>
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">DESCRIPCIÓN</th>
                                        <td class="border border-slate-300 bg-yellow-200 px-3 py-2 text-center font-semibold text-slate-900" x-text="selectedBandStateHistoryReport?.description || '—'"></td>
                                    </tr>

                                    <tr class="bg-white">
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">ANCHO</th>
                                        <td class="border border-slate-300 bg-yellow-200 px-3 py-2 text-center font-semibold text-slate-900" x-text="displayValue(selectedBandStateHistoryReport?.width)"></td>
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">ÁREA</th>
                                        <td class="border border-slate-300 px-3 py-2 text-center font-semibold text-slate-800">{{ $area->name }}</td>
                                    </tr>

                                    <tr class="bg-white">
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">CUBIERTA SUPERIOR</th>
                                        <td class="border border-slate-300 bg-yellow-200 px-3 py-2 text-center font-semibold text-slate-900" x-text="displayValue(selectedBandStateHistoryReport?.top_cover)"></td>
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">CUBIERTA INFERIOR</th>
                                        <td class="border border-slate-300 bg-yellow-200 px-3 py-2 text-center font-semibold text-slate-900" x-text="displayValue(selectedBandStateHistoryReport?.bottom_cover)"></td>
                                    </tr>

                                    <tr class="bg-white">
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">DUREZA</th>
                                        <td colspan="3" class="border border-slate-300 px-3 py-2 text-center font-semibold text-slate-500">
                                            —
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
        {{-- Modal borrador --}}
        <div
            x-cloak
            x-show="draftModalOpen"
            x-transition.opacity
            class="fixed inset-0 z-[90] flex items-center justify-center bg-slate-900/50 px-4 py-6"
            @keydown.escape.window="closeDraftModal()"
        >
            <div
                x-show="draftModalOpen"
                x-transition
                class="flex max-h-[92vh] w-full max-w-7xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl"
                @click.outside="closeDraftModal()"
            >
                <div class="border-b border-slate-200 px-6 py-4">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                        <div>
                            <h3 class="text-xl font-semibold text-slate-900">Borrador - Espesores y dureza</h3>
                            <p class="mt-1 text-sm text-slate-500">
                                Edita las cubiertas del activo <span class="font-semibold text-slate-700">{{ $element->name }}</span>.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                @click="saveDraft()"
                                :disabled="loading"
                                class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 disabled:pointer-events-none disabled:opacity-70"
                            >
                                Guardar borrador
                            </button>

                            <button
                                type="button"
                                @click="addCover()"
                                :disabled="loading"
                                class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 disabled:pointer-events-none disabled:opacity-70"
                            >
                                +
                            </button>

                            <button
                                type="button"
                                @click="openPublishConfirm()"
                                :disabled="loading || !hasDraft()"
                                class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:pointer-events-none disabled:opacity-70"
                            >
                                Publicar reporte
                            </button>

                            <button
                                type="button"
                                @click="closeDraftModal()"
                                class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                            >
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-5">
                    <div
                        x-show="errors.length > 0"
                        class="mb-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                    >
                        <div class="font-semibold">Hay errores en el borrador.</div>
                        <ul class="mt-2 list-disc pl-5">
                            <template x-for="error in errors" :key="error">
                                <li x-text="error"></li>
                            </template>
                        </ul>
                    </div>

                    <div
                        x-show="!hasDraft()"
                        x-cloak
                        class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center"
                    >
                        <p class="text-base font-semibold text-slate-700">
                            Aún no existe borrador para este activo.
                        </p>
                        <p class="mt-2 text-sm text-slate-500">
                            Usa el botón superior para crear el primer borrador.
                        </p>
                    </div>

                    <div x-show="hasDraft()" x-cloak class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                        <div class="grid xl:grid-cols-[minmax(0,1.7fr)_minmax(260px,0.95fr)]">
                            {{-- Encabezado izquierdo --}}
                            <div class="grid grid-cols-[110px_1fr_120px_120px_120px_70px_70px] border-b border-slate-200 bg-slate-50 text-[11px] font-semibold uppercase tracking-wider text-slate-500 xl:border-r xl:border-slate-200">
                                <div class="px-3 py-2">Mediciones</div>
                                <div class="px-3 py-2"> </div>
                                <div class="px-3 py-2">Izquierdo</div>
                                <div class="px-3 py-2">Centro</div>
                                <div class="px-3 py-2">Derecho</div>
                                <div class="px-3 py-2">Máx</div>
                                <div class="px-3 py-2">Mín</div>
                            </div>

                            {{-- Encabezado derecho --}}
                            <div class="grid grid-cols-[1fr_110px_110px_110px] border-b border-slate-200 bg-slate-50 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                <div class="px-3 py-2">Mediciones</div>
                                <div class="px-3 py-2">Izquierdo</div>
                                <div class="px-3 py-2">Centro</div>
                                <div class="px-3 py-2">Derecho</div>
                            </div>

                            <template x-for="line in (draft && draft.lines ? draft.lines : [])" :key="'cover-' + line.cover_number">
                                <div class="contents">
                                    {{-- Bloque izquierdo --}}
                                    <div class="grid grid-cols-[110px_1fr_120px_120px_120px_70px_70px] border-t-4 border-slate-200 first:border-t-0 xl:border-r xl:border-slate-200">
                                        <div class="row-span-2 px-3 py-3">
                                            <div class="flex items-start gap-2">
                                                <span class="inline-flex min-w-8 items-center justify-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                                    <span x-text="line.cover_number"></span>
                                                </span>

                                                <button
                                                    type="button"
                                                    @click="removeCover(line.cover_number)"
                                                    :disabled="loading"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 bg-red-50 text-red-600 transition hover:bg-red-100 disabled:pointer-events-none disabled:opacity-50"
                                                    title="Eliminar cubierta"
                                                >
                                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="border-b border-slate-200 px-3 py-2 text-xs font-bold uppercase tracking-wider text-slate-600">
                                            Cubierta superior <span x-text="line.cover_number"></span>
                                        </div>
                                        <div class="border-b border-slate-200 px-3 py-2">
                                            <input type="number" step="0.01" x-model="line.top_left" class="w-full rounded-xl border border-slate-300 px-3 py-1.5 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]">
                                        </div>
                                        <div class="border-b border-slate-200 px-3 py-2">
                                            <input type="number" step="0.01" x-model="line.top_center" class="w-full rounded-xl border border-slate-300 px-3 py-1.5 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]">
                                        </div>
                                        <div class="border-b border-slate-200 px-3 py-2">
                                            <input type="number" step="0.01" x-model="line.top_right" class="w-full rounded-xl border border-slate-300 px-3 py-1.5 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]">
                                        </div>
                                        <div class="border-b border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700" x-text="maxValue([line.top_left, line.top_center, line.top_right])"></div>
                                        <div class="border-b border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700" x-text="minValue([line.top_left, line.top_center, line.top_right])"></div>

                                        <div class="px-3 py-2 text-xs font-bold uppercase tracking-wider text-slate-600">
                                            Cubierta inferior <span x-text="line.cover_number"></span>
                                        </div>
                                        <div class="px-3 py-2">
                                            <input type="number" step="0.01" x-model="line.bottom_left" class="w-full rounded-xl border border-slate-300 px-3 py-1.5 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]">
                                        </div>
                                        <div class="px-3 py-2">
                                            <input type="number" step="0.01" x-model="line.bottom_center" class="w-full rounded-xl border border-slate-300 px-3 py-1.5 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]">
                                        </div>
                                        <div class="px-3 py-2">
                                            <input type="number" step="0.01" x-model="line.bottom_right" class="w-full rounded-xl border border-slate-300 px-3 py-1.5 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]">
                                        </div>
                                        <div class="px-3 py-2 text-sm font-semibold text-slate-700" x-text="maxValue([line.bottom_left, line.bottom_center, line.bottom_right])"></div>
                                        <div class="px-3 py-2 text-sm font-semibold text-slate-700" x-text="minValue([line.bottom_left, line.bottom_center, line.bottom_right])"></div>
                                    </div>

                                    {{-- Bloque derecho --}}
                                    <div class="grid grid-cols-[1fr_110px_110px_110px] border-t-4 border-slate-200 first:border-t-0">
                                        <div class="border-b border-slate-200 px-3 py-2 text-xs font-bold uppercase tracking-wider text-slate-600">
                                            Dureza <span x-text="line.cover_number"></span>
                                        </div>
                                        <div class="border-b border-slate-200 px-3 py-2">
                                            <input type="number" step="0.01" x-model="line.hardness_left" class="w-full rounded-xl border border-slate-300 px-3 py-1.5 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]">
                                        </div>
                                        <div class="border-b border-slate-200 px-3 py-2">
                                            <input type="number" step="0.01" x-model="line.hardness_center" class="w-full rounded-xl border border-slate-300 px-3 py-1.5 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]">
                                        </div>
                                        <div class="border-b border-slate-200 px-3 py-2">
                                            <input type="number" step="0.01" x-model="line.hardness_right" class="w-full rounded-xl border border-slate-300 px-3 py-1.5 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]">
                                        </div>

                                        <div class="px-3 py-[21px] text-transparent select-none">.</div>
                                        <div class="px-3 py-[21px] text-transparent select-none">.</div>
                                        <div class="px-3 py-[21px] text-transparent select-none">.</div>
                                        <div class="px-3 py-[21px] text-transparent select-none">.</div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    {{-- Confirmación de publicación - Informe de estado de banda --}}
    <div
        x-cloak
        x-show="bandStatePublishConfirmOpen"
        x-transition.opacity
        class="fixed inset-0 z-[97] flex items-center justify-center bg-slate-900/60 px-4"
    >
    <div
        x-show="bandStatePublishConfirmOpen"
        x-transition
        class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl"
        @click.stop
    >
        <h3 class="text-lg font-semibold text-slate-900">Confirmar publicación</h3>
        <p class="mt-2 text-sm text-slate-600">
            Selecciona la fecha del informe y confirma la publicación del borrador como reporte oficial.
        </p>

        <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <label for="band_state_report_date_confirm" class="block text-xs font-semibold uppercase tracking-wider text-slate-400">
                Fecha del reporte
            </label>

            <input
                id="band_state_report_date_confirm"
                type="date"
                x-model="bandStatePublishForm.report_date"
                class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
            >
        </div>

        <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            Después de publicar, el borrador desaparecerá y el informe oficial quedará en el histórico.
        </div>

        <div class="mt-6 flex flex-wrap justify-end gap-2">
            <button
                type="button"
                @click="bandStatePublishConfirmOpen = false"
                class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
            >
                Cancelar
            </button>

            <button
                type="button"
                @click="publishBandStateDraft()"
                :disabled="loading"
                class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:pointer-events-none disabled:opacity-70"
            >
                Confirmar publicación
            </button>
        </div>
    </div>
</div>
        {{-- Confirmación de publicación --}}
                <div
                    x-cloak
                    x-show="publishConfirmOpen"
                    x-transition.opacity
                    class="fixed inset-0 z-[95] flex items-center justify-center bg-slate-900/60 px-4"
                >
                    <div
                        x-show="publishConfirmOpen"
                        x-transition
                        class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl"
                        @click.stop
                    >
                <h3 class="text-lg font-semibold text-slate-900">Confirmar publicación</h3>
                <p class="mt-2 text-sm text-slate-600">
                    Selecciona la fecha del reporte y confirma la publicación del borrador como reporte oficial histórico.
                </p>

                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <label for="publish_report_date_confirm" class="block text-xs font-semibold uppercase tracking-wider text-slate-400">
                        Fecha del reporte
                    </label>

                    <input
                        id="publish_report_date_confirm"
                        type="date"
                        x-model="publishForm.report_date"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                    >

                    <p class="mt-2 text-xs text-slate-500">
                        Selecciona la fecha con la que se publicará este reporte oficial.
                    </p>
                </div>

                <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    Después de publicar, el borrador desaparecerá y la vista principal mostrará el nuevo reporte oficial.
                </div>

                <div class="mt-6 flex flex-wrap justify-end gap-2">
                    <button
                        type="button"
                        @click="publishConfirmOpen = false"
                        class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                    >
                        Cancelar
                    </button>

                    <button
                        type="button"
                        @click="publishDraft()"
                        :disabled="loading"
                        class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:pointer-events-none disabled:opacity-70"
                    >
                        Confirmar publicación
                    </button>
                </div>
            </div>
        </div>

        {{-- Modal histórico --}}
        <div
            x-cloak
            x-show="historyModalOpen"
            x-transition.opacity
            class="fixed inset-0 z-[92] flex items-center justify-center bg-slate-900/50 px-4 py-6"
            @keydown.escape.window="closeHistoryModal()"
        >
            <div
                x-show="historyModalOpen"
                x-transition
                class="flex max-h-[92vh] w-full max-w-7xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl"
                @click.outside="closeHistoryModal()"
            >
                <div class="border-b border-slate-200 px-6 py-4">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h3 class="text-xl font-semibold text-slate-900">Histórico de reportes oficiales</h3>
                            <p class="mt-1 text-sm text-slate-500">
                                Consulta los reportes oficiales publicados del activo <span class="font-semibold text-slate-700">{{ $element->name }}</span>, con una presentación visual cercana al formato histórico de Excel.
                            </p>
                        </div>

                        <button
                            type="button"
                            @click="closeHistoryModal()"
                            class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>

                <div class="flex-1 overflow-hidden">
                    <div class="grid h-full xl:grid-cols-[280px_minmax(0,1fr)]">
                        <div class="border-b border-slate-200 xl:border-b-0 xl:border-r xl:border-slate-200">
                            <div class="max-h-[78vh] overflow-y-auto p-3">
                                <div
                                    x-show="historicalReports.length === 0"
                                    x-cloak
                                    class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center"
                                >
                                    <p class="text-sm font-semibold text-slate-700">No hay reportes históricos aún.</p>
                                </div>

                                <div class="space-y-3">
                                    <template x-for="report in historicalReports" :key="'history-summary-' + report.id">
                                        <button
                                            type="button"
                                            @click="selectHistoricalReport(report.id)"
                                            class="block w-full rounded-xl border px-3 py-3 text-left transition"
                                            :class="selectedHistoryReport && selectedHistoryReport.id === report.id
                                                ? 'border-[#d94d33] bg-[#d94d33]/5'
                                                : 'border-slate-200 bg-white hover:bg-slate-50'"
                                        >
                                            <p class="text-sm font-semibold text-slate-900">
                                                Reporte <span x-text="formatDate(report.report_date)"></span>
                                            </p>
                                            <p class="mt-1 text-xs text-slate-500">
                                                Publicado: <span x-text="report.published_at ? formatDateTime(report.published_at) : '—'"></span>
                                            </p>
                                            <p class="mt-1 text-xs text-slate-500">
                                                Usuario: <span x-text="report.published_by || '—'"></span>
                                            </p>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div class="max-h-[78vh] overflow-y-auto p-6">
                            <div
                                x-show="historyLoading"
                                x-cloak
                                class="rounded-2xl border border-slate-200 bg-slate-50 p-6 text-center text-sm text-slate-600"
                            >
                                Cargando reporte...
                            </div>

                            <div
                                x-show="!historyLoading && !selectedHistoryReport"
                                x-cloak
                                class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center"
                            >
                                <p class="text-base font-semibold text-slate-700">
                                    Selecciona un reporte del listado.
                                </p>
                                <p class="mt-2 text-sm text-slate-500">
                                    Aquí se mostrará la visualización histórica en solo lectura.
                                </p>
                            </div>

                            <div x-show="!historyLoading && selectedHistoryReport" x-cloak class="space-y-4">
                                <div class="grid gap-4 md:grid-cols-3">
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Fecha de reporte</p>
                                        <p class="mt-2 text-sm font-semibold text-slate-800" x-text="selectedHistoryReport?.report_date ? formatDate(selectedHistoryReport.report_date) : '—'"></p>
                                    </div>

                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Fecha de publicación</p>
                                        <p class="mt-2 text-sm font-semibold text-slate-800" x-text="selectedHistoryReport?.published_at ? formatDateTime(selectedHistoryReport.published_at) : '—'"></p>
                                    </div>

                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Usuario</p>
                                        <p class="mt-2 text-sm font-semibold text-slate-800" x-text="selectedHistoryReport?.published_by || '—'"></p>
                                    </div>
                                </div>

                                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                                    <div class="overflow-x-auto">
                                        <div class="grid min-w-[1100px] grid-cols-[minmax(0,1.75fr)_minmax(320px,0.85fr)]">
                                            {{-- Tabla principal --}}
                                            <div class="border-r border-slate-200">
                                                <table class="w-full border-collapse text-sm">
                                                    <thead>
                                                        <tr class="bg-slate-100 text-slate-700">
                                                            <th class="border-b border-slate-200 px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Mediciones</th>
                                                            <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">Izquierdo</th>
                                                            <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">Centro</th>
                                                            <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">Derecho</th>
                                                            <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">Max</th>
                                                            <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">Min</th>
                                                            <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">% Suficiencia</th>
                                                        </tr>
                                                    </thead>

                                                    <template x-for="line in (selectedHistoryReport?.lines || [])" :key="'history-main-' + line.id">
                                                        <tbody>
                                                            <tr class="bg-white hover:bg-slate-50 transition">
                                                                <td class="border-b border-slate-200 px-4 py-3 font-semibold text-slate-800">
                                                                    CUBIERTA SUPERIOR <span x-text="line.cover_number"></span>
                                                                </td>
                                                                <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700" x-text="displayValue(line.top_left)"></td>
                                                                <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700" x-text="displayValue(line.top_center)"></td>
                                                                <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700" x-text="displayValue(line.top_right)"></td>
                                                                <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700" x-text="maxValue([line.top_left, line.top_center, line.top_right]) || '—'"></td>
                                                                <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700" x-text="minValue([line.top_left, line.top_center, line.top_right]) || '—'"></td>
                                                                <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-400">—</td>
                                                            </tr>

                                                            <tr class="bg-slate-50/60 hover:bg-slate-100/70 transition">
                                                                <td class="border-b border-slate-200 px-4 py-3 font-semibold text-slate-800">
                                                                    CUBIERTA INFERIOR <span x-text="line.cover_number"></span>
                                                                </td>
                                                                <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700" x-text="displayValue(line.bottom_left)"></td>
                                                                <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700" x-text="displayValue(line.bottom_center)"></td>
                                                                <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700" x-text="displayValue(line.bottom_right)"></td>
                                                                <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700" x-text="maxValue([line.bottom_left, line.bottom_center, line.bottom_right]) || '—'"></td>
                                                                <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700" x-text="minValue([line.bottom_left, line.bottom_center, line.bottom_right]) || '—'"></td>
                                                                <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-400">—</td>
                                                            </tr>
                                                        </tbody>
                                                    </template>
                                                </table>
                                            </div>

                                            {{-- Tabla dureza --}}
                                            <div>
                                                <table class="w-full border-collapse text-sm">
                                                    <thead>
                                                        <tr class="bg-slate-100 text-slate-700">
                                                            <th class="border-b border-slate-200 px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Mediciones</th>
                                                            <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">Izquierdo</th>
                                                            <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">Centro</th>
                                                            <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">Derecho</th>
                                                        </tr>
                                                    </thead>
                                                    <template x-for="line in (selectedHistoryReport?.lines || [])" :key="'history-hardness-' + line.id">
                                                        <tbody>
                                                            <tr class="bg-white hover:bg-slate-50 transition">
                                                                <td class="border-b border-slate-200 px-4 py-3 font-semibold text-slate-800">
                                                                    DUREZA <span x-text="line.cover_number"></span>
                                                                </td>
                                                                <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700" x-text="displayValue(line.hardness_left)"></td>
                                                                <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700" x-text="displayValue(line.hardness_center)"></td>
                                                                <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700" x-text="displayValue(line.hardness_right)"></td>
                                                            </tr>

                                                            <tr class="bg-slate-50/60">
                                                                <td class="border-b border-slate-200 px-4 py-3 text-transparent select-none">.</td>
                                                                <td class="border-b border-slate-200 px-4 py-3 text-transparent select-none">.</td>
                                                                <td class="border-b border-slate-200 px-4 py-3 text-transparent select-none">.</td>
                                                                <td class="border-b border-slate-200 px-4 py-3 text-transparent select-none">.</td>
                                                            </tr>
                                                        </tbody>
                                                    </template>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        function showCrudToast(message, type = 'success') {
            const toastId = 'crudInlineToast';
            let toast = document.getElementById(toastId);

            if (!toast) {
                toast = document.createElement('div');
                toast.id = toastId;
                document.body.appendChild(toast);
            }

            toast.className =
                'fixed bottom-6 right-6 z-[100] rounded-2xl px-4 py-3 text-sm font-semibold shadow-xl transition ' +
                (type === 'success'
                    ? 'border border-green-200 bg-green-100 text-green-700'
                    : 'border border-red-200 bg-red-100 text-red-700');

            toast.textContent = message;
            toast.classList.remove('hidden');

            clearTimeout(window.__crudToastTimeout);
            window.__crudToastTimeout = setTimeout(() => {
                toast.classList.add('hidden');
            }, 2600);
        }

        function measurementThicknessModule(config) {
            return {
                elementId: config.elementId,
                routes: config.routes,

                draft: config.initialDraft,
                latestReport: config.initialLatestReport,
                historicalReports: Array.isArray(config.initialHistoricalReports) ? config.initialHistoricalReports : [],
                selectedHistoryReport: null,

                bandStateDraft: config.initialBandStateDraft ?? {
                    id: null,
                    element_id: config.elementId,
                    description: '',
                    width: '',
                    top_cover: '',
                    bottom_cover: '',
                },
                latestBandStateReport: config.initialLatestBandStateReport,
                bandStateHistoricalReports: Array.isArray(config.initialBandStateHistoricalReports) ? config.initialBandStateHistoricalReports : [],
                selectedBandStateHistoryReport: null,

                draftModalOpen: false,
                historyModalOpen: false,
                publishConfirmOpen: false,

                bandStateDraftModalOpen: false,
                bandStateHistoryModalOpen: false,
                bandStatePublishConfirmOpen: false,
                bandStateHistoryLoading: false,
                bandStateErrors: [],

                historyLoading: false,
                loading: false,
                errors: [],

                publishForm: {
                    report_date: config.today,
                },

                bandStatePublishForm: {
                    report_date: config.today,
                },

                init() {
                    if (this.historicalReports.length > 0 && !this.selectedHistoryReport) {
                        this.selectedHistoryReport = null;
                    }
                },

                hasDraft() {
                    return !!(this.draft && Array.isArray(this.draft.lines));
                },
hasBandStateDraft() {
    return !!(this.bandStateDraft && this.bandStateDraft.id);
},

openBandStateDraftModal() {
    this.bandStateErrors = [];

    if (!this.hasBandStateDraft()) {
        this.createBandStateDraft();
        return;
    }

    this.bandStateDraftModalOpen = true;
},

closeBandStateDraftModal() {
    this.bandStateDraftModalOpen = false;
    this.bandStatePublishConfirmOpen = false;
    this.bandStateErrors = [];
},

openBandStatePublishConfirm() {
    this.bandStateErrors = [];

    if (!this.hasBandStateDraft()) {
        showCrudToast('No existe un borrador del informe de estado de banda.', 'error');
        return;
    }

    this.bandStatePublishForm.report_date = config.today;
    this.bandStatePublishConfirmOpen = true;
},

closeBandStateHistoryModal() {
    this.bandStateHistoryModalOpen = false;
},

async openBandStateHistoryModal() {
    this.bandStateHistoryModalOpen = true;
    await this.fetchBandStateHistoricalReports();

    if (!this.selectedBandStateHistoryReport && this.bandStateHistoricalReports.length > 0) {
        await this.selectBandStateHistoricalReport(this.bandStateHistoricalReports[0].id);
    }
},

async createBandStateDraft() {
    this.loading = true;
    this.bandStateErrors = [];

    try {
        const response = await fetch(this.routes.bandStateCreate, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'No fue posible crear el borrador del informe de estado de banda.');
        }

        this.bandStateDraft = data.draft;
        this.bandStateDraftModalOpen = true;
        showCrudToast(data.message || 'Borrador del informe de estado de banda creado correctamente.', 'success');
    } catch (error) {
        showCrudToast(error.message || 'Ocurrió un error al crear el borrador del informe de estado de banda.', 'error');
    } finally {
        this.loading = false;
    }
},

async saveBandStateDraft(showToast = true) {
    if (!this.hasBandStateDraft()) {
        if (showToast) {
            showCrudToast('Primero debes crear un borrador del informe de estado de banda.', 'error');
        }
        return false;
    }

    this.loading = true;
    this.bandStateErrors = [];

    try {
        const response = await fetch(this.routes.bandStateUpdate, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                description: this.bandStateDraft.description || null,
                width: this.bandStateDraft.width || null,
                top_cover: this.bandStateDraft.top_cover || null,
                bottom_cover: this.bandStateDraft.bottom_cover || null,
            }),
        });

        const data = await response.json();

        if (response.status === 422) {
            this.bandStateErrors = Object.values(data.errors || {}).flat();
            if (showToast) {
                showCrudToast('Corrige los errores del informe de estado de banda.', 'error');
            }
            return false;
        }

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'No fue posible guardar el borrador del informe de estado de banda.');
        }

        this.bandStateDraft = data.draft;

        if (showToast) {
            showCrudToast(data.message || 'Borrador del informe de estado de banda guardado correctamente.', 'success');
        }

        return true;
    } catch (error) {
        if (showToast) {
            showCrudToast(error.message || 'Ocurrió un error al guardar el borrador del informe de estado de banda.', 'error');
        }
        return false;
    } finally {
        this.loading = false;
    }
},

async publishBandStateDraft() {
    if (!this.hasBandStateDraft()) {
        showCrudToast('No existe un borrador del informe de estado de banda para publicar.', 'error');
        return;
    }

    const saved = await this.saveBandStateDraft(false);
    if (!saved) {
        showCrudToast('No fue posible sincronizar el borrador del informe de estado de banda antes de publicar.', 'error');
        return;
    }

    this.loading = true;
    this.bandStateErrors = [];

    try {
        const response = await fetch(this.routes.bandStatePublish, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                report_date: this.bandStatePublishForm.report_date,
            }),
        });

        const data = await response.json();

        if (response.status === 422) {
            this.bandStateErrors = Array.isArray(data.errors)
                ? data.errors
                : Object.values(data.errors || {}).flat();

            showCrudToast(data.message || 'No se pudo publicar el informe de estado de banda.', 'error');
            this.bandStatePublishConfirmOpen = false;
            return;
        }

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'No fue posible publicar el informe de estado de banda.');
        }

        this.bandStateDraft = {
            id: null,
            element_id: this.elementId,
            description: '',
            width: '',
            top_cover: '',
            bottom_cover: '',
        };
        this.latestBandStateReport = data.latest_report || data.report || null;
        this.bandStatePublishConfirmOpen = false;
        this.bandStateDraftModalOpen = false;
        this.bandStatePublishForm.report_date = config.today;

        await this.fetchBandStateHistoricalReports();

        if (this.latestBandStateReport?.id) {
            this.selectedBandStateHistoryReport = this.latestBandStateReport;
        }

        showCrudToast(data.message || 'Informe de estado de banda publicado correctamente.', 'success');
    } catch (error) {
        showCrudToast(error.message || 'Ocurrió un error al publicar el informe de estado de banda.', 'error');
    } finally {
        this.loading = false;
    }
},

        async fetchBandStateHistoricalReports() {
            this.bandStateHistoryLoading = true;

            try {
                const response = await fetch(this.routes.bandStateHistoryIndex, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'No fue posible cargar el histórico del informe de estado de banda.');
                }

                this.bandStateHistoricalReports = Array.isArray(data.reports) ? data.reports : [];
            } catch (error) {
                showCrudToast(error.message || 'Ocurrió un error al cargar el histórico del informe de estado de banda.', 'error');
            } finally {
                this.bandStateHistoryLoading = false;
            }
        },

        async selectBandStateHistoricalReport(reportId) {
            if (!reportId) return;

            this.bandStateHistoryLoading = true;

            try {
                const response = await fetch(this.routes.bandStateHistoryShowTemplate.replace('__REPORT__', reportId), {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'No fue posible cargar el reporte histórico del informe de estado de banda.');
                }

                this.selectedBandStateHistoryReport = data.report;
            } catch (error) {
                showCrudToast(error.message || 'Ocurrió un error al cargar el detalle histórico del informe de estado de banda.', 'error');
            } finally {
                this.bandStateHistoryLoading = false;
            }
        },

                displayValue(value) {
                    if (value === null || value === '' || typeof value === 'undefined') {
                        return '—';
                    }

                    const num = Number(value);
                    if (Number.isNaN(num)) {
                        return value;
                    }

                    return num.toFixed(2);
                },

                formatDate(value) {
                    if (!value) return '—';

                    const [year, month, day] = value.split('-');
                    if (!year || !month || !day) return value;

                    return `${day}/${month}/${year}`;
                },

                formatDateTime(value) {
                    if (!value) return '—';

                    const normalized = value.replace(' ', 'T');
                    const date = new Date(normalized);

                    if (Number.isNaN(date.getTime())) {
                        return value;
                    }

                    return new Intl.DateTimeFormat('es-CO', {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit',
                    }).format(date);
                },

                async openDraftModal() {
                    this.errors = [];

                    if (!this.hasDraft()) {
                        await this.createDraft();
                    }

                    if (this.hasDraft()) {
                        this.draftModalOpen = true;

                        this.$nextTick(() => {
                            if (window.lucide) {
                                window.lucide.createIcons();
                            }
                        });
                    }
                },

                closeDraftModal() {
                    this.draftModalOpen = false;
                    this.publishConfirmOpen = false;
                    this.errors = [];
                },

                async openHistoryModal() {
                    this.historyModalOpen = true;
                    await this.fetchHistoricalReports();

                    if (!this.selectedHistoryReport && this.historicalReports.length > 0) {
                        await this.selectHistoricalReport(this.historicalReports[0].id);
                    }
                },

                closeHistoryModal() {
                    this.historyModalOpen = false;
                },

                openPublishConfirm() {
                    this.errors = [];

                    if (!this.hasDraft()) {
                        showCrudToast('No existe un borrador para publicar.', 'error');
                        return;
                    }

                    this.publishForm.report_date = config.today;
                    this.publishConfirmOpen = true;
                },

                async createDraft() {
                    this.loading = true;
                    this.errors = [];

                    try {
                        const response = await fetch(this.routes.create, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            },
                        });

                        const data = await response.json();

                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'No fue posible crear el borrador.');
                        }

                        this.draft = data.draft;
                        showCrudToast(data.message || 'Borrador creado correctamente.', 'success');
                    } catch (error) {
                        showCrudToast(error.message || 'Ocurrió un error al crear el borrador.', 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                async saveDraft(showToast = true) {
                    if (!this.hasDraft()) {
                        if (showToast) {
                            showCrudToast('Primero debes crear un borrador.', 'error');
                        }
                        return false;
                    }

                    this.loading = true;
                    this.errors = [];

                    try {
                        const response = await fetch(this.routes.update, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            },
                            body: JSON.stringify({
                                lines: this.draft.lines.map(line => ({
                                    cover_number: line.cover_number,
                                    top_left: line.top_left || null,
                                    top_center: line.top_center || null,
                                    top_right: line.top_right || null,
                                    bottom_left: line.bottom_left || null,
                                    bottom_center: line.bottom_center || null,
                                    bottom_right: line.bottom_right || null,
                                    hardness_left: line.hardness_left || null,
                                    hardness_center: line.hardness_center || null,
                                    hardness_right: line.hardness_right || null,
                                })),
                            }),
                        });

                        const data = await response.json();

                        if (response.status === 422) {
                            this.errors = Object.values(data.errors || {}).flat();
                            if (showToast) {
                                showCrudToast('Corrige los errores del borrador.', 'error');
                            }
                            return false;
                        }

                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'No fue posible guardar el borrador.');
                        }

                        this.draft = data.draft;

                        if (showToast) {
                            showCrudToast(data.message || 'Borrador guardado correctamente.', 'success');
                        }

                        return true;
                    } catch (error) {
                        if (showToast) {
                            showCrudToast(error.message || 'Ocurrió un error al guardar el borrador.', 'error');
                        }
                        return false;
                    } finally {
                        this.loading = false;
                    }
                },

                async publishDraft() {
                    if (!this.hasDraft()) {
                        showCrudToast('No existe un borrador para publicar.', 'error');
                        return;
                    }

                    const saved = await this.saveDraft(false);
                    if (!saved) {
                        showCrudToast('No fue posible sincronizar el borrador antes de publicar.', 'error');
                        return;
                    }

                    this.loading = true;
                    this.errors = [];

                    try {
                        const response = await fetch(this.routes.publish, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            },
                            body: JSON.stringify({
                                report_date: this.publishForm.report_date,
                            }),
                        });

                        const data = await response.json();

                        if (response.status === 422) {
                            this.errors = Array.isArray(data.errors)
                                ? data.errors
                                : Object.values(data.errors || {}).flat();

                            showCrudToast(data.message || 'No se pudo publicar el borrador.', 'error');
                            this.publishConfirmOpen = false;
                            return;
                        }

                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'No fue posible publicar el borrador.');
                        }

                        this.draft = null;
                        this.latestReport = data.latest_report || data.report || null;
                        this.publishConfirmOpen = false;
                        this.draftModalOpen = false;
                        this.publishForm.report_date = config.today;

                        await this.fetchHistoricalReports();

                        if (this.latestReport?.id) {
                            this.selectedHistoryReport = this.latestReport;
                        }

                        showCrudToast(data.message || 'Reporte publicado correctamente.', 'success');
                    } catch (error) {
                        showCrudToast(error.message || 'Ocurrió un error al publicar el borrador.', 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                async fetchHistoricalReports() {
                    this.historyLoading = true;

                    try {
                        const response = await fetch(this.routes.historyIndex, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        const data = await response.json();

                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'No fue posible cargar el histórico.');
                        }

                        this.historicalReports = Array.isArray(data.reports) ? data.reports : [];
                    } catch (error) {
                        showCrudToast(error.message || 'Ocurrió un error al cargar el histórico.', 'error');
                    } finally {
                        this.historyLoading = false;
                    }
                },

                async selectHistoricalReport(reportId) {
                    if (!reportId) return;

                    this.historyLoading = true;

                    try {
                        const response = await fetch(this.routes.historyShowTemplate.replace('__REPORT__', reportId), {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        const data = await response.json();

                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'No fue posible cargar el reporte histórico.');
                        }

                        this.selectedHistoryReport = data.report;
                    } catch (error) {
                        showCrudToast(error.message || 'Ocurrió un error al cargar el detalle histórico.', 'error');
                    } finally {
                        this.historyLoading = false;
                    }
                },

                async addCover() {
                    if (!this.hasDraft()) {
                        showCrudToast('Primero debes crear un borrador.', 'error');
                        return;
                    }

                    this.loading = true;
                    this.errors = [];

                    try {
                        const response = await fetch(this.routes.addCover, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            },
                        });

                        const data = await response.json();

                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'No fue posible agregar una cubierta.');
                        }

                        this.draft = data.draft;
                        this.$nextTick(() => {
                            if (window.lucide) {
                                window.lucide.createIcons();
                            }
                        });
                        showCrudToast(data.message || 'Se agregó una nueva cubierta al borrador.', 'success');
                    } catch (error) {
                        showCrudToast(error.message || 'Ocurrió un error al agregar una cubierta.', 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                async removeCover(coverNumber) {
                    if (!this.hasDraft()) {
                        showCrudToast('No existe borrador para editar.', 'error');
                        return;
                    }

                    const saved = await this.saveDraft(false);
                    if (!saved) {
                        showCrudToast('No fue posible sincronizar el borrador antes de eliminar la cubierta.', 'error');
                        return;
                    }

                    this.loading = true;
                    this.errors = [];

                    try {
                        const response = await fetch(this.routes.removeCoverTemplate.replace('__COVER__', coverNumber), {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            },
                        });

                        const data = await response.json();

                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'No fue posible eliminar la cubierta.');
                        }

                        this.draft = data.draft;
                        showCrudToast(data.message || 'Cubierta eliminada correctamente.', 'success');

                        this.$nextTick(() => {
                            if (window.lucide) {
                                window.lucide.createIcons();
                            }
                        });
                    } catch (error) {
                        showCrudToast(error.message || 'Ocurrió un error al eliminar la cubierta.', 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                maxValue(values) {
                    const nums = values
                        .map(value => value === '' || value === null ? null : Number(value))
                        .filter(value => value !== null && !Number.isNaN(value));

                    if (nums.length === 0) return '';
                    return Math.max(...nums).toFixed(2);
                },

                minValue(values) {
                    const nums = values
                        .map(value => value === '' || value === null ? null : Number(value))
                        .filter(value => value !== null && !Number.isNaN(value));

                    if (nums.length === 0) return '';
                    return Math.min(...nums).toFixed(2);
                },
            }
        }
    </script>
@endsection