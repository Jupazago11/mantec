<nav class="sticky top-0 z-50 border-b border-slate-200 bg-white/90 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
        <a href="{{ route('home') }}" class="text-2xl font-extrabold tracking-tight text-slate-900">
            ManTec
        </a>

        <div class="hidden items-center gap-8 md:flex">
            <a href="#servicios" class="text-sm font-medium text-slate-600 transition hover:text-slate-900">
                Servicios
            </a>
            <a href="#nosotros" class="text-sm font-medium text-slate-600 transition hover:text-slate-900">
                Nosotros
            </a>
            <a href="#contacto" class="text-sm font-medium text-slate-600 transition hover:text-slate-900">
                Contacto
            </a>
            <a href="{{ route('login') }}"
               class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700">
                Login
            </a>
        </div>
    </div>
</nav>