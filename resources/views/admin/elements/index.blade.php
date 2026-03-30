@extends('layouts.admin')

@section('title', 'Elementos')
@section('header_title', 'Elementos')

@section('content')
    <div class="space-y-8">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Gestión de elementos</h2>
            <p class="mt-2 text-slate-600">
                Administra los elementos reales del sistema, asociados a un área y a un tipo de elemento.
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
                    <h3 class="text-lg font-semibold text-slate-900">Nuevo elemento</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Registra una instancia real como Banda 001 o Motor 003.
                    </p>

                    <form method="POST" action="{{ route('admin.elements.store') }}" class="mt-6 space-y-5">
                        @csrf

                        <x-form.input
                            name="name"
                            label="Nombre"
                            placeholder="Ej. Banda 001"
                        />

                        <x-form.input
                            name="code"
                            label="Código"
                            placeholder="Ej. BANDA-001"
                        />

                        <x-form.select name="area_id" label="Área">
                            <option value="">Seleccione un área</option>
                            @foreach($areas as $area)
                                <option value="{{ $area->id }}" @selected(old('area_id') == $area->id)>
                                    {{ $area->client?->name }} - {{ $area->name }}
                                </option>
                            @endforeach
                        </x-form.select>

                        <x-form.select name="element_type_id" label="Tipo de elemento">
                            <option value="">Seleccione un tipo</option>
                            @foreach($elementTypes as $elementType)
                                <option value="{{ $elementType->id }}" @selected(old('element_type_id') == $elementType->id)>
                                    {{ $elementType->name }}
                                </option>
                            @endforeach
                        </x-form.select>

                        <x-form.input
                            name="warehouse_code"
                            label="Código de almacén"
                            placeholder="Ej. ALM-202"
                        />

                        <x-form.select name="status" label="Estado">
                            <option value="1" @selected(old('status', '1') == '1')>Activo</option>
                            <option value="0" @selected(old('status') == '0')>Inactivo</option>
                        </x-form.select>

                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                        >
                            Guardar elemento
                        </button>
                    </form>
                </div>
            </div>

            <div class="xl:col-span-2">
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <h3 class="text-lg font-semibold text-slate-900">Listado de elementos</h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Nombre</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Código</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Cliente / Área</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Tipo</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Almacén</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Estado</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @forelse($elements as $element)
                                    <tr class="hover:bg-slate-50">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                            {{ $element->id }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">
                                            {{ $element->name }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                                            {{ $element->code }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-600">
                                            {{ $element->area?->client?->name ?? '—' }} / {{ $element->area?->name ?? '—' }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                            {{ $element->elementType?->name ?? '—' }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                                            {{ $element->warehouse_code ?: '—' }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm">
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
                                        <td class="whitespace-nowrap px-6 py-4 text-right">
                                            <div class="flex justify-end gap-2">
                                                <button
                                                    type="button"
                                                    class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                                                    onclick="openEditElementModal(
                                                        '{{ $element->id }}',
                                                        @js($element->name),
                                                        @js($element->code),
                                                        '{{ $element->area_id }}',
                                                        '{{ $element->element_type_id }}',
                                                        @js($element->warehouse_code),
                                                        '{{ $element->status ? 1 : 0 }}',
                                                        '{{ route('admin.elements.update', $element) }}'
                                                    )"
                                                >
                                                    Editar
                                                </button>

                                                <form method="POST" action="{{ route('admin.elements.destroy', $element) }}"
                                                      onsubmit="return confirm('¿Seguro que deseas eliminar este elemento?');">
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
                                        <td colspan="8" class="px-6 py-10 text-center text-sm text-slate-500">
                                            No hay elementos registrados todavía.
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

    <div id="editElementModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
        <div class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Editar elemento</h3>
                <button type="button" class="text-slate-500 hover:text-slate-900" onclick="closeEditElementModal()">✕</button>
            </div>

            <form id="editElementForm" method="POST" class="space-y-5 p-6">
                @csrf
                @method('PUT')

                <x-form.input name="name" label="Nombre" id="edit_element_name" />
                <x-form.input name="code" label="Código" id="edit_element_code" />

                <x-form.select name="area_id" label="Área" id="edit_element_area_id">
                    @foreach($areas as $area)
                        <option value="{{ $area->id }}">
                            {{ $area->client?->name }} - {{ $area->name }}
                        </option>
                    @endforeach
                </x-form.select>

                <x-form.select name="element_type_id" label="Tipo de elemento" id="edit_element_type_id">
                    @foreach($elementTypes as $elementType)
                        <option value="{{ $elementType->id }}">
                            {{ $elementType->name }}
                        </option>
                    @endforeach
                </x-form.select>

                <x-form.input name="warehouse_code" label="Código de almacén" id="edit_element_warehouse_code" />

                <x-form.select name="status" label="Estado" id="edit_element_status">
                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>
                </x-form.select>

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
                        Actualizar elemento
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditElementModal(id, name, code, areaId, elementTypeId, warehouseCode, status, actionUrl) {
            document.getElementById('editElementForm').action = actionUrl;
            document.getElementById('edit_element_name').value = name ?? '';
            document.getElementById('edit_element_code').value = code ?? '';
            document.getElementById('edit_element_area_id').value = areaId ?? '';
            document.getElementById('edit_element_type_id').value = elementTypeId ?? '';
            document.getElementById('edit_element_warehouse_code').value = warehouseCode ?? '';
            document.getElementById('edit_element_status').value = status;

            const modal = document.getElementById('editElementModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeEditElementModal() {
            const modal = document.getElementById('editElementModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }
    </script>
@endsection