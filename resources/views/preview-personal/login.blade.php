@extends('preview-personal._layout')

@section('title', 'Login administrativo')

@section('content')
<div class="mx-auto flex max-w-5xl items-center justify-center py-6">
    <div class="grid w-full overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl lg:grid-cols-2">

        <div class="hidden flex-col justify-between bg-[#0f172a] p-10 text-white lg:flex">
            <div>
                <span class="text-2xl font-extrabold tracking-tight">ManTec</span>

                <div class="mt-16">
                    <span class="inline-flex rounded-full border border-white/20 bg-white/10 px-4 py-1 text-sm font-medium text-slate-200">
                        Gestión de Personal
                    </span>

                    <h1 class="mt-6 text-4xl font-bold leading-tight">
                        Programación, bitácora y diario de campo del personal en sitio
                    </h1>

                    <p class="mt-6 max-w-md text-base leading-7 text-slate-300">
                        Login independiente del acceso de inspectores y reportes preventivos —
                        pensado para supervisores y personal administrativo.
                    </p>
                </div>
            </div>

            <div class="text-sm text-slate-400">© {{ date('Y') }} ManTec</div>
        </div>

        <div class="flex items-center justify-center p-6 sm:p-10">
            <div class="w-full max-w-md">
                <div class="mb-8 text-center lg:text-left">
                    <span class="text-2xl font-extrabold tracking-tight text-slate-900 lg:hidden">ManTec</span>
                    <h2 class="mt-4 text-3xl font-bold tracking-tight text-slate-900">Iniciar sesión</h2>
                    <p class="mt-2 text-sm text-slate-600">Acceso administrativo — Gestión de Personal</p>
                </div>

                <form class="space-y-5" onsubmit="return false">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Usuario</label>
                        <input type="text" placeholder="lfernando" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d55b20] focus:ring-1 focus:ring-[#d55b20]">
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Contraseña</label>
                        <input type="password" placeholder="••••••••" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d55b20] focus:ring-1 focus:ring-[#d55b20]">
                    </div>

                    <a
                        href="{{ route('preview-personal.programacion') }}"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-[#d55b20] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b8481a]"
                    >
                        Ingresar
                    </a>
                    <p class="text-center text-xs text-slate-400">
                        (mockup: el botón navega directo a la Programación de ejemplo)
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
