@php
    $hasFilter = function ($key) use ($activeFilters) {
        $value = $activeFilters[$key] ?? null;
        if (is_array($value)) {
            return count(array_filter($value, fn ($item) => $item !== null && $item !== '')) > 0;
        }
        return $value !== null && $value !== '';
    };
@endphp

<div id="componentsListContainer">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    @if($showClientColumn)
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <div class="flex items-center gap-2">
                                <span>Cliente</span>
                                <button
                                    type="button"
                                    onclick="openFilterPopover(event, 'client_ids')"
                                    class="rounded p-1 transition hover:bg-slate-200 {{ $hasFilter('client_ids') ? 'text-[#d94d33]' : 'text-slate-400' }}"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                    </svg>
                                </button>
                            </div>
                        </th>
                    @endif

                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <div class="flex items-center gap-2">
                            <span>Tipo de activo</span>
                            <button
                                type="button"
                                onclick="openFilterPopover(event, 'element_type_ids')"
                                class="rounded p-1 transition hover:bg-slate-200 {{ $hasFilter('element_type_ids') ? 'text-[#d94d33]' : 'text-slate-400' }}"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                </svg>
                            </button>
                        </div>
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <div class="flex items-center gap-2">
                            <span>Componente</span>
                            <button
                                type="button"
                                onclick="openFilterPopover(event, 'component_names')"
                                class="rounded p-1 transition hover:bg-slate-200 {{ $hasFilter('component_names') ? 'text-[#d94d33]' : 'text-slate-400' }}"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                </svg>
                            </button>
                        </div>
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        Por defecto
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        Relaciones
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        Uso
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <div class="flex items-center gap-2">
                            <span>Estado</span>
                            <button
                                type="button"
                                onclick="openFilterPopover(event, 'statuses')"
                                class="rounded p-1 transition hover:bg-slate-200 {{ $hasFilter('statuses') ? 'text-[#d94d33]' : 'text-slate-400' }}"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                </svg>
                            </button>
                        </div>
                    </th>

                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                        Acciones
                    </th>
                </tr>
            </thead>

            <tbody id="componentsTableBody" class="divide-y divide-slate-200 bg-white">
                @forelse($components as $component)
                    @php
                        $hasDependencies = (($component->elements_count ?? 0) + ($component->diagnostics_count ?? 0) + ($component->report_details_count ?? 0)) > 0;
                    @endphp

                    <tr class="hover:bg-slate-50" id="component-row-{{ $component->id }}">
                        @if($showClientColumn)
                            <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700" id="component-client-{{ $component->id }}">
                                {{ $component->client?->name ?? '—' }}
                            </td>
                        @endif

                        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700" id="component-element-type-{{ $component->id }}">
                            {{ $component->elementType?->name ?? '—' }}
                        </td>

                        <td class="whitespace-nowrap px-5 py-3 text-sm font-medium text-slate-900" id="component-name-{{ $component->id }}">
                            {{ $component->name }}
                        </td>

                        <td class="whitespace-nowrap px-5 py-3 text-sm" id="component-default-{{ $component->id }}">
                            <button
                                type="button"
                                data-default-toggle
                                data-url="{{ route('admin.managed-components.toggle-default', $component) }}"
                                data-enabled="{{ $component->is_default ? '1' : '0' }}"
                                onclick="toggleComponentDefault(this)"
                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition {{ $component->is_default ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}"
                                title="Clic para marcar o desmarcar por defecto"
                            >
                                <i data-lucide="{{ $component->is_default ? 'check-circle-2' : 'circle' }}" class="h-3.5 w-3.5"></i>
                                <span>{{ $component->is_default ? 'Sí' : 'No' }}</span>
                            </button>
                        </td>

                        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700" id="component-diagnostics-count-{{ $component->id }}">
                            {{ $component->diagnostics_count ?? 0 }}
                        </td>

                        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700" id="component-report-details-count-{{ $component->id }}">
                            {{ $component->report_details_count ?? 0 }}
                        </td>

                        <td class="whitespace-nowrap px-5 py-3 text-sm">
                            <button
                                type="button"
                                data-status-toggle
                                data-url="{{ route('admin.managed-components.toggle-status', $component) }}"
                                data-enabled="{{ $component->status ? '1' : '0' }}"
                                onclick="toggleComponentStatus(this)"
                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition {{ $component->status ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-red-100 text-red-700 hover:bg-red-200' }}"
                                title="{{ $hasDependencies ? 'Clic para activar o inactivar' : 'Este componente puede eliminarse si no tiene uso' }}"
                            >
                                <i data-lucide="{{ $component->status ? 'check-circle-2' : 'x-circle' }}" class="h-3.5 w-3.5"></i>
                                <span>{{ $component->status ? 'Activo' : 'Inactivo' }}</span>
                            </button>
                        </td>

                        <td class="whitespace-nowrap px-5 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    class="text-slate-400 transition hover:text-[#d94d33]"
                                    data-edit-component
                                    data-id="{{ $component->id }}"
                                    data-client_id="{{ $component->client_id }}"
                                    data-element_type_id="{{ $component->element_type_id }}"
                                    data-name="{{ $component->name }}"
                                    data-is_default="{{ $component->is_default ? 1 : 0 }}"
                                    data-action="{{ route('admin.managed-components.update', $component) }}"
                                    onclick="openEditComponentModal(this)"
                                    title="Editar componente"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16.862 4.487l1.651-1.651a2.121 2.121 0 113 3l-1.651 1.651M4 20h4l10.586-10.586a2 2 0 00-2.828-2.828L5.172 17.172A2 2 0 004 18.586V20z" />
                                    </svg>
                                </button>

                                @if(!$hasDependencies)
                                    <button
                                        type="button"
                                        onclick="deleteComponent({{ $component->id }})"
                                        class="text-red-500 transition hover:text-red-700"
                                        title="Eliminar componente"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M6 7h12M9 7V4h6v3M10 11v6M14 11v6M5 7l1 13a2 2 0 002 2h8a2 2 0 002-2l1-13" />
                                        </svg>
                                    </button>

                                    <form
                                        id="delete-component-form-{{ $component->id }}"
                                        method="POST"
                                        action="{{ route('admin.managed-components.destroy', $component) }}"
                                        class="hidden"
                                    >
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $showClientColumn ? 8 : 7 }}" class="px-5 py-10 text-center text-sm text-slate-500">
                            No hay componentes registrados todavía.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="border-t border-slate-200 px-6 py-4">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <p class="text-sm text-slate-600">
                @if($components->total() > 0)
                    Mostrando {{ $components->firstItem() }} a {{ $components->lastItem() }} de {{ $components->total() }} resultados
                @else
                    Mostrando 0 resultados
                @endif
            </p>

            @if($components->hasPages())
                <nav class="flex items-center gap-2" aria-label="Paginación de componentes">
                    @php
                        $currentPage = $components->currentPage();
                        $lastPage = $components->lastPage();
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($lastPage, $currentPage + 2);
                    @endphp

                    <a
                        href="{{ $components->previousPageUrl() ?: '#' }}"
                        data-pagination-link
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-300 text-slate-600 transition hover:bg-slate-100 {{ $components->onFirstPage() ? 'pointer-events-none opacity-50' : '' }}"
                        aria-label="Página anterior"
                    >
                        ‹
                    </a>

                    @for($page = $startPage; $page <= $endPage; $page++)
                        <a
                            href="{{ $components->url($page) }}"
                            data-pagination-link
                            class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl px-3 text-sm font-semibold transition {{ $page === $currentPage ? 'bg-slate-800 text-white' : 'border border-slate-300 text-slate-700 hover:bg-slate-100' }}"
                            aria-current="{{ $page === $currentPage ? 'page' : 'false' }}"
                        >
                            {{ $page }}
                        </a>
                    @endfor

                    <a
                        href="{{ $components->nextPageUrl() ?: '#' }}"
                        data-pagination-link
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-300 text-slate-600 transition hover:bg-slate-100 {{ $components->hasMorePages() ? '' : 'pointer-events-none opacity-50' }}"
                        aria-label="Página siguiente"
                    >
                        ›
                    </a>
                </nav>
            @endif
        </div>
    </div>
</div>
