<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Panel administrativo')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-screen overflow-hidden bg-slate-100 text-slate-900">
    <div x-data="{ sidebarOpen: false }" class="flex h-full">
        @include('components.admin.sidebar')

        <div class="flex min-w-0 flex-1 flex-col">
            @include('components.admin.topbar')

            <main class="flex-1 overflow-y-auto p-6 md:p-8">
                @yield('content')
            </main>
        </div>
    </div>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
