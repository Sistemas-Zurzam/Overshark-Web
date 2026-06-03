<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Overshark, tienda online">
    <title>@yield('title', 'Overshark')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-white antialiased">
    <header class="border-b border-white/10 bg-slate-950/90 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 lg:px-8">
            <a href="{{ route('web.home') }}" class="text-xl font-black tracking-[0.25em]">OVERSHARK</a>
            <nav class="hidden items-center gap-8 text-sm font-semibold text-slate-300 md:flex">
                <a class="transition hover:text-cyan-400" href="#productos">Productos</a>
                <a class="transition hover:text-cyan-400" href="#categorias">Categorías</a>
                <a class="transition hover:text-cyan-400" href="#contacto">Contacto</a>
            </nav>
            <a href="{{ route('admin.dashboard') }}" class="rounded-full border border-cyan-400/60 px-4 py-2 text-sm font-bold text-cyan-300 transition hover:bg-cyan-400 hover:text-slate-950">
                Administración
            </a>
        </div>
    </header>

    <main>@yield('content')</main>

    <footer id="contacto" class="border-t border-white/10 bg-slate-950">
        <div class="mx-auto flex max-w-7xl flex-col gap-3 px-5 py-8 text-sm text-slate-400 md:flex-row md:items-center md:justify-between lg:px-8">
            <p>© {{ date('Y') }} Overshark. Todos los derechos reservados.</p>
            <p>Ventas y atención al cliente</p>
        </div>
    </footer>
</body>
</html>
