@extends('layouts.admin')

@section('title', 'Constructor de semaforo')
@section('header_title', 'Constructor de semaforo')

@section('content')
    @php
        $initialSemaphoreColumns = old(
            'columns',
            $template->columns->map(function ($column) {
                return [
                    'id' => $column->id,
                    'label' => $column->label,
                    'key' => $column->key,
                    'description' => $column->description,
                    'column_type' => $column->column_type,
                    'severity_direction' => $column->severity_direction,
                    'empty_state_behavior' => $column->empty_state_behavior,
                    'source_column_key' => $column->source_column_key,
                    'position' => $column->position,
                    'status' => $column->status,
                    'rules' => $column->rules->map(function ($rule) {
                        return [
                            'component_id' => $rule->component_id,
                            'diagnostic_id' => $rule->diagnostic_id,
                        ];
                    })->values()->all(),
                ];
            })->values()->all()
        );
    @endphp

    <div class="space-y-8">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-orange-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-orange-700">
                    <i data-lucide="traffic-cone" class="h-3.5 w-3.5"></i>
                    Plantilla de semaforo
                </div>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">Configurar plantilla</h2>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('admin.semaphore-templates.index') }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                >
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                    Volver
                </a>
                <a
                    href="{{ route('admin.indicators.index') }}"
                    class="inline-flex items-center gap-2 rounded-xl bg-[#d94d33] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                >
                    <i data-lucide="bar-chart-3" class="h-4 w-4"></i>
                    Ir a indicadores
                </a>
            </div>
        </div>

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

        <div id="editSemaphoreTemplateAjaxErrors" class="hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

        <form method="POST" action="{{ route('admin.semaphore-templates.update', $template) }}" class="space-y-8" id="semaphoreTemplateBuilder">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <input type="hidden" name="client_id" id="edit_client_id" value="{{ old('client_id', $template->client_id) }}">
                    <input type="hidden" name="group_id" id="edit_group_id" value="{{ old('group_id', $template->group_id) }}">
                    <input type="hidden" name="element_type_id" id="edit_element_type_id" value="{{ old('element_type_id', $template->element_type_id) }}">
                    <input type="hidden" name="name" value="{{ old('name', $template->name) }}">
                    <input type="hidden" name="description" value="{{ old('description', $template->description) }}">
                    @if(old('is_default', $template->is_default))
                        <input type="hidden" name="is_default" value="1">
                    @endif
                    @if(old('status', $template->status))
                        <input type="hidden" name="status" value="1">
                    @endif

                    <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                        <div class="min-w-0">
                            <h3 class="text-xl font-semibold text-slate-900">{{ old('name', $template->name) }}</h3>

                            <div class="mt-2 flex flex-wrap gap-2 text-xs font-semibold text-slate-600">
                                <span class="rounded-full bg-slate-100 px-3 py-1">{{ $template->client?->name }}</span>
                                <span class="rounded-full bg-slate-100 px-3 py-1">{{ $template->group?->name ?: 'Sin agrupacion especifica' }}</span>
                                <span class="rounded-full bg-[#d94d33]/10 px-3 py-1 text-[#d94d33]">{{ $template->elementType?->name }}</span>
                                @if(old('is_default', $template->is_default))
                                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-emerald-700">Predeterminada</span>
                                @endif
                                <span class="rounded-full {{ old('status', $template->status) ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }} px-3 py-1">
                                    {{ old('status', $template->status) ? 'Activa' : 'Inactiva' }}
                                </span>
                            </div>

                            @if(old('description', $template->description))
                                <p class="mt-4 max-w-4xl text-sm leading-6 text-slate-500">
                                    {{ old('description', $template->description) }}
                                </p>
                            @else

                            @endif
                        </div>

                        <div class="grid grid-cols-2 gap-3 sm:flex sm:flex-wrap">
                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Columnas</div>
                                <div id="semaphoreColumnsCount" class="mt-1 text-xl font-bold text-slate-900">{{ $template->columns->count() }}</div>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Reglas</div>
                                <div id="semaphoreRulesCount" class="mt-1 text-xl font-bold text-emerald-700">{{ $template->columns->sum(fn($column) => $column->rules->count()) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">

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

                    <div class="sticky bottom-4 z-20">
                        <div class="rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-2xl backdrop-blur">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#d94d33] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                                >
                                    <i data-lucide="save" class="h-4 w-4"></i>
                                    Guardar plantilla
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        const semaphoreBuilderState = {
            components: @json($components->map(fn($item) => ['id' => $item->id, 'label' => trim(($item->code ? $item->code . ' - ' : '') . $item->name)])),
            diagnostics: @json($diagnostics->map(fn($item) => ['id' => $item->id, 'label' => $item->name])),
            columns: @json($initialSemaphoreColumns),
            columnTypes: @json($columnTypeOptions),
            directions: @json($directionOptions),
            emptyStates: @json($emptyStateOptions),
        };

        function escapeHtml(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function showCrudToast(message, type = 'success') {
            const toastId = 'semaphoreTemplateEditToast';
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

            clearTimeout(window.__semaphoreTemplateEditToastTimeout);
            window.__semaphoreTemplateEditToastTimeout = setTimeout(() => {
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
            box.scrollIntoView({ behavior: 'smooth', block: 'center' });
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

        function semaphoreOptionHtml(options, selectedValue, placeholder) {
            const rows = [`<option value="">${placeholder}</option>`];

            Object.entries(options).forEach(([value, label]) => {
                rows.push(`<option value="${escapeHtml(value)}" ${String(selectedValue) === String(value) ? 'selected' : ''}>${escapeHtml(label)}</option>`);
            });

            return rows.join('');
        }

        function updateSemaphoreBuilderSummary() {
            const columnsCountNode = document.getElementById('semaphoreColumnsCount');
            const rulesCountNode = document.getElementById('semaphoreRulesCount');
            const columnsCount = semaphoreBuilderState.columns.length;
            const rulesCount = semaphoreBuilderState.columns.reduce((sum, column) => {
                return sum + ((column.rules || []).length);
            }, 0);

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
                    rules: [],
                };

                const ruleCount = (column.rules || []).length;

                nextColumn.rules = Array.from({ length: ruleCount }).map((_, ruleIndex) => ({
                    component_id: container.querySelector(`[name="columns[${columnIndex}][rules][${ruleIndex}][component_id]"]`)?.value ?? column.rules?.[ruleIndex]?.component_id ?? '',
                    diagnostic_id: container.querySelector(`[name="columns[${columnIndex}][rules][${ruleIndex}][diagnostic_id]"]`)?.value ?? column.rules?.[ruleIndex]?.diagnostic_id ?? '',
                }));

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
                        <div>
                            <div class="text-sm font-bold text-slate-900">Columna ${columnIndex + 1}</div>
                            <div class="mt-1 text-xs text-slate-500">Define la regla, prioridad y componentes de esta columna.</div>
                        </div>
                        <button
                            type="button"
                            onclick="removeSemaphoreColumn(${columnIndex})"
                            class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-100"
                        >
                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                            Eliminar
                        </button>
                    </div>

                    <input type="hidden" name="columns[${columnIndex}][id]" value="${escapeHtml(column.id || '')}">
                    <input type="hidden" name="columns[${columnIndex}][position]" value="${columnIndex}">
                    <input type="hidden" name="columns[${columnIndex}][status]" value="${column.status ? '1' : '0'}">

                    <div class="mt-5 grid gap-4 xl:grid-cols-2">
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
                                <div class="grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-3 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]">
                                    <div>
                                        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Componente</label>
                                        <select name="columns[${columnIndex}][rules][${ruleIndex}][component_id]" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]">
                                            <option value="">Seleccione</option>
                                            ${semaphoreBuilderState.components.map(item => `<option value="${item.id}" ${String(rule.component_id) === String(item.id) ? 'selected' : ''}>${escapeHtml(item.label)}</option>`).join('')}
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Diagnostico</label>
                                        <select name="columns[${columnIndex}][rules][${ruleIndex}][diagnostic_id]" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]">
                                            <option value="">Seleccione</option>
                                            ${semaphoreBuilderState.diagnostics.map(item => `<option value="${item.id}" ${String(rule.diagnostic_id) === String(item.id) ? 'selected' : ''}>${escapeHtml(item.label)}</option>`).join('')}
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
                            `).join('') || `
                                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                                    Esta columna todavia no tiene reglas.
                                </div>
                            `}
                        </div>
                    </div>
                </section>
            `).join('');

            updateSemaphoreBuilderSummary();

            if (window.lucide) {
                window.lucide.createIcons();
            }
        }

        function filterSemaphoreEditOptions() {
            return;
        }

        async function handleSemaphoreTemplateBuilderSubmit(event) {
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

                const data = await response.json();

                if (response.status === 422) {
                    renderAjaxErrors('editSemaphoreTemplateAjaxErrors', data.errors || {});
                    showCrudToast('Corrige los errores del formulario.', 'error');
                    return;
                }

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'No fue posible guardar la plantilla.');
                }

                showCrudToast(data.message || 'Plantilla actualizada correctamente.', 'success');
            } catch (error) {
                showCrudToast(error.message || 'Ocurrio un error al guardar la plantilla.', 'error');
            } finally {
                setFormSubmittingState(form, false);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('semaphoreTemplateBuilder')?.addEventListener('submit', handleSemaphoreTemplateBuilderSubmit);

            renderSemaphoreColumns();

            if (window.lucide) {
                window.lucide.createIcons();
            }
        });
    </script>
@endsection
