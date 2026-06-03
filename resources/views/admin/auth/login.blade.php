<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso administrativo | Overshark</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid min-h-screen place-items-center bg-slate-950 px-5 text-slate-900 antialiased">
    <main class="w-full max-w-md rounded-3xl border border-white/10 bg-white p-7 shadow-2xl sm:p-9">
        <a href="{{ route('web.home') }}" class="mb-8 flex items-center gap-3 font-black tracking-[0.2em] text-slate-950">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-cyan-500 text-sm">OS</span>
            <span>OVERSHARK</span>
        </a>

        <p class="text-sm font-bold uppercase tracking-widest text-cyan-600">Administracion</p>
        <h1 class="mt-2 text-3xl font-black">Iniciar sesion</h1>
        <p class="mt-2 text-sm leading-6 text-slate-500">Ingresa tus credenciales para acceder al panel.</p>

        <form action="{{ route('admin.login.store') }}" method="POST" class="mt-8 space-y-5">
            @csrf
            <label class="block">
                <span class="mb-2 block text-sm font-bold">Correo electronico</span>
                <input name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100">
            </label>
            <label class="block">
                <span class="mb-2 block text-sm font-bold">Contrasena</span>
                <input name="password" type="password" required autocomplete="current-password" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100">
            </label>

            @error('email')
                <p class="rounded-xl bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $message }}</p>
            @enderror

            <button type="submit" class="w-full rounded-xl bg-slate-950 px-5 py-3 font-bold text-white transition hover:bg-cyan-600">
                Ingresar
            </button>
        </form>
    </main>
</body>
</html>
