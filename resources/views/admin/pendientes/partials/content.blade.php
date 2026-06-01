{{-- ══════════════════════════════════════════════════════
     NIVEL 1 — TARJETAS DE CLIENTES
═══════════════════════════════════════════════════════ --}}
@if(!$clientId && $clients->count() > 1)

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach($clients as $client)
        <a href="{{ route('admin.pendientes.index', ['client_id' => $client->id]) }}"
           data-nav-link
           class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-orange-300 hover:shadow-md">

            <div class="absolute inset-0 bg-gradient-to-br from-orange-50/60 via-white to-slate-50 opacity-0 transition group-hover:opacity-100"></div>

            <div class="relative space-y-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 transition group-hover:bg-orange-100">
                    <i data-lucide="factory" class="h-5 w-5 text-slate-500 transition group-hover:text-[#d94d33]"></i>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Cliente</p>
                    <h3 class="mt-1 text-lg font-bold text-slate-900 transition group-hover:text-[#d94d33]">{{ $client->name }}</h3>
                </div>
                <div class="flex items-center justify-between">
                    @if($client->paradas_count > 0)
                        <span class="text-xs text-slate-500">
                            {{ $client->paradas_count }} {{ $client->paradas_count === 1 ? 'parada' : 'paradas' }}
                        </span>
                    @else
                        <span class="text-xs text-slate-400">Sin paradas</span>
                    @endif
                    <span class="text-slate-300 transition group-hover:text-[#d94d33]">→</span>
                </div>
            </div>
        </a>
        @endforeach
    </div>

{{-- ══════════════════════════════════════════════════════
     CLIENTE SELECCIONADO
═══════════════════════════════════════════════════════ --}}
@elseif($clientId)

    {{-- Breadcrumb --}}
    @if($clients->count() > 1)
    @php $currentClient = $clients->firstWhere('id', $clientId); @endphp
    <div class="flex items-center gap-2 text-sm">
        <a href="{{ route('admin.pendientes.index') }}"
           data-nav-link
           class="flex items-center gap-1.5 font-medium text-slate-500 transition hover:text-[#d94d33]">
            <i data-lucide="chevron-left" class="h-4 w-4"></i>
            Clientes
        </a>
        <span class="text-slate-300">/</span>
        <span class="font-semibold text-slate-800">{{ $currentClient?->name }}</span>
    </div>
    @endif

    @if($paradas->isEmpty())
        <div class="rounded-2xl border border-slate-200 bg-white px-6 py-12 text-center shadow-sm">
            <i data-lucide="calendar-x-2" class="mx-auto h-10 w-10 text-slate-300"></i>
            <p class="mt-3 text-sm text-slate-500">Este cliente no tiene paradas registradas.</p>
        </div>

    @else

        @php
            // Siempre disponible, independiente del número de paradas
            $today        = now()->toDateString();
            $treeSummary  = null;
            if ($selectedParada && !empty($tree)) {
                $treeCol     = collect($tree);
                $treeSummary = [
                    'totalAreas' => count($tree),
                    'doneAreas'  => $treeCol->where('revisado', true)->count(),
                    'totalEls'   => $treeCol->sum('total'),
                    'doneEls'    => $treeCol->sum('done'),
                ];
                $treeSummary['pct'] = $treeSummary['totalEls'] > 0
                    ? (int) round(($treeSummary['doneEls'] / $treeSummary['totalEls']) * 100)
                    : 0;
            }
        @endphp

        {{-- ══════════════════════════════════════════════════════
             NIVEL 2 — TARJETAS + HISTORIAL
        ═══════════════════════════════════════════════════════ --}}
        @if($paradas->count() > 1)
        @php
            $currentParadas  = $paradas->filter(fn($p) => $p->end_date->toDateString() >= $today);
            $finishedParadas = $paradas->filter(fn($p) => $p->end_date->toDateString() < $today);
        @endphp

        {{-- Tarjetas activas / próximas --}}
        @if($currentParadas->isNotEmpty())
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($currentParadas as $parada)
                @php
                    $isSelected = $selectedParada?->id === $parada->id;
                    $isActive   = $parada->isActive();
                    $isFuture   = $parada->start_date->toDateString() > $today;
                @endphp
                <a href="{{ route('admin.pendientes.index', ['client_id' => $clientId, 'parada_id' => $parada->id]) }}"
                   data-nav-link
                   class="group relative flex flex-col gap-1.5 overflow-hidden rounded-xl border px-4 py-3 shadow-sm transition
                          {{ $isSelected ? 'border-[#d94d33] bg-orange-50 shadow-md' : 'border-slate-200 bg-white hover:border-orange-300 hover:shadow-md' }}">

                    {{-- Fila 1: badge + check --}}
                    <div class="flex items-center justify-between gap-2">
                        @if($isActive)
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>Activa
                            </span>
                        @elseif($isFuture)
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">Próxima</span>
                        @else
                            <span></span>
                        @endif

                        @if($isSelected)
                            <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-[#d94d33]">
                                <i data-lucide="check" class="h-2.5 w-2.5 text-white"></i>
                            </span>
                        @endif
                    </div>

                    {{-- Fila 2: nombre --}}
                    <p class="truncate text-sm font-bold transition
                               {{ $isSelected ? 'text-[#d94d33]' : 'text-slate-900 group-hover:text-[#d94d33]' }}">
                        {{ $parada->name }}
                    </p>

                    {{-- Fila 3: fechas + áreas --}}
                    <p class="text-xs text-slate-400">
                        {{ $parada->start_date->format('d/m/Y') }} → {{ $parada->end_date->format('d/m/Y') }}
                        @if($parada->areas_count)
                            · {{ $parada->areas_count }} {{ $parada->areas_count === 1 ? 'área' : 'áreas' }}
                        @endif
                    </p>

                    {{-- Fila 4: stats cuando está seleccionada --}}
                    @if($isSelected && $treeSummary)
                    <div class="mt-1 border-t border-orange-200 pt-2 space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500">
                                {{ $treeSummary['doneEls'] }}/{{ $treeSummary['totalEls'] }} activos
                                · {{ $treeSummary['doneAreas'] }}/{{ $treeSummary['totalAreas'] }} áreas
                            </span>
                            <span class="font-semibold {{ $treeSummary['pct'] === 100 ? 'text-emerald-600' : 'text-[#d94d33]' }}">
                                {{ $treeSummary['pct'] }}%
                            </span>
                        </div>
                        <div class="h-1 w-full overflow-hidden rounded-full bg-orange-200">
                            <div class="h-full rounded-full {{ $treeSummary['pct'] === 100 ? 'bg-emerald-500' : 'bg-[#d94d33]' }}"
                                 style="width: {{ $treeSummary['pct'] }}%"></div>
                        </div>
                    </div>
                    @endif
                </a>
            @endforeach
        </div>
        @endif

        {{-- Historial de paradas finalizadas --}}
        @if($finishedParadas->isNotEmpty())
        <details class="group/hist rounded-2xl border border-slate-200 bg-white shadow-sm">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-3.5
                           [&::-webkit-details-marker]:hidden hover:bg-slate-50 transition rounded-2xl">
                <div class="flex items-center gap-2.5">
                    <i data-lucide="history" class="h-4 w-4 text-slate-400"></i>
                    <span class="text-sm font-semibold text-slate-700">Historial de paradas finalizadas</span>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">
                        {{ $finishedParadas->count() }}
                    </span>
                </div>
                <i data-lucide="chevron-down" class="h-4 w-4 text-slate-400 transition-transform duration-200 group-open/hist:rotate-180"></i>
            </summary>

            <div class="border-t border-slate-100">
                @foreach($finishedParadas as $parada)
                    @php
                        $isSelected = $selectedParada?->id === $parada->id;
                        $prog       = $paradasProgress[$parada->id] ?? null;
                    @endphp
                    <a href="{{ route('admin.pendientes.index', ['client_id' => $clientId, 'parada_id' => $parada->id]) }}"
                       data-nav-link
                       class="group/row flex items-center gap-4 border-b border-slate-100 px-5 py-3 last:border-0 transition
                              {{ $isSelected ? 'bg-orange-50' : 'hover:bg-slate-50' }}">

                        {{-- Indicador seleccionado --}}
                        <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full
                                    {{ $isSelected ? 'bg-[#d94d33]' : 'bg-slate-100 group-hover/row:bg-slate-200' }}">
                            @if($isSelected)
                                <i data-lucide="check" class="h-3 w-3 text-white"></i>
                            @else
                                <i data-lucide="clock" class="h-3 w-3 text-slate-400"></i>
                            @endif
                        </div>

                        {{-- Nombre y fechas --}}
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold {{ $isSelected ? 'text-[#d94d33]' : 'text-slate-800 group-hover/row:text-[#d94d33]' }} transition">
                                {{ $parada->name }}
                            </p>
                            <p class="text-xs text-slate-400">
                                {{ $parada->start_date->format('d/m/Y') }} → {{ $parada->end_date->format('d/m/Y') }}
                            </p>
                        </div>

                        {{-- Progreso --}}
                        @if($prog && $prog['total'] > 0)
                        <div class="hidden w-32 space-y-1 sm:block">
                            <div class="flex justify-between text-xs text-slate-400">
                                <span>{{ $prog['done'] }}/{{ $prog['total'] }}</span>
                                <span class="{{ $prog['pct'] === 100 ? 'text-emerald-600' : 'text-slate-500' }} font-medium">{{ $prog['pct'] }}%</span>
                            </div>
                            <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full {{ $prog['pct'] === 100 ? 'bg-emerald-400' : 'bg-slate-300' }}"
                                     style="width: {{ $prog['pct'] }}%"></div>
                            </div>
                        </div>
                        @endif

                        <i data-lucide="chevron-right" class="h-4 w-4 shrink-0 text-slate-300 transition group-hover/row:text-slate-400"></i>
                    </a>
                @endforeach
            </div>
        </details>
        @endif

        @endif

        {{-- Señal para auto-refresco cuando la parada está activa --}}
        @if($selectedParada?->isActive())
            <div data-parada-activa hidden></div>
        @endif

        {{-- ══════════════════════════════════════════════════════
             NIVEL 3 — ÁRBOL DE PENDIENTES
        ═══════════════════════════════════════════════════════ --}}
        @if(!$selectedParada)
            <div class="rounded-2xl border border-slate-200 bg-white px-6 py-12 text-center shadow-sm">
                <i data-lucide="mouse-pointer-click" class="mx-auto h-10 w-10 text-slate-300"></i>
                <p class="mt-3 text-sm text-slate-500">Selecciona una parada para ver el estado de sus pendientes.</p>
            </div>

        @elseif(empty($tree))
            <div class="rounded-2xl border border-slate-200 bg-white px-6 py-12 text-center shadow-sm">
                <i data-lucide="map-pin-off" class="mx-auto h-10 w-10 text-slate-300"></i>
                <p class="mt-3 text-sm text-slate-500">Esta parada no tiene áreas o activos asociados.</p>
            </div>

        @else
            {{-- Header mínimo solo cuando no hay grid de tarjetas (1 sola parada) --}}
            @if($paradas->count() === 1 && $treeSummary)
            <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-5 py-3.5 shadow-sm">
                <div>
                    <span class="font-semibold text-slate-900">{{ $selectedParada->name }}</span>
                    <span class="ml-2 text-sm text-slate-400">{{ $selectedParada->start_date->format('d/m/Y') }} → {{ $selectedParada->end_date->format('d/m/Y') }}</span>
                </div>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <div class="w-24 h-1.5 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full {{ $treeSummary['pct'] === 100 ? 'bg-emerald-500' : 'bg-[#d94d33]' }}"
                                 style="width: {{ $treeSummary['pct'] }}%"></div>
                        </div>
                        <span class="text-sm font-semibold {{ $treeSummary['pct'] === 100 ? 'text-emerald-600' : 'text-[#d94d33]' }}">
                            {{ $treeSummary['pct'] }}%
                        </span>
                    </div>
                    <span class="text-xs text-slate-500">
                        {{ $treeSummary['doneEls'] }}/{{ $treeSummary['totalEls'] }} activos
                        · {{ $treeSummary['doneAreas'] }}/{{ $treeSummary['totalAreas'] }} áreas
                    </span>
                </div>
            </div>
            @endif

            {{-- ÁRBOL --}}
            <div class="space-y-3">
                @foreach($tree as $area)
                <details class="group/area overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 transition hover:bg-slate-50
                                   [&::-webkit-details-marker]:hidden">
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl {{ $area['revisado'] ? 'bg-emerald-100' : 'bg-amber-100' }}">
                                <i data-lucide="{{ $area['revisado'] ? 'check' : 'clock' }}"
                                   class="h-4 w-4 {{ $area['revisado'] ? 'text-emerald-600' : 'text-amber-600' }}"></i>
                            </div>
                            <div>
                                <span class="font-semibold text-slate-900">{{ $area['name'] }}</span>
                                @if($area['code'])
                                    <span class="ml-1.5 text-xs text-slate-400">({{ $area['code'] }})</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-4 shrink-0">
                            <span class="text-sm {{ $area['done'] === $area['total'] ? 'text-emerald-600 font-semibold' : 'text-amber-600 font-semibold' }}">
                                {{ $area['done'] }}/{{ $area['total'] }} activos
                            </span>
                            <i data-lucide="chevron-down" class="h-4 w-4 text-slate-400 transition-transform duration-200 group-open/area:rotate-180"></i>
                        </div>
                    </summary>

                    <div class="border-t border-slate-100">
                        @foreach($area['elements'] as $element)
                        <details class="group/el border-b border-slate-100 last:border-0">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-3 pl-14 transition hover:bg-slate-50
                                           [&::-webkit-details-marker]:hidden">
                                <div class="flex items-center gap-2">
                                    <span class="inline-block h-2 w-2 rounded-full shrink-0 {{ $element['revisado'] ? 'bg-emerald-400' : 'bg-amber-400' }}"></span>
                                    <span class="text-sm font-medium text-slate-800">{{ $element['name'] }}</span>
                                    @if($element['type'])
                                        <span class="text-xs text-slate-400">· {{ $element['type'] }}</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-3 shrink-0">
                                    <span class="text-xs {{ $element['done'] === $element['total'] ? 'text-emerald-600' : 'text-amber-600' }}">
                                        {{ $element['done'] }}/{{ $element['total'] }} componentes
                                    </span>
                                    <i data-lucide="chevron-right" class="h-3.5 w-3.5 text-slate-300 transition-transform duration-200 group-open/el:rotate-90"></i>
                                </div>
                            </summary>

                            <div class="bg-slate-50 px-5 py-2 pl-16 space-y-0.5">
                                @foreach($element['components'] as $component)
                                <details class="group/comp">
                                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 py-2
                                                   [&::-webkit-details-marker]:hidden">
                                        <div class="flex items-center gap-2">
                                            @if($component['revisado'])
                                                <i data-lucide="check-circle-2" class="h-4 w-4 shrink-0 text-emerald-500"></i>
                                            @else
                                                <i data-lucide="circle" class="h-4 w-4 shrink-0 text-amber-400"></i>
                                            @endif
                                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-600">{{ $component['name'] }}</span>
                                        </div>
                                        <i data-lucide="chevron-right" class="h-3 w-3 text-slate-300 transition-transform duration-200 group-open/comp:rotate-90"></i>
                                    </summary>

                                    <div class="pb-2 pl-6 space-y-1">
                                        @foreach($component['diagnostics'] as $diagnostic)
                                        <div class="flex items-center gap-2 py-0.5">
                                            @if($diagnostic['revisado'])
                                                <i data-lucide="check" class="h-3.5 w-3.5 shrink-0 text-emerald-500"></i>
                                                <a href="{{ route('admin.preventive-reports.evidence', $diagnostic['report_detail_id']) }}"
                                                   target="_blank"
                                                   class="group/diag flex items-center gap-1.5 text-xs text-slate-500 transition hover:text-[#d94d33]">
                                                    {{ $diagnostic['name'] }}
                                                    <i data-lucide="external-link" class="h-3 w-3 opacity-0 transition group-hover/diag:opacity-100"></i>
                                                </a>
                                            @else
                                                <i data-lucide="x" class="h-3.5 w-3.5 shrink-0 text-red-400"></i>
                                                <span class="text-xs font-medium text-red-700">{{ $diagnostic['name'] }}</span>
                                            @endif
                                        </div>
                                        @endforeach
                                    </div>
                                </details>
                                @endforeach
                            </div>
                        </details>
                        @endforeach
                    </div>
                </details>
                @endforeach
            </div>
        @endif

    @endif

@else
    <div class="rounded-2xl border border-slate-200 bg-white px-6 py-12 text-center shadow-sm">
        <i data-lucide="clipboard-list" class="mx-auto h-10 w-10 text-slate-300"></i>
        <p class="mt-3 text-sm text-slate-500">No hay información disponible.</p>
    </div>
@endif
