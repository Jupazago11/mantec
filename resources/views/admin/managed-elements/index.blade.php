@extends('layouts.admin')

@section('title', 'Activos')
@section('header_title', 'Activos')

@section('content')
    @php
        $currentFilters = [
            'client_id' => request('client_id'),
            'name' => request('name'),
            'page' => request('page'),
        ];
    @endphp

    <div class="space-y-8">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Gestión de activos</h2>
            <p class="mt-2 text-slate-600">
                Crea y administra activos para los clientes que tienes asignados.
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

        <div class="grid gap-8 xl:grid-cols-12">
            <div class="xl:col-span-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Nuevo activo</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Registra un nuevo activo para uno de tus clientes.
                    </p>

                    <form method="POST" action="{{ route('admin.managed-elements.store', $currentFilters) }}" class="mt-6 space-y-5">
                        @csrf

                        @if($singleClient)
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Cliente</label>
                                <div class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                    {{ $singleClient->name }}
                                </div>
                                <input type="hidden" name="client_id" value="{{ $singleClient->id }}" id="selected_client_id">
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
                                                {{ old('client_id') == $client->id ? 'checked' : '' }}
                                                onchange="handleSingleClientSelection(this)"
                                            >
                                            {{ $client->name }}
                                        </label>
                                    @endforeach
                                </div>
                                <input type="hidden" name="client_id" id="selected_client_id" value="{{ old('client_id') }}">
                            </div>
                        @endif

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Área</label>
                            <select
                                name="area_id"
                                id="area_id"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            >
                                <option value="">Seleccione un área</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Tipo de activo</label>
                            <select
                                name="element_type_id"
                                id="element_type_id"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            >
                                <option value="">Seleccione un tipo de activo</option>
                            </select>
                        </div>

                        <x-form.input
                            name="name"
                            label="Nombre"
                            placeholder="Ej. L71TN01"
                        />

                        <x-form.input
                            name="code"
                            label="Código"
                            placeholder="Opcional"
                        />

                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                        >
                            Guardar activo
                        </button>
                    </form>
                </div>
            </div>

            <div class="xl:col-span-9">
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                            <h3 class="text-lg font-semibold text-slate-900">Listado de activos</h3>

                            <form method="GET" action="{{ route('admin.managed-elements.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                                @if(!$singleClient)
                                    <div>
                                        <label for="filter_client_id" class="mb-2 block text-sm font-medium text-slate-700">
                                            Cliente
                                        </label>

                                        <select
                                            name="client_id"
                                            id="filter_client_id"
                                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                        >
                                            <option value="">Todos</option>
                                            @foreach($clients as $client)
                                                <option value="{{ $client->id }}" @selected($selectedClientId == $client->id)>
                                                    {{ $client->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                <div>
                                    <label for="filter_name" class="mb-2 block text-sm font-medium text-slate-700">
                                        Nombre
                                    </label>

                                    <input
                                        type="text"
                                        name="name"
                                        id="filter_name"
                                        value="{{ request('name') }}"
                                        placeholder="Buscar activo"
                                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                    >
                                </div>

                                <div class="flex gap-2">
                                    <button
                                        type="submit"
                                        class="rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                                    >
                                        Filtrar
                                    </button>

                                    <a
                                        href="{{ route('admin.managed-elements.index') }}"
                                        class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                                    >
                                        Limpiar
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    @if(!$singleClient)
                                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Cliente</th>
                                    @endif
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Área</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Tipo</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Nombre</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Código</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Comp.</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Uso</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Estado</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Acciones</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-200 bg-white">
                                @forelse($elements as $element)
                                    @php
                                        $hasDependencies = ($element->components_count + $element->report_details_count) > 0;
                                        $clientId = $element->area?->client_id;
                                        $availableComponents = $componentsByClientAndType->get($clientId . '_' . $element->element_type_id, collect());
                                        $selectedComponentIds = $element->components->pluck('id')->toArray();
                                    @endphp

                                    <tr class="hover:bg-slate-50">
                                        @if(!$singleClient)
                                            <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700">
                                                {{ $element->area?->client?->name ?? '—' }}
                                            </td>
                                        @endif

                                        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700">
                                            {{ $element->area?->name ?? '—' }}
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700">
                                            {{ $element->elementType?->name ?? '—' }}
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-3 text-sm font-medium text-slate-900">
                                            {{ $element->name }}
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-600">
                                            {{ $element->code ?: '—' }}
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700">
                                            {{ $element->components_count }}
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700">
                                            {{ $element->report_details_count }}
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-3 text-sm">
                                            @if($element->status)
                                                <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                                    Activo
                                                </span>
                                            @else
                                                <span class="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                                                    Inactivo
                                                </span>
                                            @endif
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-3 text-right">
                                            <div class="flex justify-end gap-2">
                                                <button
                                                    type="button"
                                                    class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                                                    data-area="{{ $element->area?->name }}"
                                                    data-type="{{ $element->elementType?->name }}"
                                                    data-name="{{ $element->name }}"
                                                    data-action="{{ route('admin.managed-elements.components.sync', $element) }}"
                                                    onclick="openComponentsModal(this, 'components-list-{{ $element->id }}')"
                                                >
                                                    Componentes
                                                </button>

                                                <button
                                                    type="button"
                                                    class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                                                    data-client_id="{{ $element->area?->client_id }}"
                                                    data-area_id="{{ $element->area_id }}"
                                                    data-element_type_id="{{ $element->element_type_id }}"
                                                    data-name="{{ $element->name }}"
                                                    data-code="{{ $element->code }}"
                                                    data-action="{{ route('admin.managed-elements.update', array_merge(['element' => $element->id], $currentFilters)) }}"
                                                    onclick="openEditElementModal(this)"
                                                >
                                                    Editar
                                                </button>

                                                @if(!$hasDependencies)
                                                    <form
                                                        method="POST"
                                                        action="{{ route('admin.managed-elements.destroy', array_merge(['element' => $element->id], $currentFilters)) }}"
                                                        onsubmit="return confirm('¿Seguro que deseas eliminar este activo?');"
                                                    >
                                                        @csrf
                                                        @method('DELETE')

                                                        <button
                                                            type="submit"
                                                            class="rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-red-700"
                                                        >
                                                            Eliminar
                                                        </button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('admin.managed-elements.toggle-status', array_merge(['element' => $element->id], $currentFilters)) }}">
                                                        @csrf
                                                        @method('PATCH')

                                                        <button
                                                            type="submit"
                                                            class="rounded-lg px-3 py-2 text-xs font-semibold text-white transition {{ $element->status ? 'bg-amber-500 hover:bg-amber-600' : 'bg-green-600 hover:bg-green-700' }}"
                                                        >
                                                            {{ $element->status ? 'Inactivar' : 'Activar' }}
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>

                                            <template id="components-list-{{ $element->id }}">
                                                @foreach($availableComponents as $component)
                                                    <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-3 text-sm text-slate-700">
                                                        <input
                                                            type="checkbox"
                                                            name="components[]"
                                                            value="{{ $component->id }}"
                                                            class="mt-0.5 rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                                                            {{ in_array($component->id, $selectedComponentIds) ? 'checked' : '' }}
                                                        >
                                                        <div>
                                                            <div class="font-medium text-slate-900">{{ $component->name }}</div>
                                                            <div class="text-xs text-slate-500">
                                                                {{ $component->elementType?->name ?? 'Sin tipo' }}
                                                            </div>
                                                        </div>
                                                    </label>
                                                @endforeach
                                            </template>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $singleClient ? 8 : 9 }}" class="px-5 py-10 text-center text-sm text-slate-500">
                                            No hay activos registrados todavía.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($elements->hasPages())
                        <div class="border-t border-slate-200 px-5 py-4">
                            {{ $elements->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL EDITAR -->
    <div id="editElementModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
        <div class="w-full max-w-3xl rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Editar activo</h3>
                <button type="button" class="text-slate-500 hover:text-slate-900" onclick="closeEditElementModal()">✕</button>
            </div>

            <form id="editElementForm" method="POST" class="space-y-5 p-6">
                @csrf
                @method('PUT')

                @if($singleClient)
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Cliente</label>
                        <div class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            {{ $singleClient->name }}
                        </div>
                        <input type="hidden" name="client_id" value="{{ $singleClient->id }}" id="edit_selected_client_id">
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
                                        class="edit-client-single-checkbox rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                                        onchange="handleSingleClientSelectionEdit(this)"
                                    >
                                    {{ $client->name }}
                                </label>
                            @endforeach
                        </div>
                        <input type="hidden" name="client_id" id="edit_selected_client_id">
                    </div>
                @endif

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Área</label>
                    <select
                        name="area_id"
                        id="edit_area_id"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                    >
                        <option value="">Seleccione un área</option>
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Tipo de activo</label>
                    <select
                        name="element_type_id"
                        id="edit_element_type_id"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                    >
                        <option value="">Seleccione un tipo de activo</option>
                    </select>
                </div>

                <x-form.input name="name" label="Nombre" id="edit_element_name" />
                <x-form.input name="code" label="Código" id="edit_element_code" />

                @foreach($currentFilters as $filterKey => $filterValue)
                    @if($filterValue !== null && $filterValue !== '')
                        <input type="hidden" name="{{ $filterKey }}" value="{{ $filterValue }}">
                    @endif
                @endforeach

                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        onclick="closeEditElementModal()"
                        class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                    >
                        Actualizar activo
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL COMPONENTES -->
    <div id="componentsModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
        <div class="w-full max-w-4xl rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Componentes del activo</h3>
                <button type="button" class="text-slate-500 hover:text-slate-900" onclick="closeComponentsModal()">✕</button>
            </div>

            <form id="componentsForm" method="POST" class="space-y-5 p-6">
                @csrf

                @foreach($currentFilters as $filterKey => $filterValue)
                    @if($filterValue !== null && $filterValue !== '')
                        <input type="hidden" name="{{ $filterKey }}" value="{{ $filterValue }}">
                    @endif
                @endforeach

                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Área</label>
                        <div id="components_modal_area" class="rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700"></div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Tipo de activo</label>
                        <div id="components_modal_type" class="rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700"></div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Nombre</label>
                        <div id="components_modal_name" class="rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700"></div>
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Componentes asociados</label>
                    <div id="components_modal_list" class="grid max-h-[420px] gap-3 overflow-y-auto rounded-xl border border-slate-300 p-4 md:grid-cols-2">
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        onclick="closeComponentsModal()"
                        class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                    >
                        Guardar componentes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        async function loadAreas(clientId, targetSelectId, selectedValue = '') {
            const select = document.getElementById(targetSelectId);
            select.innerHTML = '<option value="">Seleccione un área</option>';

            if (!clientId) return;

            const response = await fetch(`/admin/clients/${clientId}/areas`);
            const data = await response.json();

            data.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.name;
                if (parseInt(selectedValue) === parseInt(item.id)) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
        }

        async function loadElementTypes(clientId, targetSelectId, selectedValue = '') {
            const select = document.getElementById(targetSelectId);
            select.innerHTML = '<option value="">Seleccione un tipo de activo</option>';

            if (!clientId) return;

            const response = await fetch(`/admin/clients/${clientId}/element-types`);
            const data = await response.json();

            data.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.name;
                if (parseInt(selectedValue) === parseInt(item.id)) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
        }

        async function handleSingleClientSelection(checkbox) {
            const all = document.querySelectorAll('.client-single-checkbox');

            all.forEach(item => {
                if (item !== checkbox) {
                    item.checked = false;
                }
            });

            const clientId = checkbox.checked ? checkbox.value : '';
            document.getElementById('selected_client_id').value = clientId;

            await loadAreas(clientId, 'area_id');
            await loadElementTypes(clientId, 'element_type_id');
        }

        async function handleSingleClientSelectionEdit(checkbox) {
            const all = document.querySelectorAll('.edit-client-single-checkbox');

            all.forEach(item => {
                if (item !== checkbox) {
                    item.checked = false;
                }
            });

            const clientId = checkbox.checked ? checkbox.value : '';
            document.getElementById('edit_selected_client_id').value = clientId;

            await loadAreas(clientId, 'edit_area_id');
            await loadElementTypes(clientId, 'edit_element_type_id');
        }

        async function openEditElementModal(btn) {
            document.getElementById('editElementForm').action = btn.dataset.action;
            document.getElementById('edit_element_name').value = btn.dataset.name ?? '';
            document.getElementById('edit_element_code').value = btn.dataset.code ?? '';

            const clientId = btn.dataset.client_id ?? '';
            const areaId = btn.dataset.area_id ?? '';
            const elementTypeId = btn.dataset.element_type_id ?? '';

            const hiddenClientInput = document.getElementById('edit_selected_client_id');
            if (hiddenClientInput) {
                hiddenClientInput.value = clientId;
            }

            document.querySelectorAll('.edit-client-single-checkbox').forEach(cb => {
                cb.checked = parseInt(cb.value) === parseInt(clientId);
            });

            await loadAreas(clientId, 'edit_area_id', areaId);
            await loadElementTypes(clientId, 'edit_element_type_id', elementTypeId);

            const modal = document.getElementById('editElementModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeEditElementModal() {
            const modal = document.getElementById('editElementModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }

        function openComponentsModal(btn, templateId) {
            document.getElementById('componentsForm').action = btn.dataset.action;
            document.getElementById('components_modal_area').textContent = btn.dataset.area ?? '—';
            document.getElementById('components_modal_type').textContent = btn.dataset.type ?? '—';
            document.getElementById('components_modal_name').textContent = btn.dataset.name ?? '—';

            const template = document.getElementById(templateId);
            document.getElementById('components_modal_list').innerHTML = template.innerHTML;

            const modal = document.getElementById('componentsModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeComponentsModal() {
            const modal = document.getElementById('componentsModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }

        document.addEventListener('DOMContentLoaded', async function () {
            const selectedClient = document.getElementById('selected_client_id');

            if (selectedClient && selectedClient.value) {
                document.querySelectorAll('.client-single-checkbox').forEach(cb => {
                    cb.checked = parseInt(cb.value) === parseInt(selectedClient.value);
                });

                await loadAreas(selectedClient.value, 'area_id', @json(old('area_id')));
                await loadElementTypes(selectedClient.value, 'element_type_id', @json(old('element_type_id')));
            }
        });
    </script>
@endsection