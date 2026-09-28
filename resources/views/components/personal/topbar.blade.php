{{-- Solo movil/tablet (pedido 2026-09-28): en escritorio no hay header —
     el titulo ya esta en cada pantalla, y nombre/"Volver al panel admin"/
     "Salir" viven en el pie del sidebar. Aqui solo queda el boton ☰, sin el
     cual no habria forma de abrir el menu en pantallas pequenas. --}}
<header class="border-b border-slate-200 bg-white lg:hidden">
    <div class="flex items-center gap-3 px-4 py-3">
        <button type="button" class="text-slate-600" @click="sidebarOpen = true" title="Abrir menú">☰</button>
        <span class="text-sm font-semibold text-slate-900">@yield('title', 'Personal')</span>
    </div>
</header>
