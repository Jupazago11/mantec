@extends('layouts.admin')
@section('title', 'Componentes - Relaciones')
@section('header_title', 'Componentes - Relaciones')

@section('content')
    <div class="space-y-8">
        <div class="grid gap-8 xl:grid-cols-[340px_minmax(0,1fr)]">
            <div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Nueva asignación</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Selecciona cliente, tipo de activo y componente para asignar sus diagnósticos.
                    </p>

                    <form method="POST" action="{{ route('admin.managed-component-diagnostics.store') }}" class="mt-6 space-y-5">
                        @csrf

                        @if($singleClient)
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Cliente</label>
                                <div class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                    {{ $singleClient->name }}
                                </div>
                                <input type="hidden" id="client_id" value="{{ old('client_id', $preferredClientId ?? $singleClient->id) }}">
                            </div>
                        @else
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Cliente</label>
                                <div class="max-h-56 space-y-2 overflow-y-auto rounded-xl border border-slate-300 p-4">
                                    @foreach($clients as $client)
                                        <label class="flex items-center gap-3 text-sm text-slate-700">
                                            <input
                                                type="checkbox"
                                                value="{{ $client->id }}"
                                                class="client-single-checkbox rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                                                @checked((string) old('client_id', $preferredClientId ?? '') === (string) $client->id)
                                                onchange="selectClient(this)"
                                            >
                                            {{ $client->name }}
                                        </label>
                                    @endforeach
                                </div>
                                <input type="hidden" id="client_id" value="{{ old('client_id', $preferredClientId ?? '') }}">
                            </div>
                        @endif

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Tipo de activo</label>
                            <select
                                id="element_type_id"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            >
                                <option value="">Seleccione un tipo de activo</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Componente</label>
                            <select
                                name="component_id"
                                id="component_id"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            >
                                <option value="">Seleccione un componente</option>
                            </select>
                        </div>
                        <input type="hidden" id="preferred_element_type_id" value="{{ old('element_type_id', $preferredElementTypeId ?? '') }}">
                        <input type="hidden" id="preferred_component_id" value="{{ old('component_id', $preferredComponentId ?? '') }}">

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Diagnósticos</label>
                            <div
                                id="diagnostics_list"
                                class="max-h-72 space-y-2 overflow-y-auto rounded-xl border border-slate-300 p-4"
                            >
                                <p class="text-sm text-slate-500">
                                    Selecciona primero un cliente y un tipo de activo para cargar los diagnósticos.

                                </p>
                            </div>
                        </div>

                                        <button
                            type="submit"
                            id="cdSubmitBtn"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                        >
                            Guardar asignación
                        </button>
                    </form>
                </div>
            </div>
<div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Cómo funciona esta asignación</h3>

                    <p class="mt-2 text-sm text-slate-600">
                        Este módulo define los diagnósticos válidos para cada componente. Luego, en la operación,
                        el inspector solo podrá diligenciar diagnósticos que realmente estén asignados al componente
                        seleccionado.
                    </p>

                    <div class="mt-6 grid gap-4 md:grid-cols-3">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-900">1. Cliente</p>
                            <p class="mt-1 text-sm text-slate-600">
                                Selecciona el cliente sobre el cual vas a trabajar.
                            </p>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-900">2. Componente</p>
                            <p class="mt-1 text-sm text-slate-600">
                                Elige el tipo de activo y luego el componente específico.
                            </p>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-900">3. Diagnósticos</p>
                            <p class="mt-1 text-sm text-slate-600">
                                Marca todos los diagnósticos que deben quedar asociados.
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        Si cambias las selecciones y vuelves a guardar, la relación del componente se actualizará con los diagnósticos actualmente marcados.
                    </div>
                </div>
            </div>
        </div>
    </div>
<script>
    function resetSelect(selectId, placeholder) {
        const select = document.getElementById(selectId);

        if (!select) return;

        select.innerHTML = `<option value="">${placeholder}</option>`;
    }

    function resetDiagnostics(message = 'Selecciona primero un cliente para cargar los diagnósticos.') {
        const container = document.getElementById('diagnostics_list');

        if (!container) return;

        container.innerHTML = `
            <p class="text-sm text-slate-500">${message}</p>
        `;
    }

    function getClientId() {
        return document.getElementById('client_id')?.value ?? '';
    }

    function getElementTypeId() {
        return document.getElementById('element_type_id')?.value ?? '';
    }

    function getComponentId() {
        return document.getElementById('component_id')?.value ?? '';
    }

    function markSingleClientCheckbox(selectedValue) {
        document.querySelectorAll('.client-single-checkbox').forEach(checkbox => {
            checkbox.checked = String(checkbox.value) === String(selectedValue);
        });
    }

    async function fetchJson(url) {
        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error(`Error HTTP ${response.status}`);
        }

        return await response.json();
    }

    async function selectClient(checkbox) {
        document.querySelectorAll('.client-single-checkbox').forEach(item => {
            if (item !== checkbox) {
                item.checked = false;
            }
        });

        const clientId = checkbox.checked ? checkbox.value : '';
        const clientInput = document.getElementById('client_id');

        if (clientInput) {
            clientInput.value = clientId;
        }

        resetSelect('element_type_id', 'Seleccione un tipo de activo');
        resetSelect('component_id', 'Seleccione un componente');

        if (!clientId) {
            resetDiagnostics();
            return;
        }

        resetDiagnostics('Cargando diagnósticos...');

        try {
            await loadTypes('', '');
        } catch (error) {
            console.error(error);
            resetDiagnostics('Ocurrió un error al cargar la información.');
        }
    }

    async function loadTypes(selectedElementTypeId = '', selectedComponentId = '') {
        const clientId = getClientId();

        resetSelect('element_type_id', 'Seleccione un tipo de activo');
        resetSelect('component_id', 'Seleccione un componente');

        if (!clientId) {
            resetDiagnostics();
            return;
        }

        const select = document.getElementById('element_type_id');

        try {
            const data = await fetchJson(`/admin/cd/clients/${clientId}/element-types`);

            data.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.name;

                if (String(selectedElementTypeId) === String(item.id)) {
                    option.selected = true;
                }

                select.appendChild(option);
            });

            if (!selectedElementTypeId && data.length === 1) {
                select.value = String(data[0].id);
            }

            if (data.length === 0) {
                resetDiagnostics('Este cliente no tiene tipos de activo disponibles.');
                return;
            }

            if (select.value) {
                await loadComponents(selectedComponentId);
            } else {
                resetDiagnostics('Selecciona un tipo de activo para cargar los diagnósticos.');
            }
        } catch (error) {
            console.error(error);
            resetDiagnostics('No fue posible cargar los tipos de activo.');
        }
    }

    async function loadComponents(selectedComponentId = '') {
        const elementTypeId = getElementTypeId();

        resetSelect('component_id', 'Seleccione un componente');

        if (!elementTypeId) {
            document.querySelectorAll('[name="diagnostics[]"]').forEach(checkbox => {
                checkbox.checked = false;
            });
            resetDiagnostics('Selecciona un tipo de activo para cargar los diagnósticos.');
            return;
        }

        const select = document.getElementById('component_id');

        try {
            const data = await fetchJson(`/admin/cd/element-types/${elementTypeId}/components`);

            data.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.name;

                if (String(selectedComponentId) === String(item.id)) {
                    option.selected = true;
                }

                select.appendChild(option);
            });

            if (!selectedComponentId && data.length === 1) {
                select.value = String(data[0].id);
            }

            if (data.length === 0) {
                resetDiagnostics('Este tipo de activo no tiene componentes disponibles.');
                return;
            }

            await loadDiagnostics();
            await loadAssigned();
        } catch (error) {
            console.error(error);
            resetDiagnostics('No fue posible cargar los componentes.');
        }
    }

    async function loadDiagnostics() {
        const clientId = getClientId();
        const elementTypeId = getElementTypeId();

        if (!clientId || !elementTypeId) {
            resetDiagnostics('Selecciona primero cliente y tipo de activo para cargar los diagnósticos.');
            return;
        }

        const container = document.getElementById('diagnostics_list');

        try {
            const data = await fetchJson(`/admin/cd/clients/${clientId}/element-types/${elementTypeId}/diagnostics`);

            container.innerHTML = '';

            if (data.length === 0) {
                resetDiagnostics('Este tipo de activo no tiene diagnósticos activos disponibles.');
                return;
            }

            data.forEach(item => {
                container.innerHTML += `
                    <label class="flex items-center gap-3 text-sm text-slate-700">
                        <input
                            type="checkbox"
                            name="diagnostics[]"
                            value="${item.id}"
                            class="rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                        >
                        ${item.name}
                    </label>
                `;
            });
        } catch (error) {
            console.error(error);
            resetDiagnostics('No fue posible cargar los diagnósticos.');
        }
    }


    async function loadAssigned() {
        const componentId = getComponentId();

        if (!componentId) {
            document.querySelectorAll('[name="diagnostics[]"]').forEach(checkbox => {
                checkbox.checked = false;
            });
            return;
        }

        try {
            const data = await fetchJson(`/admin/cd/components/${componentId}/assigned`);

            document.querySelectorAll('[name="diagnostics[]"]').forEach(checkbox => {
                checkbox.checked = data.includes(parseInt(checkbox.value));
            });
        } catch (error) {
            console.error(error);
        }
    }

    // ── Toast ──────────────────────────────────────────────
    function showCdToast(msg, type = 'success') {
        let container = document.getElementById('cdToastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'cdToastContainer';
            container.className = 'fixed bottom-5 right-5 z-[99999] space-y-3';
            document.body.appendChild(container);
        }
        const t = document.createElement('div');
        t.className = `w-80 rounded-2xl border px-4 py-3 text-sm font-semibold shadow-2xl ${
            type === 'error'
                ? 'border-red-200 bg-red-50 text-red-700'
                : 'border-emerald-200 bg-emerald-50 text-emerald-700'
        }`;
        t.textContent = msg;
        container.appendChild(t);
        setTimeout(() => t.remove(), 3500);
    }

    // ── Submit AJAX ───────────────────────────────────────
    async function handleCdSubmit(event) {
        event.preventDefault();

        const form = event.currentTarget;
        const btn  = document.getElementById('cdSubmitBtn');

        if (btn) { btn.disabled = true; btn.textContent = 'Guardando...'; }

        // Guardar selección actual antes de enviar
        const savedClientId      = getClientId();
        const savedElementTypeId = getElementTypeId();
        const savedComponentId   = getComponentId();

        try {
            const formData = new FormData(form);
            // Asegurar que component_id va en el body (viene del select con name)
            const resp = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: formData,
            });

            const data = await resp.json();

            if (resp.status === 422) {
                const msgs = Object.values(data.errors ?? {}).flat().join(' ');
                showCdToast(msgs || data.message || 'Error de validación.', 'error');
                return;
            }

            if (!resp.ok || data.success === false) throw new Error(data.message || 'Error al guardar.');

            showCdToast(data.message || 'Diagnósticos asignados correctamente.');

            // Solo refrescar el estado marcado de los diagnósticos — los selects ya están correctos
            await loadAssigned();

        } catch (err) {
            showCdToast(err.message || 'Ocurrió un error.', 'error');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Guardar asignación';
            }
        }
    }

    // ── Init ──────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', async function () {
        const elementTypeSelect = document.getElementById('element_type_id');
        const componentSelect   = document.getElementById('component_id');
        const preferredElementTypeId = document.getElementById('preferred_element_type_id')?.value ?? '';
        const preferredComponentId   = document.getElementById('preferred_component_id')?.value ?? '';

        if (elementTypeSelect) {
            elementTypeSelect.addEventListener('change', () => loadComponents(''));
        }
        if (componentSelect) {
            componentSelect.addEventListener('change', loadAssigned);
        }

        // Interceptar submit con AJAX
        const form = document.querySelector('form[action="{{ route('admin.managed-component-diagnostics.store') }}"]');
        if (form) form.addEventListener('submit', handleCdSubmit);

        const currentClientId = getClientId();
        if (currentClientId) {
            markSingleClientCheckbox(currentClientId);
            try {
                await loadTypes(preferredElementTypeId, preferredComponentId);
            } catch (e) {
                resetDiagnostics('Ocurrió un error al cargar la información.');
            }
        } else {
            resetDiagnostics();
        }
    });
</script>
@endsection


        
        
        
        