@extends('layouts.admin')

@section('title', 'Activos')
@section('header_title', 'Activos')

@section('content')
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

        <div class="grid gap-8 xl:grid-cols-3">
            <div class="xl:col-span-1">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Nuevo activo</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Registra un nuevo activo para uno de tus clientes.
                    </p>

                    <form method="POST" action="{{ route('admin.managed-elements.store') }}" class="mt-6 space-y-5">
                        @csrf

                        @if($showClientColumn)
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Cliente</label>
                                <div class="space-y-2 rounded-xl border border-slate-300 p-4">
                                    @foreach($clients as $client)
                                        <label class="flex items-center gap-3 text-sm text-slate-700">
                                            <input
                                                type="checkbox"
                                                name="client_id"
                                                value="{{ $client->id }}"
                                                class="client-checkbox rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                                                {{ (string) old('client_id') === (string) $client->id ? 'checked' : '' }}
                                            >
                                            <span>{{ $client->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('client_id')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @else
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Cliente</label>
                                <input
                                    type="text"
                                    value="{{ $clients->first()?->name }}"
                                    disabled
                                    class="w-full rounded-xl border border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-700"
                                >
                                <input type="hidden" name="client_id" value="{{ $clients->first()?->id }}">
                            </div>
                        @endif

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Área</label>
                            <select
                                name="area_id"
                                id="create_area_id"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            >
                                <option value="">Seleccione un área</option>
                                @foreach($areas as $area)
                                    <option
                                        value="{{ $area->id }}"
                                        data-client-id="{{ $area->client_id }}"
                                        @selected(old('area_id') == $area->id)
                                    >
                                        @if($showClientColumn)
                                            {{ $area->client?->name }} - {{ $area->name }}
                                        @else
                                            {{ $area->name }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('area_id')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Tipo de activo</label>
                            <select
                                name="element_type_id"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            >
                                <option value="">Seleccione un tipo</option>
                                @foreach($elementTypes as $elementType)
                                    <option value="{{ $elementType->id }}" @selected(old('element_type_id') == $elementType->id)>
                                        {{ $elementType->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('element_type_id')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Nombre</label>
                            <input
                                type="text"
                                name="name"
                                value="{{ old('name') }}"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                placeholder="Ej. Banda 001"
                            >
                            @error('name')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Código</label>
                            <input
                                type="text"
                                name="code"
                                value="{{ old('code') }}"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                placeholder="Opcional"
                            >
                            @error('code')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Código de almacén</label>
                            <input
                                type="text"
                                name="warehouse_code"
                                value="{{ old('warehouse_code') }}"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                placeholder="Opcional"
                            >
                            @error('warehouse_code')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Estado</label>
                            <select
                                name="status"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                            >
                                <option value="1" @selected(old('status', '1') == '1')>Activo</option>
                                <option value="0" @selected(old('status') == '0')>Inactivo</option>
                            </select>
                            @error('status')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                        >
                            Guardar activo
                        </button>
                    </form>
                </div>
            </div>

            <div class="xl:col-span-2">
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Listado de activos</h3>
                            </div>

                            <form method="GET" action="{{ route('admin.managed-elements.index') }}" class="grid gap-3 md:grid-cols-3">
                                @if($showClientColumn)
                                    <div>
                                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Cliente</label>
                                        <select
                                            name="client_id"
                                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                        >
                                            <option value="">Todos</option>
                                            @foreach($clients as $client)
                                                <option value="{{ $client->id }}" @selected(($filters['client_id'] ?? '') == $client->id)>
                                                    {{ $client->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                <div>
                                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Nombre</label>
                                    <input
                                        type="text"
                                        name="name"
                                        value="{{ $filters['name'] ?? '' }}"
                                        class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                                        placeholder="Filtrar"
                                    >
                                </div>

                                <div class="flex items-end gap-2">
                                    <button
                                        type="submit"
                                        class="inline-flex items-center justify-center rounded-xl bg-[#d94d33] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                                    >
                                        Filtrar
                                    </button>

                                    <a
                                        href="{{ route('admin.managed-elements.index') }}"
                                        class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
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
                                    @if($showClientColumn)
                                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                            Cliente
                                        </th>
                                    @endif
                                    <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Área
                                    </th>
                                    <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Tipo
                                    </th>
                                    <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Nombre
                                    </th>
                                    <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Código
                                    </th>
                                    <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Código almacén
                                    </th>
                                    <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Comp.
                                    </th>
                                    <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Uso
                                    </th>
                                    <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Estado
                                    </th>
                                    <th class="whitespace-nowrap px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-200 bg-white">
                                @forelse($elements as $element)
                                    <tr class="hover:bg-slate-50">
                                        @if($showClientColumn)
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

                                        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700">
                                            {{ $element->code ?: '—' }}
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700">
                                            {{ $element->warehouse_code ?: '—' }}
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700">
                                            {{ $element->components_count ?? 0 }}
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700">
                                            {{ $element->report_details_count ?? 0 }}
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
                                                    onclick="openComponentsModal(
                                                        '{{ $element->id }}',
                                                        @js($element->area?->name ?? '—'),
                                                        @js($element->elementType?->name ?? '—'),
                                                        @js($element->name),
                                                        '{{ route('admin.managed-elements.components.update', $element) }}',
                                                        @js($element->components->pluck('id')->map(fn($id) => (string) $id)->toArray())
                                                    )"
                                                    class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                                                >
                                                    Componentes
                                                </button>

                                                <button
                                                    type="button"
                                                    onclick="openEditElementModal(
                                                        '{{ $element->id }}',
                                                        @js($element->name),
                                                        @js($element->code),
                                                        @js($element->warehouse_code),
                                                        '{{ $element->area_id }}',
                                                        '{{ $element->element_type_id }}',
                                                        '{{ $element->status ? 1 : 0 }}',
                                                        '{{ route('admin.managed-elements.update', $element) }}'
                                                    )"
                                                    class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                                                >
                                                    Editar
                                                </button>

                                                <form
                                                    method="POST"
                                                    action="{{ route('admin.managed-elements.destroy', $element) }}"
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
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $showClientColumn ? 10 : 9 }}" class="px-5 py-10 text-center text-sm text-slate-500">
                                            No hay activos registrados todavía.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if(method_exists($elements, 'links'))
                        <div class="border-t border-slate-200 px-6 py-4">
                            {{ $elements->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL EDITAR --}}
    <div id="editElementModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
        <div class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Editar activo</h3>
                <button type="button" class="text-slate-500 hover:text-slate-900" onclick="closeEditElementModal()">✕</button>
            </div>

            <form id="editElementForm" method="POST" class="space-y-5 p-6">
                @csrf
                @method('PUT')

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Nombre</label>
                    <input
                        type="text"
                        name="name"
                        id="edit_element_name"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                    >
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Código</label>
                    <input
                        type="text"
                        name="code"
                        id="edit_element_code"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                    >
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Código de almacén</label>
                    <input
                        type="text"
                        name="warehouse_code"
                        id="edit_element_warehouse_code"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                    >
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Área</label>
                    <select
                        name="area_id"
                        id="edit_element_area_id"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                    >
                        @foreach($areas as $area)
                            <option value="{{ $area->id }}">
                                @if($showClientColumn)
                                    {{ $area->client?->name }} - {{ $area->name }}
                                @else
                                    {{ $area->name }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Tipo de activo</label>
                    <select
                        name="element_type_id"
                        id="edit_element_type_id"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                    >
                        @foreach($elementTypes as $elementType)
                            <option value="{{ $elementType->id }}">
                                {{ $elementType->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Estado</label>
                    <select
                        name="status"
                        id="edit_element_status"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                    >
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>

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

    {{-- MODAL COMPONENTES --}}
    <div id="componentsModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
        <div class="w-full max-w-3xl rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Asociar componentes</h3>
                <button type="button" class="text-slate-500 hover:text-slate-900" onclick="closeComponentsModal()">✕</button>
            </div>

            <form id="componentsForm" method="POST" class="space-y-5 p-6">
                @csrf
                @method('PUT')

                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Área</div>
                        <div id="components_area_name" class="mt-1 text-sm text-slate-900"></div>
                    </div>

                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tipo de activo</div>
                        <div id="components_type_name" class="mt-1 text-sm text-slate-900"></div>
                    </div>

                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nombre</div>
                        <div id="components_element_name" class="mt-1 text-sm text-slate-900"></div>
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Componentes disponibles</label>
                    <div id="componentsChecklist" class="grid max-h-[420px] gap-3 overflow-y-auto rounded-xl border border-slate-200 p-4 md:grid-cols-2">
                        @forelse($components as $component)
                            <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-3 text-sm text-slate-700">
                                <input
                                    type="checkbox"
                                    name="component_ids[]"
                                    value="{{ $component->id }}"
                                    data-component-checkbox
                                    class="mt-0.5 rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                                >
                                <span>{{ $component->name }}</span>
                            </label>
                        @empty
                            <div class="text-sm text-slate-500">No hay componentes disponibles.</div>
                        @endforelse
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
        document.querySelectorAll('.client-checkbox').forEach((checkbox) => {
            checkbox.addEventListener('change', function () {
                if (!this.checked) return;

                document.querySelectorAll('.client-checkbox').forEach((item) => {
                    if (item !== this) item.checked = false;
                });
            });
        });

        function openEditElementModal(id, name, code, warehouseCode, areaId, elementTypeId, status, actionUrl) {
            document.getElementById('editElementForm').action = actionUrl;
            document.getElementById('edit_element_name').value = name ?? '';
            document.getElementById('edit_element_code').value = code ?? '';
            document.getElementById('edit_element_warehouse_code').value = warehouseCode ?? '';
            document.getElementById('edit_element_area_id').value = areaId ?? '';
            document.getElementById('edit_element_type_id').value = elementTypeId ?? '';
            document.getElementById('edit_element_status').value = status ?? '1';

            const modal = document.getElementById('editElementModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeEditElementModal() {
            const modal = document.getElementById('editElementModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }

        function openComponentsModal(elementId, areaName, typeName, elementName, actionUrl, selectedComponentIds) {
            document.getElementById('componentsForm').action = actionUrl;
            document.getElementById('components_area_name').textContent = areaName ?? '—';
            document.getElementById('components_type_name').textContent = typeName ?? '—';
            document.getElementById('components_element_name').textContent = elementName ?? '—';

            const selectedSet = new Set((selectedComponentIds ?? []).map(String));

            document.querySelectorAll('[data-component-checkbox]').forEach((checkbox) => {
                checkbox.checked = selectedSet.has(String(checkbox.value));
            });

            const modal = document.getElementById('componentsModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeComponentsModal() {
            const modal = document.getElementById('componentsModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }
    </script>
@endsection
