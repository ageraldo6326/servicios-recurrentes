<div class="financial-agenda-dashboard">
    @if ($successMessage)
        <div
            class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">
            {{ $successMessage }}</div>
    @endif

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-brand">Gestión de Compromisos</p>
            <h1 class="text-2xl font-black tracking-tight text-ink sm:text-4xl">¿Qué debo pagar y cuándo?</h1>
            <p class="mt-2 text-sm text-muted">Compromisos ordenados automáticamente por prioridad.</p>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row"><a wire:navigate
                href="{{ route('financial-agenda.commitments.index') }}" class="button-secondary">Ver todos</a><a
                wire:navigate href="{{ route('financial-agenda.beneficiaries.index') }}" class="button-secondary">Beneficiarios</a><a
                wire:navigate href="{{ route('financial-agenda.commitments.create') }}" class="button">＋ Nuevo
                compromiso</a></div>
    </div>

    <div class="mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <div class="card">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-muted">Compromisos visibles</p>
            <p class="mt-2 text-3xl font-black text-ink">{{ $summary['total'] }}</p>
        </div>
        <div class="card border-red-200 dark:border-red-900">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-red-600">Vencidos</p>
            <p class="mt-2 text-3xl font-black text-red-600">{{ $summary['overdue'] }}</p>
        </div>
        <div class="card border-amber-200 dark:border-amber-900">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-amber-600">Vencen hoy</p>
            <p class="mt-2 text-3xl font-black text-amber-600">{{ $summary['today'] }}</p>
        </div>
        <div class="card border-emerald-200 dark:border-emerald-900">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-emerald-700">Pagados</p>
            <p class="mt-2 text-3xl font-black text-emerald-700">{{ $summary['paid'] }}</p>
        </div>
        <div class="card border-brand/30">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-brand">Monto sugerido</p>
            <p class="mt-2 text-2xl font-black text-ink">{{ number_format($summary['total_amount'], 2) }}</p>
        </div>
    </div>

    @if ($cardAlerts->isNotEmpty())
        <section class="panel mb-5 border-brand/30">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div><p class="text-xs font-black uppercase tracking-[0.14em] text-brand">Alertas financieras</p><h2 class="mt-1 text-xl font-black text-ink">{{ $cardAlerts->count() }} tarjetas con estrategia activa</h2><p class="mt-1 text-sm text-muted">Consulta cortes, pagos y ventanas de compra en su dashboard especializado.</p></div><a wire:navigate href="{{ route('financial-agenda.cards.dashboard') }}" class="button">Ver tarjetas →</a></div>
        </section>
    @endif

    <div class="mb-5 grid gap-5 lg:grid-cols-[1.35fr_.65fr]">
        <div class="card border-brand/30">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div><p class="text-xs font-black uppercase tracking-[0.14em] text-brand">Resultado mensual</p><p class="mt-2 text-3xl font-black text-brand">Beneficio en DOP: {{ $monthlyBenefitDop === null ? 'Configura la tasa' : 'RD$ '.number_format($monthlyBenefitDop, 2) }}</p><p class="mt-2 text-xl font-black text-ink">Después de compromisos: {{ $monthlyBenefitDop === null ? '—' : 'RD$ '.number_format($monthlyBenefitDop - $summary['total_amount'], 2) }}</p><p class="mt-2 text-xs font-semibold text-muted">Beneficio base de servicios: USD {{ number_format($monthlyBenefitUsd, 2) }}</p></div>
            </div>
        </div>

    <details class="panel group">
        <summary class="flex cursor-pointer list-none items-center justify-between gap-4"><div><p class="section-title">Configurar tasa del dólar</p><p class="mt-1 text-xs text-muted">La tasa actual es {{ $currentRate ? 'RD$ '.number_format((float) $currentRate->rate, 4).' por USD' : 'pendiente' }}.</p></div><span class="button-secondary px-3 py-2 text-xs group-open:bg-brand group-open:text-white">Administrar tasa</span></summary>
        <div class="mt-5 border-t border-line pt-5"><div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"><div><h2 class="section-title">Historial de tasas</h2><p class="mt-1 text-xs text-muted">Registra la tasa DOP por USD para convertir servicios contratados.</p></div><form wire:submit="saveExchangeRate" class="flex flex-col gap-2 sm:flex-row sm:items-end"><label class="text-xs font-bold text-muted">Tasa<input wire:model="exchangeRate" type="number" min="0.0001" step="0.0001" class="input w-full sm:w-32" placeholder="RD$ / USD"></label><label class="text-xs font-bold text-muted">Vigencia<input wire:model="exchangeRateDate" type="date" class="input"></label><button class="button" type="submit">Guardar tasa</button></form></div>
        @if($rateMessage)<p class="mb-3 text-sm font-semibold text-emerald-700">{{ $rateMessage }}</p>@endif
        @error('exchangeRate')<p class="mb-2 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        @error('exchangeRateDate')<p class="mb-2 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        <div class="overflow-x-auto"><table class="table min-w-[520px]"><thead><tr><th>Fecha de vigencia</th><th>Tasa DOP por USD</th></tr></thead><tbody>@forelse($rateHistory as $rate)<tr><td>{{ $rate->effective_date->format('d/m/Y') }}</td><td class="font-bold text-ink">RD$ {{ number_format((float) $rate->rate, 4) }}</td></tr>@empty<tr><td colspan="2" class="text-sm text-muted">No hay tasas registradas.</td></tr>@endforelse</tbody></table></div>
        </div>
    </details>
    </div>

    <div class="panel mb-5">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <select wire:model.live="period" class="input mt-0">
                <option value="today">Hoy</option>
                <option value="week">Esta semana</option>
                <option value="month">Este mes</option>
                <option value="custom">Rango personalizado</option>
            </select>
            <select wire:model.live="status" class="input mt-0">
                <option value="all">Todos los estados</option>
                <option value="projected">Proyectados</option>
                <option value="pending">Pendientes</option>
                <option value="paid">Pagados</option>
                <option value="overdue">Vencidos</option>
            </select>
            <select wire:model.live="beneficiaryId" class="input mt-0">
                <option value="">Todos los beneficiarios</option>
                @foreach ($beneficiaries as $beneficiary)
                    <option value="{{ $beneficiary->id }}">{{ $beneficiary->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="category" class="input mt-0">
                <option value="">Todas las categorías</option>
                @foreach ($categories as $category)
                    <option value="{{ $category }}">{{ $category }}</option>
                @endforeach
            </select>
            <button type="button" wire:click="clearFilters" class="button-secondary">Limpiar filtros</button>
        </div>
        @if ($period === 'custom')
            <div class="mt-3 grid gap-3 sm:grid-cols-2"><label class="text-sm font-bold text-ink">Desde<input
                        wire:model.live="customStart" type="date" class="input"></label><label
                    class="text-sm font-bold text-ink">Hasta<input wire:model.live="customEnd" type="date"
                        class="input"></label></div>
        @endif
        <div wire:loading class="mt-3 text-xs font-semibold text-brand">Actualizando agenda…</div>
    </div>

    <div class="surface overflow-hidden">
        <div class="hidden overflow-x-auto lg:block">
            <table class="table min-w-[1180px]">
                <thead>
                    <tr>
                        <th>Prioridad</th>
                        <th>Compromiso</th>
                        <th>Beneficiario</th>
                        <th>Categoría</th>
                        <th>Fecha de corte</th>
                        <th>Fecha límite</th>
                        <th>Días para corte</th>
                        <th>Días para pago</th>
                        <th>Monto sugerido</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @php($paidSeparatorShown = false)
                    @forelse($commitments as $item)
                        @php($commitment = $item['commitment']) @php($agenda = $item['agenda'])
                        @if ($agenda['is_paid'] && !$paidSeparatorShown)
                            <tr>
                                <td colspan="11"
                                    class="border-y-2 border-brand/40 bg-brand/5 px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-brand">
                                    Compromisos pagados</td>
                            </tr>
                            @php($paidSeparatorShown = true)
                        @endif
                        @php($trafficClass = $agenda['is_paid'] ? 'bg-emerald-500 ring-emerald-200 dark:ring-emerald-900' : ($agenda['status']->value === 'overdue' ? 'bg-red-500 ring-red-200 dark:ring-red-900' : ($agenda['due_days'] !== null && $agenda['due_days'] <= 5 ? 'bg-amber-400 ring-amber-200 dark:ring-amber-900' : 'bg-sky-500 ring-sky-200 dark:ring-sky-900')))
                        @if ($agenda['is_paid'])
                            @php($trafficClass = 'bg-brand ring-brand/30 dark:ring-brand/50')
                        @endif
                        @php($statusLabel = match ($agenda['status']->value) {
                            'projected' => 'Proyectado',
                            'pending' => 'Pendiente',
                            'partially_paid' => 'Pago parcial',
                            'paid' => 'Pagado',
                            'overdue' => 'Vencido',
                            'cancelled' => 'Cancelado',
                            default => $agenda['status']->value,
                        })
                        @php($dueClass = match (true) {
                            $agenda['is_paid'] => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300',
                            $agenda['status']->value === 'projected' => 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-800/40 dark:text-slate-300',
                            $agenda['due_days'] !== null && $agenda['due_days'] <= 5 => 'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300',
                            $agenda['due_days'] !== null && $agenda['due_days'] <= 10 => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300',
                            default => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300',
                        })
                        <tr>
                            <td><span class="inline-block h-4 w-4 rounded-full ring-4 {{ $trafficClass }}"
                                    title="{{ $agenda['is_paid'] ? ($agenda['payment_timing_label'] ?: 'Pagado') : ($agenda['status']->value === 'overdue' ? 'Pago vencido' : 'Obligación pendiente') }}"
                                    aria-label="{{ $agenda['is_paid'] ? 'Pagado' : 'Riesgo de pago' }}"></span></td>
                            <td>
                                <p class="font-bold text-ink">{{ $commitment->name }}</p>
                                <p class="text-xs text-muted">{{ $agenda['payment_timing_label'] ?: ($agenda['reminder'] ?: 'Sin recordatorio activo') }}
                                </p>
                                <p class="text-xs text-muted">Período {{ $agenda['period_start']->format('m/Y') }}</p>
                                @if ($item['card'])<p class="mt-1 text-xs font-black text-brand">💳 {{ $item['card']['efficiency']->label() }} · ~{{ $item['card']['estimated_days_to_pay'] }} días</p>@endif
                            </td>
                            <td class="text-sm text-muted">{{ $commitment->beneficiary->name }}</td>
                            <td class="text-sm text-muted">{{ $commitment->category }}</td>
                            <td class="text-sm text-sky-700 dark:text-sky-300">
                                {{ $agenda['cutoff_date']?->format('d/m/Y') ?: 'No aplica' }}</td>
                            <td class="text-sm font-black text-ink">{{ $agenda['due_date']->format('d/m/Y') }}</td>
                            <td class="text-sm text-sky-700 dark:text-sky-300">{{ $agenda['cutoff_label'] ?: '—' }}
                            </td>
                            <td><span
                                    class="inline-flex rounded-lg border px-2.5 py-1 text-xs font-black {{ $dueClass }}">{{ $agenda['due_label'] }}</span>
                            </td>
                            <td class="text-sm font-semibold text-ink">
                                {{ $agenda['expected_amount'] !== null ? number_format($agenda['expected_amount'], 2) : '—' }}
                                @if ($agenda['balance'] !== null && $agenda['balance'] > 0)<span class="block text-xs font-normal text-muted">Saldo {{ number_format($agenda['balance'], 2) }}</span>@endif
                            </td>
                            <td><span
                                    class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase {{ $agenda['is_paid'] ? 'bg-emerald-100 text-emerald-700' : ($agenda['status']->value === 'overdue' ? 'bg-red-100 text-red-700' : ($agenda['status']->value === 'projected' ? 'bg-slate-100 text-slate-700' : ($agenda['status']->value === 'partially_paid' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700'))) }}">{{ $statusLabel }}</span>
                            </td>
                            <td>
                                <div class="flex items-center gap-2 whitespace-nowrap">@if (!$agenda['is_paid'])<button type="button"
                                        wire:click="openPaymentForm({{ $agenda['occurrence']->id }})"
                                        class="button min-h-9 px-3 py-1 text-xs">Registrar pago</button>@endif<a wire:navigate
                                        class="button-secondary min-h-9 px-3 py-1 text-xs"
                                        href="{{ route('financial-agenda.commitments.edit', $commitment) }}">Editar</a>
                                </div>
                            </td>
                        </tr>
                    @empty<tr>
                            <td colspan="11" class="py-12 text-center text-sm text-muted">No hay compromisos que
                                coincidan con los filtros seleccionados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="divide-y divide-line lg:hidden">
            @php($paidSeparatorShown = false)
            @forelse($commitments as $item)
                @php($commitment = $item['commitment']) @php($agenda = $item['agenda'])
                @if ($agenda['is_paid'] && !$paidSeparatorShown)
                    <div
                        class="border-y-2 border-brand/40 bg-brand/5 px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-brand">
                        Compromisos pagados</div>
                    @php($paidSeparatorShown = true)
                @endif
                @php($mobileDueClass = $agenda['is_paid'] ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : ($agenda['status']->value === 'overdue' ? 'border-red-200 bg-red-50 text-red-700' : ($agenda['status']->value === 'projected' ? 'border-slate-200 bg-slate-50 text-slate-700' : ($agenda['due_days'] !== null && $agenda['due_days'] <= 5 ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-sky-200 bg-sky-50 text-sky-700'))))
                @if ($agenda['is_paid'])
                    @php($mobileDueClass = 'border-brand/30 bg-brand/10 text-brand')
                @endif
                <article class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-bold text-ink">{{ $commitment->name }}</p>
                            <p class="text-sm text-muted">{{ $commitment->beneficiary->name }} ·
                                {{ $commitment->category }}</p>
                            @if ($item['card'])<p class="mt-1 text-xs font-black text-brand">💳 {{ $item['card']['efficiency']->label() }} · ~{{ $item['card']['estimated_days_to_pay'] }} días</p>@endif
                        </div><span
                            class="rounded-full px-2 py-1 text-[10px] font-black uppercase {{ $agenda['is_paid'] ? 'bg-emerald-100 text-emerald-700' : ($agenda['status']->value === 'overdue' ? 'bg-red-100 text-red-700' : ($agenda['status']->value === 'projected' ? 'bg-slate-100 text-slate-700' : 'bg-amber-100 text-amber-700')) }}">{{ $statusLabel }}</span>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <p class="text-xs text-muted">Prioridad</p>
                            <p class="font-semibold text-ink">{{ ucfirst($agenda['priority']->value) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted">Monto sugerido</p>
                            <p class="font-semibold text-ink">
                                {{ $agenda['expected_amount'] !== null ? number_format($agenda['expected_amount'], 2) : '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-muted">Corte</p>
                            <p class="font-semibold text-sky-700 dark:text-sky-300">
                                {{ $agenda['cutoff_label'] ?: 'No aplica' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted">Pago</p><span
                                class="mt-1 inline-flex rounded-lg border px-2 py-1 text-xs font-black {{ $mobileDueClass }}">{{ $agenda['due_label'] }}</span>
                        </div>
                    </div>@if (!$agenda['is_paid'])<button type="button" wire:click="openPaymentForm({{ $agenda['occurrence']->id }})"
                        class="button mt-4 w-full text-xs">Registrar pago</button>@endif<a wire:navigate
                        class="button-secondary mt-2 w-full text-xs"
                        href="{{ route('financial-agenda.commitments.edit', $commitment) }}">Editar compromiso</a>
                </article>
            @empty<div class="p-10 text-center text-sm text-muted">No hay compromisos que coincidan con los filtros
                    seleccionados.</div>
            @endforelse
        </div>
    </div>

    @if ($paymentOccurrenceId)
        <div class="fixed inset-0 z-50 grid place-items-center bg-ink/50 p-4" role="dialog" aria-modal="true">
            <div class="panel w-full max-w-lg" wire:click.stop>
                <div class="mb-5 flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.14em] text-brand">estión de Compromisos</p>
                        <h2 class="mt-1 text-xl font-black text-ink">Registrar pago</h2>
                        <p class="mt-1 text-sm text-muted">El pago se aplicará únicamente a la obligación seleccionada.</p>
                    </div><button type="button" wire:click="closePaymentForm" class="button-secondary px-3"
                        aria-label="Cerrar">×</button>
                </div>
                <form wire:submit="savePayment" class="space-y-4"><label
                        class="block text-sm font-bold text-ink">Fecha del pago<input wire:model="paymentDate"
                            type="date" class="input" required>
                        @error('paymentDate')
                            <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="block text-sm font-bold text-ink">Monto pagado<input wire:model="amountPaid"
                            type="number" min="0" step="0.01" class="input">
                        @error('amountPaid')
                            <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="block text-sm font-bold text-ink">Observaciones
                        <textarea wire:model="paymentObservations" class="input min-h-24"></textarea>
                        @error('paymentObservations')
                            <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="block text-sm font-bold text-ink">Comprobante <span
                            class="font-normal text-muted">(opcional: JPG, PNG o PDF)</span><input
                            wire:model="receipt" type="file" accept=".jpg,.jpeg,.png,.pdf" class="input">
                        @error('receipt')
                            <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>
                        @enderror
                    </label>
                    <div class="flex flex-col gap-3 sm:flex-row"><button class="button" type="submit"
                            wire:loading.attr="disabled">Guardar pago</button><button class="button-secondary"
                            type="button" wire:click="closePaymentForm">Cancelar</button></div>
                </form>
            </div>
        </div>
    @endif
</div>
