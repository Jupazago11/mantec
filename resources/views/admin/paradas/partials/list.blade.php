<div id="paradasListContainer">
    @if($paradas->isEmpty())
        <div class="px-5 py-10 text-center text-sm text-slate-500">
            No hay paradas registradas para este cliente.
        </div>
    @else
        <div class="divide-y divide-slate-100">
            @foreach($paradas as $parada)
                @php
                    $isActive  = $parada->isActive();
                    $areaNames = $parada->areas->pluck('name')->join(', ');
                @endphp
                <div id="parada-row-{{ $parada->id }}" class="px-5 py-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span id="parada-name-{{ $parada->id }}" class="font-semibold text-slate-900 text-sm">
                                    {{ $parada->name }}
                                </span>
                                @if($isActive)
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                                        Activa
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-500">
                                        {{ $parada->end_date->toDateString() < now()->toDateString() ? 'Finalizada' : 'Próxima' }}
                                    </span>
                                @endif

                                {{-- Badge de cliente cuando se muestran paradas de múltiples clientes --}}
                                @if(!empty($showClientColumn) && $parada->relationLoaded('client') && $parada->client)
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs text-slate-500">
                                        {{ $parada->client->name }}
                                    </span>
                                @endif
                            </div>

                            <div class="mt-1 flex items-center gap-1.5 text-xs text-slate-500">
                                <i data-lucide="calendar" class="h-3.5 w-3.5 shrink-0"></i>
                                <span id="parada-dates-{{ $parada->id }}">
                                    {{ $parada->start_date->format('d/m/Y') }} → {{ $parada->end_date->format('d/m/Y') }}
                                </span>
                            </div>

                            @if($areaNames)
                                <div class="mt-1.5 flex items-start gap-1.5 text-xs text-slate-500">
                                    <i data-lucide="map-pin" class="h-3.5 w-3.5 shrink-0 mt-0.5"></i>
                                    <span id="parada-areas-{{ $parada->id }}">{{ $areaNames }}</span>
                                </div>
                            @endif
                        </div>

                        @if(!empty($canManage))
                            <div class="flex items-center gap-2 shrink-0">
                                <button
                                    type="button"
                                    class="text-slate-400 hover:text-[#d94d33] transition"
                                    title="Editar parada"
                                    onclick="openEditParadaModal({{ $parada->id }}, @js($parada->name), '{{ $parada->start_date->format('Y-m-d') }}', '{{ $parada->end_date->format('Y-m-d') }}', @js($parada->areas->pluck('id')->all()), '{{ route('admin.paradas.update', $parada) }}', {{ $parada->client_id }})"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.651-1.651a2.121 2.121 0 113 3l-1.651 1.651M4 20h4l10.586-10.586a2 2 0 00-2.828-2.828L5.172 17.172A2 2 0 004 18.586V20z" />
                                    </svg>
                                </button>

                                <button
                                    type="button"
                                    class="text-red-400 hover:text-red-600 transition"
                                    title="Eliminar parada"
                                    onclick="deleteParada({{ $parada->id }}, '{{ route('admin.paradas.destroy', $parada) }}')"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V4h6v3M10 11v6M14 11v6M5 7l1 13a2 2 0 002 2h8a2 2 0 002-2l1-13" />
                                    </svg>
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
