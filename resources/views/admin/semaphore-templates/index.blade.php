@extends('layouts.admin')

@section('title', 'Plantillas de semaforo')
@section('header_title', 'Plantillas de semaforo')

@section('content')
    <div class="space-y-8">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-orange-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-orange-700">
                    <i data-lucide="settings-2" class="h-3.5 w-3.5"></i>
                    Configuracion dinamica
                </div>
            </div>

            <a
                href="{{ route('admin.indicators.index') }}"
                class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
            >
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Volver a indicadores
            </a>
        </div>

        @if(!empty($migrationPending))
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Faltan las tablas del modulo de plantillas de semaforo. Ejecuta las migraciones pendientes para habilitar esta pantalla.
            </div>
        @endif

        @if(session('success'))
            <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <div class="font-semibold">Hay errores en el formulario.</div>
                <ul class="mt-2 list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-8 xl:grid-cols-[360px_minmax(0,1fr)]">
            <div class="xl:sticky xl:top-6 xl:self-start">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div id="createSemaphoreTemplateAjaxErrors" class="mt-4 hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

                    <form
                        id="createSemaphoreTemplateForm"
                        method="POST"
                        action="{{ route('admin.semaphore-templates.store') }}"
                        class="mt-6 space-y-5"
                    >
                        @csrf

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Cliente</label>
                            <select
                                name="client_id"
                                id="create_client_id"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            >
                                <option value="">Seleccione un cliente</option>
                                @foreach($clients as $client)
                                    <option
                                        value="{{ $client->id }}"
                                        @selected(old('client_id', $prefill['client_id'] ?? null) == $client->id)
                                    >
                                        {{ $client->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Agrupacion</label>
                            <select
                                name="group_id"
                                id="create_group_id"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            >
                                <option value="">Sin agrupacion especifica</option>
                                @foreach($groups as $group)
                                    <option
                                        value="{{ $group->id }}"
                                        data-client-id="{{ $group->client_id }}"
                                        @selected(old('group_id', $prefill['group_id'] ?? null) == $group->id)
                                    >
                                        {{ $group->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Tipo de activo</label>
                            <select
                                name="element_type_id"
                                id="create_element_type_id"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            >
                                <option value="">Seleccione un tipo de activo</option>
                                @foreach($elementTypes as $type)
                                    <option
                                        value="{{ $type->id }}"
                                        data-client-id="{{ $type->client_id }}"
                                        @selected(old('element_type_id', $prefill['element_type_id'] ?? null) == $type->id)
                                    >
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Nombre</label>
                            <input
                                type="text"
                                name="name"
                                value="{{ old('name') }}"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                placeholder="Ej. Semaforo operativo principal"
                            >
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Descripcion</label>
                            <textarea
                                name="description"
                                rows="3"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                placeholder="Opcional"
                            >{{ old('description') }}</textarea>
                        </div>

                        <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            <input type="checkbox" name="is_default" value="1" @checked(old('is_default')) class="mt-0.5 rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]">
                            <span>Semaforo principal para este alcance.</span>
                        </label>

                        <button
                            type="submit"
                            @disabled(!empty($migrationPending))
                            class="inline-flex w-full items-center justify-center rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29] disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            Crear plantilla
                        </button>
                    </form>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Plantillas registradas</h3>
                            <p class="mt-1 text-sm text-slate-500">
                                Cada plantilla puede representar un semaforo distinto para el mismo tipo de activo.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 sm:flex sm:flex-wrap">
                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Total</div>
                                <div id="semaphoreTemplatesCount" class="mt-1 text-xl font-bold text-slate-900">{{ $templates->count() }}</div>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Activas</div>
                                <div id="semaphoreTemplatesActiveCount" class="mt-1 text-xl font-bold text-emerald-700">{{ $templates->where('status', true)->count() }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="semaphoreTemplatesList" class="divide-y divide-slate-200">
                    @forelse($templates as $template)
                        <article
                            class="px-6 py-5"
                            id="semaphore-template-row-{{ $template->id }}"
                            data-status="{{ $template->status ? '1' : '0' }}"
                            data-client-id="{{ $template->client_id }}"
                            data-group-id="{{ $template->group_id ?? '' }}"
                            data-element-type-id="{{ $template->element_type_id }}"
                        >
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0 space-y-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 class="text-base font-semibold text-slate-900">{{ $template->name }}</h4>
                                        @if($template->is_default)
                                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-bold text-emerald-700">Predeterminada</span>
                                        @endif
                                        <span class="rounded-full {{ $template->status ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-500' }} px-2.5 py-1 text-[11px] font-bold">
                                            {{ $template->status ? 'Activa' : 'Inactiva' }}
                                        </span>
                                    </div>

                                    <div class="flex flex-wrap gap-2 text-xs font-semibold text-slate-600">
                                        <span class="rounded-full bg-slate-100 px-3 py-1">{{ $template->client?->name }}</span>
                                        <span class="rounded-full bg-slate-100 px-3 py-1">{{ $template->group?->name ?: 'Sin agrupacion especifica' }}</span>
                                        <span class="rounded-full bg-[#d94d33]/10 px-3 py-1 text-[#d94d33]">{{ $template->elementType?->name }}</span>
                                    </div>

                                    <div class="flex flex-wrap gap-4 text-sm text-slate-500">
                                        <span>{{ $template->columns->count() }} columnas configuradas</span>
                                    </div>

                                    @if($template->description)
                                        <p class="max-w-3xl text-sm text-slate-500">{{ $template->description }}</p>
                                    @endif
                                </div>

                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        type="button"
                                        onclick="openSemaphoreTemplateModal({{ $template->id }})"
                                        class="text-slate-400 transition hover:text-[#d94d33]"
                                        title="Configurar plantilla"
                                    >
                                        <i data-lucide="settings-2" class="h-4 w-4"></i>
                                    </button>

                                    <button
                                        type="button"
                                        onclick="toggleSemaphoreTemplateStatus({{ $template->id }})"
                                        class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition {{ $template->status ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-red-100 text-red-700 hover:bg-red-200' }}"
                                        title="{{ $template->status ? 'Desactivar plantilla' : 'Activar plantilla' }}"
                                    >
                                        <i data-lucide="{{ $template->status ? 'check-circle-2' : 'x-circle' }}" class="h-3.5 w-3.5"></i>
                                        <span>{{ $template->status ? 'Activa' : 'Inactiva' }}</span>
                                    </button>

                                    <button
                                        type="button"
                                        onclick="destroySemaphoreTemplate({{ $template->id }})"
                                        class="text-red-500 transition hover:text-red-700"
                                        title="Eliminar plantilla"
                                    >
                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                    </button>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div id="semaphore-templates-empty-row" class="px-6 py-12 text-center text-sm text-slate-500">
                            {{ !empty($migrationPending) ? 'Las plantillas apareceran aqui despues de ejecutar las migraciones.' : 'No hay plantillas registradas todavia.' }}
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div
        id="editSemaphoreTemplateModal"
        class="fixed left-0 top-0 z-[9999] hidden h-[100dvh] w-[100vw] items-center justify-center overflow-y-auto bg-slate-950/60 px-3 py-4 backdrop-blur-sm sm:px-4 sm:py-6"
    >
        <div
            id="editSemaphoreTemplateModalContent"
            class="flex w-full max-w-6xl scale-95 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white opacity-0 shadow-2xl transition duration-200 ease-out"
            style="max-height: calc(100dvh - 2rem);"
        >
            <div class="flex shrink-0 items-center justify-between border-b border-slate-200 bg-white px-4 py-3 sm:px-6">
                <div>
                    <h3 class="text-base font-bold text-slate-900 sm:text-lg">Configurar plantilla</h3>
                    <p class="mt-0.5 hidden text-xs text-slate-500 sm:block">
                        Ajusta las columnas y reglas del semaforo sin salir del listado.
                    </p>
                </div>

                <button
                    type="button"
                    class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                    onclick="closeSemaphoreTemplateModal()"
                    title="Cerrar"
                >
                    ✕
                </button>
            </div>

            <form id="editSemaphoreTemplateForm" method="POST" class="flex min-h-0 flex-1 flex-col">
                @csrf
                @method('PUT')

                <input type="hidden" name="client_id" id="modal_template_client_id">
                <input type="hidden" name="group_id" id="modal_template_group_id">
                <input type="hidden" name="element_type_id" id="modal_template_element_type_id">
                <input type="hidden" name="name" id="modal_template_name_input">
                <input type="hidden" name="description" id="modal_template_description_input">
                <input type="hidden" name="is_default" id="modal_template_is_default_input" value="0">
                <input type="hidden" name="status" id="modal_template_status_input" value="0">

                <div class="min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-6">
                    <div id="editSemaphoreTemplateAjaxErrors" class="mb-4 hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

                    <div class="space-y-6">
                        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                                <div class="min-w-0">
                                    <h3 id="modal_template_name" class="text-xl font-semibold text-slate-900">Plantilla</h3>

                                    <div id="modal_template_badges" class="mt-2 flex flex-wrap gap-2 text-xs font-semibold text-slate-600"></div>

                                    <p id="modal_template_description" class="mt-4 max-w-4xl text-sm leading-6 text-slate-500">
                                        Esta plantilla define el alcance y las reglas que alimentan este semaforo.
                                    </p>
                                </div>

                                <div class="grid grid-cols-2 gap-3 sm:flex sm:flex-wrap">
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                        <div class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Columnas</div>
                                        <div id="modal_semaphoreColumnsCount" class="mt-1 text-xl font-bold text-slate-900">0</div>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                        <div class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Reglas</div>
                                        <div id="modal_semaphoreRulesCount" class="mt-1 text-xl font-bold text-emerald-700">0</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900">Constructor de columnas</h3>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Organiza la logica del semaforo con una vista mas amplia y un builder optimizado para varias columnas.
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    onclick="addSemaphoreColumn()"
                                    class="inline-flex items-center gap-2 rounded-xl bg-[#d94d33] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                                >
                                    <i data-lucide="plus" class="h-4 w-4"></i>
                                    Agregar columna
                                </button>
                            </div>

                            <div id="semaphoreColumnsBuilder" class="mt-6 space-y-5"></div>
                        </div>
                    </div>
                </div>

                <div class="shrink-0 border-t border-slate-200 bg-slate-50 px-4 py-3 sm:px-6">
                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-between sm:gap-3">
                        <div class="text-sm text-slate-500">
                            El guardado es por AJAX. Si hay errores, el constructor conserva lo que ya escribiste.
                        </div>

                        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:gap-3">
                            <button
                                type="button"
                                onclick="closeSemaphoreTemplateModal()"
                                class="inline-flex w-full justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 sm:w-auto"
                            >
                                Cerrar
                            </button>

                            <button
                                type="submit"
                                class="inline-flex w-full justify-center rounded-xl bg-[#d94d33] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#b83f29] sm:w-auto"
                            >
                                Guardar plantilla
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        const semaphoreTemplatesRoutes = {
            store: @json(route('admin.semaphore-templates.store')),
            toggleBase: @json(url('/admin/semaphore-templates')),
        };

        const semaphoreBuilderState = {
            templateId: null,
            components: [],
            diagnostics: [],
            columns: [],
            columnTypes: {},
            directions: {},
            emptyStates: {},
        };

        function escapeHtml(text) {
            return String(text ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function showCrudToast(message, type = 'success') {
            const toastId = 'semaphoreTemplatesCrudToast';
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

            clearTimeout(window.__semaphoreTemplatesToastTimeout);
            window.__semaphoreTemplatesToastTimeout = setTimeout(() => {
                toast.classList.add('hidden');
            }, 4200);
        }

        function renderAjaxErrors(containerId, errors) {
            const box = document.getElementById(containerId);
            if (!box) {
                return;
            }

            const messages = [];
            Object.values(errors || {}).forEach((fieldErrors) => {
                (fieldErrors || []).forEach((message) => messages.push(message));
            });

            if (!messages.length) {
                box.classList.add('hidden');
                box.innerHTML = '';
                return;
            }

            box.innerHTML = `
                <div class="font-semibold">Hay errores en el formulario.</div>
                <ul class="mt-2 list-disc pl-5">
                    ${messages.map(message => `<li>${escapeHtml(String(message))}</li>`).join('')}
                </ul>
            `;
            box.classList.remove('hidden');
        }

        function clearAjaxErrors(containerId) {
            const box = document.getElementById(containerId);
            if (!box) {
                return;
            }

            box.classList.add('hidden');
            box.innerHTML = '';
        }

        function setFormSubmittingState(form, isSubmitting, loadingText = 'Guardando...') {
            if (!form) {
                return;
            }

            form.querySelectorAll('button[type="submit"]').forEach((button) => {
                if (isSubmitting) {
                    button.dataset.originalText = button.innerHTML;
                    button.disabled = true;
                    button.classList.add('opacity-70', 'pointer-events-none');
                    button.innerHTML = loadingText;
                } else {
                    button.disabled = false;
                    button.classList.remove('opacity-70', 'pointer-events-none');
                    button.innerHTML = button.dataset.originalText || button.innerHTML;
                }
            });
        }

        function updateSemaphoreTemplateStats() {
            const cards = Array.from(document.querySelectorAll('[id^="semaphore-template-row-"]'));
            const totalNode = document.getElementById('semaphoreTemplatesCount');
            const activeNode = document.getElementById('semaphoreTemplatesActiveCount');

            if (totalNode) {
                totalNode.textContent = String(cards.length);
            }

            if (activeNode) {
                const activeCount = cards.filter(card => card.dataset.status === '1').length;
                activeNode.textContent = String(activeCount);
            }
        }

        function filterSemaphoreCreateOptions() {
            const clientId = document.getElementById('create_client_id')?.value || '';
            const groupSelect = document.getElementById('create_group_id');
            const typeSelect = document.getElementById('create_element_type_id');

            [groupSelect, typeSelect].forEach(select => {
                if (!select) {
                    return;
                }

                Array.from(select.options).forEach((option, index) => {
                    if (index === 0) {
                        option.hidden = false;
                        return;
                    }

                    option.hidden = clientId !== '' && option.dataset.clientId !== clientId;

                    if (option.hidden && option.selected) {
                        select.value = '';
                    }
                });
            });
        }

        function resetSemaphoreTemplateCreateForm() {
            const form = document.getElementById('createSemaphoreTemplateForm');
            if (!form) {
                return;
            }

            form.reset();
            clearAjaxErrors('createSemaphoreTemplateAjaxErrors');

            const clientSelect = document.getElementById('create_client_id');
            if (clientSelect && clientSelect.dataset.prefillValue) {
                clientSelect.value = clientSelect.dataset.prefillValue;
            }

            filterSemaphoreCreateOptions();
        }

        function buildSemaphoreTemplateRow(row) {
            const descriptionHtml = row.description
                ? `<p class="max-w-3xl text-sm text-slate-500">${escapeHtml(row.description)}</p>`
                : '';

            return `
                <article
                    class="px-6 py-5"
                    id="semaphore-template-row-${row.id}"
                    data-status="${row.status ? '1' : '0'}"
                    data-client-id="${escapeHtml(String(row.client_id ?? ''))}"
                    data-group-id="${escapeHtml(String(row.group_id ?? ''))}"
                    data-element-type-id="${escapeHtml(String(row.element_type_id ?? ''))}"
                >
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0 space-y-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <h4 class="text-base font-semibold text-slate-900">${escapeHtml(row.name ?? 'Sin nombre')}</h4>
                                ${row.is_default ? '<span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-bold text-emerald-700">Predeterminada</span>' : ''}
                                <span class="rounded-full ${row.status ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-500'} px-2.5 py-1 text-[11px] font-bold">
                                    ${row.status ? 'Activa' : 'Inactiva'}
                                </span>
                            </div>

                            <div class="flex flex-wrap gap-2 text-xs font-semibold text-slate-600">
                                <span class="rounded-full bg-slate-100 px-3 py-1">${escapeHtml(row.client_name ?? 'Sin cliente')}</span>
                                <span class="rounded-full bg-slate-100 px-3 py-1">${escapeHtml(row.group_name || 'Sin agrupacion especifica')}</span>
                                <span class="rounded-full bg-[#d94d33]/10 px-3 py-1 text-[#d94d33]">${escapeHtml(row.element_type_name ?? 'Sin tipo')}</span>
                            </div>

                            <div class="flex flex-wrap gap-4 text-sm text-slate-500">
                                <span>${escapeHtml(String(row.columns_count ?? 0))} columnas configuradas</span>
                            </div>

                            ${descriptionHtml}
                        </div>

                        <div class="flex items-center justify-end gap-2">
                            <button
                                type="button"
                                onclick="openSemaphoreTemplateModal(${row.id})"
                                class="text-slate-400 transition hover:text-[#d94d33]"
                                title="Configurar plantilla"
                            >
                                <i data-lucide="settings-2" class="h-4 w-4"></i>
                            </button>

                            <button
                                type="button"
                                onclick="toggleSemaphoreTemplateStatus(${row.id})"
                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition ${row.status
                                    ? 'bg-green-100 text-green-700 hover:bg-green-200'
                                    : 'bg-red-100 text-red-700 hover:bg-red-200'}"
                                title="${row.status ? 'Desactivar plantilla' : 'Activar plantilla'}"
                            >
                                <i data-lucide="${row.status ? 'check-circle-2' : 'x-circle'}" class="h-3.5 w-3.5"></i>
                                <span>${row.status ? 'Activa' : 'Inactiva'}</span>
                            </button>

                            <button
                                type="button"
                                onclick="destroySemaphoreTemplate(${row.id})"
                                class="text-red-500 transition hover:text-red-700"
                                title="Eliminar plantilla"
                            >
                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                            </button>
                        </div>
                    </div>
                </article>
            `;
        }

        function normalizeDefaultTemplateScope(row) {
            if (!row?.is_default) {
                return;
            }

            const scopeSelector = `[id^="semaphore-template-row-"][data-client-id="${String(row.client_id ?? '')}"][data-group-id="${String(row.group_id ?? '')}"][data-element-type-id="${String(row.element_type_id ?? '')}"]`;

            document.querySelectorAll(scopeSelector).forEach((card) => {
                if (card.id === `semaphore-template-row-${row.id}`) {
                    return;
                }

                const badge = card.querySelector('.bg-emerald-100.text-emerald-700');
                badge?.remove();
            });
        }

        function insertSemaphoreTemplateRow(row) {
            if (!row || !row.id) {
                return;
            }

            const list = document.getElementById('semaphoreTemplatesList');
            if (!list) {
                return;
            }

            document.getElementById('semaphore-templates-empty-row')?.remove();

            const wrapper = document.createElement('div');
            wrapper.innerHTML = buildSemaphoreTemplateRow(row);

            const element = wrapper.firstElementChild;
            element.style.opacity = '0';
            element.style.transform = 'translateY(-6px)';
            element.style.transition = 'opacity 180ms ease, transform 180ms ease';

            list.prepend(element);

            requestAnimationFrame(() => {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            });

            normalizeDefaultTemplateScope(row);

            if (window.lucide) {
                window.lucide.createIcons();
            }

            updateSemaphoreTemplateStats();
        }

        function upsertSemaphoreTemplateRow(row) {
            if (!row || !row.id) {
                return;
            }

            const current = document.getElementById(`semaphore-template-row-${row.id}`);
            if (!current) {
                insertSemaphoreTemplateRow(row);
                return;
            }

            current.outerHTML = buildSemaphoreTemplateRow(row);
            normalizeDefaultTemplateScope(row);

            if (window.lucide) {
                window.lucide.createIcons();
            }

            updateSemaphoreTemplateStats();
        }

        function removeSemaphoreTemplateRow(id) {
            const row = document.getElementById(`semaphore-template-row-${id}`);
            if (!row) {
                return;
            }

            row.style.opacity = '0';
            row.style.transform = 'translateY(-6px)';
            row.style.transition = 'opacity 160ms ease, transform 160ms ease';

            setTimeout(() => {
                row.remove();

                const list = document.getElementById('semaphoreTemplatesList');
                if (list && !list.querySelector('[id^="semaphore-template-row-"]')) {
                    list.innerHTML = `
                        <div id="semaphore-templates-empty-row" class="px-6 py-12 text-center text-sm text-slate-500">
                            No hay plantillas registradas todavia.
                        </div>
                    `;
                }

                updateSemaphoreTemplateStats();
            }, 180);
        }

        function closeSemaphoreTemplateModal() {
            const modal = document.getElementById('editSemaphoreTemplateModal');
            const content = document.getElementById('editSemaphoreTemplateModalContent');

            clearAjaxErrors('editSemaphoreTemplateAjaxErrors');

            content?.classList.remove('scale-100', 'opacity-100');
            content?.classList.add('scale-95', 'opacity-0');

            setTimeout(() => {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
                document.documentElement.classList.remove('overflow-hidden');
                document.body.classList.remove('overflow-hidden');
            }, 150);
        }

        function openSemaphoreTemplateModalShell() {
            const modal = document.getElementById('editSemaphoreTemplateModal');
            const content = document.getElementById('editSemaphoreTemplateModalContent');

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.documentElement.classList.add('overflow-hidden');
            document.body.classList.add('overflow-hidden');

            setTimeout(() => {
                content?.classList.remove('scale-95', 'opacity-0');
                content?.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function fillSemaphoreTemplateModalHeader(template) {
            document.getElementById('modal_template_client_id').value = template.client_id ?? '';
            document.getElementById('modal_template_group_id').value = template.group_id ?? '';
            document.getElementById('modal_template_element_type_id').value = template.element_type_id ?? '';
            document.getElementById('modal_template_name_input').value = template.name ?? '';
            document.getElementById('modal_template_description_input').value = template.description ?? '';

            const isDefaultInput = document.getElementById('modal_template_is_default_input');
            const statusInput = document.getElementById('modal_template_status_input');
            isDefaultInput.value = template.is_default ? '1' : '0';
            statusInput.value = template.status ? '1' : '0';

            document.getElementById('modal_template_name').textContent = template.name || 'Plantilla';
            document.getElementById('modal_template_description').textContent =
                template.description || 'Esta plantilla define el alcance y las reglas que alimentan este semaforo.';

            const badges = [
                `<span class="rounded-full bg-slate-100 px-3 py-1">${escapeHtml(template.client_name || 'Sin cliente')}</span>`,
                `<span class="rounded-full bg-slate-100 px-3 py-1">${escapeHtml(template.group_name || 'Sin agrupacion especifica')}</span>`,
                `<span class="rounded-full bg-[#d94d33]/10 px-3 py-1 text-[#d94d33]">${escapeHtml(template.element_type_name || 'Sin tipo')}</span>`,
            ];

            if (template.is_default) {
                badges.push('<span class="rounded-full bg-emerald-100 px-3 py-1 text-emerald-700">Predeterminada</span>');
            }

            badges.push(
                `<span class="rounded-full ${template.status ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'} px-3 py-1">${template.status ? 'Activa' : 'Inactiva'}</span>`
            );

            document.getElementById('modal_template_badges').innerHTML = badges.join('');
        }

        async function openSemaphoreTemplateModal(id) {
            const form = document.getElementById('editSemaphoreTemplateForm');

            try {
                clearAjaxErrors('editSemaphoreTemplateAjaxErrors');
                openSemaphoreTemplateModalShell();
                document.getElementById('modal_template_name').textContent = 'Cargando plantilla...';
                document.getElementById('modal_template_description').textContent = 'Preparando configuracion.';
                document.getElementById('modal_template_badges').innerHTML = '';
                document.getElementById('semaphoreColumnsBuilder').innerHTML = `
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
                        Cargando configuracion...
                    </div>
                `;

                const response = await fetch(`${semaphoreTemplatesRoutes.toggleBase}/${id}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const data = await parseSemaphoreTemplateJsonResponse(response);

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'No fue posible cargar la plantilla.');
                }

                const template = data.template || {};
                const options = data.options || {};

                semaphoreBuilderState.templateId = template.id ?? null;
                semaphoreBuilderState.components = template.components || [];
                semaphoreBuilderState.diagnostics = template.diagnostics || [];
                semaphoreBuilderState.columns = (template.columns || []).map((column) => ({
                    ...column,
                    expanded: false,
                }));
                semaphoreBuilderState.columnTypes = options.column_types || {};
                semaphoreBuilderState.directions = options.directions || {};
                semaphoreBuilderState.emptyStates = options.empty_states || {};

                form.action = template.update_url || '';

                fillSemaphoreTemplateModalHeader(template);
                renderSemaphoreColumns();
            } catch (error) {
                showCrudToast(error.message || 'Ocurrio un error al abrir la configuracion.', 'error');
                closeSemaphoreTemplateModal();
            }
        }

        function semaphoreOptionHtml(options, selectedValue, placeholder) {
            const rows = [`<option value="">${placeholder}</option>`];

            Object.entries(options).forEach(([value, label]) => {
                rows.push(`<option value="${escapeHtml(value)}" ${String(selectedValue) === String(value) ? 'selected' : ''}>${escapeHtml(label)}</option>`);
            });

            return rows.join('');
        }

        function diagnosticsForComponent(componentId) {
            const normalizedComponentId = String(componentId || '');

            if (!normalizedComponentId) {
                return [];
            }

            const component = semaphoreBuilderState.components.find(item => String(item.id) === normalizedComponentId);

            if (!component) {
                return [];
            }

            const allowedDiagnosticIds = new Set((component.diagnostic_ids || []).map(item => String(item)));

            return (semaphoreBuilderState.diagnostics || []).filter(item => allowedDiagnosticIds.has(String(item.id)));
        }

        async function parseSemaphoreTemplateJsonResponse(response) {
            const contentType = response.headers.get('content-type') || '';

            if (!contentType.includes('application/json')) {
                throw new Error('El servidor no devolvio JSON. Revisa la respuesta del backend.');
            }

            return await response.json();
        }

        function handleSemaphoreRuleComponentChange(columnIndex, ruleIndex, componentId) {
            syncSemaphoreBuilderStateFromDom();

            const column = semaphoreBuilderState.columns[columnIndex];
            const rule = column?.rules?.[ruleIndex];

            if (!rule) {
                return;
            }

            rule.component_id = componentId || '';

            const allowedDiagnostics = diagnosticsForComponent(componentId);
            const diagnosticStillAllowed = allowedDiagnostics.some(item => String(item.id) === String(rule.diagnostic_id || ''));

            if (allowedDiagnostics.length === 1) {
                rule.diagnostic_id = String(allowedDiagnostics[0].id);
            } else if (!diagnosticStillAllowed) {
                rule.diagnostic_id = '';
            }

            renderSemaphoreColumns();
        }

        function updateSemaphoreBuilderSummary() {
            const columnsCountNode = document.getElementById('modal_semaphoreColumnsCount');
            const rulesCountNode = document.getElementById('modal_semaphoreRulesCount');
            const columnsCount = semaphoreBuilderState.columns.length;
            const rulesCount = semaphoreBuilderState.columns.reduce((sum, column) => sum + ((column.rules || []).length), 0);

            if (columnsCountNode) {
                columnsCountNode.textContent = String(columnsCount);
            }

            if (rulesCountNode) {
                rulesCountNode.textContent = String(rulesCount);
            }
        }

        function syncSemaphoreBuilderStateFromDom() {
            const container = document.getElementById('semaphoreColumnsBuilder');

            if (!container || !semaphoreBuilderState.columns.length) {
                return;
            }

            semaphoreBuilderState.columns = semaphoreBuilderState.columns.map((column, columnIndex) => {
                const nextColumn = {
                    ...column,
                    id: container.querySelector(`[name="columns[${columnIndex}][id]"]`)?.value ?? column.id ?? '',
                    label: container.querySelector(`[name="columns[${columnIndex}][label]"]`)?.value ?? column.label ?? '',
                    key: container.querySelector(`[name="columns[${columnIndex}][key]"]`)?.value ?? column.key ?? '',
                    description: container.querySelector(`[name="columns[${columnIndex}][description]"]`)?.value ?? column.description ?? '',
                    column_type: container.querySelector(`[name="columns[${columnIndex}][column_type]"]`)?.value ?? column.column_type ?? 'condition_aggregate',
                    severity_direction: container.querySelector(`[name="columns[${columnIndex}][severity_direction]"]`)?.value ?? column.severity_direction ?? 'asc',
                    empty_state_behavior: container.querySelector(`[name="columns[${columnIndex}][empty_state_behavior]"]`)?.value ?? column.empty_state_behavior ?? 'neutral',
                    source_column_key: container.querySelector(`[name="columns[${columnIndex}][source_column_key]"]`)?.value ?? column.source_column_key ?? '',
                    position: columnIndex,
                    status: (container.querySelector(`[name="columns[${columnIndex}][status]"]`)?.value ?? (column.status ? '1' : '0')) === '1',
                    expanded: column.expanded !== false,
                    rules: [],
                };

                const ruleCount = (column.rules || []).length;

                nextColumn.rules = Array.from({ length: ruleCount }).map((_, ruleIndex) => ({
                    component_id: container.querySelector(`[name="columns[${columnIndex}][rules][${ruleIndex}][component_id]"]`)?.value ?? column.rules?.[ruleIndex]?.component_id ?? '',
                    diagnostic_id: container.querySelector(`[name="columns[${columnIndex}][rules][${ruleIndex}][diagnostic_id]"]`)?.value ?? column.rules?.[ruleIndex]?.diagnostic_id ?? '',
                }));

                nextColumn.rules = nextColumn.rules.map((rule) => {
                    const allowedDiagnostics = diagnosticsForComponent(rule.component_id);
                    const diagnosticStillAllowed = allowedDiagnostics.some(item => String(item.id) === String(rule.diagnostic_id || ''));

                    return {
                        ...rule,
                        diagnostic_id: allowedDiagnostics.length === 1
                            ? String(allowedDiagnostics[0].id)
                            : (diagnosticStillAllowed ? rule.diagnostic_id : ''),
                    };
                });

                return nextColumn;
            });
        }

        function addSemaphoreColumn(column = null) {
            syncSemaphoreBuilderStateFromDom();

            semaphoreBuilderState.columns.push(column || {
                id: '',
                label: '',
                key: '',
                description: '',
                column_type: 'condition_aggregate',
                severity_direction: 'asc',
                empty_state_behavior: 'neutral',
                source_column_key: '',
                position: semaphoreBuilderState.columns.length,
                status: true,
                expanded: true,
                rules: [],
            });

            renderSemaphoreColumns();
        }

        function removeSemaphoreColumn(index) {
            syncSemaphoreBuilderStateFromDom();
            semaphoreBuilderState.columns.splice(index, 1);
            renderSemaphoreColumns();
        }

        function addSemaphoreRule(columnIndex, rule = null) {
            syncSemaphoreBuilderStateFromDom();
            semaphoreBuilderState.columns[columnIndex].rules.push(rule || {
                component_id: '',
                diagnostic_id: '',
            });
            renderSemaphoreColumns();
        }

        function removeSemaphoreRule(columnIndex, ruleIndex) {
            syncSemaphoreBuilderStateFromDom();
            semaphoreBuilderState.columns[columnIndex].rules.splice(ruleIndex, 1);
            renderSemaphoreColumns();
        }

        function toggleSemaphoreColumnExpanded(columnIndex) {
            syncSemaphoreBuilderStateFromDom();

            const column = semaphoreBuilderState.columns[columnIndex];
            if (!column) {
                return;
            }

            column.expanded = !(column.expanded !== false);
            renderSemaphoreColumns();
        }

        function renderSemaphoreColumns() {
            const container = document.getElementById('semaphoreColumnsBuilder');

            if (!container) {
                return;
            }

            if (!semaphoreBuilderState.columns.length) {
                container.innerHTML = `
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
                        Todavia no hay columnas. Agrega la primera para comenzar el constructor.
                    </div>
                `;
                updateSemaphoreBuilderSummary();
                return;
            }

            container.innerHTML = semaphoreBuilderState.columns.map((column, columnIndex) => `
                <section class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <button
                            type="button"
                            onclick="toggleSemaphoreColumnExpanded(${columnIndex})"
                            class="flex min-w-0 flex-1 items-start justify-between gap-4 rounded-xl text-left transition hover:bg-slate-100/80 lg:pr-3"
                        >
                            <div class="min-w-0">
                                <div class="text-sm font-bold text-slate-900">Columna ${columnIndex + 1}${column.label ? `: ${escapeHtml(column.label)}` : ''}</div>
                                <div class="mt-1 text-xs text-slate-500">
                                    ${(column.expanded !== false)
                                        ? 'Define la regla, prioridad y componentes de esta columna.'
                                        : `${(column.rules || []).length} regla(s) configurada(s)`}
                                </div>
                            </div>
                            <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500">
                                <i data-lucide="${column.expanded !== false ? 'chevron-up' : 'chevron-down'}" class="h-4 w-4"></i>
                            </span>
                        </button>
                        <div class="flex items-center gap-2">
                            ${(column.expanded !== false) ? `
                                <button
                                    type="button"
                                    onclick="toggleSemaphoreColumnExpanded(${columnIndex})"
                                    class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                                >
                                    <i data-lucide="minimize-2" class="h-4 w-4"></i>
                                    Ocultar
                                </button>
                            ` : ''}
                            <button
                                type="button"
                                onclick="removeSemaphoreColumn(${columnIndex})"
                                class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-100"
                            >
                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                                Eliminar
                            </button>
                        </div>
                    </div>

                    <input type="hidden" name="columns[${columnIndex}][id]" value="${escapeHtml(column.id || '')}">
                    <input type="hidden" name="columns[${columnIndex}][position]" value="${columnIndex}">
                    <input type="hidden" name="columns[${columnIndex}][status]" value="${column.status ? '1' : '0'}">

                    <div class="mt-5 ${column.expanded !== false ? '' : 'hidden'}">
                    <div class="grid gap-4 xl:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Nombre visible</label>
                            <input type="text" name="columns[${columnIndex}][label]" value="${escapeHtml(column.label || '')}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Clave interna</label>
                            <input type="text" name="columns[${columnIndex}][key]" value="${escapeHtml(column.key || '')}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]" placeholder="ej. seguridad">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Tipo de columna</label>
                            <select name="columns[${columnIndex}][column_type]" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]">
                                ${semaphoreOptionHtml(semaphoreBuilderState.columnTypes, column.column_type, 'Seleccione')}
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Direccion de criticidad</label>
                            <select name="columns[${columnIndex}][severity_direction]" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]">
                                ${semaphoreOptionHtml(semaphoreBuilderState.directions, column.severity_direction, 'Seleccione')}
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Estado vacio</label>
                            <select name="columns[${columnIndex}][empty_state_behavior]" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]">
                                ${semaphoreOptionHtml(semaphoreBuilderState.emptyStates, column.empty_state_behavior, 'Seleccione')}
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Columna fuente</label>
                            <input type="text" name="columns[${columnIndex}][source_column_key]" value="${escapeHtml(column.source_column_key || '')}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]" placeholder="Opcional, para columnas dependientes">
                        </div>
                        <div class="xl:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-slate-700">Descripcion</label>
                            <textarea name="columns[${columnIndex}][description]" rows="2" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]">${escapeHtml(column.description || '')}</textarea>
                        </div>
                    </div>

                    <div class="mt-5 rounded-2xl border border-slate-200 bg-white p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="text-sm font-semibold text-slate-900">Reglas de componentes y diagnosticos</div>
                                <div class="mt-1 text-xs text-slate-500">Define que pares componente + diagnostico alimentan esta columna.</div>
                            </div>
                            <button
                                type="button"
                                onclick="addSemaphoreRule(${columnIndex})"
                                class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                            >
                                <i data-lucide="plus" class="h-4 w-4"></i>
                                Agregar regla
                            </button>
                        </div>

                        <div class="mt-4 space-y-3">
                            ${(column.rules || []).map((rule, ruleIndex) => `
                                ${(() => {
                                    const filteredDiagnostics = diagnosticsForComponent(rule.component_id);
                                    return `
                                <div class="grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-3 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]">
                                    <div>
                                        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Componente</label>
                                        <select name="columns[${columnIndex}][rules][${ruleIndex}][component_id]" onchange="handleSemaphoreRuleComponentChange(${columnIndex}, ${ruleIndex}, this.value)" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]">
                                            <option value="">Seleccione</option>
                                            ${semaphoreBuilderState.components.map(item => `<option value="${item.id}" ${String(rule.component_id) === String(item.id) ? 'selected' : ''}>${escapeHtml(item.label)}</option>`).join('')}
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Diagnostico</label>
                                        <select name="columns[${columnIndex}][rules][${ruleIndex}][diagnostic_id]" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]">
                                            <option value="">Seleccione</option>
                                            ${filteredDiagnostics.map(item => `<option value="${item.id}" ${String(rule.diagnostic_id) === String(item.id) ? 'selected' : ''}>${escapeHtml(item.label)}</option>`).join('')}
                                        </select>
                                    </div>
                                    <div class="flex items-end">
                                        <button
                                            type="button"
                                            onclick="removeSemaphoreRule(${columnIndex}, ${ruleIndex})"
                                            class="inline-flex h-[42px] items-center justify-center rounded-xl border border-red-200 bg-red-50 px-3 text-sm font-semibold text-red-700 transition hover:bg-red-100"
                                        >
                                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                                        </button>
                                    </div>
                                </div>
                                    `;
                                })()}
                            `).join('') || `
                                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                                    Esta columna todavia no tiene reglas.
                                </div>
                            `}
                        </div>
                    </div>
                    </div>
                </section>
            `).join('');

            updateSemaphoreBuilderSummary();

            if (window.lucide) {
                window.lucide.createIcons();
            }
        }

        async function handleCreateSemaphoreTemplateSubmit(event) {
            event.preventDefault();

            const form = event.currentTarget;
            clearAjaxErrors('createSemaphoreTemplateAjaxErrors');
            setFormSubmittingState(form, true, 'Creando...');

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: new FormData(form),
                });

                const data = await parseSemaphoreTemplateJsonResponse(response);

                if (response.status === 422) {
                    renderAjaxErrors('createSemaphoreTemplateAjaxErrors', data.errors || {});
                    showCrudToast('Corrige los errores del formulario.', 'error');
                    return;
                }

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'No fue posible crear la plantilla.');
                }

                insertSemaphoreTemplateRow(data.row);
                resetSemaphoreTemplateCreateForm();
                showCrudToast(data.message || 'Plantilla creada correctamente.', 'success');
            } catch (error) {
                showCrudToast(error.message || 'Ocurrio un error al crear la plantilla.', 'error');
            } finally {
                setFormSubmittingState(form, false);
            }
        }

        async function handleEditSemaphoreTemplateSubmit(event) {
            event.preventDefault();

            const form = event.currentTarget;
            syncSemaphoreBuilderStateFromDom();
            clearAjaxErrors('editSemaphoreTemplateAjaxErrors');
            setFormSubmittingState(form, true, 'Guardando...');

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: new FormData(form),
                });

                const data = await parseSemaphoreTemplateJsonResponse(response);

                if (response.status === 422) {
                    renderAjaxErrors('editSemaphoreTemplateAjaxErrors', data.errors || {});
                    showCrudToast('Corrige los errores del formulario.', 'error');
                    return;
                }

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'No fue posible actualizar la plantilla.');
                }

                upsertSemaphoreTemplateRow(data.row);
                showCrudToast(data.message || 'Plantilla actualizada correctamente.', 'success');
            } catch (error) {
                showCrudToast(error.message || 'Ocurrio un error al actualizar la plantilla.', 'error');
            } finally {
                setFormSubmittingState(form, false);
            }
        }

        async function toggleSemaphoreTemplateStatus(id) {
            try {
                const response = await fetch(`${semaphoreTemplatesRoutes.toggleBase}/${id}/toggle-status`, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                });

                const data = await parseSemaphoreTemplateJsonResponse(response);

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'No fue posible actualizar el estado.');
                }

                upsertSemaphoreTemplateRow(data.row);
                showCrudToast(data.message || 'Estado actualizado correctamente.', 'success');
            } catch (error) {
                showCrudToast(error.message || 'Ocurrio un error al actualizar el estado.', 'error');
            }
        }

        async function destroySemaphoreTemplate(id) {
            if (!window.confirm('Seguro que deseas eliminar esta plantilla?')) {
                return;
            }

            try {
                const response = await fetch(`${semaphoreTemplatesRoutes.toggleBase}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                });

                const data = await parseSemaphoreTemplateJsonResponse(response);

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'No fue posible eliminar la plantilla.');
                }

                if (semaphoreBuilderState.templateId === id) {
                    closeSemaphoreTemplateModal();
                }

                removeSemaphoreTemplateRow(id);
                showCrudToast(data.message || 'Plantilla eliminada correctamente.', 'success');
            } catch (error) {
                showCrudToast(error.message || 'Ocurrio un error al eliminar la plantilla.', 'error');
            }
        }

        document.addEventListener('click', function (event) {
            const modal = document.getElementById('editSemaphoreTemplateModal');

            if (modal.classList.contains('flex') && event.target === modal) {
                closeSemaphoreTemplateModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeSemaphoreTemplateModal();
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            const clientSelect = document.getElementById('create_client_id');
            const createForm = document.getElementById('createSemaphoreTemplateForm');
            const editForm = document.getElementById('editSemaphoreTemplateForm');

            if (clientSelect) {
                clientSelect.dataset.prefillValue = clientSelect.value;
                clientSelect.addEventListener('change', filterSemaphoreCreateOptions);
            }

            filterSemaphoreCreateOptions();
            updateSemaphoreTemplateStats();

            if (createForm && !createForm.querySelector('button[type="submit"]')?.disabled) {
                createForm.addEventListener('submit', handleCreateSemaphoreTemplateSubmit);
            }

            if (editForm) {
                editForm.addEventListener('submit', handleEditSemaphoreTemplateSubmit);
            }

            if (window.lucide) {
                window.lucide.createIcons();
            }
        });
    </script>
@endsection
