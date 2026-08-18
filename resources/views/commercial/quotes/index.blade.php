@extends('layouts.app')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-brand">Comercial</p>
            <h1 class="text-2xl font-black tracking-tight text-ink sm:text-4xl">Cotizaciones</h1>
            <p class="mt-2 text-sm text-muted">Gestiona oportunidades, detecta vencimientos y conviértelas en facturas.</p>
        </div>
        <a class="button" href="{{ route('commercial.quotes.create') }}">＋ Nueva cotización</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card"><p class="text-xs font-bold uppercase tracking-wider text-muted">Creadas</p><p class="mt-2 text-3xl font-black text-ink">{{ $quoteSummary['created_count'] }}</p><p class="mt-2 text-sm font-bold text-brand">@forelse($quoteSummary['created_totals'] as $currency => $total){{ $currency }} {{ number_format($total, 2) }}@if(! $loop->last)<br>@endif @empty—@endforelse</p></div>
        <div class="card"><p class="text-xs font-bold uppercase tracking-wider text-muted">Abiertas</p><p class="mt-2 text-3xl font-black text-amber-700 dark:text-amber-300">{{ $quoteSummary['open_count'] }}</p><p class="mt-2 text-sm font-bold text-amber-700 dark:text-amber-300">@forelse($quoteSummary['open_totals'] as $currency => $total){{ $currency }} {{ number_format($total, 2) }}@if(! $loop->last)<br>@endif @empty—@endforelse</p></div>
        <div class="card"><p class="text-xs font-bold uppercase tracking-wider text-muted">Convertidas</p><p class="mt-2 text-3xl font-black text-emerald-700 dark:text-emerald-300">{{ $quoteSummary['converted_count'] }}</p><p class="mt-2 text-sm font-bold text-emerald-700 dark:text-emerald-300">@forelse($quoteSummary['converted_totals'] as $currency => $total){{ $currency }} {{ number_format($total, 2) }}@if(! $loop->last)<br>@endif @empty—@endforelse</p></div>
        <div class="card"><p class="text-xs font-bold uppercase tracking-wider text-muted">Conversión</p><p class="mt-2 text-3xl font-black text-ink">{{ $quoteSummary['conversion_rate'] }}%</p><p class="mt-2 text-xs text-muted">De las cotizaciones creadas en el período.</p></div>
    </div>

    <form class="panel mt-6 grid gap-3 lg:grid-cols-6" method="get">
        <label class="lg:col-span-2 text-sm font-bold text-ink">Buscar
            <input class="input" name="search" value="{{ request('search') }}" placeholder="Número o cliente">
        </label>
        <label class="text-sm font-bold text-ink">Estado
            <select class="input" name="status"><option value="">Todos</option>@foreach(\App\Enums\CommercialQuoteStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select>
        </label>
        <label class="text-sm font-bold text-ink">Fecha por
            <select class="input" name="date_field"><option value="issue" @selected(request('date_field', 'issue') === 'issue')>Emisión</option><option value="due" @selected(request('date_field') === 'due')>Vencimiento</option><option value="converted" @selected(request('date_field') === 'converted')>Conversión</option></select>
        </label>
        <label class="text-sm font-bold text-ink">Desde<input class="input" type="date" name="date_from" value="{{ request('date_from', $from->toDateString()) }}"></label>
        <label class="text-sm font-bold text-ink">Hasta<input class="input" type="date" name="date_to" value="{{ request('date_to', $to->toDateString()) }}"></label>
        <div class="lg:col-span-6 flex justify-end"><button class="button">Aplicar filtros</button></div>
    </form>

    <div class="surface mt-5 overflow-hidden"><div class="overflow-x-auto"><table class="table"><thead><tr><th>Número</th><th>Cliente</th><th>Emisión</th><th>Vence</th><th>Total</th><th>Estado</th><th>Seguimiento</th><th></th></tr></thead><tbody>
        @forelse($quotes as $quote)
            <tr><td class="font-bold text-ink">{{ $quote->number }}</td><td>{{ $quote->client->name }}</td><td>{{ $quote->issue_date->format('d/m/Y') }}</td><td>{{ $quote->due_date?->format('d/m/Y') ?? '—' }}</td><td class="font-bold">{{ $quote->currency }} {{ number_format($quote->total, 2) }}</td><td><span class="rounded-full bg-brand/10 px-2 py-1 text-xs font-bold text-brand">{{ $quote->status->label() }}</span></td><td class="text-xs text-muted">@if($quote->status->value === 'converted') Convertida {{ $quote->converted_at?->format('d/m/Y') }} @elseif($quote->due_date && $quote->due_date->isPast()) Vencida: dar seguimiento @elseif($quote->due_date) Seguimiento antes del {{ $quote->due_date->format('d/m/Y') }} @else Sin vencimiento @endif</td><td><a class="font-bold text-brand" href="{{ route('commercial.quotes.edit', $quote) }}">Editar →</a></td></tr>
        @empty<tr><td colspan="8" class="py-12 text-center text-muted">No hay cotizaciones que coincidan.</td></tr>@endforelse
    </tbody></table></div><div class="border-t border-line px-4 py-3">{{ $quotes->links() }}</div></div>
@endsection
