@php
    $isRoute = fn (...$patterns) => request()->routeIs(...$patterns);

    $dashboardActive = $isRoute('admin.system-modules.measurements.index');
    $levelOneActive = $isRoute('admin.system-modules.measurements.level-one');
    $showActive = $isRoute('admin.system-modules.measurements.show');

    $itemClass = function (bool $active) {
        return $active
            ? 'flex items-center gap-3 rounded-xl bg-[#d94d33]/10 px-3 py-2 text-sm font-semibold text-[#d94d33] transition'
            : 'flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900';
    };

    $iconClass = function (bool $active) {
        return $active ? 'h-5 w-5 text-[#d94d33]' : 'h-5 w-5 text-slate-400';
    };
@endphp

<aside
    class="fixed inset-y-0 left-0 z-50 border-r border-slate-200 bg-white transition-all duration-300 lg:static lg:translate-x-0"
    :class="[
        sidebarOpen ? 'translate-x-0 w-72' : '-translate-x-full w-72 lg:translate-x-0',
        sidebarCollapsed ? 'lg:w-0 lg:min-w-0 lg:overflow-hidden lg:border-r-0' : 'lg:w-72'
    ]"
>
    <div class="flex h-full flex-col">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
            <div>
                <a href="{{ route('admin.system-modules.measurements.index') }}" class="text-2xl font-extrabold tracking-tight text-slate-900">
                    Mediciones
                </a>

                <p class="mt-1 text-sm text-slate-500">
                    Módulo independiente
                </p>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="hidden text-slate-500 lg:inline-flex"
                    @click="toggleSidebarCollapse()"
                    title="Ocultar menú"
                >
                    <i data-lucide="chevron-left" class="h-5 w-5"></i>
                </button>

                <button
                    type="button"
                    class="text-slate-500 lg:hidden"
                    @click="sidebarOpen = false"
                >
                    ✕
                </button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto px-4 py-6">
            <p class="px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">
                Navegación
            </p>

            <nav class="mt-4 space-y-1">
                <a href="{{ route('admin.system-modules.measurements.index') }}" class="{{ $itemClass($dashboardActive) }}">
                    <i data-lucide="layout-dashboard" class="{{ $iconClass($dashboardActive) }}"></i>
                    <span>Inicio</span>
                </a>

                <a href="{{ route('admin.system-modules.measurements.level-one') }}" class="{{ $itemClass($levelOneActive || $showActive) }}">
                    <i data-lucide="folders" class="{{ $iconClass($levelOneActive || $showActive) }}"></i>
                    <span>Nivel 1</span>
                </a>
            </nav>

            <div class="mt-8 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Estado
                </p>
                <p class="mt-2 text-sm text-slate-700">
                    Aquí iremos incorporando la navegación interna del módulo.
                </p>
            </div>

            <div class="mt-4">
                <a
                    href="{{ route('admin.dashboard') }}"
                    class="inline-flex w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                >
                    Volver al panel
                </a>
            </div>
        </div>
    </div>
</aside>