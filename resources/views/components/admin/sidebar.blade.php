@php
    $user = auth()->user();
    $role = $user->role->key ?? null;

    $isPowerAdmin = in_array($role, ['superadmin', 'admin_global'], true);
    $isOperationalAdmin = in_array($role, ['superadmin', 'admin_global', 'admin'], true);
    $isObserver = in_array($role, ['observador', 'observador_cliente'], true);

    $isRoute = fn (...$patterns) => request()->routeIs(...$patterns);

    $dashboardActive = $isRoute('admin.dashboard');
    $clientsActive = $isRoute('admin.clients.*');
    $usersActive = $isRoute('admin.managed-users.*');
    $areasActive = $isRoute('admin.managed-areas.*');
    $elementsActive = $isRoute('admin.managed-elements.*');
    $groupsActive = $isRoute('admin.managed-groups.*');
    $elementTypesActive = $isRoute('admin.managed-element-types.*');
    $componentsActive = $isRoute('admin.managed-components.*');
    $diagnosticsActive = $isRoute('admin.managed-diagnostics.*');
    $componentDiagnosticsActive = $isRoute('admin.managed-component-diagnostics.*');
    $conditionsActive = $isRoute('admin.managed-conditions.*');
    $inspectorReportsActive = $isRoute('inspector.reports.*');

    $itemClass = function (bool $active) {
        return $active
            ? 'flex items-center gap-3 rounded-xl bg-[#d55b20]/10 px-3 py-2 text-sm font-semibold text-[#d55b20] transition'
            : 'flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900';
    };

    $iconClass = function (bool $active) {
        return $active ? 'h-5 w-5 text-[#d55b20]' : 'h-5 w-5 text-slate-400';
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
                @if($role === 'inspector')
                    <a href="{{ route('inspector.reports.index') }}" class="text-2xl font-extrabold tracking-tight text-slate-900">
                        ManTec
                    </a>
                @else
                    <a href="{{ route('admin.dashboard') }}" class="text-2xl font-extrabold tracking-tight text-slate-900">
                        ManTec
                    </a>
                @endif

                <p class="mt-1 text-sm text-slate-500">
                    {{ $role === 'inspector' ? 'Panel inspector' : 'Panel administrativo' }}
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
                Menú
            </p>

            <nav class="mt-4 space-y-1">
                {{-- SUPERADMIN / ADMIN_GLOBAL / ADMIN --}}
                @if($isOperationalAdmin)
                    <a href="{{ route('admin.dashboard') }}" class="{{ $itemClass($dashboardActive) }}">
                        <i data-lucide="layout-dashboard" class="{{ $iconClass($dashboardActive) }}"></i>
                        <span>Dashboard</span>
                    </a>

                    {{-- Solo superadmin y admin_global --}}
                    @if($isPowerAdmin)
                        <div class="mt-4 space-y-1">
                            <p class="px-3 pt-2 pb-1 text-xs font-semibold uppercase tracking-wider text-slate-400">
                                Global
                            </p>

                            <a href="{{ route('admin.clients.index') }}" class="{{ $itemClass($clientsActive) }}">
                                <i data-lucide="factory" class="{{ $iconClass($clientsActive) }}"></i>
                                <span>Clientes</span>
                            </a>

                            <a href="{{ route('admin.managed-users.index') }}" class="{{ $itemClass($usersActive) }}">
                                <i data-lucide="users" class="{{ $iconClass($usersActive) }}"></i>
                                <span>Usuarios</span>
                            </a>
                        </div>
                    @endif

                    <div class="mt-4 space-y-1">
                        <p class="px-3 pt-2 pb-1 text-xs font-semibold uppercase tracking-wider text-slate-400">
                            Operación
                        </p>

                        @if($role === 'admin')
                            <a href="{{ route('admin.managed-users.index') }}" class="{{ $itemClass($usersActive) }}">
                                <i data-lucide="users" class="{{ $iconClass($usersActive) }}"></i>
                                <span>Usuarios</span>
                            </a>
                        @endif

                        <a href="{{ route('admin.managed-areas.index') }}" class="{{ $itemClass($areasActive) }}">
                            <i data-lucide="map-pin" class="{{ $iconClass($areasActive) }}"></i>
                            <span>Áreas</span>
                        </a>

                        <a href="{{ route('admin.managed-groups.index') }}" class="{{ $itemClass($groupsActive) }}">
                            <i data-lucide="layers-3" class="{{ $iconClass($groupsActive) }}"></i>
                            <span>Agrupaciones</span>
                        </a>

                        <a href="{{ route('admin.managed-elements.index') }}" class="{{ $itemClass($elementsActive) }}">
                            <i data-lucide="settings" class="{{ $iconClass($elementsActive) }}"></i>
                            <span>Activos</span>
                        </a>
                    </div>


                    <div class="mt-4 space-y-1">
                        <p class="px-3 pt-2 pb-1 text-xs font-semibold uppercase tracking-wider text-slate-400">
                            Configuración técnica
                        </p>

                        <a href="{{ route('admin.managed-element-types.index') }}" class="{{ $itemClass($elementTypesActive) }}">
                            <i data-lucide="boxes" class="{{ $iconClass($elementTypesActive) }}"></i>
                            <span>Tipos de activos - Plantilla</span>
                        </a>

                        <a href="{{ route('admin.managed-components.index') }}" class="{{ $itemClass($componentsActive) }}">
                            <i data-lucide="wrench" class="{{ $iconClass($componentsActive) }}"></i>
                            <span>Componentes - Plantilla</span>
                        </a>

                        <a href="{{ route('admin.managed-diagnostics.index') }}" class="{{ $itemClass($diagnosticsActive) }}">
                            <i data-lucide="clipboard-check" class="{{ $iconClass($diagnosticsActive) }}"></i>
                            <span>Diagnósticos</span>
                        </a>

                        <a href="{{ route('admin.managed-component-diagnostics.index') }}" class="{{ $itemClass($componentDiagnosticsActive) }}">
                            <i data-lucide="git-merge" class="{{ $iconClass($componentDiagnosticsActive) }}"></i>
                            <span>Componentes - Diagnósticos</span>
                        </a>

                        <a href="{{ route('admin.managed-conditions.index') }}" class="{{ $itemClass($conditionsActive) }}">
                            <i data-lucide="alert-triangle" class="{{ $iconClass($conditionsActive) }}"></i>
                            <span>Condiciones</span>
                        </a>
                    </div>
                @endif

                {{-- INSPECTOR --}}
                @if($role === 'inspector')
                    <a href="{{ route('inspector.reports.index') }}" class="{{ $itemClass($inspectorReportsActive) }}">
                        <i data-lucide="file-text" class="{{ $iconClass($inspectorReportsActive) }}"></i>
                        <span>Registrar reporte</span>
                    </a>
                @endif

                {{-- OBSERVADOR / OBSERVADOR_CLIENTE --}}
                @if($isObserver)
                    <a href="{{ route('admin.dashboard') }}" class="{{ $itemClass($dashboardActive) }}">
                        <i data-lucide="layout-dashboard" class="{{ $iconClass($dashboardActive) }}"></i>
                        <span>Dashboard</span>
                    </a>
                @endif
            </nav>
        </div>
    </div>
</aside>