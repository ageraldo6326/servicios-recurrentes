@extends('layouts.app')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-brand">Módulo comercial</p>
            <h1 class="text-2xl font-black tracking-tight text-ink sm:text-4xl">Dashboard comercial</h1>
            <p class="mt-2 text-sm text-muted">Controla lo facturado, lo cobrado y las oportunidades que requieren seguimiento.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a class="button-secondary" href="{{ route('commercial.quotes.create') }}">＋ Cotización</a>
            <a class="button" href="{{ route('commercial.invoices.create') }}">＋ Factura</a>
        </div>
    </div>

    <form class="panel mb-6 grid gap-3 sm:grid-cols-[1fr_1fr_auto]" method="get">
        <label class="text-sm font-bold text-ink">Desde
            <input class="input" type="date" name="date_from" value="{{ request('date_from', $from->toDateString()) }}">
        </label>
        <label class="text-sm font-bold text-ink">Hasta
            <input class="input" type="date" name="date_to" value="{{ request('date_to', $to->toDateString()) }}">
        </label>
        <button class="button self-end">Actualizar período</button>
        <p class="sm:col-span-3 text-xs leading-5 text-muted">Las facturas y cotizaciones creadas se miden por emisión; lo cobrado por fecha de pago; y los pendientes por vencimiento. Los totales se mantienen separados por moneda.</p>
    </form>

    <section>
        <div class="mb-3 flex items-center justify-between"><h2 class="section-title">Facturación y cobros</h2><span class="text-xs text-muted">{{ $from->format('d/m/Y') }} — {{ $to->format('d/m/Y') }}</span></div>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="card"><p class="text-xs font-bold uppercase tracking-wider text-muted">Facturas emitidas</p><p class="mt-3 text-3xl font-black text-ink">{{ $invoiceSummary['issued_count'] }}</p><div class="mt-2 text-sm font-bold text-brand">@forelse($invoiceSummary['issued_totals'] as $currency => $total){{ $currency }} {{ number_format($total, 2) }}@if(! $loop->last)<br>@endif @empty—@endforelse</div><p class="mt-1 text-xs text-muted">Por fecha de emisión</p></div>
            <div class="card"><p class="text-xs font-bold uppercase tracking-wider text-muted">Monto cobrado</p><p class="mt-3 text-3xl font-black text-emerald-700 dark:text-emerald-300">{{ $invoiceSummary['paid_count'] }}</p><div class="mt-2 text-sm font-bold text-emerald-700 dark:text-emerald-300">@forelse($invoiceSummary['paid_totals'] as $currency => $total){{ $currency }} {{ number_format($total, 2) }}@if(! $loop->last)<br>@endif @empty—@endforelse</div><p class="mt-1 text-xs text-muted">Facturas en estado pagada</p></div>
            <div class="card"><p class="text-xs font-bold uppercase tracking-wider text-muted">Pendientes de cobro</p><p class="mt-3 text-3xl font-black text-amber-700 dark:text-amber-300">{{ $invoiceSummary['pending_count'] }}</p><div class="mt-2 text-sm font-bold text-amber-700 dark:text-amber-300">@forelse($invoiceSummary['pending_totals'] as $currency => $total){{ $currency }} {{ number_format($total, 2) }}@if(! $loop->last)<br>@endif @empty—@endforelse</div><p class="mt-1 text-xs text-muted">Vencen dentro del período</p></div>
            <div class="card border-red-200 dark:border-red-900"><p class="text-xs font-bold uppercase tracking-wider text-red-700 dark:text-red-300">Facturas vencidas</p><p class="mt-3 text-3xl font-black text-red-700 dark:text-red-300">{{ $invoiceSummary['overdue_count'] }}</p><div class="mt-2 text-sm font-bold text-red-700 dark:text-red-300">@forelse($invoiceSummary['overdue_totals'] as $currency => $total){{ $currency }} {{ number_format($total, 2) }}@if(! $loop->last)<br>@endif @empty—@endforelse</div><p class="mt-1 text-xs text-muted">Requieren gestión inmediata</p></div>
        </div>
    </section>

    <section class="mt-6">
        <div class="mb-3 flex items-center justify-between"><h2 class="section-title">Cotizaciones y oportunidades</h2><a class="text-sm font-bold text-brand" href="{{ route('commercial.quotes.index') }}">Gestionar cotizaciones →</a></div>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="card"><p class="text-xs font-bold uppercase tracking-wider text-muted">Cotizaciones creadas</p><p class="mt-3 text-3xl font-black text-ink">{{ $quoteSummary['created_count'] }}</p><div class="mt-2 text-sm font-bold text-brand">@forelse($quoteSummary['created_totals'] as $currency => $total){{ $currency }} {{ number_format($total, 2) }}@if(! $loop->last)<br>@endif @empty—@endforelse</div><p class="mt-1 text-xs text-muted">Por fecha de emisión</p></div>
            <div class="card"><p class="text-xs font-bold uppercase tracking-wider text-muted">Oportunidades abiertas</p><p class="mt-3 text-3xl font-black text-amber-700 dark:text-amber-300">{{ $quoteSummary['open_count'] }}</p><div class="mt-2 text-sm font-bold text-amber-700 dark:text-amber-300">@forelse($quoteSummary['open_totals'] as $currency => $total){{ $currency }} {{ number_format($total, 2) }}@if(! $loop->last)<br>@endif @empty—@endforelse</div><p class="mt-1 text-xs text-muted">Creadas en el período</p></div>
            <div class="card"><p class="text-xs font-bold uppercase tracking-wider text-muted">Convertidas en factura</p><p class="mt-3 text-3xl font-black text-emerald-700 dark:text-emerald-300">{{ $quoteSummary['converted_count'] }}</p><div class="mt-2 text-sm font-bold text-emerald-700 dark:text-emerald-300">@forelse($quoteSummary['converted_totals'] as $currency => $total){{ $currency }} {{ number_format($total, 2) }}@if(! $loop->last)<br>@endif @empty—@endforelse</div><p class="mt-1 text-xs text-muted">Por fecha de conversión</p></div>
            <div class="card"><p class="text-xs font-bold uppercase tracking-wider text-muted">Tasa de conversión</p><p class="mt-3 text-3xl font-black text-ink">{{ $quoteSummary['conversion_rate'] }}%</p><div class="mt-4 h-1.5 overflow-hidden rounded-full bg-surface-soft"><div class="h-full bg-brand" style="width: {{ $quoteSummary['conversion_rate'] }}%"></div></div><p class="mt-2 text-xs text-muted">Convertidas entre las creadas</p></div>
        </div>
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="panel">
            <div class="mb-4 flex items-center justify-between"><div><h2 class="section-title">Facturas por gestionar</h2><p class="mt-1 text-xs text-muted">Pendientes y vencidas ordenadas por vencimiento.</p></div><a class="font-bold text-brand" href="{{ route('commercial.invoices.index', ['status' => 'pending']) }}">Ver todas →</a></div>
            @forelse($pendingInvoices as $invoice)
                <div class="row flex items-center justify-between gap-4"><div><p class="font-bold text-ink">{{ $invoice->number }} · {{ $invoice->client->name }}</p><p class="text-xs text-muted">Vence {{ $invoice->due_date->format('d/m/Y') }} · {{ $invoice->currency }} {{ number_format($invoice->total, 2) }}</p></div><a class="button-secondary min-h-9 px-3 py-1.5 text-xs" href="{{ route('commercial.invoices.edit', $invoice) }}">Gestionar</a></div>
            @empty <p class="muted">No hay facturas pendientes con vencimiento registrado.</p> @endforelse
        </section>
        <section class="panel">
            <div class="mb-4 flex items-center justify-between"><div><h2 class="section-title">Cotizaciones para seguimiento</h2><p class="mt-1 text-xs text-muted">Borradores, enviadas y vistas ordenadas por vencimiento.</p></div><a class="font-bold text-brand" href="{{ route('commercial.quotes.index') }}">Ver todas →</a></div>
            @forelse($openQuotesForFollowUp as $quote)
                <div class="row flex items-center justify-between gap-4"><div><p class="font-bold text-ink">{{ $quote->number }} · {{ $quote->client->name }}</p><p class="text-xs text-muted">Vence {{ $quote->due_date->format('d/m/Y') }} · {{ $quote->currency }} {{ number_format($quote->total, 2) }}</p></div><a class="button-secondary min-h-9 px-3 py-1.5 text-xs" href="{{ route('commercial.quotes.edit', $quote) }}">Editar</a></div>
            @empty <p class="muted">No hay cotizaciones abiertas con vencimiento registrado.</p> @endforelse
        </section>
    </div>
@endsection
