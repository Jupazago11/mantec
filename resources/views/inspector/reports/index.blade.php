@extends('layouts.admin')

@section('title', 'Registrar reporte')
@section('header_title', 'Registrar reporte')

@section('content')
<div class="space-y-8">

    @php
        $formState = session('form_state', []);
        $selectedClientId = old('client_id', $formState['client_id'] ?? '');
        $selectedAreaId = old('area_id', $formState['area_id'] ?? '');
        $selectedElementId = old('element_id', $formState['element_id'] ?? '');
        $selectedComponentId = old('component_id');
        $selectedDiagnosticId = old('diagnostic_id');
    @endphp

    <div class="flex items-start justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Registro de reportes</h2>
            <p class="mt-2 text-slate-600">
                Diligencia hallazgos por activo, componente y diagnóstico según la semana actual.
            </p>
        </div>

        <div class="shrink-0 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-right shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Semana actual</p>
            <p class="mt-1 text-sm font-bold text-slate-900">
                S{{ now()->weekOfYear }} / {{ now()->year }}
            </p>
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

    <div class="grid gap-8 xl:grid-cols-3">

        <!-- FORMULARIO -->
        <div class="xl:col-span-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <h3 class="text-lg font-semibold text-slate-900">Nuevo reporte</h3>

                <form method="POST" action="{{ route('inspector.reports.store') }}" class="mt-6 space-y-5">
                    @csrf

                    <div class="grid gap-5 md:grid-cols-2">

                        @if(isset($inspectorClients) && $inspectorClients->count() === 1)
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Cliente</label>
                                <div class="w-full rounded-xl border border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-700">
                                    {{ $inspectorClients->first()->name }}
                                </div>
                                <input
                                    type="hidden"
                                    name="client_id"
                                    id="client_id"
                                    value="{{ $inspectorClients->first()->id }}"
                                >
                            </div>
                        @elseif(isset($inspectorClients) && $inspectorClients->count() > 1)
                            <div class="md:col-span-2">
                                <label class="mb-2 block text-sm font-medium text-slate-700">Cliente</label>
                                <div class="max-h-56 space-y-2 overflow-y-auto rounded-xl border border-slate-300 p-4">
                                    @foreach($inspectorClients as $client)
                                        <label class="flex items-center gap-3 text-sm text-slate-700">
                                            <input
                                                type="checkbox"
                                                value="{{ $client->id }}"
                                                class="client-single-checkbox rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                                                {{ (string) $selectedClientId === (string) $client->id ? 'checked' : '' }}
                                                onchange="handleSingleClientSelection(this)"
                                            >
                                            {{ $client->name }}
                                        </label>
                                    @endforeach
                                </div>
                                <input type="hidden" name="client_id" id="client_id" value="{{ $selectedClientId }}">
                            </div>
                        @elseif($assignedClient ?? false)
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Cliente</label>
                                <div class="w-full rounded-xl border border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-700">
                                    {{ $assignedClient->name }}
                                </div>
                                <input
                                    type="hidden"
                                    name="client_id"
                                    id="client_id"
                                    value="{{ $assignedClient->id }}"
                                >
                            </div>
                        @endif

                        <x-form.select name="area_id" label="Área" id="area_id">
                            <option value="">Seleccione un área</option>
                            @foreach($areas as $area)
                                <option
                                    value="{{ $area->id }}"
                                    data-client-id="{{ $area->client_id }}"
                                    @selected((string) $selectedAreaId === (string) $area->id)
                                >
                                    {{ $area->name }}
                                </option>
                            @endforeach
                        </x-form.select>

                        <x-form.select name="element_id" label="Activo" id="element_id">
                            <option value="">Seleccione un activo</option>
                        </x-form.select>

                        <x-form.select name="component_id" label="Componente" id="component_id">
                            <option value="">Seleccione un componente</option>
                        </x-form.select>

                        <x-form.select name="diagnostic_id" label="Diagnóstico" id="diagnostic_id">
                            <option value="">Seleccione un diagnóstico</option>
                        </x-form.select>

                        <x-form.select name="condition_id" label="Condición">
                            <option value="">Seleccione una condición</option>
                            @foreach($conditions as $condition)
                                <option value="{{ $condition->id }}" @selected(old('condition_id') == $condition->id)>
                                    {{ $condition->code }}
                                </option>
                            @endforeach
                        </x-form.select>
                    </div>

                    <x-form.textarea
                        name="recommendation"
                        label="Recomendación"
                        placeholder="Describe la acción recomendada"
                        rows="4"
                    />

                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                    >
                        Guardar reporte
                    </button>

                </form>
            </div>
        </div>

        <!-- PENDIENTES -->
        <div class="xl:col-span-1">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <div class="flex justify-between items-start gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Pendientes</h3>
                        <p class="text-sm text-slate-500">Semana actual</p>
                    </div>

                    <div class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">
                        <span id="pending-count">0</span>
                    </div>
                </div>

                <div id="pending-container" class="mt-5 space-y-3">
                    <div class="text-sm text-slate-500">
                        Selecciona un activo.
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- REPORTES ABAJO -->
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-slate-900">Últimos 7 días</h3>

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse($recentReports as $report)
                <div class="rounded-xl border border-slate-200 p-4">
                    <div class="text-sm font-semibold text-slate-900">{{ $report->element?->name }}</div>
                    <div class="text-xs text-slate-500">
                        {{ $report->component?->name }} · {{ $report->diagnostic?->name }}
                    </div>
                    <div class="mt-2 text-xs text-slate-500">
                        S{{ $report->week }} / {{ $report->year }}
                    </div>
                </div>
            @empty
                <div class="text-sm text-slate-500">
                    Sin registros recientes.
                </div>
            @endforelse
        </div>
    </div>

</div>

<script>
const clientInput = document.getElementById('client_id');
const areaSelect = document.getElementById('area_id');
const elementSelect = document.getElementById('element_id');
const componentSelect = document.getElementById('component_id');
const diagnosticSelect = document.getElementById('diagnostic_id');

const selectedAreaId = @json($selectedAreaId);
const selectedElementId = @json($selectedElementId);
const selectedComponentId = @json($selectedComponentId);
const selectedDiagnosticId = @json($selectedDiagnosticId);

function reset(select, text) {
    select.innerHTML = `<option value="">${text}</option>`;
}

function resetPending() {
    document.getElementById('pending-count').innerText = '0';
    document.getElementById('pending-container').innerHTML = `
        <div class="text-sm text-slate-500">
            Selecciona un activo.
        </div>
    `;
}

function handleSingleClientSelection(checkbox) {
    document.querySelectorAll('.client-single-checkbox').forEach(item => {
        if (item !== checkbox) {
            item.checked = false;
        }
    });

    clientInput.value = checkbox.checked ? checkbox.value : '';

    areaSelect.value = '';
    reset(elementSelect, 'Seleccione un activo');
    reset(componentSelect, 'Seleccione un componente');
    reset(diagnosticSelect, 'Seleccione un diagnóstico');
    resetPending();

    filterAreasByClient();
}

function filterAreasByClient() {
    const selectedClientId = clientInput ? clientInput.value : '';

    Array.from(areaSelect.options).forEach(option => {
        if (option.value === '') return;

        const optionClientId = option.dataset.clientId || '';
        option.hidden = !!selectedClientId && optionClientId !== selectedClientId;
    });

    if (selectedClientId) {
        const currentOption = areaSelect.options[areaSelect.selectedIndex];
        if (currentOption && currentOption.value && currentOption.dataset.clientId !== selectedClientId) {
            areaSelect.value = '';
        }
    }
}

async function loadElements(preselectId = '') {
    reset(elementSelect, 'Seleccione un activo');
    reset(componentSelect, 'Seleccione un componente');
    reset(diagnosticSelect, 'Seleccione un diagnóstico');
    resetPending();

    if (!areaSelect.value) return;

    const res = await fetch(`/inspector/areas/${areaSelect.value}/elements`);
    const data = await res.json();

    data.forEach(e => {
        const selected = String(preselectId) === String(e.id) ? 'selected' : '';
        elementSelect.innerHTML += `<option value="${e.id}" ${selected}>${e.name}</option>`;
    });

    if (preselectId) {
        await loadComponents(selectedComponentId || '');
    }
}

async function loadComponents(preselectId = '') {
    reset(componentSelect, 'Seleccione un componente');
    reset(diagnosticSelect, 'Seleccione un diagnóstico');

    if (!elementSelect.value) return;

    const res = await fetch(`/inspector/elements/${elementSelect.value}/components`);
    const data = await res.json();

    data.forEach(c => {
        const selected = String(preselectId) === String(c.id) ? 'selected' : '';
        componentSelect.innerHTML += `<option value="${c.id}" ${selected}>${c.name}</option>`;
    });

    await loadPending();

    if (preselectId) {
        await loadDiagnostics(selectedDiagnosticId || '');
    }
}

async function loadDiagnostics(preselectId = '') {
    reset(diagnosticSelect, 'Seleccione un diagnóstico');

    if (!componentSelect.value) return;

    const res = await fetch(`/inspector/components/${componentSelect.value}/diagnostics`);
    const data = await res.json();

    data.forEach(d => {
        const selected = String(preselectId) === String(d.id) ? 'selected' : '';
        diagnosticSelect.innerHTML += `<option value="${d.id}" ${selected}>${d.name}</option>`;
    });
}

async function loadPending() {
    if (!elementSelect.value) return;

    const res = await fetch(`/inspector/elements/${elementSelect.value}/pending-diagnostics`);
    const data = await res.json();

    document.getElementById('pending-count').innerText = data.total_pending;

    document.getElementById('pending-container').innerHTML =
        data.items.length
            ? data.items.map(i => `<div class="text-sm">${i.component_name} - ${i.diagnostic_name}</div>`).join('')
            : `<div class="text-sm text-slate-500">No hay pendientes para este activo.</div>`;
}

areaSelect.addEventListener('change', () => loadElements());
elementSelect.addEventListener('change', () => loadComponents());
componentSelect.addEventListener('change', () => loadDiagnostics());

document.addEventListener('DOMContentLoaded', async function () {
    filterAreasByClient();

    if (clientInput && clientInput.value) {
        document.querySelectorAll('.client-single-checkbox').forEach(cb => {
            cb.checked = String(cb.value) === String(clientInput.value);
        });
    }

    if (selectedAreaId) {
        areaSelect.value = String(selectedAreaId);
        await loadElements(selectedElementId || '');
    }
});
</script>
@endsection