@extends('layouts.admin')

@section('title', 'Registrar reporte')
@section('header_title', 'Registrar reporte')

@section('content')
<div class="space-y-8">

    <div class="flex items-start justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Registro de reportes</h2>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-right shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Semana actual</div>
            <div class="mt-1 text-lg font-bold text-slate-900">S{{ now()->weekOfYear }}</div>
            <div class="text-sm text-slate-500">{{ now()->year }}</div>
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

        <div class="xl:col-span-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900">Nuevo reporte</h3>

                <form method="POST"
                      action="{{ route('inspector.reports.store') }}"
                      enctype="multipart/form-data"
                      class="mt-6 space-y-5">
                    @csrf

                    <div class="grid gap-5 md:grid-cols-2">

                        @if($assignedClient)
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Cliente</label>
                                <input
                                    type="text"
                                    value="{{ $assignedClient->name }}"
                                    disabled
                                    class="w-full rounded-xl border border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-700"
                                >
                                <input type="hidden" name="client_id" id="client_id" value="{{ $selectedClientId }}">
                            </div>
                        @else
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Cliente</label>
                                <select
                                    name="client_id"
                                    id="client_id"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                >
                                    <option value="">Seleccione un cliente</option>
                                    @foreach($assignedClients as $client)
                                        <option value="{{ $client->id }}" @selected($selectedClientId == $client->id)>
                                            {{ $client->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Especialidades permitidas</label>
                            <div
                                id="specialty_box"
                                class="min-h-[50px] w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700"
                            >
                                @if($selectedClientId)
                                    {{ $allowedElementTypesForSelectedClient->pluck('name')->implode(', ') ?: 'Sin especialidad asignada.' }}
                                @else
                                    Selecciona un cliente.
                                @endif
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Área</label>
                            <select
                                name="area_id"
                                id="area_id"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            >
                                <option value="">Seleccione un área</option>
                                @foreach($areas as $area)
                                    <option value="{{ $area->id }}" @selected($selectedAreaId == $area->id)>
                                        {{ $area->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Activo</label>
                            <select
                                name="element_id"
                                id="element_id"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            >
                                <option value="">Seleccione un activo</option>
                                @foreach($elements as $element)
                                    <option value="{{ $element->id }}" @selected($selectedElementId == $element->id)>
                                        {{ $element->name }}
                                    </option>
                                @endforeach
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
                            <label class="mb-2 block text-sm font-medium text-slate-700">Diagnóstico</label>
                            <select
                                name="diagnostic_id"
                                id="diagnostic_id"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            >
                                <option value="">Seleccione un diagnóstico</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Condición</label>
                            <select
                                name="condition_id"
                                id="condition_id"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            >
                                <option value="">Seleccione una condición</option>
                                @foreach($conditions as $condition)
                                    <option value="{{ $condition->id }}" @selected(old('condition_id') == $condition->id)>
                                        {{ $condition->code }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div id="belt-change-wrapper" class="hidden md:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-slate-700">¿Cambio de banda?</label>

                            <div class="flex gap-6 rounded-xl border border-slate-300 bg-slate-50 px-4 py-3">
                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                    <input
                                        type="radio"
                                        name="is_belt_change"
                                        value="1"
                                        class="border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                                        {{ old('is_belt_change') === '1' ? 'checked' : '' }}
                                    >
                                    <span>Sí</span>
                                </label>

                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                    <input
                                        type="radio"
                                        name="is_belt_change"
                                        value="0"
                                        class="border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                                        {{ old('is_belt_change') === '0' ? 'checked' : '' }}
                                    >
                                    <span>No</span>
                                </label>
                            </div>

                            <p class="mt-2 text-xs text-slate-500">
                                Esta pregunta solo aplica cuando el componente es Banda y el diagnóstico es Estado.
                            </p>
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Recomendación</label>
                        <textarea
                            name="recommendation"
                            rows="4"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            placeholder="Describe la acción recomendada"
                        >{{ old('recommendation') }}</textarea>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Subir archivos</label>
                        <input
                            type="file"
                            name="attachments[]"
                            multiple
                            accept="image/jpeg,image/png,image/webp,video/mp4,video/quicktime,video/webm"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"
                        >
                        <p class="mt-2 text-xs text-slate-500">
                            Puedes subir varias fotos y videos del hallazgo.
                        </p>
                    </div>

                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                    >
                        Guardar reporte
                    </button>
                </form>
            </div>
        </div>
<div class="xl:col-span-1">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Diagnósticos pendientes</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Semana actual
                        </p>
                    </div>

                    <div class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">
                        Pendientes: <span id="pending-count">0</span>
                    </div>
                </div>

                <div id="pending-container" class="mt-5 space-y-3">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                        Selecciona un activo.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-slate-900">Últimos 7 días</h3>
        <p class="mt-1 text-sm text-slate-500">
            Reportes creados por ti en las últimas 168 horas.
        </p>

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse($recentReports as $report)
                <div class="rounded-xl border border-slate-200 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">
                                {{ $report->element?->name ?? '—' }}
                            </p>
                            <p class="text-xs text-slate-500">
                                {{ $report->element?->elementType?->name ?? '—' }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $report->component?->name ?? '—' }} · {{ $report->diagnostic?->name ?? '—' }}
                            </p>
                        </div>

                        <span class="rounded-lg bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">
                            S{{ $report->week }}
                        </span>
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
const clientSelect = document.getElementById('client_id');
const areaSelect = document.getElementById('area_id');
const elementSelect = document.getElementById('element_id');
const componentSelect = document.getElementById('component_id');
const diagnosticSelect = document.getElementById('diagnostic_id');
const conditionSelect = document.getElementById('condition_id');
const specialtyBox = document.getElementById('specialty_box');
const beltChangeWrapper = document.getElementById('belt-change-wrapper');

const specialtiesByClient = @json($specialtiesByClient);
const selectedAreaId = @json($selectedAreaId);
const selectedElementId = @json($selectedElementId);

function resetSelect(select, placeholder) {
    if (!select) return;
    select.innerHTML = `<option value="">${placeholder}</option>`;
}

function updateSpecialtyBox() {
    const clientId = clientSelect ? clientSelect.value : '';

    if (!clientId) {
        if (specialtyBox) {
            specialtyBox.innerHTML = 'Selecciona un cliente.';
        }
        return;
    }

    const specialties = specialtiesByClient[clientId] || [];

    if (specialtyBox) {
        specialtyBox.innerHTML = specialties.length
            ? specialties.join(', ')
            : 'Sin especialidad asignada.';
    }
}

function updateBeltChangeVisibility() {
    if (!beltChangeWrapper || !componentSelect || !diagnosticSelect) return;

    const selectedComponentText =
        componentSelect.options[componentSelect.selectedIndex]?.text?.trim()?.toLowerCase() ?? '';

    const selectedDiagnosticText =
        diagnosticSelect.options[diagnosticSelect.selectedIndex]?.text?.trim()?.toLowerCase() ?? '';

    const shouldShow =
        selectedComponentText === 'banda' &&
        selectedDiagnosticText === 'estado';

    beltChangeWrapper.classList.toggle('hidden', !shouldShow);

    if (!shouldShow) {
        document.querySelectorAll('input[name="is_belt_change"]').forEach(input => {
            input.checked = false;
        });
    }
}

async function loadAreas(preserveAreaId = null, preserveElementId = null) {
    resetSelect(areaSelect, 'Seleccione un área');
    resetSelect(elementSelect, 'Seleccione un activo');
    resetSelect(componentSelect, 'Seleccione un componente');
    resetSelect(diagnosticSelect, 'Seleccione un diagnóstico');
    resetSelect(conditionSelect, 'Seleccione una condición');
    updateBeltChangeVisibility();

    const pendingCount = document.getElementById('pending-count');
    const pendingContainer = document.getElementById('pending-container');

    if (pendingCount) {
        pendingCount.innerText = '0';
    }

    if (pendingContainer) {
        pendingContainer.innerHTML = `
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                Selecciona un activo.
            </div>
        `;
    }

    if (!clientSelect || !clientSelect.value) {
        return;
    }

    try {
        const response = await fetch(`/inspector/clients/${clientSelect.value}/areas`, {
            headers: { 'Accept': 'application/json' }
        });

        if (!response.ok) {
            throw new Error(`Error cargando áreas: ${response.status}`);
        }

        const areas = await response.json();

        areas.forEach(area => {
            areaSelect.innerHTML += `<option value="${area.id}">${area.name}</option>`;
        });

        if (
            preserveAreaId &&
            areas.some(area => String(area.id) === String(preserveAreaId))
        ) {
            areaSelect.value = String(preserveAreaId);
            await loadElements(preserveElementId);
        }
    } catch (error) {
        console.error(error);
    }
}

async function loadElements(preserveElementId = null) {
    resetSelect(elementSelect, 'Seleccione un activo');
    resetSelect(componentSelect, 'Seleccione un componente');
    resetSelect(diagnosticSelect, 'Seleccione un diagnóstico');
    resetSelect(conditionSelect, 'Seleccione una condición');
    updateBeltChangeVisibility();

    if (!areaSelect || !areaSelect.value) {
        return;
    }

    try {
        const response = await fetch(`/inspector/areas/${areaSelect.value}/elements`, {
            headers: { 'Accept': 'application/json' }
        });

        if (!response.ok) {
            throw new Error(`Error cargando activos: ${response.status}`);
        }

        const elements = await response.json();

        elements.forEach(element => {
            elementSelect.innerHTML += `<option value="${element.id}">${element.name}</option>`;
        });

        if (
            preserveElementId &&
            elements.some(element => String(element.id) === String(preserveElementId))
        ) {
            elementSelect.value = String(preserveElementId);
            await loadComponents();
        }
    } catch (error) {
        console.error(error);
    }
}

async function loadComponents() {
    resetSelect(componentSelect, 'Seleccione un componente');
    resetSelect(diagnosticSelect, 'Seleccione un diagnóstico');
    resetSelect(conditionSelect, 'Seleccione una condición');
    updateBeltChangeVisibility();

    if (!elementSelect || !elementSelect.value) {
        return;
    }

    try {
        const response = await fetch(`/inspector/elements/${elementSelect.value}/components`, {
            headers: { 'Accept': 'application/json' }
        });

        if (!response.ok) {
            throw new Error(`Error cargando componentes: ${response.status}`);
        }

        const components = await response.json();

        components.forEach(component => {
            componentSelect.innerHTML += `<option value="${component.id}">${component.name}</option>`;
        });

        await loadPending();
    } catch (error) {
        console.error(error);
    }
}

async function loadConditions() {
    resetSelect(conditionSelect, 'Seleccione una condición');

    if (
        !elementSelect || !elementSelect.value ||
        !componentSelect || !componentSelect.value
    ) {
        return;
    }

    try {
        const response = await fetch(
            `/inspector/elements/${elementSelect.value}/conditions?component_id=${componentSelect.value}`,
            {
                headers: { 'Accept': 'application/json' }
            }
        );

        if (!response.ok) {
            throw new Error(`Error cargando condiciones: ${response.status}`);
        }

        const conditions = await response.json();

        conditions.forEach(condition => {
            conditionSelect.innerHTML += `<option value="${condition.id}">${condition.code}</option>`;
        });
    } catch (error) {
        console.error('loadConditions error:', error);
    }
}

async function loadDiagnostics() {
    resetSelect(diagnosticSelect, 'Seleccione un diagnóstico');
    updateBeltChangeVisibility();

    if (!componentSelect || !componentSelect.value || !elementSelect || !elementSelect.value) {
        return;
    }

    try {
        const response = await fetch(
            `/inspector/components/${componentSelect.value}/diagnostics?element_id=${elementSelect.value}`,
            { headers: { 'Accept': 'application/json' } }
        );

        if (!response.ok) {
            throw new Error(`Error cargando diagnósticos: ${response.status}`);
        }

        const diagnostics = await response.json();

        diagnostics.forEach(diagnostic => {
            diagnosticSelect.innerHTML += `<option value="${diagnostic.id}">${diagnostic.name}</option>`;
        });

        updateBeltChangeVisibility();
    } catch (error) {
        console.error(error);
    }
}

async function loadPending() {
    if (!elementSelect || !elementSelect.value) {
        return;
    }

    try {
        const response = await fetch(`/inspector/elements/${elementSelect.value}/pending-diagnostics`, {
            headers: { 'Accept': 'application/json' }
        });

        if (!response.ok) {
            throw new Error(`Error cargando pendientes: ${response.status}`);
        }

        const data = await response.json();

        const pendingCount = document.getElementById('pending-count');
        const pendingContainer = document.getElementById('pending-container');

        if (pendingCount) {
            pendingCount.innerText = data.total_pending;
        }

        if (pendingContainer) {
            pendingContainer.innerHTML = data.items.length
                ? data.items.map(item => `
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                        <div class="font-medium">${item.component_name}</div>
                        <div class="text-xs text-slate-500">${item.diagnostic_name}</div>
                    </div>
                `).join('')
                : `
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                        No hay diagnósticos pendientes para este activo en la semana actual.
                    </div>
                `;
        }
    } catch (error) {
        console.error(error);
    }
}

if (clientSelect && clientSelect.tagName === 'SELECT') {
    clientSelect.addEventListener('change', async () => {
        updateSpecialtyBox();
        await loadAreas();
    });
}

if (areaSelect) {
    areaSelect.addEventListener('change', async () => {
        await loadElements();
    });
}

if (elementSelect) {
    elementSelect.addEventListener('change', async () => {
        await loadComponents();
    });
}

if (componentSelect) {
    componentSelect.addEventListener('change', async () => {
        await loadDiagnostics();
        await loadConditions();
        updateBeltChangeVisibility();
    });
}

if (diagnosticSelect) {
    diagnosticSelect.addEventListener('change', updateBeltChangeVisibility);
}

document.addEventListener('DOMContentLoaded', async () => {
    updateSpecialtyBox();
    updateBeltChangeVisibility();

    if (clientSelect && clientSelect.value) {
        await loadAreas(selectedAreaId, selectedElementId);
    }
});

</script>
@endsection
