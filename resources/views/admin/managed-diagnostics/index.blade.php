@extends('layouts.admin')

@section('title', 'Diagnósticos')
@section('header_title', 'Diagnósticos')

@section('content')
    <div class="space-y-8">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Gestión de diagnósticos</h2>
            <p class="mt-2 text-slate-600">
                Crea y administra diagnósticos para los clientes que tienes asignados.
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
                    <h3 class="text-lg font-semibold text-slate-900">Nuevo diagnóstico</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Registra un nuevo diagnóstico para uno de tus clientes.
                    </p>

                    <form method="POST" action="{{ route('admin.managed-diagnostics.store') }}" class="mt-6 space-y-5">
                        @csrf

                        @if($singleClient)
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Cliente</label>
                                <div class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                    {{ $singleClient->name }}
                                </div>
                                <input type="hidden" name="client_id" value="{{ $singleClient->id }}">
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

                        <x-form.input
                            name="name"
                            label="Nombre"
                            placeholder="Ej. Desalineación"
                        />

                        <x-form.input
                            name="code"
                            label="Código"
                            placeholder="Ej. DES-01"
                        />

                        <x-form.textarea
                            name="description"
                            label="Descripción"
                            placeholder="Descripción opcional del diagnóstico"
                            rows="4"
                        />

                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                        >
                            Guardar diagnóstico
                        </button>
                    </form>
                </div>
            </div>

            <!-- TABLA -->
            <div class="xl:col-span-2">
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <h3 class="text-lg font-semibold text-slate-900">Listado de diagnósticos</h3>

                            <form method="GET" action="{{ route('admin.managed-diagnostics.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
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
                                        href="{{ route('admin.managed-diagnostics.index') }}"
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
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Cliente
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Nombre
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Código
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Descripción
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Relaciones
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Uso
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Estado
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-200 bg-white">
                                @forelse($diagnostics as $diagnostic)
                                    @php
                                        $hasDependencies = ($diagnostic->components_count + $diagnostic->report_details_count) > 0;
                                    @endphp

                                    <tr class="hover:bg-slate-50">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                            {{ $diagnostic->client?->name ?? '—' }}
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">
                                            {{ $diagnostic->name }}
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                            {{ $diagnostic->code }}
                                        </td>

                                        <td class="px-6 py-4 text-sm text-slate-600">
                                            {{ $diagnostic->description ?: '—' }}
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                            {{ $diagnostic->components_count }}
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                            {{ $diagnostic->report_details_count }}
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                                            @if($diagnostic->status)
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
                                                    data-name="{{ $diagnostic->name }}"
                                                    data-code="{{ $diagnostic->code }}"
                                                    data-description="{{ $diagnostic->description }}"
                                                    data-client_id="{{ $diagnostic->client_id }}"
                                                    data-action="{{ route('admin.managed-diagnostics.update', $diagnostic) }}"
                                                    onclick="openEditDiagnosticModal(this)"
                                                >
                                                    Editar
                                                </button>

                                                @if(!$hasDependencies)
                                                    <form
                                                        method="POST"
                                                        action="{{ route('admin.managed-diagnostics.destroy', $diagnostic) }}"
                                                        onsubmit="return confirm('¿Seguro que deseas eliminar este diagnóstico?');"
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
                                                    <form method="POST" action="{{ route('admin.managed-diagnostics.toggle-status', $diagnostic) }}">
                                                        @csrf
                                                        @method('PATCH')

                                                        <button
                                                            type="submit"
                                                            class="rounded-lg px-3 py-2 text-xs font-semibold text-white transition {{ $diagnostic->status ? 'bg-amber-500 hover:bg-amber-600' : 'bg-green-600 hover:bg-green-700' }}"
                                                        >
                                                            {{ $diagnostic->status ? 'Inactivar' : 'Activar' }}
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-10 text-center text-sm text-slate-500">
                                            No hay diagnósticos registrados todavía.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($diagnostics->hasPages())
                        <div class="border-t border-slate-200 px-6 py-4">
                            {{ $diagnostics->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL EDITAR -->
    <div id="editDiagnosticModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
        <div class="w-full max-w-3xl rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Editar diagnóstico</h3>
                <button type="button" class="text-slate-500 hover:text-slate-900" onclick="closeEditDiagnosticModal()">✕</button>
            </div>

            <form id="editDiagnosticForm" method="POST" class="space-y-5 p-6">
                @csrf
                @method('PUT')

                @if($singleClient)
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Cliente</label>
                        <div class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            {{ $singleClient->name }}
                        </div>
                        <input type="hidden" name="client_id" value="{{ $singleClient->id }}">
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

                <x-form.input
                    name="name"
                    label="Nombre"
                    id="edit_diagnostic_name"
                />

                <x-form.input
                    name="code"
                    label="Código"
                    id="edit_diagnostic_code"
                />

                <x-form.textarea
                    name="description"
                    label="Descripción"
                    id="edit_diagnostic_description"
                    rows="4"
                />

                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        onclick="closeEditDiagnosticModal()"
                        class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                    >
                        Actualizar diagnóstico
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function handleSingleClientSelection(checkbox) {
            const all = document.querySelectorAll('.client-single-checkbox');

            all.forEach(item => {
                if (item !== checkbox) {
                    item.checked = false;
                }
            });

            document.getElementById('selected_client_id').value = checkbox.checked ? checkbox.value : '';
        }

        function handleSingleClientSelectionEdit(checkbox) {
            const all = document.querySelectorAll('.edit-client-single-checkbox');

            all.forEach(item => {
                if (item !== checkbox) {
                    item.checked = false;
                }
            });

            document.getElementById('edit_selected_client_id').value = checkbox.checked ? checkbox.value : '';
        }

        function openEditDiagnosticModal(btn) {
            document.getElementById('editDiagnosticForm').action = btn.dataset.action;
            document.getElementById('edit_diagnostic_name').value = btn.dataset.name ?? '';
            document.getElementById('edit_diagnostic_code').value = btn.dataset.code ?? '';
            document.getElementById('edit_diagnostic_description').value = btn.dataset.description ?? '';

            const clientId = btn.dataset.client_id ?? '';

            const hiddenClientInput = document.getElementById('edit_selected_client_id');
            if (hiddenClientInput) {
                hiddenClientInput.value = clientId;
            }

            document.querySelectorAll('.edit-client-single-checkbox').forEach(cb => {
                cb.checked = parseInt(cb.value) === parseInt(clientId);
            });

            const modal = document.getElementById('editDiagnosticModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeEditDiagnosticModal() {
            const modal = document.getElementById('editDiagnosticModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }

        document.addEventListener('DOMContentLoaded', function () {
            const selectedClient = document.getElementById('selected_client_id');

            if (selectedClient && selectedClient.value) {
                document.querySelectorAll('.client-single-checkbox').forEach(cb => {
                    cb.checked = parseInt(cb.value) === parseInt(selectedClient.value);
                });
            }
        });
    </script>
@endsection