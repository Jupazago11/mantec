{{-- Sidebar real del modulo "Personal y Programacion" — mismo lenguaje
     visual que el sidebar real de Mantec (naranja de marca #d55b20), pero
     independiente (ver seccion 3 de NUEVA_FUNCIONALIDAD_PERSONAL_Y_PROGRAMACION.md).
     Cada item se muestra segun el permiso del PersonalRole del actor (ver
     "Roles y permisos dinamicos", seccion 14.5). "Roles y permisos" usa el
     permiso "administrar_roles" (desde 2026-09-28; antes solo superadmin),
     que exige Rol Y Subrol marcados, igual que "Responsable de actividad". --}}
{{-- Ancho/visibilidad por CSS (.personal-sidebar en layouts/personal):
     colapsado en escritorio = clase personal-sidebar-collapsed en <html>,
     fijada antes del primer pintado; abierto en movil = .is-open. Ya no
     depende de que Alpine cargue para verse bien (pedido 2026-09-28: el
     sidebar parpadeaba al recargar). --}}
<aside
    class="personal-sidebar fixed inset-y-0 left-0 z-50 w-72 border-r border-slate-200 bg-white lg:static"
    :class="{ 'is-open': sidebarOpen }"
>
    <div class="flex h-full flex-col">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
            <div>
                {{-- Primer modulo permitido para el rol (mismo criterio que el
                     login), no Empleados fijo: un rol sin "ver_empleados"
                     recibia un 403 al hacer clic en el logo. --}}
                <a href="{{ route(\App\Support\PersonalGuard::firstAccessibleRoute() ?? 'personal.sin-acceso') }}" class="text-2xl font-extrabold tracking-tight text-slate-900">
                    ManTec
                </a>
                <p class="mt-1 text-sm text-slate-500">Panel de Personal</p>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" class="hidden text-slate-500 lg:inline-flex" @click="toggleSidebarCollapse()" title="Ocultar menú">
                    <i data-lucide="chevron-left" class="h-5 w-5"></i>
                </button>
                <button type="button" class="text-slate-500 lg:hidden" @click="sidebarOpen = false">✕</button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto px-4 py-6">
            <p class="px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Menú</p>

            @php
                $navItems = array_filter([
                    ['route' => 'personal.empleados.index', 'label' => 'Empleados', 'icon' => 'users', 'preview' => false, 'visible' => \App\Support\PersonalGuard::can('ver_empleados')],
                    ['route' => 'personal.programacion.index', 'label' => 'Programación', 'icon' => 'calendar-days', 'preview' => false, 'visible' => \App\Support\PersonalGuard::can('ver_programacion')],
                    ['route' => 'personal.diario-campo.index', 'label' => 'Diario de Campo', 'icon' => 'clipboard-list', 'preview' => false, 'visible' => \App\Support\PersonalGuard::can('ver_diario_campo')],
                    ['route' => 'personal.bitacora.index', 'label' => 'Bitácora mensual', 'icon' => 'table', 'preview' => false, 'visible' => \App\Support\PersonalGuard::can('ver_bitacora')],
                    ['route' => 'personal.roles.index', 'label' => 'Roles y permisos', 'icon' => 'shield-check', 'preview' => false, 'visible' => \App\Support\PersonalGuard::can('administrar_roles')],
                ], fn ($item) => $item['visible']);
            @endphp

            <nav class="mt-4 space-y-1">
                @foreach ($navItems as $item)
                    @php $activo = request()->routeIs($item['route']); @endphp
                    <a
                        href="{{ route($item['route']) }}"
                        class="{{ $activo
                            ? 'flex items-center justify-between gap-3 rounded-xl bg-[#d55b20]/10 px-3 py-2 text-sm font-semibold text-[#d55b20] transition'
                            : 'flex items-center justify-between gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900' }}"
                    >
                        <span class="flex items-center gap-3">
                            <i data-lucide="{{ $item['icon'] }}" class="{{ $activo ? 'h-5 w-5 text-[#d55b20]' : 'h-5 w-5 text-slate-400' }}"></i>
                            <span>{{ $item['label'] }}</span>
                        </span>
                        @if ($item['preview'])
                            <span class="rounded-full bg-amber-100 px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-amber-700" title="Vista previa de diseño, sin guardar en base de datos todavía">
                                Vista previa
                            </span>
                        @endif
                    </a>
                @endforeach
            </nav>
        </div>

        {{-- Pie: quien esta logueado + "Volver al panel admin" (superadmin)
             + "Salir". Estos dos ultimos vivian en el header, que se quito
             en escritorio (pedido 2026-09-28). --}}
        <div class="border-t border-slate-200 px-4 py-3">
            <div class="px-3 text-xs leading-relaxed text-slate-400">
                {{ \App\Support\PersonalGuard::displayName() }}
                @if ($role = \App\Support\PersonalGuard::roleLabel())
                    <span class="block text-slate-500">{{ $role }}</span>
                @endif
            </div>

            <div class="mt-2 space-y-1">
                @if (\App\Support\PersonalGuard::isSuperadmin())
                    <a
                        href="{{ route('admin.dashboard') }}"
                        class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900"
                    >
                        <i data-lucide="arrow-left" class="h-5 w-5 text-slate-400"></i>
                        <span>Volver al panel admin</span>
                    </a>
                @endif
                <form method="POST" action="{{ route('personal.logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900"
                    >
                        <i data-lucide="log-out" class="h-5 w-5 text-slate-400"></i>
                        <span>Salir</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</aside>
