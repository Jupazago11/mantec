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

        <div class="grid gap-8 xl:grid-cols-3">
            <!-- FORMULARIO -->
            <div class="xl:col-span-1">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Nueva asignación</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Selecciona cliente, tipo de activo y componente para asignar sus diagnósticos.
                    </p>

                    <form method="POST" action="{{ route('admin.component-diagnostics.store') }}" class="mt-6 space-y-5">
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

            <!-- PANEL INFORMATIVO -->
            <div class="xl:col-span-2">
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
            select.innerHTML = `<option value="">${placeholder}</option>`;
        }

        function resetDiagnostics(message = 'Selecciona primero un cliente para cargar los diagnósticos.') {
            document.getElementById('diagnostics_list').innerHTML = `
                <p class="text-sm text-slate-500">${message}</p>
            `;
        }

        async function selectClient(cb) {
            document.querySelectorAll('.client-single-checkbox').forEach(x => x.checked = false);
            cb.checked = true;

            document.getElementById('client_id').value = cb.value;

            resetSelect('element_type_id', 'Seleccione un tipo de activo');
            resetSelect('component_id', 'Seleccione un componente');
            resetDiagnostics('Cargando diagnósticos...');

            await loadTypes();
            await loadDiagnostics();
        }

        async function loadTypes() {
            const client = document.getElementById('client_id').value;

            resetSelect('element_type_id', 'Seleccione un tipo de activo');
            resetSelect('component_id', 'Seleccione un componente');

            if (!client) {
                resetDiagnostics();
                return;
            }

            const res = await fetch(`/admin/cd/clients/${client}/element-types`);
            const data = await res.json();

            const select = document.getElementById('element_type_id');

            data.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.name;
                select.appendChild(option);
            });

            if (data.length === 0) {
                resetDiagnostics('Este cliente no tiene tipos de activo disponibles.');
            }
        }

        async function loadComponents() {
            const type = document.getElementById('element_type_id').value;

            resetSelect('component_id', 'Seleccione un componente');

            if (!type) {
                return;
            }

            const res = await fetch(`/admin/cd/element-types/${type}/components`);
            const data = await res.json();

            const select = document.getElementById('component_id');

            data.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.name;
                select.appendChild(option);
            });

            if (data.length === 0) {
                resetDiagnostics('Este tipo de activo no tiene componentes disponibles.');
            } else {
                await loadAssigned();
            }
        }

        async function loadDiagnostics() {
            const client = document.getElementById('client_id').value;

            if (!client) {
                resetDiagnostics();
                return;
            }

            const res = await fetch(`/admin/cd/clients/${client}/diagnostics`);
            const data = await res.json();

            const container = document.getElementById('diagnostics_list');
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
        }

        async function loadAssigned() {
            const component = document.getElementById('component_id').value;

            if (!component) {
                document.querySelectorAll('[name="diagnostics[]"]').forEach(cb => cb.checked = false);
                return;
            }

            const res = await fetch(`/admin/cd/components/${component}/assigned`);
            const data = await res.json();

            document.querySelectorAll('[name="diagnostics[]"]').forEach(cb => {
                cb.checked = data.includes(parseInt(cb.value));
            });
        }

        document.getElementById('element_type_id').addEventListener('change', loadComponents);
        document.getElementById('component_id').addEventListener('change', loadAssigned);

        @if($singleClient)
            document.addEventListener('DOMContentLoaded', async () => {
                document.getElementById('client_id').value = {{ $singleClient->id }};
                await loadTypes();
                await loadDiagnostics();
            });
        @endif
    </script>
@endsection