{{--
    Encabezado de columna filtrable, mismo patron visual que el filtro de
    columna de /admin/preventive-reports (checklist buscable con
    Seleccionar/Deseleccionar todo + Aplicar/Limpiar) pero implementado en
    cliente: aqui los datos ya estan completos en memoria (empleados), no
    hay ida y vuelta al servidor por cada filtro.

    Parametros:
    - $field: clave usada en columnFilters y en columnValue() (personalEmpleadosPage)
    - $label: texto visible del encabezado
    - $align: 'left' (default) o 'center', para alinear el boton igual que el resto de la fila
    - $minWidth: opcional, ej. '14rem'
--}}
@php($align = $align ?? 'left')
<th
    class="px-4 py-2 {{ $align === 'center' ? 'text-center' : 'text-left' }} text-[11px] font-semibold uppercase tracking-wider text-slate-500"
    @if (! empty($minWidth)) style="min-width:{{ $minWidth }}" @endif
>
    <div
        class="relative inline-flex items-center gap-1"
        x-data="{
            open: false, estilo: '', busqueda: '', seleccion: [],
            valoresFiltrados() {
                const todos = distinctValuesFor('{{ $field }}');
                if (!this.busqueda) return todos;
                const q = this.busqueda.toLowerCase();
                return todos.filter((v) => String(v).toLowerCase().includes(q));
            },
            toggleValor(val) {
                const idx = this.seleccion.indexOf(val);
                if (idx === -1) { this.seleccion.push(val); } else { this.seleccion.splice(idx, 1); }
            },
            seleccionarTodo() {
                this.valoresFiltrados().forEach((v) => { if (!this.seleccion.includes(v)) this.seleccion.push(v); });
            },
            deseleccionarTodo() {
                const visibles = this.valoresFiltrados();
                this.seleccion = this.seleccion.filter((v) => !visibles.includes(v));
            },
        }"
    >
        <button
            type="button"
            x-ref="trigger"
            @click="open = !open; if (open) { estilo = posicionarPopover($el, 240, 300); seleccion = [...columnFilters['{{ $field }}']]; busqueda = ''; }"
            class="inline-flex items-center gap-1 text-[11px] font-semibold uppercase tracking-wider text-slate-500 hover:text-slate-700"
        >
            {{ $label }}
            <i data-lucide="filter" class="h-3 w-3" :class="columnFilters['{{ $field }}'].length > 0 ? 'text-[#d55b20]' : 'text-slate-300'"></i>
        </button>

        <template x-teleport="body">
            <div
                x-show="open" x-cloak x-transition
                @click.outside="if (!$refs.trigger.contains($event.target)) { open = false; }"
                @click.stop
                :style="estilo"
                class="rounded-xl border border-slate-200 bg-white p-3 normal-case text-left text-xs font-normal tracking-normal text-slate-700 shadow-xl"
            >
                <input
                    type="text"
                    x-model="busqueda"
                    placeholder="Buscar..."
                    class="mb-2 w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs"
                >

                <div class="mb-2 flex items-center justify-between text-[11px] font-medium">
                    <button type="button" @click="seleccionarTodo()" class="text-[#d55b20] hover:underline">Seleccionar todo</button>
                    <button type="button" @click="deseleccionarTodo()" class="text-slate-500 hover:underline">Deseleccionar todo</button>
                </div>

                <div class="max-h-48 space-y-0.5 overflow-y-auto border-t border-slate-100 pt-2">
                    <template x-for="val in valoresFiltrados()" :key="val">
                        <label class="flex items-center gap-2 rounded px-1.5 py-1 hover:bg-slate-50">
                            <input type="checkbox" :checked="seleccion.includes(val)" @change="toggleValor(val)">
                            <span x-text="val" class="truncate"></span>
                        </label>
                    </template>
                    <p x-show="valoresFiltrados().length === 0" class="px-1.5 py-2 text-center text-slate-400">Sin resultados.</p>
                </div>

                <div class="mt-3 flex items-center justify-between gap-2 border-t border-slate-100 pt-2">
                    <button type="button" @click="seleccion = []; columnFilters['{{ $field }}'] = []; paginaActual = 1; open = false;" class="text-[11px] font-medium text-slate-500 hover:text-slate-700">Limpiar</button>
                    <button type="button" @click="columnFilters['{{ $field }}'] = [...seleccion]; paginaActual = 1; open = false;" class="rounded-lg bg-[#d55b20] px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-[#b8481a]">Aplicar</button>
                </div>
            </div>
        </template>
    </div>
</th>
