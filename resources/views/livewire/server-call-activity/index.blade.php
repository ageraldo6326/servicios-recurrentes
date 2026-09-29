<div wire:poll.60s>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-brand">Monitoreo de servidores</p>
            <h1 class="text-2xl font-black tracking-tight text-ink sm:text-4xl">Actividad de marcado</h1>
            <p class="mt-2 max-w-3xl text-sm text-muted">Distingue la inactividad real de llamadas de los servidores que dejaron de reportar. Los contadores avanzan en vivo y los datos se consultan cada 60 segundos.</p>
        </div>
        <div class="rounded-xl border border-line bg-surface px-4 py-3 text-xs text-muted">
            <span class="font-bold text-ink">Zona horaria:</span> {{ $timezone }}
            <span wire:loading class="ml-2 font-semibold text-brand">Actualizando…</span>
        </div>
    </div>

    <div class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-5">
        @foreach (\App\Enums\CallActivityState::cases() as $summaryState)
            <button wire:click="$set('state', '{{ $summaryState->value }}')" class="card p-4 text-left transition hover:border-brand {{ $state === $summaryState->value ? 'ring-2 ring-brand/20' : '' }}">
                <p class="text-[10px] font-black uppercase tracking-[0.14em] text-muted">{{ $summaryState->label() }}</p>
                <p class="mt-2 text-2xl font-black text-ink">{{ $summary[$summaryState->value] ?? 0 }}</p>
            </button>
        @endforeach
    </div>

    <div class="panel mb-5">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-[minmax(240px,1fr)_180px_220px_240px_auto]">
            <label class="relative">
                <span class="sr-only">Buscar cliente, servicio o IP</span>
                <span class="pointer-events-none absolute left-3 top-3 text-lg text-muted">⌕</span>
                <input wire:model.live.debounce.300ms="search" class="input mt-0 pl-10" placeholder="Cliente, servidor o IP…">
            </label>
            <select wire:model.live="platform" class="input mt-0">
                <option value="all">Todas las plataformas</option>
                <option value="vicidial">VICIdial</option>
                <option value="issabel">Issabel</option>
            </select>
            <select wire:model.live="state" class="input mt-0">
                <option value="all">Todos los estados</option>
                @foreach (\App\Enums\CallActivityState::cases() as $filterState)
                    <option value="{{ $filterState->value }}">{{ $filterState->label() }}</option>
                @endforeach
            </select>
            <select wire:model.live="sort" class="input mt-0">
                <option value="outbound">Mayor tiempo sin llamadas</option>
                <option value="report">Último reporte más antiguo</option>
            </select>
            <button wire:click="clearFilters" class="button-secondary">Limpiar</button>
        </div>
    </div>

    <div class="surface overflow-hidden">
        <div class="hidden overflow-x-auto lg:block">
            <table class="table min-w-[1360px]">
                <thead><tr><th>Cliente</th><th>Servidor</th><th>Plataforma</th><th>Último marcado saliente</th><th>Tiempo sin marcar</th><th>Último reporte</th><th>Estado</th><th>Umbral</th><th></th></tr></thead>
                <tbody>
                    @forelse ($services as $service)
                        @php($serviceState = $states[$service->id])
                        <tr wire:key="call-activity-{{ $service->id }}-{{ $service->callActivity?->last_outbound_at?->getTimestamp() ?? 'none' }}">
                            <td><p class="font-bold text-ink">{{ $service->client->name }}</p><p class="text-xs text-muted">Servicio #{{ $service->id }}</p></td>
                            <td><p class="font-semibold text-ink">{{ $service->catalogService->name }}</p><p class="font-mono text-xs text-muted">{{ $service->ip }}</p></td>
                            <td class="font-mono text-xs font-bold text-ink">{{ $service->call_monitoring_platform->label() }}</td>
                            <td>
                                @if ($service->callActivity?->last_outbound_at)
                                    <p class="font-semibold text-ink">{{ $this->localDate($service->callActivity->last_outbound_at) }}</p>
                                @else
                                    <p class="font-semibold text-muted">Sin llamadas registradas</p>
                                @endif
                            </td>
                            <td>
                                @if ($service->callActivity?->last_outbound_at)
                                    <div
                                        x-data="callActivityElapsed(@js($serverNowEpoch), @js($service->callActivity->last_outbound_at->getTimestamp()))"
                                        class="inline-flex min-w-32 items-center rounded-xl border border-brand/20 bg-brand/[0.06] px-3 py-2"
                                        title="Tiempo calculado desde el último intento saliente">
                                        <span class="font-mono text-base font-black tabular-nums text-brand" x-text="label">{{ $elapsedLabels[$service->id] }}</span>
                                    </div>
                                    <p class="mt-1 text-[10px] font-bold uppercase tracking-wider text-muted">Actualización en vivo</p>
                                @else
                                    <span class="font-semibold text-muted">Sin llamadas</span>
                                @endif
                            </td>
                            <td>
                                @if ($service->callActivity?->last_reported_at)
                                    <p class="font-semibold text-ink">{{ $this->localDate($service->callActivity->last_reported_at) }}</p>
                                    <p class="text-xs text-muted">{{ $this->elapsed($service->callActivity->last_reported_at) }}</p>
                                @else
                                    <p class="font-semibold text-muted">Nunca</p>
                                @endif
                            </td>
                            <td><span title="{{ $serviceState->description() }}" class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-black uppercase {{ $serviceState->badgeClasses() }}">{{ $serviceState->label() }}</span><p class="mt-1 max-w-48 text-xs text-muted">{{ $serviceState->description() }}</p></td>
                            <td class="text-xs text-muted"><p><span class="font-bold text-ink">{{ $service->inactivity_threshold_hours }} h</span> sin marcado</p><p><span class="font-bold text-ink">{{ $service->report_delay_threshold_hours }} h</span> sin reporte</p></td>
                            <td class="whitespace-nowrap"><button wire:click="openHistory({{ $service->id }})" class="font-bold text-brand hover:underline">Historial</button><a href="{{ route('contracted-services.edit', $service) }}" class="ml-3 font-bold text-ink hover:underline">Configurar</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="py-12 text-center text-muted">No hay servidores monitoreados que coincidan con los filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="divide-y divide-line lg:hidden">
            @forelse ($services as $service)
                @php($serviceState = $states[$service->id])
                <article class="p-4" wire:key="call-activity-mobile-{{ $service->id }}-{{ $service->callActivity?->last_outbound_at?->getTimestamp() ?? 'none' }}">
                    <div class="flex items-start justify-between gap-3"><div><p class="font-bold text-ink">{{ $service->client->name }}</p><p class="text-sm text-muted">{{ $service->catalogService->name }} · {{ $service->ip }}</p></div><span class="shrink-0 rounded-full px-2 py-1 text-[9px] font-black uppercase {{ $serviceState->badgeClasses() }}">{{ $serviceState->label() }}</span></div>
                    <p class="mt-2 text-xs text-muted">{{ $serviceState->description() }}</p>
                    <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div><p class="text-xs text-muted">Plataforma</p><p class="font-bold text-ink">{{ $service->call_monitoring_platform->label() }}</p></div>
                        <div><p class="text-xs text-muted">Umbral</p><p class="font-bold text-ink">{{ $service->inactivity_threshold_hours }} h</p></div>
                        <div><p class="text-xs text-muted">Último marcado</p><p class="text-ink">{{ $service->callActivity?->last_outbound_at ? $this->localDate($service->callActivity->last_outbound_at) : 'Sin llamadas' }}</p></div>
                        <div><p class="text-xs text-muted">Último reporte</p><p class="text-ink">{{ $service->callActivity?->last_reported_at ? $this->localDate($service->callActivity->last_reported_at) : 'Nunca' }}</p></div>
                        <div class="col-span-2 rounded-xl border border-brand/20 bg-brand/[0.06] p-3">
                            <p class="text-[10px] font-black uppercase tracking-[0.14em] text-muted">Tiempo sin marcar</p>
                            @if ($service->callActivity?->last_outbound_at)
                                <div x-data="callActivityElapsed(@js($serverNowEpoch), @js($service->callActivity->last_outbound_at->getTimestamp()))">
                                    <p class="mt-1 font-mono text-lg font-black tabular-nums text-brand" x-text="label">{{ $elapsedLabels[$service->id] }}</p>
                                    <p class="mt-1 text-[10px] font-bold uppercase tracking-wider text-muted">Actualización en vivo</p>
                                </div>
                            @else
                                <p class="mt-1 font-bold text-muted">Sin llamadas</p>
                            @endif
                        </div>
                    </div>
                    <div class="mt-4 flex gap-4"><button wire:click="openHistory({{ $service->id }})" class="font-bold text-brand">Ver historial</button><a href="{{ route('contracted-services.edit', $service) }}" class="font-bold text-ink">Configurar</a></div>
                </article>
            @empty
                <div class="p-10 text-center text-muted">No hay servidores monitoreados que coincidan con los filtros.</div>
            @endforelse
        </div>

        <div class="border-t border-line px-4 py-3">{{ $services->links() }}</div>
    </div>

    @if ($historyService)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/50 p-0 sm:items-center sm:p-6" wire:click.self="closeHistory">
            <section class="max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-t-2xl border border-line bg-surface shadow-2xl sm:rounded-2xl">
                <header class="sticky top-0 flex items-start justify-between gap-4 border-b border-line bg-surface p-5">
                    <div><p class="text-xs font-bold uppercase tracking-[0.16em] text-brand">Últimos 20 reportes</p><h2 class="mt-1 text-xl font-black text-ink">{{ $historyService->client->name }}</h2><p class="text-sm text-muted">{{ $historyService->catalogService->name }} · {{ $historyService->ip }}</p></div>
                    <button wire:click="closeHistory" class="button-secondary min-h-9 px-3" aria-label="Cerrar historial">×</button>
                </header>
                <div class="overflow-x-auto">
                    <table class="table min-w-[720px]">
                        <thead><tr><th>Recibido</th><th>Marcado informado</th><th>Resultado</th><th>IP de origen</th></tr></thead>
                        <tbody>
                            @forelse ($historyReports as $report)
                                <tr><td>{{ $this->localDate($report->received_at) }}</td><td>{{ $report->reported_last_outbound_at ? $this->localDate($report->reported_last_outbound_at) : 'Sin llamadas registradas' }}</td><td><span class="rounded-full px-2 py-1 text-[10px] font-black uppercase {{ $report->updated_last_outbound ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">{{ $report->updated_last_outbound ? 'Marcado actualizado' : 'Sin cambio' }}</span></td><td class="font-mono text-xs">{{ $report->source_ip ?: '—' }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="py-10 text-center text-muted">Aún no se han recibido reportes.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    @endif
</div>
