@extends('layouts.admin')
@section('title', 'Componentes - Diagnósticos')
@section('header_title', 'Componentes - Diagnósticos')

@section('content')
    <div class="space-y-8">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Asignación de diagnósticos a componentes</h2>
            <p class="mt-2 text-slate-600">
                Configura qué diagnósticos aplican a cada componente según el cliente y tipo de activo.
            </p>
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
                                <input type="hidden" id="client_id" value="{{ $singleClient->id }}">
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
                                                onchange="selectClient(this)"
                                            >
                                            {{ $client->name }}
                                        </label>
                                    @endforeach
                                </div>
                                <input type="hidden" id="client_id">
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

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Diagnósticos</label>
                            <div
                                id="diagnostics_list"
                                class="max-h-72 space-y-2 overflow-y-auto rounded-xl border border-slate-300 p-4"
                            >
                                <p class="text-sm text-slate-500">
                                    Selecciona primero un cliente para cargar los diagnósticos.
                                </p>
                            </div>
                        </div>

                        <button
                            type="submit"
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
            await loadTypes();
            await loadDiagnostics();
        } catch (error) {
            console.error(error);
            resetDiagnostics('Ocurrió un error al cargar la información.');
        }
    }

    async function loadTypes() {
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
                select.appendChild(option);
            });

            if (data.length === 0) {
                resetDiagnostics('Este cliente no tiene tipos de activo disponibles.');
            }
        } catch (error) {
            console.error(error);
            resetDiagnostics('No fue posible cargar los tipos de activo.');
        }
    }

    async function loadComponents() {
        const elementTypeId = getElementTypeId();

        resetSelect('component_id', 'Seleccione un componente');

        if (!elementTypeId) {
            document.querySelectorAll('[name="diagnostics[]"]').forEach(checkbox => {
                checkbox.checked = false;
            });
            return;
        }

        const select = document.getElementById('component_id');

        try {
            const data = await fetchJson(`/admin/cd/element-types/${elementTypeId}/components`);

            data.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.name;
                select.appendChild(option);
            });

            if (data.length === 0) {
                resetDiagnostics('Este tipo de activo no tiene componentes disponibles.');
                return;
            }

            await loadAssigned();
        } catch (error) {
            console.error(error);
            resetDiagnostics('No fue posible cargar los componentes.');
        }
    }

    async function loadDiagnostics() {
        const clientId = getClientId();

        if (!clientId) {
            resetDiagnostics();
            return;
        }

        const container = document.getElementById('diagnostics_list');

        try {
            const data = await fetchJson(`/admin/cd/clients/${clientId}/diagnostics`);

            container.innerHTML = '';

            if (data.length === 0) {
                resetDiagnostics('Este cliente no tiene diagnósticos activos disponibles.');
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

    document.addEventListener('DOMContentLoaded', async function () {
        const elementTypeSelect = document.getElementById('element_type_id');
        const componentSelect = document.getElementById('component_id');

        if (elementTypeSelect) {
            elementTypeSelect.addEventListener('change', loadComponents);
        }

        if (componentSelect) {
            componentSelect.addEventListener('change', loadAssigned);
        }

        @if($singleClient)
            const clientInput = document.getElementById('client_id');

            if (clientInput) {
                clientInput.value = '{{ $singleClient->id }}';
            }

            try {
                await loadTypes();
                await loadDiagnostics();
            } catch (error) {
                console.error(error);
                resetDiagnostics('Ocurrió un error al cargar la información.');
            }
        @else
            const currentClientId = getClientId();

            if (currentClientId) {
                markSingleClientCheckbox(currentClientId);

                try {
                    await loadTypes();
                    await loadDiagnostics();
                } catch (error) {
                    console.error(error);
                    resetDiagnostics('Ocurrió un error al cargar la información.');
                }
            }
        @endif
    });
</script>
@endsection


        
        
        
        