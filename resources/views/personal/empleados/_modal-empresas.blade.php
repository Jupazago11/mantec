{{-- Modal CRUD Empresas — comparte el x-data del padre
     (personal/empleados/index.blade.php: propiedades empresas/modalEmpresas
     y metodos agregarEmpresa/marcarDefecto/archivarEmpresa/guardarNombreEmpresa). --}}
<div x-show="modalEmpresas" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 p-4" style="top:0;left:0;height:100vh;width:100vw;">
    <div @click.outside="modalEmpresas = false" x-show="modalEmpresas" x-transition class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
        <div class="mb-1 flex items-center justify-between">
            <h2 class="text-lg font-bold text-slate-900">Empresas</h2>
            <button @click="modalEmpresas = false" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100"><i data-lucide="x" class="h-5 w-5"></i></button>
        </div>
        <p class="mb-4 text-xs text-slate-500">
            Catálogo de empresas cliente (sitios donde se presta mano de obra) que alimenta el selector de
            Empresa en la Programación. La marcada "Por defecto" se preselecciona ahí automáticamente.
        </p>

        <label class="mb-3 flex items-center gap-2 text-xs text-slate-500">
            <input type="checkbox" x-model="mostrarEmpresasArchivadas">
            Mostrar empresas archivadas
        </label>

        <div class="space-y-2">
            <template x-for="(empresa, ei) in empresas" :key="empresa.id">
                <div class="flex items-center justify-between gap-2 rounded-xl border border-slate-200 p-3" x-show="!empresa.archivada || mostrarEmpresasArchivadas" :class="empresa.archivada ? 'opacity-50' : ''">
                    <div class="flex min-w-0 items-center gap-2">
                        <template x-if="!empresa.editando">
                            <span class="font-semibold text-slate-800" x-text="empresa.nombre"></span>
                        </template>
                        <template x-if="empresa.editando">
                            <input
                                type="text" x-model="empresa.nombre"
                                @keydown.enter="guardarNombreEmpresa(empresa)" @blur="guardarNombreEmpresa(empresa)"
                                x-init="$nextTick(() => $el.focus())"
                                class="rounded-lg border border-slate-300 px-2 py-1 text-sm font-semibold text-slate-800"
                            >
                        </template>
                        <button type="button" @click="empresa.editando = !empresa.editando" class="shrink-0 text-slate-400 hover:text-[#d55b20]" title="Editar nombre">
                            <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                        </button>
                        <span x-show="empresa.defecto" class="shrink-0 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-800">Por defecto</span>
                        <span x-show="empresa.archivada" class="shrink-0 rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-semibold text-slate-600">Archivada</span>
                    </div>
                    <div class="flex shrink-0 items-center gap-3">
                        <button type="button" @click="marcarDefecto(empresa)" x-show="!empresa.defecto && !empresa.archivada" class="text-xs font-medium text-[#d55b20] hover:underline">Marcar por defecto</button>
                        <button type="button" @click="archivarEmpresa(empresa)" class="text-xs font-medium text-slate-500 hover:text-slate-800" x-text="empresa.archivada ? 'Restablecer' : 'Archivar'"></button>
                    </div>
                </div>
            </template>
            <template x-if="empresas.filter(e => !e.archivada || mostrarEmpresasArchivadas).length === 0">
                <p class="px-1 text-xs text-slate-400">Sin empresas para mostrar.</p>
            </template>
        </div>

        <div class="mt-4 border-t border-slate-200 pt-4">
            <label class="mb-1 block text-xs font-medium text-slate-600">Nueva empresa <span class="text-red-500">*</span></label>
            <div class="flex gap-2">
                <input type="text" x-model="nuevaEmpresaNombre" @keydown.enter="agregarEmpresa()" placeholder="Ej. CEMEX" class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <button type="button" @click="agregarEmpresa()" class="inline-flex items-center gap-2 rounded-xl bg-[#d55b20] px-4 py-2 text-sm font-semibold text-white hover:bg-[#b8481a]">
                    <i data-lucide="plus" class="h-4 w-4"></i> Nueva empresa
                </button>
            </div>
        </div>

        <div class="mt-5 flex justify-end">
            <button @click="modalEmpresas = false" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Cerrar</button>
        </div>
    </div>
</div>
