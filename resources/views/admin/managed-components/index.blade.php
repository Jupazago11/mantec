@extends('layouts.admin')

@section('title', 'Componentes - Plantilla')
@section('header_title', 'Componentes - Plantilla')

@section('content')
    <div class="space-y-8">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Gestión de componentes</h2>
            <p class="mt-2 text-slate-600">
                Crea y administra componentes para los clientes que tienes asignados.
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
            <div class="xl:col-span-1">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Nuevo componente</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Registra un nuevo componente para uno de tus clientes.
                    </p>

                    <form method="POST" action="{{ route('admin.managed-components.store') }}" class="mt-6 space-y-5">
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
                                                name="client_id_checkbox"
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

                        <x-form.select name="element_type_id" label="Tipo de activo" id="element_type_id">
                            <option value="">Seleccione un tipo de activo</option>
                        </x-form.select>

                        <x-form.input
                            name="name"
                            label="Nombre"
                            placeholder="Ej. Tambor motriz"
                        />

                        <x-form.select name="is_default" label="¿Viene marcado por defecto?">
                            <option value="1" @selected(old('is_default') == '1')>Sí</option>
                            <option value="0" @selected(old('is_default', '0') == '0')>No</option>
                        </x-form.select>

                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                        >
                            Guardar componente
                        </button>
                    </form>
                </div>
            </div>

            <div class="xl:col-span-2">
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <h3 class="text-lg font-semibold text-slate-900">Listado de componentes</h3>

                            <form method="GET" action="{{ route('admin.managed-components.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                                <div>
                                    <label for="filter_client_id" class="mb-2 block text-sm font-medium text-slate-700">
                                        Filtrar por cliente
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

                                <div class="flex gap-2">
                                    <button
                                        type="submit"
                                        class="rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                                    >
                                        Filtrar
                                    </button>

                                    <a
                                        href="{{ route('admin.managed-components.index') }}"
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
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Cliente</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Tipo de activo</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Componente</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Por defecto</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Relaciones</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Uso</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Estado</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Acciones</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-200 bg-white">
                                @forelse($components as $component)
                                    @php
                                        $hasDependencies = ($component->elements_count + $component->diagnostics_count + $component->report_details_count) > 0;
                                    @endphp

                                    <tr class="hover:bg-slate-50">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                            {{ $component->client?->name ?? '—' }}
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                            {{ $component->elementType?->name ?? '—' }}
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">
                                            {{ $component->name }}
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                            {{ $component->is_default ? 'Sí' : 'No' }}
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                            {{ $component->diagnostics_count }}
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                            {{ $component->report_details_count }}
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                                            @if($component->status)
                                                <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                                    Activo
                                                </span>
                                            @else
                                                <span class="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                                                    Inactivo
                                                </span>
                                            @endif
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4 text-right">
                                            <div class="flex justify-end gap-2">
                                                <button
                                                    type="button"
                                                    class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                                                    data-client_id="{{ $component->client_id }}"
                                                    data-element_type_id="{{ $component->element_type_id }}"
                                                    data-name="{{ $component->name }}"
                                                    data-is_default="{{ $component->is_default ? 1 : 0 }}"
                                                    data-action="{{ route('admin.managed-components.update', $component) }}"
                                                    onclick="openEditComponentModal(this)"
                                                >
                                                    Editar
                                                </button>

                                                @if(!$hasDependencies)
                                                    <form
                                                        method="POST"
                                                        action="{{ route('admin.managed-components.destroy', $component) }}"
                                                        onsubmit="return confirm('¿Seguro que deseas eliminar este componente?');"
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
                                                    <form method="POST" action="{{ route('admin.managed-components.toggle-status', $component) }}">
                                                        @csrf
                                                        @method('PATCH')

                                                        <button
                                                            type="submit"
                                                            class="rounded-lg px-3 py-2 text-xs font-semibold text-white transition {{ $component->status ? 'bg-amber-500 hover:bg-amber-600' : 'bg-green-600 hover:bg-green-700' }}"
                                                        >
                                                            {{ $component->status ? 'Inactivar' : 'Activar' }}
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-10 text-center text-sm text-slate-500">
                                            No hay componentes registrados todavía.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($components->hasPages())
                        <div class="border-t border-slate-200 px-6 py-4">
                            {{ $components->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div id="editComponentModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
        <div class="w-full max-w-3xl rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Editar componente</h3>
                <button type="button" class="text-slate-500 hover:text-slate-900" onclick="closeEditComponentModal()">✕</button>
            </div>

            <form id="editComponentForm" method="POST" class="space-y-5 p-6">
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
                                        name="edit_client_id_checkbox"
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

                <x-form.select name="element_type_id" label="Tipo de activo" id="edit_element_type_id">
                    <option value="">Seleccione un tipo de activo</option>
                </x-form.select>

                <x-form.input
                    name="name"
                    label="Nombre"
                    id="edit_component_name"
                />

                <x-form.select name="is_default" label="¿Viene marcado por defecto?" id="edit_component_is_default">
                    <option value="1">Sí</option>
                    <option value="0">No</option>
                </x-form.select>

                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        onclick="closeEditComponentModal()"
                        class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                    >
                        Actualizar componente
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
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

        function handleSingleClientSelection(checkbox) {
            const all = document.querySelectorAll('.client-single-checkbox');

            all.forEach(item => {
                if (item !== checkbox) {
                    item.checked = false;
                }
            });

            const clientId = checkbox.checked ? checkbox.value : '';
            document.getElementById('selected_client_id').value = clientId;
            loadElementTypes(clientId, 'element_type_id');
        }

        function handleSingleClientSelectionEdit(checkbox) {
            const all = document.querySelectorAll('.edit-client-single-checkbox');

            all.forEach(item => {
                if (item !== checkbox) {
                    item.checked = false;
                }
            });

            const clientId = checkbox.checked ? checkbox.value : '';
            document.getElementById('edit_selected_client_id').value = clientId;
            loadElementTypes(clientId, 'edit_element_type_id');
        }

        async function openEditComponentModal(btn) {
            document.getElementById('editComponentForm').action = btn.dataset.action;
            document.getElementById('edit_component_name').value = btn.dataset.name ?? '';
            document.getElementById('edit_component_is_default').value = btn.dataset.is_default ?? '0';

            const clientId = btn.dataset.client_id ?? '';
            const elementTypeId = btn.dataset.element_type_id ?? '';

            const hiddenClientInput = document.getElementById('edit_selected_client_id');
            if (hiddenClientInput) {
                hiddenClientInput.value = clientId;
            }

            document.querySelectorAll('.edit-client-single-checkbox').forEach(cb => {
                cb.checked = parseInt(cb.value) === parseInt(clientId);
            });

            await loadElementTypes(clientId, 'edit_element_type_id', elementTypeId);

            const modal = document.getElementById('editComponentModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeEditComponentModal() {
            const modal = document.getElementById('editComponentModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }

        document.addEventListener('DOMContentLoaded', function () {
            const selectedClient = document.getElementById('selected_client_id');

            if (selectedClient && selectedClient.value) {
                document.querySelectorAll('.client-single-checkbox').forEach(cb => {
                    cb.checked = parseInt(cb.value) === parseInt(selectedClient.value);
                });

                loadElementTypes(selectedClient.value, 'element_type_id', '{{ old('element_type_id') }}');
            }
        });
    </script>
@endsection