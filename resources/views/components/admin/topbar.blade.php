<header class="border-b border-slate-200 bg-white">
    <div class="flex items-center justify-between px-6 py-4 md:px-8">
        <div>
            <p class="text-sm text-slate-500">Sistema de Gestión de Inspecciones</p>
            <h1 class="text-lg font-semibold text-slate-900">
                @yield('header_title', 'Dashboard')
            </h1>
        </div>

        <div class="flex items-center gap-4">
            <div class="text-right">
                <p class="text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                <p class="text-xs uppercase tracking-wide text-slate-500">
                    {{ auth()->user()->role->name ?? 'Sin rol' }}
                </p>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button
                    type="submit"
                    class="rounded-xl bg-[#d94d33] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                >
                    Cerrar sesión
                </button>
            </form>
        </div>
    </div>
</header>