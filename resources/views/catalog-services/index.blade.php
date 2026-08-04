@extends('layouts.app')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-brand">Productos comerciales</p>
            <h1 class="text-2xl font-black tracking-tight text-ink sm:text-4xl">Catálogo de servicios</h1>
            <p class="mt-2 max-w-2xl text-sm text-muted">Define los servicios que puedes ofrecer y reutilizar en nuevas contrataciones.</p>
        </div>
        <a wire:navigate href="{{ route('catalog-services.create') }}" class="button w-full sm:w-auto">＋ Nuevo servicio</a>
    </div>

    <form method="get" class="panel mb-5 flex flex-col gap-3 sm:flex-row" role="search">
        <label class="relative flex-1">
            <span class="sr-only">Buscar servicio del catálogo</span>
            <span class="pointer-events-none absolute left-3 top-3 text-lg text-muted">⌕</span>
            <input class="input mt-0 pl-10" name="search" value="{{ $search }}" placeholder="Buscar servicio del catálogo">
        </label>
        <button class="button-secondary">Buscar</button>
        @if($search !== '')<a wire:navigate href="{{ route('catalog-services.index') }}" class="button-secondary">Limpiar</a>@endif
    </form>

    <div class="surface overflow-hidden">
        <div class="hidden overflow-x-auto md:block">
            <table class="table">
                <thead><tr><th>Servicio</th><th>Estado</th><th>Contrataciones</th><th></th></tr></thead>
                <tbody>
                    @forelse($services as $service)
                        <tr>
                            <td class="font-bold text-ink">{{ $service->name }}</td>
                            <td><span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase {{ $service->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300' }}">{{ $service->is_active ? 'Activo' : 'Inactivo' }}</span></td>
                            <td class="text-sm text-muted">{{ $service->contracted_services_count }}</td>
                            <td><a wire:navigate class="font-bold text-brand hover:underline" href="{{ route('catalog-services.edit', $service) }}">Editar</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-12 text-center text-muted">No hay servicios que coincidan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="divide-y divide-line md:hidden">
            @forelse($services as $service)
                <article class="flex items-center justify-between gap-4 p-4">
                    <div class="min-w-0"><p class="truncate font-bold text-ink">{{ $service->name }}</p><p class="mt-1 text-xs text-muted">{{ $service->contracted_services_count }} contrataciones</p></div>
                    <div class="flex shrink-0 flex-col items-end gap-2"><span class="rounded-full px-2 py-1 text-[10px] font-black uppercase {{ $service->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $service->is_active ? 'Activo' : 'Inactivo' }}</span><a wire:navigate class="text-sm font-bold text-brand" href="{{ route('catalog-services.edit', $service) }}">Editar</a></div>
                </article>
            @empty
                <div class="p-10 text-center text-sm text-muted">No hay servicios que coincidan.</div>
            @endforelse
        </div>
        <div class="border-t border-line px-4 py-3">{{ $services->links() }}</div>
    </div>
@endsection
