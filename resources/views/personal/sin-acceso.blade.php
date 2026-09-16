@extends('layouts.personal')

@section('title', 'Sin acceso')

@section('content')
<div class="mx-auto flex max-w-lg flex-col items-center gap-4 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center shadow-sm">
    <i data-lucide="shield-off" class="h-10 w-10 text-slate-300"></i>
    <div>
        <h1 class="text-lg font-bold text-slate-900">Tu rol todavía no tiene módulos asignados</h1>
        <p class="mt-1 text-sm text-slate-500">
            {{ \App\Support\PersonalGuard::displayName() }}
            @if ($rol = \App\Support\PersonalGuard::roleLabel())
                — rol <span class="font-medium text-slate-700">{{ $rol }}</span>
            @endif
            no tiene ningún permiso marcado. Pide a un administrador que te asigne acceso
            desde "Roles y permisos".
        </p>
    </div>
    <form method="POST" action="{{ route('personal.logout') }}">
        @csrf
        <button type="submit" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
            Cerrar sesión
        </button>
    </form>
</div>
@endsection
