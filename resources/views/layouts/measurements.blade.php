<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Mediciones')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>
<body class="h-screen overflow-hidden bg-slate-100 text-slate-900">
    <div
        x-data="{
            sidebarOpen: false,
            sidebarCollapsed: localStorage.getItem('measurements_sidebar_collapsed') === '1',
            toggleSidebarCollapse() {
                this.sidebarCollapsed = !this.sidebarCollapsed;
                localStorage.setItem('measurements_sidebar_collapsed', this.sidebarCollapsed ? '1' : '0');
            },
            showSidebar() {
                this.sidebarCollapsed = false;
                localStorage.setItem('measurements_sidebar_collapsed', '0');
            }
        }"
        class="flex h-full"
    >
        @include('components.measurements.sidebar')

        <div class="flex min-w-0 flex-1 flex-col">
            <div class="border-b border-slate-200 bg-white px-6 py-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex min-w-0 items-start gap-3">
                        <button
                            type="button"
                            class="mt-1 text-slate-600 lg:hidden"
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

                        <div class="min-w-0">
                            <p class="text-sm text-slate-500">Módulo independiente</p>
                            <h1 class="truncate text-lg font-semibold text-slate-900">
                                @yield('header_title', 'Mediciones')
                            </h1>

                            @hasSection('header_context')
                                <div class="mt-2">
                                    @yield('header_context')
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        @yield('header_actions')
                    </div>
                </div>
            </div>

            <main class="flex-1 overflow-y-auto p-6 md:p-8">
                @yield('content')
            </main>
        </div>
    </div>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                window.lucide.createIcons();
            }
        });
    </script>
</body>
</html>