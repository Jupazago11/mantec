@extends('layouts.admin')
@section('title', 'Paradas')
@section('header_title', 'Paradas')

@section('content')
<div class="space-y-8">
    @if(session('success'))
        <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    <div class="{{ $canManage ? 'grid gap-8 xl:grid-cols-[380px_minmax(0,1fr)]' : '' }}">

        {{-- FORMULARIO NUEVA PARADA — solo roles con escritura --}}
        @if($canManage)
        <div class="space-y-5">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900">Nueva parada</h3>
                <p class="mt-1 text-sm text-slate-500">Registra una parada de planta con sus áreas afectadas.</p>

                <form id="createParadaForm" class="mt-5 space-y-4">
                    @csrf

                    {{-- Cliente --}}
                    @if($singleClient)
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Cliente</label>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-700">{{ $singleClient->name }}</div>
                            <input type="hidden" name="client_id" value="{{ $singleClient->id }}">
                        </div>
                    @else
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Cliente</label>
                            <select
                                name="client_id"
                                id="create_client_id"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                onchange="loadAreasForCreate(this.value)"
                            >
                                <option value="">Seleccione un cliente</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ $selectedClientId == $client->id ? 'selected' : '' }}>
                                        {{ $client->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- Nombre --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Nombre de la parada</label>
                        <input
                            type="text"
                            name="name"
                            id="create_parada_name"
                            placeholder="Ej. Parada de Horno"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                        >
                    </div>

                    {{-- Fechas --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Fecha inicio</label>
                            <input
                                type="date"
                                name="start_date"
                                id="create_start_date"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            >
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Fecha fin</label>
                            <input
                                type="date"
                                name="end_date"
                                id="create_end_date"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            >
                        </div>
                    </div>

                    {{-- Áreas --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Áreas afectadas</label>
                        <div id="create_areas_list" class="max-h-48 space-y-1.5 overflow-y-auto rounded-xl border border-slate-200 p-3 text-sm text-slate-500">
                            @if($areas->isEmpty())
                                <span>Selecciona un cliente para cargar las áreas.</span>
                            @else
                                @foreach($areas as $area)
                                    <label class="flex items-center gap-2 cursor-pointer text-slate-700">
                                        <input type="checkbox" name="area_ids[]" value="{{ $area->id }}"
                                            class="rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]">
                                        <span>{{ $area->name }}@if($area->code) <span class="text-slate-400">({{ $area->code }})</span>@endif</span>
                                    </label>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                    >
                        Guardar parada
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- LISTADO --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Paradas registradas</h3>
                <p class="mt-0.5 text-xs text-slate-500">Ordenadas de más reciente a más antigua.</p>
            </div>

            <div id="paradasRightContent">
                @include('admin.paradas.partials.list', compact('paradas', 'areas', 'selectedClientId', 'roleKey', 'canManage', 'showClientColumn'))
            </div>
        </div>
    </div>
</div>

@if($canManage)
{{-- MODAL EDITAR PARADA --}}
<div id="editParadaModal"
    class="fixed left-0 top-0 z-[9999] hidden h-[100dvh] w-[100vw] items-center justify-center overflow-y-auto bg-slate-950/60 px-3 py-4 backdrop-blur-sm">
    <div id="editParadaModalContent"
        class="flex w-full max-w-lg scale-95 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white opacity-0 shadow-2xl transition duration-200"
        style="max-height: calc(100dvh - 2rem);">

        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <h3 class="text-base font-bold text-slate-900">Editar parada</h3>
            <button type="button" onclick="closeEditParadaModal()" class="text-slate-400 hover:text-slate-700">✕</button>
        </div>

        <form id="editParadaForm" class="flex min-h-0 flex-1 flex-col">
            @csrf
            <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4 space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Nombre</label>
                    <input type="text" id="edit_parada_name" name="name"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Fecha inicio</label>
                        <input type="date" id="edit_start_date" name="start_date"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Fecha fin</label>
                        <input type="date" id="edit_end_date" name="end_date"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]">
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Áreas afectadas</label>
                    <div id="edit_areas_list" class="max-h-48 space-y-1.5 overflow-y-auto rounded-xl border border-slate-200 p-3 text-sm text-slate-700">
                        <span class="text-slate-400">Cargando áreas...</span>
                    </div>
                </div>
            </div>

            <div class="shrink-0 border-t border-slate-200 bg-slate-50 px-5 py-3 flex justify-end gap-3">
                <button type="button" onclick="closeEditParadaModal()"
                    class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                    Cancelar
                </button>
                <button type="submit"
                    class="rounded-xl bg-[#d94d33] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#b83f29]">
                    Actualizar parada
                </button>
            </div>
        </form>
    </div>
</div>
@endif

<div id="paradaToastContainer" class="fixed bottom-5 right-5 z-[99999] space-y-3"></div>

<script>
const CSRF     = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const indexUrl = '{{ route('admin.paradas.index') }}';
@if($canManage)
const areasUrl = '{{ route('admin.paradas.areas-by-client') }}';
const storeUrl = '{{ route('admin.paradas.store') }}';
@endif

// ── Helpers ───────────────────────────────────────────────
function showParadaToast(msg, type = 'success') {
    const c = document.getElementById('paradaToastContainer');
    const t = document.createElement('div');
    t.className = `w-80 rounded-2xl border px-4 py-3 text-sm font-semibold shadow-2xl ${type === 'error' ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'}`;
    t.textContent = msg;
    c.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}

function escHtml(t) {
    return String(t).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

@if($canManage)
function getClientId() {
    return document.getElementById('create_client_id')?.value
        ?? document.querySelector('input[name="client_id"]')?.value
        ?? '';
}

function renderAreaCheckboxes(container, areas, selectedIds = []) {
    if (!areas.length) {
        container.innerHTML = '<span class="text-slate-500">No hay áreas activas para este cliente.</span>';
        return;
    }
    container.innerHTML = areas.map(a => `
        <label class="flex items-center gap-2 cursor-pointer text-slate-700">
            <input type="checkbox" name="area_ids[]" value="${a.id}"
                class="area-check rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                ${selectedIds.includes(a.id) ? 'checked' : ''}>
            <span>${escHtml(a.name)}${a.code ? ` <span class="text-slate-400">(${escHtml(a.code)})</span>` : ''}</span>
        </label>`).join('');
}

// ── Refresca el listado sin recargar la página ────────────
async function refreshParadasList() {
    const clientId = getClientId();
    if (!clientId) return;

    try {
        const r = await fetch(`${indexUrl}?client_id=${clientId}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const d = await r.json();
        if (!d.success) return;

        const inner = document.getElementById('paradasListContainer');
        if (inner) {
            inner.outerHTML = d.paradas_html;
        } else {
            const wrapper = document.getElementById('paradasRightContent');
            if (wrapper) wrapper.innerHTML = d.paradas_html;
        }

        if (window.lucide) window.lucide.createIcons();
    } catch (_) {}
}

// ── Áreas para el formulario de creación ─────────────────
let cachedAreas = @json($areas->values());

async function loadAreasForCreate(clientId) {
    const container = document.getElementById('create_areas_list');
    if (!clientId) {
        container.innerHTML = '<span class="text-slate-500">Selecciona un cliente para cargar las áreas.</span>';
        document.getElementById('paradasRightContent').innerHTML =
            '<div class="px-5 py-10 text-center text-sm text-slate-500">Selecciona un cliente para ver sus paradas.</div>';
        return;
    }

    container.innerHTML = '<span class="text-slate-400">Cargando áreas...</span>';

    try {
        const r = await fetch(`${areasUrl}?client_id=${clientId}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const d = await r.json();
        if (!r.ok || !d.success) throw new Error();
        cachedAreas = d.areas;
        renderAreaCheckboxes(container, d.areas);
    } catch (_) {
        container.innerHTML = '<span class="text-red-500">No se pudieron cargar las áreas.</span>';
    }

    await refreshParadasList();
}

// ── Crear parada ──────────────────────────────────────────
document.getElementById('createParadaForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;

    try {
        const r = await fetch(storeUrl, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF },
            body: new FormData(this),
        });
        const d = await r.json();

        if (r.status === 422) {
            Object.values(d.errors || {}).flat()
                .forEach(m => showParadaToast(m, 'error'));
            return;
        }
        if (!r.ok || !d.success) throw new Error(d.message || 'Error al crear la parada.');

        const clientId = getClientId();
        this.reset();
        if (document.getElementById('create_client_id')) {
            document.getElementById('create_client_id').value = clientId;
        }
        renderAreaCheckboxes(document.getElementById('create_areas_list'), cachedAreas);

        showParadaToast(d.message || 'Parada creada correctamente.');
        await refreshParadasList();
    } catch (err) {
        showParadaToast(err.message || 'Error al crear la parada.', 'error');
    } finally {
        btn.disabled = false;
    }
});

// ── Modal editar ──────────────────────────────────────────
async function openEditParadaModal(id, name, startDate, endDate, areaIds, actionUrl, clientId) {
    document.getElementById('edit_parada_name').value  = name;
    document.getElementById('edit_start_date').value   = startDate;
    document.getElementById('edit_end_date').value     = endDate;
    document.getElementById('editParadaForm').dataset.action = actionUrl;

    const editAreasList = document.getElementById('edit_areas_list');
    editAreasList.innerHTML = '<span class="text-slate-400 text-sm">Cargando áreas...</span>';

    // Abrir modal de inmediato (sin esperar las áreas)
    const modal   = document.getElementById('editParadaModal');
    const content = document.getElementById('editParadaModalContent');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.documentElement.classList.add('overflow-hidden');
    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);

    // Obtener las áreas del cliente de ESTA parada (ignora cachedAreas)
    try {
        const r = await fetch(`${areasUrl}?client_id=${clientId}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const d = await r.json();
        if (r.ok && d.success) {
            cachedAreas = d.areas;
        } else {
            throw new Error();
        }
    } catch (_) {
        editAreasList.innerHTML = '<span class="text-red-500 text-sm">No se pudieron cargar las áreas.</span>';
        return;
    }

    renderAreaCheckboxes(editAreasList, cachedAreas, areaIds);
}

function closeEditParadaModal() {
    const modal   = document.getElementById('editParadaModal');
    const content = document.getElementById('editParadaModalContent');
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
        document.documentElement.classList.remove('overflow-hidden');
    }, 150);
}

// ── Actualizar parada ─────────────────────────────────────
document.getElementById('editParadaForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;

    const formData = new FormData(this);
    formData.append('_method', 'PUT');

    try {
        const r = await fetch(this.dataset.action, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF },
            body: formData,
        });
        const d = await r.json();

        if (r.status === 422) {
            Object.values(d.errors || {}).flat()
                .forEach(m => showParadaToast(m, 'error'));
            return;
        }
        if (!r.ok || !d.success) throw new Error(d.message || 'Error al actualizar.');

        closeEditParadaModal();
        showParadaToast(d.message || 'Parada actualizada correctamente.');
        await refreshParadasList();
    } catch (err) {
        showParadaToast(err.message || 'Error al actualizar la parada.', 'error');
    } finally {
        btn.disabled = false;
    }
});

// ── Eliminar parada ───────────────────────────────────────
async function deleteParada(id, url) {
    if (!confirm('¿Eliminar esta parada? Esta acción no se puede deshacer.')) return;

    const row = document.getElementById(`parada-row-${id}`);
    if (row) row.classList.add('opacity-50', 'pointer-events-none');

    try {
        const formData = new FormData();
        formData.append('_method', 'DELETE');
        const r = await fetch(url, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF },
            body: formData,
        });
        const d = await r.json();
        if (!r.ok || !d.success) throw new Error(d.message || 'Error al eliminar.');

        row?.remove();
        showParadaToast(d.message || 'Parada eliminada correctamente.');
    } catch (err) {
        if (row) row.classList.remove('opacity-50', 'pointer-events-none');
        showParadaToast(err.message || 'Error al eliminar la parada.', 'error');
    }
}

// ── Cierre de modal con clic fuera / Escape ───────────────
document.addEventListener('click', e => {
    const modal = document.getElementById('editParadaModal');
    if (modal?.classList.contains('flex') && e.target === modal) closeEditParadaModal();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeEditParadaModal(); });
@endif
</script>
@endsection
