@extends('layouts.admin')

@section('title', 'Clientes')
@section('header_title', 'Clientes')

@section('content')
    <div class="space-y-8">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Gestión de clientes</h2>
            <p class="mt-2 text-slate-600">
                Administra los clientes del sistema y su estado operativo.
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
                    <h3 class="text-lg font-semibold text-slate-900">Nuevo cliente</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Registra una nueva empresa cliente.
                    </p>

                    <form method="POST" action="{{ route('admin.clients.store') }}" class="mt-6 space-y-5">
                        @csrf

                        <x-form.input
                            name="name"
                            label="Nombre"
                            placeholder="Ej. CORONA"
                        />

                        <x-form.textarea
                            name="obs"
                            label="Observaciones"
                            placeholder="Información adicional del cliente"
                            rows="4"
                        />

                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                        >
                            Guardar cliente
                        </button>
                    </form>
                </div>
            </div>

            <!-- TABLA -->
            <div class="xl:col-span-2">
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <h3 class="text-lg font-semibold text-slate-900">Listado de clientes</h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Nombre</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Obs.</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Dependencias</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Estado</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Acciones</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-200 bg-white">
                                @forelse($clients as $client)
                                    @php
                                        $dependencyCount =
                                            $client->areas_count +
                                            $client->users_count +
                                            $client->element_types_count +
                                            $client->components_count +
                                            $client->diagnostics_count +
                                            $client->conditions_count;
                                    @endphp

                                    <tr class="hover:bg-slate-50">

                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">
                                            {{ $client->name }}
                                        </td>

                                        <td class="px-6 py-4 text-sm text-slate-600">
                                            {{ $client->obs ?: '—' }}
                                        </td>
                                        
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                            {{ $dependencyCount }}
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                                            @if($client->status)
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
                                                    onclick="openEditClientModal(
                                                        '{{ $client->id }}',
                                                        @js($client->name),
                                                        @js($client->obs),
                                                        '{{ route('admin.clients.update', $client) }}'
                                                    )"
                                                >
                                                    Editar
                                                </button>

                                                @if($dependencyCount === 0)
                                                    <form method="POST"
                                                          action="{{ route('admin.clients.destroy', $client) }}"
                                                          onsubmit="return confirm('¿Seguro que deseas eliminar este cliente?');">
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
                                                    <form method="POST" action="{{ route('admin.clients.toggle-status', $client) }}">
                                                        @csrf
                                                        @method('PATCH')

                                                        <button
                                                            type="submit"
                                                            class="rounded-lg px-3 py-2 text-xs font-semibold text-white transition {{ $client->status ? 'bg-amber-500 hover:bg-amber-600' : 'bg-green-600 hover:bg-green-700' }}"
                                                        >
                                                            {{ $client->status ? 'Inactivar' : 'Activar' }}
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-10 text-center text-sm text-slate-500">
                                            No hay clientes registrados todavía.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- MODAL EDITAR -->
    <div id="editClientModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
        <div class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Editar cliente</h3>
                <button type="button" class="text-slate-500 hover:text-slate-900" onclick="closeEditClientModal()">✕</button>
            </div>

            <form id="editClientForm" method="POST" class="space-y-5 p-6">
                @csrf
                @method('PUT')

                <x-form.input
                    name="name"
                    label="Nombre"
                    id="edit_client_name"
                />

                <x-form.textarea
                    name="obs"
                    label="Observaciones"
                    id="edit_client_obs"
                    rows="4"
                />

                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        onclick="closeEditClientModal()"
                        class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                    >
                        Actualizar cliente
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditClientModal(id, name, obs, actionUrl) {
            document.getElementById('editClientForm').action = actionUrl;
            document.getElementById('edit_client_name').value = name ?? '';
            document.getElementById('edit_client_obs').value = obs ?? '';

            const modal = document.getElementById('editClientModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeEditClientModal() {
            const modal = document.getElementById('editClientModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }
    </script>
@endsection