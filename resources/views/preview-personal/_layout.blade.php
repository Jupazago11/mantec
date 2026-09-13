<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Vista previa') · Gestión de Personal (mockup)</title>
    <meta name="robots" content="noindex, nofollow">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        [x-cloak]{display:none!important}

        /* Mismo patron de tabla ancha que usa el reporte preventivo real
           (resources/views/admin/reports/preventive/show.blade.php), para
           sincronia visual con el resto de Mantec. */
        .table-scroll-container {
            overflow-x: auto;
            overflow-y: visible;
            scrollbar-width: none;
        }
        .table-scroll-container::-webkit-scrollbar { height: 0; }

        .preventive-table {
            width: max-content;
            min-width: 100%;
            table-layout: auto;
        }
        .preventive-table th,
        .preventive-table td { vertical-align: top; }
        .preventive-table th {
            white-space: normal;
            line-height: 1.05rem;
            vertical-align: middle;
        }
        .sticky-table-head th {
            position: sticky;
            top: 0;
            z-index: 10;
            background: rgb(248 250 252);
            box-shadow: inset 0 -1px 0 rgb(226 232 240);
        }
        .sticky-table-head th.sticky-col,
        .preventive-table td.sticky-col {
            position: sticky;
            left: 0;
            z-index: 5;
            background: rgb(248 250 252);
        }
        .preventive-table td.sticky-col { background: white; z-index: 4; }

        /* Para tablas grandes (Bitacora, Diario de Campo): el patron de
           scroll oculto de arriba no alcanza — aqui el contenedor SI debe
           quedar acotado a la pantalla, con scrollbar lateral (vertical) e
           inferior (horizontal) visibles y usables en ambos sentidos. */
        .scroll-container-visible {
            overflow: auto;
            max-height: 65vh;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f1f5f9;
        }
        .scroll-container-visible::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }
        .scroll-container-visible::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        .scroll-container-visible::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }
        .scroll-container-visible::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>
<body class="h-screen overflow-hidden bg-slate-100 text-slate-900">

    <div class="flex h-full flex-col">
        <div class="shrink-0 bg-amber-400 px-4 py-2 text-center text-xs font-semibold text-amber-950 shadow">
            VISTA PREVIA DE DISEÑO — Gestión de Personal y Programación de Actividades — sin funcionalidad real, datos de ejemplo
        </div>

        {{-- Clon visual y funcional del sidebar real
             (resources/views/components/admin/sidebar.blade.php): mismo fondo
             blanco, acento naranja de marca (#d55b20), colapso de escritorio
             persistido en localStorage y drawer movil off-canvas. Usa una
             clave de localStorage propia (preview_personal_*) para no
             compartir el estado de colapso con el sidebar real del panel
             admin — misma app, mismo origen. --}}
        <div
            x-data="{
                sidebarOpen: false,
                sidebarCollapsed: localStorage.getItem('preview_personal_sidebar_collapsed') === '1',
                toggleSidebarCollapse() {
                    this.sidebarCollapsed = !this.sidebarCollapsed;
                    localStorage.setItem('preview_personal_sidebar_collapsed', this.sidebarCollapsed ? '1' : '0');
                },
                showSidebar() {
                    this.sidebarCollapsed = false;
                    localStorage.setItem('preview_personal_sidebar_collapsed', '0');
                }
            }"
            class="flex min-h-0 flex-1"
        >
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
                            <a href="{{ route('preview-personal.index') }}" class="text-2xl font-extrabold tracking-tight text-slate-900">
                                ManTec
                            </a>
                            <p class="mt-1 text-sm text-slate-500">Panel de Personal</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button" class="hidden text-slate-500 lg:inline-flex" @click="toggleSidebarCollapse()" title="Ocultar menú">
                                <i data-lucide="chevron-left" class="h-5 w-5"></i>
                            </button>
                            <button type="button" class="text-slate-500 lg:hidden" @click="sidebarOpen = false">
                                ✕
                            </button>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto px-4 py-6">
                        <p class="px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Menú</p>

                        @php
                            $navItems = [
                                ['route' => 'preview-personal.login', 'label' => 'Login', 'icon' => 'log-in'],
                                ['route' => 'preview-personal.programacion', 'label' => 'Programación', 'icon' => 'calendar-days'],
                                ['route' => 'preview-personal.diario-campo', 'label' => 'Diario de Campo', 'icon' => 'clipboard-list'],
                                ['route' => 'preview-personal.bitacora', 'label' => 'Bitácora mensual', 'icon' => 'table'],
                                ['route' => 'preview-personal.empleados', 'label' => 'Empleados', 'icon' => 'users'],
                            ];
                        @endphp

                        <nav class="mt-4 space-y-1">
                            @foreach ($navItems as $item)
                                @php $activo = request()->routeIs($item['route']); @endphp
                                <a
                                    href="{{ route($item['route']) }}"
                                    class="{{ $activo
                                        ? 'flex items-center gap-3 rounded-xl bg-[#d55b20]/10 px-3 py-2 text-sm font-semibold text-[#d55b20] transition'
                                        : 'flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900' }}"
                                >
                                    <i data-lucide="{{ $item['icon'] }}" class="{{ $activo ? 'h-5 w-5 text-[#d55b20]' : 'h-5 w-5 text-slate-400' }}"></i>
                                    <span>{{ $item['label'] }}</span>
                                </a>
                            @endforeach
                        </nav>
                    </div>

                    <div class="border-t border-slate-200 px-6 py-4 text-xs leading-relaxed text-slate-400">
                        Prototipo de diseño para revisión con el cliente.
                        No hay backend, base de datos ni autenticación real detrás de estas pantallas.
                    </div>
                </div>
            </aside>

            <div class="flex min-w-0 flex-1 flex-col overflow-hidden">
                <header class="sticky top-0 z-30 border-b border-slate-200 bg-white">
                    <div class="flex items-center gap-3 px-4 py-4 md:px-8">
                        <button type="button" class="text-slate-600 lg:hidden" @click="sidebarOpen = true" title="Abrir menú">
                            ☰
                        </button>
                        <button
                            type="button"
                            class="hidden items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 lg:inline-flex"
                            x-show="sidebarCollapsed" x-cloak
                            @click="showSidebar()"
                            title="Mostrar menú"
                        >
                            <i data-lucide="chevron-right" class="mr-2 h-4 w-4"></i> Menú
                        </button>
                        <h1 class="text-lg font-semibold text-slate-900">@yield('title', 'Vista previa')</h1>
                    </div>
                </header>

                <main class="min-w-0 flex-1 overflow-y-auto p-4 md:p-8">
                    @yield('content')
                </main>
            </div>
        </div>
    </div>

    <script>
        // Los popovers/tooltips dentro de .table-scroll-container no pueden
        // usar position:absolute — el navegador fuerza overflow-y a "auto"
        // (nunca "visible") apenas overflow-x no es "visible", asi que
        // cualquier hijo absoluto se recorta. Se "teletransportan" a <body>
        // (x-teleport) y se posicionan aqui como fixed, en base al boton que
        // los dispara. Devuelve un string listo para :style.
        function posicionarPopover(el, ancho, altoEstimado = 110) {
            const rect = el.getBoundingClientRect();
            let left = rect.right - ancho;
            if (left < 8) left = 8;
            if (left + ancho > window.innerWidth - 8) left = window.innerWidth - ancho - 8;
            let top = rect.bottom + 6;
            if (top + altoEstimado > window.innerHeight - 8) {
                top = rect.top - altoEstimado - 6;
            }
            return `position:fixed; top:${top}px; left:${left}px; width:${ancho}px; z-index:9999;`;
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) window.lucide.createIcons();
        });
        document.addEventListener('alpine:init', () => {
            if (window.lucide) window.lucide.createIcons();
        });
    </script>
    @stack('scripts')
</body>
</html>
