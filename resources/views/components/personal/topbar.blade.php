<header class="sticky top-0 z-30 border-b border-slate-200 bg-white">
    <div class="flex items-center justify-between gap-3 px-4 py-4 md:px-8">
        <div class="flex items-center gap-3">
            <button type="button" class="text-slate-600 lg:hidden" @click="sidebarOpen = true" title="Abrir menú">☰</button>
            <button
                type="button"
                class="hidden items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 lg:inline-flex"
                x-show="sidebarCollapsed" x-cloak
                @click="showSidebar()"
                title="Mostrar menú"
            >
                <i data-lucide="chevron-right" class="mr-2 h-4 w-4"></i> Menú
            </button>
            <h1 class="text-lg font-semibold text-slate-900">@yield('title', 'Personal')</h1>
        </div>

        <div class="flex items-center gap-3">
            @if (\App\Support\PersonalGuard::isSuperadmin())
                <a href="{{ route('admin.dashboard') }}" class="text-xs font-medium text-slate-500 hover:text-slate-800">
                    ← Volver al panel admin
                </a>
            @endif
            <span class="hidden text-sm text-slate-600 sm:inline">{{ \App\Support\PersonalGuard::displayName() }}</span>
            <form method="POST" action="{{ route('personal.logout') }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">
                    <i data-lucide="log-out" class="h-3.5 w-3.5"></i> Salir
                </button>
            </form>
        </div>
    </div>
</header>
