<div wire:poll.60s>
    <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-brand">Cola de trabajo diaria</p>
            <h1 class="text-2xl font-black tracking-tight text-ink sm:text-4xl">¿Qué debo gestionar hoy?</h1>
            <p class="mt-2 max-w-2xl text-sm text-muted">Cada tarjeta representa un servicio contratado que requiere una acción.</p>
        </div>
        <span class="inline-flex w-fit items-center gap-2 rounded-full bg-brand/10 px-3 py-2 text-xs font-bold text-brand"><span class="h-2 w-2 animate-pulse rounded-full bg-brand"></span> Actualización automática</span>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-7">
        <div class="card"><p class="text-xs font-bold uppercase tracking-[0.12em] text-muted">Pendientes</p><p class="mt-3 text-3xl font-black text-ink">{{ $stats['pending'] }}</p><p class="mt-1 text-xs text-muted">Servicios para trabajar</p></div>
        <div class="card border-brand/20 bg-brand/[0.03]"><p class="text-xs font-bold uppercase tracking-[0.12em] text-muted">Total por cobrar</p><p class="mt-3 text-2xl font-black text-brand">USD {{ number_format($stats['collectionTotal'], 2) }}</p><p class="mt-1 text-xs text-muted">Según los filtros aplicados</p></div>
        <div class="card"><p class="text-xs font-bold uppercase tracking-[0.12em] text-muted">Costo total</p><p class="mt-3 text-2xl font-black text-ink">USD {{ number_format($stats['costTotal'], 2) }}</p><p class="mt-1 text-xs text-muted">De los servicios filtrados</p></div>
        <div class="card"><p class="text-xs font-bold uppercase tracking-[0.12em] text-muted">Promesas hoy</p><p class="mt-3 text-3xl font-black text-orange-600">{{ $stats['promises'] }}</p><p class="mt-1 text-xs text-muted">Vencidas o para hoy</p></div>
        <div class="card"><p class="text-xs font-bold uppercase tracking-[0.12em] text-muted">Cobros vencidos</p><p class="mt-3 text-3xl font-black text-red-600">{{ $stats['overdue'] }}</p><p class="mt-1 text-xs text-muted">Requieren contacto</p></div>
        <div class="card"><p class="text-xs font-bold uppercase tracking-[0.12em] text-muted">Pagos por validar</p><p class="mt-3 text-3xl font-black text-blue-600">{{ $stats['pendingPayments'] }}</p><p class="mt-1 text-xs text-muted">Evidencia recibida</p></div>
        <div class="card"><p class="text-xs font-bold uppercase tracking-[0.12em] text-muted">En riesgo</p><p class="mt-3 text-3xl font-black text-amber-600">{{ $stats['risk'] }}</p><p class="mt-1 text-xs text-muted">Sin respuesta</p></div>
    </div>

    <div class="panel mb-6">
        <div class="grid gap-3 md:grid-cols-[1fr_180px_180px_180px_180px_auto]">
            <label class="relative"><span class="sr-only">Buscar cliente o servicio</span><span class="pointer-events-none absolute left-3 top-3 text-lg text-muted">⌕</span><input wire:model.live.debounce.300ms="search" class="input mt-0 pl-10" placeholder="Buscar cliente, servicio o IP..."></label>
            <select wire:model.live="provider" class="input mt-0"><option value="all">Todos los proveedores</option>@foreach($providers as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select>
            <select wire:model.live="serviceType" class="input mt-0"><option value="all">Todos los servicios</option>@foreach($catalogServices as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select>
            <select wire:model.live="status" class="input mt-0"><option value="active">Activos</option><option value="cancelled">Cancelados</option><option value="all">Todos los estados</option></select>
            <select wire:model.live="type" class="input mt-0"><option value="all">Todos los seguimientos</option>@foreach($types as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
            <button wire:click="clearFilters" class="button-secondary">Limpiar</button>
        </div>
        <div wire:loading class="mt-3 text-xs font-semibold text-brand">Actualizando la cola…</div>
    </div>

    <div class="space-y-4">
        @forelse($services as $service)
            @php
                $lastGestion = $service->gestions->first();
                $pendingCharge = $service->charges->filter(fn ($charge) => in_array($charge->status, [\App\Enums\ChargeStatus::Pending, \App\Enums\ChargeStatus::Partial, \App\Enums\ChargeStatus::Overdue], true))->sortBy('due_date')->first();
                $pendingPayments = $service->charges->flatMap->payments->filter(fn ($payment) => $payment->status === \App\Enums\PaymentStatus::Pending);
                $typeClasses = match ($service->follow_up_type) {
                    'promise_overdue', 'charge_overdue' => 'border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-950/20',
                    'promise_today', 'cancellation_risk' => 'border-orange-200 bg-orange-50 dark:border-orange-900 dark:bg-orange-950/20',
                    'payment_pending' => 'border-blue-200 bg-blue-50 dark:border-blue-900 dark:bg-blue-950/20',
                    default => 'border-line bg-surface',
                };
                $typeBadge = match ($service->follow_up_type) {
                    'promise_overdue', 'charge_overdue' => 'bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300',
                    'promise_today', 'cancellation_risk' => 'bg-orange-100 text-orange-700 dark:bg-orange-950/60 dark:text-orange-300',
                    'payment_pending' => 'bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300',
                    default => 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300',
                };
            @endphp
            <article class="rounded-2xl border p-5 shadow-card sm:p-6 {{ $typeClasses }}">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase {{ $typeBadge }}">{{ $types[$service->follow_up_type] }}</span>
                            <span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase {{ $service->status->value === 'active' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300' }}">{{ $service->status->value === 'active' ? 'Activo' : 'Cancelado' }}</span>
                        </div>
                        <h2 class="mt-3 text-xl font-black text-ink">{{ $service->client->name }}</h2>
                        <p class="mt-1 text-sm font-semibold text-muted">{{ $service->catalogService->name }} · {{ $service->provider->name }}</p>
                        <p class="mt-2 max-w-3xl text-sm text-ink"><span class="font-bold">Descripción:</span> {{ $service->observations ?: 'Sin descripción registrada' }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a class="button-secondary" href="https://wa.me/{{ preg_replace('/\D+/', '', $service->client->phone) }}" target="_blank" rel="noreferrer">WhatsApp</a>
                        <a class="button-secondary" href="tel:{{ $service->client->phone }}">Llamar</a>
                        <a class="button" href="{{ route('gestions.create', ['client_id' => $service->client_id, 'contracted_service_id' => $service->id]) }}">Registrar gestión</a>
                        @if($service->status->value === 'active')
                            <form method="post" action="{{ route('contracted-services.mark-paid', $service) }}" class="inline">@csrf<button class="button bg-emerald-600 hover:bg-emerald-700" onclick="return confirm('¿Confirmas que este servicio contratado fue pagado?')">✓ Marcar como pagado</button></form>
                        @endif
                    </div>
                </div>

                <div class="mt-5 grid gap-3 border-t border-current/10 pt-5 sm:grid-cols-2 lg:grid-cols-5">
                    <div><p class="text-xs font-bold uppercase tracking-[0.12em] text-muted">Monto pendiente</p><p class="mt-1 font-black text-ink">{{ $pendingCharge ? $pendingCharge->currency.' '.number_format($pendingCharge->amount, 2) : $service->price_currency.' '.number_format($service->price, 2) }}</p></div>
                    <div><p class="text-xs font-bold uppercase tracking-[0.12em] text-muted">Día de cobro</p><p class="mt-1 font-semibold text-ink">Día {{ $service->billing_day }} · {{ $service->billing_date->format('d/m/Y') }}</p></div>
                    @if ($service->follow_up_type === 'upcoming')
                        <div class="rounded-xl border border-brand/20 bg-brand/[0.03] px-3 py-2"><p class="text-xs font-bold uppercase tracking-[0.12em] text-muted">Próximo cobro</p><p class="mt-1 text-lg font-black text-brand">{{ $service->days_until_billing === 1 ? 'Falta 1 día' : 'Faltan '.$service->days_until_billing.' días' }} para el cobro</p></div>
                    @else
                        <div class="rounded-xl border px-3 py-2 {{ $service->overdue_days > 0 ? 'border-red-200 bg-red-100/70 dark:border-red-900 dark:bg-red-950/30' : 'border-line bg-surface/60' }}"><p class="text-xs font-bold uppercase tracking-[0.12em] {{ $service->overdue_days > 0 ? 'text-red-700 dark:text-red-300' : 'text-muted' }}">Días vencidos</p><p class="mt-1 text-lg font-black {{ $service->overdue_days > 0 ? 'text-red-700 dark:text-red-300' : 'text-ink' }}">{{ $service->overdue_days }} {{ $service->overdue_days === 1 ? 'día' : 'días' }}</p></div>
                    @endif
                    <div><p class="text-xs font-bold uppercase tracking-[0.12em] text-muted">Última gestión</p><p class="mt-1 font-semibold text-ink">{{ $lastGestion?->occurred_at?->format('d/m/Y H:i') ?? 'Sin gestión' }}</p><p class="text-xs text-muted">{{ $lastGestion?->result ?? 'Requiere primer contacto' }}</p></div>
                    <div><p class="text-xs font-bold uppercase tracking-[0.12em] text-muted">Próximo seguimiento</p><p class="mt-1 font-semibold text-ink">{{ $lastGestion?->next_follow_up_at?->format('d/m/Y H:i') ?? 'No programado' }}</p><p class="text-xs text-muted">{{ $service->ip ?: 'Sin IP registrada' }}</p></div>
                </div>

                <div class="mt-5 flex flex-col gap-3 border-t border-current/10 pt-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="inline-flex max-w-full flex-wrap items-center overflow-hidden rounded-xl border border-line bg-surface/70 divide-x divide-line">
                            @if($pendingCharge)<a class="px-3 py-2 text-sm font-bold text-brand transition hover:bg-brand/10 hover:underline" href="{{ route('payments.create', ['charge_id' => $pendingCharge->id]) }}">Registrar pago</a>@endif
                            @foreach($pendingPayments as $payment)<form method="post" action="{{ route('payments.validate', $payment) }}" class="inline">@csrf<button class="px-3 py-2 text-sm font-bold text-blue-700 transition hover:bg-blue-50 hover:underline dark:text-blue-300 dark:hover:bg-blue-950/30">Validar pago</button></form>@endforeach
                            <a class="px-3 py-2 text-sm font-bold text-brand transition hover:bg-brand/10 hover:underline" href="{{ route('gestions.create', ['client_id' => $service->client_id, 'contracted_service_id' => $service->id]) }}">Reprogramar seguimiento</a>
                            <a class="px-3 py-2 text-sm font-bold text-brand transition hover:bg-brand/10 hover:underline" href="{{ route('contracted-services.edit', $service) }}">Abrir detalle</a>
                        </div>
                    </div>
                    @if($service->status->value === 'active')
                        <form method="post" action="{{ route('contracted-services.cancel', $service) }}" class="flex gap-2">@csrf<input class="input mt-0 min-h-10 w-40" name="cancellation_reason" placeholder="Razón de cancelación" required><button class="text-sm font-bold text-red-600 hover:underline" onclick="return confirm('¿Cancelar este servicio?')">Cancelar</button></form>
                    @endif
                </div>
            </article>
        @empty
            <div class="panel py-16 text-center"><p class="text-lg font-black text-ink">No hay servicios pendientes de seguimiento.</p><p class="mt-2 text-sm text-muted">La cola está al día con los filtros seleccionados.</p></div>
        @endforelse
    </div>
</div>
