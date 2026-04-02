@php
    $user = auth()->user();
    $role = $user->role->key ?? null;
@endphp

<aside
    class="fixed inset-y-0 left-0 z-50 w-72 transform border-r border-slate-200 bg-white transition-transform duration-300 lg:static lg:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
>
    <div class="flex h-full flex-col">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
            <div>
                <a href="{{ route('admin.dashboard') }}" class="text-2xl font-extrabold tracking-tight text-slate-900">
                    ManTec
                </a>
                <p class="mt-1 text-sm text-slate-500">
                    Panel administrativo
                </p>
            </div>

            <button
                type="button"
                class="text-slate-500 lg:hidden"
                @click="sidebarOpen = false"
            >
                ✕
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-4 py-6">
            <p class="px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">
                Menú
            </p>

            <nav class="mt-4 space-y-1">
                <a href="{{ route('admin.dashboard') }}"
                   class="flex items-center rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                    Dashboard
                </a>

                @if($role === 'superadmin')
                    <div class="space-y-1">
                        <p class="px-3 pt-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
                            Organización
                        </p>

                        <a href="{{ route('admin.clients.index') }}"
                           class="flex items-center rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                            Clientes
                        </a>

                        <a href="{{ route('admin.users.index') }}"
                           class="flex items-center rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                            Usuarios
                        </a>

                        <a href="{{ route('admin.areas.index') }}"
                           class="flex items-center rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                            Áreas
                        </a>
                    </div>

                    <div class="mt-6 space-y-1">
                        <p class="px-3 pt-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
                            Activos
                        </p>

                        <a href="{{ route('admin.elements.index') }}"
                           class="flex items-center rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                            Activos
                        </a>

                        <a href="{{ route('admin.element-components.index') }}"
                           class="flex items-center rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                            Elem. - Componentes
                        </a>
                    </div>

                    <div class="mt-6 space-y-1">
                        <p class="px-3 pt-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
                            Configuración
                        </p>

                        <a href="{{ route('admin.element-types.index') }}"
                           class="flex items-center rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                            Tipos de Activos - Plantilla
                        </a>

                        <a href="{{ route('admin.components.index') }}"
                           class="flex items-center rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                            Componentes - Plantilla
                        </a>

                        <a href="{{ route('admin.diagnostics.index') }}"
                           class="flex items-center rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                            Diagnósticos
                        </a>

                        <a href="{{ route('admin.component-diagnostics.index') }}"
                           class="flex items-center rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                            Componentes - Diagnósticos
                        </a>

                        <a href="{{ route('admin.conditions.index') }}"
                           class="flex items-center rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                            Condiciones
                        </a>
                    </div>
                @endif

                @if($role === 'admin_global')
                    <a href="{{ route('admin.clients.index') }}"
                       class="flex items-center rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                        Clientes
                    </a>

                    <a href="{{ route('admin.users.index') }}"
                       class="flex items-center rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                        Usuarios
                    </a>
                @endif

                @if($role === 'admin')
                    <div class="space-y-1">
                        <p class="px-3 pt-2 pb-1 text-xs font-semibold uppercase tracking-wider text-slate-400">
                            Operación
                        </p>

                        <a href="{{ route('admin.managed-users.index') }}"
                           class="flex items-center rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                            Usuarios
                        </a>

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

                        <a href="{{ route('admin.component-diagnostics.index') }}"
                           class="flex items-center rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                            Componentes - Diagnósticos
                        </a>

                        <a href="{{ route('admin.managed-conditions.index') }}"
                           class="flex items-center rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                            Condiciones
                        </a>
                    </div>
                @endif

                @if($role === 'admin_cliente')
                    <a href="#"
                       class="flex items-center rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                        Mi cliente
                    </a>

                    <a href="#"
                       class="flex items-center rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                        Áreas
                    </a>

                    <a href="#"
                       class="flex items-center rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                        Elementos
                    </a>

                    <a href="#"
                       class="flex items-center rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                        Reportes
                    </a>
                @endif

                @if($role === 'inspector')
                    <a href="{{ route('inspector.reports.index') }}"
                       class="flex items-center rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                        Registrar reporte
                    </a>
                @endif
            </nav>
        </div>
    </div>
</aside>
