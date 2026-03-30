@extends('layouts.admin')

@section('title', 'Elementos y componentes')
@section('header_title', 'Elementos y componentes')

@section('content')
    <div class="space-y-8">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Relación elemento - componente</h2>
            <p class="mt-2 text-slate-600">
                Define qué componentes pertenecen a cada elemento real del sistema.
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
                        Asocia un componente a un elemento real.
                    </p>

                    <form method="POST" action="{{ route('admin.element-components.store') }}" class="mt-6 space-y-5">
                        @csrf

                        <x-form.select name="element_id" label="Elemento" id="element_id" onchange="filterComponentsByElementType()">
                            <option value="">Seleccione un elemento</option>
                            @foreach($elements as $element)
                                <option
                                    value="{{ $element->id }}"
                                    data-element-type-id="{{ $element->element_type_id }}"
                                    @selected(old('element_id') == $element->id)
                                >
                                    {{ $element->name }} ({{ $element->code }}) - {{ $element->area?->client?->name }}
                                </option>
                            @endforeach
                        </x-form.select>

                        <x-form.select name="component_id" label="Componente" id="component_id">
                            <option value="">Seleccione un componente</option>
                            @foreach($components as $component)
                                <option
                                    value="{{ $component->id }}"
                                    data-element-type-id="{{ $component->element_type_id }}"
                                    @selected(old('component_id') == $component->id)
                                >
                                    {{ $component->name }} ({{ $component->code }}) - {{ $component->elementType?->name }}
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
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Elemento</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Cliente / Área</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Componente</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @forelse($elementComponents as $item)
                                    <tr class="hover:bg-slate-50">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                            {{ $item->id }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">
                                            {{ $item->element?->name ?? '—' }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-600">
                                            {{ $item->element?->area?->client?->name ?? '—' }} /
                                            {{ $item->element?->area?->name ?? '—' }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                            {{ $item->component?->name ?? '—' }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right">
                                            <div class="flex justify-end gap-2">
                                                <button
                                                    type="button"
                                                    class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                                                    onclick="openEditElementComponentModal(
                                                        '{{ $item->id }}',
                                                        '{{ $item->element_id }}',
                                                        '{{ $item->component_id }}',
                                                        '{{ route('admin.element-components.update', $item) }}'
                                                    )"
                                                >
                                                    Editar
                                                </button>

                                                <form method="POST" action="{{ route('admin.element-components.destroy', $item) }}"
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

    <div id="editElementComponentModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
        <div class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Editar relación</h3>
                <button type="button" class="text-slate-500 hover:text-slate-900" onclick="closeEditElementComponentModal()">✕</button>
            </div>

            <form id="editElementComponentForm" method="POST" class="space-y-5 p-6">
                @csrf
                @method('PUT')

                <x-form.select name="element_id" label="Elemento" id="edit_element_component_element_id" disabled onchange="filterEditComponentsByElementType()">
                    @foreach($elements as $element)
                        <option
                            value="{{ $element->id }}"
                            data-element-type-id="{{ $element->element_type_id }}"
                        >
                            {{ $element->name }} ({{ $element->code }}) - {{ $element->area?->client?->name }}
                        </option>
                    @endforeach
                </x-form.select>

                <x-form.select name="component_id" label="Componente" id="edit_element_component_component_id">
                    @foreach($components as $component)
                        <option
                            value="{{ $component->id }}"
                            data-element-type-id="{{ $component->element_type_id }}"
                        >
                            {{ $component->name }} ({{ $component->code }}) - {{ $component->elementType?->name }}
                        </option>
                    @endforeach
                </x-form.select>

                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        onclick="closeEditElementComponentModal()"
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
        function filterComponents(selectElementId, selectComponentId) {
            const elementSelect = document.getElementById(selectElementId);
            const componentSelect = document.getElementById(selectComponentId);

            const selectedElementOption = elementSelect.options[elementSelect.selectedIndex];
            const elementTypeId = selectedElementOption?.dataset?.elementTypeId || '';

            Array.from(componentSelect.options).forEach(option => {
                if (!option.value) {
                    option.hidden = false;
                    return;
                }

                const componentTypeId = option.dataset.elementTypeId;

                option.hidden = elementTypeId && componentTypeId !== elementTypeId;
            });

            const currentSelected = componentSelect.options[componentSelect.selectedIndex];
            if (currentSelected && currentSelected.hidden) {
                componentSelect.value = '';
            }
        }

        function filterComponentsByElementType() {
            filterComponents('element_id', 'component_id');
        }

        function filterEditComponentsByElementType() {
            filterComponents('edit_element_component_element_id', 'edit_element_component_component_id');
        }

        function openEditElementComponentModal(id, elementId, componentId, actionUrl) {
            document.getElementById('editElementComponentForm').action = actionUrl;
            document.getElementById('edit_element_component_element_id').value = elementId ?? '';
            filterEditComponentsByElementType();
            document.getElementById('edit_element_component_component_id').value = componentId ?? '';

            const modal = document.getElementById('editElementComponentModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeEditElementComponentModal() {
            const modal = document.getElementById('editElementComponentModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }

        document.addEventListener('DOMContentLoaded', function () {
            filterComponentsByElementType();
        });
    </script>
@endsection