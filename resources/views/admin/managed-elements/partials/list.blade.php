<div id="elementsListContainer">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    @if($showClientColumn)
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
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

                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <div class="flex items-center gap-2">
                            <span>Área</span>
                            <button
                                type="button"
                                onclick="openFilterPopover(event, 'area_ids')"
                                class="rounded p-1 transition hover:bg-slate-200 {{ $hasFilter('area_ids') ? 'text-[#d94d33]' : 'text-slate-400' }}"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                </svg>
                            </button>
                        </div>
                    </th>

                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        Agrupación
                    </th>

                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <div class="flex items-center gap-2">
                            <span>Tipo</span>
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

                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <div class="flex items-center gap-2">
                            <span>Nombre</span>
                            <button
                                type="button"
                                onclick="openFilterPopover(event, 'names')"
                                class="rounded p-1 transition hover:bg-slate-200 {{ $hasFilter('names') ? 'text-[#d94d33]' : 'text-slate-400' }}"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                </svg>
                            </button>
                        </div>
                    </th>

                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <div class="flex items-center gap-2">
                            <span>Almacén</span>
                            <button
                                type="button"
                                onclick="openFilterPopover(event, 'warehouse_codes')"
                                class="rounded p-1 transition hover:bg-slate-200 {{ $hasFilter('warehouse_codes') ? 'text-[#d94d33]' : 'text-slate-400' }}"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                </svg>
                            </button>
                        </div>
                    </th>

                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        Comp.
                    </th>

                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        Uso
                    </th>

                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
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

                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                        Acciones
                    </th>
                </tr>
            </thead>

            <tbody id="elementsTableBody" class="divide-y divide-slate-200 bg-white">
                @forelse($elements as $element)
                    @php
                        $hasDependencies = (($element->components_count ?? 0) + ($element->report_details_count ?? 0)) > 0;
                    @endphp

                    <tr class="hover:bg-slate-50" id="element-row-{{ $element->id }}">
                        @if($showClientColumn)
                            <td class="px-4 py-3 text-sm text-slate-700" id="element-client-{{ $element->id }}">
                                {{ $element->area?->client?->name ?? '—' }}
                            </td>
                        @endif

                        <td class="px-4 py-3 text-sm text-slate-700" id="element-area-{{ $element->id }}">
                            {{ $element->area?->name ?? '—' }}
                        </td>

                        <td class="px-4 py-3 text-sm text-slate-700" id="element-group-{{ $element->id }}">
                            {{ $element->group?->name ?? '—' }}
                        </td>

                        <td class="px-4 py-3 text-sm text-slate-700" id="element-type-{{ $element->id }}">
                            {{ $element->elementType?->name ?? '—' }}
                        </td>

                        <td class="px-4 py-3 text-sm font-medium text-slate-900" id="element-name-{{ $element->id }}">
                            {{ $element->name }}
                        </td>

                        <td class="px-4 py-3 text-sm text-slate-700" id="element-warehouse-code-{{ $element->id }}">
                            {{ $element->warehouse_code ?: '—' }}
                        </td>

                        <td class="px-4 py-3 text-sm text-slate-700" id="element-components-count-{{ $element->id }}">
                            {{ $element->components_count ?? 0 }}
                        </td>

                        <td class="px-4 py-3 text-sm text-slate-700" id="element-report-details-count-{{ $element->id }}">
                            {{ $element->report_details_count ?? 0 }}
                        </td>

                        <td class="px-4 py-3 text-sm">
                            <button
                                type="button"
                                data-status-toggle
                                data-url="{{ route('admin.managed-elements.toggle-status', $element) }}"
                                data-enabled="{{ $element->status ? '1' : '0' }}"
                                onclick="toggleElementStatus(this)"
                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition {{ $element->status ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-red-100 text-red-700 hover:bg-red-200' }}"
                                title="Clic para activar o inactivar"
                            >
                                <i data-lucide="{{ $element->status ? 'check-circle-2' : 'x-circle' }}" class="h-3.5 w-3.5"></i>
                                <span>{{ $element->status ? 'Activo' : 'Inactivo' }}</span>
                            </button>
                        </td>

                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    class="text-blue-500 transition hover:text-blue-700"
                                    title="Asociar componentes"
                                    data-components-element
                                    data-id="{{ $element->id }}"
                                    data-client_id="{{ $element->area?->client_id ?? '' }}"
                                    data-element_type_id="{{ $element->element_type_id ?? '' }}"
                                    data-area_name="{{ $element->area?->name ?? '—' }}"
                                    data-type_name="{{ $element->elementType?->name ?? '—' }}"
                                    data-name="{{ $element->name }}"
                                    data-action="{{ route('admin.managed-elements.components.sync', $element) }}"
                                    data-component_ids='@json($element->components->pluck("id")->map(fn($id) => (string) $id)->toArray())'
                                    onclick="openComponentsModalFromButton(this)"
                                >
                                    <i data-lucide="boxes" class="h-4 w-4"></i>
                                </button>

                                <button
                                    type="button"
                                    data-edit-element
                                    data-id="{{ $element->id }}"
                                    data-name="{{ $element->name }}"
                                    data-code="{{ $element->code }}"
                                    data-warehouse_code="{{ $element->warehouse_code }}"
                                    data-area_id="{{ $element->area_id }}"
                                    data-element_type_id="{{ $element->element_type_id }}"
                                    data-status="{{ $element->status ? 1 : 0 }}"
                                    data-action="{{ route('admin.managed-elements.update', $element) }}"
                                    onclick="openEditElementModalFromButton(this)"
                                    class="text-slate-400 transition hover:text-[#d94d33]"
                                    title="Editar activo"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.651-1.651a2.121 2.121 0 113 3l-1.651 1.651M4 20h4l10.586-10.586a2 2 0 00-2.828-2.828L5.172 17.172A2 2 0 004 18.586V20z" />
                                    </svg>
                                </button>

                                @if(!$hasDependencies)
                                    <button
                                        type="button"
                                        onclick="deleteElement({{ $element->id }})"
                                        class="text-red-500 transition hover:text-red-700"
                                        title="Eliminar activo"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V4h6v3M10 11v6M14 11v6M5 7l1 13a2 2 0 002 2h8a2 2 0 002-2l1-13" />
                                        </svg>
                                    </button>

                                    <form
                                        id="delete-element-form-{{ $element->id }}"
                                        method="POST"
                                        action="{{ route('admin.managed-elements.destroy', $element) }}"
                                        class="hidden"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        @foreach(($activeFilters['client_ids'] ?? []) as $value)
                                            <input type="hidden" name="redirect_client_ids[]" value="{{ $value }}">
                                        @endforeach
                                        @foreach(($activeFilters['area_ids'] ?? []) as $value)
                                            <input type="hidden" name="redirect_area_ids[]" value="{{ $value }}">
                                        @endforeach
                                        @foreach(($activeFilters['element_type_ids'] ?? []) as $value)
                                            <input type="hidden" name="redirect_element_type_ids[]" value="{{ $value }}">
                                        @endforeach
                                        @foreach(($activeFilters['names'] ?? []) as $value)
                                            <input type="hidden" name="redirect_names[]" value="{{ $value }}">
                                        @endforeach
                                        @foreach(($activeFilters['warehouse_codes'] ?? []) as $value)
                                            <input type="hidden" name="redirect_warehouse_codes[]" value="{{ $value }}">
                                        @endforeach
                                        @foreach(($activeFilters['statuses'] ?? []) as $value)
                                            <input type="hidden" name="redirect_statuses[]" value="{{ $value }}">
                                        @endforeach
                                        <input type="hidden" name="redirect_page" value="{{ $elements->currentPage() }}">
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $showClientColumn ? 10 : 9 }}" class="px-4 py-10 text-center text-sm text-slate-500">
                            No hay activos registrados todavía.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="border-t border-slate-200 px-6 py-4">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <p class="text-sm text-slate-600">
                @if($elements->total() > 0)
                    Mostrando {{ $elements->firstItem() }} a {{ $elements->lastItem() }} de {{ $elements->total() }} resultados
                @else
                    Mostrando 0 resultados
                @endif
            </p>

            @if($elements->hasPages())
                <nav class="flex items-center gap-2" aria-label="Paginación de activos">
                    @php
                        $currentPage = $elements->currentPage();
                        $lastPage = $elements->lastPage();
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($lastPage, $currentPage + 2);
                    @endphp

                    <a
                        href="{{ $elements->previousPageUrl() ?: '#' }}"
                        data-pagination-link
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-300 text-slate-600 transition hover:bg-slate-100 {{ $elements->onFirstPage() ? 'pointer-events-none opacity-50' : '' }}"
                        aria-label="Página anterior"
                    >
                        ‹
                    </a>

                    @for($page = $startPage; $page <= $endPage; $page++)
                        <a
                            href="{{ $elements->url($page) }}"
                            data-pagination-link
                            class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl px-3 text-sm font-semibold transition {{ $page === $currentPage ? 'bg-slate-800 text-white' : 'border border-slate-300 text-slate-700 hover:bg-slate-100' }}"
                            aria-current="{{ $page === $currentPage ? 'page' : 'false' }}"
                        >
                            {{ $page }}
                        </a>
                    @endfor

                    <a
                        href="{{ $elements->nextPageUrl() ?: '#' }}"
                        data-pagination-link
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-300 text-slate-600 transition hover:bg-slate-100 {{ $elements->hasMorePages() ? '' : 'pointer-events-none opacity-50' }}"
                        aria-label="Página siguiente"
                    >
                        ›
                    </a>
                </nav>
            @endif
        </div>
    </div>
</div>
