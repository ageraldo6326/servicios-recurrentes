@extends('layouts.app')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-brand">Comercial</p>
            <h1 class="text-2xl font-black tracking-tight text-ink sm:text-4xl">Facturas</h1>
            <p class="mt-2 text-sm text-muted">Da seguimiento al efectivo cobrado y a las facturas que requieren gestión.</p>
        </div>
        <a class="button" href="{{ route('commercial.invoices.create') }}">＋ Nueva factura</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card"><p class="text-xs font-bold uppercase tracking-wider text-muted">Emitidas</p><p class="mt-2 text-3xl font-black text-ink">{{ $invoiceSummary['issued_count'] }}</p><p class="mt-2 text-sm font-bold text-brand">@forelse($invoiceSummary['issued_totals'] as $currency => $total){{ $currency }} {{ number_format($total, 2) }}@if(! $loop->last)<br>@endif @empty—@endforelse</p></div>
        <div class="card"><p class="text-xs font-bold uppercase tracking-wider text-muted">Cobradas</p><p class="mt-2 text-3xl font-black text-emerald-700 dark:text-emerald-300">{{ $invoiceSummary['paid_count'] }}</p><p class="mt-2 text-sm font-bold text-emerald-700 dark:text-emerald-300">@forelse($invoiceSummary['paid_totals'] as $currency => $total){{ $currency }} {{ number_format($total, 2) }}@if(! $loop->last)<br>@endif @empty—@endforelse</p></div>
        <div class="card"><p class="text-xs font-bold uppercase tracking-wider text-muted">Pendientes</p><p class="mt-2 text-3xl font-black text-amber-700 dark:text-amber-300">{{ $invoiceSummary['pending_count'] }}</p><p class="mt-2 text-sm font-bold text-amber-700 dark:text-amber-300">@forelse($invoiceSummary['pending_totals'] as $currency => $total){{ $currency }} {{ number_format($total, 2) }}@if(! $loop->last)<br>@endif @empty—@endforelse</p></div>
        <div class="card border-red-200 dark:border-red-900"><p class="text-xs font-bold uppercase tracking-wider text-red-700 dark:text-red-300">Vencidas</p><p class="mt-2 text-3xl font-black text-red-700 dark:text-red-300">{{ $invoiceSummary['overdue_count'] }}</p><p class="mt-2 text-sm font-bold text-red-700 dark:text-red-300">@forelse($invoiceSummary['overdue_totals'] as $currency => $total){{ $currency }} {{ number_format($total, 2) }}@if(! $loop->last)<br>@endif @empty—@endforelse</p></div>
    </div>

    <form class="panel mt-6 grid gap-3 lg:grid-cols-6" method="get">
        <label class="lg:col-span-2 text-sm font-bold text-ink">Buscar<input class="input" name="search" value="{{ request('search') }}" placeholder="Número o cliente"></label>
        <label class="text-sm font-bold text-ink">Estado<select class="input" name="status"><option value="">Todos</option>@foreach(\App\Enums\CommercialInvoiceStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select></label>
        <label class="text-sm font-bold text-ink">Fecha por<select class="input" name="date_field"><option value="issue" @selected(request('date_field', 'issue') === 'issue')>Emisión</option><option value="due" @selected(request('date_field') === 'due')>Vencimiento</option><option value="paid" @selected(request('date_field') === 'paid')>Pago</option></select></label>
        <label class="text-sm font-bold text-ink">Desde<input class="input" type="date" name="date_from" value="{{ request('date_from', $from->toDateString()) }}"></label>
        <label class="text-sm font-bold text-ink">Hasta<input class="input" type="date" name="date_to" value="{{ request('date_to', $to->toDateString()) }}"></label>
        <div class="lg:col-span-6 flex justify-end"><button class="button">Aplicar filtros</button></div>
    </form>

    <div class="surface mt-5 overflow-hidden"><div class="overflow-x-auto"><table class="table"><thead><tr><th>Número</th><th>Cliente</th><th>Emisión</th><th>Vence</th><th>Total</th><th>Estado</th><th>Seguimiento</th><th></th></tr></thead><tbody>
        @forelse($invoices as $invoice)
            <tr><td class="font-bold text-ink">{{ $invoice->number }}</td><td>{{ $invoice->client->name }}</td><td>{{ $invoice->issue_date->format('d/m/Y') }}</td><td>{{ $invoice->due_date?->format('d/m/Y') ?? '—' }}</td><td class="font-bold">{{ $invoice->currency }} {{ number_format($invoice->total, 2) }}</td><td><span class="rounded-full px-2 py-1 text-xs font-bold {{ $invoice->status->value === 'paid' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300' : ($invoice->status->value === 'overdue' ? 'bg-red-100 text-red-800 dark:bg-red-950/40 dark:text-red-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300') }}">{{ $invoice->status->label() }}</span></td><td class="text-xs text-muted">@if($invoice->status->value === 'paid') Pagada {{ $invoice->paid_at?->format('d/m/Y') ?? 'sin fecha registrada' }} @elseif($invoice->due_date && $invoice->due_date->isPast()) Vencida: gestionar cobro @elseif($invoice->due_date) Gestionar antes del {{ $invoice->due_date->format('d/m/Y') }} @else Sin vencimiento @endif</td><td><a class="font-bold text-brand" href="{{ route('commercial.invoices.edit', $invoice) }}">Gestionar →</a></td></tr>
        @empty<tr><td colspan="8" class="py-12 text-center text-muted">No hay facturas que coincidan.</td></tr>@endforelse
    </tbody></table></div><div class="border-t border-line px-4 py-3">{{ $invoices->links() }}</div></div>
@endsection
