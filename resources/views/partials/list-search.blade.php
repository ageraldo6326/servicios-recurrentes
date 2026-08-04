<form method="get" class="panel mb-5 flex flex-col gap-3 sm:flex-row" role="search">
    <label class="relative flex-1"><span class="sr-only">{{ $placeholder ?? 'Buscar' }}</span><span class="pointer-events-none absolute left-3 top-3 text-lg text-muted">⌕</span><input class="input mt-0 pl-10" name="search" value="{{ $value ?? request('search') }}" placeholder="{{ $placeholder ?? 'Buscar...' }}"></label>
    <button class="button-secondary">Buscar</button>
    @if(request('search'))<a href="{{ url()->current() }}" class="button-secondary">Limpiar</a>@endif
</form>
