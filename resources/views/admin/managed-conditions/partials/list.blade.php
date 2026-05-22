@php
    $hasFilter = function ($key) use ($activeFilters) {
        $value = $activeFilters[$key] ?? null;
        if (is_array($value)) {
            return count(array_filter($value, fn ($item) => $item !== null && $item !== '')) > 0;
        }
        return $value !== null && $value !== '';
    };
@endphp

<div id="conditionsListContainer">
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
                            <span>Código</span>
                            <button
                                type="button"
                                onclick="openFilterPopover(event, 'codes')"
                                class="rounded p-1 transition hover:bg-slate-200 {{ $hasFilter('codes') ? 'text-[#d94d33]' : 'text-slate-400' }}"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                </svg>
                            </button>
                        </div>
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
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

                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        Descripción
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        Criticidad
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

            <tbody id="conditionsTableBody" class="divide-y divide-slate-200 bg-white">
                @forelse($conditions as $condition)
                    @php
                        $hasDependencies = ($condition->report_details_count ?? 0) > 0;
                    @endphp

                    <tr class="hover:bg-slate-50" id="condition-row-{{ $condition->id }}">
                        @if($showClientColumn)
                            <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700">
                                {{ $condition->client?->name ?? '—' }}
                            </td>
                        @endif

                        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700" id="condition-element-type-{{ $condition->id }}">
                            {{ $condition->elementType?->name ?? '—' }}
                        </td>

                        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700" id="condition-code-{{ $condition->id }}">
                            {{ $condition->code }}
                        </td>

                        <td class="whitespace-nowrap px-5 py-3 text-sm font-medium text-slate-900">
                            <div class="flex items-center gap-2">
                                <span
                                    class="inline-block h-5 w-5 rounded-full border border-slate-300"
                                    id="condition-color-dot-{{ $condition->id }}"
                                    style="background-color: {{ $condition->color ?? '#ffffff' }};"
                                ></span>
                                <span id="condition-name-{{ $condition->id }}">{{ $condition->name }}</span>
                            </div>
                        </td>

                        <td class="px-5 py-3 text-sm text-slate-600" id="condition-description-{{ $condition->id }}">
                            {{ $condition->description ?: '—' }}
                        </td>

                        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700" id="condition-severity-{{ $condition->id }}">
                            {{ $condition->severity }}
                        </td>

                        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700">
                            {{ $condition->report_details_count }}
                        </td>

                        <td class="whitespace-nowrap px-5 py-3 text-sm">
                            <button
                                type="button"
                                onclick="toggleConditionStatus({{ $condition->id }})"
                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition {{ $condition->status ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-red-100 text-red-700 hover:bg-red-200' }}"
                                id="condition-status-badge-{{ $condition->id }}"
                                title="Cambiar estado"
                            >
                                <i data-lucide="{{ $condition->status ? 'check-circle-2' : 'x-circle' }}" class="h-3.5 w-3.5"></i>
                                <span>{{ $condition->status ? 'Activo' : 'Inactivo' }}</span>
                            </button>
                        </td>

                        <td class="whitespace-nowrap px-5 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-100"
                                    onclick="openComponentConditionModal(
                                        '{{ $condition->id }}',
                                        @js($condition->client?->name ?? '—'),
                                        @js($condition->elementType?->name ?? '—'),
                                        @js($condition->name)
                                    )"
                                >
                                    Componentes
                                </button>

                                <button
                                    type="button"
                                    id="condition-edit-button-{{ $condition->id }}"
                                    class="text-slate-400 transition hover:text-[#d94d33]"
                                    data-code="{{ $condition->code }}"
                                    data-name="{{ $condition->name }}"
                                    data-description="{{ $condition->description }}"
                                    data-severity="{{ $condition->severity }}"
                                    data-color="{{ $condition->color }}"
                                    data-client_id="{{ $condition->client_id }}"
                                    data-element_type_id="{{ $condition->element_type_id }}"
                                    data-action="{{ route('admin.managed-conditions.update', $condition) }}"
                                    onclick="openEditConditionModal(this)"
                                    title="Editar condición"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16.862 4.487l1.651-1.651a2.121 2.121 0 113 3l-1.651 1.651M4 20h4l10.586-10.586a2 2 0 00-2.828-2.828L5.172 17.172A2 2 0 004 18.586V20z" />
                                    </svg>
                                </button>

                                @if(!$hasDependencies)
                                    <button
                                        type="button"
                                        onclick="deleteCondition({{ $condition->id }})"
                                        class="text-red-500 transition hover:text-red-700"
                                        title="Eliminar condición"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M6 7h12M9 7V4h6v3M10 11v6M14 11v6M5 7l1 13a2 2 0 002 2h8a2 2 0 002-2l1-13" />
                                        </svg>
                                    </button>

                                    <form
                                        id="delete-condition-form-{{ $condition->id }}"
                                        method="POST"
                                        action="{{ route('admin.managed-conditions.destroy', $condition) }}"
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
                        <td colspan="{{ $showClientColumn ? 9 : 8 }}" class="px-5 py-10 text-center text-sm text-slate-500">
                            No hay condiciones registradas todavía.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="border-t border-slate-200 px-6 py-4">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <p class="text-sm text-slate-600">
                @if($conditions->total() > 0)
                    Mostrando {{ $conditions->firstItem() }} a {{ $conditions->lastItem() }} de {{ $conditions->total() }} resultados
                @else
                    Mostrando 0 resultados
                @endif
            </p>

            @if($conditions->hasPages())
                <nav class="flex items-center gap-2" aria-label="Paginación de condiciones">
                    @php
                        $currentPage = $conditions->currentPage();
                        $lastPage = $conditions->lastPage();
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($lastPage, $currentPage + 2);
                    @endphp

                    <a
                        href="{{ $conditions->previousPageUrl() ?: '#' }}"
                        data-pagination-link
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-300 text-slate-600 transition hover:bg-slate-100 {{ $conditions->onFirstPage() ? 'pointer-events-none opacity-50' : '' }}"
                        aria-label="Página anterior"
                    >
                        ‹
                    </a>

                    @for($page = $startPage; $page <= $endPage; $page++)
                        <a
                            href="{{ $conditions->url($page) }}"
                            data-pagination-link
                            class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl px-3 text-sm font-semibold transition {{ $page === $currentPage ? 'bg-slate-800 text-white' : 'border border-slate-300 text-slate-700 hover:bg-slate-100' }}"
                            aria-current="{{ $page === $currentPage ? 'page' : 'false' }}"
                        >
                            {{ $page }}
                        </a>
                    @endfor

                    <a
                        href="{{ $conditions->nextPageUrl() ?: '#' }}"
                        data-pagination-link
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-300 text-slate-600 transition hover:bg-slate-100 {{ $conditions->hasMorePages() ? '' : 'pointer-events-none opacity-50' }}"
                        aria-label="Página siguiente"
                    >
                        ›
                    </a>
                </nav>
            @endif
        </div>
    </div>
</div>
