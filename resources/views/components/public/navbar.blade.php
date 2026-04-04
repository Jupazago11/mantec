<header class="sticky top-0 z-50 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
        <a href="{{ url('/') }}" class="text-2xl font-extrabold tracking-tight text-slate-900">
            ManTec
        </a>

        {{-- Menú desktop --}}
        <nav class="hidden items-center gap-8 md:flex">
            <a href="#hero" class="text-sm font-medium text-slate-700 transition hover:text-[#d94d33]">Inicio</a>
            <a href="#servicios" class="text-sm font-medium text-slate-700 transition hover:text-[#d94d33]">Servicios</a>
            <a href="#nosotros" class="text-sm font-medium text-slate-700 transition hover:text-[#d94d33]">Nosotros</a>
            <a href="#contacto" class="text-sm font-medium text-slate-700 transition hover:text-[#d94d33]">Contacto</a>

            <a
                href="{{ route('login') }}"
                class="inline-flex items-center rounded-xl bg-[#d94d33] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
            >
                Iniciar sesión
            </a>
        </nav>

        {{-- Botón móvil --}}
        <button
            type="button"
            id="mobileMenuButton"
            class="inline-flex items-center justify-center rounded-xl border border-slate-300 p-2 text-slate-700 transition hover:bg-slate-100 md:hidden"
            aria-controls="mobileMenu"
            aria-expanded="false"
        >
            <svg id="menuOpenIcon" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>

            <svg id="menuCloseIcon" xmlns="http://www.w3.org/2000/svg" class="hidden h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    {{-- Menú móvil --}}
    <div id="mobileMenu" class="hidden border-t border-slate-200 bg-white md:hidden">
        <nav class="mx-auto flex max-w-7xl flex-col gap-1 px-4 py-4 sm:px-6">
            <a href="#hero" class="rounded-xl px-3 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-[#d94d33]">
                Inicio
            </a>
            <a href="#servicios" class="rounded-xl px-3 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-[#d94d33]">
                Servicios
            </a>
            <a href="#nosotros" class="rounded-xl px-3 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-[#d94d33]">
                Nosotros
            </a>
            <a href="#contacto" class="rounded-xl px-3 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-[#d94d33]">
                Contacto
            </a>

            <a
                href="{{ route('login') }}"
                class="mt-2 inline-flex items-center justify-center rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
            >
                Iniciar sesión
            </a>
        </nav>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const button = document.getElementById('mobileMenuButton');
            const menu = document.getElementById('mobileMenu');
            const openIcon = document.getElementById('menuOpenIcon');
            const closeIcon = document.getElementById('menuCloseIcon');

            if (!button || !menu) return;

            function closeMenu() {
                menu.classList.add('hidden');
                button.setAttribute('aria-expanded', 'false');
                openIcon?.classList.remove('hidden');
                closeIcon?.classList.add('hidden');
            }

            function openMenu() {
                menu.classList.remove('hidden');
                button.setAttribute('aria-expanded', 'true');
                openIcon?.classList.add('hidden');
                closeIcon?.classList.remove('hidden');
            }

            button.addEventListener('click', function () {
                const isHidden = menu.classList.contains('hidden');

                if (isHidden) {
                    openMenu();
                } else {
                    closeMenu();
                }
            });

            menu.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', function () {
                    closeMenu();
                });
            });

            window.addEventListener('resize', function () {
                if (window.innerWidth >= 768) {
                    closeMenu();
                }
            });
        });
    </script>
</header>
