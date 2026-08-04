<!DOCTYPE html>
<html lang="es" class="h-full" x-data="{ dark: localStorage.getItem('theme') === 'dark' }" :class="{ 'dark': dark }">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ config('app.name', 'ServiceManager') }}</title>
    <script>if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.classList.add('dark');</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full bg-app text-ink">
<main class="grid min-h-screen lg:grid-cols-[1.05fr_.95fr]">
    <section class="relative hidden overflow-hidden bg-ink p-10 text-white lg:flex lg:flex-col lg:justify-between">
        <div class="absolute -right-32 -top-32 h-96 w-96 rounded-full bg-brand/40 blur-3xl"></div><div class="absolute -bottom-40 -left-20 h-96 w-96 rounded-full bg-cyan-400/20 blur-3xl"></div>
        <div class="relative"><div class="mb-10 flex items-center gap-3"><div class="grid h-11 w-11 place-items-center rounded-xl bg-white text-sm font-black text-ink">SR</div><div><p class="text-lg font-black">ServiceManager</p><p class="text-[10px] uppercase tracking-[0.2em] text-white/60">B2B operations</p></div></div><p class="max-w-lg text-4xl font-black leading-tight">Controla tus servicios recurrentes con claridad.</p><p class="mt-5 max-w-md text-base leading-7 text-white/65">Una operación simple para saber qué gestionar hoy y cuánto dinero debe entrar.</p></div>
        <p class="relative text-xs font-semibold uppercase tracking-[0.18em] text-white/50">Operación · Cobranza · Proyección</p>
    </section>
    <section class="flex min-h-screen flex-col px-5 py-6 sm:px-10 lg:px-20">
        <div class="flex justify-end"><button @click="dark = !dark; localStorage.setItem('theme', dark ? 'dark' : 'light')" class="grid h-10 w-10 place-items-center rounded-xl border border-line text-lg" :aria-label="dark ? 'Activar modo claro' : 'Activar modo oscuro'"><span x-text="dark ? '☀' : '☾'"></span></button></div>
        <div class="m-auto w-full max-w-md py-10"><div class="mb-8 lg:hidden"><p class="text-xl font-black text-ink">ServiceManager</p><p class="text-xs uppercase tracking-[0.18em] text-muted">B2B operations</p></div>{{ $slot }}</div>
        <p class="text-center text-xs text-muted">© {{ now()->year }} ServiceManager · Gestión segura</p>
    </section>
</main>
@livewireScripts
</body>
</html>
