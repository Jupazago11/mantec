@extends('layouts.guest')

@section('title', 'Iniciar sesión')

@section('content')
<div class="grid w-full max-w-6xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl lg:grid-cols-2">

    <div class="hidden lg:flex flex-col justify-between bg-[#0f172a] p-10 text-white">
        <div>
            <a href="{{ route('home') }}" class="text-2xl font-extrabold tracking-tight">
                ManTec
            </a>

            <div class="mt-16">
                <span class="inline-flex rounded-full border border-white/20 bg-white/10 px-4 py-1 text-sm font-medium text-slate-200">
                    Sistema de Inspecciones
                </span>

                <h1 class="mt-6 text-4xl font-bold leading-tight">
                    Control técnico y trazabilidad operativa
                </h1>

                <p class="mt-6 max-w-md text-base leading-7 text-slate-300">
                    Gestiona inspecciones, evidencias y reportes desde una sola plataforma.
                </p>
            </div>
        </div>

        <div class="text-sm text-slate-400">
            © {{ date('Y') }} ManTec
        </div>
    </div>

    <div class="flex items-center justify-center p-6 sm:p-10">
        <div class="w-full max-w-md">

            <div class="mb-8 text-center lg:text-left">
                <a href="{{ route('home') }}" class="text-2xl font-extrabold tracking-tight text-slate-900 lg:hidden">
                    ManTec
                </a>

                <h2 class="mt-4 text-3xl font-bold tracking-tight text-slate-900">
                    Iniciar sesión
                </h2>

                <p class="mt-2 text-sm text-slate-600">
                    Accede con tus credenciales
                </p>
            </div>

            @if ($errors->has('login'))
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first('login') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.attempt') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Usuario
                    </label>
                    <input
                        type="text"
                        name="username"
                        value="{{ old('username') }}"
                        placeholder="superadmin"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                    >
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <label class="text-sm font-medium text-slate-700">
                            Contraseña
                        </label>
                        <a href="#" class="text-sm font-medium text-[#d94d33] hover:underline">
                            ¿Olvidaste?
                        </a>
                    </div>

                    <input
                        type="password"
                        name="password"
                        placeholder="123456"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#d94d33] focus:ring-1 focus:ring-[#d94d33]"
                    >
                </div>

                <div class="flex items-center justify-between">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]">
                        Recordarme
                    </label>
                </div>

                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center rounded-xl bg-[#d94d33] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                >
                    Ingresar
                </button>
            </form>

            <div class="mt-6 text-center text-sm text-slate-500">
                <a href="{{ route('home') }}" class="font-medium text-[#d94d33] hover:underline">
                    Volver al sitio
                </a>
            </div>

        </div>
    </div>

</div>
@endsection