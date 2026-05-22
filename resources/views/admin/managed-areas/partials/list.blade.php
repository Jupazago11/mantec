<div id="areasListContainer">
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
                            <span>Área</span>
                            <button
                                type="button"
                                onclick="openFilterPopover(event, 'area_names')"
                                class="rounded p-1 transition hover:bg-slate-200 {{ $hasFilter('area_names') ? 'text-[#d94d33]' : 'text-slate-400' }}"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                </svg>
                            </button>
                        </div>
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        Código
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        Activos
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

            <tbody id="areasTableBody" class="divide-y divide-slate-200 bg-white">
                @forelse($areas as $area)
                    @php
                        $hasDependencies = ($area->elements_count ?? 0) > 0;
                    @endphp

                    <tr class="hover:bg-slate-50" id="area-row-{{ $area->id }}">
                        @if($showClientColumn)
                            <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700" id="area-client-{{ $area->id }}">
                                {{ $area->client?->name ?? '—' }}
                            </td>
                        @endif

                        <td class="whitespace-nowrap px-5 py-3 text-sm font-medium text-slate-900" id="area-name-{{ $area->id }}">
                            {{ $area->name }}
                        </td>

                        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700" id="area-code-{{ $area->id }}">
                            {{ $area->code ?: '—' }}
                        </td>

                        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700" id="area-elements-count-{{ $area->id }}">
                            {{ $area->elements_count ?? 0 }}
                        </td>

                        <td class="whitespace-nowrap px-5 py-3 text-sm">
                            <button
                                type="button"
                                data-status-toggle
                                data-url="{{ route('admin.managed-areas.toggle-status', $area) }}"
                                data-enabled="{{ $area->status ? '1' : '0' }}"
                                onclick="toggleAreaStatus(this)"
                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition {{ $area->status ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-red-100 text-red-700 hover:bg-red-200' }}"
                                title="Clic para activar o inactivar"
                            >
                                <i data-lucide="{{ $area->status ? 'check-circle-2' : 'x-circle' }}" class="h-3.5 w-3.5"></i>
                                <span>{{ $area->status ? 'Activo' : 'Inactivo' }}</span>
                            </button>
                        </td>

                        <td class="whitespace-nowrap px-5 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    data-edit-area
                                    data-id="{{ $area->id }}"
                                    data-client_id="{{ $area->client_id }}"
                                    data-name="{{ $area->name }}"
                                    data-code="{{ $area->code }}"
                                    data-action="{{ route('admin.managed-areas.update', $area) }}"
                                    onclick="openEditAreaModal(this)"
                                    class="text-slate-400 transition hover:text-[#d94d33]"
                                    title="Editar área"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16.862 4.487l1.651-1.651a2.121 2.121 0 113 3l-1.651 1.651M4 20h4l10.586-10.586a2 2 0 00-2.828-2.828L5.172 17.172A2 2 0 004 18.586V20z" />
                                    </svg>
                                </button>

                                @if(!$hasDependencies)
                                    <button
                                        type="button"
                                        onclick="deleteArea({{ $area->id }})"
                                        class="text-red-500 transition hover:text-red-700"
                                        title="Eliminar área"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M6 7h12M9 7V4h6v3M10 11v6M14 11v6M5 7l1 13a2 2 0 002 2h8a2 2 0 002-2l1-13" />
                                        </svg>
                                    </button>

                                    <form
                                        id="delete-area-form-{{ $area->id }}"
                                        method="POST"
                                        action="{{ route('admin.managed-areas.destroy', $area) }}"
                                        class="hidden"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        @foreach(($activeFilters['client_ids'] ?? []) as $value)
                                            <input type="hidden" name="redirect_client_ids[]" value="{{ $value }}">
                                        @endforeach

                                        @foreach(($activeFilters['area_names'] ?? []) as $value)
                                            <input type="hidden" name="redirect_area_names[]" value="{{ $value }}">
                                        @endforeach

                                        @foreach(($activeFilters['statuses'] ?? []) as $value)
                                            <input type="hidden" name="redirect_statuses[]" value="{{ $value }}">
                                        @endforeach

                                        <input type="hidden" name="redirect_page" value="{{ $areas->currentPage() }}">
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $showClientColumn ? 6 : 5 }}" class="px-5 py-10 text-center text-sm text-slate-500">
                            No hay áreas registradas todavía.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="border-t border-slate-200 px-6 py-4">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <p class="text-sm text-slate-600">
                @if($areas->total() > 0)
                    Mostrando {{ $areas->firstItem() }} a {{ $areas->lastItem() }} de {{ $areas->total() }} resultados
                @else
                    Mostrando 0 resultados
                @endif
            </p>

            @if($areas->hasPages())
                <nav class="flex items-center gap-2" aria-label="Paginación de áreas">
                    @php
                        $currentPage = $areas->currentPage();
                        $lastPage = $areas->lastPage();
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($lastPage, $currentPage + 2);
                    @endphp

                    <a
                        href="{{ $areas->previousPageUrl() ?: '#' }}"
                        data-pagination-link
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-300 text-slate-600 transition hover:bg-slate-100 {{ $areas->onFirstPage() ? 'pointer-events-none opacity-50' : '' }}"
                        aria-label="Página anterior"
                    >
                        ‹
                    </a>

                    @for($page = $startPage; $page <= $endPage; $page++)
                        <a
                            href="{{ $areas->url($page) }}"
                            data-pagination-link
                            class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl px-3 text-sm font-semibold transition {{ $page === $currentPage ? 'bg-slate-800 text-white' : 'border border-slate-300 text-slate-700 hover:bg-slate-100' }}"
                            aria-current="{{ $page === $currentPage ? 'page' : 'false' }}"
                        >
                            {{ $page }}
                        </a>
                    @endfor

                    <a
                        href="{{ $areas->nextPageUrl() ?: '#' }}"
                        data-pagination-link
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-300 text-slate-600 transition hover:bg-slate-100 {{ $areas->hasMorePages() ? '' : 'pointer-events-none opacity-50' }}"
                        aria-label="Página siguiente"
                    >
                        ›
                    </a>
                </nav>
            @endif
        </div>
    </div>
</div>
