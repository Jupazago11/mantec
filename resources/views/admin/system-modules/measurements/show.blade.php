@extends('layouts.measurements')

@section('title', 'Mediciones - ' . $element->name)

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
            initialBandEventLatestReport: @js($bandEventLatestReportData ?? null),
            initialBandEventActiveBand: @js($bandEventActiveBandData ?? null),
            initialBandEventBands: @js($bandEventBandsData ?? []),
            initialBandEventHistoricalTree: @js($bandEventHistoricalTreeData ?? []),

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
                bandCreate: @js(route('band-events.draft.create', $element->id)),
                bandUpdate: @js(route('band-events.draft.update', $element->id)),
                bandPublish: @js(route('band-events.draft.publish', $element->id)),

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
                <div class="p-6">
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

                        <div class="mt-4 flex justify-center gap-2">
                            <button
                                type="button"
                                @click="openBandStateDraftModal()"
                                :title="hasBandStateDraft() ? 'Continuar borrador' : 'Crear borrador'"
                                class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-[#d94d33] text-white transition hover:bg-[#b83f29]"
                            >
                                <i data-lucide="file-pen-line" class="h-5 w-5"></i>
                            </button>

                            <button
                                type="button"
                                @click="openBandStateHistoryModal()"
                                :disabled="loading"
                                title="Ver histórico"
                                class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-700 transition hover:bg-slate-100 disabled:pointer-events-none disabled:opacity-70"
                            >
                                <i data-lucide="history" class="h-5 w-5"></i>
                            </button>
                        </div>
                    </div>

                    <div
                        x-show="latestBandStateReport"
                        x-cloak
                        class="flex items-start gap-3"
                    >
                        <div class="min-w-0 flex-1 overflow-hidden rounded-2xl border border-slate-200 bg-white">
                            <table class="w-full border-collapse text-sm">
                                <tbody>
                                    <tr class="bg-[#4f79bd] text-white">
                                        <th colspan="4" class="border border-slate-300 px-3 py-2 text-center text-sm font-bold uppercase">
                                            Informe de estado de bandas
                                            <span
                                                class="ml-2 font-semibold normal-case"
                                                x-text="latestBandStateReport?.report_date ? ' - ' + formatDate(latestBandStateReport.report_date) : ''"
                                            ></span>
                                        </th>
                                    </tr>

                                    <tr class="bg-white">
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                            TAG DE LA BANDA
                                        </th>
                                        <td class="border border-slate-300 px-3 py-2 text-center font-semibold text-slate-800">
                                            {{ $element->name }}
                                        </td>
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                            DESCRIPCIÓN
                                        </th>
                                        <td
                                            class="border border-slate-300 bg-yellow-200 px-3 py-2 text-center font-semibold text-slate-900"
                                            x-text="latestBandStateReport?.description || '—'"
                                        ></td>
                                    </tr>

                                    <tr class="bg-white">
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                            ANCHO
                                        </th>
                                        <td
                                            class="border border-slate-300 bg-yellow-200 px-3 py-2 text-center font-semibold text-slate-900"
                                            x-text="displayValue(latestBandStateReport?.width)"
                                        ></td>
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                            ÁREA
                                        </th>
                                        <td class="border border-slate-300 px-3 py-2 text-center font-semibold text-slate-800">
                                            {{ $area->name }}
                                        </td>
                                    </tr>

                                    <tr class="bg-white">
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                            CUBIERTA SUPERIOR
                                        </th>
                                        <td
                                            class="border border-slate-300 bg-yellow-200 px-3 py-2 text-center font-semibold text-slate-900"
                                            x-text="displayValue(latestBandStateReport?.top_cover)"
                                        ></td>
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                            CUBIERTA INFERIOR
                                        </th>
                                        <td
                                            class="border border-slate-300 bg-yellow-200 px-3 py-2 text-center font-semibold text-slate-900"
                                            x-text="displayValue(latestBandStateReport?.bottom_cover)"
                                        ></td>
                                    </tr>

                                    <tr class="bg-white">
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                            DUREZA
                                        </th>
                                        <td
                                            colspan="3"
                                            class="border border-slate-300 px-3 py-2 text-center font-semibold"
                                            :class="latestBandStateReport?.calculated_hardness ? 'text-slate-900' : 'text-slate-500'"
                                            x-text="latestBandStateReport?.calculated_hardness ? displayValue(latestBandStateReport.calculated_hardness) : '—'"
                                        ></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="flex shrink-0 flex-col gap-2 pt-1">
                            <button
                                type="button"
                                @click="openBandStateDraftModal()"
                                :title="hasBandStateDraft() ? 'Continuar borrador' : 'Crear borrador'"
                                class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-[#d94d33] text-white transition hover:bg-[#b83f29]"
                            >
                                <i data-lucide="file-pen-line" class="h-5 w-5"></i>
                            </button>

                            <button
                                type="button"
                                @click="openBandStateHistoryModal()"
                                :disabled="loading"
                                title="Ver histórico"
                                class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-700 transition hover:bg-slate-100 disabled:pointer-events-none disabled:opacity-70"
                            >
                                <i data-lucide="history" class="h-5 w-5"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        {{-- Superior derecha --}}
            {{-- Superior derecha - Cambio de banda --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="p-6">
                    <div
                        x-show="!bandEventLatestReport"
                        x-cloak
                        class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center"
                    >
                        <p class="text-sm font-semibold text-slate-700">
                            Aún no existe un reporte oficial de cambio de banda.
                        </p>

                        <p class="mt-2 text-sm text-slate-500">
                            Usa el asistente para registrar una banda nueva, un vulcanizado o un cambio de tramo.
                        </p>

                        <div class="mt-5 flex flex-wrap justify-center gap-2">
                            <button
                                type="button"
                                @click="openBandWizard('band')"
                                class="rounded-xl bg-[#d94d33] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                            >
                                Banda nueva
                            </button>

                            <button
                                type="button"
                                @click="openBandWizard('vulcanization')"
                                class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                            >
                                Vulcanizado
                            </button>

                            <button
                                type="button"
                                @click="openBandWizard('section_change')"
                                class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                            >
                                Cambio tramo
                            </button>
                        </div>
                    </div>

                    <div
                        x-show="bandEventLatestReport"
                        x-cloak
                        class="flex items-start gap-3"
                    >
                        <div class="min-w-0 flex-1 overflow-hidden rounded-2xl border border-slate-200 bg-white">
                            <table class="w-full border-collapse text-sm">
                                <tbody>
                                    <tr class="bg-[#4f79bd] text-white">
                                        <th colspan="4" class="border border-slate-300 px-3 py-2 text-center text-sm font-bold uppercase">
                                            Cambio de banda
                                            <span
                                                class="ml-2 font-semibold normal-case"
                                                x-text="bandEventLatestReport?.report_date ? ' - ' + formatDate(bandEventLatestReport.report_date) : ''"
                                            ></span>
                                        </th>
                                    </tr>

                                    <tr class="bg-white">
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                            Tipo
                                        </th>
                                        <td
                                            class="border border-slate-300 bg-yellow-200 px-3 py-2 text-center font-semibold text-slate-900"
                                            x-text="bandEventTypeLabel(bandEventLatestReport?.type)"
                                        ></td>

                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                            Activo
                                        </th>
                                        <td class="border border-slate-300 px-3 py-2 text-center font-semibold text-slate-800">
                                            {{ $element->name }}
                                        </td>
                                    </tr>

                                    <tr class="bg-white">
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                            Marca
                                        </th>
                                        <td
                                            class="border border-slate-300 bg-yellow-200 px-3 py-2 text-center font-semibold text-slate-900"
                                            x-text="bandEventLatestReport?.brand || '—'"
                                        ></td>

                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                            Rollos
                                        </th>
                                        <td
                                            class="border border-slate-300 bg-yellow-200 px-3 py-2 text-center font-semibold text-slate-900"
                                            x-text="bandEventLatestReport?.roll_count ?? '—'"
                                        ></td>
                                    </tr>

                                    <tr class="bg-white">
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                            Ancho
                                        </th>
                                        <td
                                            class="border border-slate-300 bg-yellow-200 px-3 py-2 text-center font-semibold text-slate-900"
                                            x-text="displayValue(bandEventLatestReport?.width)"
                                        ></td>

                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                            Longitud
                                        </th>
                                        <td
                                            class="border border-slate-300 bg-yellow-200 px-3 py-2 text-center font-semibold text-slate-900"
                                            x-text="displayValue(bandEventLatestReport?.length)"
                                        ></td>
                                    </tr>

                                    <tr class="bg-white">
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                            Observación
                                        </th>
                                        <td
                                            colspan="3"
                                            class="border border-slate-300 px-3 py-2 text-center font-semibold"
                                            :class="bandEventLatestReport?.observation ? 'text-slate-900' : 'text-slate-500'"
                                            x-text="bandEventLatestReport?.observation || '—'"
                                        ></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="flex shrink-0 flex-col gap-2 pt-1">
                            <button
                                type="button"
                                @click="openBandHistoryModal()"
                                title="Ver histórico"
                                class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-700 transition hover:bg-slate-100"
                            >
                                <i data-lucide="clock" class="h-5 w-5"></i>
                            </button>

                            <button
                                type="button"
                                @click="openBandWizard('vulcanization')"
                                title="Registrar vulcanizado"
                                class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-700 transition hover:bg-slate-100"
                            >
                                <i data-lucide="wrench" class="h-5 w-5"></i>
                            </button>

                            <button
                                type="button"
                                @click="openBandWizard('section_change')"
                                title="Registrar cambio de tramo"
                                class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-700 transition hover:bg-slate-100"
                            >
                                <i data-lucide="scissors-line-dashed" class="h-5 w-5"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- Modal histórico - Cambio de banda --}}
        <div
            x-cloak
            x-show="bandHistoryModalOpen"
            x-transition.opacity
            class="fixed top-0 left-0 z-[9999] flex h-screen w-screen items-center justify-center bg-slate-900/60 px-4 py-6"
            @keydown.escape.window="closeBandHistoryModal()"
        >
            <div
                x-show="bandHistoryModalOpen"
                x-transition
                class="flex max-h-[92vh] w-full max-w-7xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl"
                @click.outside="closeBandHistoryModal()"
            >
                <div class="border-b border-slate-200 px-6 py-4">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h3 class="text-xl font-semibold text-slate-900">Histórico - Cambio de banda</h3>
                            <p class="mt-1 text-sm text-slate-500">
                                Consulta la banda padre y sus eventos hijos del activo
                                <span class="font-semibold text-slate-700">{{ $element->name }}</span>.
                            </p>
                        </div>

                        <button
                            type="button"
                            @click="closeBandHistoryModal()"
                            class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>

                <div class="flex-1 overflow-hidden">
                    <div class="grid h-full xl:grid-cols-[280px_minmax(0,1fr)]">
                        {{-- LISTADO IZQUIERDO --}}
                        <div class="border-b border-slate-200 xl:border-b-0 xl:border-r xl:border-slate-200">
                            <div class="max-h-[78vh] overflow-y-auto p-3">
                                <div
                                    x-show="bandHistoricalTree.length === 0"
                                    x-cloak
                                    class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center"
                                >
                                    <p class="text-sm font-semibold text-slate-700">
                                        No hay histórico de cambio de banda aún.
                                    </p>
                                </div>

                                <div class="space-y-3">
                                    <template x-for="band in bandHistoricalTree" :key="'band-history-summary-' + band.id">
                                        <button
                                            type="button"
                                            @click="selectBandHistory(band)"
                                            class="block w-full rounded-xl border px-3 py-3 text-left transition"
                                            :class="selectedBandHistory && selectedBandHistory.id === band.id
                                                ? 'border-[#d94d33] bg-[#d94d33]/5'
                                                : 'border-slate-200 bg-white hover:bg-slate-50'"
                                        >
                                            <p class="text-sm font-semibold text-slate-900">
                                                <span x-text="formatDate(band.report_date)"></span>
                                            </p>
                                            <p class="mt-1 text-xs text-slate-500">
                                                Marca: <span x-text="band.brand || '—'"></span>
                                            </p>
                                            <p class="mt-1 text-xs text-slate-500">
                                                Rollos: <span x-text="band.roll_count ?? '—'"></span>
                                            </p>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        {{-- DETALLE DERECHO --}}
                        <div class="max-h-[78vh] overflow-y-auto p-4">
                            <div
                                x-show="!selectedBandHistory"
                                x-cloak
                                class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center"
                            >
                                <p class="text-base font-semibold text-slate-700">
                                    Selecciona una banda del listado.
                                </p>
                            </div>

                            <div
                                x-show="selectedBandHistory"
                                x-cloak
                                class="space-y-5"
                            >
                                {{-- PADRE --}}
                                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                    <table class="w-full border-collapse text-sm">
                                        <tbody>
                                            <tr class="bg-[#4f79bd] text-white">
                                                <th colspan="4" class="border border-slate-300 px-3 py-2 text-center text-sm font-bold uppercase">
                                                    Cambio de banda
                                                    <span
                                                        class="ml-2 font-semibold normal-case"
                                                        x-text="selectedBandHistory?.report_date ? ' - ' + formatDate(selectedBandHistory.report_date) : ''"
                                                    ></span>
                                                </th>
                                            </tr>

                                            <tr class="bg-white">
                                                <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Marca</th>
                                                <td class="border border-slate-300 bg-yellow-200 px-3 py-2 text-center font-semibold text-slate-900"
                                                    x-text="selectedBandHistory?.brand || '—'"></td>

                                                <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Rollos</th>
                                                <td class="border border-slate-300 bg-yellow-200 px-3 py-2 text-center font-semibold text-slate-900"
                                                    x-text="selectedBandHistory?.roll_count ?? '—'"></td>
                                            </tr>

                                            <tr class="bg-white">
                                                <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Ancho</th>
                                                <td class="border border-slate-300 bg-yellow-200 px-3 py-2 text-center font-semibold text-slate-900"
                                                    x-text="displayValue(selectedBandHistory?.width)"></td>

                                                <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Longitud</th>
                                                <td class="border border-slate-300 bg-yellow-200 px-3 py-2 text-center font-semibold text-slate-900"
                                                    x-text="displayValue(selectedBandHistory?.length)"></td>
                                            </tr>

                                            <tr class="bg-white">
                                                <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Observación</th>
                                                <td colspan="3"
                                                    class="border border-slate-300 px-3 py-2 text-center font-semibold"
                                                    :class="selectedBandHistory?.observation ? 'text-slate-900' : 'text-slate-500'"
                                                    x-text="selectedBandHistory?.observation || '—'"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                {{-- HIJOS --}}
                                <div class="space-y-4">
                                    <h4 class="text-sm font-semibold uppercase tracking-wider text-slate-500">
                                        Eventos hijos
                                    </h4>

                                    <div
                                        x-show="!selectedBandHistory?.children || selectedBandHistory.children.length === 0"
                                        x-cloak
                                        class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center text-sm font-semibold text-slate-600"
                                    >
                                        Esta banda no tiene vulcanizados ni cambios de tramo registrados.
                                    </div>

                                    <template x-for="child in (selectedBandHistory?.children || [])" :key="'band-child-' + child.id">
                                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                            <table class="w-full border-collapse text-sm">
                                                <tbody>
                                                    <tr class="bg-slate-100 text-slate-700">
                                                        <th colspan="4" class="border border-slate-200 px-3 py-2 text-left text-xs font-bold uppercase tracking-wider">
                                                            <span x-text="bandEventTypeLabel(child.type)"></span>
                                                            <span class="ml-2 normal-case font-semibold"
                                                                x-text="child.report_date ? formatDate(child.report_date) : '—'"></span>
                                                        </th>
                                                    </tr>

                                                    <template x-if="child.type === 'vulcanization'">
                                                        <tr class="bg-white">
                                                            <th class="border border-slate-200 px-3 py-2 font-bold text-slate-900">Temperatura</th>
                                                            <td class="border border-slate-200 bg-yellow-100 px-3 py-2 text-center font-semibold text-slate-900"
                                                                x-text="displayValue(child.temperature)"></td>

                                                            <th class="border border-slate-200 px-3 py-2 font-bold text-slate-900">Presión</th>
                                                            <td class="border border-slate-200 bg-yellow-100 px-3 py-2 text-center font-semibold text-slate-900"
                                                                x-text="displayValue(child.pressure)"></td>
                                                        </tr>
                                                    </template>

                                                    <template x-if="child.type === 'vulcanization'">
                                                        <tr class="bg-white">
                                                            <th class="border border-slate-200 px-3 py-2 font-bold text-slate-900">Tiempo</th>
                                                            <td colspan="3"
                                                                class="border border-slate-200 bg-yellow-100 px-3 py-2 text-center font-semibold text-slate-900"
                                                                x-text="displayValue(child.time)"></td>
                                                        </tr>
                                                    </template>

                                                    <template x-if="child.type === 'section_change'">
                                                        <tr class="bg-white">
                                                            <th class="border border-slate-200 px-3 py-2 font-bold text-slate-900">Longitud tramo</th>
                                                            <td class="border border-slate-200 bg-yellow-100 px-3 py-2 text-center font-semibold text-slate-900"
                                                                x-text="displayValue(child.section_length)"></td>

                                                            <th class="border border-slate-200 px-3 py-2 font-bold text-slate-900">Ancho tramo</th>
                                                            <td class="border border-slate-200 bg-yellow-100 px-3 py-2 text-center font-semibold text-slate-900"
                                                                x-text="displayValue(child.section_width)"></td>
                                                        </tr>
                                                    </template>

                                                    <tr class="bg-white">
                                                        <th class="border border-slate-200 px-3 py-2 font-bold text-slate-900">Observación</th>
                                                        <td colspan="3"
                                                            class="border border-slate-200 px-3 py-2 text-center font-semibold"
                                                            :class="child.observation ? 'text-slate-900' : 'text-slate-500'"
                                                            x-text="child.observation || '—'"></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- Inferior ancho completo --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
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

                    <div class="mt-4 flex justify-center gap-2">
                        <button
                            type="button"
                            @click="openDraftModal()"
                            :title="hasDraft() ? 'Continuar borrador' : 'Crear borrador'"
                            class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-[#d94d33] text-white transition hover:bg-[#b83f29]"
                        >
                            <i data-lucide="file-pen-line" class="h-5 w-5"></i>
                        </button>

                        <button
                            type="button"
                            @click="openHistoryModal()"
                            :disabled="loading"
                            title="Ver histórico"
                            class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-700 transition hover:bg-slate-100 disabled:pointer-events-none disabled:opacity-70"
                        >
                            <i data-lucide="history" class="h-5 w-5"></i>
                        </button>
                    </div>
                </div>

                <div
                    x-show="latestReport"
                    x-cloak
                    class="flex items-start gap-3"
                >
                    <div class="min-w-0 flex-1 overflow-hidden rounded-2xl border border-slate-200 bg-white">
                        <div class="bg-[#4f79bd] px-4 py-3 text-center text-sm font-bold uppercase tracking-wider text-white">
                            Medición de espesores y dureza
                            <span
                                class="ml-2 font-semibold normal-case"
                                x-text="latestReport?.report_date ? ' - ' + formatDate(latestReport.report_date) : ''"
                            ></span>
                        </div>

                        <div class="overflow-x-auto">
                            <div class="grid min-w-[1100px] grid-cols-[minmax(0,1.75fr)_minmax(320px,0.85fr)] border-t border-slate-200 bg-slate-50">
                                {{-- Tabla principal --}}
                                <div class="border-r border-slate-200">
                                    <table class="w-full border-collapse text-sm">
                                        <thead>
                                            <tr class="bg-slate-100 text-slate-700">
                                                <th class="border-b border-slate-200 px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">
                                                    Mediciones
                                                </th>
                                                <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">
                                                    Izquierdo
                                                </th>
                                                <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">
                                                    Centro
                                                </th>
                                                <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">
                                                    Derecho
                                                </th>
                                                <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">
                                                    Max
                                                </th>
                                                <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">
                                                    Min
                                                </th>
                                                <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">
                                                    % Suficiencia
                                                </th>
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
                                                    <td
                                                        class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700"
                                                        x-text="calculateSufficiency(
                                                            minValue([line.top_left, line.top_center, line.top_right]),
                                                            'top'
                                                        ) ?? '—'"
                                                    ></td>
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
                                                    <td
                                                        class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700"
                                                        x-text="calculateSufficiency(
                                                            minValue([line.bottom_left, line.bottom_center, line.bottom_right]),
                                                            'bottom'
                                                        ) ?? '—'"
                                                    ></td>
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
                                                <th class="border-b border-slate-200 px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">
                                                    Mediciones
                                                </th>
                                                <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">
                                                    Izquierdo
                                                </th>
                                                <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">
                                                    Centro
                                                </th>
                                                <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">
                                                    Derecho
                                                </th>
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

                    <div class="flex shrink-0 flex-col gap-2 pt-1">
                        <button
                            type="button"
                            @click="openDraftModal()"
                            :title="hasDraft() ? 'Continuar borrador' : 'Crear borrador'"
                            class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-[#d94d33] text-white transition hover:bg-[#b83f29]"
                        >
                            <i data-lucide="file-pen-line" class="h-5 w-5"></i>
                        </button>

                        <button
                            type="button"
                            @click="openHistoryModal()"
                            :disabled="loading"
                            title="Ver histórico"
                            class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-700 transition hover:bg-slate-100 disabled:pointer-events-none disabled:opacity-70"
                        >
                            <i data-lucide="history" class="h-5 w-5"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
            {{-- Modal borrador - Informe de estado de banda --}}
    <div
        x-cloak
        x-show="bandStateDraftModalOpen"
        x-transition.opacity
        class="fixed top-0 left-0 w-screen h-screen z-[9999] flex items-center justify-center bg-slate-900/60 px-4 py-6"
        @keydown.escape.window="closeBandStateDraftModal()"
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
                            Edita los 4 campos del informe del activo
                            <span class="font-semibold text-slate-700">{{ $element->name }}</span>.
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
                    x-cloak
                    class="mb-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                >
                    <div class="font-semibold">Hay errores en el informe de estado de banda.</div>
                    <ul class="mt-2 list-disc pl-5">
                        <template x-for="error in bandStateErrors" :key="error">
                            <li x-text="error"></li>
                        </template>
                    </ul>
                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                    <table class="w-full border-collapse text-sm">
                        <tbody>
                            <tr class="bg-[#4f79bd] text-white">
                                <th colspan="4" class="border border-slate-300 px-3 py-2 text-center text-sm font-bold uppercase">
                                    Informe de estado de bandas
                                </th>
                            </tr>

                            <tr class="bg-white">
                                <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                    TAG DE LA BANDA
                                </th>
                                <td class="border border-slate-300 px-3 py-2 text-center font-semibold text-slate-800">
                                    {{ $element->name }}
                                </td>
                                <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                    DESCRIPCIÓN
                                </th>
                                <td class="border border-slate-300 bg-yellow-200 px-3 py-2">
                                    <input
                                        type="text"
                                        x-model="bandStateDraft.description"
                                        class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                    >
                                </td>
                            </tr>

                            <tr class="bg-white">
                                <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                    ANCHO
                                </th>
                                <td class="border border-slate-300 bg-yellow-200 px-3 py-2">
                                    <input
                                        type="number"
                                        step="0.01"
                                        x-model="bandStateDraft.width"
                                        class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                    >
                                </td>
                                <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                    ÁREA
                                </th>
                                <td class="border border-slate-300 px-3 py-2 text-center font-semibold text-slate-800">
                                    {{ $area->name }}
                                </td>
                            </tr>

                            <tr class="bg-white">
                                <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                    CUBIERTA SUPERIOR
                                </th>
                                <td class="border border-slate-300 bg-yellow-200 px-3 py-2">
                                    <input
                                        type="number"
                                        step="0.01"
                                        x-model="bandStateDraft.top_cover"
                                        class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                    >
                                </td>
                                <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                    CUBIERTA INFERIOR
                                </th>
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
                                <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                    DUREZA
                                </th>
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
        class="fixed top-0 left-0 w-screen h-screen z-[9999] flex items-center justify-center bg-slate-900/60 px-4 py-6"
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
                            Consulta los reportes oficiales publicados del activo
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
                            class="overflow-hidden rounded-2xl border border-slate-200 bg-white"
                        >
                            <table class="w-full border-collapse text-sm">
                                <tbody>
                                    <tr class="bg-[#4f79bd] text-white">
                                        <th colspan="4" class="border border-slate-300 px-3 py-2 text-center text-sm font-bold uppercase">
                                            Informe de estado de bandas
                                            <span
                                                class="ml-2 font-semibold normal-case"
                                                x-text="selectedBandStateHistoryReport?.report_date ? ' - ' + formatDate(selectedBandStateHistoryReport.report_date) : ''"
                                            ></span>
                                        </th>
                                    </tr>

                                    <tr class="bg-white">
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                            TAG DE LA BANDA
                                        </th>
                                        <td class="border border-slate-300 px-3 py-2 text-center font-semibold text-slate-800">
                                            {{ $element->name }}
                                        </td>
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                            DESCRIPCIÓN
                                        </th>
                                        <td
                                            class="border border-slate-300 bg-yellow-200 px-3 py-2 text-center font-semibold text-slate-900"
                                            x-text="selectedBandStateHistoryReport?.description || '—'"
                                        ></td>
                                    </tr>

                                    <tr class="bg-white">
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                            ANCHO
                                        </th>
                                        <td
                                            class="border border-slate-300 bg-yellow-200 px-3 py-2 text-center font-semibold text-slate-900"
                                            x-text="displayValue(selectedBandStateHistoryReport?.width)"
                                        ></td>
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                            ÁREA
                                        </th>
                                        <td class="border border-slate-300 px-3 py-2 text-center font-semibold text-slate-800">
                                            {{ $area->name }}
                                        </td>
                                    </tr>

                                    <tr class="bg-white">
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                            CUBIERTA SUPERIOR
                                        </th>
                                        <td
                                            class="border border-slate-300 bg-yellow-200 px-3 py-2 text-center font-semibold text-slate-900"
                                            x-text="displayValue(selectedBandStateHistoryReport?.top_cover)"
                                        ></td>
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                            CUBIERTA INFERIOR
                                        </th>
                                        <td
                                            class="border border-slate-300 bg-yellow-200 px-3 py-2 text-center font-semibold text-slate-900"
                                            x-text="displayValue(selectedBandStateHistoryReport?.bottom_cover)"
                                        ></td>
                                    </tr>

                                    <tr class="bg-white">
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                            DUREZA
                                        </th>
                                        <td
                                            colspan="3"
                                            class="border border-slate-300 px-3 py-2 text-center font-semibold"
                                            :class="selectedBandStateHistoryReport?.calculated_hardness ? 'text-slate-900' : 'text-slate-500'"
                                            x-text="selectedBandStateHistoryReport?.calculated_hardness ? displayValue(selectedBandStateHistoryReport.calculated_hardness) : '—'"
                                        ></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>  
{{-- Wizard - Cambio de banda / Vulcanizado / Cambio de tramo --}}
<div
    x-cloak
    x-show="bandWizardOpen"
    x-transition.opacity
    class="fixed top-0 left-0 z-[9999] flex h-screen w-screen items-center justify-center bg-slate-900/60 px-4 py-6"
    @keydown.escape.window="closeBandWizard()"
>
    <div
        x-show="bandWizardOpen"
        x-transition
        class="flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl"
        @click.outside="closeBandWizard()"
    >
        <div class="border-b border-slate-200 px-6 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h3 class="text-xl font-semibold text-slate-900">
                        <span x-text="bandWizardTitle()"></span>
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Paso <span x-text="bandWizardStep"></span> de <span x-text="bandWizardTotalSteps()"></span>
                        · Activo <span class="font-semibold text-slate-700">{{ $element->name }}</span>
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        @click="saveBandDraft()"
                        :disabled="loading || !bandDraft"
                        class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 disabled:pointer-events-none disabled:opacity-70"
                    >
                        Guardar borrador
                    </button>

                    <button
                        type="button"
                        @click="closeBandWizard()"
                        class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </div>

        <div class="border-b border-slate-200 bg-slate-50 px-6 py-3">
            <div class="flex flex-wrap gap-2">
                <template x-for="step in Array.from({ length: bandWizardTotalSteps() }, (_, i) => i + 1)" :key="'band-step-' + step">
                    <div
                        class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                        :class="bandWizardStep === step
                            ? 'bg-[#d94d33] text-white'
                            : bandWizardStep > step
                                ? 'bg-emerald-100 text-emerald-700'
                                : 'bg-slate-200 text-slate-500'"
                    >
                        Paso <span class="ml-1" x-text="step"></span>
                    </div>
                </template>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6">
            <div
                x-show="bandErrors.length > 0"
                x-cloak
                class="mb-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
            >
                <div class="font-semibold">Hay errores en el borrador.</div>
                <ul class="mt-2 list-disc pl-5">
                    <template x-for="error in bandErrors" :key="error">
                        <li x-text="error"></li>
                    </template>
                </ul>
            </div>

            {{-- PASO 1: tipo --}}
            <div x-show="bandWizardStep === 1" x-cloak class="space-y-6">
                <div class="rounded-2xl border border-slate-200 bg-white p-6">
                    <h4 class="text-base font-semibold text-slate-900">Selecciona el tipo de registro</h4>
                    <p class="mt-1 text-sm text-slate-500">
                        Este flujo cambiará los pasos siguientes.
                    </p>

                    <div class="mt-5 grid gap-4 md:grid-cols-3">
                        <button
                            type="button"
                            @click="changeBandWizardType('band')"
                            class="rounded-2xl border p-5 text-left transition"
                            :class="bandType === 'band' ? 'border-[#d94d33] bg-[#d94d33]/5' : 'border-slate-200 hover:bg-slate-50'"
                        >
                            <div class="text-sm font-semibold text-slate-900">Banda nueva</div>
                            <div class="mt-1 text-xs text-slate-500">Crea el registro padre.</div>
                        </button>

                        <button
                            type="button"
                            @click="changeBandWizardType('vulcanization')"
                            class="rounded-2xl border p-5 text-left transition"
                            :class="bandType === 'vulcanization' ? 'border-[#d94d33] bg-[#d94d33]/5' : 'border-slate-200 hover:bg-slate-50'"
                        >
                            <div class="text-sm font-semibold text-slate-900">Vulcanizado</div>
                            <div class="mt-1 text-xs text-slate-500">Registro hijo de una banda.</div>
                        </button>

                        <button
                            type="button"
                            @click="changeBandWizardType('section_change')"
                            class="rounded-2xl border p-5 text-left transition"
                            :class="bandType === 'section_change' ? 'border-[#d94d33] bg-[#d94d33]/5' : 'border-slate-200 hover:bg-slate-50'"
                        >
                            <div class="text-sm font-semibold text-slate-900">Cambio de tramo</div>
                            <div class="mt-1 text-xs text-slate-500">Registro hijo con datos de tramo.</div>
                        </button>
                    </div>
                </div>
            </div>

            {{-- PASO 2: banda asociada --}}
            <div x-show="bandWizardStep === 2" x-cloak class="space-y-6">
                <template x-if="bandType === 'band'">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6 text-sm text-slate-600">
                        Estás creando una banda nueva. Este registro será el padre.
                    </div>
                </template>

                <template x-if="bandType !== 'band'">
                    <div class="rounded-2xl border border-slate-200 bg-white p-6">
                        <h4 class="text-base font-semibold text-slate-900">Banda asociada</h4>
                        <p class="mt-1 text-sm text-slate-500">
                            Por defecto se selecciona la banda activa, pero puedes cambiarla manualmente.
                        </p>

                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                                    Banda activa
                                </div>

                                <div class="mt-2 text-sm font-semibold text-slate-800" x-text="bandActiveBandLabel()"></div>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400">
                                    Seleccionar banda
                                </label>

                                <select
                                    x-model="bandDraft.parent_id"
                                    class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                >
                                    <option value="">Seleccionar banda</option>

                                    <template x-for="band in bandEventBands" :key="'band-option-' + band.id">
                                        <option :value="band.id" x-text="bandOptionLabel(band)"></option>
                                    </template>
                                </select>

                                <p class="mt-2 text-xs text-slate-500">
                                    Si no cambias nada, se usará la banda activa.
                                </p>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- PASO 3: datos técnicos --}}
            <div x-show="bandWizardStep === 3" x-cloak class="space-y-6">
                <template x-if="bandType === 'band'">
                    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)]">
                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                            <table class="w-full border-collapse text-sm">
                                <tbody>
                                    <tr class="bg-[#4f79bd] text-white">
                                        <th colspan="2" class="border border-slate-300 px-3 py-2 text-center text-sm font-bold uppercase">
                                            Referencia de la banda
                                        </th>
                                    </tr>
                                    <tr>
                                        <th class="w-[45%] border border-slate-300 px-3 py-2 font-bold text-slate-900">Marca</th>
                                        <td class="border border-slate-300 bg-yellow-200 px-3 py-2">
                                            <input type="text" x-model="bandDraft.brand" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Ancho</th>
                                        <td class="border border-slate-300 bg-yellow-200 px-3 py-2">
                                            <input type="number" step="0.01" x-model="bandDraft.width" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Longitud</th>
                                        <td class="border border-slate-300 bg-yellow-200 px-3 py-2">
                                            <input type="number" step="0.01" x-model="bandDraft.length" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Cantidad de rollos</th>
                                        <td class="border border-slate-300 bg-yellow-200 px-3 py-2">
                                            <input type="number" min="1" x-model="bandDraft.roll_count" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                            <table class="w-full border-collapse text-sm">
                                <tbody>
                                    <tr class="bg-[#4f79bd] text-white">
                                        <th colspan="2" class="border border-slate-300 px-3 py-2 text-center text-sm font-bold uppercase">
                                            Parámetros de vulcanizado
                                        </th>
                                    </tr>
                                    <tr>
                                        <th class="w-[45%] border border-slate-300 px-3 py-2 font-bold text-slate-900">Temperatura</th>
                                        <td class="border border-slate-300 bg-yellow-200 px-3 py-2">
                                            <input type="number" step="0.01" x-model="bandDraft.temperature" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Presión</th>
                                        <td class="border border-slate-300 bg-yellow-200 px-3 py-2">
                                            <input type="number" step="0.01" x-model="bandDraft.pressure" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Tiempo</th>
                                        <td class="border border-slate-300 bg-yellow-200 px-3 py-2">
                                            <input type="number" step="0.01" x-model="bandDraft.time" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none">
                                        </td>
                                    </tr>
                                                                    <tr>
                                    <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                        Cooling time
                                    </th>
                                    <td class="border border-slate-300 bg-yellow-200 px-3 py-2">
                                        <input
                                            type="number"
                                            step="0.01"
                                            x-model="bandDraft.cooling_time"
                                            class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                        >
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>

                <template x-if="bandType === 'vulcanization'">
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                        <table class="w-full border-collapse text-sm">
                            <tbody>
                                <tr class="bg-[#4f79bd] text-white">
                                    <th colspan="2" class="border border-slate-300 px-3 py-2 text-center text-sm font-bold uppercase">
                                        Parámetros de vulcanizado
                                    </th>
                                </tr>
                                <tr>
                                    <th class="w-[35%] border border-slate-300 px-3 py-2 font-bold text-slate-900">Temperatura</th>
                                    <td class="border border-slate-300 bg-yellow-200 px-3 py-2">
                                        <input type="number" step="0.01" x-model="bandDraft.temperature" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none">
                                    </td>
                                </tr>
                                <tr>
                                    <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Presión</th>
                                    <td class="border border-slate-300 bg-yellow-200 px-3 py-2">
                                        <input type="number" step="0.01" x-model="bandDraft.pressure" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none">
                                    </td>
                                </tr>
                                <tr>
                                    <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Tiempo</th>
                                    <td class="border border-slate-300 bg-yellow-200 px-3 py-2">
                                        <input type="number" step="0.01" x-model="bandDraft.time" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none">
                                    </td>
                                </tr>
                                <tr>
                                    <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                        Cooling time
                                    </th>
                                    <td class="border border-slate-300 bg-yellow-200 px-3 py-2">
                                        <input
                                            type="number"
                                            step="0.01"
                                            x-model="bandDraft.cooling_time"
                                            class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                        >
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>

                <template x-if="bandType === 'section_change'">
                    <div class="grid gap-6 xl:grid-cols-2">
                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                            <table class="w-full border-collapse text-sm">
                                <tbody>
                                    <tr class="bg-[#4f79bd] text-white">
                                        <th colspan="2" class="border border-slate-300 px-3 py-2 text-center text-sm font-bold uppercase">
                                            Parámetros de vulcanizado
                                        </th>
                                    </tr>
                                    <tr>
                                        <th class="w-[45%] border border-slate-300 px-3 py-2 font-bold text-slate-900">Temperatura</th>
                                        <td class="border border-slate-300 bg-yellow-200 px-3 py-2">
                                            <input type="number" step="0.01" x-model="bandDraft.temperature" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Presión</th>
                                        <td class="border border-slate-300 bg-yellow-200 px-3 py-2">
                                            <input type="number" step="0.01" x-model="bandDraft.pressure" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Tiempo</th>
                                        <td class="border border-slate-300 bg-yellow-200 px-3 py-2">
                                            <input type="number" step="0.01" x-model="bandDraft.time" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none">
                                        </td>
                                    </tr>
                                                                    <tr>
                                    <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                        Cooling time
                                    </th>
                                    <td class="border border-slate-300 bg-yellow-200 px-3 py-2">
                                        <input
                                            type="number"
                                            step="0.01"
                                            x-model="bandDraft.cooling_time"
                                            class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                        >
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                            <table class="w-full border-collapse text-sm">
                                <tbody>
                                    <tr class="bg-[#4f79bd] text-white">
                                        <th colspan="2" class="border border-slate-300 px-3 py-2 text-center text-sm font-bold uppercase">
                                            Cambio de tramo de banda
                                        </th>
                                    </tr>
                                    <tr>
                                        <th class="w-[45%] border border-slate-300 px-3 py-2 font-bold text-slate-900">Longitud</th>
                                        <td class="border border-slate-300 bg-yellow-200 px-3 py-2">
                                            <input type="number" step="0.01" x-model="bandDraft.section_length" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Ancho</th>
                                        <td class="border border-slate-300 bg-yellow-200 px-3 py-2">
                                            <input type="number" step="0.01" x-model="bandDraft.section_width" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
            </div>

            {{-- PASO 4: observación y fecha --}}
            <div x-show="bandWizardStep === 4" x-cloak class="grid gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(280px,0.7fr)]">
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                    <table class="w-full border-collapse text-sm">
                        <tbody>
                            <tr class="bg-[#4f79bd] text-white">
                                <th class="border border-slate-300 px-3 py-2 text-center text-sm font-bold uppercase">
                                    Observación
                                </th>
                            </tr>
                            <tr>
                                <td class="border border-slate-300 bg-yellow-200 px-3 py-2">
                                    <textarea
                                        x-model="bandDraft.observation"
                                        rows="10"
                                        class="w-full resize-none bg-transparent text-sm font-semibold text-slate-900 outline-none"
                                    ></textarea>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                    <table class="w-full border-collapse text-sm">
                        <tbody>
                            <tr class="bg-[#4f79bd] text-white">
                                <th class="border border-slate-300 px-3 py-2 text-center text-sm font-bold uppercase">
                                    Fecha del reporte
                                </th>
                            </tr>
                            <tr>
                                <td class="border border-slate-300 bg-yellow-200 px-3 py-4">
                                    <input
                                        type="date"
                                        x-model="bandDraft.report_date"
                                        class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                    >
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- PASO 5: resumen --}}
            <div x-show="bandWizardStep === 5" x-cloak class="space-y-6">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6">
                    <h4 class="text-base font-semibold text-slate-900">Resumen</h4>
                    <p class="mt-1 text-sm text-slate-500">
                        Revisa la información antes de publicar.
                    </p>

                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">Tipo</div>
                            <div class="mt-1 text-sm font-semibold text-slate-800" x-text="bandEventTypeLabel(bandType)"></div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">Fecha</div>
                            <div class="mt-1 text-sm font-semibold text-slate-800" x-text="bandDraft?.report_date || '—'"></div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">Marca</div>
                            <div class="mt-1 text-sm font-semibold text-slate-800" x-text="bandDraft?.brand || '—'"></div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">Observación</div>
                            <div class="mt-1 text-sm font-semibold text-slate-800" x-text="bandDraft?.observation || '—'"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="border-t border-slate-200 px-6 py-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <button
                    type="button"
                    @click="prevBandStep()"
                    :disabled="bandWizardStep === 1"
                    class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 disabled:pointer-events-none disabled:opacity-50"
                >
                    Anterior
                </button>

                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        x-show="bandWizardStep < bandWizardTotalSteps()"
                        @click="nextBandStep()"
                        class="rounded-xl bg-[#d94d33] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                    >
                        Siguiente
                    </button>

                    <button
                        type="button"
                        x-show="bandWizardStep === bandWizardTotalSteps()"
                        @click="publishBandDraft()"
                        :disabled="loading || !bandDraft"
                        class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:pointer-events-none disabled:opacity-70"
                    >
                        Publicar reporte
                    </button>
                </div>
            </div>
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
        class="fixed top-0 left-0 w-screen h-screen z-[9999] flex items-center justify-center bg-slate-900/60 px-4 py-6"
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
                            Consulta los reportes oficiales publicados del activo
                            <span class="font-semibold text-slate-700">{{ $element->name }}</span>.
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
                                <p class="text-sm font-semibold text-slate-700">
                                    No hay reportes históricos aún.
                                </p>
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

                        <div
                            x-show="!historyLoading && selectedHistoryReport"
                            x-cloak
                            class="flex items-start gap-3"
                        >
                            <div class="min-w-0 flex-1 overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                <div class="bg-[#4f79bd] px-4 py-3 text-center text-sm font-bold uppercase tracking-wider text-white">
                                    Medición de espesores y dureza
                                    <span
                                        class="ml-2 font-semibold normal-case"
                                        x-text="selectedHistoryReport?.report_date ? ' - ' + formatDate(selectedHistoryReport.report_date) : ''"
                                    ></span>
                                </div>

                                <div class="overflow-x-auto">
                                    <div class="grid min-w-[1100px] grid-cols-[minmax(0,1.75fr)_minmax(320px,0.85fr)] border-t border-slate-200 bg-slate-50">
                                        {{-- Tabla principal --}}
                                        <div class="border-r border-slate-200">
                                            <table class="w-full border-collapse text-sm">
                                                <thead>
                                                    <tr class="bg-slate-100 text-slate-700">
                                                        <th class="border-b border-slate-200 px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">
                                                            Mediciones
                                                        </th>
                                                        <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">
                                                            Izquierdo
                                                        </th>
                                                        <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">
                                                            Centro
                                                        </th>
                                                        <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">
                                                            Derecho
                                                        </th>
                                                        <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">
                                                            Max
                                                        </th>
                                                        <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">
                                                            Min
                                                        </th>
                                                        <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">
                                                            % Suficiencia
                                                        </th>
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
                                                            <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700">
                                                                —
                                                            </td>
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
                                                            <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700">
                                                                —
                                                            </td>
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
                                                        <th class="border-b border-slate-200 px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">
                                                            Mediciones
                                                        </th>
                                                        <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">
                                                            Izquierdo
                                                        </th>
                                                        <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">
                                                            Centro
                                                        </th>
                                                        <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">
                                                            Derecho
                                                        </th>
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

                bandDraft: null,
                bandType: 'band',
                bandWizardOpen: false,
                bandWizardStep: 1,
                bandErrors: [],
                bandEventLatestReport: config.initialBandEventLatestReport ?? null,
                bandEventActiveBand: config.initialBandEventActiveBand ?? null,
                bandEventBands: Array.isArray(config.initialBandEventBands) ? config.initialBandEventBands : [],
                bandActiveParentId: config.initialBandEventActiveBand?.id ?? null,
                bandHistoryModalOpen: false,
                bandHistoricalTree: Array.isArray(config.initialBandEventHistoricalTree) ? config.initialBandEventHistoricalTree : [],
                selectedBandHistory: null,

                draft: config.initialDraft,
                latestReport: config.initialLatestReport,
                historicalReports: Array.isArray(config.initialHistoricalReports)
                    ? config.initialHistoricalReports
                    : [],
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
                bandStateHistoricalReports: Array.isArray(config.initialBandStateHistoricalReports)
                    ? config.initialBandStateHistoricalReports
                    : [],
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

                    this.$nextTick(() => {
                        if (window.lucide) {
                            window.lucide.createIcons();
                        }
                    });
                },

                hasDraft() {
                    return !!(this.draft && Array.isArray(this.draft.lines));
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

                    if (!year || !month || !day) {
                        return value;
                    }

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

                calculateSufficiency(minValue, type) {
                    if (!this.latestBandStateReport) return null;

                    const base =
                        type === 'top'
                            ? Number(this.latestBandStateReport.top_cover)
                            : Number(this.latestBandStateReport.bottom_cover);

                    const min = Number(minValue);

                    if (!base || Number.isNaN(base) || base === 0) return null;
                    if (Number.isNaN(min)) return null;

                    return ((min / base) * 100).toFixed(0) + '%';
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

                    this.$nextTick(() => {
                        if (window.lucide) {
                            window.lucide.createIcons();
                        }
                    });
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

                    this.$nextTick(() => {
                        if (window.lucide) {
                            window.lucide.createIcons();
                        }
                    });
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

                        this.bandStateDraft = data.draft ?? {
                            id: null,
                            element_id: this.elementId,
                            description: '',
                            width: '',
                            top_cover: '',
                            bottom_cover: '',
                        };

                        this.bandStateDraftModalOpen = true;

                        this.$nextTick(() => {
                            if (window.lucide) {
                                window.lucide.createIcons();
                            }
                        });

                        showCrudToast(
                            data.message || 'Borrador del informe de estado de banda creado correctamente.',
                            'success'
                        );
                    } catch (error) {
                        showCrudToast(
                            error.message || 'Ocurrió un error al crear el borrador del informe de estado de banda.',
                            'error'
                        );
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
                            this.bandStateErrors = Array.isArray(data.errors)
                                ? data.errors
                                : Object.values(data.errors || {}).flat();

                            if (showToast) {
                                showCrudToast('Corrige los errores del informe de estado de banda.', 'error');
                            }

                            return false;
                        }

                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'No fue posible guardar el borrador del informe de estado de banda.');
                        }

                        this.bandStateDraft = data.draft ?? this.bandStateDraft;

                        if (showToast) {
                            showCrudToast(
                                data.message || 'Borrador del informe de estado de banda guardado correctamente.',
                                'success'
                            );
                        }

                        return true;
                    } catch (error) {
                        if (showToast) {
                            showCrudToast(
                                error.message || 'Ocurrió un error al guardar el borrador del informe de estado de banda.',
                                'error'
                            );
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
                        showCrudToast(
                            'No fue posible sincronizar el borrador del informe de estado de banda antes de publicar.',
                            'error'
                        );
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

                            showCrudToast(
                                data.message || 'No se pudo publicar el informe de estado de banda.',
                                'error'
                            );

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

                        showCrudToast(
                            data.message || 'Informe de estado de banda publicado correctamente.',
                            'success'
                        );
                    } catch (error) {
                        showCrudToast(
                            error.message || 'Ocurrió un error al publicar el informe de estado de banda.',
                            'error'
                        );
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
                            throw new Error(
                                data.message || 'No fue posible cargar el histórico del informe de estado de banda.'
                            );
                        }

                        this.bandStateHistoricalReports = Array.isArray(data.reports) ? data.reports : [];
                    } catch (error) {
                        showCrudToast(
                            error.message || 'Ocurrió un error al cargar el histórico del informe de estado de banda.',
                            'error'
                        );
                    } finally {
                        this.bandStateHistoryLoading = false;
                    }
                },

                async selectBandStateHistoricalReport(reportId) {
                    if (!reportId) return;

                    this.bandStateHistoryLoading = true;

                    try {
                        const response = await fetch(
                            this.routes.bandStateHistoryShowTemplate.replace('__REPORT__', reportId),
                            {
                                method: 'GET',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            }
                        );

                        const data = await response.json();

                        if (!response.ok || !data.success) {
                            throw new Error(
                                data.message || 'No fue posible cargar el reporte histórico del informe de estado de banda.'
                            );
                        }

                        this.selectedBandStateHistoryReport = data.report;
                    } catch (error) {
                        showCrudToast(
                            error.message || 'Ocurrió un error al cargar el detalle histórico del informe de estado de banda.',
                            'error'
                        );
                    } finally {
                        this.bandStateHistoryLoading = false;
                    }
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

                    this.$nextTick(() => {
                        if (window.lucide) {
                            window.lucide.createIcons();
                        }
                    });
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
                            this.errors = Array.isArray(data.errors)
                                ? data.errors
                                : Object.values(data.errors || {}).flat();

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
                        const response = await fetch(
                            this.routes.historyShowTemplate.replace('__REPORT__', reportId),
                            {
                                method: 'GET',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            }
                        );

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

                        showCrudToast(
                            data.message || 'Se agregó una nueva cubierta al borrador.',
                            'success'
                        );
                    } catch (error) {
                        showCrudToast(
                            error.message || 'Ocurrió un error al agregar una cubierta.',
                            'error'
                        );
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
                        showCrudToast(
                            'No fue posible sincronizar el borrador antes de eliminar la cubierta.',
                            'error'
                        );
                        return;
                    }

                    this.loading = true;
                    this.errors = [];

                    try {
                        const response = await fetch(
                            this.routes.removeCoverTemplate.replace('__COVER__', coverNumber),
                            {
                                method: 'DELETE',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                },
                            }
                        );

                        const data = await response.json();

                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'No fue posible eliminar la cubierta.');
                        }

                        this.draft = data.draft;

                        this.$nextTick(() => {
                            if (window.lucide) {
                                window.lucide.createIcons();
                            }
                        });

                        showCrudToast(
                            data.message || 'Cubierta eliminada correctamente.',
                            'success'
                        );
                    } catch (error) {
                        showCrudToast(
                            error.message || 'Ocurrió un error al eliminar la cubierta.',
                            'error'
                        );
                    } finally {
                        this.loading = false;
                    }
                },


                // ======================
                // BAND EVENTS (WIZARD)
                // ======================

                bandWizardTitle() {
                    if (this.bandType === 'band') return 'Asistente - Cambio de banda';
                    if (this.bandType === 'vulcanization') return 'Asistente - Vulcanizado';
                    return 'Asistente - Cambio de tramo de banda';
                },

                bandWizardTotalSteps() {
                    return 5;
                },

                bandEventTypeLabel(type) {
                    if (type === 'band') return 'Banda nueva';
                    if (type === 'vulcanization') return 'Vulcanizado';
                    if (type === 'section_change') return 'Cambio de tramo';
                    return '—';
                },

                bandParentLabel() {
                    if (this.bandType === 'band') return 'No aplica';
                    return this.bandDraft?.parent_id ? `Banda #${this.bandDraft.parent_id}` : this.bandActiveBandLabel();
                },

                bandActiveBandLabel() {
                    if (!this.bandEventActiveBand) return 'No existe una banda activa';
                    return this.bandOptionLabel(this.bandEventActiveBand);
                },

                bandOptionLabel(band) {
                    if (!band) return '—';

                    const date = band.report_date ? this.formatDate(band.report_date) : 'Sin fecha';
                    const brand = band.brand || 'Sin marca';
                    const rolls = band.roll_count ?? '—';

                    return `${date} · ${brand} · Rollos: ${rolls}`;
                },
                openBandHistoryModal() {
                    this.bandHistoryModalOpen = true;

                    if (!this.selectedBandHistory && this.bandHistoricalTree.length > 0) {
                        this.selectedBandHistory = this.bandHistoricalTree[0];
                    }

                    this.$nextTick(() => {
                        if (window.lucide) {
                            window.lucide.createIcons();
                        }
                    });
                },

                closeBandHistoryModal() {
                    this.bandHistoryModalOpen = false;
                },

                selectBandHistory(band) {
                    this.selectedBandHistory = band;
                },

                async createBandDraft(type = 'band') {
                    this.loading = true;
                    this.bandType = type;
                    this.bandErrors = [];

                    try {
                        const res = await fetch(this.routes.bandCreate, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            },
                            body: JSON.stringify({ type })
                        });

                        const data = await res.json();

                        if (!res.ok || !data.success) {
                            throw new Error(data.message || 'Error creando borrador');
                        }

                        this.bandDraft = {
                            ...data.draft,
                            report_date: data.draft?.report_date || config.today,
                        };

                        if (type !== 'band' && !this.bandDraft.parent_id && this.bandEventActiveBand?.id) {
                            this.bandDraft.parent_id = this.bandEventActiveBand.id;
                        }

                        if (!this.bandDraft.report_date) {
                            this.bandDraft.report_date = config.today;
                        }

                        return true;
                    } catch (error) {
                        showCrudToast(error.message || 'Error creando borrador', 'error');
                        return false;
                    } finally {
                        this.loading = false;
                    }
                },

                async openBandWizard(type = 'band') {
                    this.bandWizardStep = 1;
                    this.bandErrors = [];

                    const ok = await this.createBandDraft(type);

                    if (!ok || !this.bandDraft) return;

                    this.bandWizardOpen = true;

                    this.$nextTick(() => {
                        if (window.lucide) {
                            window.lucide.createIcons();
                        }
                    });
                },

                closeBandWizard() {
                    this.bandWizardOpen = false;
                    this.bandWizardStep = 1;
                    this.bandErrors = [];
                },

                async changeBandWizardType(type) {
                    if (this.bandType === type) return;
                    await this.createBandDraft(type);
                },

                nextBandStep() {
                    if (this.bandWizardStep < this.bandWizardTotalSteps()) {
                        this.bandWizardStep++;
                    }
                },

                prevBandStep() {
                    if (this.bandWizardStep > 1) {
                        this.bandWizardStep--;
                    }
                },

                async saveBandDraft(showToast = true) {
                    if (!this.bandDraft) return false;

                    this.loading = true;
                    this.bandErrors = [];

                    try {
                        const res = await fetch(this.routes.bandUpdate, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            },
                            body: JSON.stringify({
                                ...this.bandDraft,
                                type: this.bandType
                            })
                        });

                        const data = await res.json();

                        if (res.status === 422) {
                            this.bandErrors = Array.isArray(data.errors)
                                ? data.errors
                                : Object.values(data.errors || {}).flat();

                            if (showToast) {
                                showCrudToast(data.message || 'Corrige los errores del borrador.', 'error');
                            }

                            return false;
                        }

                        if (!res.ok || !data.success) {
                            throw new Error(data.message || 'Error guardando');
                        }

                        this.bandDraft = data.draft;

                        if (showToast) {
                            showCrudToast(data.message || 'Borrador guardado correctamente.', 'success');
                        }

                        return true;
                    } catch (error) {
                        if (showToast) {
                            showCrudToast(error.message || 'Error guardando', 'error');
                        }
                        return false;
                    } finally {
                        this.loading = false;
                    }
                },

                async publishBandDraft() {
                    if (!this.bandDraft) {
                        showCrudToast('No existe borrador para publicar.', 'error');
                        return;
                    }

                    const saved = await this.saveBandDraft(false);
                    if (!saved) return;

                    this.loading = true;
                    this.bandErrors = [];

                    try {
                        const res = await fetch(this.routes.bandPublish, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            },
                            body: JSON.stringify({
                                type: this.bandType,
                                report_date: this.bandDraft.report_date,
                            })
                        });

                        const data = await res.json();

                        if (res.status === 422) {
                            this.bandErrors = Array.isArray(data.errors)
                                ? data.errors
                                : Object.values(data.errors || {}).flat();

                            showCrudToast(data.message || 'Corrige los errores del borrador.', 'error');
                            return;
                        }

                        if (!res.ok || !data.success) {
                            throw new Error(data.message || 'Error publicando');
                        }

                        this.bandDraft = null;
                        this.bandErrors = [];
                        this.bandWizardOpen = false;
                        this.bandWizardStep = 1;

                        showCrudToast(data.message || 'Publicado correctamente', 'success');
                        window.location.reload();
                    } catch (error) {
                        showCrudToast(error.message || 'Error publicando', 'error');
                    } finally {
                        this.loading = false;
                    }
                },
            }
        }
    </script>
@endsection