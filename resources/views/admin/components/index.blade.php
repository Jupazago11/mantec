@extends('layouts.admin')

@section('title', 'Componentes')
@section('header_title', 'Componentes')

@section('content')
    <div class="space-y-8">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Gestión de componentes</h2>
            <p class="mt-2 text-slate-600">
                Define los componentes asociados a cada tipo de elemento.
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
                        Registra un componente para un tipo de elemento específico.
                    </p>

                    <form method="POST" action="{{ route('admin.components.store') }}" class="mt-6 space-y-5">
                        @csrf

                        <x-form.select name="element_type_id" label="Tipo de elemento">
                            <option value="">Seleccione un tipo</option>
                            @foreach($elementTypes as $elementType)
                                <option value="{{ $elementType->id }}" @selected(old('element_type_id') == $elementType->id)>
                                    {{ $elementType->name }}
                                </option>
                            @endforeach
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
                        <h3 class="text-lg font-semibold text-slate-900">Listado de componentes</h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Componente</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Tipo de elemento</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Por defecto</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Estado</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @forelse($components as $component)
                                    <tr class="hover:bg-slate-50">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                            {{ $component->id }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">
                                            {{ $component->name }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                            {{ $component->elementType?->name ?? '—' }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                                            {{ $component->is_default ? 'Sí' : 'No' }}
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
                                                    onclick="openEditComponentModal(
                                                        '{{ $component->id }}',
                                                        '{{ $component->element_type_id }}',
                                                        @js($component->name),
                                                        '{{ $component->is_default ? 1 : 0 }}',
                                                        '{{ route('admin.components.update', $component) }}'
                                                    )"
                                                >
                                                    Editar
                                                </button>

                                                <form method="POST" action="{{ route('admin.components.toggle-status', $component) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button
                                                        type="submit"
                                                        class="rounded-lg px-3 py-2 text-xs font-semibold text-white transition {{ $component->status ? 'bg-amber-500 hover:bg-amber-600' : 'bg-green-600 hover:bg-green-700' }}"
                                                    >
                                                        {{ $component->status ? 'Inactivar' : 'Activar' }}
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-10 text-center text-sm text-slate-500">
                                            No hay componentes registrados todavía.
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

    <div id="editComponentModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
        <div class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Editar componente</h3>
                <button type="button" class="text-slate-500 hover:text-slate-900" onclick="closeEditComponentModal()">✕</button>
            </div>

            <form id="editComponentForm" method="POST" class="space-y-5 p-6">
                @csrf
                @method('PUT')

                <x-form.select name="element_type_id" label="Tipo de elemento" id="edit_component_element_type_id">
                    @foreach($elementTypes as $elementType)
                        <option value="{{ $elementType->id }}">{{ $elementType->name }}</option>
                    @endforeach
                </x-form.select>

                <x-form.input name="name" label="Nombre" id="edit_component_name" />

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
        function openEditComponentModal(id, elementTypeId, name, isDefault, actionUrl) {
            document.getElementById('editComponentForm').action = actionUrl;
            document.getElementById('edit_component_element_type_id').value = elementTypeId ?? '';
            document.getElementById('edit_component_name').value = name ?? '';
            document.getElementById('edit_component_is_default').value = isDefault;

            const modal = document.getElementById('editComponentModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeEditComponentModal() {
            const modal = document.getElementById('editComponentModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }
    </script>
@endsection