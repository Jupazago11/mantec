@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
            <p class="text-sm text-slate-600">
                Bienvenido, {{ auth()->user()->name }}
            </p>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="rounded-lg bg-[#d94d33] px-4 py-2 text-sm font-semibold text-white hover:bg-[#b83f29]">
                Cerrar sesión
            </button>
        </form>
    </div>
@endsection