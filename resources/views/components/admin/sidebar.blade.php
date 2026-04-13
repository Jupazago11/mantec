@php
    $user = auth()->user();
    $role = $user->role->key ?? null;

    $isPowerAdmin = in_array($role, ['superadmin', 'admin_global'], true);
    $isOperationalAdmin = in_array($role, ['superadmin', 'admin_global', 'admin'], true);
    $isObserver = in_array($role, ['observador', 'observador_cliente'], true);
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
                    Panel administrativo
                </p>
            </div>

            <div class="flex items-center gap-2">
            <button
                type="button"
                class="hidden text-slate-500 lg:inline-flex"
                @click="toggleSidebarCollapse()"
                title="Ocultar menú"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
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
                    <a href="{{ route('admin.dashboard') }}"
                       class="flex items-center rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                        Dashboard
                    </a>

                    {{-- Solo superadmin y admin_global --}}
                    @if($isPowerAdmin)
                        <div class="mt-4 space-y-1">
                            <p class="px-3 pt-2 pb-1 text-xs font-semibold uppercase tracking-wider text-slate-400">
                                Global
                            </p>

                            <a href="{{ route('admin.clients.index') }}"
                               class="flex items-center rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                                Clientes
                            </a>

                            <a href="{{ route('admin.managed-users.index') }}"
                               class="flex items-center rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                                Usuarios
                            </a>
                        </div>
                    @endif

                    <div class="mt-4 space-y-1">
                        <p class="px-3 pt-2 pb-1 text-xs font-semibold uppercase tracking-wider text-slate-400">
                            Operación
                        </p>

                        @if($role === 'admin')
                            <a href="{{ route('admin.managed-users.index') }}"
                               class="flex items-center rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                                Usuarios
                            </a>
                        @endif

                        <a href="{{ route('admin.managed-areas.index') }}"
                           class="flex items-center rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                            Áreas
                        </a>

                        <a href="{{ route('admin.managed-elements.index') }}"
                           class="flex items-center rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                            Activos
                        </a>
                    </div>

                    <div class="mt-4 space-y-1">
                        <p class="px-3 pt-2 pb-1 text-xs font-semibold uppercase tracking-wider text-slate-400">
                            Configuración técnica
                        </p>

                        <a href="{{ route('admin.managed-element-types.index') }}"
                           class="flex items-center rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                            Tipos de activos - Plantilla
                        </a>

                        <a href="{{ route('admin.managed-components.index') }}"
                           class="flex items-center rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                            Componentes - Plantilla
                        </a>

                        <a href="{{ route('admin.managed-diagnostics.index') }}"
                           class="flex items-center rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                            Diagnósticos
                        </a>

                        <a href="{{ route('admin.managed-component-diagnostics.index') }}"
                           class="flex items-center rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                            Componentes - Diagnósticos
                        </a>

                        <a href="{{ route('admin.managed-conditions.index') }}"
                           class="flex items-center rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                            Condiciones
                        </a>
                    </div>
                @endif

                {{-- INSPECTOR --}}
                @if($role === 'inspector')
                    <a href="{{ route('inspector.reports.index') }}"
                       class="flex items-center rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                        Registrar reporte
                    </a>
                @endif

                {{-- OBSERVADOR / OBSERVADOR_CLIENTE --}}
                @if($isObserver)
                    <a href="{{ route('admin.dashboard') }}"
                       class="flex items-center rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                        Dashboard
                    </a>
                @endif
            </nav>
        </div>
    </div>
</aside>
