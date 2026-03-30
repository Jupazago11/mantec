<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Panel administrativo')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <div class="flex min-h-screen">
        @include('components.admin.sidebar')

        <div class="flex min-w-0 flex-1 flex-col">
            @include('components.admin.topbar')

            <main class="flex-1 p-6 md:p-8">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>