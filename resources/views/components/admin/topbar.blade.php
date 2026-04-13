<header class="sticky top-0 z-40 border-b border-slate-200 bg-white">
    <div class="flex items-center justify-between px-6 py-4 md:px-8">
        <div class="flex items-center gap-3">
            <button
                type="button"
                class="text-slate-600 lg:hidden"
                @click="sidebarOpen = true"
                title="Abrir menú"
            >
                ☰
            </button>

            <button
                type="button"
                class="hidden rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 lg:inline-flex"
                x-show="sidebarCollapsed"
                x-cloak
                @click="showSidebar()"
                title="Mostrar menú"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
                Menú
            </button>

            <div>
                <p class="text-sm text-slate-500">Sistema de Gestión de Inspecciones</p>
                <h1 class="text-lg font-semibold text-slate-900">
                    @yield('header_title', 'Dashboard')
                </h1>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <div class="text-right">
                <p class="text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                <p class="text-xs uppercase tracking-wide text-slate-500">
                    {{ auth()->user()->role->name ?? 'Sin rol' }}
                </p>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button
                    type="submit"
                    class="rounded-xl bg-[#d94d33] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                >
                    Cerrar sesión
                </button>
            </form>
        </div>
    </div>
</header>
