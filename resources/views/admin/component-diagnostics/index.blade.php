@extends('layouts.admin')

@section('title', 'Componentes y diagnósticos')
@section('header_title', 'Componentes y diagnósticos')

@section('content')
    <div class="space-y-8">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Relación componente - diagnóstico</h2>
            <p class="mt-2 text-slate-600">
                Define qué diagnósticos aplican a cada componente del sistema.
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
                    <h3 class="text-lg font-semibold text-slate-900">Nueva relación</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Asocia un diagnóstico a un componente.
                    </p>

                    <form method="POST" action="{{ route('admin.component-diagnostics.store') }}" class="mt-6 space-y-5">
                        @csrf

                        <x-form.select name="component_id" label="Componente">
                            <option value="">Seleccione un componente</option>
                            @foreach($components as $component)
                                <option value="{{ $component->id }}" @selected(old('component_id') == $component->id)>
                                    {{ $component->elementType?->name }} - {{ $component->name }} ({{ $component->code }})
                                </option>
                            @endforeach
                        </x-form.select>

                        <x-form.select name="diagnostic_id" label="Diagnóstico">
                            <option value="">Seleccione un diagnóstico</option>
                            @foreach($diagnostics as $diagnostic)
                                <option value="{{ $diagnostic->id }}" @selected(old('diagnostic_id') == $diagnostic->id)>
                                    {{ $diagnostic->name }} ({{ $diagnostic->code }})
                                </option>
                            @endforeach
                        </x-form.select>

                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                        >
                            Guardar relación
                        </button>
                    </form>
                </div>
            </div>

            <div class="xl:col-span-2">
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <h3 class="text-lg font-semibold text-slate-900">Listado de relaciones</h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Tipo</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Componente</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Diagnóstico</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @forelse($componentDiagnostics as $item)
                                    <tr class="hover:bg-slate-50">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                            {{ $item->id }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                            {{ $item->component?->elementType?->name ?? '—' }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">
                                            {{ $item->component?->name ?? '—' }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                            {{ $item->diagnostic?->name ?? '—' }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right">
                                            <div class="flex justify-end gap-2">
                                                <button
                                                    type="button"
                                                    class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                                                    onclick="openEditComponentDiagnosticModal(
                                                        '{{ $item->id }}',
                                                        '{{ $item->component_id }}',
                                                        '{{ $item->diagnostic_id }}',
                                                        '{{ route('admin.component-diagnostics.update', $item) }}'
                                                    )"
                                                >
                                                    Editar
                                                </button>

                                                <form method="POST" action="{{ route('admin.component-diagnostics.destroy', $item) }}"
                                                      onsubmit="return confirm('¿Seguro que deseas eliminar esta relación?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button
                                                        type="submit"
                                                        class="rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-red-700"
                                                    >
                                                        Eliminar
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500">
                                            No hay relaciones registradas todavía.
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

    <div id="editComponentDiagnosticModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
        <div class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Editar relación</h3>
                <button type="button" class="text-slate-500 hover:text-slate-900" onclick="closeEditComponentDiagnosticModal()">✕</button>
            </div>

            <form id="editComponentDiagnosticForm" method="POST" class="space-y-5 p-6">
                @csrf
                @method('PUT')

                <x-form.select name="component_id" label="Componente" id="edit_component_diagnostic_component_id">
                    @foreach($components as $component)
                        <option value="{{ $component->id }}">
                            {{ $component->elementType?->name }} - {{ $component->name }} ({{ $component->code }})
                        </option>
                    @endforeach
                </x-form.select>

                <x-form.select name="diagnostic_id" label="Diagnóstico" id="edit_component_diagnostic_diagnostic_id">
                    @foreach($diagnostics as $diagnostic)
                        <option value="{{ $diagnostic->id }}">
                            {{ $diagnostic->name }} ({{ $diagnostic->code }})
                        </option>
                    @endforeach
                </x-form.select>

                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        onclick="closeEditComponentDiagnosticModal()"
                        class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                    >
                        Actualizar relación
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditComponentDiagnosticModal(id, componentId, diagnosticId, actionUrl) {
            document.getElementById('editComponentDiagnosticForm').action = actionUrl;
            document.getElementById('edit_component_diagnostic_component_id').value = componentId ?? '';
            document.getElementById('edit_component_diagnostic_diagnostic_id').value = diagnosticId ?? '';

            const modal = document.getElementById('editComponentDiagnosticModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeEditComponentDiagnosticModal() {
            const modal = document.getElementById('editComponentDiagnosticModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }
    </script>
@endsection