@extends('layouts.admin')

@section('title', 'Condiciones')
@section('header_title', 'Condiciones')

@section('content')
    <div class="space-y-8">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Gestión de condiciones</h2>
            <p class="mt-2 text-slate-600">
                Define el catálogo de condiciones usado en la evaluación de diagnósticos.
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
                    <h3 class="text-lg font-semibold text-slate-900">Nueva condición</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Registra una condición con su severidad.
                    </p>

                    <form method="POST" action="{{ route('admin.conditions.store') }}" class="mt-6 space-y-5">
                        @csrf

                        <x-form.input
                            name="code"
                            label="Código"
                            placeholder="Ej. COND-01"
                        />

                        <x-form.input
                            name="name"
                            label="Nombre"
                            placeholder="Ej. Crítica"
                        />

                        <x-form.textarea
                            name="description"
                            label="Descripción"
                            placeholder="Describe la condición"
                            rows="4"
                        />

                        <x-form.input
                            name="severity"
                            label="Severidad"
                            type="number"
                            placeholder="Ej. 1"
                        />

                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                        >
                            Guardar condición
                        </button>
                    </form>
                </div>
            </div>

            <div class="xl:col-span-2">
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <h3 class="text-lg font-semibold text-slate-900">Listado de condiciones</h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Código</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Nombre</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Descripción</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Severidad</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Uso</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Estado</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @forelse($conditions as $condition)
                                    <tr class="hover:bg-slate-50">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                            {{ $condition->id }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                                            {{ $condition->code }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">
                                            {{ $condition->name }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-600">
                                            {{ $condition->description ?: '—' }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                            {{ $condition->severity }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                            {{ $condition->report_details_count }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                                            @if($condition->status)
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
                                                    onclick="openEditConditionModal(
                                                        '{{ $condition->id }}',
                                                        @js($condition->code),
                                                        @js($condition->name),
                                                        @js($condition->description),
                                                        '{{ $condition->severity }}',
                                                        '{{ route('admin.conditions.update', $condition) }}'
                                                    )"
                                                >
                                                    Editar
                                                </button>

                                                @if($condition->report_details_count == 0)
                                                    <form method="POST" action="{{ route('admin.conditions.destroy', $condition) }}"
                                                          onsubmit="return confirm('¿Seguro que deseas eliminar esta condición?');">
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
                                                    <form method="POST" action="{{ route('admin.conditions.toggle-status', $condition) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button
                                                            type="submit"
                                                            class="rounded-lg px-3 py-2 text-xs font-semibold text-white transition {{ $condition->status ? 'bg-amber-500 hover:bg-amber-600' : 'bg-green-600 hover:bg-green-700' }}"
                                                        >
                                                            {{ $condition->status ? 'Inactivar' : 'Activar' }}
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-10 text-center text-sm text-slate-500">
                                            No hay condiciones registradas todavía.
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

    <div id="editConditionModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
        <div class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Editar condición</h3>
                <button type="button" class="text-slate-500 hover:text-slate-900" onclick="closeEditConditionModal()">✕</button>
            </div>

            <form id="editConditionForm" method="POST" class="space-y-5 p-6">
                @csrf
                @method('PUT')

                <x-form.input name="code" label="Código" id="edit_condition_code" />
                <x-form.input name="name" label="Nombre" id="edit_condition_name" />
                <x-form.textarea name="description" label="Descripción" id="edit_condition_description" rows="4" />
                <x-form.input name="severity" label="Severidad" id="edit_condition_severity" type="number" />

                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        onclick="closeEditConditionModal()"
                        class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                    >
                        Actualizar condición
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditConditionModal(id, code, name, description, severity, actionUrl) {
            document.getElementById('editConditionForm').action = actionUrl;
            document.getElementById('edit_condition_code').value = code ?? '';
            document.getElementById('edit_condition_name').value = name ?? '';
            document.getElementById('edit_condition_description').value = description ?? '';
            document.getElementById('edit_condition_severity').value = severity ?? '';

            const modal = document.getElementById('editConditionModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeEditConditionModal() {
            clearAjaxErrors('editConditionAjaxErrors');

            const modal = document.getElementById('editConditionModal');
            const content = document.getElementById('editConditionModalContent');

            content.classList.remove('scale-100', 'opacity-100');
            content.classList.add('scale-95', 'opacity-0');

            setTimeout(() => {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            }, 150);
        }
    </script>
@endsection