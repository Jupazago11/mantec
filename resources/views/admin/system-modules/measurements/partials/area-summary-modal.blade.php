{{--
    Modal "Resumen valores mínimos obtenidos" por área + sidebar "Otros activos de esta área".
    Comparten el mismo array reactivo `areaSummaryItems`, así que excluir/incluir un activo
    (toggle dentro del modal) se refleja también en el sidebar sin refrescar.
    Compartido entre level-one.blade.php y show.blade.php.
    Se abren disparando los eventos globales:
    - `open-area-summary` { id, name, elementTypeId, clientName, elementTypeName }
    - `open-siblings-sidebar` { id, name, elementTypeId, clientName, elementTypeName, currentElementId }
--}}
<div
    x-data="measurementAreaSummaryModal()"
    x-on:open-area-summary.window="openAreaSummary($event.detail)"
    x-on:open-siblings-sidebar.window="openSiblingsSidebar($event.detail)"
>
    <div
        x-cloak
        x-show="areaSummaryOpen"
        x-transition.opacity
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/60 px-4 py-6"
        @keydown.escape.window="closeAreaSummary()"
    >
        <div
            x-show="areaSummaryOpen"
            class="flex max-h-[92vh] w-full max-w-7xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl"
            @click.outside="closeAreaSummary()"
            @click.stop
        >
            <div class="border-b border-slate-200 bg-white px-6 py-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                            Resumen por área
                        </p>

                        <h3 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">
                            Resumen valores mínimos obtenidos
                        </h3>

                        <div class="mt-2 flex flex-wrap items-center gap-2 text-xs font-semibold">
                            <span class="inline-flex rounded-full bg-[#4f79bd]/10 px-3 py-1 text-[#315f9e]">
                                Área:
                                <span class="ml-1" x-text="selectedArea?.name || '—'"></span>
                            </span>

                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-slate-600">
                                Cliente:
                                <span class="ml-1" x-text="selectedArea?.client_name || selectedArea?.clientName || '—'"></span>
                            </span>

                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-slate-600">
                                Tipo:
                                <span class="ml-1" x-text="selectedArea?.element_type_name || selectedArea?.elementTypeName || '—'"></span>
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            x-show="areaSummaryItems.some(i => !i.show_in_summary)"
                            x-cloak
                            @click="showHiddenSummary = !showHiddenSummary; $nextTick(() => { if (window.lucide) window.lucide.createIcons(); })"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                            :title="showHiddenSummary ? 'Ocultar los excluidos' : 'Ver activos excluidos del resumen'"
                        >
                            <i :data-lucide="showHiddenSummary ? 'eye-off' : 'eye'" class="h-4 w-4"></i>
                            <span x-text="showHiddenSummary ? 'Ocultar excluidos' : 'Ver excluidos (' + areaSummaryItems.filter(i => !i.show_in_summary).length + ')'"></span>
                        </button>

                        <button
                            type="button"
                            @click="closeAreaSummary()"
                            class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto bg-slate-50 px-6 py-5">
                <div
                    x-show="areaSummaryLoading"
                    x-cloak
                    class="rounded-2xl border border-slate-200 bg-white px-5 py-8 text-center text-sm font-medium text-slate-500"
                >
                    Cargando resumen del área...
                </div>

                <div
                    x-show="areaSummaryError"
                    x-cloak
                    class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700"
                    x-text="areaSummaryError"
                ></div>

                <template x-if="!areaSummaryLoading && !areaSummaryError && areaSummaryItems.length === 0">
                    <div class="rounded-2xl border border-slate-200 bg-white px-5 py-8 text-center text-sm font-medium text-slate-500">
                        No hay activos con información oficial disponible para esta área.
                    </div>
                </template>

                <template x-if="!areaSummaryLoading && !areaSummaryError && areaSummaryItems.length > 0">
                    <div class="space-y-4">
                        {{-- Tarjetas visibles --}}
                        <div class="grid gap-4 xl:grid-cols-2">
                            <template x-for="item in areaSummaryItems.filter(i => i.show_in_summary)" :key="'summary-vis-' + item.id">
                                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                                    <table class="w-full border-collapse text-xs md:text-sm">
                                        <tbody>
                                            <tr class="bg-[#4f79bd] text-white">
                                                <th class="w-[150px] border border-[#3f67a8] px-3 py-2 text-center font-extrabold">
                                                    <a
                                                        :href="item.url"
                                                        class="inline-flex items-center justify-center rounded-lg bg-white/15 px-3 py-1 text-white transition hover:bg-white/25"
                                                        x-text="item.name"
                                                    ></a>
                                                </th>
                                                <th class="border border-[#3f67a8] px-3 py-2 text-center font-extrabold">Cubierta superior</th>
                                                <th class="border border-[#3f67a8] px-3 py-2 text-center font-extrabold">Cubierta inferior</th>
                                                <th class="w-[90px] border border-[#3f67a8] px-3 py-2 text-center font-extrabold">Dureza</th>
                                                <th class="w-[40px] border border-[#3f67a8] px-1 py-2 text-center">
                                                    <button
                                                        type="button"
                                                        @click="toggleElementSummaryVisibility(item)"
                                                        title="Excluir del resumen"
                                                        class="inline-flex items-center justify-center rounded p-1 text-white/70 transition hover:bg-white/20 hover:text-white"
                                                    >
                                                        <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                                    </button>
                                                </th>
                                            </tr>

                                            <tr class="bg-white">
                                                <th class="border border-slate-200 bg-slate-50 px-3 py-2 text-center font-extrabold text-slate-800">Especificación</th>
                                                <td class="border border-slate-200 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900" x-text="formatValue(item.top_specification)"></td>
                                                <td class="border border-slate-200 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900" x-text="formatValue(item.bottom_specification)"></td>
                                                <td class="border border-slate-200 bg-slate-100 px-3 py-2 text-center font-semibold text-slate-900" x-text="formatValue(item.hardness_specification)"></td>
                                                <td class="border border-slate-200 bg-slate-100"></td>
                                            </tr>

                                            <tr class="bg-white">
                                                <th class="border border-slate-200 bg-slate-50 px-3 py-2 text-center font-extrabold text-slate-800">Medición</th>
                                                <td class="border border-slate-200 px-3 py-2 text-center font-semibold text-slate-900" x-text="formatValue(item.top_measurement)"></td>
                                                <td class="border border-slate-200 px-3 py-2 text-center font-semibold text-slate-900" x-text="formatValue(item.bottom_measurement)"></td>
                                                <td class="border border-slate-200 px-3 py-2 text-center font-semibold text-slate-400">—</td>
                                                <td class="border border-slate-200"></td>
                                            </tr>

                                            <tr class="bg-slate-50/70">
                                                <th class="border border-slate-200 bg-slate-50 px-3 py-2 text-center font-extrabold text-slate-800">Porcentaje</th>
                                                <td class="border border-slate-200 px-3 py-2 text-center font-extrabold" :class="percentageClass(item.top_percentage)" x-text="formatPercentage(item.top_percentage)"></td>
                                                <td class="border border-slate-200 px-3 py-2 text-center font-extrabold" :class="percentageClass(item.bottom_percentage)" x-text="formatPercentage(item.bottom_percentage)"></td>
                                                <td class="border border-slate-200 px-3 py-2 text-center font-semibold text-slate-400">—</td>
                                                <td class="border border-slate-200"></td>
                                            </tr>

                                            <tr class="bg-white">
                                                <td colspan="5" class="border border-slate-200 px-3 py-2">
                                                    <div class="flex flex-wrap justify-end gap-2 text-[11px] font-semibold text-slate-500">
                                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1">
                                                            Espesores: <span class="ml-1 text-slate-700" x-text="item.thickness_report_date || 'Sin reporte'"></span>
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </template>
                        </div>

                        {{-- Activos excluidos (solo cuando showHiddenSummary) --}}
                        <template x-if="showHiddenSummary && areaSummaryItems.some(i => !i.show_in_summary)">
                            <div>
                                <p class="mb-2 text-[11px] font-bold uppercase tracking-wide text-slate-400">Excluidos del resumen</p>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="item in areaSummaryItems.filter(i => !i.show_in_summary)" :key="'summary-hid-' + item.id">
                                        <div class="flex items-center gap-2 rounded-xl border border-dashed border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-500">
                                            <a :href="item.url" class="text-[#4f79bd] hover:underline" x-text="item.name"></a>
                                            <button
                                                type="button"
                                                @click="toggleElementSummaryVisibility(item)"
                                                title="Incluir en el resumen"
                                                class="inline-flex items-center justify-center rounded p-0.5 text-slate-400 transition hover:text-[#4f79bd]"
                                            >
                                                <i data-lucide="eye-off" class="h-3.5 w-3.5"></i>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Sidebar: otros activos de la misma área (excluye los ocultos del resumen) --}}
    <div
        x-cloak
        x-show="siblingsOpen"
        x-transition.opacity
        class="fixed inset-0 z-[9998] bg-slate-900/50"
        @click="closeSiblingsSidebar()"
    ></div>

    <div
        x-cloak
        x-show="siblingsOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed right-0 top-0 z-[9999] flex h-screen w-full max-w-sm flex-col overflow-hidden bg-white shadow-2xl"
        @click.outside="closeSiblingsSidebar()"
        @keydown.escape.window="closeSiblingsSidebar()"
    >
        <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Área: <span x-text="selectedArea?.name || '—'"></span>
                </p>
                <h3 class="mt-1 text-lg font-bold text-slate-900">
                    Otros activos de esta área
                </h3>
            </div>

            <button
                type="button"
                @click="closeSiblingsSidebar()"
                class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
            >
                Cerrar
            </button>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto px-3 py-3">
            <div
                x-show="areaSummaryLoading"
                x-cloak
                class="px-3 py-4 text-sm font-medium text-slate-500"
            >
                Cargando activos...
            </div>

            <div
                x-show="areaSummaryError"
                x-cloak
                class="px-3 py-4 text-sm font-semibold text-red-700"
                x-text="areaSummaryError"
            ></div>

            <template x-if="!areaSummaryLoading && !areaSummaryError">
                <template x-for="item in areaSummaryItems.filter(i => i.show_in_summary)" :key="'sibling-' + item.id">
                    <a
                        :href="item.url"
                        class="mb-1.5 flex items-center justify-between rounded-xl px-3 py-2.5 text-sm font-semibold transition"
                        :class="item.id === currentElementId ? 'bg-[#d94d33]/10 text-[#d94d33]' : 'text-slate-700 hover:bg-slate-100'"
                    >
                        <span x-text="item.name"></span>
                        <span
                            x-show="item.id === currentElementId"
                            x-cloak
                            class="text-[10px] font-bold uppercase tracking-wide text-[#d94d33]"
                        >Actual</span>
                    </a>
                </template>
            </template>

            <template x-if="!areaSummaryLoading && !areaSummaryError && areaSummaryItems.filter(i => i.show_in_summary).length === 0">
                <p class="px-3 py-4 text-sm text-slate-500">
                    No hay otros activos visibles en esta área.
                </p>
            </template>
        </div>
    </div>
</div>

<script>
    function measurementAreaSummaryModal() {
        return {
            areaSummaryOpen: false,
            areaSummaryLoading: false,
            areaSummaryError: null,
            selectedArea: null,
            areaSummaryItems: [],
            showHiddenSummary: false,

            siblingsOpen: false,
            currentElementId: null,

            async openAreaSummary(area) {
                this.areaSummaryOpen = true;
                await this.loadAreaItems(area);
            },

            async openSiblingsSidebar(area) {
                this.currentElementId = area?.currentElementId ?? null;
                this.siblingsOpen = true;
                await this.loadAreaItems(area);
            },

            async loadAreaItems(area) {
                if (!area || !area.id || !area.elementTypeId) {
                    this.areaSummaryError = 'No fue posible identificar el área seleccionada.';
                    return;
                }

                this.selectedArea = area;
                this.areaSummaryItems = [];
                this.areaSummaryError = null;
                this.areaSummaryLoading = true;

                const url = @js(route('admin.system-modules.measurements.level-one.area-summary', [
                    'area' => '__AREA__',
                ])) + '?element_type_id=' + encodeURIComponent(area.elementTypeId);

                try {
                    const response = await fetch(url.replace('__AREA__', area.id), {
                        headers: {
                            'Accept': 'application/json',
                        },
                    });

                    const data = await response.json();

                    if (!response.ok || data.success === false) {
                        this.areaSummaryError = data.message || 'No fue posible cargar el resumen del área.';
                        return;
                    }

                    this.selectedArea = {
                        ...this.selectedArea,
                        ...(data.area || {}),
                    };

                    this.areaSummaryItems = data.items || [];
                } catch (error) {
                    this.areaSummaryError = 'Ocurrió un error de red al cargar el resumen del área.';
                } finally {
                    this.areaSummaryLoading = false;

                    this.$nextTick(() => {
                        if (window.lucide) {
                            window.lucide.createIcons();
                        }
                    });
                }
            },

            closeAreaSummary() {
                this.areaSummaryOpen = false;
                this.showHiddenSummary = false;
            },

            closeSiblingsSidebar() {
                this.siblingsOpen = false;
            },

            async toggleElementSummaryVisibility(item) {
                try {
                    const response = await fetch(item.toggle_summary_url, {
                        method: 'PATCH',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        item.show_in_summary = data.show_in_summary;
                    }
                } catch (e) {
                    // silencioso — el estado local no cambia si falla
                }

                this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
            },

            formatValue(value) {
                if (value === null || value === undefined || value === '') {
                    return '—';
                }

                const number = Number(value);

                if (Number.isNaN(number)) {
                    return value;
                }

                return new Intl.NumberFormat('es-CO', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2,
                }).format(number);
            },

            formatPercentage(value) {
                if (value === null || value === undefined || value === '') {
                    return '—';
                }

                const number = Number(value);

                if (Number.isNaN(number)) {
                    return '—';
                }

                return `${new Intl.NumberFormat('es-CO', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0,
                }).format(number)}%`;
            },
            percentageClass(value) {
                if (value === null || value === undefined || value === '') {
                    return 'text-slate-400';
                }

                const number = Number(value);

                if (Number.isNaN(number)) {
                    return 'text-slate-400';
                }

                if (number <= 54.9) {
                    return 'text-red-700';
                }

                if (number >= 55 && number <= 89.9) {
                    return 'text-amber-600';
                }

                return 'text-emerald-700';
            },
            percentageBadgeClass(value) {
                if (value === null || value === undefined || value === '') {
                    return 'bg-slate-100 text-slate-400 ring-slate-200';
                }

                const number = Number(value);

                if (Number.isNaN(number)) {
                    return 'bg-slate-100 text-slate-400 ring-slate-200';
                }

                if (number <= 54.9) {
                    return 'bg-red-50 text-red-700 ring-red-200';
                }

                if (number >= 55 && number <= 89.9) {
                    return 'bg-amber-50 text-amber-700 ring-amber-200';
                }

                return 'bg-emerald-50 text-emerald-700 ring-emerald-200';
            },
        };
    }
</script>
