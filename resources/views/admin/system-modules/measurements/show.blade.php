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
            routes: {
                create: @js(route('admin.system-modules.measurements.thickness-draft.create', $element->id)),
                update: @js(route('admin.system-modules.measurements.thickness-draft.update', $element->id)),
                addCover: @js(route('admin.system-modules.measurements.thickness-draft.add-cover', $element->id)),
                removeLastCover: @js(route('admin.system-modules.measurements.thickness-draft.remove-last-cover', $element->id)),
            }
        })"
        class="space-y-8"
    >
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">{{ $element->name }}</h2>
            <p class="mt-2 text-slate-600">
                Vista operativa del activo. Esta pantalla se divide en tres submódulos: dos superiores y uno inferior de ancho completo.
            </p>
        </div>

        <div class="grid gap-8 xl:grid-cols-2">
            {{-- Superior izquierda --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h3 class="text-lg font-semibold text-slate-900">Submódulo superior izquierdo</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Espacio reservado para la primera sección del activo.
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
                        <p class="mt-1 text-sm text-slate-500">
                            El borrador único por activo se gestiona en una ventana modal independiente.
                        </p>
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
                            class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                            disabled
                        >
                            Ver histórico
                        </button>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6">
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Estado del borrador</p>
                            <p class="mt-2 text-sm font-semibold text-slate-800" x-text="hasDraft() ? 'Disponible' : 'No existe aún'"></p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Cubiertas actuales</p>
                            <p class="mt-2 text-sm font-semibold text-slate-800" x-text="draft?.lines?.length ?? 0"></p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Modo de trabajo</p>
                            <p class="mt-2 text-sm font-semibold text-slate-800">Edición en modal</p>
                        </div>
                    </div>

                    <p class="mt-4 text-sm text-slate-500">
                        La tabla editable no se muestra directamente aquí. Para crear o continuar el borrador, usa el botón superior y trabaja dentro del modal.
                    </p>
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
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
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
                                + Crear cubierta
                            </button>

                            <button
                                type="button"
                                @click="removeLastCover()"
                                :disabled="loading"
                                class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 disabled:pointer-events-none disabled:opacity-70"
                            >
                                Eliminar última cubierta
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

                    <div x-show="hasDraft()" x-cloak class="space-y-6">
                        <div class="overflow-x-auto rounded-2xl border border-slate-200">
                            <table class="min-w-full table-auto divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="w-[10%] px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                            Cubierta
                                        </th>
                                        <th class="w-[28%] px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                            Medición
                                        </th>
                                        <th class="w-[13%] px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                            Izquierda
                                        </th>
                                        <th class="w-[13%] px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                            Centro
                                        </th>
                                        <th class="w-[13%] px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                            Derecha
                                        </th>
                                        <th class="w-[11.5%] px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                            Máximo
                                        </th>
                                        <th class="w-[11.5%] px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                            Mínimo
                                        </th>
                                    </tr>
                                </thead>

                                <template x-for="line in (draft && draft.lines ? draft.lines : [])" :key="'cover-' + line.cover_number">
                                    <tbody class="divide-y divide-slate-200 bg-white">
                                        <tr>
                                            <td rowspan="3" class="align-top px-4 py-3 text-sm font-semibold text-slate-900">
                                                <span class="inline-flex min-w-8 items-center justify-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                                    <span x-text="line.cover_number"></span>
                                                </span>
                                            </td>

                                            <td class="px-4 py-2 text-xs font-bold uppercase leading-4 tracking-wider text-slate-500">
                                                Cubierta superior <span x-text="line.cover_number"></span>
                                            </td>

                                            <td class="px-4 py-2 align-middle">
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    x-model="line.top_left"
                                                    class="w-full rounded-xl border border-slate-300 px-3 py-1.5 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                                >
                                            </td>

                                            <td class="px-4 py-2 align-middle">
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    x-model="line.top_center"
                                                    class="w-full rounded-xl border border-slate-300 px-3 py-1.5 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                                >
                                            </td>

                                            <td class="px-4 py-2 align-middle">
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    x-model="line.top_right"
                                                    class="w-full rounded-xl border border-slate-300 px-3 py-1.5 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                                >
                                            </td>

                                            <td class="px-4 py-2 text-sm font-semibold text-slate-700" x-text="maxValue([line.top_left, line.top_center, line.top_right])"></td>
                                            <td class="px-4 py-2 text-sm font-semibold text-slate-700" x-text="minValue([line.top_left, line.top_center, line.top_right])"></td>
                                        </tr>

                                        <tr>
                                            <td class="px-4 py-2 text-xs font-bold uppercase leading-4 tracking-wider text-slate-500">
                                                Cubierta inferior <span x-text="line.cover_number"></span>
                                            </td>

                                            <td class="px-4 py-2 align-middle">
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    x-model="line.bottom_left"
                                                    class="w-full rounded-xl border border-slate-300 px-3 py-1.5 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                                >
                                            </td>

                                            <td class="px-4 py-2 align-middle">
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    x-model="line.bottom_center"
                                                    class="w-full rounded-xl border border-slate-300 px-3 py-1.5 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                                >
                                            </td>

                                            <td class="px-4 py-2 align-middle">
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    x-model="line.bottom_right"
                                                    class="w-full rounded-xl border border-slate-300 px-3 py-1.5 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                                >
                                            </td>

                                            <td class="px-4 py-2 text-sm font-semibold text-slate-700" x-text="maxValue([line.bottom_left, line.bottom_center, line.bottom_right])"></td>
                                            <td class="px-4 py-2 text-sm font-semibold text-slate-700" x-text="minValue([line.bottom_left, line.bottom_center, line.bottom_right])"></td>
                                        </tr>

                                        <tr class="border-b-4 border-slate-200">
                                            <td class="px-4 py-2 text-xs font-bold uppercase leading-4 tracking-wider text-slate-500">
                                                Dureza <span x-text="line.cover_number"></span>
                                            </td>

                                            <td class="px-4 py-2 align-middle">
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    x-model="line.hardness_left"
                                                    class="w-full rounded-xl border border-slate-300 px-3 py-1.5 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                                >
                                            </td>

                                            <td class="px-4 py-2 align-middle">
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    x-model="line.hardness_center"
                                                    class="w-full rounded-xl border border-slate-300 px-3 py-1.5 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                                >
                                            </td>

                                            <td class="px-4 py-2 align-middle">
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    x-model="line.hardness_right"
                                                    class="w-full rounded-xl border border-slate-300 px-3 py-1.5 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                                >
                                            </td>

                                            <td class="px-4 py-2 text-sm font-semibold text-slate-700" x-text="maxValue([line.hardness_left, line.hardness_center, line.hardness_right])"></td>
                                            <td class="px-4 py-2 text-sm font-semibold text-slate-700" x-text="minValue([line.hardness_left, line.hardness_center, line.hardness_right])"></td>
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
                'fixed bottom-6 right-6 z-[80] rounded-2xl px-4 py-3 text-sm font-semibold shadow-xl transition ' +
                (type === 'success'
                    ? 'border border-green-200 bg-green-100 text-green-700'
                    : 'border border-red-200 bg-red-100 text-red-700');

            toast.textContent = message;
            toast.classList.remove('hidden');

            clearTimeout(window.__crudToastTimeout);
            window.__crudToastTimeout = setTimeout(() => {
                toast.classList.add('hidden');
            }, 2200);
        }

        function measurementThicknessModule(config) {
            return {
                elementId: config.elementId,
                routes: config.routes,
                draft: config.initialDraft,
                draftModalOpen: false,
                loading: false,
                errors: [],

                hasDraft() {
                    return !!(this.draft && Array.isArray(this.draft.lines));
                },

                async openDraftModal() {
                    this.errors = [];

                    if (!this.hasDraft()) {
                        await this.createDraft();
                    }

                    if (this.hasDraft()) {
                        this.draftModalOpen = true;
                    }
                },

                closeDraftModal() {
                    this.draftModalOpen = false;
                    this.errors = [];
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

                async saveDraft() {
                    if (!this.hasDraft()) {
                        showCrudToast('Primero debes crear un borrador.', 'error');
                        return;
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
                            showCrudToast('Corrige los errores del borrador.', 'error');
                            return;
                        }

                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'No fue posible guardar el borrador.');
                        }

                        this.draft = data.draft;
                        showCrudToast(data.message || 'Borrador guardado correctamente.', 'success');
                    } catch (error) {
                        showCrudToast(error.message || 'Ocurrió un error al guardar el borrador.', 'error');
                    } finally {
                        this.loading = false;
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
                        showCrudToast(data.message || 'Se agregó una nueva cubierta al borrador.', 'success');
                    } catch (error) {
                        showCrudToast(error.message || 'Ocurrió un error al agregar una cubierta.', 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                async removeLastCover() {
                    if (!this.hasDraft()) {
                        showCrudToast('No existe borrador para editar.', 'error');
                        return;
                    }

                    this.loading = true;
                    this.errors = [];

                    try {
                        const response = await fetch(this.routes.removeLastCover, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            },
                        });

                        const data = await response.json();

                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'No fue posible eliminar la última cubierta.');
                        }

                        this.draft = data.draft;
                        showCrudToast(data.message || 'Última cubierta eliminada correctamente.', 'success');
                    } catch (error) {
                        showCrudToast(error.message || 'Ocurrió un error al eliminar la última cubierta.', 'error');
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