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
            creationEnabled: @js((bool) ($creationEnabled ?? false)),
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
                historyUpdateTemplate: @js(route('admin.system-modules.measurements.reports.update', [
                    'element' => $element->id,
                    'report' => '__REPORT__',
                ])),
                historyDeleteTemplate: @js(route('admin.system-modules.measurements.reports.delete', [
                    'element' => $element->id,
                    'report' => '__REPORT__',
                ])),
                bandCreate: @js(route('band-events.draft.create', $element->id)),
                bandUpdate: @js(route('band-events.draft.update', $element->id)),
                bandPublish: @js(route('band-events.draft.publish', $element->id)),

                bandReportUpdateTemplate: @js(route('band-events.reports.update', [
                    'element' => $element->id,
                    'event' => '__EVENT__',
                ])),
                bandReportDeleteTemplate: @js(route('band-events.reports.destroy', [
                    'element' => $element->id,
                    'event' => '__EVENT__',
                ])),

                bandStateCreate: @js(route('admin.system-modules.measurements.band-state-draft.create', $element->id)),
                bandStateUpdate: @js(route('admin.system-modules.measurements.band-state-draft.update', $element->id)),
                bandStatePublish: @js(route('admin.system-modules.measurements.band-state-draft.publish', $element->id)),
                bandStateHistoryIndex: @js(route('admin.system-modules.measurements.band-state-reports.index', $element->id)),
                bandStateHistoryShowTemplate: @js(route('admin.system-modules.measurements.band-state-reports.show', [
                    'element' => $element->id,
                    'report' => '__REPORT__',
                ])),
                bandStateHistoryUpdateTemplate: @js(route('admin.system-modules.measurements.band-state-reports.update', [
                    'element' => $element->id,
                    'report' => '__REPORT__',
                ])),
                bandStateHistoryDeleteTemplate: @js(route('admin.system-modules.measurements.band-state-reports.delete', [
                    'element' => $element->id,
                    'report' => '__REPORT__',
                ])),
            },

            today: @js(now()->format('Y-m-d')),
        })"
        class="space-y-8"
    >
        <div
            x-show="!canCreateRecords()"
            x-cloak
            class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800"
        >
            <div class="font-semibold">
                Modo consulta activo
            </div>
            <p class="mt-1">
                Puedes consultar reportes e históricos, pero no crear nuevos borradores ni publicar reportes.
            </p>
        </div>
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
                                            class="border border-slate-300 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900"
                                            x-text="latestBandStateReport?.description || '—'"
                                        ></td>
                                    </tr>

                                    <tr class="bg-white">
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                            ANCHO
                                        </th>
                                        <td
                                            class="border border-slate-300 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900"
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
                                            class="border border-slate-300 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900"
                                            x-text="displayValue(latestBandStateReport?.top_cover)"
                                        ></td>
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                            CUBIERTA INFERIOR
                                        </th>
                                        <td
                                            class="border border-slate-300 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900"
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
                                x-show="canCreateRecords()"
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

        {{-- Superior derecha - Cambio de banda --}}
        {{-- Superior derecha - Cambio de banda --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="p-6">
                <div
                    x-show="!latestBandChangeReport()"
                    x-cloak
                    class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center"
                >
                    <p class="text-sm font-semibold text-slate-700">
                        Aún no existe un reporte oficial de cambio de banda.
                    </p>

                    <p class="mt-2 text-sm text-slate-500">
                        Registra una banda nueva para iniciar el histórico técnico del activo.
                    </p>

                    <div class="mt-4 flex justify-center gap-2">
                        <button
                            type="button"
                            x-show="canCreateRecords()"
                            @click="openBandWizard('band')"
                            title="Nuevo cambio de banda"
                            class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-[#d94d33] text-white transition hover:bg-[#b83f29]"
                        >
                            <i data-lucide="file-pen-line" class="h-5 w-5"></i>
                        </button>

                        <button
                            type="button"
                            @click="openBandHistoryModal()"
                            title="Ver histórico"
                            class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-700 transition hover:bg-slate-100"
                        >
                            <i data-lucide="history" class="h-5 w-5"></i>
                        </button>
                    </div>
                </div>

                <div
                    x-show="latestBandChangeReport()"
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
                                            x-text="latestBandChangeReport()?.report_date ? ' - ' + formatDate(latestBandChangeReport().report_date) : ''"
                                        ></span>
                                    </th>
                                </tr>

                                <tr class="bg-white">
                                    <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                        MARCA
                                    </th>
                                    <td
                                        class="border border-slate-300 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900"
                                        x-text="latestBandChangeReport()?.brand || '—'"
                                    ></td>

                                    <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                        ROLLOS
                                    </th>
                                    <td
                                        class="border border-slate-300 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900"
                                        x-text="latestBandChangeReport()?.roll_count ?? '—'"
                                    ></td>
                                </tr>

                                <tr class="bg-white">
                                    <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                        ANCHO
                                    </th>
                                    <td
                                        class="border border-slate-300 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900"
                                        x-text="displayValue(latestBandChangeReport()?.width)"
                                    ></td>

                                    <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                        LONGITUD
                                    </th>
                                    <td
                                        class="border border-slate-300 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900"
                                        x-text="displayValue(latestBandChangeReport()?.length)"
                                    ></td>
                                </tr>
                                <tr class="bg-white">
                                    <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                        VULCANIZADOS
                                    </th>
                                    <td
                                        class="border border-slate-300 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900"
                                        x-text="latestBandChangeReport()?.children?.filter(child => child.type === 'vulcanization').length ?? 0"
                                    ></td>

                                    <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                        CAMBIOS DE TRAMO
                                    </th>
                                    <td
                                        class="border border-slate-300 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900"
                                        x-text="latestBandChangeReport()?.children?.filter(child => child.type === 'section_change').length ?? 0"
                                    ></td>
                                </tr>
                                <tr class="bg-white">
                                    <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                        OBSERVACIÓN
                                    </th>
                                    <td
                                        colspan="3"
                                        class="border border-slate-300 px-3 py-2 text-center font-semibold"
                                        :class="latestBandChangeReport()?.observation ? 'text-slate-900' : 'text-slate-500'"
                                        x-text="latestBandChangeReport()?.observation || '—'"
                                    ></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex shrink-0 flex-col gap-2 pt-1">
                        <button
                            type="button"
                            x-show="canCreateRecords()"
                            @click="openBandWizard('band')"
                            title="Nuevo cambio de banda"
                            class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-[#d94d33] text-white transition hover:bg-[#b83f29]"
                        >
                            <i data-lucide="file-pen-line" class="h-5 w-5"></i>
                        </button>

                        <button
                            type="button"
                            @click="openBandHistoryModal()"
                            title="Ver histórico"
                            class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-700 transition hover:bg-slate-100"
                        >
                            <i data-lucide="history" class="h-5 w-5"></i>
                        </button>

                        <button
                            type="button"
                            x-show="canCreateRecords()"
                            @click="openBandWizard('vulcanization')"
                            title="Registrar vulcanizado"
                            class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-700 transition hover:bg-slate-100"
                        >
                            <i data-lucide="wrench" class="h-5 w-5"></i>
                        </button>

                        <button
                            type="button"
                            x-show="canCreateRecords()"
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
                class="flex max-h-[92vh] w-full max-w-7xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl"
                @click.outside="if (!bandEditModalOpen) closeBandHistoryModal()"
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

                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                x-show="selectedBandHistory"
                                @click="openBandEditModal(selectedBandHistory)"
                                class="rounded-xl bg-[#d94d33] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                            >
                                Editar banda
                            </button>

                            <button
                                type="button"
                                x-show="selectedBandHistory"
                                @click="deleteBandHistoricalEvent(selectedBandHistory)"
                                class="rounded-xl border border-red-300 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-100"
                            >
                                Eliminar banda
                            </button>

                            <button
                                type="button"
                                @click="closeBandHistoryModal()"
                                class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                            >
                                Cerrar
                            </button>
                        </div>
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
                                {{-- PADRE --}}
                                <div class="space-y-4">
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
                                                    <td class="border border-slate-300 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900"
                                                        x-text="selectedBandHistory?.brand || '—'"></td>

                                                    <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Rollos</th>
                                                    <td class="border border-slate-300 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900"
                                                        x-text="selectedBandHistory?.roll_count ?? '—'"></td>
                                                </tr>

                                                <tr class="bg-white">
                                                    <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Ancho</th>
                                                    <td class="border border-slate-300 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900"
                                                        x-text="displayValue(selectedBandHistory?.width)"></td>

                                                    <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Longitud</th>
                                                    <td class="border border-slate-300 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900"
                                                        x-text="displayValue(selectedBandHistory?.length)"></td>
                                                </tr>

                                                <tr class="bg-white">
                                                    <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Espesor total</th>
                                                    <td class="border border-slate-300 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900"
                                                        x-text="displayValue(selectedBandHistory?.total_thickness)"></td>

                                                    <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Lonas</th>
                                                    <td class="border border-slate-300 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900"
                                                        x-text="displayValue(selectedBandHistory?.plies)"></td>
                                                </tr>

                                                <tr class="bg-white">
                                                    <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Cubierta superior</th>
                                                    <td class="border border-slate-300 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900"
                                                        x-text="displayValue(selectedBandHistory?.top_cover_thickness)"></td>

                                                    <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Cubierta inferior</th>
                                                    <td class="border border-slate-300 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900"
                                                        x-text="displayValue(selectedBandHistory?.bottom_cover_thickness)"></td>
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

                                    <div class="grid gap-4 xl:grid-cols-2">
                                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                            <table class="w-full border-collapse text-sm">
                                                <tbody>
                                                    <tr class="bg-[#4f79bd] text-white">
                                                        <th colspan="4" class="border border-slate-300 px-3 py-2 text-center text-sm font-bold uppercase">
                                                            Datos de vulcanizado
                                                        </th>
                                                    </tr>

                                                    <tr class="bg-white">
                                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Temperatura</th>
                                                        <td class="border border-slate-300 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900"
                                                            x-text="displayValue(selectedBandHistory?.temperature)"></td>

                                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Presión</th>
                                                        <td class="border border-slate-300 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900"
                                                            x-text="displayValue(selectedBandHistory?.pressure)"></td>
                                                    </tr>

                                                    <tr class="bg-white">
                                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Tiempo vulcanizado</th>
                                                        <td class="border border-slate-300 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900"
                                                            x-text="displayValue(selectedBandHistory?.time)"></td>

                                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Tiempo enfriamiento</th>
                                                        <td class="border border-slate-300 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900"
                                                            x-text="displayValue(selectedBandHistory?.cooling_time)"></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                            <table class="w-full border-collapse text-sm">
                                                <tbody>
                                                    <tr class="bg-[#4f79bd] text-white">
                                                        <th colspan="4" class="border border-slate-300 px-3 py-2 text-center text-sm font-bold uppercase">
                                                            Datos de entrega de equipo
                                                        </th>
                                                    </tr>

                                                    <tr class="bg-white">
                                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Corriente motor</th>
                                                        <td class="border border-slate-300 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900"
                                                            x-text="displayValue(selectedBandHistory?.motor_current)"></td>

                                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Alineación</th>
                                                        <td class="border border-slate-300 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900"
                                                            x-text="selectedBandHistory?.alignment || '—'"></td>
                                                    </tr>

                                                    <tr class="bg-white">
                                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Mat acumul</th>
                                                        <td class="border border-slate-300 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900"
                                                            x-text="selectedBandHistory?.material_accumulation || '—'"></td>

                                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Guardilña</th>
                                                        <td class="border border-slate-300 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900"
                                                            x-text="selectedBandHistory?.guard || '—'"></td>
                                                    </tr>

                                                    <tr class="bg-white">
                                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Rodillería</th>
                                                        <td colspan="3" class="border border-slate-300 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900"
                                                            x-text="selectedBandHistory?.idler_condition || '—'"></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
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
                                                        <th colspan="4" class="border border-slate-200 px-3 py-2">
                                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                                <div class="text-left text-xs font-bold uppercase tracking-wider">
                                                                    <span x-text="bandEventTypeLabel(child.type)"></span>
                                                                    <span
                                                                        class="ml-2 normal-case font-semibold"
                                                                        x-text="child.report_date ? formatDate(child.report_date) : '—'"
                                                                    ></span>
                                                                </div>

                                                                <div class="flex gap-2">
                                                                    <button
                                                                        type="button"
                                                                        @click.stop="openBandEditModal(child)"
                                                                        class="rounded-lg bg-[#d94d33] px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-[#b83f29]"
                                                                    >
                                                                        Editar
                                                                    </button>

                                                                    <button
                                                                        type="button"
                                                                        @click.stop="deleteBandHistoricalEvent(child)"
                                                                        class="rounded-lg border border-red-300 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100"
                                                                    >
                                                                        Eliminar
                                                                    </button>
                                                                </div>
                                                            </div>
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


{{-- Modal editar histórico - Cambio de banda / Vulcanizado / Cambio de tramo --}}
<div
    x-cloak
    x-show="bandEditModalOpen"
    x-transition.opacity
    class="fixed top-0 left-0 z-[10000] flex h-screen w-screen items-center justify-center bg-slate-900/60 px-4 py-6"
    @keydown.escape.window="closeBandEditModal()"
>
    <div
        x-show="bandEditModalOpen"
        x-transition
        class="flex max-h-[92vh] w-full max-w-7xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl"
        @click.outside="closeBandEditModal()"
        @click.stop
    >
        <div class="border-b border-slate-200 bg-white px-6 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-full bg-[#d94d33]/10 px-3 py-1 text-xs font-semibold text-[#d94d33]">
                            <span x-text="bandEventTypeLabel(bandEditForm?.type || 'band')"></span>
                        </span>

                        <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                            Edición oficial
                        </span>
                    </div>

                    <h3 class="mt-2 text-xl font-semibold text-slate-900">
                        Editar evento histórico
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Modifica el evento oficial seleccionado del activo
                        <span class="font-semibold text-slate-700">{{ $element->name }}</span>.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex items-center gap-2 rounded-xl border border-slate-300 bg-slate-50 px-3 py-2">
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">
                            Fecha
                        </label>

                        <input
                            type="date"
                            x-model="bandEditForm.report_date"
                            class="w-[145px] bg-transparent text-sm font-semibold text-slate-800 outline-none"
                        >
                    </div>

                    <button
                        type="button"
                        @click="updateBandHistoricalEvent()"
                        :disabled="loading || !bandEditForm"
                        class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:pointer-events-none disabled:opacity-70"
                    >
                        Guardar cambios
                    </button>

                    <button
                        type="button"
                        @click="closeBandEditModal()"
                        class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto bg-slate-50 px-6 py-5">
            <div
                x-show="bandEditErrors.length > 0"
                x-cloak
                class="mb-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
            >
                <div class="font-semibold">Hay errores en la edición.</div>
                <ul class="mt-2 list-disc pl-5">
                    <template x-for="error in bandEditErrors" :key="error">
                        <li x-text="error"></li>
                    </template>
                </ul>
            </div>

            <template x-if="bandEditForm">
                <div class="space-y-5">

                    {{-- Selector de banda padre para eventos hijos --}}
                    <template x-if="bandEditForm.type !== 'band'">
                        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <h4 class="text-sm font-semibold uppercase tracking-wider text-slate-500">
                                Banda asociada
                            </h4>

                            <div class="mt-4 grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)]">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                                        Banda actual
                                    </div>
                                    <div
                                        class="mt-2 text-sm font-semibold text-slate-800"
                                        x-text="bandOptionLabel(bandEventBands.find(band => String(band.id) === String(bandEditForm?.parent_id)) || null)"
                                    ></div>
                                </div>

                                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400">
                                        Seleccionar banda padre
                                    </label>

                                    <select
                                        x-model="bandEditForm.parent_id"
                                        class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                    >
                                        <option value="">Seleccionar banda</option>
                                        <template x-for="band in bandEventBands" :key="'edit-parent-option-' + band.id">
                                            <option :value="band.id" x-text="bandOptionLabel(band)"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Cambio de banda padre --}}
                    <template x-if="bandEditForm.type === 'band'">
                        <div class="grid gap-5 xl:grid-cols-3">
                            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                                <table class="w-full border-collapse text-sm">
                                    <tbody>
                                        <tr class="bg-[#4f79bd] text-white">
                                            <th colspan="2" class="border border-slate-300 px-3 py-2 text-center text-xs font-bold uppercase">
                                                Referencia de la banda
                                            </th>
                                        </tr>

                                        <tr>
                                            <th class="w-[45%] border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                                Marca
                                            </th>
                                            <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                <input
                                                    type="text"
                                                    x-model="bandEditForm.brand"
                                                    class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                                >
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                                Espesor total
                                            </th>
                                            <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    x-model="bandEditForm.total_thickness"
                                                    class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                                >
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                                Cubierta superior
                                            </th>
                                            <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    x-model="bandEditForm.top_cover_thickness"
                                                    class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                                >
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                                Cubierta inferior
                                            </th>
                                            <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    x-model="bandEditForm.bottom_cover_thickness"
                                                    class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                                >
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                                Lonas
                                            </th>
                                            <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                <input
                                                    type="number"
                                                    min="1"
                                                    x-model="bandEditForm.plies"
                                                    class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                                >
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                                Ancho
                                            </th>
                                            <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    x-model="bandEditForm.width"
                                                    class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                                >
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                                Longitud
                                            </th>
                                            <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    x-model="bandEditForm.length"
                                                    class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                                >
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                                Rollos
                                            </th>
                                            <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                <input
                                                    type="number"
                                                    min="1"
                                                    x-model="bandEditForm.roll_count"
                                                    class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                                >
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                                <table class="w-full border-collapse text-sm">
                                    <tbody>
                                        <tr class="bg-[#4f79bd] text-white">
                                            <th colspan="2" class="border border-slate-300 px-3 py-2 text-center text-xs font-bold uppercase">
                                                Vulcanizado
                                            </th>
                                        </tr>

                                        <tr>
                                            <th class="w-[45%] border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                                Temperatura
                                            </th>
                                            <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                <input type="number" step="0.01" x-model="bandEditForm.temperature" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none">
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                                Presión
                                            </th>
                                            <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                <input type="number" step="0.01" x-model="bandEditForm.pressure" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none">
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                                Tiempo vulcanizado
                                            </th>
                                            <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                <input type="number" step="0.01" x-model="bandEditForm.time" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none">
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                                Tiempo enfriamiento
                                            </th>
                                            <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                <input type="number" step="0.01" x-model="bandEditForm.cooling_time" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none">
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                                <table class="w-full border-collapse text-sm">
                                    <tbody>
                                        <tr class="bg-[#4f79bd] text-white">
                                            <th colspan="2" class="border border-slate-300 px-3 py-2 text-center text-xs font-bold uppercase">
                                                Entrega de equipo
                                            </th>
                                        </tr>

                                        <tr>
                                            <th class="w-[45%] border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                                Corriente motor
                                            </th>
                                            <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                <input type="number" step="0.01" x-model="bandEditForm.motor_current" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none">
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                                Alineación
                                            </th>
                                            <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                <input type="text" x-model="bandEditForm.alignment" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none">
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                                Mat acumul
                                            </th>
                                            <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                <input type="text" x-model="bandEditForm.material_accumulation" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none">
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                                Guardilña
                                            </th>
                                            <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                <input type="text" x-model="bandEditForm.guard" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none">
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                                Rodillería
                                            </th>
                                            <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                <input type="text" x-model="bandEditForm.idler_condition" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none">
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>
                                        {{-- Vulcanizado hijo --}}
                    <template x-if="bandEditForm.type === 'vulcanization'">
                        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                                <table class="w-full border-collapse text-sm">
                                    <tbody>
                                        <tr class="bg-[#4f79bd] text-white">
                                            <th colspan="2" class="border border-slate-300 px-3 py-2 text-center text-xs font-bold uppercase">
                                                Parámetros de vulcanizado
                                            </th>
                                        </tr>

                                        <tr>
                                            <th class="w-[42%] border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                                Temperatura
                                            </th>
                                            <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    x-model="bandEditForm.temperature"
                                                    class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                                >
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                                Presión
                                            </th>
                                            <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    x-model="bandEditForm.pressure"
                                                    class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                                >
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                                Tiempo vulcanizado
                                            </th>
                                            <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    x-model="bandEditForm.time"
                                                    class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                                >
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                                Tiempo enfriamiento
                                            </th>
                                            <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    x-model="bandEditForm.cooling_time"
                                                    class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                                >
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                                <table class="w-full border-collapse text-sm">
                                    <tbody>
                                        <tr class="bg-[#4f79bd] text-white">
                                            <th class="border border-slate-300 px-3 py-2 text-center text-xs font-bold uppercase">
                                                Observación
                                            </th>
                                        </tr>

                                        <tr>
                                            <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                <textarea
                                                    x-model="bandEditForm.observation"
                                                    rows="8"
                                                    class="w-full resize-none bg-transparent text-sm font-semibold text-slate-900 outline-none"
                                                ></textarea>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>

                    {{-- Cambio de tramo hijo --}}
                    <template x-if="bandEditForm.type === 'section_change'">
                        <div class="space-y-5">
                            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                <label class="flex items-center gap-3 text-sm font-semibold text-slate-800">
                                    <input
                                        type="checkbox"
                                        x-model="bandEditForm.same_reference"
                                        class="h-4 w-4 rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                                    >
                                    ¿La referencia es igual a la instalada?
                                </label>
                            </div>

                            <div class="grid gap-5 xl:grid-cols-3">
                                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                                    <table class="w-full border-collapse text-sm">
                                        <tbody>
                                            <tr class="bg-[#4f79bd] text-white">
                                                <th colspan="2" class="border border-slate-300 px-3 py-2 text-center text-xs font-bold uppercase">
                                                    Vulcanizado
                                                </th>
                                            </tr>

                                            <tr>
                                                <th class="w-[45%] border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                                    Temperatura
                                                </th>
                                                <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        x-model="bandEditForm.temperature"
                                                        class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                                    >
                                                </td>
                                            </tr>

                                            <tr>
                                                <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                                    Presión
                                                </th>
                                                <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        x-model="bandEditForm.pressure"
                                                        class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                                    >
                                                </td>
                                            </tr>

                                            <tr>
                                                <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                                    Tiempo vulcanizado
                                                </th>
                                                <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        x-model="bandEditForm.time"
                                                        class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                                    >
                                                </td>
                                            </tr>

                                            <tr>
                                                <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                                    Tiempo enfriamiento
                                                </th>
                                                <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        x-model="bandEditForm.cooling_time"
                                                        class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                                    >
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                                    <table class="w-full border-collapse text-sm">
                                        <tbody>
                                            <tr class="bg-[#4f79bd] text-white">
                                                <th colspan="2" class="border border-slate-300 px-3 py-2 text-center text-xs font-bold uppercase">
                                                    Entrega de equipo
                                                </th>
                                            </tr>

                                            <tr>
                                                <th class="w-[45%] border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                                    Corriente motor
                                                </th>
                                                <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        x-model="bandEditForm.motor_current"
                                                        class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                                    >
                                                </td>
                                            </tr>

                                            <tr>
                                                <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                                    Alineación
                                                </th>
                                                <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                    <input
                                                        type="text"
                                                        x-model="bandEditForm.alignment"
                                                        class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                                    >
                                                </td>
                                            </tr>

                                            <tr>
                                                <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                                    Mat acumul
                                                </th>
                                                <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                    <input
                                                        type="text"
                                                        x-model="bandEditForm.material_accumulation"
                                                        class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                                    >
                                                </td>
                                            </tr>

                                            <tr>
                                                <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                                    Guardilña
                                                </th>
                                                <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                    <input
                                                        type="text"
                                                        x-model="bandEditForm.guard"
                                                        class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                                    >
                                                </td>
                                            </tr>

                                            <tr>
                                                <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                                    Rodillería
                                                </th>
                                                <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                    <input
                                                        type="text"
                                                        x-model="bandEditForm.idler_condition"
                                                        class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                                    >
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                                    <table class="w-full border-collapse text-sm">
                                        <tbody>
                                            <tr class="bg-[#4f79bd] text-white">
                                                <th colspan="2" class="border border-slate-300 px-3 py-2 text-center text-xs font-bold uppercase">
                                                    Cambio de tramo
                                                </th>
                                            </tr>

                                            <tr>
                                                <th class="w-[45%] border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                                    Marca
                                                </th>
                                                <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                    <input
                                                        type="text"
                                                        x-model="bandEditForm.section_brand"
                                                        class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                                    >
                                                </td>
                                            </tr>

                                            <tr>
                                                <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                                    Espesor
                                                </th>
                                                <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        x-model="bandEditForm.section_thickness"
                                                        class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                                    >
                                                </td>
                                            </tr>

                                            <tr>
                                                <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                                    Lonas
                                                </th>
                                                <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                    <input
                                                        type="number"
                                                        min="1"
                                                        x-model="bandEditForm.section_plies"
                                                        class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                                    >
                                                </td>
                                            </tr>

                                            <tr>
                                                <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                                    Longitud
                                                </th>
                                                <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        x-model="bandEditForm.section_length"
                                                        class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                                    >
                                                </td>
                                            </tr>

                                            <tr>
                                                <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                                    Ancho
                                                </th>
                                                <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        x-model="bandEditForm.section_width"
                                                        class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                                    >
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                                <table class="w-full border-collapse text-sm">
                                    <tbody>
                                        <tr class="bg-[#4f79bd] text-white">
                                            <th class="border border-slate-300 px-3 py-2 text-center text-xs font-bold uppercase">
                                                Observación
                                            </th>
                                        </tr>

                                        <tr>
                                            <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                                <textarea
                                                    x-model="bandEditForm.observation"
                                                    rows="5"
                                                    class="w-full resize-none bg-transparent text-sm font-semibold text-slate-900 outline-none"
                                                ></textarea>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>

                    {{-- Observación para cambio de banda padre --}}
                    <template x-if="bandEditForm.type === 'band'">
                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                            <table class="w-full border-collapse text-sm">
                                <tbody>
                                    <tr class="bg-[#4f79bd] text-white">
                                        <th class="border border-slate-300 px-3 py-2 text-center text-xs font-bold uppercase">
                                            Observación
                                        </th>
                                    </tr>

                                    <tr>
                                        <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                            <textarea
                                                x-model="bandEditForm.observation"
                                                rows="5"
                                                class="w-full resize-none bg-transparent text-sm font-semibold text-slate-900 outline-none"
                                            ></textarea>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </div>
            </template>
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
                            x-show="canCreateRecords()"
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
                            x-show="canCreateRecords()"
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
                            @click="publishBandStateDraft()"
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
                                <td class="border border-slate-300 bg-slate-100 px-3 py-2">
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
                                <td class="border border-slate-300 bg-slate-100 px-3 py-2">
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
                                <td class="border border-slate-300 bg-slate-100 px-3 py-2">
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
                                <td class="border border-slate-300 bg-slate-100 px-3 py-2">
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
                            <tr class="bg-white">
                                <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                    FECHA REPORTE
                                </th>
                                <td colspan="3" class="border border-slate-300 bg-slate-100 px-3 py-2">
                                    <input
                                        type="date"
                                        x-model="bandStatePublishForm.report_date"
                                        class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                    >
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
            class="flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl"
            @click.outside="if (!bandStateEditModalOpen) closeBandStateHistoryModal()"
        >
            <div class="border-b border-slate-200 px-6 py-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h3 class="text-xl font-semibold text-slate-900">Histórico - Informe de estado de banda</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            <span class="font-semibold text-slate-700">{{ $element->name }}</span>.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            x-show="selectedBandStateHistoryReport"
                            @click="openBandStateEditModal()"
                            class="rounded-xl bg-[#d94d33] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                        >
                            Editar
                        </button>

                        <button
                            type="button"
                            x-show="selectedBandStateHistoryReport"
                            @click="deleteBandStateHistoricalReport()"
                            class="rounded-xl border border-red-300 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-100"
                        >
                            Eliminar
                        </button>

                        <button
                            type="button"
                            @click="closeBandStateHistoryModal()"
                            class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                        >
                            Cerrar
                        </button>
                    </div>
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
                            x-show="historyLoading && !selectedHistoryReport"
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
                            x-show="selectedBandStateHistoryReport"
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
                                            class="border border-slate-300 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900"
                                            x-text="selectedBandStateHistoryReport?.description || '—'"
                                        ></td>
                                    </tr>

                                    <tr class="bg-white">
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                            ANCHO
                                        </th>
                                        <td
                                            class="border border-slate-300 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900"
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
                                            class="border border-slate-300 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900"
                                            x-text="displayValue(selectedBandStateHistoryReport?.top_cover)"
                                        ></td>
                                        <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                            CUBIERTA INFERIOR
                                        </th>
                                        <td
                                            class="border border-slate-300 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900"
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

{{-- Modal editar histórico - Informe de estado de banda --}}
<div
    x-cloak
    x-show="bandStateEditModalOpen"
    x-transition.opacity
    class="fixed top-0 left-0 z-[10000] flex h-screen w-screen items-center justify-center bg-slate-900/60 px-4 py-6"
    @keydown.escape.window="closeBandStateEditModal()"
>
    <div
        x-show="bandStateEditModalOpen"
        x-transition
        class="flex w-full max-w-4xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl"
        @click.outside="closeBandStateEditModal()"
        @click.stop
    >
        <div class="border-b border-slate-200 px-6 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h3 class="text-xl font-semibold text-slate-900">
                        Editar reporte - Informe de estado de banda
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Modifica el reporte oficial seleccionado.
                    </p>
                </div>

                <button
                    type="button"
                    @click="closeBandStateEditModal()"
                    class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                >
                    Cerrar
                </button>
            </div>
        </div>

        <div class="p-6">
            <div
                x-show="bandStateEditErrors.length > 0"
                x-cloak
                class="mb-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
            >
                <div class="font-semibold">Hay errores en la edición.</div>
                <ul class="mt-2 list-disc pl-5">
                    <template x-for="error in bandStateEditErrors" :key="error">
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
                                FECHA REPORTE
                            </th>
                            <td colspan="3" class="border border-slate-300 bg-slate-100 px-3 py-2">
                                <input
                                    type="date"
                                    x-model="bandStateEditForm.report_date"
                                    class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                >
                            </td>
                        </tr>

                        <tr class="bg-white">
                            <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                DESCRIPCIÓN
                            </th>
                            <td colspan="3" class="border border-slate-300 bg-slate-100 px-3 py-2">
                                <input
                                    type="text"
                                    x-model="bandStateEditForm.description"
                                    class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                >
                            </td>
                        </tr>

                        <tr class="bg-white">
                            <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                ANCHO
                            </th>
                            <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                <input
                                    type="number"
                                    step="0.01"
                                    x-model="bandStateEditForm.width"
                                    class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                >
                            </td>

                            <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                CUBIERTA SUPERIOR
                            </th>
                            <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                <input
                                    type="number"
                                    step="0.01"
                                    x-model="bandStateEditForm.top_cover"
                                    class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                >
                            </td>
                        </tr>

                        <tr class="bg-white">
                            <th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">
                                CUBIERTA INFERIOR
                            </th>
                            <td colspan="3" class="border border-slate-300 bg-slate-100 px-3 py-2">
                                <input
                                    type="number"
                                    step="0.01"
                                    x-model="bandStateEditForm.bottom_cover"
                                    class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                >
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex flex-wrap justify-end gap-2">
                <button
                    type="button"
                    @click="closeBandStateEditModal()"
                    class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                >
                    Cancelar
                </button>

                <button
                    type="button"
                    @click="updateBandStateHistoricalReport()"
                    :disabled="loading"
                    class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:pointer-events-none disabled:opacity-70"
                >
                    Guardar cambios
                </button>
            </div>
        </div>
    </div>
</div>
{{-- Wizard - Cambio de banda / Vulcanizado / Cambio de tramo --}}
{{-- Nuevo wizard - Cambio de banda / Vulcanizado / Cambio de tramo --}}
{{-- Wizard compacto - Cambio de banda / Vulcanizado / Cambio de tramo --}}
<div
    x-cloak
    x-show="bandWizardOpen"
    x-transition.opacity
    class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/60 px-4 py-5"
    @keydown.escape.window="closeBandWizard()"
>
    <div
        x-show="bandWizardOpen"
        x-transition
        class="flex h-[90vh] w-full max-w-[1350px] flex-col overflow-hidden rounded-[28px] border border-slate-200 bg-slate-50 shadow-2xl"
        @click.outside="closeBandWizard()"
    >
        {{-- Header compacto --}}
        <div class="border-b border-slate-200 bg-white px-6 py-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-full bg-[#d94d33]/10 px-3 py-1 text-xs font-semibold text-[#d94d33]">
                            <span x-text="bandEventTypeLabel(bandType)"></span>
                        </span>

                        <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                            Paso <span class="mx-1" x-text="bandWizardStep"></span> de <span class="ml-1" x-text="bandWizardTotalSteps()"></span>
                        </span>

                        <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                            Activo: {{ $element->name }}
                        </span>
                    </div>

                    <h3 class="mt-2 text-xl font-semibold text-slate-900" x-text="bandWizardTitle()"></h3>
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

        {{-- Progreso compacto --}}
        <div class="border-b border-slate-200 bg-slate-900 px-6 py-3">
            <div class="grid gap-2 md:grid-cols-3">
                <template x-for="step in compactBandSteps()" :key="'compact-band-step-' + step.id">
                    <button
                        type="button"
                        @click="bandWizardStep = step.id"
                        class="rounded-xl px-3 py-2 text-left text-sm transition"
                        :class="bandWizardStep === step.id
                            ? 'bg-white text-slate-900'
                            : bandWizardStep > step.id
                                ? 'bg-emerald-500/20 text-white'
                                : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                    >
                        <div class="flex items-center gap-2">
                            <span
                                class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold"
                                :class="bandWizardStep === step.id
                                    ? 'bg-[#d94d33] text-white'
                                    : bandWizardStep > step.id
                                        ? 'bg-emerald-500 text-white'
                                        : 'bg-slate-700 text-slate-300'"
                                x-text="step.id"
                            ></span>

                            <span class="font-semibold" x-text="step.title"></span>
                        </div>
                    </button>
                </template>
            </div>
        </div>

        {{-- Body --}}
        <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
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

            {{-- Paso 1: tipo solo cuando no se abrió explícitamente como band/vulcanization/section_change --}}
            <section x-show="bandWizardStep === 1" x-cloak class="space-y-5">
                <template x-if="bandType === 'band'">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h4 class="text-lg font-semibold text-slate-900">Nuevo cambio de banda</h4>
                        <p class="mt-1 text-sm text-slate-500">
                            Registra la información técnica de la nueva banda. No se muestran opciones de vulcanizado ni tramo para evitar confusión.
                        </p>
                    </div>
                </template>

                <template x-if="bandType !== 'band'">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h4 class="text-lg font-semibold text-slate-900">Banda asociada</h4>
                        <p class="mt-1 text-sm text-slate-500">
                            Selecciona la banda oficial a la que se asociará este evento.
                        </p>

                        <div class="mt-4 grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">Banda activa sugerida</div>
                                <div class="mt-2 text-sm font-semibold text-slate-800" x-text="bandActiveBandLabel()"></div>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400">
                                    Banda asociada
                                </label>

                                <select
                                    x-model="bandDraft.parent_id"
                                    class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                >
                                    <option value="">Seleccionar banda</option>
                                    <template x-for="band in bandEventBands" :key="'compact-parent-option-' + band.id">
                                        <option :value="band.id" x-text="bandOptionLabel(band)"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                    </div>
                </template>
            </section>
                        {{-- Paso 2: captura técnica --}}
            <section x-show="bandWizardStep === 2" x-cloak class="space-y-5">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h4 class="text-lg font-semibold text-slate-900">Captura técnica</h4>

                    <div class="mt-5 space-y-5">
                        <template x-if="bandType === 'band'">
                            <div class="grid gap-5 xl:grid-cols-3">
                                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                    <table class="w-full border-collapse text-sm">
                                        <tbody>
                                            <tr class="bg-[#4f79bd] text-white">
                                                <th colspan="2" class="border border-slate-300 px-3 py-2 text-center text-xs font-bold uppercase">Referencia de la banda</th>
                                            </tr>
                                            <tr><th class="w-[45%] border border-slate-300 px-3 py-2 font-bold text-slate-900">Marca</th><td class="border border-slate-300 bg-slate-100 px-3 py-2"><input type="text" x-model="bandDraft.brand" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"></td></tr>
                                            <tr><th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Espesor total</th><td class="border border-slate-300 bg-slate-100 px-3 py-2"><input type="number" step="0.01" x-model="bandDraft.total_thickness" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"></td></tr>
                                            <tr><th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Cubierta superior</th><td class="border border-slate-300 bg-slate-100 px-3 py-2"><input type="number" step="0.01" x-model="bandDraft.top_cover_thickness" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"></td></tr>
                                            <tr><th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Cubierta inferior</th><td class="border border-slate-300 bg-slate-100 px-3 py-2"><input type="number" step="0.01" x-model="bandDraft.bottom_cover_thickness" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"></td></tr>
                                            <tr><th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Lonas</th><td class="border border-slate-300 bg-slate-100 px-3 py-2"><input type="number" min="1" x-model="bandDraft.plies" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"></td></tr>
                                            <tr><th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Ancho</th><td class="border border-slate-300 bg-slate-100 px-3 py-2"><input type="number" step="0.01" x-model="bandDraft.width" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"></td></tr>
                                            <tr><th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Longitud</th><td class="border border-slate-300 bg-slate-100 px-3 py-2"><input type="number" step="0.01" x-model="bandDraft.length" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"></td></tr>
                                            <tr><th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Rollos</th><td class="border border-slate-300 bg-slate-100 px-3 py-2"><input type="number" min="1" x-model="bandDraft.roll_count" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"></td></tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                    <table class="w-full border-collapse text-sm">
                                        <tbody>
                                            <tr class="bg-[#4f79bd] text-white">
                                                <th colspan="2" class="border border-slate-300 px-3 py-2 text-center text-xs font-bold uppercase">Vulcanizado</th>
                                            </tr>
                                            <tr><th class="w-[45%] border border-slate-300 px-3 py-2 font-bold text-slate-900">Temperatura</th><td class="border border-slate-300 bg-slate-100 px-3 py-2"><input type="number" step="0.01" x-model="bandDraft.temperature" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"></td></tr>
                                            <tr><th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Presión</th><td class="border border-slate-300 bg-slate-100 px-3 py-2"><input type="number" step="0.01" x-model="bandDraft.pressure" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"></td></tr>
                                            <tr><th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Tiempo vulcanizado</th><td class="border border-slate-300 bg-slate-100 px-3 py-2"><input type="number" step="0.01" x-model="bandDraft.time" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"></td></tr>
                                            <tr><th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Tiempo enfriamiento</th><td class="border border-slate-300 bg-slate-100 px-3 py-2"><input type="number" step="0.01" x-model="bandDraft.cooling_time" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"></td></tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                    <table class="w-full border-collapse text-sm">
                                        <tbody>
                                            <tr class="bg-[#4f79bd] text-white">
                                                <th colspan="2" class="border border-slate-300 px-3 py-2 text-center text-xs font-bold uppercase">Entrega de equipo</th>
                                            </tr>
                                            <tr><th class="w-[45%] border border-slate-300 px-3 py-2 font-bold text-slate-900">Corriente motor</th><td class="border border-slate-300 bg-slate-100 px-3 py-2"><input type="number" step="0.01" x-model="bandDraft.motor_current" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"></td></tr>
                                            <tr><th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Alineación</th><td class="border border-slate-300 bg-slate-100 px-3 py-2"><input type="text" x-model="bandDraft.alignment" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"></td></tr>
                                            <tr><th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Mat acumul</th><td class="border border-slate-300 bg-slate-100 px-3 py-2"><input type="text" x-model="bandDraft.material_accumulation" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"></td></tr>
                                            <tr><th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Guardilña</th><td class="border border-slate-300 bg-slate-100 px-3 py-2"><input type="text" x-model="bandDraft.guard" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"></td></tr>
                                            <tr><th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Rodillería</th><td class="border border-slate-300 bg-slate-100 px-3 py-2"><input type="text" x-model="bandDraft.idler_condition" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"></td></tr>
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
                                            <th colspan="2" class="border border-slate-300 px-3 py-2 text-center text-xs font-bold uppercase">Parámetros de vulcanizado</th>
                                        </tr>
                                        <tr><th class="w-[35%] border border-slate-300 px-3 py-2 font-bold text-slate-900">Temperatura</th><td class="border border-slate-300 bg-slate-100 px-3 py-2"><input type="number" step="0.01" x-model="bandDraft.temperature" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"></td></tr>
                                        <tr><th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Presión</th><td class="border border-slate-300 bg-slate-100 px-3 py-2"><input type="number" step="0.01" x-model="bandDraft.pressure" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"></td></tr>
                                        <tr><th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Tiempo vulcanizado</th><td class="border border-slate-300 bg-slate-100 px-3 py-2"><input type="number" step="0.01" x-model="bandDraft.time" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"></td></tr>
                                        <tr><th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Tiempo enfriamiento</th><td class="border border-slate-300 bg-slate-100 px-3 py-2"><input type="number" step="0.01" x-model="bandDraft.cooling_time" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </template>
                                                <template x-if="bandType === 'section_change'">
                            <div class="space-y-5">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <label class="flex items-center gap-3 text-sm font-semibold text-slate-800">
                                        <input
                                            type="checkbox"
                                            x-model="bandDraft.same_reference"
                                            class="h-4 w-4 rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                                        >
                                        ¿La referencia es igual a la instalada?
                                    </label>
                                </div>

                                <div class="grid gap-5 xl:grid-cols-3">
                                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                        <table class="w-full border-collapse text-sm">
                                            <tbody>
                                                <tr class="bg-[#4f79bd] text-white">
                                                    <th colspan="2" class="border border-slate-300 px-3 py-2 text-center text-xs font-bold uppercase">Vulcanizado</th>
                                                </tr>
                                                <tr><th class="w-[45%] border border-slate-300 px-3 py-2 font-bold text-slate-900">Temperatura</th><td class="border border-slate-300 bg-slate-100 px-3 py-2"><input type="number" step="0.01" x-model="bandDraft.temperature" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"></td></tr>
                                                <tr><th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Presión</th><td class="border border-slate-300 bg-slate-100 px-3 py-2"><input type="number" step="0.01" x-model="bandDraft.pressure" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"></td></tr>
                                                <tr><th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Tiempo vulcanizado</th><td class="border border-slate-300 bg-slate-100 px-3 py-2"><input type="number" step="0.01" x-model="bandDraft.time" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"></td></tr>
                                                <tr><th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Tiempo enfriamiento</th><td class="border border-slate-300 bg-slate-100 px-3 py-2"><input type="number" step="0.01" x-model="bandDraft.cooling_time" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"></td></tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                        <table class="w-full border-collapse text-sm">
                                            <tbody>
                                                <tr class="bg-[#4f79bd] text-white">
                                                    <th colspan="2" class="border border-slate-300 px-3 py-2 text-center text-xs font-bold uppercase">Entrega de equipo</th>
                                                </tr>
                                                <tr><th class="w-[45%] border border-slate-300 px-3 py-2 font-bold text-slate-900">Corriente motor</th><td class="border border-slate-300 bg-slate-100 px-3 py-2"><input type="number" step="0.01" x-model="bandDraft.motor_current" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"></td></tr>
                                                <tr><th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Alineación</th><td class="border border-slate-300 bg-slate-100 px-3 py-2"><input type="text" x-model="bandDraft.alignment" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"></td></tr>
                                                <tr><th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Mat acumul</th><td class="border border-slate-300 bg-slate-100 px-3 py-2"><input type="text" x-model="bandDraft.material_accumulation" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"></td></tr>
                                                <tr><th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Guardilña</th><td class="border border-slate-300 bg-slate-100 px-3 py-2"><input type="text" x-model="bandDraft.guard" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"></td></tr>
                                                <tr><th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Rodillería</th><td class="border border-slate-300 bg-slate-100 px-3 py-2"><input type="text" x-model="bandDraft.idler_condition" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"></td></tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                        <table class="w-full border-collapse text-sm">
                                            <tbody>
                                                <tr class="bg-[#4f79bd] text-white">
                                                    <th colspan="2" class="border border-slate-300 px-3 py-2 text-center text-xs font-bold uppercase">Cambio de tramo</th>
                                                </tr>
                                                <tr><th class="w-[45%] border border-slate-300 px-3 py-2 font-bold text-slate-900">Marca</th><td class="border border-slate-300 bg-slate-100 px-3 py-2"><input type="text" x-model="bandDraft.section_brand" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"></td></tr>
                                                <tr><th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Espesor</th><td class="border border-slate-300 bg-slate-100 px-3 py-2"><input type="number" step="0.01" x-model="bandDraft.section_thickness" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"></td></tr>
                                                <tr><th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Lonas</th><td class="border border-slate-300 bg-slate-100 px-3 py-2"><input type="number" min="1" x-model="bandDraft.section_plies" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"></td></tr>
                                                <tr><th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Longitud</th><td class="border border-slate-300 bg-slate-100 px-3 py-2"><input type="number" step="0.01" x-model="bandDraft.section_length" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"></td></tr>
                                                <tr><th class="border border-slate-300 px-3 py-2 font-bold text-slate-900">Ancho</th><td class="border border-slate-300 bg-slate-100 px-3 py-2"><input type="number" step="0.01" x-model="bandDraft.section_width" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"></td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </section>

            {{-- Paso 3: cierre --}}
            <section x-show="bandWizardStep === 3" x-cloak class="space-y-5">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h4 class="text-lg font-semibold text-slate-900">Cierre del registro</h4>

                    <div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1fr)_260px_260px]">
                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                            <table class="w-full border-collapse text-sm">
                                <tbody>
                                    <tr class="bg-[#4f79bd] text-white">
                                        <th class="border border-slate-300 px-3 py-2 text-center text-xs font-bold uppercase">Observación</th>
                                    </tr>
                                    <tr>
                                        <td class="border border-slate-300 bg-slate-100 px-3 py-2">
                                            <textarea
                                                x-model="bandDraft.observation"
                                                rows="8"
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
                                        <th class="border border-slate-300 px-3 py-2 text-center text-xs font-bold uppercase">Evidencia</th>
                                    </tr>
                                    <tr>
                                        <td class="border border-slate-300 bg-slate-100 px-3 py-6 text-center">
                                            <p class="text-sm font-semibold text-slate-800">Fotos y videos</p>
                                            <p class="mt-1 text-xs text-slate-500">Pendiente carga en R2.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                            <table class="w-full border-collapse text-sm">
                                <tbody>
                                    <tr class="bg-[#4f79bd] text-white">
                                        <th class="border border-slate-300 px-3 py-2 text-center text-xs font-bold uppercase">Fecha reporte</th>
                                    </tr>
                                    <tr>
                                        <td class="border border-slate-300 bg-slate-100 px-3 py-4">
                                            <input
                                                type="date"
                                                x-model="bandDraft.report_date"
                                                class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none"
                                            >
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="border border-slate-300 px-3 py-2 text-center text-xs text-slate-500">
                                            Fecha manual ingresada por el usuario
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        {{-- Footer compacto --}}
        <div class="border-t border-slate-200 bg-white px-6 py-4">
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
                        @click="saveBandDraft()"
                        :disabled="loading || !bandDraft"
                        class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 disabled:pointer-events-none disabled:opacity-50"
                    >
                        Guardar borrador
                    </button>

                    <button
                        type="button"
                        x-show="bandWizardStep < bandWizardTotalSteps()"
                        @click="nextBandStep()"
                        class="rounded-xl bg-[#d94d33] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                    >
                        Siguiente
                    </button>

                    <button
                        type="button"
                        x-show="bandWizardStep === bandWizardTotalSteps()"
                        @click="publishBandDraft()"
                        :disabled="loading || !bandDraft"
                        class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:pointer-events-none disabled:opacity-70"
                    >
                        Publicar reporte
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal borrador - Medición de espesores y dureza --}}
<div
    x-cloak
    x-show="draftModalOpen"
    x-transition.opacity
    class="fixed top-0 left-0 z-[9999] flex h-screen w-screen items-center justify-center bg-slate-900/60 px-4 py-6"
    @keydown.escape.window="closeDraftModal()"
>
    <div
        x-show="draftModalOpen"
        x-transition
        class="flex max-h-[92vh] w-full max-w-7xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl"
        @click.outside="closeDraftModal()"
    >
        <div class="border-b border-slate-200 bg-white px-6 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h3 class="text-xl font-semibold text-slate-900">
                        Borrador - Medición de espesores y dureza
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Registra las mediciones por cubierta del activo
                        <span class="font-semibold text-slate-700">{{ $element->name }}</span>.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex items-center gap-2 rounded-xl border border-slate-300 bg-slate-50 px-3 py-2">
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">
                            Fecha
                        </label>

                        <input
                            type="date"
                            x-model="publishForm.report_date"
                            class="w-[145px] bg-transparent text-sm font-semibold text-slate-800 outline-none"
                        >
                    </div>

                    <button
                        type="button"
                        @click="addCover()"
                        :disabled="loading"
                        class="rounded-xl bg-[#d94d33] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#b83f29] disabled:pointer-events-none disabled:opacity-70"
                    >
                        Agregar cubierta
                    </button>
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
                        @click="publishDraft()"
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

        <div class="min-h-0 flex-1 overflow-y-auto bg-slate-50 px-6 py-5">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="bg-[#4f79bd] px-4 py-3 text-center text-sm font-bold uppercase tracking-wider text-white">
                    Medición de espesores y dureza
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-[1120px] w-full table-fixed border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-100 text-slate-700">
                                <th class="w-[140px] border-b border-slate-200 px-2 py-2 text-left text-[11px] font-bold uppercase tracking-wider">Mediciones</th>
                                <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">Izquierdo</th>
                                <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">Centro</th>
                                <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">Derecho</th>
                                <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">Max</th>
                                <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">Min</th>
                                <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">% Suficiencia</th>
                                <th class="border-b border-l border-slate-200 px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Mediciones</th>
                                <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">Izquierdo</th>
                                <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">Centro</th>
                                <th class="border-b border-slate-200 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">Derecho</th>
                            </tr>
                        </thead>

                        <template x-for="line in (draft?.lines || [])" :key="'draft-cover-' + line.cover_number">
                            <tbody>
                                <tr class="bg-white">
                                    <td class="border-b border-slate-200 px-4 py-3 font-semibold text-slate-800">
                                        CUBIERTA SUPERIOR <span x-text="line.cover_number"></span>
                                    </td>

                                    <td class="border-b border-slate-200 bg-slate-100 px-3 py-2">
                                        <input type="number" step="0.01" x-model="line.top_left" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none">
                                    </td>

                                    <td class="border-b border-slate-200 bg-slate-100 px-3 py-2">
                                        <input type="number" step="0.01" x-model="line.top_center" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none">
                                    </td>

                                    <td class="border-b border-slate-200 bg-slate-100 px-3 py-2">
                                        <input type="number" step="0.01" x-model="line.top_right" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none">
                                    </td>

                                    <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700" x-text="maxValue([line.top_left, line.top_center, line.top_right]) || '—'"></td>
                                    <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700" x-text="minValue([line.top_left, line.top_center, line.top_right]) || '—'"></td>
                                    <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700" x-text="calculateSufficiency(minValue([line.top_left, line.top_center, line.top_right]), 'top') ?? '—'"></td>

                                    <td class="border-b border-l border-slate-200 px-4 py-3 font-semibold text-slate-800">
                                        DUREZA <span x-text="line.cover_number"></span>
                                    </td>

                                    <td class="border-b border-slate-200 bg-slate-100 px-3 py-2">
                                        <input type="number" step="0.01" x-model="line.hardness_left" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none">
                                    </td>

                                    <td class="border-b border-slate-200 bg-slate-100 px-3 py-2">
                                        <input type="number" step="0.01" x-model="line.hardness_center" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none">
                                    </td>

                                    <td class="border-b border-slate-200 bg-slate-100 px-3 py-2">
                                        <input type="number" step="0.01" x-model="line.hardness_right" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none">
                                    </td>
                                </tr>

                                <tr class="bg-slate-50/60">
                                    <td class="border-b border-slate-200 px-4 py-3 font-semibold text-slate-800">
                                        CUBIERTA INFERIOR <span x-text="line.cover_number"></span>
                                    </td>

                                    <td class="border-b border-slate-200 bg-slate-100 px-3 py-2">
                                        <input type="number" step="0.01" x-model="line.bottom_left" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none">
                                    </td>

                                    <td class="border-b border-slate-200 bg-slate-100 px-3 py-2">
                                        <input type="number" step="0.01" x-model="line.bottom_center" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none">
                                    </td>

                                    <td class="border-b border-slate-200 bg-slate-100 px-3 py-2">
                                        <input type="number" step="0.01" x-model="line.bottom_right" class="w-full bg-transparent text-center font-semibold text-slate-900 outline-none">
                                    </td>

                                    <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700" x-text="maxValue([line.bottom_left, line.bottom_center, line.bottom_right]) || '—'"></td>
                                    <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700" x-text="minValue([line.bottom_left, line.bottom_center, line.bottom_right]) || '—'"></td>
                                    <td class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700" x-text="calculateSufficiency(minValue([line.bottom_left, line.bottom_center, line.bottom_right]), 'bottom') ?? '—'"></td>

                                    <td colspan="4" class="border-b border-l border-slate-200 bg-white px-4 py-3">
                                        <div class="flex justify-end">
                                            <button
                                                type="button"
                                                x-show="(draft?.lines || []).length > 1"
                                                @click="removeCover(line.cover_number)"
                                                :disabled="loading"
                                                class="rounded-xl border border-red-300 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100 disabled:pointer-events-none disabled:opacity-70"
                                            >
                                                Eliminar cubierta
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </template>
                    </table>
                </div>
            </div>
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
            class="flex max-h-[92vh] w-full max-w-7xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl"
            @click.outside="if (!historyEditModalOpen) closeHistoryModal()"
        >
            <div class="border-b border-slate-200 px-6 py-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h3 class="text-xl font-semibold text-slate-900">Histórico de reportes oficiales</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            <span class="font-semibold text-slate-700">{{ $element->name }}</span>.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            @click="historySidebarOpen = !historySidebarOpen; refreshLucide()"
                            :title="historySidebarOpen ? 'Ocultar listado' : 'Mostrar listado'"
                            class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-700 transition hover:bg-slate-100"
                        >
                            <i
                                data-lucide="eye-off"
                                x-show="historySidebarOpen"
                                x-cloak
                                class="h-5 w-5"
                            ></i>

                            <i
                                data-lucide="eye"
                                x-show="!historySidebarOpen"
                                x-cloak
                                class="h-5 w-5"
                            ></i>
                        </button>
                        <button
                            type="button"
                            x-show="selectedHistoryReport"
                            @click="openHistoryEditModal()"
                            class="rounded-xl bg-[#d94d33] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                        >
                            Editar
                        </button>

                        <button
                            type="button"
                            x-show="selectedHistoryReport"
                            @click="deleteThicknessHistoricalReport()"
                            class="rounded-xl border border-red-300 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-100"
                        >
                            Eliminar
                        </button>

                        <button
                            type="button"
                            @click="closeHistoryModal()"
                            class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex-1 overflow-hidden">
                <div class="flex h-full min-w-0 overflow-hidden">
                    <div
                        class="min-h-0 shrink-0 overflow-hidden border-r border-slate-200 transition-[width,opacity] duration-300 ease-out"
                        :class="historySidebarOpen ? 'w-[280px] opacity-100' : 'w-0 opacity-0'"
                    >
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

                    <div class="min-w-0 flex-1 max-h-[78vh] overflow-y-auto p-6 transition-all duration-300 ease-out">
                        <div
                            x-show="bandStateHistoryLoading && !selectedBandStateHistoryReport"
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
                            x-show="selectedHistoryReport"
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

                                                            <td
                                                                class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700"
                                                                x-text="displayValue(line.top_left)"
                                                            ></td>

                                                            <td
                                                                class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700"
                                                                x-text="displayValue(line.top_center)"
                                                            ></td>

                                                            <td
                                                                class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700"
                                                                x-text="displayValue(line.top_right)"
                                                            ></td>

                                                            <td
                                                                class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700"
                                                                x-text="maxValue([line.top_left, line.top_center, line.top_right]) || '—'"
                                                            ></td>

                                                            <td
                                                                class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700"
                                                                x-text="minValue([line.top_left, line.top_center, line.top_right]) || '—'"
                                                            ></td>

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

                                                            <td
                                                                class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700"
                                                                x-text="displayValue(line.bottom_left)"
                                                            ></td>

                                                            <td
                                                                class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700"
                                                                x-text="displayValue(line.bottom_center)"
                                                            ></td>

                                                            <td
                                                                class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700"
                                                                x-text="displayValue(line.bottom_right)"
                                                            ></td>

                                                            <td
                                                                class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700"
                                                                x-text="maxValue([line.bottom_left, line.bottom_center, line.bottom_right]) || '—'"
                                                            ></td>

                                                            <td
                                                                class="border-b border-slate-200 px-4 py-3 text-center font-semibold text-slate-700"
                                                                x-text="minValue([line.bottom_left, line.bottom_center, line.bottom_right]) || '—'"
                                                            ></td>

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

    {{-- Modal editar histórico - Medición de espesores y dureza --}}
<div
    x-cloak
    x-show="historyEditModalOpen"
    x-transition.opacity
    class="fixed top-0 left-0 z-[10000] flex h-screen w-screen items-center justify-center bg-slate-900/60 px-4 py-6"
    @keydown.escape.window="closeHistoryEditModal()"
>
    <div
        x-show="historyEditModalOpen"
        x-transition
        class="flex max-h-[92vh] w-full max-w-7xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl"
        @click.outside="closeHistoryEditModal()"
        @click.stop
    >
        <div class="border-b border-slate-200 bg-white px-6 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h3 class="text-xl font-semibold text-slate-900">
                        Editar reporte - Medición de espesores y dureza
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Modifica el reporte oficial seleccionado del activo
                        <span class="font-semibold text-slate-700">{{ $element->name }}</span>.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex items-center gap-2 rounded-xl border border-slate-300 bg-slate-50 px-3 py-2">
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">
                            Fecha
                        </label>

                        <input
                            type="date"
                            x-model="historyEditForm.report_date"
                            class="w-[145px] bg-transparent text-sm font-semibold text-slate-800 outline-none"
                        >
                    </div>

                    <button
                        type="button"
                        @click="addHistoryEditCover()"
                        :disabled="loading"
                        class="rounded-xl bg-[#d94d33] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#b83f29] disabled:pointer-events-none disabled:opacity-70"
                    >
                        Agregar cubierta
                    </button>

                    <button
                        type="button"
                        @click="updateThicknessHistoricalReport()"
                        :disabled="loading"
                        class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:pointer-events-none disabled:opacity-70"
                    >
                        Guardar cambios
                    </button>

                    <button
                        type="button"
                        @click="closeHistoryEditModal()"
                        class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto bg-slate-50 px-6 py-5">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="bg-[#4f79bd] px-4 py-3 text-center text-sm font-bold uppercase tracking-wider text-white">
                    Medición de espesores y dureza
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-[1120px] w-full table-fixed border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-100 text-slate-700">
                                <th class="w-[140px] border-b border-slate-200 px-2 py-2 text-left text-[11px] font-bold uppercase tracking-wider">
                                    Mediciones
                                </th>
                                <th class="w-[90px] border-b border-slate-200 px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider">
                                    Izquierdo
                                </th>
                                <th class="w-[90px] border-b border-slate-200 px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider">
                                    Centro
                                </th>
                                <th class="w-[90px] border-b border-slate-200 px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider">
                                    Derecho
                                </th>
                                <th class="w-[70px] border-b border-slate-200 px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider">
                                    Max
                                </th>
                                <th class="w-[70px] border-b border-slate-200 px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider">
                                    Min
                                </th>
                                <th class="w-[90px] border-b border-slate-200 px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider">
                                    % Suf.
                                </th>
                                <th class="w-[120px] border-b border-l border-slate-200 px-2 py-2 text-left text-[11px] font-bold uppercase tracking-wider">
                                    Mediciones
                                </th>
                                <th class="w-[80px] border-b border-slate-200 px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider">
                                    Izquierdo
                                </th>
                                <th class="w-[80px] border-b border-slate-200 px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider">
                                    Centro
                                </th>
                                <th class="w-[80px] border-b border-slate-200 px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider">
                                    Derecho
                                </th>
                            </tr>
                        </thead>

                        <template x-for="line in (historyEditForm?.lines || [])" :key="'history-edit-cover-' + line.cover_number">
                            <tbody>
                                <tr class="bg-white">
                                    <td class="border-b border-slate-200 px-2 py-2 font-semibold text-slate-800">
                                        CUBIERTA SUPERIOR <span x-text="line.cover_number"></span>
                                    </td>

                                    <td class="border-b border-slate-200 bg-slate-100 px-2 py-1.5">
                                        <input
                                            type="number"
                                            step="0.01"
                                            x-model="line.top_left"
                                            class="w-full min-w-0 bg-transparent text-center text-xs font-semibold text-slate-900 outline-none"
                                        >
                                    </td>

                                    <td class="border-b border-slate-200 bg-slate-100 px-2 py-1.5">
                                        <input
                                            type="number"
                                            step="0.01"
                                            x-model="line.top_center"
                                            class="w-full min-w-0 bg-transparent text-center text-xs font-semibold text-slate-900 outline-none"
                                        >
                                    </td>

                                    <td class="border-b border-slate-200 bg-slate-100 px-2 py-1.5">
                                        <input
                                            type="number"
                                            step="0.01"
                                            x-model="line.top_right"
                                            class="w-full min-w-0 bg-transparent text-center text-xs font-semibold text-slate-900 outline-none"
                                        >
                                    </td>

                                    <td
                                        class="border-b border-slate-200 px-2 py-2 text-center font-semibold text-slate-700"
                                        x-text="maxValue([line.top_left, line.top_center, line.top_right]) || '—'"
                                    ></td>

                                    <td
                                        class="border-b border-slate-200 px-2 py-2 text-center font-semibold text-slate-700"
                                        x-text="minValue([line.top_left, line.top_center, line.top_right]) || '—'"
                                    ></td>

                                    <td
                                        class="border-b border-slate-200 px-2 py-2 text-center font-semibold text-slate-700"
                                        x-text="calculateSufficiency(minValue([line.top_left, line.top_center, line.top_right]), 'top') ?? '—'"
                                    ></td>

                                    <td class="border-b border-l border-slate-200 px-2 py-2 font-semibold text-slate-800">
                                        DUREZA <span x-text="line.cover_number"></span>
                                    </td>

                                    <td class="border-b border-slate-200 bg-slate-100 px-2 py-1.5">
                                        <input
                                            type="number"
                                            step="0.01"
                                            x-model="line.hardness_left"
                                            class="w-full min-w-0 bg-transparent text-center text-xs font-semibold text-slate-900 outline-none"
                                        >
                                    </td>

                                    <td class="border-b border-slate-200 bg-slate-100 px-2 py-1.5">
                                        <input
                                            type="number"
                                            step="0.01"
                                            x-model="line.hardness_center"
                                            class="w-full min-w-0 bg-transparent text-center text-xs font-semibold text-slate-900 outline-none"
                                        >
                                    </td>

                                    <td class="border-b border-slate-200 bg-slate-100 px-2 py-1.5">
                                        <input
                                            type="number"
                                            step="0.01"
                                            x-model="line.hardness_right"
                                            class="w-full min-w-0 bg-transparent text-center text-xs font-semibold text-slate-900 outline-none"
                                        >
                                    </td>
                                </tr>

                                <tr class="bg-slate-50/60">
                                    <td class="border-b border-slate-200 px-2 py-2 font-semibold text-slate-800">
                                        CUBIERTA INFERIOR <span x-text="line.cover_number"></span>
                                    </td>

                                    <td class="border-b border-slate-200 bg-slate-100 px-2 py-1.5">
                                        <input
                                            type="number"
                                            step="0.01"
                                            x-model="line.bottom_left"
                                            class="w-full min-w-0 bg-transparent text-center text-xs font-semibold text-slate-900 outline-none"
                                        >
                                    </td>

                                    <td class="border-b border-slate-200 bg-slate-100 px-2 py-1.5">
                                        <input
                                            type="number"
                                            step="0.01"
                                            x-model="line.bottom_center"
                                            class="w-full min-w-0 bg-transparent text-center text-xs font-semibold text-slate-900 outline-none"
                                        >
                                    </td>

                                    <td class="border-b border-slate-200 bg-slate-100 px-2 py-1.5">
                                        <input
                                            type="number"
                                            step="0.01"
                                            x-model="line.bottom_right"
                                            class="w-full min-w-0 bg-transparent text-center text-xs font-semibold text-slate-900 outline-none"
                                        >
                                    </td>

                                    <td
                                        class="border-b border-slate-200 px-2 py-2 text-center font-semibold text-slate-700"
                                        x-text="maxValue([line.bottom_left, line.bottom_center, line.bottom_right]) || '—'"
                                    ></td>

                                    <td
                                        class="border-b border-slate-200 px-2 py-2 text-center font-semibold text-slate-700"
                                        x-text="minValue([line.bottom_left, line.bottom_center, line.bottom_right]) || '—'"
                                    ></td>

                                    <td
                                        class="border-b border-slate-200 px-2 py-2 text-center font-semibold text-slate-700"
                                        x-text="calculateSufficiency(minValue([line.bottom_left, line.bottom_center, line.bottom_right]), 'bottom') ?? '—'"
                                    ></td>

                                    <td colspan="4" class="border-b border-l border-slate-200 bg-white px-2 py-2">
                                        <div class="flex justify-end">
                                            <button
                                                type="button"
                                                x-show="(historyEditForm?.lines || []).length > 1"
                                                @click="removeHistoryEditCover(line.cover_number)"
                                                :disabled="loading"
                                                class="rounded-xl border border-red-300 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100 disabled:pointer-events-none disabled:opacity-70"
                                            >
                                                Eliminar cubierta
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </template>
                    </table>
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
        'fixed bottom-6 right-6 z-[20000] max-w-[420px] rounded-2xl px-4 py-3 text-sm font-semibold shadow-2xl transition ' +
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
        loading: false,
        creationEnabled: !!config.creationEnabled,

        // ======================
        // THICKNESS
        // ======================
        draftModalOpen: false,
        draft: config.initialDraft ?? null,
        latestReport: config.initialLatestReport ?? null,
        historicalReports: Array.isArray(config.initialHistoricalReports) ? config.initialHistoricalReports : [],
        selectedHistoryReport: null,
        historyModalOpen: false,
        historyLoading: false,
        historySidebarOpen: true,

        historyEditModalOpen: false,
        historyEditErrors: [],
        historyEditForm: {
            id: null,
            report_date: '',
            lines: [],
        },



        publishConfirmOpen: false,
        publishForm: {
            report_date: config.today,
        },

        errors: [],

        // ======================
        // BAND STATE
        // ======================
        bandStateDraftModalOpen: false,
        bandStateDraft: config.initialBandStateDraft ?? {
            id: null,
            element_id: config.elementId,
            description: '',
            width: '',
            top_cover: '',
            bottom_cover: '',
        },
        latestBandStateReport: config.initialLatestBandStateReport ?? null,
        bandStateHistoricalReports: Array.isArray(config.initialBandStateHistoricalReports)
            ? config.initialBandStateHistoricalReports
            : [],
        selectedBandStateHistoryReport: null,
        bandStateHistoryModalOpen: false,
        bandStateHistoryLoading: false,
        bandStateEditModalOpen: false,
        bandStateEditErrors: [],
        bandStateEditForm: {
            id: null,
            report_date: '',
            description: '',
            width: '',
            top_cover: '',
            bottom_cover: '',
        },

        bandStatePublishConfirmOpen: false,
        bandStatePublishForm: {
            report_date: config.today,
        },

        bandStateErrors: [],

        // ======================
        // BAND EVENTS
        // ======================
        bandType: 'band',
        bandWizardOpen: false,
        bandWizardStep: 1,
        bandErrors: [],

        bandEventLatestReport: config.initialBandEventLatestReport ?? null,
        bandEventActiveBand: config.initialBandEventActiveBand ?? null,
        bandEventBands: Array.isArray(config.initialBandEventBands) ? config.initialBandEventBands : [],
        bandHistoricalTree: Array.isArray(config.initialBandEventHistoricalTree) ? config.initialBandEventHistoricalTree : [],
        selectedBandHistory: null,
        bandHistoryModalOpen: false,

        bandEditModalOpen: false,
        bandEditErrors: [],
        bandEditForm: null,

        bandDraft: {
            id: null,
            element_id: config.elementId,
            parent_id: null,
            type: 'band',

            // REFERENCIA BANDA
            brand: '',
            total_thickness: '',
            top_cover_thickness: '',
            bottom_cover_thickness: '',
            plies: '',
            width: '',
            length: '',
            roll_count: '',

            // VULCANIZADO
            temperature: '',
            pressure: '',
            time: '',
            cooling_time: '',

            // ENTREGA EQUIPO
            motor_current: '',
            alignment: '',
            material_accumulation: '',
            guard: '',
            idler_condition: '',

            // CAMBIO TRAMO
            section_brand: '',
            section_thickness: '',
            section_plies: '',
            section_length: '',
            section_width: '',

            // LÓGICA
            same_reference: false,

            // COMUNES
            observation: '',
            report_date: config.today,
        },

        // ======================
        // HELPERS BASE
        // ======================
        emptyBandDraft(type = 'band') {
            return {
                id: null,
                element_id: config.elementId,
                parent_id: type === 'band' ? null : (this.bandEventActiveBand?.id ?? null),
                type,

                // REFERENCIA BANDA
                brand: '',
                total_thickness: '',
                top_cover_thickness: '',
                bottom_cover_thickness: '',
                plies: '',
                width: '',
                length: '',
                roll_count: '',

                // VULCANIZADO
                temperature: '',
                pressure: '',
                time: '',
                cooling_time: '',

                // ENTREGA EQUIPO
                motor_current: '',
                alignment: '',
                material_accumulation: '',
                guard: '',
                idler_condition: '',

                // CAMBIO TRAMO
                section_brand: '',
                section_thickness: '',
                section_plies: '',
                section_length: '',
                section_width: '',

                // LÓGICA
                same_reference: false,

                // COMUNES
                observation: '',
                report_date: config.today,
            };
        },

        csrf() {
            const token = document.querySelector('meta[name="csrf-token"]');
            return token ? token.getAttribute('content') : '';
        },

        async parseJsonResponse(response) {
            const text = await response.text();

            if (!text) {
                return {};
            }

            try {
                return JSON.parse(text);
            } catch (error) {
                return {
                    success: false,
                    message: 'La respuesta del servidor no es JSON válido.',
                    raw: text,
                };
            }
        },

        normalizeErrors(payload, fallback = 'Ocurrió un error inesperado.') {
            if (!payload) {
                return [fallback];
            }

            if (Array.isArray(payload.errors)) {
                return payload.errors.flat().filter(Boolean);
            }

            if (payload.errors && typeof payload.errors === 'object') {
                return Object.values(payload.errors).flat().filter(Boolean);
            }

            if (payload.message) {
                return [payload.message];
            }

            return [fallback];
        },

        displayValue(value) {
            if (value === null || value === undefined || value === '') {
                return '—';
            }

            return value;
        },

        formatDate(value) {
            if (!value) return '—';

            const date = new Date(`${value}T00:00:00`);
            if (Number.isNaN(date.getTime())) return value;

            return date.toLocaleDateString('es-CO', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
            });
        },

        formatDateTime(value) {
            if (!value) return '—';

            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return value;

            return date.toLocaleString('es-CO', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
            });
        },
        canCreateRecords() {
            return !!this.creationEnabled;
        },

        notifyCreationDisabled() {
            this.showCrudToast(
                'La creación de registros está deshabilitada para este cliente y tipo de activo.',
                'error'
            );
        },

        showCrudToast(message, type = 'success') {
            if (window.showCrudToast) {
                window.showCrudToast(message, type);
                return;
            }

            console.log(`[${type.toUpperCase()}] ${message}`);
        },

        refreshLucide() {
            this.$nextTick(() => {
                if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    window.lucide.createIcons();
                }
            });
        },
                // ======================
        // THICKNESS HELPERS
        // ======================
        emptyThicknessLine(coverNumber = 1) {
            return {
                id: null,
                cover_number: coverNumber,
                top_left: '',
                top_center: '',
                top_right: '',
                bottom_left: '',
                bottom_center: '',
                bottom_right: '',
                hardness_left: '',
                hardness_center: '',
                hardness_right: '',
            };
        },

        emptyThicknessDraft() {
            return {
                id: null,
                element_id: config.elementId,
                lines: [this.emptyThicknessLine(1)],
            };
        },

        ensureDraftStructure() {
            if (!this.draft || typeof this.draft !== 'object') {
                this.draft = this.emptyThicknessDraft();
            }

            if (!Array.isArray(this.draft.lines) || this.draft.lines.length === 0) {
                this.draft.lines = [this.emptyThicknessLine(1)];
            }

            this.draft.lines = this.draft.lines.map((line, index) => ({
                ...this.emptyThicknessLine(index + 1),
                ...(line || {}),
                cover_number: line?.cover_number ?? (index + 1),
            }));
        },

        hasDraft() {
            return !!(this.draft && this.draft.id);
        },

        openDraftModal() {
            this.ensureDraftStructure();
            this.errors = [];

            if (!this.publishForm.report_date) {
                this.publishForm.report_date = config.today;
            }

            this.draftModalOpen = true;
            this.refreshLucide();
        },

        closeDraftModal() {
            this.draftModalOpen = false;
            this.errors = [];
        },

        async createDraftIfNeeded() {
            if (this.hasDraft()) {
                this.ensureDraftStructure();
                return true;
            }

            this.loading = true;
            this.errors = [];

            try {
                const response = await fetch(config.routes.create, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({}),
                });

                const data = await this.parseJsonResponse(response);

                if (!response.ok || data.success === false) {
                    this.errors = this.normalizeErrors(data, 'No fue posible crear el borrador.');
                    return false;
                }

                this.draft = {
                    ...this.emptyThicknessDraft(),
                    ...(data.draft || {}),
                    lines: Array.isArray(data.draft?.lines) && data.draft.lines.length
                        ? data.draft.lines.map((line, index) => ({
                            ...this.emptyThicknessLine(index + 1),
                            ...(line || {}),
                            cover_number: line?.cover_number ?? (index + 1),
                        }))
                        : [this.emptyThicknessLine(1)],
                };

                this.showCrudToast('Borrador creado correctamente.');
                return true;
            } catch (error) {
                this.errors = ['Ocurrió un error de red al crear el borrador.'];
                return false;
            } finally {
                this.loading = false;
            }
        },

        async saveDraft() {
            this.ensureDraftStructure();
            this.loading = true;
            this.errors = [];

            try {
                const response = await fetch(config.routes.update, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        lines: this.draft.lines,
                    }),
                });

                const data = await this.parseJsonResponse(response);

                if (!response.ok || data.success === false) {
                    this.errors = this.normalizeErrors(data, 'No fue posible guardar el borrador.');
                    this.showCrudToast(this.errors[0] || 'No fue posible guardar el borrador.', 'error');
                    return false;
                }

                this.draft = {
                    ...this.emptyThicknessDraft(),
                    ...(data.draft || {}),
                    lines: Array.isArray(data.draft?.lines) && data.draft.lines.length
                        ? data.draft.lines.map((line, index) => ({
                            ...this.emptyThicknessLine(index + 1),
                            ...(line || {}),
                            cover_number: line?.cover_number ?? (index + 1),
                        }))
                        : [this.emptyThicknessLine(1)],
                };

                this.showCrudToast('Borrador guardado correctamente.');
                return true;
            } catch (error) {
                this.errors = ['Ocurrió un error de red al guardar el borrador.'];
                return false;
            } finally {
                this.loading = false;
            }
        },

        async addCover() {
            const ok = await this.createDraftIfNeeded();
            if (!ok) return;

            this.loading = true;
            this.errors = [];

            try {
                const response = await fetch(config.routes.addCover, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({}),
                });

                const data = await this.parseJsonResponse(response);

                if (!response.ok || data.success === false) {
                    this.errors = this.normalizeErrors(data, 'No fue posible agregar la cubierta.');
                    return false;
                }

                this.draft = {
                    ...this.emptyThicknessDraft(),
                    ...(data.draft || {}),
                    lines: Array.isArray(data.draft?.lines) && data.draft.lines.length
                        ? data.draft.lines.map((line, index) => ({
                            ...this.emptyThicknessLine(index + 1),
                            ...(line || {}),
                            cover_number: line?.cover_number ?? (index + 1),
                        }))
                        : [this.emptyThicknessLine(1)],
                };

                this.showCrudToast('Cubierta agregada correctamente.');
                return true;
            } catch (error) {
                this.errors = ['Ocurrió un error de red al agregar la cubierta.'];
                return false;
            } finally {
                this.loading = false;
            }
        },

        async removeCover(coverNumber) {
            const url = config.routes.removeCoverTemplate.replace('__COVER__', coverNumber);

            this.loading = true;
            this.errors = [];

            try {
                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': this.csrf(),
                        'Accept': 'application/json',
                    },
                });

                const data = await this.parseJsonResponse(response);

                if (!response.ok || data.success === false) {
                    this.errors = this.normalizeErrors(data, 'No fue posible eliminar la cubierta.');
                    return false;
                }

                this.draft = {
                    ...this.emptyThicknessDraft(),
                    ...(data.draft || {}),
                    lines: Array.isArray(data.draft?.lines) && data.draft.lines.length
                        ? data.draft.lines.map((line, index) => ({
                            ...this.emptyThicknessLine(index + 1),
                            ...(line || {}),
                            cover_number: line?.cover_number ?? (index + 1),
                        }))
                        : [this.emptyThicknessLine(1)],
                };

                this.showCrudToast('Cubierta eliminada correctamente.');
                return true;
            } catch (error) {
                this.errors = ['Ocurrió un error de red al eliminar la cubierta.'];
                return false;
            } finally {
                this.loading = false;
            }
        },

        openPublishConfirm() {
            this.publishForm.report_date = config.today;
            this.publishConfirmOpen = true;
        },

        closePublishConfirm() {
            this.publishConfirmOpen = false;
        },

        async publishDraft() {
            this.ensureDraftStructure();
            this.loading = true;
            this.errors = [];

            try {
                const saveResponse = await fetch(config.routes.update, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        lines: this.draft.lines,
                    }),
                });

                const saveData = await this.parseJsonResponse(saveResponse);

                if (!saveResponse.ok || saveData.success === false) {
                    this.errors = this.normalizeErrors(saveData, 'No fue posible guardar el borrador antes de publicar.');

                    this.showCrudToast(
                        'Revisa los datos del borrador antes de publicar.',
                        'error'
                    );

                    return false;
                }

                this.draft = {
                    ...this.emptyThicknessDraft(),
                    ...(saveData.draft || {}),
                    lines: Array.isArray(saveData.draft?.lines) && saveData.draft.lines.length
                        ? saveData.draft.lines.map((line, index) => ({
                            ...this.emptyThicknessLine(index + 1),
                            ...(line || {}),
                            cover_number: line?.cover_number ?? (index + 1),
                        }))
                        : [this.emptyThicknessLine(1)],
                };

                const publishResponse = await fetch(config.routes.publish, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        report_date: this.publishForm.report_date || config.today,
                    }),
                });

                const publishData = await this.parseJsonResponse(publishResponse);

                if (!publishResponse.ok || publishData.success === false) {
                    this.errors = this.normalizeErrors(publishData, 'No fue posible publicar el reporte.');

                    this.showCrudToast(
                        'Faltan datos obligatorios para publicar el reporte.',
                        'error'
                    );

                    return false;
                }

                this.latestReport = publishData.report ?? null;
                this.historicalReports = Array.isArray(publishData.historical_reports)
                    ? publishData.historical_reports
                    : this.historicalReports;

                this.draft = this.emptyThicknessDraft();
                this.publishConfirmOpen = false;
                this.draftModalOpen = false;
                this.publishForm.report_date = config.today;

                this.showCrudToast('Reporte publicado correctamente.');
                this.refreshLucide();

                return true;
            } catch (error) {
                this.errors = ['Ocurrió un error de red al publicar el reporte.'];
                this.showCrudToast(this.errors[0], 'error');
                return false;
            } finally {
                this.loading = false;
            }
        },

        openHistoryModal() {
            this.historyModalOpen = true;
            this.historySidebarOpen = true;

            const reports = Array.isArray(this.historicalReports)
                ? [...this.historicalReports].sort((a, b) => {
                    const dateCompare = String(b.report_date || '').localeCompare(String(a.report_date || ''));

                    if (dateCompare !== 0) {
                        return dateCompare;
                    }

                    return Number(b.id || 0) - Number(a.id || 0);
                })
                : [];

            if (reports.length > 0) {
                this.selectHistoricalReport(reports[0].id);
            }

            this.refreshLucide();
        },

        closeHistoryModal() {
            this.historyModalOpen = false;
        },

        async selectHistoricalReport(reportId) {
            if (!reportId) return;

            if (
                this.selectedHistoryReport &&
                String(this.selectedHistoryReport.id) === String(reportId)
            ) {
                return;
            }

            const url = config.routes.historyShowTemplate.replace('__REPORT__', reportId);
            const hadPreviousSelection = !!this.selectedHistoryReport;

            this.historyLoading = !hadPreviousSelection;

            try {
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                    },
                });

                const data = await this.parseJsonResponse(response);

                if (!response.ok || data.success === false) {
                    return;
                }

                if (data.report) {
                    this.selectedHistoryReport = data.report;
                }
            } catch (error) {
                if (!hadPreviousSelection) {
                    this.selectedHistoryReport = null;
                }
            } finally {
                this.historyLoading = false;
            }
        },

        openHistoryEditModal() {
            if (!this.selectedHistoryReport) {
                return;
            }

            this.historyEditErrors = [];

            this.historyEditForm = {
                id: this.selectedHistoryReport.id,
                report_date: this.selectedHistoryReport.report_date || config.today,
                lines: Array.isArray(this.selectedHistoryReport.lines)
                    ? this.selectedHistoryReport.lines.map((line, index) => ({
                        ...this.emptyThicknessLine(index + 1),
                        ...(line || {}),
                        cover_number: line?.cover_number ?? (index + 1),
                    }))
                    : [this.emptyThicknessLine(1)],
            };

            this.historyEditModalOpen = true;
            this.refreshLucide();
        },

        closeHistoryEditModal() {
            this.historyEditModalOpen = false;
            this.historyEditErrors = [];
        },

        addHistoryEditCover() {
            const lines = Array.isArray(this.historyEditForm.lines)
                ? this.historyEditForm.lines
                : [];

            this.historyEditForm.lines = [
                ...lines,
                this.emptyThicknessLine(lines.length + 1),
            ];
        },

        removeHistoryEditCover(coverNumber) {
            const lines = Array.isArray(this.historyEditForm.lines)
                ? this.historyEditForm.lines
                : [];

            if (lines.length <= 1) {
                this.showCrudToast('El reporte debe conservar al menos una cubierta.', 'error');
                return;
            }

            this.historyEditForm.lines = lines
                .filter(line => Number(line.cover_number) !== Number(coverNumber))
                .map((line, index) => ({
                    ...line,
                    cover_number: index + 1,
                }));
        },

        async updateThicknessHistoricalReport() {
            if (!this.historyEditForm?.id) {
                return false;
            }

            this.loading = true;
            this.historyEditErrors = [];

            const url = config.routes.historyUpdateTemplate.replace('__REPORT__', this.historyEditForm.id);

            try {
                const response = await fetch(url, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        report_date: this.historyEditForm.report_date,
                        lines: this.historyEditForm.lines,
                    }),
                });

                const data = await this.parseJsonResponse(response);

                if (!response.ok || data.success === false) {
                    this.historyEditErrors = this.normalizeErrors(data, 'No fue posible actualizar el reporte.');
                    this.showCrudToast('Revisa los datos antes de guardar cambios.', 'error');
                    return false;
                }

                this.latestReport = data.latest_report ?? this.latestReport;
                this.historicalReports = Array.isArray(data.reports)
                    ? data.reports
                    : this.historicalReports;

                this.selectedHistoryReport = data.report ?? this.selectedHistoryReport;
                this.historyEditModalOpen = false;

                this.showCrudToast(data.message || 'Reporte actualizado correctamente.');
                this.refreshLucide();

                return true;
            } catch (error) {
                this.historyEditErrors = ['Ocurrió un error de red al actualizar el reporte.'];
                this.showCrudToast(this.historyEditErrors[0], 'error');
                return false;
            } finally {
                this.loading = false;
            }
        },

        async deleteThicknessHistoricalReport() {
            if (!this.selectedHistoryReport?.id) {
                return false;
            }

            const confirmed = window.confirm('¿Eliminar este reporte oficial? Esta acción no se puede deshacer.');

            if (!confirmed) {
                return false;
            }

            this.loading = true;
            this.historyEditErrors = [];
            this.errors = [];

            const deletedId = this.selectedHistoryReport.id;
            const url = config.routes.historyDeleteTemplate.replace('__REPORT__', deletedId);

            try {
                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': this.csrf(),
                        'Accept': 'application/json',
                    },
                });

                const data = await this.parseJsonResponse(response);

                if (!response.ok || data.success === false) {
                    this.errors = this.normalizeErrors(data, 'No fue posible eliminar el reporte.');
                    this.showCrudToast(this.errors[0] || 'No fue posible eliminar el reporte.', 'error');
                    return false;
                }

                this.latestReport = data.latest_report ?? null;
                this.historicalReports = Array.isArray(data.reports)
                    ? data.reports
                    : this.historicalReports.filter(report => String(report.id) !== String(deletedId));

                this.selectedHistoryReport = null;

                if (this.historicalReports.length > 0) {
                    const reports = [...this.historicalReports].sort((a, b) => {
                        const dateCompare = String(b.report_date || '').localeCompare(String(a.report_date || ''));

                        if (dateCompare !== 0) {
                            return dateCompare;
                        }

                        return Number(b.id || 0) - Number(a.id || 0);
                    });

                    await this.selectHistoricalReport(reports[0].id);
                }

                this.showCrudToast(data.message || 'Reporte eliminado correctamente.');
                this.refreshLucide();

                return true;
            } catch (error) {
                this.errors = ['Ocurrió un error de red al eliminar el reporte.'];
                this.showCrudToast(this.errors[0], 'error');
                return false;
            } finally {
                this.loading = false;
            }
        },

        numericValues(values) {
            return (values || [])
                .filter(value => value !== null && value !== undefined && value !== '')
                .map(value => Number(value))
                .filter(value => !Number.isNaN(value));
        },

        maxValue(values) {
            const parsed = this.numericValues(values);
            if (!parsed.length) return null;
            return Math.max(...parsed).toFixed(2);
        },

        minValue(values) {
            const parsed = this.numericValues(values);
            if (!parsed.length) return null;
            return Math.min(...parsed).toFixed(2);
        },

        calculateSufficiency(minValue, side = 'top') {
            if (minValue === null || minValue === undefined || minValue === '') return null;

            const numeric = Number(minValue);
            if (Number.isNaN(numeric) || numeric <= 0) return null;

            const latest = this.latestBandStateReport;
            if (!latest) return null;

            const reference = side === 'bottom'
                ? Number(latest.bottom_cover)
                : Number(latest.top_cover);

            if (Number.isNaN(reference) || reference <= 0) return null;

            return `${((numeric / reference) * 100).toFixed(2)}%`;
        },
                // ======================
        // BAND STATE HELPERS
        // ======================
        emptyBandStateDraft() {
            return {
                id: null,
                element_id: config.elementId,
                description: '',
                width: '',
                top_cover: '',
                bottom_cover: '',
            };
        },

        ensureBandStateDraftStructure() {
            if (!this.bandStateDraft || typeof this.bandStateDraft !== 'object') {
                this.bandStateDraft = this.emptyBandStateDraft();
                return;
            }

            this.bandStateDraft = {
                ...this.emptyBandStateDraft(),
                ...this.bandStateDraft,
            };
        },

        hasBandStateDraft() {
            return !!(this.bandStateDraft && this.bandStateDraft.id);
        },

        async createBandStateDraftIfNeeded() {
            if (this.hasBandStateDraft()) {
                this.ensureBandStateDraftStructure();
                return true;
            }

            this.loading = true;
            this.bandStateErrors = [];

            try {
                const response = await fetch(config.routes.bandStateCreate, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({}),
                });

                const data = await this.parseJsonResponse(response);

                if (!response.ok || data.success === false) {
                    this.bandStateErrors = this.normalizeErrors(data, 'No fue posible crear el borrador del informe de estado.');
                    return false;
                }

                this.bandStateDraft = {
                    ...this.emptyBandStateDraft(),
                    ...(data.draft || {}),
                };

                this.showCrudToast('Borrador del informe de estado creado correctamente.');
                return true;
            } catch (error) {
                this.bandStateErrors = ['Ocurrió un error de red al crear el borrador del informe de estado.'];
                return false;
            } finally {
                this.loading = false;
            }
        },

        async openBandStateDraftModal() {
            this.bandStateErrors = [];

            const ok = await this.createBandStateDraftIfNeeded();
            if (!ok) return;

            this.ensureBandStateDraftStructure();
            this.bandStateDraftModalOpen = true;
            this.refreshLucide();
        },

        closeBandStateDraftModal() {
            this.bandStateDraftModalOpen = false;
            this.bandStateErrors = [];
        },

        async saveBandStateDraft() {
            this.ensureBandStateDraftStructure();
            this.loading = true;
            this.bandStateErrors = [];

            try {
                const response = await fetch(config.routes.bandStateUpdate, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        description: this.bandStateDraft.description,
                        width: this.bandStateDraft.width,
                        top_cover: this.bandStateDraft.top_cover,
                        bottom_cover: this.bandStateDraft.bottom_cover,
                    }),
                });

                const data = await this.parseJsonResponse(response);

                if (!response.ok || data.success === false) {
                    this.bandStateErrors = this.normalizeErrors(data, 'No fue posible guardar el borrador del informe de estado.');
                    return false;
                }

                this.bandStateDraft = {
                    ...this.emptyBandStateDraft(),
                    ...(data.draft || {}),
                };

                this.showCrudToast('Borrador del informe de estado guardado correctamente.');
                return true;
            } catch (error) {
                this.bandStateErrors = ['Ocurrió un error de red al guardar el borrador del informe de estado.'];
                return false;
            } finally {
                this.loading = false;
            }
        },

        openBandStatePublishConfirm() {
            this.bandStatePublishForm.report_date = config.today;
            this.bandStatePublishConfirmOpen = true;
        },

        closeBandStatePublishConfirm() {
            this.bandStatePublishConfirmOpen = false;
        },

        async publishBandStateDraft() {
            this.ensureBandStateDraftStructure();
            this.loading = true;
            this.bandStateErrors = [];

            try {
                const saveResponse = await fetch(config.routes.bandStateUpdate, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        description: this.bandStateDraft.description,
                        width: this.bandStateDraft.width,
                        top_cover: this.bandStateDraft.top_cover,
                        bottom_cover: this.bandStateDraft.bottom_cover,
                    }),
                });

                const saveData = await this.parseJsonResponse(saveResponse);

                if (!saveResponse.ok || saveData.success === false) {
                    this.bandStateErrors = this.normalizeErrors(saveData, 'No fue posible guardar el borrador antes de publicar.');
                    return false;
                }

                this.bandStateDraft = {
                    ...this.emptyBandStateDraft(),
                    ...(saveData.draft || {}),
                };

                const publishResponse = await fetch(config.routes.bandStatePublish, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        report_date: this.bandStatePublishForm.report_date || config.today,
                    }),
                });

                const publishData = await this.parseJsonResponse(publishResponse);

                if (!publishResponse.ok || publishData.success === false) {
                    this.bandStateErrors = this.normalizeErrors(publishData, 'No fue posible publicar el informe de estado.');
                    return false;
                }

                this.latestBandStateReport = publishData.latest_report ?? publishData.report ?? null;

                await this.refreshBandStateHistoricalReports(
                    publishData.report?.id ?? this.latestBandStateReport?.id ?? null
                );

                this.bandStateDraft = this.emptyBandStateDraft();
                this.bandStatePublishConfirmOpen = false;
                this.bandStateDraftModalOpen = false;

                this.showCrudToast('Informe de estado publicado correctamente.');
                this.refreshLucide();

                return true;
            } catch (error) {
                this.bandStateErrors = ['Ocurrió un error de red al publicar el informe de estado.'];
                return false;
            } finally {
                this.loading = false;
            }
        },

        async refreshBandStateHistoricalReports(preferredReportId = null) {
            try {
                const response = await fetch(config.routes.bandStateHistoryIndex, {
                    headers: {
                        'Accept': 'application/json',
                    },
                });

                const data = await this.parseJsonResponse(response);

                if (!response.ok || data.success === false) {
                    return false;
                }

                this.bandStateHistoricalReports = Array.isArray(data.reports)
                    ? data.reports
                    : [];

                const reports = [...this.bandStateHistoricalReports].sort((a, b) => {
                    const dateCompare = String(b.report_date || '').localeCompare(String(a.report_date || ''));

                    if (dateCompare !== 0) {
                        return dateCompare;
                    }

                    return Number(b.id || 0) - Number(a.id || 0);
                });

                const selectedId = preferredReportId || reports[0]?.id || null;

                if (selectedId) {
                    await this.selectBandStateHistoricalReport(selectedId);
                }

                return true;
            } catch (error) {
                return false;
            }
        },
        openBandStateHistoryModal() {
            this.bandStateHistoryModalOpen = true;
            this.selectedBandStateHistoryReport = null;
            this.bandStateHistoryLoading = false;

            const reports = Array.isArray(this.bandStateHistoricalReports)
                ? [...this.bandStateHistoricalReports].sort((a, b) => {
                    const dateCompare = String(b.report_date || '').localeCompare(String(a.report_date || ''));

                    if (dateCompare !== 0) {
                        return dateCompare;
                    }

                    return Number(b.id || 0) - Number(a.id || 0);
                })
                : [];

            if (reports.length > 0) {
                this.$nextTick(() => {
                    this.selectBandStateHistoricalReport(reports[0].id);
                });
            }

            this.refreshLucide();
        },

        closeBandStateHistoryModal() {
            this.bandStateHistoryModalOpen = false;
        },

        async selectBandStateHistoricalReport(reportId) {
            if (!reportId) return;

            const url = config.routes.bandStateHistoryShowTemplate.replace('__REPORT__', reportId);

            this.bandStateHistoryLoading = true;

            try {
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                    },
                });

                const data = await this.parseJsonResponse(response);

                if (!response.ok || data.success === false || !data.report) {
                    this.selectedBandStateHistoryReport = null;
                    this.showCrudToast(
                        data.message || 'No fue posible cargar el detalle del histórico.',
                        'error'
                    );
                    return false;
                }

                this.selectedBandStateHistoryReport = data.report;
                this.refreshLucide();

                return true;
            } catch (error) {
                this.selectedBandStateHistoryReport = null;
                this.showCrudToast('Ocurrió un error de red al cargar el histórico.', 'error');
                return false;
            } finally {
                this.bandStateHistoryLoading = false;
            }
        },

        openBandStateEditModal() {
            if (!this.selectedBandStateHistoryReport) {
                return;
            }

            this.bandStateEditErrors = [];

            this.bandStateEditForm = {
                id: this.selectedBandStateHistoryReport.id,
                report_date: this.selectedBandStateHistoryReport.report_date || config.today,
                description: this.selectedBandStateHistoryReport.description || '',
                width: this.selectedBandStateHistoryReport.width || '',
                top_cover: this.selectedBandStateHistoryReport.top_cover || '',
                bottom_cover: this.selectedBandStateHistoryReport.bottom_cover || '',
            };

            this.bandStateEditModalOpen = true;
            this.refreshLucide();
        },

        closeBandStateEditModal() {
            this.bandStateEditModalOpen = false;
            this.bandStateEditErrors = [];
        },

        async updateBandStateHistoricalReport() {
            if (!this.bandStateEditForm?.id) {
                return false;
            }

            this.loading = true;
            this.bandStateEditErrors = [];

            const url = config.routes.bandStateHistoryUpdateTemplate.replace('__REPORT__', this.bandStateEditForm.id);

            try {
                const response = await fetch(url, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        report_date: this.bandStateEditForm.report_date,
                        description: this.bandStateEditForm.description,
                        width: this.bandStateEditForm.width,
                        top_cover: this.bandStateEditForm.top_cover,
                        bottom_cover: this.bandStateEditForm.bottom_cover,
                    }),
                });

                const data = await this.parseJsonResponse(response);

                if (!response.ok || data.success === false) {
                    this.bandStateEditErrors = this.normalizeErrors(data, 'No fue posible actualizar el reporte.');
                    return false;
                }

                this.latestBandStateReport = data.latest_report ?? this.latestBandStateReport;
                this.bandStateHistoricalReports = Array.isArray(data.reports)
                    ? data.reports
                    : this.bandStateHistoricalReports;

                this.selectedBandStateHistoryReport = data.report ?? this.selectedBandStateHistoryReport;

                this.bandStateEditModalOpen = false;

                this.showCrudToast(data.message || 'Reporte actualizado correctamente.');
                this.refreshLucide();

                return true;
            } catch (error) {
                this.bandStateEditErrors = ['Ocurrió un error de red al actualizar el reporte.'];
                return false;
            } finally {
                this.loading = false;
            }
        },

        async deleteBandStateHistoricalReport() {
            if (!this.selectedBandStateHistoryReport?.id) {
                return false;
            }

            const confirmed = window.confirm('¿Eliminar este reporte oficial? Esta acción no se puede deshacer.');

            if (!confirmed) {
                return false;
            }

            this.loading = true;
            this.bandStateEditErrors = [];
            this.bandStateErrors = [];

            const deletedId = this.selectedBandStateHistoryReport.id;
            const url = config.routes.bandStateHistoryDeleteTemplate.replace('__REPORT__', deletedId);

            try {
                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': this.csrf(),
                        'Accept': 'application/json',
                    },
                });

                const data = await this.parseJsonResponse(response);

                if (!response.ok || data.success === false) {
                    this.bandStateErrors = this.normalizeErrors(data, 'No fue posible eliminar el reporte.');
                    return false;
                }

                this.latestBandStateReport = data.latest_report ?? null;
                this.bandStateHistoricalReports = Array.isArray(data.reports)
                    ? data.reports
                    : this.bandStateHistoricalReports.filter(report => String(report.id) !== String(deletedId));

                this.selectedBandStateHistoryReport = null;

                if (this.bandStateHistoricalReports.length > 0) {
                    const reports = [...this.bandStateHistoricalReports].sort((a, b) => {
                        const dateCompare = String(b.report_date || '').localeCompare(String(a.report_date || ''));

                        if (dateCompare !== 0) {
                            return dateCompare;
                        }

                        return Number(b.id || 0) - Number(a.id || 0);
                    });

                    await this.selectBandStateHistoricalReport(reports[0].id);
                }

                this.showCrudToast(data.message || 'Reporte eliminado correctamente.');
                this.refreshLucide();

                return true;
            } catch (error) {
                this.bandStateErrors = ['Ocurrió un error de red al eliminar el reporte.'];
                return false;
            } finally {
                this.loading = false;
            }
        },


        // ======================
        // BAND EVENTS HELPERS
        // ======================
        bandWizardTotalSteps() {
            return 3;
        },

        compactBandSteps() {
            return [
                {
                    id: 1,
                    title: this.bandType === 'band' ? 'Inicio' : 'Banda asociada',
                },
                {
                    id: 2,
                    title: 'Captura técnica',
                },
                {
                    id: 3,
                    title: 'Cierre y publicación',
                },
            ];
        },

        bandWizardTitle() {
            return {
                band: 'Cambio de banda',
                vulcanization: 'Vulcanizado',
                section_change: 'Cambio de tramo',
            }[this.bandType] || 'Cambio de banda';
        },

        bandEventTypeLabel(type) {
            return {
                band: 'Cambio de banda',
                vulcanization: 'Vulcanizado',
                section_change: 'Cambio de tramo',
            }[type] || 'Evento';
        },

        latestBandChangeReport() {
            const officialBands = this.safeArray(this.bandHistoricalTree)
                .filter(item => item && item.type === 'band' && item.report_date);

            if (!officialBands.length) {
                return null;
            }

            return [...officialBands].sort((a, b) => {
                const dateCompare = String(b.report_date || '').localeCompare(String(a.report_date || ''));

                if (dateCompare !== 0) {
                    return dateCompare;
                }

                return Number(b.id || 0) - Number(a.id || 0);
            })[0];
        },

        bandOptionLabel(band) {
            if (!band) return '—';

            const date = band.report_date ? this.formatDate(band.report_date) : 'Sin fecha';
            const brand = band.brand || 'Sin marca';
            const rolls = band.roll_count ?? '—';

            return `${date} · ${brand} · Rollos: ${rolls}`;
        },

        bandActiveBandLabel() {
            if (!this.bandEventActiveBand) {
                return 'No hay banda activa oficial';
            }

            return this.bandOptionLabel(this.bandEventActiveBand);
        },

        bandParentLabel() {
            if (this.bandType === 'band') {
                return 'Nueva banda padre';
            }

            const parentId = this.bandDraft?.parent_id;
            if (!parentId) {
                return this.bandActiveBandLabel();
            }

            const found = (this.bandEventBands || []).find(band => String(band.id) === String(parentId));
            return found ? this.bandOptionLabel(found) : 'Banda no encontrada';
        },

        ensureBandDraftStructure(type = null) {
            const resolvedType = type || this.bandType || 'band';

            if (!this.bandDraft || typeof this.bandDraft !== 'object') {
                this.bandDraft = this.emptyBandDraft(resolvedType);
                return;
            }

            this.bandDraft = {
                ...this.emptyBandDraft(resolvedType),
                ...this.bandDraft,
                type: this.bandDraft.type || resolvedType,
            };

            if (resolvedType !== 'band' && !this.bandDraft.parent_id && this.bandEventActiveBand?.id) {
                this.bandDraft.parent_id = this.bandEventActiveBand.id;
            }

            if (resolvedType === 'band') {
                this.bandDraft.parent_id = null;
            }
        },

        resetBandErrors() {
            this.bandErrors = [];
        },

        openBandHistoryModal() {
            this.bandHistoryModalOpen = true;

            if (!this.selectedBandHistory && this.bandHistoricalTree.length > 0) {
                this.selectedBandHistory = this.bandHistoricalTree[0];
            }

            this.refreshLucide();
        },

        closeBandHistoryModal() {
            this.bandHistoryModalOpen = false;
        },

        selectBandHistory(band) {
            if (!band) {
                this.selectedBandHistory = null;
                return;
            }

            if (
                this.selectedBandHistory &&
                String(this.selectedBandHistory.id) === String(band.id)
            ) {
                return;
            }

            this.selectedBandHistory = band;
        },

        openBandEditModal(event) {
            if (!event || !event.id) {
                return;
            }

            this.bandEditErrors = [];

            this.bandEditForm = {
                ...this.emptyBandDraft(event.type || 'band'),
                ...event,
                parent_id: event.type === 'band'
                    ? null
                    : (event.parent_id || this.selectedBandHistory?.id || this.bandEventActiveBand?.id || null),
                type: event.type || 'band',
                report_date: event.report_date || config.today,
                same_reference: !!event.same_reference,
            };

            this.bandEditModalOpen = true;
            this.refreshLucide();
        },

        closeBandEditModal() {
            this.bandEditModalOpen = false;
            this.bandEditErrors = [];
            this.bandEditForm = null;
        },

        async updateBandHistoricalEvent() {
            if (!this.bandEditForm?.id) {
                return false;
            }

            this.loading = true;
            this.bandEditErrors = [];

            const url = config.routes.bandReportUpdateTemplate.replace('__EVENT__', this.bandEditForm.id);

            try {
                const response = await fetch(url, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        ...this.bandEditForm,
                        type: this.bandEditForm.type,
                        report_date: this.bandEditForm.report_date || config.today,
                    }),
                });

                const data = await this.parseJsonResponse(response);

                if (!response.ok || data.success === false) {
                    this.bandEditErrors = this.normalizeErrors(data, 'No fue posible actualizar el evento.');
                    this.showCrudToast(
                        this.bandEditErrors[0] || 'No fue posible actualizar el evento.',
                        'error'
                    );
                    return false;
                }

                if (data.latest_report || data.active_band || data.bands || data.historical_tree) {
                    this.refreshBandCollections(data);
                }

                if (data.report?.type === 'band') {
                    const found = this.bandHistoricalTree.find(item => String(item.id) === String(data.report.id));
                    this.selectedBandHistory = found || this.selectedBandHistory;
                } else if (data.report?.parent_id) {
                    const parent = this.bandHistoricalTree.find(item => String(item.id) === String(data.report.parent_id));
                    this.selectedBandHistory = parent || this.selectedBandHistory;
                }

                this.bandEditModalOpen = false;
                this.bandEditForm = null;

                this.showCrudToast(data.message || 'Evento actualizado correctamente.');
                this.refreshLucide();

                return true;
            } catch (error) {
                this.bandEditErrors = ['Ocurrió un error de red al actualizar el evento.'];
                this.showCrudToast(this.bandEditErrors[0], 'error');
                return false;
            } finally {
                this.loading = false;
            }
        },

        async deleteBandHistoricalEvent(event) {
            if (!event?.id) {
                return false;
            }

            const label = this.bandEventTypeLabel(event.type || 'band');
            const confirmed = window.confirm(`¿Eliminar este evento oficial (${label})? Esta acción no se puede deshacer.`);

            if (!confirmed) {
                return false;
            }

            this.loading = true;
            this.bandEditErrors = [];
            this.bandErrors = [];

            const deletedId = event.id;
            const deletedParentId = event.parent_id || null;
            const deletedType = event.type || 'band';
            const url = config.routes.bandReportDeleteTemplate.replace('__EVENT__', deletedId);

            try {
                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': this.csrf(),
                        'Accept': 'application/json',
                    },
                });

                const data = await this.parseJsonResponse(response);

                if (!response.ok || data.success === false) {
                    const errors = this.normalizeErrors(data, 'No fue posible eliminar el evento.');
                    this.showCrudToast(errors[0] || 'No fue posible eliminar el evento.', 'error');
                    return false;
                }

                if (data.latest_report || data.active_band || data.bands || data.historical_tree) {
                    this.refreshBandCollections(data);
                }

                if (deletedType === 'band') {
                    this.selectedBandHistory = this.bandHistoricalTree[0] ?? null;
                } else if (deletedParentId) {
                    const parent = this.bandHistoricalTree.find(item => String(item.id) === String(deletedParentId));
                    this.selectedBandHistory = parent || (this.bandHistoricalTree[0] ?? null);
                }

                this.showCrudToast(data.message || 'Evento eliminado correctamente.');
                this.refreshLucide();

                return true;
            } catch (error) {
                this.showCrudToast('Ocurrió un error de red al eliminar el evento.', 'error');
                return false;
            } finally {
                this.loading = false;
            }
        },

        syncBandLatestStateAfterPublish(report = null) {
            if (!report) return;

            this.bandEventLatestReport = report;

            if (report.type === 'band') {
                this.bandEventActiveBand = report;
            } else {
                const parentId = report.parent_id;
                const foundParent = (this.bandEventBands || []).find(band => String(band.id) === String(parentId));
                if (foundParent) {
                    this.bandEventActiveBand = foundParent;
                }
            }
        },

        sortBandEventsDesc(events = []) {
            return [...events].sort((a, b) => {
                const dateA = a?.report_date || '';
                const dateB = b?.report_date || '';

                if (dateA !== dateB) {
                    return dateB.localeCompare(dateA);
                }

                const publishedA = a?.published_at || '';
                const publishedB = b?.published_at || '';

                if (publishedA !== publishedB) {
                    return publishedB.localeCompare(publishedA);
                }

                return Number(b?.id || 0) - Number(a?.id || 0);
            });
        },

        sortBandChildrenAsc(events = []) {
            return [...events].sort((a, b) => {
                const dateA = a?.report_date || '';
                const dateB = b?.report_date || '';

                if (dateA !== dateB) {
                    return dateA.localeCompare(dateB);
                }

                const publishedA = a?.published_at || '';
                const publishedB = b?.published_at || '';

                if (publishedA !== publishedB) {
                    return publishedA.localeCompare(publishedB);
                }

                return Number(a?.id || 0) - Number(b?.id || 0);
            });
        },

        normalizeBandSummary(item = {}) {
            return {
                id: item.id ?? null,
                parent_id: item.parent_id ?? null,
                type: item.type ?? 'band',
                brand: item.brand ?? '',
                width: item.width ?? '',
                length: item.length ?? '',
                roll_count: item.roll_count ?? '',
                report_date: item.report_date ?? null,
                published_at: item.published_at ?? null,
                observation: item.observation ?? '',
            };
        },

        normalizeBandEvent(item = {}) {
            return {
                ...this.emptyBandDraft(item.type || 'band'),
                ...item,
                children: Array.isArray(item.children)
                    ? this.sortBandChildrenAsc(item.children.map(child => ({
                        ...this.emptyBandDraft(child.type || 'band'),
                        ...child,
                    })))
                    : [],
            };
        },

        refreshBandCollections(payload = {}) {
            if (payload.latest_report) {
                this.bandEventLatestReport = this.normalizeBandEvent(payload.latest_report);
            }

            if (payload.active_band) {
                this.bandEventActiveBand = this.normalizeBandEvent(payload.active_band);
            }

            if (Array.isArray(payload.bands)) {
                this.bandEventBands = this.sortBandEventsDesc(
                    payload.bands.map(item => this.normalizeBandSummary(item))
                );
            }

            if (Array.isArray(payload.historical_tree)) {
                this.bandHistoricalTree = this.sortBandEventsDesc(
                    payload.historical_tree.map(item => this.normalizeBandEvent(item))
                );

                if (this.selectedBandHistory?.id) {
                    const found = this.bandHistoricalTree.find(item => item.id === this.selectedBandHistory.id);
                    this.selectedBandHistory = found || (this.bandHistoricalTree[0] ?? null);
                } else if (!this.selectedBandHistory && this.bandHistoricalTree.length > 0) {
                    this.selectedBandHistory = this.bandHistoricalTree[0];
                }
            }
        },

        nextBandStep() {
            if (this.bandWizardStep < this.bandWizardTotalSteps()) {
                this.bandWizardStep += 1;
                this.refreshLucide();
            }
        },

        prevBandStep() {
            if (this.bandWizardStep > 1) {
                this.bandWizardStep -= 1;
                this.refreshLucide();
            }
        },
                // ======================
        // BAND EVENTS FLOW
        // ======================
        async openBandWizard(type = 'band') {
            this.bandWizardStep = 1;
            this.resetBandErrors();
            this.bandType = type;

            if (!this.bandDraft || typeof this.bandDraft !== 'object') {
                this.bandDraft = this.emptyBandDraft(type);
            } else {
                this.bandDraft = {
                    ...this.emptyBandDraft(type),
                    ...this.bandDraft,
                    type,
                };
            }

            const ok = await this.createBandDraft(type);

            if (!ok || !this.bandDraft) {
                return;
            }

            this.bandWizardOpen = true;
            this.refreshLucide();
        },

        closeBandWizard() {
            this.bandWizardOpen = false;
            this.resetBandErrors();
        },

        async changeBandWizardType(type) {
            if (this.bandType === type) return;

            this.resetBandErrors();
            this.bandType = type;
            this.bandDraft = this.emptyBandDraft(type);

            const ok = await this.createBandDraft(type);

            if (ok) {
                this.bandWizardStep = 1;
                this.refreshLucide();
            }
        },

        async createBandDraft(type = 'band') {
            this.loading = true;
            this.resetBandErrors();

            try {
                const response = await fetch(config.routes.bandCreate, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        type,
                    }),
                });

                const data = await this.parseJsonResponse(response);

                if (!response.ok || data.success === false) {
                    this.bandErrors = this.normalizeErrors(data, 'No fue posible preparar el borrador del evento.');
                    return false;
                }

                this.bandType = type;
                this.bandDraft = {
                    ...this.emptyBandDraft(type),
                    ...(data.draft || {}),
                    type,
                    report_date: data.draft?.report_date || config.today,
                };

                this.ensureBandDraftStructure(type);

                return true;
            } catch (error) {
                this.bandErrors = ['Ocurrió un error de red al crear u obtener el borrador del evento.'];
                return false;
            } finally {
                this.loading = false;
            }
        },
                // ======================
        // BAND EVENTS SAVE / PUBLISH
        // ======================
        async saveBandDraft() {
            this.ensureBandDraftStructure(this.bandType);
            this.loading = true;
            this.resetBandErrors();

            try {
                const response = await fetch(config.routes.bandUpdate, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        ...this.bandDraft,
                        type: this.bandType,
                    }),
                });

                const data = await this.parseJsonResponse(response);

                if (!response.ok || data.success === false) {
                    this.bandErrors = this.normalizeErrors(data, 'No fue posible guardar el borrador del evento.');
                    return false;
                }

                this.bandDraft = {
                    ...this.emptyBandDraft(this.bandType),
                    ...(data.draft || {}),
                    type: this.bandType,
                    report_date: data.draft?.report_date || this.bandDraft?.report_date || config.today,
                };

                this.ensureBandDraftStructure(this.bandType);

                if (data.latest_report || data.active_band || data.bands || data.historical_tree) {
                    this.refreshBandCollections(data);
                }

                this.showCrudToast(data.message || 'Borrador guardado correctamente.');
                return true;
            } catch (error) {
                this.bandErrors = ['Ocurrió un error de red al guardar el borrador del evento.'];
                return false;
            } finally {
                this.loading = false;
            }
        },

        async publishBandDraft() {
            this.ensureBandDraftStructure(this.bandType);
            this.loading = true;
            this.resetBandErrors();

            try {
                const response = await fetch(config.routes.bandPublish, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        ...this.bandDraft,
                        type: this.bandType,
                        report_date: this.bandDraft?.report_date || config.today,
                    }),
                });

                const data = await this.parseJsonResponse(response);

                if (!response.ok || data.success === false) {
                    this.bandErrors = this.normalizeErrors(data, 'No fue posible publicar el reporte del evento.');
                    return false;
                }

                if (data.report) {
                    this.syncBandLatestStateAfterPublish(this.normalizeBandEvent(data.report));
                }

                if (data.latest_report || data.active_band || data.bands || data.historical_tree) {
                    this.refreshBandCollections(data);
                }

                this.bandDraft = this.emptyBandDraft(this.bandType);
                this.bandWizardOpen = false;
                this.bandWizardStep = 1;

                this.showCrudToast(data.message || 'Reporte publicado correctamente.');
                return true;
            } catch (error) {
                this.bandErrors = ['Ocurrió un error de red al publicar el reporte del evento.'];
                return false;
            } finally {
                this.loading = false;
            }
        },
                // ======================
        // INIT / NORMALIZATION
        // ======================
        normalizeInitialState() {
            // Thickness
            if (!this.draft || typeof this.draft !== 'object') {
                this.draft = this.emptyThicknessDraft();
            } else {
                this.draft = {
                    ...this.emptyThicknessDraft(),
                    ...this.draft,
                    lines: Array.isArray(this.draft.lines) && this.draft.lines.length
                        ? this.draft.lines.map((line, index) => ({
                            ...this.emptyThicknessLine(index + 1),
                            ...(line || {}),
                            cover_number: line?.cover_number ?? (index + 1),
                        }))
                        : [this.emptyThicknessLine(1)],
                };
            }

            this.historicalReports = Array.isArray(this.historicalReports)
                ? this.historicalReports
                : [];

            // Band state
            this.bandStateDraft = {
                ...this.emptyBandStateDraft(),
                ...(this.bandStateDraft || {}),
            };

            this.bandStateHistoricalReports = Array.isArray(this.bandStateHistoricalReports)
                ? this.bandStateHistoricalReports
                : [];

            // Band events
            this.bandEventBands = this.sortBandEventsDesc(
                (Array.isArray(this.bandEventBands) ? this.bandEventBands : [])
                    .map(item => this.normalizeBandSummary(item))
            );

            this.bandHistoricalTree = this.sortBandEventsDesc(
                (Array.isArray(this.bandHistoricalTree) ? this.bandHistoricalTree : [])
                    .map(item => this.normalizeBandEvent(item))
            );

            this.bandEventLatestReport = this.bandEventLatestReport
                ? this.normalizeBandEvent(this.bandEventLatestReport)
                : null;

            this.bandEventActiveBand = this.bandEventActiveBand
                ? this.normalizeBandEvent(this.bandEventActiveBand)
                : null;

            this.bandDraft = {
                ...this.emptyBandDraft(this.bandType),
                ...(this.bandDraft || {}),
                type: this.bandType || 'band',
            };

            this.ensureBandDraftStructure(this.bandType);

            if (!this.selectedBandHistory && this.bandHistoricalTree.length > 0) {
                this.selectedBandHistory = this.bandHistoricalTree[0];
            }
        },

        init() {
            this.normalizeInitialState();
            this.refreshLucide();
        },
                // ======================
        // UI HELPERS
        // ======================
        isBandType(type) {
            return this.bandType === type;
        },

        hasBandEventLatestReport() {
            return !!this.bandEventLatestReport;
        },

        hasBandHistoricalTree() {
            return Array.isArray(this.bandHistoricalTree) && this.bandHistoricalTree.length > 0;
        },

        hasBandEventBands() {
            return Array.isArray(this.bandEventBands) && this.bandEventBands.length > 0;
        },

        hasLatestReport() {
            return !!this.latestReport;
        },

        hasLatestBandStateReport() {
            return !!this.latestBandStateReport;
        },

        selectedBandSummary() {
            if (this.bandType === 'band') {
                return 'Nueva banda padre';
            }

            const parentId = this.bandDraft?.parent_id;
            if (!parentId) {
                return this.bandActiveBandLabel();
            }

            const found = this.bandEventBands.find(item => String(item.id) === String(parentId));
            return found ? this.bandOptionLabel(found) : 'Banda no encontrada';
        },

        selectedBandHistoryChildren() {
            return Array.isArray(this.selectedBandHistory?.children)
                ? this.selectedBandHistory.children
                : [];
        },

        bandDraftHasObservation() {
            return !!(this.bandDraft?.observation && String(this.bandDraft.observation).trim() !== '');
        },

        bandDraftHasDate() {
            return !!this.bandDraft?.report_date;
        },

        canOpenBandWizard() {
            return !this.loading;
        },

        canSaveBandDraft() {
            return !this.loading && !!this.bandDraft;
        },

        canPublishBandDraft() {
            return !this.loading && !!this.bandDraft;
        },

        canGoNextBandStep() {
            return this.bandWizardStep < this.bandWizardTotalSteps();
        },

        canGoPrevBandStep() {
            return this.bandWizardStep > 1;
        },

        stepIsActive(step) {
            return this.bandWizardStep === step;
        },

        stepIsCompleted(step) {
            return this.bandWizardStep > step;
        },

        bandStepTitle(step) {
            return {
                1: this.bandType === 'band' ? 'Inicio' : 'Banda asociada',
                2: 'Captura técnica',
                3: 'Cierre y publicación',
            }[step] || 'Paso';
        },

        bandStepDescription(step) {
            return {
                1: this.bandType === 'band'
                    ? 'Nuevo cambio de banda'
                    : 'Selecciona la banda oficial asociada',
                2: 'Completa las tablas técnicas',
                3: 'Agrega observación, evidencia y fecha',
            }[step] || '';
        },

        sanitizeString(value) {
            if (value === null || value === undefined) return '';
            return String(value).trim();
        },

        sanitizeNullableNumber(value) {
            if (value === null || value === undefined || value === '') return '';
            const numeric = Number(value);
            return Number.isNaN(numeric) ? '' : value;
        },

        safeArray(value) {
            return Array.isArray(value) ? value : [];
        },

        safeObject(value, fallback = {}) {
            return value && typeof value === 'object' ? value : fallback;
        },

        noop() {
            return null;
        },
                // ======================
        // BAND EVENTS SUMMARY HELPERS
        // ======================
        bandDraftPrimaryBrand() {
            if (this.bandType === 'section_change') {
                return this.bandDraft?.section_brand || '—';
            }

            return this.bandDraft?.brand || '—';
        },

        bandDraftPrimaryWidth() {
            if (this.bandType === 'section_change') {
                return this.displayValue(this.bandDraft?.section_width);
            }

            return this.displayValue(this.bandDraft?.width);
        },

        bandDraftPrimaryLength() {
            if (this.bandType === 'section_change') {
                return this.displayValue(this.bandDraft?.section_length);
            }

            return this.displayValue(this.bandDraft?.length);
        },

        bandDraftRollCountLabel() {
            if (this.bandType !== 'band') return '—';
            return this.bandDraft?.roll_count ?? '—';
        },

        bandDraftTemperatureLabel() {
            return this.displayValue(this.bandDraft?.temperature);
        },

        bandDraftPressureLabel() {
            return this.displayValue(this.bandDraft?.pressure);
        },

        bandDraftTimeLabel() {
            return this.displayValue(this.bandDraft?.time);
        },

        bandDraftCoolingTimeLabel() {
            return this.displayValue(this.bandDraft?.cooling_time);
        },

        bandDraftObservationLabel() {
            return this.bandDraft?.observation || '—';
        },

        bandDraftDateLabel() {
            return this.bandDraft?.report_date
                ? this.formatDate(this.bandDraft.report_date)
                : '—';
        },

        bandDraftReferenceSummary() {
            if (this.bandType !== 'band') return [];

            return [
                { label: 'Marca', value: this.bandDraft?.brand || '—' },
                { label: 'Espesor total', value: this.displayValue(this.bandDraft?.total_thickness) },
                { label: 'Cubierta superior', value: this.displayValue(this.bandDraft?.top_cover_thickness) },
                { label: 'Cubierta inferior', value: this.displayValue(this.bandDraft?.bottom_cover_thickness) },
                { label: 'Lonas', value: this.displayValue(this.bandDraft?.plies) },
                { label: 'Ancho', value: this.displayValue(this.bandDraft?.width) },
                { label: 'Longitud', value: this.displayValue(this.bandDraft?.length) },
                { label: 'Rollos', value: this.displayValue(this.bandDraft?.roll_count) },
            ];
        },

        bandDraftVulcanizationSummary() {
            return [
                { label: 'Temperatura', value: this.displayValue(this.bandDraft?.temperature) },
                { label: 'Presión', value: this.displayValue(this.bandDraft?.pressure) },
                { label: 'Tiempo vulcanizado', value: this.displayValue(this.bandDraft?.time) },
                { label: 'Tiempo enfriamiento', value: this.displayValue(this.bandDraft?.cooling_time) },
            ];
        },

        bandDraftDeliverySummary() {
            if (this.bandType !== 'band' && this.bandType !== 'section_change') return [];

            return [
                { label: 'Corriente motor', value: this.displayValue(this.bandDraft?.motor_current) },
                { label: 'Alineación', value: this.bandDraft?.alignment || '—' },
                { label: 'Material acumulado', value: this.bandDraft?.material_accumulation || '—' },
                { label: 'Guardilña', value: this.bandDraft?.guard || '—' },
                { label: 'Rodillería', value: this.bandDraft?.idler_condition || '—' },
            ];
        },

        bandDraftSectionSummary() {
            if (this.bandType !== 'section_change') return [];

            return [
                { label: 'Misma referencia', value: this.bandDraft?.same_reference ? 'Sí' : 'No' },
                { label: 'Marca tramo', value: this.bandDraft?.section_brand || '—' },
                { label: 'Espesor tramo', value: this.displayValue(this.bandDraft?.section_thickness) },
                { label: 'Lonas tramo', value: this.displayValue(this.bandDraft?.section_plies) },
                { label: 'Longitud tramo', value: this.displayValue(this.bandDraft?.section_length) },
                { label: 'Ancho tramo', value: this.displayValue(this.bandDraft?.section_width) },
            ];
        },

        bandSummaryBlocks() {
            const blocks = [
                {
                    key: 'general',
                    title: 'Resumen general',
                    items: [
                        { label: 'Tipo', value: this.bandEventTypeLabel(this.bandType) },
                        { label: 'Fecha', value: this.bandDraftDateLabel() },
                        { label: 'Banda padre', value: this.bandParentLabel() },
                    ],
                },
            ];

            if (this.bandType === 'band') {
                blocks.push({
                    key: 'reference',
                    title: 'Referencia de la banda',
                    items: this.bandDraftReferenceSummary(),
                });
            }

            blocks.push({
                key: 'vulcanization',
                title: 'Parámetros de vulcanizado',
                items: this.bandDraftVulcanizationSummary(),
            });

            if (this.bandType === 'band' || this.bandType === 'section_change') {
                blocks.push({
                    key: 'delivery',
                    title: 'Datos de entrega de equipo',
                    items: this.bandDraftDeliverySummary(),
                });
            }

            if (this.bandType === 'section_change') {
                blocks.push({
                    key: 'section',
                    title: 'Cambio de tramo',
                    items: this.bandDraftSectionSummary(),
                });
            }

            blocks.push({
                key: 'observation',
                title: 'Observación',
                items: [
                    { label: 'Detalle', value: this.bandDraftObservationLabel() },
                ],
            });

            return blocks;
        },

        childEventSummaryItems(child) {
            if (!child) return [];

            if (child.type === 'vulcanization') {
                return [
                    { label: 'Temperatura', value: this.displayValue(child.temperature) },
                    { label: 'Presión', value: this.displayValue(child.pressure) },
                    { label: 'Tiempo', value: this.displayValue(child.time) },
                    { label: 'Enfriamiento', value: this.displayValue(child.cooling_time) },
                    { label: 'Observación', value: child.observation || '—' },
                ];
            }

            if (child.type === 'section_change') {
                return [
                    { label: 'Temperatura', value: this.displayValue(child.temperature) },
                    { label: 'Presión', value: this.displayValue(child.pressure) },
                    { label: 'Tiempo', value: this.displayValue(child.time) },
                    { label: 'Enfriamiento', value: this.displayValue(child.cooling_time) },
                    { label: 'Marca tramo', value: child.section_brand || '—' },
                    { label: 'Espesor tramo', value: this.displayValue(child.section_thickness) },
                    { label: 'Lonas tramo', value: this.displayValue(child.section_plies) },
                    { label: 'Longitud tramo', value: this.displayValue(child.section_length) },
                    { label: 'Ancho tramo', value: this.displayValue(child.section_width) },
                    { label: 'Observación', value: child.observation || '—' },
                ];
            }

            return [];
        },
                // ======================
        // BAND EVENTS STATE / HISTORY UX
        // ======================
        clearBandWizardState({ keepType = true } = {}) {
            this.resetBandErrors();
            this.bandWizardStep = 1;

            if (!keepType) {
                this.bandType = 'band';
            }

            this.bandDraft = this.emptyBandDraft(this.bandType || 'band');
        },

        resetBandHistorySelection() {
            this.selectedBandHistory = this.bandHistoricalTree.length > 0
                ? this.bandHistoricalTree[0]
                : null;
        },

        ensureBandHistorySelection() {
            if (!this.selectedBandHistory && this.bandHistoricalTree.length > 0) {
                this.selectedBandHistory = this.bandHistoricalTree[0];
                return;
            }

            if (this.selectedBandHistory?.id) {
                const found = this.bandHistoricalTree.find(item => String(item.id) === String(this.selectedBandHistory.id));
                this.selectedBandHistory = found || (this.bandHistoricalTree[0] ?? null);
            }
        },

        latestBandChildCount(bandId = null) {
            const targetId = bandId ?? this.bandEventActiveBand?.id;
            if (!targetId) return 0;

            const found = this.bandHistoricalTree.find(item => String(item.id) === String(targetId));
            return Array.isArray(found?.children) ? found.children.length : 0;
        },

        bandDraftCompletionSnapshot() {
            return {
                hasType: !!this.bandType,
                hasParent: this.bandType === 'band' ? true : !!(this.bandDraft?.parent_id || this.bandEventActiveBand?.id),
                hasDate: !!this.bandDraft?.report_date,
                hasObservation: !!this.sanitizeString(this.bandDraft?.observation),
                hasAnyTechnicalValue: [
                    this.bandDraft?.brand,
                    this.bandDraft?.total_thickness,
                    this.bandDraft?.top_cover_thickness,
                    this.bandDraft?.bottom_cover_thickness,
                    this.bandDraft?.plies,
                    this.bandDraft?.width,
                    this.bandDraft?.length,
                    this.bandDraft?.roll_count,
                    this.bandDraft?.temperature,
                    this.bandDraft?.pressure,
                    this.bandDraft?.time,
                    this.bandDraft?.cooling_time,
                    this.bandDraft?.motor_current,
                    this.bandDraft?.alignment,
                    this.bandDraft?.material_accumulation,
                    this.bandDraft?.guard,
                    this.bandDraft?.idler_condition,
                    this.bandDraft?.section_brand,
                    this.bandDraft?.section_thickness,
                    this.bandDraft?.section_plies,
                    this.bandDraft?.section_length,
                    this.bandDraft?.section_width,
                ].some(value => value !== null && value !== undefined && value !== ''),
            };
        },

        bandDraftProgressCount() {
            const snapshot = this.bandDraftCompletionSnapshot();
            return [
                snapshot.hasType,
                snapshot.hasParent,
                snapshot.hasAnyTechnicalValue,
                snapshot.hasObservation,
                snapshot.hasDate,
            ].filter(Boolean).length;
        },

        bandDraftProgressLabel() {
            return `${this.bandDraftProgressCount()}/5`;
        },

        bandDraftStatusLabel() {
            const progress = this.bandDraftProgressCount();

            if (this.bandDraft?.id && progress >= 4) return 'Borrador avanzado';
            if (this.bandDraft?.id && progress >= 2) return 'Borrador en progreso';
            if (this.bandDraft?.id) return 'Borrador inicial';

            return 'Sin borrador';
        },

        bandDraftStatusTone() {
            const progress = this.bandDraftProgressCount();

            if (this.bandDraft?.id && progress >= 4) return 'text-emerald-700';
            if (this.bandDraft?.id && progress >= 2) return 'text-amber-700';
            if (this.bandDraft?.id) return 'text-slate-700';

            return 'text-slate-500';
        },

        selectedHistoryBandLabel() {
            if (!this.selectedBandHistory) return '—';
            return this.bandOptionLabel(this.selectedBandHistory);
        },

        selectedHistoryBandReferenceItems() {
            if (!this.selectedBandHistory) return [];

            return [
                { label: 'Marca', value: this.selectedBandHistory.brand || '—' },
                { label: 'Espesor total', value: this.displayValue(this.selectedBandHistory.total_thickness) },
                { label: 'Cubierta superior', value: this.displayValue(this.selectedBandHistory.top_cover_thickness) },
                { label: 'Cubierta inferior', value: this.displayValue(this.selectedBandHistory.bottom_cover_thickness) },
                { label: 'Lonas', value: this.displayValue(this.selectedBandHistory.plies) },
                { label: 'Ancho', value: this.displayValue(this.selectedBandHistory.width) },
                { label: 'Longitud', value: this.displayValue(this.selectedBandHistory.length) },
                { label: 'Rollos', value: this.displayValue(this.selectedBandHistory.roll_count) },
                { label: 'Observación', value: this.selectedBandHistory.observation || '—' },
            ];
        },

        selectedHistoryBandDeliveryItems() {
            if (!this.selectedBandHistory) return [];

            return [
                { label: 'Corriente motor', value: this.displayValue(this.selectedBandHistory.motor_current) },
                { label: 'Alineación', value: this.selectedBandHistory.alignment || '—' },
                { label: 'Material acumulado', value: this.selectedBandHistory.material_accumulation || '—' },
                { label: 'Guardilña', value: this.selectedBandHistory.guard || '—' },
                { label: 'Rodillería', value: this.selectedBandHistory.idler_condition || '—' },
            ];
        },

        selectedHistoryBandVulcanizationItems() {
            if (!this.selectedBandHistory) return [];

            return [
                { label: 'Temperatura', value: this.displayValue(this.selectedBandHistory.temperature) },
                { label: 'Presión', value: this.displayValue(this.selectedBandHistory.pressure) },
                { label: 'Tiempo', value: this.displayValue(this.selectedBandHistory.time) },
                { label: 'Enfriamiento', value: this.displayValue(this.selectedBandHistory.cooling_time) },
            ];
        },
                // ======================
        // BAND EVENTS AUTO-SYNC
        // ======================
        syncBandDraftParentByType() {
            if (!this.bandDraft || typeof this.bandDraft !== 'object') {
                this.bandDraft = this.emptyBandDraft(this.bandType || 'band');
                return;
            }

            if (this.bandType === 'band') {
                this.bandDraft.parent_id = null;
                return;
            }

            if (!this.bandDraft.parent_id && this.bandEventActiveBand?.id) {
                this.bandDraft.parent_id = this.bandEventActiveBand.id;
            }
        },

        syncBandDraftType() {
            if (!this.bandDraft || typeof this.bandDraft !== 'object') {
                this.bandDraft = this.emptyBandDraft(this.bandType || 'band');
                return;
            }

            this.bandDraft.type = this.bandType || 'band';
        },

        normalizeSameReferenceFlag() {
            if (!this.bandDraft || typeof this.bandDraft !== 'object') return;

            this.bandDraft.same_reference = !!this.bandDraft.same_reference;
        },

        maybeClearSectionFieldsIfSameReference() {
            if (!this.bandDraft || typeof this.bandDraft !== 'object') return;
            if (this.bandType !== 'section_change') return;
            if (!this.bandDraft.same_reference) return;

            // No borro longitud/ancho ni datos técnicos generales.
            // Solo limpio referencia específica del tramo si el usuario indicó que es igual a la instalada.
            this.bandDraft.section_brand = this.bandDraft.section_brand || '';
            this.bandDraft.section_thickness = this.bandDraft.section_thickness || '';
            this.bandDraft.section_plies = this.bandDraft.section_plies || '';
        },

        syncBandDraftDefaults() {
            this.ensureBandDraftStructure(this.bandType);
            this.syncBandDraftType();
            this.syncBandDraftParentByType();
            this.normalizeSameReferenceFlag();
            this.maybeClearSectionFieldsIfSameReference();
        },

        bandDraftUsesActiveParent() {
            if (this.bandType === 'band') return false;
            if (!this.bandDraft?.parent_id) return false;
            return String(this.bandDraft.parent_id) === String(this.bandEventActiveBand?.id ?? '');
        },

        selectedParentBandObject() {
            if (this.bandType === 'band') return null;

            const parentId = this.bandDraft?.parent_id || this.bandEventActiveBand?.id;
            if (!parentId) return null;

            return this.bandEventBands.find(item => String(item.id) === String(parentId)) || null;
        },

        selectedParentBandDateLabel() {
            const parent = this.selectedParentBandObject();
            if (!parent?.report_date) return '—';
            return this.formatDate(parent.report_date);
        },

        selectedParentBandBrandLabel() {
            const parent = this.selectedParentBandObject();
            return parent?.brand || '—';
        },

        selectedParentBandRollCountLabel() {
            const parent = this.selectedParentBandObject();
            return parent?.roll_count ?? '—';
        },

        selectedParentBandMeasuresLabel() {
            const parent = this.selectedParentBandObject();
            if (!parent) return '—';

            const width = this.displayValue(parent.width);
            const length = this.displayValue(parent.length);

            return `${width} × ${length}`;
        },

        bandContextCards() {
            if (this.bandType === 'band') {
                return [
                    {
                        title: 'Activo',
                        value: config.elementId,
                        description: 'Nuevo evento padre para este activo',
                    },
                    {
                        title: 'Resultado',
                        value: 'Nueva banda padre',
                        description: 'Se agregará al histórico oficial',
                    },
                ];
            }

            return [
                {
                    title: 'Banda sugerida',
                    value: this.bandActiveBandLabel(),
                    description: 'Se autoselecciona la más reciente',
                },
                {
                    title: 'Banda elegida',
                    value: this.bandParentLabel(),
                    description: this.bandDraftUsesActiveParent() ? 'Usando banda activa' : 'Selección manual',
                },
            ];
        },

        beforeOpenBandWizard(type = 'band') {
            this.bandType = type;
            this.resetBandErrors();
            this.bandWizardStep = 1;
            this.bandDraft = this.emptyBandDraft(type);
            this.syncBandDraftDefaults();
        },

        afterLoadBandDraft(type = 'band', draft = null) {
            this.bandType = type;
            this.bandDraft = {
                ...this.emptyBandDraft(type),
                ...(draft || {}),
                type,
                report_date: draft?.report_date || config.today,
            };

            this.syncBandDraftDefaults();
            this.ensureBandHistorySelection();
        },
                // ======================
        // BAND EVENTS FLOW REFINED
        // ======================
        async openBandWizard(type = 'band') {
            this.beforeOpenBandWizard(type);

            const ok = await this.createBandDraft(type);

            if (!ok || !this.bandDraft) {
                return;
            }

            this.bandWizardOpen = true;
            this.refreshLucide();
        },

        closeBandWizard() {
            this.bandWizardOpen = false;
            this.resetBandErrors();
        },

        async changeBandWizardType(type) {
            if (this.bandType === type) return;

            this.beforeOpenBandWizard(type);

            const ok = await this.createBandDraft(type);

            if (ok) {
                this.bandWizardStep = 1;
                this.refreshLucide();
            }
        },

        async createBandDraft(type = 'band') {
            this.loading = true;
            this.resetBandErrors();

            try {
                const response = await fetch(config.routes.bandCreate, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        type,
                    }),
                });

                const data = await this.parseJsonResponse(response);

                if (!response.ok || data.success === false) {
                    this.bandErrors = this.normalizeErrors(data, 'No fue posible preparar el borrador del evento.');
                    return false;
                }

                this.afterLoadBandDraft(type, data.draft || null);

                if (data.latest_report || data.active_band || data.bands || data.historical_tree) {
                    this.refreshBandCollections(data);
                }

                return true;
            } catch (error) {
                this.bandErrors = ['Ocurrió un error de red al crear u obtener el borrador del evento.'];
                return false;
            } finally {
                this.loading = false;
            }
        },

        async saveBandDraft() {
            this.syncBandDraftDefaults();
            this.loading = true;
            this.resetBandErrors();

            try {
                const response = await fetch(config.routes.bandUpdate, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        ...this.bandDraft,
                        type: this.bandType,
                    }),
                });

                const data = await this.parseJsonResponse(response);

                if (!response.ok || data.success === false) {
                    this.bandErrors = this.normalizeErrors(data, 'No fue posible guardar el borrador del evento.');
                    return false;
                }

                this.afterLoadBandDraft(this.bandType, data.draft || null);

                if (data.latest_report || data.active_band || data.bands || data.historical_tree) {
                    this.refreshBandCollections(data);
                }

                this.showCrudToast(data.message || 'Borrador guardado correctamente.');
                return true;
            } catch (error) {
                this.bandErrors = ['Ocurrió un error de red al guardar el borrador del evento.'];
                return false;
            } finally {
                this.loading = false;
            }
        },

        async publishBandDraft() {
            this.syncBandDraftDefaults();
            this.loading = true;
            this.resetBandErrors();

            try {
                const response = await fetch(config.routes.bandPublish, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        ...this.bandDraft,
                        type: this.bandType,
                        report_date: this.bandDraft?.report_date || config.today,
                    }),
                });

                const data = await this.parseJsonResponse(response);

                if (!response.ok || data.success === false) {
                    this.bandErrors = this.normalizeErrors(data, 'No fue posible publicar el reporte del evento.');
                    return false;
                }

                if (data.report) {
                    this.syncBandLatestStateAfterPublish(this.normalizeBandEvent(data.report));
                }

                if (data.latest_report || data.active_band || data.bands || data.historical_tree) {
                    this.refreshBandCollections(data);
                }

                this.bandDraft = this.emptyBandDraft(this.bandType);
                this.bandWizardOpen = false;
                this.bandWizardStep = 1;

                this.showCrudToast(data.message || 'Reporte publicado correctamente.');
                return true;
            } catch (error) {
                this.bandErrors = ['Ocurrió un error de red al publicar el reporte del evento.'];
                return false;
            } finally {
                this.loading = false;
            }
        },
                // ======================
        // MODAL / CONFIRM HELPERS
        // ======================
        resetThicknessErrors() {
            this.errors = [];
        },

        resetBandStateErrors() {
            this.bandStateErrors = [];
        },

        openPublishConfirmIfPossible() {
            this.resetThicknessErrors();
            this.publishForm.report_date = config.today;
            this.publishConfirmOpen = true;
        },

        closeAllThicknessModals() {
            this.draftModalOpen = false;
            this.historyModalOpen = false;
            this.publishConfirmOpen = false;
            this.resetThicknessErrors();
        },

        closeAllBandStateModals() {
            this.bandStateDraftModalOpen = false;
            this.bandStateHistoryModalOpen = false;
            this.bandStatePublishConfirmOpen = false;
            this.resetBandStateErrors();
        },

        closeAllBandEventModals() {
            this.bandWizardOpen = false;
            this.bandHistoryModalOpen = false;
            this.resetBandErrors();
        },

        closeEverythingMeasurementModule() {
            this.closeAllThicknessModals();
            this.closeAllBandStateModals();
            this.closeAllBandEventModals();
        },

        reopenLucideAfterModal() {
            this.refreshLucide();
        },

        canPublishThicknessDraft() {
            return !this.loading && this.hasDraft();
        },

        canPublishBandStateDraft() {
            return !this.loading && this.hasBandStateDraft();
        },

        selectedHistoryReportExists() {
            return !!this.selectedHistoryReport;
        },

        selectedBandStateHistoryReportExists() {
            return !!this.selectedBandStateHistoryReport;
        },

        selectedBandHistoryExists() {
            return !!this.selectedBandHistory;
        },

        hasHistoricalReports() {
            return Array.isArray(this.historicalReports) && this.historicalReports.length > 0;
        },

        hasBandStateHistoricalReports() {
            return Array.isArray(this.bandStateHistoricalReports) && this.bandStateHistoricalReports.length > 0;
        },

        closePublishBandStateAndRefresh() {
            this.bandStatePublishConfirmOpen = false;
            this.refreshLucide();
        },

        closePublishThicknessAndRefresh() {
            this.publishConfirmOpen = false;
            this.refreshLucide();
        },

        touchBandDraftDateIfMissing() {
            if (!this.bandDraft) return;
            if (!this.bandDraft.report_date) {
                this.bandDraft.report_date = config.today;
            }
        },

        touchBandStateDraftIfMissing() {
            this.bandStateDraft = {
                ...this.emptyBandStateDraft(),
                ...(this.bandStateDraft || {}),
            };
        },

        touchThicknessDraftIfMissing() {
            this.ensureDraftStructure();
        },

        refreshAllIconsAndSelections() {
            this.ensureBandHistorySelection();
            this.refreshLucide();
        },
                // ======================
        // BOOT / FINAL SYNC
        // ======================
        bootMeasurementModule() {
            // Thickness
            this.touchThicknessDraftIfMissing();

            // Band State
            this.touchBandStateDraftIfMissing();

            // Band Events
            this.syncBandDraftDefaults();
            this.touchBandDraftDateIfMissing();
            
            if (!this.selectedBandStateHistoryReport && this.bandStateHistoricalReports.length > 0) {
                const firstBandState = this.bandStateHistoricalReports[0];
                if (firstBandState?.id) {
                    this.selectedBandStateHistoryReport = firstBandState;
                }
            }

            this.ensureBandHistorySelection();
            this.refreshLucide();
        },

        rehydrateMeasurementModule() {
            this.normalizeInitialState();
            this.bootMeasurementModule();
        },

        init() {
            this.rehydrateMeasurementModule();
        },
    };
}


</script>
@endsection