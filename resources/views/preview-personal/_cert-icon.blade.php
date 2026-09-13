@php
    // $estado: 'vigente' | 'vencido' | 'no_posee'
    // $label: nombre del certificado (ej. "Alturas")
    // $detalle: texto del tooltip (ej. "Vigente hasta 12/2026" / "Vencido desde 01/09/2026")
    $icon = match ($estado) {
        'vigente' => 'shield-check',
        'vencido' => 'shield-alert',
        default => 'shield-off',
    };
    $classes = match ($estado) {
        'vigente' => 'text-emerald-600',
        'vencido' => 'text-amber-500 animate-pulse',
        default => 'text-slate-300',
    };
@endphp
<span x-data="{ tip: false, estilo: '' }" class="relative inline-flex">
    <button
        type="button"
        @mouseenter="tip = true; estilo = posicionarPopover($el, 192, 70)" @mouseleave="tip = false" @click="tip = !tip"
        class="inline-flex h-7 w-7 items-center justify-center rounded-lg hover:bg-slate-100"
    >
        <i data-lucide="{{ $icon }}" class="h-4 w-4 {{ $classes }}"></i>
    </button>
    <template x-teleport="body">
        <div
            x-show="tip" x-cloak x-transition :style="estilo"
            class="w-max max-w-[12rem] rounded-lg bg-slate-900 px-2.5 py-1.5 text-center text-xs text-white shadow-lg"
        >
            <strong class="block font-semibold">{{ $label }}</strong>
            {{ $detalle }}
        </div>
    </template>
</span>
